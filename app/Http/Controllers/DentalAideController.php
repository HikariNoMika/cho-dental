<?php

namespace App\Http\Controllers;

use App\Models\DentalAide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DentalAideController extends Controller
{
    public function index(): View
    {
        $dentalAides = DentalAide::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('dental-aides.index', compact('dentalAides'));
    }

    public function create(): View
    {
        return view('dental-aides.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_ext' => ['nullable', 'string', 'max:32'],
        ]);

        DentalAide::create($validated);

        return redirect()->route('dental-aides.index')->with('status', 'Dental aide created.');
    }

    public function show(DentalAide $dentalAide): View
    {
        return view('dental-aides.show', compact('dentalAide'));
    }

    public function edit(DentalAide $dentalAide): View
    {
        return view('dental-aides.edit', compact('dentalAide'));
    }

    public function update(Request $request, DentalAide $dentalAide): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_ext' => ['nullable', 'string', 'max:32'],
        ]);

        $dentalAide->update($validated);

        return redirect()->route('dental-aides.index')->with('status', 'Dental aide updated.');
    }

    public function destroy(DentalAide $dentalAide): RedirectResponse
    {
        $dentalAide->delete();

        return redirect()->route('dental-aides.index')->with('status', 'Dental aide deleted.');
    }
}
