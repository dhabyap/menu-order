<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Credentials
    |--------------------------------------------------------------------------
    |
    | Credential source of truth for the admin dashboard login. The password
    | may be stored as plain text or as a bcrypt/argon hash; hashed values
    | are verified with the Hash contract automatically.
    |
    */

    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),
];
