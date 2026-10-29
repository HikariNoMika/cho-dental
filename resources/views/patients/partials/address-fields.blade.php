@php
    $patient = $patient ?? null;
    $address = config('address');
    $regionValue = old('region', $patient?->region ?? $address['defaults']['region']);
    $provinceValue = old('province', $patient?->province ?? $address['defaults']['province']);
    $cityValue = old('city', $patient?->city ?? $address['defaults']['city']);
    $barangayValue = old('barangay', $patient?->barangay ?? '');
    $contactValue = old('contact_number', $patient?->contact_number ?? '');
    $barangays = $address['barangays'];
@endphp

<div>
    <x-input-label for="region" value="Region" />
    <x-text-input id="region" class="block mt-1 w-full" type="text" name="region" :value="$regionValue" list="region-options" placeholder="Region XII (SOCCSKSARGEN)" />
    <datalist id="region-options">
        @foreach ($address['regions'] as $region)
            <option value="{{ $region }}"></option>
        @endforeach
    </datalist>
    <x-input-error :messages="$errors->get('region')" class="mt-2" />
</div>

<div>
    <x-input-label for="province" value="Province" />
    <x-text-input id="province" class="block mt-1 w-full" type="text" name="province" :value="$provinceValue" list="province-options" placeholder="South Cotabato" />
    <datalist id="province-options">
        @foreach ($address['provinces'] as $province)
            <option value="{{ $province }}"></option>
        @endforeach
    </datalist>
    <x-input-error :messages="$errors->get('province')" class="mt-2" />
</div>

<div>
    <x-input-label for="city" value="City/Municipality" />
    <x-text-input id="city" class="block mt-1 w-full" type="text" name="city" :value="$cityValue" list="city-options" placeholder="Koronadal City" />
    <datalist id="city-options">
        @foreach ($address['cities'] as $city)
            <option value="{{ $city }}"></option>
        @endforeach
    </datalist>
    <x-input-error :messages="$errors->get('city')" class="mt-2" />
</div>

<div>
    <x-input-label for="barangay" value="Barangay" />
    <select id="barangay" name="barangay" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        <option value="">Select Barangay</option>
        @if (filled($barangayValue) && ! in_array($barangayValue, $barangays, true))
            <option value="{{ $barangayValue }}" selected>{{ $barangayValue }} (current)</option>
        @endif
        @foreach ($barangays as $barangay)
            <option value="{{ $barangay }}" @selected($barangayValue === $barangay)>{{ $barangay }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-500">Koronadal City barangays only.</p>
    <x-input-error :messages="$errors->get('barangay')" class="mt-2" />
</div>

<div>
    <x-input-label for="contact_number" value="Contact Number" />
    <x-text-input id="contact_number" class="block mt-1 w-full" type="tel" name="contact_number" :value="$contactValue" maxlength="30" placeholder="09XX XXX XXXX" />
    <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
</div>
