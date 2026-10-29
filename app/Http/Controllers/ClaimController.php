<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Services\ClaimPacketGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClaimController extends Controller
{
    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function __construct(
        private readonly ClaimPacketGenerator $claimPacketGenerator
    ) {}

    /**
     * Show the downloadable DOCX forms for a visit.
     */
    public function forms(int $visit_id): View
    {
        return view('claims.forms', [
            'visit' => $this->findVisit($visit_id),
        ]);
    }

    /**
     * Download one filled form as a DOCX file.
     */
    public function downloadForm(int $visit_id, string $form): BinaryFileResponse
    {
        $file = $this->claimPacketGenerator->generateForm(
            $this->findVisit($visit_id),
            $form
        );

        return response()->download($file['path'], $file['filename'], [
            'Content-Type' => self::DOCX_MIME,
        ]);
    }

    /**
     * Download all three filled forms in one ZIP archive.
     */
    public function downloadArchive(int $visit_id): BinaryFileResponse
    {
        $file = $this->claimPacketGenerator->generateFormsArchive(
            $this->findVisit($visit_id)
        );

        return response()->download($file['path'], $file['filename'], [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Keep the old print URL working by sending users to the form downloads.
     */
    public function print(int $visit_id): RedirectResponse
    {
        return redirect()->route('claims.forms', $visit_id);
    }

    private function findVisit(int $visit_id): Visit
    {
        return Visit::query()
            ->with(['patient.member', 'provider', 'dentalServices'])
            ->findOrFail($visit_id);
    }
}
