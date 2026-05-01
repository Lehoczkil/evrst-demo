<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-start gap-2">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
