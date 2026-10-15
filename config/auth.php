<?php

return [
    // Table and class used for authenticated users. The class is filled from the row by
    // matching constructor parameter names to columns; the password column is never passed.
    'table' => 'users',
    'user' => App\Data\User::class,

    // Column used to log in, and the column holding the password hash.
    'identifier' => 'email',
    'password' => 'password',

    // API tokens: table name and lifetime in minutes (null = never expire).
    'tokens_table' => 'api_tokens',
    'token_ttl' => 60 * 24 * 30,
];
