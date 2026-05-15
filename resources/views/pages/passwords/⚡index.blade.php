<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Passwords')] class extends Component
{
    public Team $teamModel;

    public string $search = '';

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
    }

    #[Computed]
    public function passwords()
    {
        return $this->teamModel->passwords()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(50);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Passwords</flux:heading>
        <flux:button :href="route('passwords.create', $teamModel)" wire:navigate>
            New password
        </flux:button>
    </div>

    <div class="mt-6 max-w-sm">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search passwords..." icon="magnifying-glass" clearable />
    </div>

    <div class="mt-6">
        @if ($this->passwords->total() === 0)
            <flux:callout variant="note" class="mt-4">
                No passwords yet.
            </flux:callout>
        @else
            <flux:table :paginate="$this->passwords">
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Username</flux:table.column>
                    <flux:table.column>Website</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->passwords as $password)
                        <flux:table.row :key="$password->id">
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('passwords.show', [$teamModel, $password])" wire:navigate :first="true" aria-label="{{ $password->name }}" />
                                {{ $password->name }}
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('passwords.show', [$teamModel, $password])" wire:navigate />
                                {{ $password->username }}
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('passwords.show', [$teamModel, $password])" wire:navigate />
                                @if ($password->website)
                                    <flux:link :href="$password->website" target="_blank" :accent="false" wire:click.stop>{{ $password->website }}</flux:link>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</section>
