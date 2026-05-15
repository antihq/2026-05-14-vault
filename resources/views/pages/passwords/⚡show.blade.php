<?php

use App\Models\Password;
use App\Models\Team;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public Password $passwordModel;

    public function mount(Team $team, Password $password): void
    {
        $this->teamModel = $team;
        $this->passwordModel = $password;
    }

    public function render()
    {
        return $this->view()->title($this->passwordModel->name);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ $passwordModel->name }}</flux:heading>
        <flux:button :href="route('passwords.edit', [$teamModel, $passwordModel])" wire:navigate>
            Edit
        </flux:button>
    </div>

    <x-description.list class="mt-2.5">
        <x-description.term>Username</x-description.term>
        <x-description.details>
            <flux:input :value="$passwordModel->username" readonly copyable class="max-w-lg" />
        </x-description.details>

        <x-description.term>Password</x-description.term>
        <x-description.details>
            <flux:input type="password" :value="$passwordModel->password" viewable copyable class="max-w-lg" />
        </x-description.details>

        @if ($passwordModel->website)
            <x-description.term>Website</x-description.term>
            <x-description.details>
                <flux:link :href="$passwordModel->website" target="_blank">{{ $passwordModel->website }}</flux:link>
            </x-description.details>
        @endif

        @if ($passwordModel->notes)
            <x-description.term>Notes</x-description.term>
            <x-description.details>
                <flux:textarea :value="$passwordModel->notes" readonly class="max-w-lg" />
            </x-description.details>
        @endif

        <x-description.term>Team</x-description.term>
        <x-description.details>{{ $teamModel->name }}</x-description.details>

        <x-description.term>Encryption</x-description.term>
        <x-description.details><flux:badge size="sm" inset="top bottom">Encrypted at rest</flux:badge></x-description.details>

        <x-description.term>Created</x-description.term>
        <x-description.details>{{ $passwordModel->created_at->format('M j, Y \a\t H:i') }}</x-description.details>

        <x-description.term>Updated</x-description.term>
        <x-description.details>{{ $passwordModel->updated_at->format('M j, Y \a\t H:i') }}</x-description.details>
    </x-description.list>
</section>
