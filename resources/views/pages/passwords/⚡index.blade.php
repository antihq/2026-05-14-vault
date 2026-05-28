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
}; ?>

<section class="w-full max-w-2xl">
    <div class="flex gap-3 items-baseline">
        <div class="flex items-center gap-2">
            <flux:heading class="lowercase" level="1">Passwords</flux:heading>
            <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->passwords->total() }}</span>
        </div>
        <flux:link :href="route('passwords.create', ['current_team' => $teamModel])" wire:navigate>
            New password
        </flux:link>
    </div>

    <div class="mt-4 max-w-sm">
        <flux:input wire:model.live="search" placeholder="search" clearable />
    </div>

    <div class="mt-8">
        <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/5">
            @foreach ($this->passwords as $password)
                <li wire:key="{{ $password->id }}" class="py-2"
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
                        <p class="font-semibold">{{ $password->name }}</p>
                        <div class="flex flex-wrap gap-x-3">
                            @if ($password->website)
                                <span class="truncate" title="{{ $password->website }}">
                                    {{ parse_url($password->website, PHP_URL_HOST) ?: $password->website }}
                                </span>
                            @endif
                            <flux:link :href="route('passwords.edit', ['current_team' => $teamModel, 'password' => $password])" wire:navigate>
                                Edit
                            </flux:link>
                        </div>
                    </div>

                    <div class="break-all">
                        {{ $password->username }}
                    </div>

                    <div>
                        <span x-show="!showPass" x-text="'•'.repeat(password.length)" class="font-mono break-all"></span>
                        <span x-show="showPass" x-cloak x-text="password" class="font-mono break-all"></span>
                        <flux:button
                            size="xs"
                            variant="filled"
                            x-on:click="showPass = !showPass"
                            class="lowercase"
                        >
                            <span x-text="showPass ? 'Hide' : 'Show'"></span>
                        </flux:button>
                    </div>

                    <div>
                        <div x-show="showNotes" x-cloak>
                            {!! Illuminate\Support\Str::markdown($password->notes ?? '') !!}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-1 mt-2">
                            <flux:button
                                size="xs"
                                variant="primary"
                                color="lime"
                                x-on:click="navigator.clipboard.writeText(username); copiedUser = true; setTimeout(() => copiedUser = false, 2000)"
                                class="lowercase"
                            >
                                <span x-text="copiedUser ? 'Copied!' : 'Copy username'"></span>
                            </flux:button>

                            <flux:button
                                size="xs"
                                variant="primary"
                                color="lime"
                                x-on:click="navigator.clipboard.writeText(password); copiedPass = true; setTimeout(() => copiedPass = false, 2000)"
                                class="lowercase"
                            >
                                <span x-text="copiedPass ? 'Copied!' : 'Copy password'"></span>
                            </flux:button>

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
