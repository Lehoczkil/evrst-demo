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

    'discord' => [
        'webhook' => env('DISCORD_WEBHOOK_URL'),
        // Bot token for direct-message delivery. When this is set,
        // App\Support\DiscordDelivery DMs each recipient who has a
        // snowflake in team_members.discord_id, *in addition to* the
        // channel post above — the two run side by side.
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'api_base'  => env('DISCORD_API_BASE', 'https://discord.com/api/v10'),
        // Only `discord:sync-ids` needs this — reading the member list
        // is the one call that requires the Server Members intent.
        'guild_id'  => env('DISCORD_GUILD_ID'),
    ],

    'onshape' => [
        // API keys generated at https://dev-portal.onshape.com → API keys.
        // Translation requests run as the owner of these keys, so the keys
        // need to belong to a user with read access to the documents being
        // exported.
        'access_key' => env('ONSHAPE_ACCESS_KEY'),
        'secret_key' => env('ONSHAPE_SECRET_KEY'),
        'base_url'   => env('ONSHAPE_BASE_URL', 'https://cad.onshape.com/api/v6'),
    ],

];
