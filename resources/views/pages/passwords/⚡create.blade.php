<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Password')] class extends Component
{
    public Team $teamModel;

    public string $name = '';

    public string $username = '';

    public string $password = '';

    public string $website = '';

    public string $notes = '';

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
    }

    public function generatePassword(): void
    {
        $this->password = Str::password(16);
    }

    public function createPassword(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);

        $password = $this->teamModel->passwords()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'website' => $validated['website'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ]);

        Flux::toast(variant: 'success', text: 'Password created and encrypted.');

        $this->redirectRoute('passwords.show', ['team' => $this->teamModel->slug, 'password' => $password->id], navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">New password</flux:heading>

    <form wire:submit="createPassword" class="mt-6 space-y-8 max-w-xl">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:description>A label for this entry, e.g. "Work email" or "Netflix"</flux:description>
            <flux:input wire:model="name" type="text" required autofocus />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Username</flux:label>
            <flux:description>The email or username used to sign in</flux:description>
            <flux:input wire:model="username" type="text" required />
            <flux:error name="username" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:description>The sign-in password — use Generate for a random 16-character string</flux:description>
            <flux:input.group>
                <flux:input wire:model="password" type="password" required viewable />
                <flux:button wire:click.prevent="generatePassword" type="button">Generate</flux:button>
            </flux:input.group>
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>Website</flux:label>
            <flux:description>The login page URL for this service</flux:description>
            <flux:input wire:model="website" type="url" placeholder="https://example.com" />
            <flux:error name="website" />
        </flux:field>

        <flux:field>
            <flux:label>Notes</flux:label>
            <flux:description>Security questions, recovery codes, or other details</flux:description>
            <flux:textarea wire:model="notes" />
            <flux:error name="notes" />
        </flux:field>

        <div class="flex">
            <flux:spacer />
            <flux:button variant="primary" type="submit" class="max-sm:w-full">
                Create password
            </flux:button>
        </div>
    </form>
</section>
