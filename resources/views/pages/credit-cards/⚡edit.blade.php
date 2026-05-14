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
            'cardNumber' => ['required', 'string', 'min:13', 'max:19'],
            'expiryDate' => ['required', 'string', 'max:5'],
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

        Flux::toast(variant: 'success', text: 'Credit card updated.');

        $this->redirectRoute('credit-cards.show', ['team' => $this->teamModel->slug, 'creditCard' => $this->creditCardModel->id], navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit — ' . $this->creditCardModel->name);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit credit card</flux:heading>

    <form wire:submit="updateCreditCard" class="mt-6 space-y-6">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name" type="text" required class="max-w-lg" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Name on card</flux:label>
            <flux:input wire:model="nameOnCard" type="text" required class="max-w-lg" />
            <flux:error name="nameOnCard" />
        </flux:field>

        <flux:field>
            <flux:label>Card number</flux:label>
            <flux:input wire:model="cardNumber" type="text" required mask="9999 9999 9999 9999" icon:trailing="credit-card" class="max-w-lg" />
            <flux:error name="cardNumber" />
        </flux:field>

        <div class="grid grid-cols-2 gap-4 max-w-lg">
            <flux:field>
                <flux:label>Expiry date</flux:label>
                <flux:input wire:model="expiryDate" type="text" required mask="99/99" placeholder="MM/YY" />
                <flux:error name="expiryDate" />
            </flux:field>

            <flux:field>
                <flux:label>CVV</flux:label>
                <flux:input wire:model="cvv" type="password" required viewable mask="9999" />
                <flux:error name="cvv" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Notes</flux:label>
            <flux:textarea wire:model="notes" class="max-w-lg" />
            <flux:error name="notes" />
        </flux:field>

        <flux:button variant="primary" type="submit">
            Save
        </flux:button>
    </form>
</section>
