<div class="space-y-3">
    @if ($drawing->url)
        <div class="rounded-lg border border-gray-200 dark:border-white/10 bg-[repeating-conic-gradient(#f3f4f6_0_25%,#fff_0_50%)] dark:bg-gray-900 p-2 overflow-auto">
            <img
                src="{{ $drawing->url }}"
                alt="{{ $drawing->title }}"
                class="block max-h-[70vh] mx-auto"
            />
        </div>
    @else
        <p class="text-sm text-gray-500">File missing.</p>
    @endif

    <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
        <dt class="text-gray-500">Author</dt>
        <dd class="text-gray-900 dark:text-white">{{ $drawing->user?->name ?? '—' }}</dd>

        <dt class="text-gray-500">Created</dt>
        <dd class="text-gray-900 dark:text-white">{{ $drawing->created_at?->format('d M Y H:i') }}</dd>

        @if ($drawing->width && $drawing->height)
            <dt class="text-gray-500">Dimensions</dt>
            <dd class="text-gray-900 dark:text-white">{{ $drawing->width }} × {{ $drawing->height }} px</dd>
        @endif

        @if ($drawing->size)
            <dt class="text-gray-500">Size</dt>
            <dd class="text-gray-900 dark:text-white">{{ number_format($drawing->size / 1024, 1) }} KB</dd>
        @endif
    </dl>
</div>
