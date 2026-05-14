<?php

use App\Models\CreditCard;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public CreditCard $creditCardModel;

    public function mount(Team $team, CreditCard $creditCard): void
    {
        $this->teamModel = $team;
        $this->creditCardModel = $creditCard;
    }

    public function deleteCreditCard(): void
    {
        Gate::authorize('delete', $this->creditCardModel);

        $this->creditCardModel->delete();

        Flux::toast(variant: 'success', text: 'Credit card deleted.');

        $this->redirectRoute('credit-cards.index', ['team' => $this->teamModel->slug], navigate: true);
    }

    public function render()
    {
        return $this->view()->title($this->creditCardModel->name);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ $creditCardModel->name }}</flux:heading>
        <flux:button variant="primary" :href="route('credit-cards.edit', [$teamModel, $creditCardModel])" wire:navigate>
            Edit
        </flux:button>
    </div>

    <x-description.list class="mt-2.5">
        <x-description.term>Name on card</x-description.term>
        <x-description.details>
            <flux:input :value="$creditCardModel->name_on_card" readonly copyable class="max-w-lg" />
        </x-description.details>

        <x-description.term>Card number</x-description.term>
        <x-description.details>
            <flux:input type="password" :value="$creditCardModel->card_number" viewable copyable class="max-w-lg" />
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
            <flux:input type="password" :value="$creditCardModel->cvv" viewable copyable class="max-w-xs" />
        </x-description.details>

        @if ($creditCardModel->notes)
            <x-description.term>Notes</x-description.term>
            <x-description.details>
                <flux:textarea :value="$creditCardModel->notes" readonly class="max-w-lg" />
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

    <flux:separator class="mt-8" />

    <div class="mt-4">
        <flux:button variant="danger" wire:click="deleteCreditCard" wire:confirm="Delete this credit card? This cannot be undone.">
            Delete credit card
        </flux:button>
    </div>
</section>
