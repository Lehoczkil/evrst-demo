<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\Field;

/**
 * Fill an edit form from a model's accessors, not just its columns.
 *
 * The CMS resources keep almost everything in the `payload` JSON column and
 * surface it as Eloquent virtual attributes (`title`, `description_en`,
 * `logo`, `discord_webhook_url`, …). Filament seeds an edit form from
 * `$record->attributesToArray()`, which only knows about real columns and
 * `$appends` — so every payload-backed field opened blank on a record that
 * clearly had data in the table and on the public site. Worse, saving that
 * blank form wrote the blanks straight back through the virtual setters.
 *
 * This walks the form's own fields and pulls anything the record can answer
 * for but `attributesToArray()` didn't. Only non-null values are added, so a
 * field whose record value is genuinely empty still falls back to its
 * configured default exactly as before.
 */
trait FillsVirtualAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        foreach ($this->form->getFlatFields(withHidden: true) as $field) {
            if (! $field instanceof Field) {
                continue;
            }

            $name = $field->getName();

            // Nested state (repeaters, relationship groups) resolves through
            // the relation, not an accessor on this record — leave it alone.
            if (str_contains($name, '.') || array_key_exists($name, $data)) {
                continue;
            }

            $value = rescue(fn () => $record->{$name}, report: false);

            if ($value !== null) {
                $data[$name] = $value;
            }
        }

        return $data;
    }
}
