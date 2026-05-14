<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Credit Cards')] class extends Component
{
    public Team $teamModel;

    public string $search = '';

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
    }

    #[Computed]
    public function creditCards()
    {
        return $this->teamModel->creditCards()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('name_on_card', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(50);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Credit Cards</flux:heading>
        <flux:button variant="primary" :href="route('credit-cards.create', $teamModel)" wire:navigate>
            New credit card
        </flux:button>
    </div>

    <div class="mt-6 max-w-sm">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search credit cards..." icon="magnifying-glass" clearable />
    </div>

    <div class="mt-6">
        @if ($this->creditCards->total() === 0)
            <flux:callout variant="note" class="mt-4">
                No credit cards yet.
            </flux:callout>
        @else
            <flux:table :paginate="$this->creditCards">
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Number</flux:table.column>
                    <flux:table.column>Expires</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->creditCards as $creditCard)
                        <flux:table.row :key="$creditCard->id">
                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('credit-cards.show', [$teamModel, $creditCard])" wire:navigate :first="true" aria-label="{{ $creditCard->name }}" />
                                {{ $creditCard->name }}
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('credit-cards.show', [$teamModel, $creditCard])" wire:navigate />
                                {{ $creditCard->masked_number }}
                            </flux:table.cell>

                            <flux:table.cell class="relative">
                                <x-table-row-link :href="route('credit-cards.show', [$teamModel, $creditCard])" wire:navigate />
                                {{ $creditCard->expiry_date }}@if ($creditCard->is_expired) (Expired)@endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</section>
