<x-filament-panels::page>
    <form wire:submit="save" style="display: flex; flex-direction: column; gap: 1.75rem;">
        {{ $this->form }}

        <div style="display: flex; justify-content: flex-start; gap: .5rem; margin-top: .5rem;">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
