<?php

use App\Models\Team;
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
}; ?>

<section class="w-full max-w-2xl">
    <div class="flex gap-3 items-baseline">
        <div class="flex items-center gap-2">
            <flux:heading class="lowercase" level="1">Credit Cards</flux:heading>
            <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->creditCards->total() }}</span>
        </div>
        <flux:link :href="route('credit-cards.create', ['current_team' => $teamModel])" wire:navigate>
            New credit card
        </flux:link>
    </div>

    <div class="mt-3 max-w-sm">
        <flux:input wire:model.live="search" placeholder="Search credit cards..." />
    </div>

    <div class="mt-2">
        <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/5">
            @foreach ($this->creditCards as $creditCard)
                <li class="py-2"
                    x-data="{
                        copiedNumber: false,
                        copiedCvv: false,
                        showNumber: false,
                        showCvv: false,
                        showNotes: false,
                        cardNumber: {{ \Illuminate\Support\Js::encode($creditCard->card_number) }},
                        cvv: {{ \Illuminate\Support\Js::encode($creditCard->cvv) }}
                    }"
                >
                    <div class="flex flex-wrap justify-between gap-x-3">
                        <div class="flex flex-1 flex-wrap gap-x-3 items-center">
                            <p class="font-semibold">{{ $creditCard->name }}</p>
                            <flux:link :href="route('credit-cards.edit', ['current_team' => $teamModel, 'creditCard' => $creditCard])" wire:navigate>
                                Edit
                            </flux:link>
                        </div>
                        @if ($creditCard->is_expired)
                            <span>expired</span>
                        @endif
                    </div>

                    <div class="mt-1 flex gap-x-3">
                        <div>
                            <span x-show="!showNumber">{{ $creditCard->masked_number }}</span>
                            <span x-show="showNumber" x-cloak x-text="cardNumber" class="font-mono"></span>
                        </div>

                        <div class="flex gap-1.5">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="lime"
                                x-on:click="navigator.clipboard.writeText(cardNumber); copiedNumber = true; setTimeout(() => copiedNumber = false, 2000)"
                                class="lowercase"
                            >
                                <span x-text="copiedNumber ? 'Copied!' : 'Copy number'"></span>
                            </flux:button>

                            <flux:button
                                size="xs"
                                variant="filled"
                                x-on:click="showNumber = !showNumber"
                                class="lowercase"
                            >
                                <span x-text="showNumber ? 'Hide' : 'Show'"></span>
                            </flux:button>
                        </div>
                    </div>

                    <div>
                        Expires {{ $creditCard->expiry_date }}
                    </div>

                    <div class="mt-1 flex gap-x-3">
                        <div>
                            <span x-show="!showCvv">•••</span>
                            <span x-show="showCvv" x-cloak x-text="cvv" class="font-mono"></span>
                        </div>

                        <div class="flex gap-1.5">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="lime"
                                x-on:click="navigator.clipboard.writeText(cvv); copiedCvv = true; setTimeout(() => copiedCvv = false, 2000)"
                                class="lowercase"
                            >
                                <span x-text="copiedCvv ? 'Copied!' : 'Copy cvv'"></span>
                            </flux:button>

                            <flux:button
                                size="xs"
                                variant="filled"
                                x-on:click="showCvv = !showCvv"
                                class="lowercase"
                            >
                                <span x-text="showCvv ? 'Hide' : 'Show'"></span>
                            </flux:button>
                        </div>
                    </div>

                    <div class="mt-2">
                        <div x-show="showNotes" x-cloak>
                            {!! Illuminate\Support\Str::markdown($creditCard->notes ?? '') !!}
                        </div>
                    </div>

                    <div class="flex items-center gap-1 mt-1">
                        <flux:button
                            size="xs"
                            variant="filled"
                            x-on:click="showNotes = !showNotes"
                            :disabled="! $creditCard->notes"
                            class="lowercase"
                        >
                            <span x-text="showNotes ? 'Hide notes' : 'View notes'"></span>
                        </flux:button>
                    </div>
                </li>
            @endforeach
        </ul>

        <flux:pagination :paginator="$this->creditCards" pagination:scroll-to class="mt-4" />
    </div>
</section>
