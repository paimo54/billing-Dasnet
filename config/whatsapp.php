<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Configure WhatsApp gateway settings for sending notifications
    |
    */

    'enabled' => env('WHATSAPP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | Default WhatsApp provider to use (fonnte, wablas, woowa, twilio)
    |
    */

    'default_provider' => env('WHATSAPP_DEFAULT_PROVIDER', 'fonnte'),

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'fonnte' => [
            'api_key' => env('FONNTE_API_KEY'),
            'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
            'sender_number' => env('FONNTE_SENDER_NUMBER'),
            'daily_limit' => env('FONNTE_DAILY_LIMIT', 1000),
        ],

        'wablas' => [
            'api_key' => env('WABLAS_API_KEY'),
            'api_url' => env('WABLAS_API_URL', 'https://console.wablas.com/api/send-message'),
            'sender_number' => env('WABLAS_SENDER_NUMBER'),
            'daily_limit' => env('WABLAS_DAILY_LIMIT', 1000),
        ],

        'woowa' => [
            'api_key' => env('WOOWA_API_KEY'),
            'api_url' => env('WOOWA_API_URL', 'https://api.woowa.id/v1/send'),
            'sender_number' => env('WOOWA_SENDER_NUMBER'),
            'daily_limit' => env('WOOWA_DAILY_LIMIT', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Send Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic WhatsApp notifications
    |
    */

    'auto_send' => [
        'invoice_created' => env('WHATSAPP_AUTO_INVOICE_CREATED', true),
        'invoice_reminder' => env('WHATSAPP_AUTO_INVOICE_REMINDER', true),
        'payment_success' => env('WHATSAPP_AUTO_PAYMENT_SUCCESS', true),
        'payment_failed' => env('WHATSAPP_AUTO_PAYMENT_FAILED', false),
        'customer_registered' => env('WHATSAPP_AUTO_CUSTOMER_REGISTERED', true),
        'customer_approved' => env('WHATSAPP_AUTO_CUSTOMER_APPROVED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminder Settings
    |--------------------------------------------------------------------------
    |
    | Configure when to send invoice reminders (days before due date)
    |
    */

    'reminder_days' => [
        7,  // H-7
        3,  // H-3
        1,  // H-1
        0,  // H-0 (due date)
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Use queue for sending WhatsApp messages
    |
    */

    'use_queue' => env('WHATSAPP_USE_QUEUE', true),
    'queue_name' => env('WHATSAPP_QUEUE_NAME', 'whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limit for sending messages (messages per minute)
    |
    */

    'rate_limit' => env('WHATSAPP_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Retry failed messages
    |
    */

    'retry_failed' => env('WHATSAPP_RETRY_FAILED', true),
    'max_retries' => env('WHATSAPP_MAX_RETRIES', 3),
    'retry_delay' => env('WHATSAPP_RETRY_DELAY', 300), // seconds

    /*
    |--------------------------------------------------------------------------
    | Template Types
    |--------------------------------------------------------------------------
    |
    | Available template types with their configurations
    |
    */

    'template_types' => [
        'invoice-created' => [
            'name' => 'Invoice Created',
            'description' => 'Sent when new invoice is created',
            'auto_send' => true,
        ],
        'invoice-reminder' => [
            'name' => 'Invoice Reminder',
            'description' => 'Sent as reminder before due date',
            'auto_send' => true,
        ],
        'invoice-overdue' => [
            'name' => 'Invoice Overdue',
            'description' => 'Sent when invoice is overdue',
            'auto_send' => true,
        ],
        'payment-success' => [
            'name' => 'Payment Success',
            'description' => 'Sent when payment is successful',
            'auto_send' => true,
        ],
        'payment-failed' => [
            'name' => 'Payment Failed',
            'description' => 'Sent when payment fails',
            'auto_send' => false,
        ],
        'customer-registered' => [
            'name' => 'Customer Registered',
            'description' => 'Sent when customer submits registration',
            'auto_send' => true,
        ],
        'customer-approved' => [
            'name' => 'Customer Approved',
            'description' => 'Sent when admin approves customer registration',
            'auto_send' => true,
        ],
        'maintenance-notification' => [
            'name' => 'Maintenance Notification',
            'description' => 'Sent for maintenance announcements',
            'auto_send' => false,
        ],
        'general-broadcast' => [
            'name' => 'General Broadcast',
            'description' => 'General broadcast to customers',
            'auto_send' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    'log_messages' => env('WHATSAPP_LOG_MESSAGES', true),
    'log_channel' => env('WHATSAPP_LOG_CHANNEL', 'daily'),

];
