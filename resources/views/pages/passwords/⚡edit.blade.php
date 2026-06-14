<?php

use App\Models\Password;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
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

    public function mount(Team $current_team, Password $password): void
    {
        $this->teamModel = $current_team;
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

    #[Computed]
    public function usernameSuggestions()
    {
        return $this->teamModel->passwords()
            ->select('username')
            ->distinct()
            ->pluck('username');
    }

    public function updatePassword(): void
    {
        Gate::authorize('update', $this->passwordModel);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
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

        Flux::toast(variant: 'success', text: 'Password updated and re-encrypted.');

        $this->redirectRoute('passwords.index', ['current_team' => $this->teamModel->slug], navigate: true);
    }

    public function deletePassword(): void
    {
        Gate::authorize('delete', $this->passwordModel);

        $this->passwordModel->delete();

        Flux::toast(variant: 'success', text: 'Password deleted.');

        $this->redirectRoute('passwords.index', ['current_team' => $this->teamModel->slug], navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit — ' . $this->passwordModel->name);
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:heading level="1" class="lowercase">edit password</flux:heading>

    <form wire:submit="updatePassword" class="mt-2 max-w-xl">
        <flux:field class="max-w-sm">
            <flux:label class="lowercase">Name</flux:label>
            <flux:input wire:model="name" type="text" required />
            <flux:description class="lowercase">A label for this entry, e.g. "Work email" or "Netflix"</flux:description>
            <flux:error name="name" />
        </flux:field>

        <flux:field class="mt-4 max-w-sm">
            <flux:label class="lowercase">Username</flux:label>
            <flux:autocomplete wire:model="username" required>
                @foreach($this->usernameSuggestions as $suggestion)
                    <flux:autocomplete.item>{{ $suggestion }}</flux:autocomplete.item>
                @endforeach
            </flux:autocomplete>
            <flux:description class="lowercase">The email or username used to sign in</flux:description>
            <flux:error name="username" />
        </flux:field>

        <flux:field class="mt-4 max-w-sm">
            <flux:label class="lowercase">Password</flux:label>
            <flux:input wire:model="password" type="password" required viewable clearable />
            <flux:button wire:click.prevent="generatePassword" size="xs" variant="filled" type="button" class="mt-1 lowercase">regenerate</flux:button>
            <flux:description class="lowercase">The sign-in password — auto-generated, overwrite or use Regenerate</flux:description>
            <flux:error name="password" />
        </flux:field>

        <flux:field class="mt-4 max-w-sm">
            <flux:label class="lowercase">Website</flux:label>
            <flux:input wire:model="website" type="url" placeholder="https://example.com" />
            <flux:description class="lowercase">The login page URL for this service</flux:description>
            <flux:error name="website" />
        </flux:field>

        <flux:field class="mt-4 max-w-sm">
            <flux:label class="lowercase">Notes</flux:label>
            <flux:textarea wire:model="notes" />
            <flux:error name="notes" />
            <flux:description class="lowercase">Security questions, recovery codes, or other details</flux:description>
        </flux:field>

        <div class="mt-4">
            <flux:button variant="primary" color="lime" type="submit" class="lowercase">
                update password
            </flux:button>
        </div>
    </form>

    <form wire:submit="deletePassword" wire:confirm="Delete this password? This cannot be undone." class="mt-8">
        <flux:button type="submit" variant="danger" class="lowercase">
            delete password
        </flux:button>
    </form>
</section>
