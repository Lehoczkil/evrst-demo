<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Event extends CollectionResource
{
    protected static string $collectionId = '36b42185-3a49-43ee-ba79-5cc73075b0d2';

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

    protected function startAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('start_at'),
            set: fn ($value) => $this->writePayload('start_at', $value ? (string) $value : null),
        );
    }

    protected function endAt(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('end_at'),
            set: fn ($value) => $this->writePayload('end_at', $value ? (string) $value : null),
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('status'),
            set: fn ($value) => $this->writePayload('status', $value),
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
