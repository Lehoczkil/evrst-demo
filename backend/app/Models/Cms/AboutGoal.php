<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class AboutGoal extends CollectionResource
{
    protected static string $collectionId = 'd3aaad26-e2e3-4f73-9e75-2cba9a85b0a6';

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
}
