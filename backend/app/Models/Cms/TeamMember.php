<?php

namespace App\Models\Cms;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TeamMember extends CollectionResource
{
    protected static string $collectionId = '00338d38-b302-4653-bb4e-9a734f46470e';

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

    protected function degree(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('degree'),
            set: fn ($value) => $this->writePayload('degree', $value),
        );
    }

    protected function degreeEn(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('degree', 'en'),
            set: fn ($value) => $this->writePayloadLocale('degree', 'en', $value),
        );
    }

    protected function degreeHu(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayloadLocale('degree', 'hu'),
            set: fn ($value) => $this->writePayloadLocale('degree', 'hu', $value),
        );
    }

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->readPayload('photo'),
            set: fn ($value) => $this->writePayload('photo', $value),
        );
    }

    /**
     * Virtual position_ids attribute — round-trips through payload.positions:
     *   read  → array of UUIDs from payload.positions[*].id
     *   write → re-snapshot each linked TeamMemberGroup as
     *            { id, payload } so the frontend can render the position
     *            name without a join.
     */
    protected function positionIds(): Attribute
    {
        return Attribute::make(
            get: function () {
                $positions = $this->currentPayload()['positions'] ?? [];
                if (! is_array($positions)) return [];
                return array_values(array_filter(array_map(fn ($p) => $p['id'] ?? null, $positions)));
            },
            set: function ($value) {
                $payload = $this->currentPayload();
                $ids = array_values(array_filter((array) $value));

                if (empty($ids)) {
                    unset($payload['positions']);
                } else {
                    $groups = TeamMemberGroup::whereIn('id', $ids)->get()->keyBy('id');
                    $payload['positions'] = array_values(array_map(function ($id) use ($groups) {
                        $group = $groups->get($id);
                        return $group
                            ? ['id' => $group->id, 'payload' => $group->payload]
                            : ['id' => $id];
                    }, $ids));
                }

                // Drop main_position if it is no longer in the positions list.
                $mainId = $payload['main_position']['id'] ?? null;
                if ($mainId && ! in_array($mainId, $ids, true)) {
                    unset($payload['main_position']);
                }

                return $this->packPayload($payload);
            },
        );
    }

    /**
     * Virtual main_position_id attribute — round-trips through payload.main_position:
     *   read  → payload.main_position.id
     *   write → re-snapshot the linked TeamMemberGroup as { id, payload } so
     *            the frontend can build the org chart without a join.
     */
    protected function mainPositionId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currentPayload()['main_position']['id'] ?? null,
            set: function ($value) {
                $payload = $this->currentPayload();
                if (! $value) {
                    unset($payload['main_position']);
                } else {
                    $group = TeamMemberGroup::find($value);
                    $payload['main_position'] = $group
                        ? ['id' => $group->id, 'payload' => $group->payload]
                        : ['id' => $value];
                }
                return $this->packPayload($payload);
            },
        );
    }

    /**
     * Virtual user_id attribute — round-trips through payload.user:
     *   read  → payload.user.id (the User's primary key, an int)
     *   write → re-snapshot the linked User as { id, name, email } so the
     *            admin can render the linked-account hint without a join.
     */
    protected function userId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currentPayload()['user']['id'] ?? null,
            set: function ($value) {
                $payload = $this->currentPayload();
                if (! $value) {
                    unset($payload['user']);
                } else {
                    $user = User::find($value);
                    $payload['user'] = $user
                        ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]
                        : ['id' => $value];
                }
                return $this->packPayload($payload);
            },
        );
    }
}
