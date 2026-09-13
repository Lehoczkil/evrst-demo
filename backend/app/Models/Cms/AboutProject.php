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

    /**
     * Where the vehicle is in its life: flown / building / design.
     *
     * The SPA used to derive this from the row's INDEX in the collection,
     * along with the year span and the apogee — its own comment called
     * that "the honest limitation here", because reordering the projects
     * in the panel silently reassigned them. They are per-vehicle facts,
     * so they live on the vehicle.
     */
    protected function state(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('state'),
            set: fn ($value) => $this->writePayload('state', $value),
        );
    }

    /** Free text, e.g. "2024 — 2025" or "2027 —". Not a date range: an open
     *  end and an em dash are part of how it reads on the page. */
    protected function years(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('years'),
            set: fn ($value) => $this->writePayload('years', $value),
        );
    }

    /**
     * The altitude line, label and figure together — "Elért csúcsmagasság
     * · 640 m". One field rather than two because the label changes with
     * the state (reached / target / category) and the team writes the pair
     * that fits.
     */
    protected function apogee(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('apogee'),
            set: fn ($value) => $this->writePayload('apogee', $value),
        );
    }

    protected function apogeeEn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('apogee', 'en'),
            set: fn ($value) => $this->writePayloadLocale('apogee', 'en', $value),
        );
    }

    protected function apogeeHu(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('apogee', 'hu'),
            set: fn ($value) => $this->writePayloadLocale('apogee', 'hu', $value),
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
                // Normalise before storing: the pickers dehydrate to
                // 'Y-m-d H:i' (seconds are off), and SQLite keeps a datetime
                // column as the literal string it was handed. A value without
                // seconds sorts as a *prefix* of one with them, so a row
                // landing exactly on a range boundary — a project starting at
                // 00:00 on the 1st — fell outside the calendar's whereBetween.
                $string = filled($value) ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
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
                // Canonical 'Y-m-d H:i:s' — see the note on start_at.
                $string = filled($value) ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
                return array_merge(
                    $this->writePayload('end_at', $string),
                    ['end_at' => $string],
                );
            },
        );
    }
}
