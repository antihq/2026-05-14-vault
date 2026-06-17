<?php

use App\Models\CreditCard;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Credit Cards')] class extends Component
{
    use WithPagination;

    public Team $teamModel;

    public string $search = '';

    public function mount(Team $current_team): void
    {
        $this->teamModel = $current_team;
    }

    #[Computed]
    public function creditCards()
    {
        return $this->teamModel->creditCards()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('name_on_card', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(50);
    }

    public function deleteCreditCard(CreditCard $creditCard): void
    {
        Gate::authorize('delete', $creditCard);

        $creditCard->delete();

        Flux::toast(variant: 'success', text: 'Credit card deleted.');
    }
}; ?>

<section class="w-full max-w-2xl">
    <div class="flex gap-3 items-baseline justify-between">
        <div class="flex items-center gap-2">
            <flux:heading level="1">Credit Cards</flux:heading>
            <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->creditCards->total() }}</span>
        </div>
        <flux:button :href="route('credit-cards.create', ['current_team' => $teamModel])" variant="primary" inset="top bottom" wire:navigate>
            New credit card
        </flux:button>
    </div>

    <div class="mt-6.5">
        <flux:input wire:model.live="search" placeholder="Search..." clearable />
    </div>

    <flux:card class="mt-4 p-0!">
        <ul role="list" class="divide-y divide-zinc-100 dark:divide-zinc-700">
            @foreach ($this->creditCards as $creditCard)
                <li wire:key="{{ $creditCard->id }}" class="relative flex justify-between gap-x-6 px-4 py-3"
                    x-data="{
                        cardNumber: {{ \Illuminate\Support\Js::encode($creditCard->card_number) }},
                        cvv: {{ \Illuminate\Support\Js::encode($creditCard->cvv) }}
                    }"
                >
                    <div class="min-w-0 flex-auto">
                        <p class="font-medium">
                            <a href="{{ route('credit-cards.show', ['current_team' => $teamModel, 'creditCard' => $creditCard]) }}" wire:navigate>
                                <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                {{ $creditCard->name }}
                            </a>
                        </p>
                        <flux:text class="flex truncate mt-1">
                            {{ $creditCard->masked_number }}
                        </flux:text>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-4">
                        <div class="hidden sm:flex sm:flex-col sm:items-end relative z-10">
                            <flux:text :color="$creditCard->is_expired ? 'red' : null" class="font-medium" variant="strong">{{ $creditCard->expiry_date }}</flux:text>
                        </div>
                        <flux:dropdown align="end" class="relative z-10">
                            <flux:button icon="ellipsis-horizontal" variant="ghost" inset="right" />
                            <flux:menu>
                                <flux:menu.item :href="route('credit-cards.edit', ['current_team' => $teamModel, 'creditCard' => $creditCard])" wire:navigate>
                                    Edit
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item x-on:click="navigator.clipboard.writeText(cardNumber); $flux.toast('Card number copied.', { variant: 'success' })">
                                    Copy number
                                </flux:menu.item>
                                <flux:menu.item x-on:click="navigator.clipboard.writeText(cvv); $flux.toast('CVV copied.', { variant: 'success' })">
                                    Copy CVV
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    variant="danger"
                                    wire:click="deleteCreditCard({{ $creditCard->id }})"
                                    wire:confirm="Delete this credit card? This cannot be undone."
                                >
                                    Delete...
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </li>
            @endforeach
        </ul>
    </flux:card>

    <div class="mt-2">
        <flux:pagination :paginator="$this->creditCards" pagination:scroll-to />
    </div>
</section>
