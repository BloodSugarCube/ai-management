<?php

$extra = env('GENAPI_EXTRA_JSON_BODY');
$decodedExtra = null;
if (is_string($extra) && $extra !== '') {
    $decodedExtra = json_decode($extra, true);
}

return [
    'base_url' => rtrim(env('GENAPI_BASE_URL', 'https://api.gen-api.ru'), '/'),
    'api_key' => env('GENAPI_API_KEY', ''),
    'timeout' => (int) env('GENAPI_HTTP_TIMEOUT', 120),
    'verify_ssl' => filter_var(env('GENAPI_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    'ca_bundle' => env('GENAPI_CA_BUNDLE'),
    'step1_network_id' => env('GENAPI_STEP1_NETWORK_ID'),
    'step2_network_id' => env('GENAPI_STEP2_NETWORK_ID'),
    'is_async' => filter_var(env('GENAPI_IS_ASYNC', false), FILTER_VALIDATE_BOOLEAN),
    'extra_json_body' => is_array($decodedExtra) ? $decodedExtra : [],
];
