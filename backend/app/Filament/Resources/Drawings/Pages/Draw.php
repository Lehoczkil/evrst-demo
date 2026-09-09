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

    /** When set, save() updates THIS record instead of creating a new
     *  one — that's the "Edit drawing" entrypoint. */
    public ?int $editId = null;

    public function getTitle(): string
    {
        return $this->editId
            ? __('admin.drawing.edit_title')
            : __('admin.drawing.new');
    }

    public function getHeading(): string
    {
        return $this->editId
            ? __('admin.drawing.edit_heading')
            : __('admin.drawing.studio_title');
    }

    public function getSubheading(): ?string
    {
        return $this->editId
            ? __('admin.drawing.edit_subtitle')
            : __('admin.drawing.studio_subtitle');
    }

    public function mount(): void
    {
        $this->drawingTitle = __('admin.drawing.untitled');

        // ?edit={id} → load the record into the canvas AND save back to
        // the same row. Authorisation is canEdit (owner or admin).
        $editId = (int) request()->query('edit');
        if ($editId > 0) {
            $source = Drawing::find($editId);
            abort_unless($source && DrawingResource::canEdit($source), 403);
            if ($source->url) {
                $this->editId = $source->id;
                $this->sourceId = $source->id;
                $this->sourceUrl = $this->sameOriginUrl($source->url);
                $this->drawingTitle = $source->title;
                if ($source->width && $source->height) {
                    $this->canvasWidth = $source->width;
                    $this->canvasHeight = $source->height;
                }
            }
            return;
        }

        // ?from={id} → clone an existing drawing into a fresh canvas.
        abort_unless(DrawingResource::canCreate(), 403);
        $from = (int) request()->query('from');
        if ($from > 0) {
            $source = Drawing::find($from);
            if ($source && $source->url) {
                $this->sourceId = $source->id;
                $this->sourceUrl = $this->sameOriginUrl($source->url);
                $this->drawingTitle = $source->title . ' (' . __('admin.drawing.copy_suffix') . ')';
                if ($source->width && $source->height) {
                    $this->canvasWidth = $source->width;
                    $this->canvasHeight = $source->height;
                }
            }
        }
    }

    /**
     * Storage::url() builds an absolute URL off APP_URL — when you load
     * the admin from a different host (e.g. APP_URL is set to a LAN IP
     * but the user opens localhost) the canvas image fetch becomes a
     * cross-origin request that `crossOrigin='anonymous'` blocks. Strip
     * the scheme + host so the browser fetches the file from the same
     * origin as the page itself, which is the only origin guaranteed
     * to work without CORS.
     */
    private function sameOriginUrl(?string $url): ?string
    {
        if (! $url) return null;
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') return $url;
        $query = parse_url($url, PHP_URL_QUERY);
        return $query ? ($path . '?' . $query) : $path;
    }

    /**
     * Persist a base64 PNG produced by `canvas.toDataURL('image/png')`.
     * Pure data URL handling — never hits Livewire's temp-upload pipeline,
     * so the 8 MB Livewire upload-limit knob doesn't apply.
     */
    public function save(string $dataUrl, ?int $width = null, ?int $height = null): void
    {
        $title = trim($this->drawingTitle) !== '' ? trim($this->drawingTitle) : 'Untitled drawing';

        if (! preg_match('/^data:image\/(png|jpeg);base64,(.+)$/', $dataUrl, $m)) {
            Notification::make()->title(__('admin.drawing.invalid_payload'))->danger()->send();
            return;
        }

        $mime = 'image/' . $m[1];
        $binary = base64_decode($m[2], true);
        if ($binary === false) {
            Notification::make()->title(__('admin.drawing.decode_failed'))->danger()->send();
            return;
        }

        $ext = $m[1] === 'jpeg' ? 'jpg' : 'png';

        if ($this->editId) {
            $existing = Drawing::find($this->editId);
            abort_unless($existing && DrawingResource::canEdit($existing), 403);

            // Overwrite the same path so the URL stays stable. If the
            // disk doesn't support overwrite, write next to the old one
            // and clean up the previous file.
            $path = $existing->path ?: ('drawings/' . Str::ulid() . '.' . $ext);
            Storage::disk($existing->disk ?: 'public')->put($path, $binary, 'public');

            $existing->forceFill([
                'title' => mb_substr($title, 0, 160),
                'mime'  => $mime,
                'size'  => strlen($binary),
                'width' => $width ?: $this->canvasWidth,
                'height' => $height ?: $this->canvasHeight,
                'path'  => $path,
            ])->save();

            Notification::make()
                ->title(__('admin.drawing.updated'))
                ->success()
                ->send();
            $this->redirect(DrawingResource::getUrl('index'));
            return;
        }

        abort_unless(DrawingResource::canCreate(), 403);
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
            ->title(__('admin.drawing.saved'))
            ->body(__('admin.drawing.saved_body'))
            ->success()
            ->send();

        $this->redirect(DrawingResource::getUrl('index'));
    }
}
