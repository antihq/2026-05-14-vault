<?php

use App\Rules\Luhn;
use App\Rules\ValidExpiryDate;
use App\Models\CreditCard;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public CreditCard $creditCardModel;

    public string $name = '';

    public string $nameOnCard = '';

    public string $cardNumber = '';

    public string $expiryDate = '';

    public string $cvv = '';

    public string $notes = '';

    public function mount(Team $team, CreditCard $creditCard): void
    {
        $this->teamModel = $team;
        $this->creditCardModel = $creditCard;
        $this->name = $creditCard->name;
        $this->nameOnCard = $creditCard->name_on_card;
        $this->cardNumber = $creditCard->card_number;
        $this->expiryDate = $creditCard->expiry_date;
        $this->cvv = $creditCard->cvv;
        $this->notes = $creditCard->notes ?? '';
    }

    public function updateCreditCard(): void
    {
        Gate::authorize('update', $this->creditCardModel);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'nameOnCard' => ['required', 'string', 'max:255'],
            'cardNumber' => ['required', 'string', 'min:13', 'max:19', new Luhn],
            'expiryDate' => ['required', 'string', 'max:5', new ValidExpiryDate],
            'cvv' => ['required', 'string', 'min:3', 'max:4'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);

        $this->creditCardModel->update([
            'name' => $validated['name'],
            'name_on_card' => $validated['nameOnCard'],
            'card_number' => $validated['cardNumber'],
            'expiry_date' => $validated['expiryDate'],
            'cvv' => $validated['cvv'],
            'notes' => $validated['notes'] ?: null,
        ]);

        Flux::toast(variant: 'success', text: 'Credit card updated and re-encrypted.');

        $this->redirectRoute('credit-cards.index', ['team' => $this->teamModel->slug], navigate: true);
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
        return $this->view()->title('Edit — ' . $this->creditCardModel->name);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit credit card</flux:heading>

    <form wire:submit="updateCreditCard" class="mt-6 space-y-8 max-w-xl">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:description>A label for this card, e.g. "Personal Visa"</flux:description>
            <flux:input wire:model="name" type="text" required />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Name on card</flux:label>
            <flux:description>The cardholder name exactly as printed on the card</flux:description>
            <flux:input wire:model="nameOnCard" type="text" required />
            <flux:error name="nameOnCard" />
        </flux:field>

        <flux:field>
            <flux:label>Card number</flux:label>
            <flux:description>The 13–19 digit number on the front or back of the card</flux:description>
            <flux:input wire:model="cardNumber" type="text" required mask="9999 9999 9999 9999" icon:trailing="credit-card" />
            <flux:error name="cardNumber" />
        </flux:field>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Expiry date</flux:label>
                <flux:input wire:model="expiryDate" type="text" required mask="99/99" placeholder="MM/YY" />
                <flux:description>Month and year printed on the card</flux:description>
                <flux:error name="expiryDate" />
            </flux:field>

            <flux:field>
                <flux:label>CVV</flux:label>
                <flux:input wire:model="cvv" type="password" required viewable mask="9999" />
                <flux:description>3-digit code on the back (4 digits on the front for Amex)</flux:description>
                <flux:error name="cvv" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Notes</flux:label>
            <flux:description>Billing address, PIN, or other details</flux:description>
            <flux:textarea wire:model="notes" />
            <flux:error name="notes" />
        </flux:field>

        <div class="flex">
            <flux:spacer />
            <flux:button variant="primary" type="submit" class="max-sm:w-full">
                Update credit card
            </flux:button>
        </div>
    </form>

    <div class="max-w-xl">
        <flux:separator class="my-8" />

        <div class="flex">
            <flux:spacer />
            <flux:button class="text-red-700! dark:text-red-300! max-sm:w-full" wire:click="deleteCreditCard" wire:confirm="Delete this credit card? This cannot be undone.">
                Delete credit card
            </flux:button>
        </div>
    </div>
</section>
