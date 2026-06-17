<?php

use App\Models\CreditCard;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public CreditCard $creditCardModel;

    public function mount(Team $current_team, CreditCard $creditCard): void
    {
        $this->teamModel = $current_team;
        $this->creditCardModel = $creditCard;

        Gate::authorize('view', $creditCard);

        $creditCard->update(['last_viewed_at' => now()]);
    }

    public function render()
    {
        return $this->view()->title($this->creditCardModel->name);
    }
}; ?>

<section class="w-full max-w-2xl" x-data="{
    showNumber: false,
    showCvv: false,
    cardNumber: {{ \Illuminate\Support\Js::encode($creditCardModel->card_number) }},
    cvv: {{ \Illuminate\Support\Js::encode($creditCardModel->cvv) }}
}">

    <div class="flex justify-between flex-wrap items-center gap-x-4 gap-y-4">
        <div class="flex items-center gap-2">
            <flux:heading level="1">{{ $creditCardModel->name }}</flux:heading>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <flux:button
                :href="route('credit-cards.edit', ['current_team' => $teamModel, 'creditCard' => $creditCardModel])"
                wire:navigate
                inset="top bottom"
                class="max-lg:hidden"
            >
                Edit
            </flux:button>

            <flux:button
                x-on:click="navigator.clipboard.writeText(cvv); $flux.toast('CVV copied.', { variant: 'success' })"
                inset="top bottom"
                class="max-lg:hidden"
            >
                Copy CVV
            </flux:button>

            <flux:button
                variant="primary"
                x-on:click="navigator.clipboard.writeText(cardNumber); $flux.toast('Card number copied.', { variant: 'success' })"
                inset="top bottom"
            >
                Copy number
            </flux:button>

            <flux:dropdown class="-my-2.5 lg:hidden" align="end">
                <flux:button icon="ellipsis-horizontal" variant="ghost" class="text-zinc-800" inset="left" />
                <flux:navmenu>
                    <flux:navmenu.item :href="route('credit-cards.edit', ['current_team' => $teamModel, 'creditCard' => $creditCardModel])" class="text-zinc-800">Edit</flux:navmenu.item>
                    <flux:navmenu.item x-on:click="navigator.clipboard.writeText(cvv); $flux.toast('CVV copied.', { variant: 'success' })" class="text-zinc-800">Copy CVV</flux:navmenu.item>
                </flux:navmenu>
            </flux:dropdown>
        </div>
    </div>

    <flux:card class="mt-6.5 p-0">
        <x-description.list>
            <x-description.term class="pl-4 max-sm:px-4">Name on card</x-description.term>
            <x-description.details class="break-all pr-4 max-sm:px-4 font-medium">{{ $creditCardModel->name_on_card }}</x-description.details>

            <x-description.term class="pl-4 max-sm:px-4">Card number</x-description.term>
            <x-description.details class="flex gap-x-4 items-center pr-4 max-sm:px-4 font-medium">
                <span x-show="!showNumber">{{ $creditCardModel->masked_number }}</span>
                <span x-show="showNumber" x-cloak x-text="cardNumber" class="font-mono break-all"></span>
                <flux:button
                    size="sm"
                    x-on:click="showNumber = !showNumber"
                    inset="top bottom"
                >
                    <span x-text="showNumber ? 'Hide' : 'Show'"></span>
                </flux:button>
            </x-description.details>

            <x-description.term class="pl-4 max-sm:px-4">Expiry date</x-description.term>
            <x-description.details class="pr-4 max-sm:px-4 font-medium">
                <flux:text :color="$creditCardModel->is_expired ? 'red' : null">{{ $creditCardModel->expiry_date }}</flux:text>
            </x-description.details>

            <x-description.term class="pl-4 max-sm:px-4">CVV</x-description.term>
            <x-description.details class="flex gap-x-4 items-center pr-4 max-sm:px-4 font-medium">
                <span x-show="!showCvv">•••</span>
                <span x-show="showCvv" x-cloak x-text="cvv" class="font-mono"></span>
                <flux:button
                    size="sm"
                    x-on:click="showCvv = !showCvv"
                    inset="top bottom"
                >
                    <span x-text="showCvv ? 'Hide' : 'Show'"></span>
                </flux:button>
            </x-description.details>

            @if ($creditCardModel->notes)
                <x-description.term class="pl-4 max-sm:px-4">Notes</x-description.term>
                <x-description.details class="pr-4 max-sm:px-4">
                    {!! Illuminate\Support\Str::markdown($creditCardModel->notes) !!}
                </x-description.details>
            @endif
        </x-description.list>
    </flux:card>
</section>
