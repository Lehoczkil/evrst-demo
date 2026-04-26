<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class AboutProject extends CollectionResource
{
    protected static string $collectionId = 'b9b3d531-12cf-4d83-9c39-90a86b4d7c74';

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
}
