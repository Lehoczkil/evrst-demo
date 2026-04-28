<?php

namespace App\Filament\Resources\Bugs\Schemas;

use App\Auth\Perm;
use App\Models\BugReport;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BugReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.bugs.section_what'))
                    ->description(__('admin.bugs.section_what_help'))
                    ->components([
                        TextInput::make('title')
                            ->label(__('admin.common.title'))
                            ->required()
                            ->maxLength(200)
                            ->placeholder(__('admin.bugs.title_placeholder'))
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label(__('admin.bugs.description'))
                            ->required()
                            ->rows(6)
                            ->maxLength(5000)
                            ->placeholder(__('admin.bugs.description_placeholder'))
                            ->columnSpanFull(),
                        Select::make('severity')
                            ->label(__('admin.bugs.severity'))
                            ->required()
                            ->options([
                                BugReport::SEVERITY_LOW      => __('admin.bugs.severities.low'),
                                BugReport::SEVERITY_MEDIUM   => __('admin.bugs.severities.medium'),
                                BugReport::SEVERITY_HIGH     => __('admin.bugs.severities.high'),
                                BugReport::SEVERITY_CRITICAL => __('admin.bugs.severities.critical'),
                            ])
                            ->default(BugReport::SEVERITY_MEDIUM)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        TextInput::make('page_url')
                            ->label(__('admin.bugs.page_url'))
                            ->placeholder(__('admin.bugs.page_url_placeholder'))
                            ->maxLength(500)
                            ->columnSpan(['default' => 12, 'md' => 8]),
                    ])
                    ->columns(12),

                Section::make(__('admin.bugs.section_evidence'))
                    ->components([
                        FileUpload::make('screenshot_path')
                            ->label(__('admin.bugs.screenshot'))
                            ->directory('bug-reports')
                            ->disk('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),

                // Triage section: only visible on EDIT, never on create.
                // New reports default to STATUS_OPEN (see BugReport::$attributes).
                Section::make(__('admin.bugs.section_triage'))
                    ->hiddenOn('create')
                    ->visible(fn () => auth()->user()?->can(Perm::BUGS_TRIAGE) ?? false)
                    ->components([
                        Select::make('status')
                            ->label(__('admin.bugs.status'))
                            ->required()
                            ->options([
                                BugReport::STATUS_OPEN        => __('admin.bugs.statuses.open'),
                                BugReport::STATUS_TRIAGING    => __('admin.bugs.statuses.triaging'),
                                BugReport::STATUS_IN_PROGRESS => __('admin.bugs.statuses.in_progress'),
                                BugReport::STATUS_RESOLVED    => __('admin.bugs.statuses.resolved'),
                                BugReport::STATUS_CLOSED      => __('admin.bugs.statuses.closed'),
                                BugReport::STATUS_WONT_FIX    => __('admin.bugs.statuses.wont_fix'),
                            ])
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        Select::make('assignee_id')
                            ->label(__('admin.bugs.assignee'))
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 12, 'md' => 8]),
                        Textarea::make('admin_notes')
                            ->label(__('admin.bugs.admin_notes'))
                            ->rows(3)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(12),

                // Reporter is auto-stamped on create; never editable.
                Hidden::make('reporter_id')
                    ->default(fn () => auth()->id())
                    ->dehydrated(fn ($state, $record) => $record === null && $state !== null),

                // Snapshot the user's environment on create so triagers
                // can repro without asking for a follow-up.
                Hidden::make('environment')
                    ->default(fn () => [
                        'user_agent' => request()->userAgent(),
                        'locale'     => app()->getLocale(),
                        'reported_at' => now()->toIso8601String(),
                    ])
                    ->dehydrated(fn ($state, $record) => $record === null),
            ]);
    }
}
