<?php

namespace App\Filament\Resources\MemberApplications\Schemas;

use App\Models\MemberApplication;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * A submitted application, as the reviewer sees it.
 *
 * Name, email and status are structural. Everything else is whatever the
 * form asked at the time, so it is rendered from the answers rather than
 * from a fixed list of inputs — a question added in the panel shows up here
 * with no code change, and an answer to a question since deleted is still
 * shown (under its raw key) instead of vanishing from the record.
 */
class MemberApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 1, 'md' => 5]),
                TextInput::make('email')
                    ->label(__('admin.common.email'))
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->columnSpan(['default' => 1, 'md' => 5]),
                Select::make('status')
                    ->label(__('admin.common.status'))
                    ->options([
                        'PENDING' => __('admin.applications.statuses.PENDING'),
                        'ACCEPTED' => __('admin.applications.statuses.ACCEPTED'),
                        'REJECTED' => __('admin.applications.statuses.REJECTED'),
                    ])
                    ->disabled()
                    ->dehydrated(false)
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.application_status'))
                    ->columnSpan(['default' => 1, 'md' => 2]),
                Section::make(__('admin.applications.answers'))
                    ->description(__('admin.applications.answers_help'))
                    ->columnSpanFull()
                    ->schema([
                        Text::make(fn (?MemberApplication $record) => new HtmlString(self::renderAnswers($record))),
                    ]),
            ])
            ->columns(['default' => 1, 'md' => 12]);
    }

    /**
     * The answers as a definition list. Rendered rather than put in disabled
     * inputs: these are a record of what was submitted, not something an
     * admin edits — and the shape varies per submission.
     *
     * The class names are ours, styled in `admin-theme.css`, NOT Tailwind
     * utilities. Utilities written here are built at runtime, so Tailwind's
     * scanner never sees them and they are absent from the compiled CSS —
     * `uppercase` does not appear in it once. That is why the question and
     * the answer used to render identically: the `<dt>` was unstyled, and
     * twenty-four lines of the same grey ran together.
     */
    private static function renderAnswers(?MemberApplication $record): string
    {
        if (! $record) {
            return '';
        }

        $rows = $record->answeredFields();

        if ($rows === []) {
            return '<p class="evrst-answers-empty">'
                . e(__('admin.applications.answers_empty'))
                . '</p>';
        }

        $html = '<dl class="evrst-answers">';

        foreach ($rows as $row) {
            $value = is_array($row['value'])
                ? implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $row['value']))
                : (string) $row['value'];

            $label = e($row['label']);
            if ($row['orphaned']) {
                // Answered, then the question was removed from the form.
                $label .= ' <span class="evrst-answers-orphan">('
                    . e(__('admin.applications.answer_orphaned'))
                    . ')</span>';
            }

            $html .= '<div class="evrst-answers-row">'
                . '<dt>' . $label . '</dt>'
                . '<dd>' . e($value) . '</dd>'
                . '</div>';
        }

        return $html . '</dl>';
    }
}
