<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberApplication extends Model
{
    use HasUuids, LogsActivity;

    public function labelForLog(): string
    {
        return 'Application: ' . $this->name;
    }

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'name',
        'answers',
        'status',
        'reviewed_at',
        'reviewed_by',
        'team_member_id',
    ];

    protected $casts = [
        'answers' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * One answer by field key. `name` and `email` are columns rather than
     * answers, so they are served from there — a caller asking for a field
     * key should not have to know which of the two it is.
     */
    public function answer(string $key): mixed
    {
        if ($key === 'name' || $key === 'email') {
            return $this->{$key};
        }

        return ($this->answers ?? [])[$key] ?? null;
    }

    /**
     * Every answer paired with the question that was asked, in form order.
     *
     * Answers to questions that have since been deleted are kept and shown
     * last under their raw key: the applicant did answer them, and silently
     * dropping that from the admin view would misrepresent the submission.
     *
     * @return array<int, array{key: string, label: string, value: mixed, type: string, orphaned: bool}>
     */
    public function answeredFields(?string $lang = null): array
    {
        $answers = $this->answers ?? [];

        $fields = ApplicationFormField::query()
            ->where('is_system', false)
            ->orderBy('position')
            ->get();

        $rows = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field->key, $answers)) {
                continue;
            }

            $rows[] = [
                'key' => $field->key,
                'label' => TeamMemberGroup::pickLocale($field->label, $lang) ?: $field->key,
                'value' => self::labelledValue($field, $answers[$field->key], $lang),
                'type' => $field->type,
                'orphaned' => false,
            ];
        }

        $known = $fields->pluck('key')->all();
        foreach ($answers as $key => $value) {
            if (in_array($key, $known, true)) {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'label' => $key,
                'value' => $value,
                'type' => ApplicationFormField::TYPE_TEXT,
                'orphaned' => true,
            ];
        }

        return $rows;
    }

    /**
     * The answer worth showing in a list: the first choice question on the
     * form. That is the department picker today, but the form is editable,
     * so it is resolved rather than hardcoded.
     *
     * @return array{label: string, value: string}|null
     */
    public function summaryAnswer(?string $lang = null): ?array
    {
        $field = ApplicationFormField::query()
            ->where('is_system', false)
            ->where('is_active', true)
            ->whereIn('type', ApplicationFormField::CHOICE_TYPES)
            ->orderBy('position')
            ->first();

        if (! $field) {
            return null;
        }

        $value = ($this->answers ?? [])[$field->key] ?? null;

        if ($value === null || $value === []) {
            return null;
        }

        return [
            'label' => TeamMemberGroup::pickLocale($field->label, $lang) ?: $field->key,
            'value' => self::labelledValue($field, $value, $lang),
        ];
    }

    /** Swap stored option values for their localised labels. */
    private static function labelledValue(ApplicationFormField $field, mixed $value, ?string $lang): mixed
    {
        if (! $field->isChoice()) {
            return $value;
        }

        $labels = collect($field->optionsForLocale($lang))->pluck('label', 'value');

        if (is_array($value)) {
            return array_values(array_map(fn ($v) => $labels[$v] ?? $v, $value));
        }

        return $labels[$value] ?? $value;
    }
}
