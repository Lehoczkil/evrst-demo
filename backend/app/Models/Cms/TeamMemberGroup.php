<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class TeamMemberGroup extends CollectionResource
{
    protected static string $collectionId = 'a4b4cb01-f2a2-4be6-9c39-2c01b6fb1c70';

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('name'),
            set: fn ($value) => $this->writePayload('name', $value),
        );
    }

    protected function nameEn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('name', 'en'),
            set: fn ($value) => $this->writePayloadLocale('name', 'en', $value),
        );
    }

    protected function nameHu(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('name', 'hu'),
            set: fn ($value) => $this->writePayloadLocale('name', 'hu', $value),
        );
    }
}
