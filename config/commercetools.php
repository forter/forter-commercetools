<?php
/**
 * Forter Commercetools app
 */

return [
    'project_key' => env('CTP_PROJECT_KEY'),
    'client_secret' => env('CTP_CLIENT_SECRET'),
    'client_id' => env('CTP_CLIENT_ID'),
    'region' => env('CTP_REGION'),
    'auth_url' => env('CTP_AUTH_URL', 'https://auth.' . env('CTP_REGION') . '.commercetools.com'),
    'api_url' => env('CTP_API_URL', 'https://api.' . env('CTP_REGION') . '.commercetools.com'),
    'scopes' => env('CTP_SCOPES'),
];
