<?php

namespace App\Filament\Resources\Drawings\Pages;

use App\Filament\Resources\Drawings\DrawingResource;
use App\Models\Drawing;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Canva-like drawing studio. The Blade view renders a <canvas> + toolbar
 * with vanilla JS (no external CDNs). When the user hits "Save", the
 * canvas is serialised to a PNG data URL and POSTed back to {@see save()}
 * which decodes + persists it via the public disk.
 */
class Draw extends Page
{
    protected static string $resource = DrawingResource::class;

    protected string $view = 'filament.resources.drawings.pages.draw';

    public string $drawingTitle = '';

    public int $canvasWidth = 1200;

    public int $canvasHeight = 800;

    /** Drawing record we cloned this canvas from (if any). */
    public ?int $sourceId = null;

    /** Public URL of the source PNG so the JS can preload it. */
    public ?string $sourceUrl = null;

    public function getTitle(): string
    {
        return __('admin.drawing.new');
    }

    public function getHeading(): string
    {
        return __('admin.drawing.studio_title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.drawing.studio_subtitle');
    }

    public function mount(): void
    {
        abort_unless(DrawingResource::canCreate(), 403);
        $this->drawingTitle = __('admin.drawing.untitled');

        // Optional clone-from-existing flow: ?from={id} preloads a previous
        // drawing into the canvas as a starting point.
        $from = (int) request()->query('from');
        if ($from > 0) {
            $source = Drawing::find($from);
            if ($source && $source->url) {
                $this->sourceId = $source->id;
                $this->sourceUrl = $source->url;
                $this->drawingTitle = $source->title . ' (' . __('admin.drawing.continue_edit') . ')';
                if ($source->width && $source->height) {
                    $this->canvasWidth = $source->width;
                    $this->canvasHeight = $source->height;
                }
            }
        }
    }

    /**
     * Persist a base64 PNG produced by `canvas.toDataURL('image/png')`.
     * Pure data URL handling — never hits Livewire's temp-upload pipeline,
     * so the 8 MB Livewire upload-limit knob doesn't apply.
     */
    public function save(string $dataUrl, ?int $width = null, ?int $height = null): void
    {
        abort_unless(DrawingResource::canCreate(), 403);

        $title = trim($this->drawingTitle) !== '' ? trim($this->drawingTitle) : 'Untitled drawing';

        if (! preg_match('/^data:image\/(png|jpeg);base64,(.+)$/', $dataUrl, $m)) {
            Notification::make()->title('Invalid drawing payload')->danger()->send();
            return;
        }

        $mime = 'image/' . $m[1];
        $binary = base64_decode($m[2], true);
        if ($binary === false) {
            Notification::make()->title('Could not decode drawing')->danger()->send();
            return;
        }

        $ext = $m[1] === 'jpeg' ? 'jpg' : 'png';
        $path = 'drawings/' . Str::ulid() . '.' . $ext;
        Storage::disk('public')->put($path, $binary, 'public');

        Drawing::create([
            'user_id' => auth()->id(),
            'title' => mb_substr($title, 0, 160),
            'disk' => 'public',
            'path' => $path,
            'mime' => $mime,
            'size' => strlen($binary),
            'width' => $width ?: $this->canvasWidth,
            'height' => $height ?: $this->canvasHeight,
        ]);

        Notification::make()
            ->title('Drawing saved')
            ->body('Find it in the Drawings gallery.')
            ->success()
            ->send();

        $this->redirect(DrawingResource::getUrl('index'));
    }
}
