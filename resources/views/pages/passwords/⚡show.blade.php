<?php

use App\Models\Password;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
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

    public function deletePassword(): void
    {
        Gate::authorize('delete', $this->passwordModel);

        $this->passwordModel->delete();

        Flux::toast(variant: 'success', text: 'Password deleted.');

        $this->redirectRoute('passwords.index', ['team' => $this->teamModel->slug], navigate: true);
    }

    public function render()
    {
        return $this->view()->title($this->passwordModel->name);
    }
}; ?>

<section class="w-full">
    <div class="flex items-end justify-between gap-4">
        <flux:heading size="xl" level="1">{{ $passwordModel->name }}</flux:heading>
        <flux:button variant="primary" :href="route('passwords.edit', [$teamModel, $passwordModel])" wire:navigate>
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
    </x-description.list>

    <flux:separator class="mt-8" />

    <div class="mt-4">
        <flux:button variant="danger" wire:click="deletePassword" wire:confirm="Are you sure you want to delete this password?">
            Delete password
        </flux:button>
    </div>
</section>
