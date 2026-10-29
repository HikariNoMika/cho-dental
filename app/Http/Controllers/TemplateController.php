<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class TemplateController extends Controller
{
    private const TEMPLATES = [
        'referral' => 'Referral_Form System.docx',
        'claim_1' => 'PhilHealth_Claim_Form_1 System.docx',
        'claim_2' => 'PhilHealth_Claim_Form_2 System.docx',
    ];

    public function index(): View
    {
        $path = config('claims.templates_path', storage_path('app/templates'));

        return view('templates.index', [
            'templates' => $this->templateStatus($path),
            'path' => $path,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'referral' => ['nullable', 'file', 'mimes:docx', 'max:10240'],
            'claim_1' => ['nullable', 'file', 'mimes:docx', 'max:10240'],
            'claim_2' => ['nullable', 'file', 'mimes:docx', 'max:10240'],
        ]);

        $path = config('claims.templates_path', storage_path('app/templates'));
        File::ensureDirectoryExists($path);

        foreach (self::TEMPLATES as $input => $filename) {
            if (! $request->hasFile($input)) {
                continue;
            }

            $file = $request->file($input);
            $destination = $path.DIRECTORY_SEPARATOR.$filename;
            $backup = $path.DIRECTORY_SEPARATOR.$filename.'.bak-'.now()->format('YmdHis');

            if (is_file($destination)) {
                @copy($destination, $backup);
            }

            $file->move($path, $filename);
        }

        return redirect()->route('templates.index')->with('status', 'Templates updated.');
    }

    public function placeholders(): View
    {
        $path = base_path('docs/template-placeholders.md');
        $markdown = is_file($path) ? File::get($path) : '';

        return view('templates.placeholders', [
            'markdown' => $markdown,
        ]);
    }
    private function templateStatus(string $path): array
    {
        $status = [];

        foreach (self::TEMPLATES as $key => $filename) {
            $full = $path.DIRECTORY_SEPARATOR.$filename;

            $status[] = [
                'key' => $key,
                'filename' => $filename,
                'exists' => is_file($full),
                'updated_at' => is_file($full) ? date('Y-m-d H:i', filemtime($full)) : null,
                'size' => is_file($full) ? filesize($full) : null,
            ];
        }

        return $status;
    }
}
