<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Address
    |--------------------------------------------------------------------------
    |
    | Most patients of the clinic live in Koronadal City, South Cotabato.
    | These values pre-fill the patient form while still allowing the
    | encoder to type a different region, province, or city.
    |
    */

    'defaults' => [
        'region' => 'Region XII (SOCCSKSARGEN)',
        'province' => 'South Cotabato',
        'city' => 'Koronadal City',
    ],

    /*
    |--------------------------------------------------------------------------
    | Region XII (SOCCSKSARGEN)
    |--------------------------------------------------------------------------
    |
    | Suggestions for the free-text region, province, and city inputs.
    | The fields use HTML datalists, so encoders may still enter a value
    | that is not on these lists.
    |
    */

    'regions' => [
        'Region XII (SOCCSKSARGEN)',
    ],

    'provinces' => [
        'South Cotabato',
        'Cotabato',
        'Sultan Kudarat',
        'Sarangani',
        'General Santos City',
    ],

    'cities' => [
        'Koronadal City',
        'Banga',
        'General Santos City',
        'Kidapawan City',
        'Lake Sebu',
        'Norala',
        'Polomolok',
        'Santo Niño',
        'Surallah',
        'T\'Boli',
        'Tacurong City',
        'Tampakan',
        'Tantangan',
        'Tupi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Koronadal City Barangays
    |--------------------------------------------------------------------------
    |
    | Barangay is a fixed dropdown because the clinic only accepts
    | Koronadal City addresses. Existing records with a different
    | barangay keep their value when edited (see the address partial).
    |
    */

    'barangays' => [
        'Assumption',
        'Avanceña',
        'Cacub',
        'Caloocan',
        'Carpenter Hill',
        'Concepcion',
        'Esperanza',
        'General Paulino Santos',
        'Mabini',
        'Magsaysay',
        'Mambucal',
        'Morales',
        'Namnama',
        'New Pangasinan',
        'Paraiso',
        'Rotonda',
        'San Isidro',
        'San Roque',
        'San Jose',
        'Santa Cruz',
        'Santo Niño',
        'Saravia',
        'Topland',
        'Zone 1',
        'Zone 2',
        'Zone 3',
        'Zone 4',
    ],

];
