<?php

use App\Models\Team;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vault')] class extends Component
{
    public Team $teamModel;

    public string $search = '';

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
    }

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $search = $this->search;

        $passwords = $this->teamModel->passwords()
            ->select('id', 'name', 'username', 'website', 'updated_at')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"))
            ->get()
            ->each(fn ($p) => $p->type = 'password')
            ->each(fn ($p) => $p->key = $p->username);

        $creditCards = $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date', 'updated_at')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('name_on_card', 'like', "%{$search}%"))
            ->get()
            ->each(fn ($card) => $card->type = 'credit_card')
            ->each(fn ($card) => $card->key = '•••• •••• •••• ' . ($card->last_four ?? '    '));

        $all = $passwords->merge($creditCards)->sortByDesc('updated_at')->values();

        $page = request()->integer('page', 1);
        $perPage = 50;

        return new LengthAwarePaginator(
            items: $all->forPage($page, $perPage),
            total: $all->count(),
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function itemRoute(object $item): string
    {
        return $item->type === 'password'
            ? route('passwords.show', [$this->teamModel, $item->id])
            : route('credit-cards.show', [$this->teamModel, $item->id]);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Vault</flux:heading>
        <div class="flex gap-2">
            <flux:button :href="route('passwords.create', $teamModel)" wire:navigate>
                New password
            </flux:button>
            <flux:button variant="primary" :href="route('credit-cards.create', $teamModel)" wire:navigate>
                New credit card
            </flux:button>
        </div>
    </div>

    <div class="mt-6 max-w-sm">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search vault..." icon="magnifying-glass" clearable />
    </div>

    <div class="mt-6">
        @if ($this->items->total() === 0)
            <flux:callout variant="note" class="mt-4">
                No items in the vault yet.
            </flux:callout>
        @else
            <flux:table :paginate="$this->items">
                <flux:table.columns>
                    <flux:table.column>Type</flux:table.column>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Key</flux:table.column>
                    <flux:table.column>Updated</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->items as $item)
                        <flux:table.row :key="'{$item->type}-{$item->id}'">
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="$this->itemRoute($item)" wire:navigate :first="true" />
                                <flux:badge size="sm" inset="top bottom">{{ $item->type === 'password' ? 'Password' : 'Credit card' }}</flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                {{ $item->name }}
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                {{ $item->key }}
                            </flux:table.cell>

                            <flux:table.cell class="relative whitespace-nowrap">
                                <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                {{ $item->updated_at->format('M j, Y') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</section>
