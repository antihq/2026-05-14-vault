<?php

use App\Models\Password;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Passwords')] class extends Component
{
    use WithPagination;

    public Team $teamModel;

    public string $search = '';

    public function mount(Team $current_team): void
    {
        $this->teamModel = $current_team;
    }

    #[Computed]
    public function passwords()
    {
        return $this->teamModel->passwords()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->paginate(50);
    }

    public function deletePassword(Password $password): void
    {
        Gate::authorize('delete', $password);

        $password->delete();

        Flux::toast(variant: 'success', text: 'Password deleted.');
    }
}; ?>

<section class="w-full max-w-2xl">
    <div class="flex gap-3 items-baseline justify-between">
        <div class="flex items-center gap-2">
            <flux:heading level="1">Passwords</flux:heading>
            <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->passwords->total() }}</span>
        </div>
        <flux:button :href="route('passwords.create', ['current_team' => $teamModel])" variant="primary" inset="top bottom" wire:navigate>
            New password
        </flux:button>
    </div>

    <div class="mt-6.5">
        <flux:input wire:model.live="search" placeholder="Search..." clearable />
    </div>

    <flux:card class="mt-4 p-0!">
        <ul role="list" class="divide-y divide-zinc-100 dark:divide-zinc-700">
            @foreach ($this->passwords as $password)
                <li wire:key="{{ $password->id }}" class="relative flex justify-between gap-x-6 px-4 py-3"
                    x-data="{
                        username: {{ \Illuminate\Support\Js::encode($password->username) }},
                        password: {{ \Illuminate\Support\Js::encode($password->password) }}
                    }"
                >
                    <div class="min-w-0 flex-auto">
                        <p class="font-medium">
                            <a href="{{ route('passwords.show', ['current_team' => $teamModel, 'password' => $password]) }}" wire:navigate>
                                <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                {{ $password->name }}
                            </a>
                        </p>
                        <flux:text class="flex truncate mt-1">
                            {{ $password->username }}
                        </flux:text>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-4">
                        <div class="hidden sm:flex sm:flex-col sm:items-end relative z-10">
                            @if ($password->website)
                                <flux:link :href="$password->website" target="_blank" variant="ghost" class="truncate lowercase">
                                    {{ parse_url($password->website, PHP_URL_HOST) ?: $password->website }}
                                </flux:link>
                            @endif
                        </div>
                        <flux:dropdown align="end" class="relative z-10">
                            <flux:button icon="ellipsis-horizontal" variant="ghost" inset="right" />
                            <flux:menu>
                                <flux:menu.item :href="route('passwords.edit', ['current_team' => $teamModel, 'password' => $password])" wire:navigate>
                                    Edit
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item x-on:click="navigator.clipboard.writeText(username); $flux.toast('Username copied.', { variant: 'success' })">
                                    Copy username
                                </flux:menu.item>
                                <flux:menu.item x-on:click="navigator.clipboard.writeText(password); $flux.toast('Password copied.', { variant: 'success' })">
                                    Copy password
                                </flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item
                                    variant="danger"
                                    wire:click="deletePassword({{ $password->id }})"
                                    wire:confirm="Delete this password? This cannot be undone."
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
        <flux:pagination :paginator="$this->passwords" pagination:scroll-to />
    </div>
</section>
