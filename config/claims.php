<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Claim Packet Templates
    |--------------------------------------------------------------------------
    |
    | The three DOCX templates must live in this directory. Keep the exact
    | filenames used by ClaimPacketGenerator. The CSF page is filled by a
    | separate system and is not merged by this application.
    |
    */

    'templates_path' => env('CLAIM_TEMPLATES_PATH', storage_path('app/templates')),

    /*
    |--------------------------------------------------------------------------
    | Generated Prints
    |--------------------------------------------------------------------------
    */

    'prints_path' => env('CLAIM_PRINTS_PATH', storage_path('app/prints')),

    /*
    |--------------------------------------------------------------------------
    | LibreOffice
    |--------------------------------------------------------------------------
    |
    | On Windows this is usually:
    | C:\Program Files\LibreOffice\program\soffice.exe
    |
    | It must be reachable from the user that runs the web server / queue
    | worker. "soffice" only works when it is already on the PATH.
    |
    */

    'libreoffice_binary' => env('LIBREOFFICE_BINARY', 'soffice'),

    /*
    |--------------------------------------------------------------------------
    | Referral Codes
    |--------------------------------------------------------------------------
    |
    | Format: {prefix}-{MMDDYYYY}-{daily sequence}
    | Example: CHO-10242024-01
    |
    */

    'referral_prefix' => env('CLAIM_REFERRAL_PREFIX', 'CHO'),

    /*
    |--------------------------------------------------------------------------
    | Dental Claim Service Codes
    |--------------------------------------------------------------------------
    |
    | A patient is normally entitled to two dental services per calendar year.
    | The OPH01 series covers the first service and the OPH02 series covers the
    | second. The value is the visit number used for the yearly benefit label.
    |
    */

    'visit_codes' => [
        'OPH01' => 1,
        'OPH01A' => 1,
        'OPH01B' => 1,
        'OPH02' => 2,
        'OPH02A' => 2,
        'OPH02B' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider / Encoder Labels
    |--------------------------------------------------------------------------
    */

    'provider_designation' => env('CLAIM_PROVIDER_DESIGNATION', 'Dentist'),

    'encoder_designation' => env('CLAIM_ENCODER_DESIGNATION', 'Encoder'),

    'encoder_name' => env('CLAIM_ENCODER_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Seeded Administrator
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'first_name' => env('ADMIN_FIRST_NAME', 'System'),
        'last_name' => env('ADMIN_LAST_NAME', 'Administrator'),
        'designation' => env('ADMIN_DESIGNATION', 'Administrator'),
        'email' => env('ADMIN_EMAIL', 'admin@dental.local'),
        'password' => env('ADMIN_PASSWORD', 'Admin@12345'),
    ],

];
