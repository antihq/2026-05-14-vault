<?php

use App\Models\Password;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public Password $passwordModel;

    public function mount(Team $current_team, Password $password): void
    {
        $this->teamModel = $current_team;
        $this->passwordModel = $password;

        Gate::authorize('view', $password);

        $password->update(['last_viewed_at' => now()]);
    }

    public function render()
    {
        return $this->view()->title($this->passwordModel->name);
    }
}; ?>

<section class="w-full max-w-2xl" x-data="{
    showPass: false,
    username: {{ \Illuminate\Support\Js::encode($passwordModel->username) }},
    password: {{ \Illuminate\Support\Js::encode($passwordModel->password) }}
}">

    <div class="flex justify-between flex-wrap items-center gap-x-4 gap-y-4">
        <flux:heading level="1">{{ $passwordModel->name }}</flux:heading>

        <div class="flex flex-wrap items-center gap-4">
            <flux:button
                :href="route('passwords.edit', ['current_team' => $teamModel, 'password' => $passwordModel])"
                wire:navigate
                inset="top bottom"
                class="max-lg:hidden"
            >
                Edit
            </flux:button>

            <flux:button
                x-on:click="navigator.clipboard.writeText(username); $flux.toast('Username copied.', { variant: 'success' })"
                inset="top bottom"
                class="max-lg:hidden"
            >
                Copy username
            </flux:button>

            <flux:button
                variant="primary"
                x-on:click="navigator.clipboard.writeText(password); $flux.toast('Password copied.', { variant: 'success' })"
                inset="top bottom"
            >
                Copy password
            </flux:button>

            <flux:dropdown class="-my-2.5 lg:hidden" align="end">
                <flux:button icon="ellipsis-horizontal" variant="ghost" class="text-zinc-800" inset="left" />
                <flux:navmenu>
                    <flux:navmenu.item :href="route('passwords.edit', ['current_team' => $teamModel, 'password' => $passwordModel])" class="text-zinc-800">Edit</flux:navmenu.item>
                    <flux:navmenu.item x-on:click="navigator.clipboard.writeText(username); $flux.toast('Username copied.', { variant: 'success' })" class="text-zinc-800">Copy username</flux:navmenu.item>
                </flux:navmenu>
            </flux:dropdown>
        </div>
    </div>

    <flux:card class="mt-6.5 p-0">
        <x-description.list>
            @if ($passwordModel->website)
                <x-description.term class="pl-4 max-sm:px-4">Website</x-description.term>
                <x-description.details class="pr-4 max-sm:px-4 font-medium">
                    <flux:link :href="$passwordModel->website" target="_blank" variant="ghost" class="truncate lowercase">
                        {{ parse_url($passwordModel->website, PHP_URL_HOST) ?: $passwordModel->website }}
                    </flux:link>
                </x-description.details>
            @endif

            <x-description.term class="pl-4 max-sm:px-4">Username</x-description.term>
            <x-description.details class="break-all pr-4 max-sm:px-4 font-medium">{{ $passwordModel->username }}</x-description.details>

            <x-description.term class="pl-4 max-sm:px-4">Password</x-description.term>
            <x-description.details class="flex gap-x-4 items-center pr-4 max-sm:px-4 font-medium">
                <span x-show="!showPass" x-text="'•'.repeat(password.length)" class="font-mono"></span>
                <span x-show="showPass" x-cloak x-text="password" class="font-mono break-all"></span>
                <flux:button
                    size="sm"
                    x-on:click="showPass = !showPass"
                    inset="top bottom"
                >
                    <span x-text="showPass ? 'Hide' : 'Show'"></span>
                </flux:button>
            </x-description.details>

            @if ($passwordModel->notes)
                <x-description.term class="pl-4 max-sm:px-4">Notes</x-description.term>
                <x-description.details class="pr-4 max-sm:px-4">
                    {!! Illuminate\Support\Str::markdown($passwordModel->notes) !!}
                </x-description.details>
            @endif
        </x-description.list>
    </flux:card>
</section>
