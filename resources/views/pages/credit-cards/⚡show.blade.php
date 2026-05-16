<?php

use App\Models\CreditCard;
use App\Models\Team;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public CreditCard $creditCardModel;

    public function mount(Team $team, CreditCard $creditCard): void
    {
        $this->teamModel = $team;
        $this->creditCardModel = $creditCard;

        $creditCard->withoutTimestamps(fn () => $creditCard->updateQuietly(['last_viewed_at' => now()]));
    }

    public function render()
    {
        return $this->view()->title($this->creditCardModel->name);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ $creditCardModel->name }}</flux:heading>
        <flux:button :href="route('credit-cards.edit', [$teamModel, $creditCardModel])" wire:navigate>
            Edit
        </flux:button>
    </div>

    <x-description.list class="mt-2.5">
        <x-description.term>Name on card</x-description.term>
        <x-description.details>
            <div x-data="{ copied: false, value: {{ \Illuminate\Support\Js::encode($creditCardModel->name_on_card) }} }">
                <flux:button
                    variant="ghost"
                    x-on:click="navigator.clipboard.writeText(value); copied = true; setTimeout(() => copied = false, 2000)"
                    inset="left right top bottom"
                >
                    <span x-text="copied ? 'Copied!' : value"></span>
                </flux:button>
            </div>
        </x-description.details>

        <x-description.term>Card number</x-description.term>
        <x-description.details>
            <div x-data="{ hovering: false, pinned: false, copied: false, value: {{ \Illuminate\Support\Js::encode($creditCardModel->card_number) }} }" class="inline-block">
                <flux:button
                    variant="ghost"
                    x-on:mouseenter="hovering = true"
                    x-on:mouseleave="hovering = false"
                    x-on:click="if (!pinned) { navigator.clipboard.writeText(value); copied = true; setTimeout(() => copied = false, 2000) }; pinned = !pinned"
                    inset="left right top bottom"
                >
                    <span x-text="copied ? 'Copied!' : (hovering || pinned ? value : '•'.repeat(value.length))"></span>
                </flux:button>
            </div>
        </x-description.details>

        <x-description.term>Expiry date</x-description.term>
        <x-description.details>
            {{ $creditCardModel->expiry_date }}
            @if ($creditCardModel->is_expired)
                — Expired
            @endif
        </x-description.details>

        <x-description.term>CVV</x-description.term>
        <x-description.details>
            <div x-data="{ hovering: false, pinned: false, copied: false, value: {{ \Illuminate\Support\Js::encode($creditCardModel->cvv) }} }" class="inline-block">
                <flux:button
                    variant="ghost"
                    x-on:mouseenter="hovering = true"
                    x-on:mouseleave="hovering = false"
                    x-on:click="if (!pinned) { navigator.clipboard.writeText(value); copied = true; setTimeout(() => copied = false, 2000) }; pinned = !pinned"
                    inset="left right top bottom"
                >
                    <span x-text="copied ? 'Copied!' : (hovering || pinned ? value : '•'.repeat(value.length))"></span>
                </flux:button>
            </div>
        </x-description.details>

        @if ($creditCardModel->notes)
            <x-description.term>Notes</x-description.term>
            <x-description.details>
                <div x-data="{ visible: false }" class="max-w-lg">
                    <flux:button
                        variant="ghost"
                        x-on:click="visible = !visible"
                        inset="left right top bottom"
                    >
                        <span x-text="visible ? 'Hide notes' : 'Show notes'"></span>
                    </flux:button>
                    <div x-show="visible" x-transition class="mt-2">
                        {!! Illuminate\Support\Str::markdown($creditCardModel->notes) !!}
                    </div>
                </div>
            </x-description.details>
        @endif

        <x-description.term>Team</x-description.term>
        <x-description.details>{{ $teamModel->name }}</x-description.details>

        <x-description.term>Encryption</x-description.term>
        <x-description.details><flux:badge size="sm" inset="top bottom">Encrypted at rest</flux:badge></x-description.details>

        <x-description.term>Created</x-description.term>
        <x-description.details>{{ $creditCardModel->created_at->format('M j, Y \a\t H:i') }}</x-description.details>

        <x-description.term>Updated</x-description.term>
        <x-description.details>{{ $creditCardModel->updated_at->format('M j, Y \a\t H:i') }}</x-description.details>
    </x-description.list>
</section>
