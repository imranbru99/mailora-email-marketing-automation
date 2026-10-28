<?php

return [
    'mode' => env('APP_MODE', 'self-hosted'),
    'demo' => (bool) env('APP_DEMO', false),
    'sandbox' => (bool) env('MAIL_SANDBOX', true),
    'contact_seed_count' => (int) env('DEMO_CONTACTS', 25000),
    'presets' => ['E-commerce store', 'SaaS / Software company', 'Marketing agency', 'Publisher / Newsletter creator', 'Non-profit / Fundraising', 'Education / Course creator', 'Real estate', 'Restaurant / Hospitality', 'Healthcare / Clinic', 'Events / Webinars', 'Local services', 'Enterprise', 'Custom'],
];
