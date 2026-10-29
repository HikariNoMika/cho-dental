<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderController extends Controller
{
    public function index(): View
    {
        $providers = Provider::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('providers.index', compact('providers'));
    }

    public function create(): View
    {
        return view('providers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_ext' => ['nullable', 'string', 'max:32'],
            'credentials' => ['nullable', 'string', 'max:32'],
            'pan' => ['required', 'string', 'max:30'],
            'signature_path' => ['nullable', 'string', 'max:255'],
        ]);

        Provider::create($validated);

        return redirect()->route('providers.index')->with('status', 'Dentist/Provider created.');
    }

    public function show(Provider $provider): View
    {
        $provider->load('visits');

        return view('providers.show', compact('provider'));
    }

    public function edit(Provider $provider): View
    {
        return view('providers.edit', compact('provider'));
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_ext' => ['nullable', 'string', 'max:32'],
            'credentials' => ['nullable', 'string', 'max:32'],
            'pan' => ['required', 'string', 'max:30'],
            'signature_path' => ['nullable', 'string', 'max:255'],
        ]);

        $provider->update($validated);

        return redirect()->route('providers.index')->with('status', 'Dentist/Provider updated.');
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        $provider->delete();

        return redirect()->route('providers.index')->with('status', 'Dentist/Provider deleted.');
    }
}
