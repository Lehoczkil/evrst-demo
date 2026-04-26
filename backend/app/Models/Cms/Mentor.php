<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Mentor extends CollectionResource
{
    protected static string $collectionId = '8639b34c-3415-40cc-85d0-e5ac0eb8d456';

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('name'),
            set: fn ($value) => $this->writePayload('name', $value),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('email'),
            set: fn ($value) => $this->writePayload('email', $value),
        );
    }

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('photo'),
            set: fn ($value) => $this->writePayload('photo', $value),
        );
    }
}
