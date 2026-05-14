<?php

use App\Models\Password;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public Password $passwordModel;

    public string $name = '';

    public string $username = '';

    public string $password = '';

    public string $website = '';

    public string $notes = '';

    public function mount(Team $team, Password $password): void
    {
        $this->teamModel = $team;
        $this->passwordModel = $password;
        $this->name = $password->name;
        $this->username = $password->username;
        $this->password = $password->password;
        $this->website = $password->website ?? '';
        $this->notes = $password->notes ?? '';
    }

    public function generatePassword(): void
    {
        $this->password = Str::password(16);
    }

    public function updatePassword(): void
    {
        Gate::authorize('update', $this->passwordModel);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);

        $this->passwordModel->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'website' => $validated['website'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        Flux::toast(variant: 'success', text: 'Password updated.');

        $this->redirectRoute('passwords.show', ['team' => $this->teamModel->slug, 'password' => $this->passwordModel->id], navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit — ' . $this->passwordModel->name);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit password</flux:heading>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name" type="text" required class="max-w-lg" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Username</flux:label>
            <flux:input wire:model="username" type="text" required class="max-w-lg" />
            <flux:error name="username" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input.group>
                <flux:input wire:model="password" type="password" required viewable class="max-w-lg" />
                <flux:button wire:click.prevent="generatePassword" type="button">Generate</flux:button>
            </flux:input.group>
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>Website</flux:label>
            <flux:input wire:model="website" type="url" placeholder="https://example.com" class="max-w-lg" />
            <flux:error name="website" />
        </flux:field>

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
