<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mercadopago' => [
        'client_id' => env('MERCADOPAGO_CLIENT_ID'),
        'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
        'redirect_uri' => env('MERCADOPAGO_REDIRECT_URI'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        'test_token' => env('MERCADOPAGO_TEST_TOKEN', false),
        'payment_expiration_minutes' => (int) env('MERCADOPAGO_PAYMENT_EXPIRATION_MINUTES', 60),
        'payment_expiration_grace_minutes' => (int) env('MERCADOPAGO_PAYMENT_EXPIRATION_GRACE_MINUTES', 30),
    ],

    'payments' => [
        'expiration_grace_minutes' => (int) env(
            'ONLINE_PAYMENT_EXPIRATION_GRACE_MINUTES',
            env('MERCADOPAGO_PAYMENT_EXPIRATION_GRACE_MINUTES', 30)
        ),
    ],

    'wompi' => [
        'payment_expiration_minutes' => (int) env('WOMPI_PAYMENT_EXPIRATION_MINUTES', 60),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'image_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-1'),
        'image_quality' => env('OPENAI_IMAGE_QUALITY', 'low'),
        'proxy' => env('OPENAI_PROXY'),
    ],

    'support' => [
        'whatsapp' => env('SUPPORT_WHATSAPP', '573170613664'),
    ],

    'trial' => [
        'phone_hash_key' => env('TRIAL_PHONE_HASH_KEY')
            ?: (env('APP_ENV') === 'production' ? null : env('APP_KEY')),
    ],

    'landing_social' => [
        'instagram' => env('LANDING_INSTAGRAM_URL'),
        'facebook' => env('LANDING_FACEBOOK_URL'),
        'tiktok' => env('LANDING_TIKTOK_URL'),
    ],

    'meta' => [
        'landing_pixel_id' => env('LANDING_META_PIXEL_ID', '2170146153556872'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
        'verification_url' => env('TURNSTILE_VERIFICATION_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
        'required' => env('TURNSTILE_REQUIRED', env('APP_ENV') === 'production'),
    ],

    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'admin_phone' => env('WHATSAPP_ADMIN_PHONE', env('SUPPORT_WHATSAPP', '573170613664')),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v24.0'),
        'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'es_CO'),
        'admin_registration_template' => env('WHATSAPP_ADMIN_REGISTRATION_TEMPLATE', 'vendly_nuevo_registro'),
        'customer_welcome_template' => env('WHATSAPP_CUSTOMER_WELCOME_TEMPLATE', 'vendly_bienvenida_cliente'),
        'phone_verification_template' => env('WHATSAPP_PHONE_VERIFICATION_TEMPLATE', 'vendly_verificar_numero'),
        'followup_day_1_template' => env('WHATSAPP_FOLLOWUP_DAY_1_TEMPLATE', 'seguimiento_dia_1_tienda'),
        'followup_day_3_template' => env('WHATSAPP_FOLLOWUP_DAY_3_TEMPLATE', 'seguimiento_dia_3_productos'),
        'followup_day_6_template' => env('WHATSAPP_FOLLOWUP_DAY_6_TEMPLATE', 'seguimiento_dia_6_prueba'),
        'subscription_expires_3_days_template' => env('WHATSAPP_PLAN_EXPIRES_3_DAYS_TEMPLATE', 'plan_vence_3_dias'),
        'subscription_expires_1_day_template' => env('WHATSAPP_PLAN_EXPIRES_1_DAY_TEMPLATE', 'plan_vence_manana'),
        'subscription_expired_template' => env('WHATSAPP_PLAN_EXPIRED_TEMPLATE', 'plan_vencido'),
        'authentication_button_sub_type' => env('WHATSAPP_AUTHENTICATION_BUTTON_SUB_TYPE', 'url'),
        'require_phone_verification' => env('WHATSAPP_REQUIRE_PHONE_VERIFICATION', env('APP_ENV') === 'production'),
        'verification_min_response_ms' => (int) env(
            'WHATSAPP_VERIFICATION_MIN_RESPONSE_MS',
            env('APP_ENV') === 'production' ? 2500 : 0,
        ),
        'consent_version' => env('WHATSAPP_CONSENT_VERSION', 'registration_v1'),
        'retention_days' => (int) env('WHATSAPP_RETENTION_DAYS', 365),
    ],

];
