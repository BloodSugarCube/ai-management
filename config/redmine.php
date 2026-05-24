<?php

return [
    'base_url' => rtrim(env('REDMINE_BASE_URL', ''), '/'),
    'api_key' => env('REDMINE_API_KEY', ''),
    'verify_ssl' => env('REDMINE_VERIFY_SSL', true),
    'timeout' => (int) env('REDMINE_HTTP_TIMEOUT', 60),
    'page_size' => (int) env('REDMINE_PAGE_SIZE', 100),
    'issue_status_filter' => env('REDMINE_ISSUE_STATUS_FILTER', 'open'),
    'in_progress_status_names' => array_filter(array_map('trim', explode('|', env('REDMINE_IN_PROGRESS_STATUS_NAMES', 'In progress|В работе|Started')))),
];
