<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

/**
 * One question on the public join-us form.
 *
 * The `key` is the contract: it is what the SPA posts, what
 * member_applications.answers is keyed by, and what the admin reads an
 * answer back under. Renaming it orphans every answer already collected,
 * which is why the form only lets you set it once.
 */
class ApplicationFormField extends Model
{
    use LogsActivity;

    public const TYPE_TEXT = 'text';
    public const TYPE_EMAIL = 'email';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_RADIO = 'radio';
    public const TYPE_SELECT = 'select';
    public const TYPE_CHECKBOX = 'checkbox';

    /** Types whose answer is one of `options`. */
    public const CHOICE_TYPES = [self::TYPE_RADIO, self::TYPE_SELECT, self::TYPE_CHECKBOX];

    /** The one type whose answer is a list rather than a scalar. */
    public const MULTI_TYPES = [self::TYPE_CHECKBOX];

    /** Fallback length caps, matching what the hand-written rules used. */
    private const DEFAULT_MAX = [
        self::TYPE_TEXT => 255,
        self::TYPE_EMAIL => 255,
        self::TYPE_TEXTAREA => 5000,
        self::TYPE_RADIO => 120,
        self::TYPE_SELECT => 120,
        self::TYPE_CHECKBOX => 120,
    ];

    protected $fillable = [
        'section_id',
        'key',
        'type',
        'label',
        'help',
        'placeholder',
        'options',
        'is_required',
        'is_system',
        'max_length',
        'position',
        'is_active',
    ];

    protected $casts = [
        'label' => 'array',
        'help' => 'array',
        'placeholder' => 'array',
        'options' => 'array',
        'is_required' => 'boolean',
        'is_system' => 'boolean',
        'max_length' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function labelForLog(): string
    {
        return 'Application form field: ' . (TeamMemberGroup::pickLocale($this->label) ?? $this->key);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ApplicationFormSection::class, 'section_id');
    }

    /** @return array<int, string> */
    public static function types(): array
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_EMAIL,
            self::TYPE_TEXTAREA,
            self::TYPE_RADIO,
            self::TYPE_SELECT,
            self::TYPE_CHECKBOX,
        ];
    }

    public function isChoice(): bool
    {
        return in_array($this->type, self::CHOICE_TYPES, true);
    }

    public function isMulti(): bool
    {
        return in_array($this->type, self::MULTI_TYPES, true);
    }

    public function maxLength(): int
    {
        return $this->max_length ?: (self::DEFAULT_MAX[$this->type] ?? 255);
    }

    /**
     * The raw option values, in order. Empty for a non-choice field.
     *
     * @return array<int, string>
     */
    public function optionValues(): array
    {
        return collect($this->options ?? [])
            ->pluck('value')
            ->filter(fn ($v) => is_string($v) && $v !== '')
            ->values()
            ->all();
    }

    /**
     * Options with the label resolved for one locale, ready for the API.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function optionsForLocale(?string $lang = null): array
    {
        return collect($this->options ?? [])
            ->filter(fn ($o) => is_array($o) && filled($o['value'] ?? null))
            ->map(fn ($o) => [
                'value' => (string) $o['value'],
                // An option with no translation falls back to its raw value
                // rather than rendering as an empty pill.
                'label' => TeamMemberGroup::pickLocale($o['label'] ?? null, $lang) ?: (string) $o['value'],
            ])
            ->values()
            ->all();
    }

    /**
     * Validation rules for this field, keyed the way the request is shaped.
     *
     * This replaces the hand-written rule set in the API controller: a
     * question that exists is validated, one that was removed is not, and
     * the two can no longer disagree.
     *
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(): array
    {
        $presence = $this->is_required ? 'required' : 'nullable';

        if ($this->isMulti()) {
            return [
                $this->key => [$presence, 'array'],
                $this->key . '.*' => $this->optionValues() === []
                    ? ['string', 'max:' . $this->maxLength()]
                    : [Rule::in($this->optionValues())],
            ];
        }

        if ($this->isChoice() && $this->optionValues() !== []) {
            return [$this->key => [$presence, 'string', Rule::in($this->optionValues())]];
        }

        $rules = [$presence, 'string', 'max:' . $this->maxLength()];

        if ($this->type === self::TYPE_EMAIL) {
            // Keep `email` ahead of the length cap so the message a bad
            // address gets is about the address.
            array_splice($rules, 1, 1, ['email', 'string']);
        }

        return [$this->key => $rules];
    }
}
