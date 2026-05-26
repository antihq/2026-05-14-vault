<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Passwords')] class extends Component
{
    use WithPagination;

    public Team $teamModel;

    public string $search = '';

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
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
}; ?>

<section class="w-full max-w-2xl">
    <div class="flex gap-3 items-baseline">
        <flux:heading class="lowercase" level="1">Passwords</flux:heading>
        <flux:link :href="route('passwords.create', $teamModel)" wire:navigate>
            New password
        </flux:link>
    </div>

    <div class="mt-3 max-w-sm">
        <flux:input wire:model.live="search" placeholder="Search passwords..." />
    </div>

    <div class="mt-2">
        <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/5">
            @foreach ($this->passwords as $password)
                <li class="py-2"
                    x-data="{
                        copiedUser: false,
                        copiedPass: false,
                        showPass: false,
                        showNotes: false,
                        username: {{ \Illuminate\Support\Js::encode($password->username) }},
                        password: {{ \Illuminate\Support\Js::encode($password->password) }}
                    }"
                >
                    <div class="flex flex-wrap justify-between gap-x-3">
                        <div class="flex flex-1 flex-wrap gap-x-3 items-center">
                            <p class="font-semibold">{{ $password->name }}</p>
                            <flux:link :href="route('passwords.edit', [$teamModel, $password])" wire:navigate>
                                Edit
                            </flux:link>
                        </div>
                        @if ($password->website)
                            <span class="truncate" title="{{ $password->website }}">
                                {{ parse_url($password->website, PHP_URL_HOST) ?: $password->website }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-1 flex flex-wrap gap-x-3">
                        <div class="min-w-0 truncate">
                            {{ $password->username }}
                        </div>

                        <div class="flex gap-1.5 shrink-0">
                            <flux:button
                                size="xs"
                                variant="filled"
                                color="lime"
                                x-on:click="navigator.clipboard.writeText(username); copiedUser = true; setTimeout(() => copiedUser = false, 2000)"
                                class="lowercase"
                            >
                                <span x-text="copiedUser ? 'Copied!' : 'Copy'"></span>
                            </flux:button>
                        </div>
                    </div>

                    <div class="mt-1 flex flex-wrap gap-x-3">
                        <div class="min-w-0">
                            <span x-show="!showPass" x-text="'•'.repeat(password.length)" class="font-mono truncate block"></span>
                            <span x-show="showPass" x-cloak x-text="password" class="font-mono truncate block"></span>
                        </div>

                        <div class="flex gap-1.5 shrink-0">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="lime"
                                x-on:click="navigator.clipboard.writeText(password); copiedPass = true; setTimeout(() => copiedPass = false, 2000)"
                                class="lowercase"
                            >
                                <span x-text="copiedPass ? 'Copied!' : (showPass ? 'Copy' : 'Copy password')"></span>
                            </flux:button>

                            <flux:button
                                size="xs"
                                variant="filled"
                                x-on:click="showPass = !showPass"
                                class="lowercase"
                            >
                                <span x-text="showPass ? 'Hide' : 'Show'"></span>
                            </flux:button>
                        </div>
                    </div>

                    <div class="mt-1">
                        <div x-show="showNotes" x-cloak>
                            {!! Illuminate\Support\Str::markdown($password->notes ?? '') !!}
                        </div>
                    </div>

                    <div class="flex items-center gap-1 mt-1">
                        <flux:button
                            size="xs"
                            variant="filled"
                            x-on:click="showNotes = !showNotes"
                            :disabled="! $password->notes"
                            class="lowercase"
                        >
                            <span x-text="showNotes ? 'Hide notes' : 'View notes'"></span>
                        </flux:button>
                    </div>
                </li>
            @endforeach
        </ul>

        <flux:pagination :paginator="$this->passwords" pagination:scroll-to class="mt-4" />
    </div>
</section>
