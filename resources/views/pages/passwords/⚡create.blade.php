<?php

use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
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

    public function mount(Team $current_team): void
    {
        $this->teamModel = $current_team;
        $this->generatePassword();
    }

    #[Computed]
    public function usernameSuggestions()
    {
        return $this->teamModel->passwords()
            ->select('username')
            ->distinct()
            ->pluck('username');
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
            'password' => ['required', 'string'],
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

        $this->redirectRoute('passwords.index', ['current_team' => $this->teamModel->slug], navigate: true);
    }
}; ?>

<section class="w-full max-w-xl">
    <form wire:submit="createPassword">
        <flux:card class="px-4 pt-3 pb-4.5">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" type="text" required autofocus />
                <flux:description>A label for this entry, e.g. "Work email" or "Netflix"</flux:description>
                <flux:error name="name" />
            </flux:field>
            <flux:field class="mt-6">
                <flux:label>Username</flux:label>
                <flux:autocomplete wire:model="username" required>
                    @foreach($this->usernameSuggestions as $suggestion)
                        <flux:autocomplete.item>{{ $suggestion }}</flux:autocomplete.item>
                    @endforeach
                </flux:autocomplete>
                <flux:description>The email or username used to sign in</flux:description>
                <flux:error name="username" />
            </flux:field>
            <flux:field class="mt-6">
                <flux:label>Password</flux:label>
                <flux:input wire:model="password" type="password" required viewable clearable />
                <flux:button wire:click.prevent="generatePassword" size="sm" type="button" class="mt-2">Regenerate</flux:button>
                <flux:description>The sign-in password — auto-generated, overwrite or use Regenerate</flux:description>
                <flux:error name="password" />
            </flux:field>
            <flux:field class="mt-6">
                <flux:label>Website</flux:label>
                <flux:input wire:model="website" type="url" placeholder="https://example.com" />
                <flux:description>The login page URL for this service</flux:description>
                <flux:error name="website" />
            </flux:field>
            <flux:field class="mt-6">
                <flux:label>Notes</flux:label>
                <flux:textarea wire:model="notes" />
                <flux:error name="notes" />
                <flux:description>Security questions, recovery codes, or other details</flux:description>
            </flux:field>
        </flux:card>
        <div class="mt-4 flex">
            <flux:spacer />
            <flux:button variant="primary" type="submit">
                Create password
            </flux:button>
        </div>
    </form>
</section>
