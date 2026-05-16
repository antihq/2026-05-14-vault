<?php

use App\Models\Team;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    public Team $teamModel;

    public function mount(Team $current_team): void
    {
        $this->teamModel = $current_team;
    }

    #[Computed]
    public function recentItems()
    {
        $passwords = $this->teamModel->passwords()
            ->select('id', 'name', 'username', 'updated_at')
            ->latest('updated_at')
            ->limit(25)
            ->get()
            ->each(fn ($p) => $p->type = 'password')
            ->each(fn ($p) => $p->key = $p->username);

        $creditCards = $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date', 'updated_at')
            ->latest('updated_at')
            ->limit(25)
            ->get()
            ->each(fn ($card) => $card->type = 'credit_card')
            ->each(fn ($card) => $card->key = '•••• •••• •••• ' . ($card->last_four ?? '    '));

        return $passwords->concat($creditCards)->sortByDesc('updated_at')->take(25)->values();
    }

    #[Computed]
    public function expiredCards()
    {
        return $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date')
            ->get()
            ->filter(fn ($card) => $card->isExpired)
            ->values();
    }

    #[Computed]
    public function expiringSoonCards()
    {
        return $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date')
            ->get()
            ->filter(function ($card) {
                if ($card->isExpired) {
                    return false;
                }

                $expiry = Carbon::createFromFormat('m/y', $card->expiry_date)->endOfMonth()->endOfDay();

                return $expiry->isFuture() && now()->diffInDays($expiry, false) <= 60;
            })
            ->values();
    }

    #[Computed]
    public function passwordCount(): int
    {
        return $this->teamModel->passwords()->count();
    }

    #[Computed]
    public function creditCardCount(): int
    {
        return $this->teamModel->creditCards()->count();
    }

    public function itemRoute(object $item): string
    {
        return $item->type === 'password'
            ? route('passwords.show', [$this->teamModel, $item->id])
            : route('credit-cards.show', [$this->teamModel, $item->id]);
    }
}; ?>

<section class="w-full space-y-10">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <flux:heading size="xl" level="1">Dashboard</flux:heading>
        <div class="flex gap-4">
            <flux:button :href="route('passwords.create', $teamModel)" wire:navigate>New password</flux:button>
            <flux:button :href="route('credit-cards.create', $teamModel)" wire:navigate>New credit card</flux:button>
        </div>
    </div>

    <x-description.list>
        <x-description.term>Passwords</x-description.term>
        <x-description.details>
            <flux:link :href="route('passwords.index', $teamModel)" wire:navigate>
                {{ $this->passwordCount }} {{ Str::plural('password', $this->passwordCount) }}
            </flux:link>
        </x-description.details>

        <x-description.term>Credit cards</x-description.term>
        <x-description.details>
            <flux:link :href="route('credit-cards.index', $teamModel)" wire:navigate>
                {{ $this->creditCardCount }} {{ Str::plural('card', $this->creditCardCount) }}
            </flux:link>
        </x-description.details>
    </x-description.list>

    @if ($this->recentItems->isNotEmpty())
        <div>
            <flux:heading size="lg" level="2">Recent items</flux:heading>
            <div class="mt-3">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Key</flux:table.column>
                        <flux:table.column align="end">Updated</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentItems as $item)
                            <flux:table.row :key="$item->type . '-' . $item->id">
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate :first="true" />
                                    <flux:badge size="sm" inset="top bottom">{{ $item->type === 'password' ? 'Password' : 'Credit card' }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="relative font-medium">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                    {{ $item->name }}
                                </flux:table.cell>
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                    {{ $item->key }}
                                </flux:table.cell>
                                <flux:table.cell class="relative whitespace-nowrap !text-zinc-500" align="end">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                    {{ $item->updated_at->diffForHumans() }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif

    @if ($this->expiredCards->isNotEmpty() || $this->expiringSoonCards->isNotEmpty())
        <div>
            <flux:heading size="lg" level="2">Card expiry</flux:heading>
            <div class="mt-3">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Number</flux:table.column>
                        <flux:table.column align="end">Expires</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->expiredCards->concat($this->expiringSoonCards) as $card)
                            <flux:table.row>
                                <flux:table.cell>
                                    @if ($card->isExpired)
                                        <flux:badge size="sm" color="red">Expired</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="amber">Expiring soon</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="relative font-medium">
                                    <x-table-row-link :href="route('credit-cards.show', [$teamModel, $card])" wire:navigate :first="true" />
                                    {{ $card->name }}
                                </flux:table.cell>
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="route('credit-cards.show', [$teamModel, $card])" wire:navigate />
                                    {{ $card->maskedNumber }}
                                </flux:table.cell>
                                <flux:table.cell class="relative whitespace-nowrap" align="end">
                                    <x-table-row-link :href="route('credit-cards.show', [$teamModel, $card])" wire:navigate />
                                    {{ $card->expiry_date }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif
</section>
