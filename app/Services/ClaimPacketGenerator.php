<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\Visit;
use Carbon\CarbonInterface;
use DateTimeInterface;
use iio\libmergepdf\Merger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use ZipArchive;

class ClaimPacketGenerator
{
    private const REFERRAL_TEMPLATE = 'Referral_Form System.docx';

    private const CLAIM_FORM_1_TEMPLATE = 'PhilHealth_Claim_Form_1 System.docx';

    private const CLAIM_FORM_2_TEMPLATE = 'PhilHealth_Claim_Form_2 System.docx';

    /**
     * PHPWord stores the macro delimiters statically. Some tutorial snippets
     * show setMacroChars() as a static call, but in current PHPWord releases
     * it is an instance method.
     *
     * The templates use {{ placeholder }} (two braces on each side), so the
     * opening delimiter must be "{{", not "{". Using a single brace leaves
     * stray braces in the output and breaks PHPWord's split-run repair.
     */
    private static bool $macroCharsConfigured = false;

    private string $templatePath;

    private string $printsPath;

    private string $libreOfficeBinary;

    private string $referralPrefix;

    public function __construct()
    {
        $this->templatePath = rtrim(
            (string) config('claims.templates_path', storage_path('app/templates')),
            '\\/'
        );

        $this->printsPath = rtrim(
            (string) config('claims.prints_path', storage_path('app/prints')),
            '\\/'
        );

        $this->libreOfficeBinary = (string) config('claims.libreoffice_binary', 'soffice');
        $this->referralPrefix = (string) config('claims.referral_prefix', 'CHO');
    }

    /**
     * Build the complete, merged claim packet and return the PDF path.
     */
    public function generate(Visit $visit): string
    {
        $this->prepareVisit($visit);

        $workingDirectory = $this->makeWorkingDirectory($visit);

        try {
            $docxFiles = [
                $this->fillReferralForm($visit, $workingDirectory),
                $this->fillClaimFormOne($visit, $workingDirectory),
                $this->fillClaimFormTwo($visit, $workingDirectory),
            ];

            $pdfFiles = [];

            foreach ($docxFiles as $docxFile) {
                $pdfFiles[] = $this->convertToPdf($docxFile, $workingDirectory);
            }

            /*
             * Annex E page 3 (CSF) is filled by a separate system, so it is
             * intentionally not merged here. This packet contains only the
             * referral form and Annex E pages 1 and 2.
             */
            return $this->mergePdfs($pdfFiles, $visit);
        } finally {
            File::deleteDirectory($workingDirectory);
        }
    }

    /**
     * Fill one claim form and return its DOCX path and download name.
     *
     * @return array{path: string, filename: string}
     */
    public function generateForm(Visit $visit, string $form): array
    {
        $this->prepareVisit($visit);

        return $this->fillForm($visit, $form, $this->makeDocxDirectory($visit));
    }

    /**
     * Fill all three forms and package them in one ZIP archive.
     *
     * @return array{path: string, filename: string}
     */
    public function generateFormsArchive(Visit $visit): array
    {
        $this->prepareVisit($visit);

        $directory = $this->makeDocxDirectory($visit);
        $forms = [];

        foreach (['referral', 'claim-1', 'claim-2'] as $form) {
            $forms[] = $this->fillForm($visit, $form, $directory);
        }

        $patient = $visit->patient;
        $patientName = $patient ? $this->fullName($patient) : '';

        $filename = 'Claim-Forms-'.$this->safeFilename(
            trim($patientName.'-'.$visit->referral_code ?: 'visit-'.$visit->id, '-')
        ).'.zip';

        if ($filename === 'Claim-Forms-.zip' || $filename === 'Claim-Forms-.zip') {
            $filename = 'Claim-Forms-'.$this->safeFilename(
                $visit->referral_code ?: 'visit-'.$visit->id
            ).'.zip';
        }

        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        $archive = new ZipArchive;

        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Unable to create DOCX archive [{$path}].");
        }

        foreach ($forms as $file) {
            $archive->addFile($file['path'], $file['filename']);
        }

        $archive->close();

        return ['path' => $path, 'filename' => $filename];
    }

    /**
     * @return array{path: string, filename: string}
     */
    private function fillForm(Visit $visit, string $form, string $directory): array
    {
        $path = match ($form) {
            'referral' => $this->fillReferralForm($visit, $directory),
            'claim-1' => $this->fillClaimFormOne($visit, $directory),
            'claim-2' => $this->fillClaimFormTwo($visit, $directory),
            default => throw new \InvalidArgumentException("Unknown claim form [{$form}]."),
        };

        $prefix = match ($form) {
            'referral' => 'Referral-',
            'claim-1' => 'Claim-Form-1-',
            'claim-2' => 'Claim-Form-2-',
        };

        return [
            'path' => $path,
            'filename' => $prefix.$this->safeFilename(
                $visit->referral_code ?: 'visit-'.$visit->id
            ).'.docx',
        ];
    }

    /**
     * Generate and persist the daily referral transaction code.
     *
     * Format: CHO-MMDDYYYY-NN
     * Example: CHO-10242024-01
     */
    public function ensureReferralCode(Visit $visit): string
    {
        if (filled($visit->referral_code)) {
            return $visit->referral_code;
        }

        $date = $this->serviceDate($visit);
        $codePrefix = $this->referralPrefix.'-'.$date->format('mdY').'-';

        /*
         * Continue after the highest sequence already used for this date.
         * Counting existing visits would skip numbers (or reuse one) when an
         * older visit has no code yet.
         */
        $sequence = Visit::query()
            ->where('referral_code', 'like', $codePrefix.'%')
            ->pluck('referral_code')
            ->map(fn ($code) => (int) substr((string) $code, strlen($codePrefix)))
            ->max() ?? 0;

        $sequence++;
        $code = $this->buildReferralCode($date, $sequence);

        /*
         * Two visits printed out of order could otherwise receive the same
         * number. Keep incrementing until this date's code is unique.
         */
        while (
            Visit::query()
                ->where('referral_code', $code)
                ->whereKeyNot($visit->getKey())
                ->exists()
        ) {
            $sequence++;
            $code = $this->buildReferralCode($date, $sequence);
        }

        $visit->referral_code = $code;
        $visit->save();

        return $code;
    }

    /**
     * Fill the referral form. Available template variables:
     * referral_code, date_today, patient_name, age, sex, opd_assigned,
     * op_designation, encoder_name, e_designation.
     */
    private function fillReferralForm(Visit $visit, string $workingDirectory): string
    {
        $template = $this->loadTemplate(self::REFERRAL_TEMPLATE);

        $provider = $visit->provider;

        $values = [
            'referral_code' => $visit->referral_code,
            'date_today' => $this->formatDate($this->serviceDate($visit)),
            'patient_name' => $this->fullName($visit->patient),
            'age' => (string) ($visit->patient->age ?? ''),
            'sex' => $this->upper($visit->patient->sex),
            'opd_assigned' => $this->providerName($provider),
            'op_designation' => (string) config('claims.provider_designation', 'Dentist'),
            'encoder_name' => $this->encoderName($provider),
            'e_designation' => $this->encoderDesignation(),
            'claim_code' => (string) $visit->claim_code,
        ];

        $this->setValuesIfPresent($template, $values);
        $this->insertSignatureIfAvailable($template, $provider);

        return $this->saveTemplate($template, $workingDirectory, 'referral-form');
    }

    /**
     * Fill Annex E page 1 (Claim Form 1).
     *
     * Part I is always the PhilHealth member. Part II is only filled when the
     * patient is a dependent; when the patient is the member we intentionally
     * leave every Part II placeholder blank.
     */
    private function fillClaimFormOne(Visit $visit, string $workingDirectory): string
    {
        $template = $this->loadTemplate(self::CLAIM_FORM_1_TEMPLATE);

        $patient = $visit->patient;
        $isDependent = ! $patient->is_member;
        $member = $isDependent ? $patient->memberProfile() : $patient;
        $provider = $visit->provider;
        $providerName = $this->providerName($provider);

        if (! $member) {
            throw new RuntimeException(
                "Patient [{$patient->id}] is a dependent but has no linked member or manual member details."
            );
        }

        /*
         * The real templates use mixed-case names in Part I (P_Lastname,
         * P_firstname, ...). The uppercase variants are included as well so
         * templates using the convention from the project guide keep working.
         */
        $values = array_merge(
            [
                'philid' => $this->formatPin($member->philhealth_pin),
                'bdate' => $this->formatDate($member->dob),
                'p_barangay' => $this->upper($member->barangay),
                'p_city' => $this->upper($member->city),
                'p_province' => $this->upper($member->province),
                'gender' => $this->upper($member->sex),

                'chk_member_yes' => $this->checkbox($patient->is_member),
                'chk_member_no' => $this->checkbox(! $patient->is_member),
                'chk_Member' => $this->checkbox($patient->is_member),
                'chk_member' => $this->checkbox($patient->is_member),

                'D_PIN' => (string) $provider->pan,
                'date_now' => now()->format('m-d-Y'),
                'claim_code' => (string) $visit->claim_code,
            ],
            $this->namePlaceholders($member, 'P'),
            $this->namePlaceholders($provider, 'D', $providerName),
            $this->dependentPartTwoPlaceholders($patient, $isDependent),
            $this->pinBoxPlaceholders($isDependent ? $patient->philhealth_pin : null)
        );

        $this->setValuesIfPresent($template, $values);
        $this->insertSignatureIfAvailable($template, $provider);

        return $this->saveTemplate($template, $workingDirectory, 'claim-form-1');
    }

    /**
     * Fill Annex E page 2 (Claim Form 2).
     *
     * The dental service table is cloned for each service. If a visit has no
     * services we still emit one empty row so Word/LibreOffice keeps the
     * table structure valid.
     */
    private function fillClaimFormTwo(Visit $visit, string $workingDirectory): string
    {
        $template = $this->loadTemplate(self::CLAIM_FORM_2_TEMPLATE);

        $values = [
            'P_fullname' => Str::upper($visit->patient->claimSignatoryName()),
            'D_fullname' => $this->providerName($visit->provider),
            'date_now' => now()->format('m-d-Y'),
            'referral_code' => $visit->referral_code,
            'service_date' => $this->formatDate($this->serviceDate($visit)),
            'date_provision' => $this->formatDate($this->serviceDate($visit)),
            'working_dx' => (string) $visit->working_dx,
            'final_dx' => (string) $visit->final_dx,
            'disposition' => $this->upper($visit->disposition),
            'claim_code' => (string) $visit->claim_code,
        ];

        $this->setValuesIfPresent($template, $values);
        $this->fillDentalServiceRows($template, $visit);
        $this->insertSignatureIfAvailable($template, $visit->provider);

        return $this->saveTemplate($template, $workingDirectory, 'claim-form-2');
    }

    /**
     * Clone {{tooth_number}} / {{dental_service}} for every service.
     */
    private function fillDentalServiceRows(TemplateProcessor $template, Visit $visit): void
    {
        if (
            ! $this->hasPlaceholder($template, 'tooth_number')
            || ! $this->hasPlaceholder($template, 'dental_service')
        ) {
            // The bundled template does not contain this row yet. Nothing to clone.
            return;
        }

        $services = $visit->dentalServices;

        if ($services->isEmpty()) {
            $template->cloneRow('tooth_number', 1);
            $template->setValue('tooth_number#1', '');
            $template->setValue('dental_service#1', '');

            return;
        }

        $template->cloneRow('tooth_number', $services->count());

        foreach ($services->values() as $index => $service) {
            $row = $index + 1;

            $template->setValue('tooth_number#'.$row, (string) $service->tooth_number);
            $template->setValue('dental_service#'.$row, (string) $service->service_code);
        }
    }

    /**
     * Name fields shared by patients and providers.
     */
    private function namePlaceholders(
        Patient|Provider $person,
        string $prefix,
        ?string $fullName = null
    ): array {
        $lastName = $this->upper($person->last_name);
        $firstName = $this->upper($person->first_name);
        $middleName = $this->upper($person->middle_name);
        $extension = $this->upper($person->name_ext);
        $fullName ??= $this->fullName($person);

        return [
            // Real template casing (e.g. P_Lastname, D_LASTNAME).
            $prefix.'_Lastname' => $lastName,
            $prefix.'_firstname' => $firstName,
            $prefix.'_ext' => $extension,
            $prefix.'_middlename' => $middleName,
            $prefix.'_FULLNAME' => $fullName,
            $prefix.'_fullname' => $fullName,

            // Uppercase variants used by the project guide.
            $prefix.'_LASTNAME' => $lastName,
            $prefix.'_FIRSTNAME' => $firstName,
            $prefix.'_EXT' => $extension,
            $prefix.'_MIDDLENAME' => $middleName,
        ];
    }

    /**
     * Part II placeholders. All values are blank when the patient is the
     * member. The DEP_* names are used by dependent-aware templates; the real
     * bundled form currently has no Part II placeholders, so they are ignored.
     */
    private function dependentPartTwoPlaceholders(Patient $patient, bool $isDependent): array
    {
        $lastName = $isDependent ? $this->upper($patient->last_name) : '';
        $firstName = $isDependent ? $this->upper($patient->first_name) : '';
        $middleName = $isDependent ? $this->upper($patient->middle_name) : '';
        $extension = $isDependent ? $this->upper($patient->name_ext) : '';
        $fullName = $isDependent ? $this->fullName($patient) : '';
        $pin = $isDependent ? $this->formatPin($patient->philhealth_pin) : '';
        $birthDate = $isDependent ? $this->formatDate($patient->dob) : '';
        $sex = $isDependent ? $this->upper($patient->sex) : '';
        $relationship = $isDependent ? $this->upper($patient->relationship) : '';

        return [
            'DEP_LASTNAME' => $lastName,
            'DEP_FIRSTNAME' => $firstName,
            'DEP_MIDDLENAME' => $middleName,
            'DEP_EXT' => $extension,
            'DEP_FULLNAME' => $fullName,
            'DEP_PIN' => $pin,
            'DEP_BDATE' => $birthDate,
            'DEP_SEX' => $sex,
            'RELATIONSHIP' => $relationship,
            'chk_child' => $this->checkbox($isDependent && $relationship === 'CHILD'),
            'chk_parent' => $this->checkbox($isDependent && $relationship === 'PARENT'),
            'chk_spouse' => $this->checkbox($isDependent && $relationship === 'SPOUSE'),
            'chk_sex_male' => $this->checkbox($isDependent && $sex === 'MALE'),
            'chk_sex_female' => $this->checkbox($isDependent && $sex === 'FEMALE'),
        ];
    }

    /**
     * Map each PIN digit to pin_1 ... pin_12. Passing null blanks the boxes.
     */
    private function pinBoxPlaceholders(?string $pin): array
    {
        $digits = $this->pinDigits($pin);
        $values = [];

        for ($position = 1; $position <= 12; $position++) {
            $values['pin_'.$position] = $digits[$position - 1] ?? '';
        }

        return $values;
    }

    /**
     * Add the provider's signature image if the template has a matching
     * placeholder and the image path can be resolved.
     */
    private function insertSignatureIfAvailable(TemplateProcessor $template, ?Provider $provider): void
    {
        if (! $provider || blank($provider->signature_path)) {
            return;
        }

        $signaturePath = $this->resolveSignaturePath($provider->signature_path);

        if ($signaturePath === null) {
            return;
        }

        foreach (['signature', 'D_signature', 'provider_signature'] as $placeholder) {
            if (! $this->hasPlaceholder($template, $placeholder)) {
                continue;
            }

            $template->setImageValue($placeholder, [
                'path' => $signaturePath,
                'width' => 70,
                'height' => 25,
                'ratio' => true,
            ]);

            return;
        }
    }

    /**
     * Resolve a signature path whether it is absolute, relative to storage/app
     * or on the public disk.
     */
    private function resolveSignaturePath(string $path): ?string
    {
        $candidates = [
            $path,
            storage_path('app/'.$path),
            storage_path('app/public/'.$path),
            public_path($path),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return null;
    }

    /**
     * Load a DOCX and activate {{ double-curly-brace }} macros.
     *
     * PHPWord repairs macros that Word split across multiple runs during the
     * TemplateProcessor constructor. Because the delimiters are static, a
     * throw-away load is used to set them before the real load.
     */
    private function loadTemplate(string $filename): TemplateProcessor
    {
        $path = $this->templatePath.DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            throw new RuntimeException("Claim template not found: [{$path}]");
        }

        if (! self::$macroCharsConfigured) {
            $primer = new TemplateProcessor($path);
            $primer->setMacroChars('{{', '}}');
            $primerTempFile = $primer->getTempDocumentFilename();
            unset($primer);

            if (is_file($primerTempFile)) {
                @unlink($primerTempFile);
            }

            self::$macroCharsConfigured = true;
        }

        $template = new TemplateProcessor($path);
        $template->setMacroChars('{{', '}}');

        return $template;
    }

    /**
     * Only call setValue() for placeholders that actually exist in the DOCX.
     */
    private function setValuesIfPresent(TemplateProcessor $template, array $values): void
    {
        $available = $template->getVariables();

        foreach ($values as $placeholder => $value) {
            if (! in_array($placeholder, $available, true)) {
                continue;
            }

            $template->setValue($placeholder, (string) ($value ?? ''));
        }
    }

    private function hasPlaceholder(TemplateProcessor $template, string $placeholder): bool
    {
        return in_array($placeholder, $template->getVariables(), true);
    }

    private function saveTemplate(
        TemplateProcessor $template,
        string $workingDirectory,
        string $name
    ): string {
        $path = $workingDirectory.DIRECTORY_SEPARATOR.$name.'.docx';
        $template->saveAs($path);

        return $path;
    }

    /**
     * Convert a DOCX to PDF with LibreOffice in headless mode.
     */
    private function convertToPdf(string $docxPath, string $workingDirectory): string
    {
        $pdfPath = $workingDirectory.DIRECTORY_SEPARATOR
            .pathinfo($docxPath, PATHINFO_FILENAME)
            .'.pdf';

        /*
         * A per-conversion LibreOffice profile allows requests to run without
         * fighting over the normal desktop profile lock.
         */
        $profileDirectory = $workingDirectory.DIRECTORY_SEPARATOR.'libreoffice-profile';
        File::ensureDirectoryExists($profileDirectory);

        $profileUri = 'file:///'.str_replace('\\', '/', $profileDirectory);

        $command = sprintf(
            '%s -env:UserInstallation=%s --headless --convert-to pdf --outdir %s %s 2>&1',
            $this->quoteShellArgument($this->libreOfficeBinary),
            $this->quoteShellArgument($profileUri),
            $this->quoteShellArgument($workingDirectory),
            $this->quoteShellArgument($docxPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($pdfPath)) {
            throw new RuntimeException(
                'LibreOffice failed to convert ['.$docxPath.']. '
                .'Binary: ['.$this->libreOfficeBinary.']. '
                .'Command output: '.implode(' ', $output)
            );
        }

        return $pdfPath;
    }

    /**
     * Merge the PDFs in print order and save the packet.
     */
    private function mergePdfs(array $pdfFiles, Visit $visit): string
    {
        File::ensureDirectoryExists($this->printsPath);

        $merger = new Merger;

        foreach ($pdfFiles as $pdfFile) {
            if (! is_file($pdfFile)) {
                throw new RuntimeException("Cannot merge missing PDF: [{$pdfFile}]");
            }

            $merger->addFile($pdfFile);
        }

        $filename = 'claim-packet-'.$this->safeFilename(
            $visit->referral_code ?: 'visit-'.$visit->id
        ).'.pdf';

        $outputPath = $this->printsPath.DIRECTORY_SEPARATOR.$filename;

        File::put($outputPath, $merger->merge());

        return $outputPath;
    }

    private function assertTemplatesExist(): void
    {
        $templates = [
            self::REFERRAL_TEMPLATE,
            self::CLAIM_FORM_1_TEMPLATE,
            self::CLAIM_FORM_2_TEMPLATE,
        ];

        foreach ($templates as $template) {
            $path = $this->templatePath.DIRECTORY_SEPARATOR.$template;

            if (! is_file($path)) {
                throw new RuntimeException(
                    "Missing claim packet template: [{$path}]. "
                    ."Place all three DOCX files in [{$this->templatePath}]."
                );
            }
        }
    }

    private function prepareVisit(Visit $visit): void
    {
        $visit->loadMissing(['patient.member', 'provider', 'dentalServices']);

        if (! $visit->patient) {
            throw new RuntimeException("Visit [{$visit->id}] does not have a patient.");
        }

        if (! $visit->provider) {
            throw new RuntimeException("Visit [{$visit->id}] does not have a provider.");
        }

        $this->assertTemplatesExist();
        $this->ensureReferralCode($visit);
    }

    private function makeWorkingDirectory(Visit $visit): string
    {
        $directory = storage_path('app/tmp/claims/'.$visit->id.'-'.Str::uuid());
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    private function makeDocxDirectory(Visit $visit): string
    {
        $directory = $this->printsPath.DIRECTORY_SEPARATOR
            .'docx'.DIRECTORY_SEPARATOR
            .$this->safeFilename($visit->referral_code ?: 'visit-'.$visit->id);

        File::ensureDirectoryExists($directory);

        return $directory;
    }

    private function buildReferralCode(CarbonInterface $date, int $sequence): string
    {
        return sprintf(
            '%s-%s-%02d',
            $this->referralPrefix,
            $date->format('mdY'),
            $sequence
        );
    }

    private function serviceDate(Visit $visit): CarbonInterface
    {
        if ($visit->service_date instanceof CarbonInterface) {
            return $visit->service_date;
        }

        if (filled($visit->service_date)) {
            return Carbon::parse($visit->service_date);
        }

        return $visit->created_at ?? now();
    }

    private function fullName(Patient|Provider|null $person): string
    {
        if (! $person) {
            return '';
        }

        $parts = array_filter([
            $person->first_name,
            $person->middle_name,
            $person->last_name,
            $person->name_ext,
        ], fn ($part) => filled($part));

        return Str::upper(
            trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '')
        );
    }

    private function providerName(Provider $provider): string
    {
        $name = $this->fullName($provider);
        $credentials = $this->upper($provider->credentials);

        return $credentials !== '' ? $name.', '.$credentials : $name;
    }

    private function formatDate(DateTimeInterface|string|null $value, string $format = 'm-d-Y'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format($format);
        }

        return Carbon::parse($value)->format($format);
    }

    private function pinDigits(?string $pin): string
    {
        return preg_replace('/\D+/', '', (string) $pin) ?? '';
    }

    /**
     * Format a 12-digit PhilHealth PIN as 99-999999999-9.
     */
    private function formatPin(?string $pin): string
    {
        $digits = $this->pinDigits($pin);

        if (strlen($digits) === 12) {
            return substr($digits, 0, 2)
                .'-'.substr($digits, 2, 9)
                .'-'.substr($digits, 11, 1);
        }

        return $digits;
    }

    private function checkbox(bool $checked): string
    {
        return $checked ? '☑' : '☐';
    }

    private function upper(?string $value): string
    {
        return Str::upper((string) $value);
    }

    private function encoderName(?Provider $provider): string
    {
        $user = auth()->user();

        if ($user instanceof User) {
            return Str::upper($user->fullName());
        }

        $configured = config('claims.encoder_name');

        if (filled($configured)) {
            return (string) $configured;
        }

        return $provider ? $this->providerName($provider) : '';
    }

    private function encoderDesignation(): string
    {
        $user = auth()->user();

        if ($user instanceof User && filled($user->designation)) {
            return (string) $user->designation;
        }

        return (string) config('claims.provider_designation', 'Dentist');
    }

    private function safeFilename(string $value): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?? '';
        $safe = trim($safe, '-_.');

        return $safe !== '' ? $safe : 'claim';
    }

    private function quoteShellArgument(string $value): string
    {
        return escapeshellarg($value);
    }
}
