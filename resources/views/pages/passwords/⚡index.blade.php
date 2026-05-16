<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Passwords')] class extends Component
{
    use WithPagination;

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
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            }))
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
        <flux:table :paginate="$this->passwords" pagination:scroll-to>
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
                            <span class="font-medium">{{ $password->name }}</span>
                        </flux:table.cell>

                        <flux:table.cell class="relative">
                            <x-table-row-link :href="route('passwords.show', [$teamModel, $password])" wire:navigate />
                            {{ $password->username }}
                        </flux:table.cell>

                        <flux:table.cell class="relative !text-zinc-500">
                            <x-table-row-link :href="route('passwords.show', [$teamModel, $password])" wire:navigate />
                            <span title="{{ $password->website }}">{{ parse_url($password->website, PHP_URL_HOST) ?: $password->website }}</span>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</section>
