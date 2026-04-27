<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\Task;
use App\Models\TaskProof;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProofsRelationManager extends RelationManager
{
    protected static string $relationship = 'proofs';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tasks.proofs');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->label(__('admin.tasks.proof_kind'))
                    ->options([
                        TaskProof::KIND_IMAGE => __('admin.tasks.proof_kinds.image'),
                        TaskProof::KIND_FILE  => __('admin.tasks.proof_kinds.file'),
                        TaskProof::KIND_LINK  => __('admin.tasks.proof_kinds.link'),
                        TaskProof::KIND_NOTE  => __('admin.tasks.proof_kinds.note'),
                    ])
                    ->default(TaskProof::KIND_IMAGE)
                    ->required()
                    ->live()
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.proof_kind'))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('title')
                    ->label(__('admin.common.title'))
                    ->required()
                    ->maxLength(200)
                    ->columnSpan(['default' => 12, 'md' => 8]),
                Textarea::make('body')
                    ->label(__('admin.tasks.proof_notes'))
                    ->rows(3)
                    ->maxLength(5000)
                    ->columnSpan(12),
                TextInput::make('link_url')
                    ->label(__('admin.tasks.proof_link'))
                    ->url()
                    ->maxLength(500)
                    ->required(fn (Get $get) => $get('kind') === TaskProof::KIND_LINK)
                    ->visible(fn (Get $get) => $get('kind') === TaskProof::KIND_LINK)
                    ->placeholder('https://…')
                    ->columnSpan(12),
                FileUpload::make('file_path')
                    ->label(__('admin.tasks.proof_file'))
                    ->disk('public')
                    ->directory('task-proofs')
                    ->visibility('public')
                    ->openable()
                    ->downloadable()
                    ->maxSize(20480)
                    ->required(fn (Get $get) => in_array($get('kind'), [TaskProof::KIND_IMAGE, TaskProof::KIND_FILE], true))
                    ->visible(fn (Get $get) => in_array($get('kind'), [TaskProof::KIND_IMAGE, TaskProof::KIND_FILE], true))
                    ->image()
                    // Image kind validates as image/*; file kind accepts the broader 3D/PDF set.
                    ->acceptedFileTypes(fn (Get $get) => $get('kind') === TaskProof::KIND_IMAGE
                        ? ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/gif', 'image/heic', 'image/heif']
                        : ['model/gltf-binary', 'model/gltf+json', 'application/octet-stream', 'application/pdf', 'application/zip', 'application/step', 'application/sla'])
                    ->preserveFilenames(false)
                    ->columnSpan(12),
            ])
            ->columns(12);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('kind')
                    ->label(__('admin.tasks.proof_kind'))
                    ->icon(fn ($state) => match ($state) {
                        TaskProof::KIND_IMAGE => 'heroicon-o-photo',
                        TaskProof::KIND_FILE  => 'heroicon-o-cube',
                        TaskProof::KIND_LINK  => 'heroicon-o-link',
                        TaskProof::KIND_NOTE  => 'heroicon-o-pencil',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->wrap()
                    ->weight('semibold'),
                TextColumn::make('user.name')
                    ->label(__('admin.drawing.author'))
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label(__('admin.tasks.proof_kind'))
                    ->options([
                        TaskProof::KIND_IMAGE => __('admin.tasks.proof_kinds.image'),
                        TaskProof::KIND_FILE  => __('admin.tasks.proof_kinds.file'),
                        TaskProof::KIND_LINK  => __('admin.tasks.proof_kinds.link'),
                        TaskProof::KIND_NOTE  => __('admin.tasks.proof_kinds.note'),
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.tasks.add_proof'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->visible(fn () => $this->canPostProof()),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('admin.drawing.view'))
                    ->icon('heroicon-o-eye')
                    ->visible(fn (TaskProof $r) => $r->link_url || $r->file_url)
                    ->url(fn (TaskProof $r) => $r->link_url ?: $r->file_url, true),
                EditAction::make()
                    ->visible(fn (TaskProof $r) => $this->canEditProof($r)),
                DeleteAction::make()
                    ->before(fn (TaskProof $r) => $r->deleteFile())
                    ->visible(fn (TaskProof $r) => $this->canEditProof($r)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }

    /**
     * Anyone tied to the task — assignee, supervisor, or admin — may
     * attach a proof. Members without TASKS_EDIT can still upload
     * evidence on a task they're assigned to.
     */
    protected function canPostProof(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if ($u->isAdmin()) return true;
        /** @var Task $task */
        $task = $this->getOwnerRecord();
        $task->loadMissing(['assignees']);
        return $task->supervisor_id === $u->id
            || $task->assignees->contains('id', $u->id);
    }

    protected function canEditProof(TaskProof $proof): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if ($u->isAdmin()) return true;
        /** @var Task $task */
        $task = $this->getOwnerRecord();
        return $proof->user_id === $u->id || $task->supervisor_id === $u->id;
    }
}
