<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Event extends CollectionResource
{
    protected static string $collectionId = '36b42185-3a49-43ee-ba79-5cc73075b0d2';

    /**
     * `start_at`, `end_at`, and `event_status` are real columns now (see
     * 2026_05_03_000004_promote_event_keys_to_columns) — keep them as
     * datetimes for date math, and let Eloquent expose them transparently.
     * `payload` cast comes from the parent Resource model.
     */
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

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('content'),
            set: fn ($value) => $this->writePayload('content', $value),
        );
    }

    protected function contentEn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('content', 'en'),
            set: fn ($value) => $this->writePayloadLocale('content', 'en', $value),
        );
    }

    protected function contentHu(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('content', 'hu'),
            set: fn ($value) => $this->writePayloadLocale('content', 'hu', $value),
        );
    }

    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('date'),
            set: fn ($value) => $this->writePayload('date', $value),
        );
    }

    /**
     * The promoted columns dual-write — the JSON payload stays in
     * lockstep with the column so /api/resource consumers (which read
     * payload.start_at) keep working. Once the SPA reads the column
     * directly the JSON copy can be removed.
     */
    protected function startAt(): Attribute
    {
        return Attribute::make(
            // The accessor short-circuits the datetime cast on the column,
            // so we re-parse explicitly here. Falls back to the JSON copy
            // for legacy rows where the column is still null.
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

    /**
     * Mapped to `event_status` in the schema (avoids clashing with any
     * future generic Resource status field). Public attribute name on
     * the model stays `status` so callers don't change.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->event_status ?? $this->readPayload('status'),
            set: function ($value) {
                $string = is_string($value) ? $value : null;
                return array_merge(
                    $this->writePayload('status', $string),
                    ['event_status' => $string],
                );
            },
        );
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('image'),
            set: fn ($value) => $this->writePayload('image', $value),
        );
    }
}
