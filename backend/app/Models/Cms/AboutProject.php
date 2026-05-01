<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class AboutProject extends CollectionResource
{
    protected static string $collectionId = 'b9b3d531-12cf-4d83-9c39-90a86b4d7c74';

    /** Promoted columns share the resources table; cast for date math. */
    protected $casts = [
        'payload' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('title'),
            set: fn ($value) => $this->writePayload('title', $value),
        );
    }

    protected function titleEn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('title', 'en'),
            set: fn ($value) => $this->writePayloadLocale('title', 'en', $value),
        );
    }

    protected function titleHu(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('title', 'hu'),
            set: fn ($value) => $this->writePayloadLocale('title', 'hu', $value),
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('description'),
            set: fn ($value) => $this->writePayload('description', $value),
        );
    }

    protected function discordWebhookUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('discord_webhook_url'),
            set: fn ($value) => $this->writePayload('discord_webhook_url', $value),
        );
    }

    protected function descriptionEn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('description', 'en'),
            set: fn ($value) => $this->writePayloadLocale('description', 'en', $value),
        );
    }

    protected function descriptionHu(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('description', 'hu'),
            set: fn ($value) => $this->writePayloadLocale('description', 'hu', $value),
        );
    }

    protected function startAt(): Attribute
    {
        return Attribute::make(
            // Accessors short-circuit the datetime cast, so re-parse here.
            // Falls back to the JSON payload for legacy rows pre-promotion.
            get: function ($value) {
                $raw = $value ?? $this->readPayload('start_at');
                return $raw ? \Carbon\Carbon::parse($raw) : null;
            },
            set: function ($value) {
                $string = $value ? (is_string($value) ? $value : $value->format('Y-m-d H:i:s')) : null;
                return array_merge(
                    $this->writePayload('start_at', $string),
                    ['start_at' => $string],
                );
            },
        );
    }

    protected function endAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $raw = $value ?? $this->readPayload('end_at');
                return $raw ? \Carbon\Carbon::parse($raw) : null;
            },
            set: function ($value) {
                $string = $value ? (is_string($value) ? $value : $value->format('Y-m-d H:i:s')) : null;
                return array_merge(
                    $this->writePayload('end_at', $string),
                    ['end_at' => $string],
                );
            },
        );
    }
}
