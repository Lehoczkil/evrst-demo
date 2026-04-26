<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Sponsor extends CollectionResource
{
    protected static string $collectionId = '8aadff44-5a0b-4d84-b570-324db3f11a94';

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('name'),
            set: fn ($value) => $this->writePayload('name', $value),
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

    protected function year(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('year'),
            set: fn ($value) => $this->writePayload('year', $value),
        );
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('url'),
            set: fn ($value) => $this->writePayload('url', $value),
        );
    }

    protected function logo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('logo'),
            set: fn ($value) => $this->writePayload('logo', $value),
        );
    }
}
