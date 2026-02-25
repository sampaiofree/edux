<?php

return [
    'enabled' => env('TRACKING_INTERNAL_ENABLED', true),

    // Max events accepted per request batch.
    'batch_max' => 40,

    'visitor_cookie' => env('TRACKING_VISITOR_COOKIE', 'edux_vid'),
    'visitor_cookie_days' => (int) env('TRACKING_VISITOR_COOKIE_DAYS', 365),

    'session_cookie' => env('TRACKING_SESSION_COOKIE', 'edux_sid'),
    'session_cookie_days' => (int) env('TRACKING_SESSION_COOKIE_DAYS', 30),
    'session_idle_minutes' => (int) env('TRACKING_SESSION_IDLE_MINUTES', 30),
];

