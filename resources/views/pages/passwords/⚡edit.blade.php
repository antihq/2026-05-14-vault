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
            'password' => ['required', 'string', 'min:8'],
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

        $this->redirectRoute('passwords.show', ['team' => $this->teamModel->slug, 'password' => $this->passwordModel->id], navigate: true);
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
        return $this->view()->title('Edit — ' . $this->passwordModel->name);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">Edit password</flux:heading>

    <form wire:submit="updatePassword" class="mt-6 space-y-8 max-w-xl">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:description>A label for this entry, e.g. "Work email" or "Netflix"</flux:description>
            <flux:input wire:model="name" type="text" required />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Username</flux:label>
            <flux:description>The email or username used to sign in</flux:description>
            <flux:autocomplete wire:model="username" required>
                @foreach($this->usernameSuggestions as $suggestion)
                    <flux:autocomplete.item>{{ $suggestion }}</flux:autocomplete.item>
                @endforeach
            </flux:autocomplete>
            <flux:error name="username" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:description>The sign-in password — use Generate for a random 16-character string</flux:description>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <flux:input wire:model="password" type="password" required viewable class="sm:flex-1" />
                <flux:button wire:click.prevent="generatePassword" type="button" class="sm:w-auto w-full">Generate</flux:button>
            </div>
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
                Update password
            </flux:button>
        </div>
    </form>

    <div class="max-w-xl">
        <flux:separator class="my-8" />

        <div class="flex">
            <flux:spacer />
            <flux:button class="text-red-700! dark:text-red-300! max-sm:w-full" wire:click="deletePassword" wire:confirm="Delete this password? This cannot be undone.">
                Delete password
            </flux:button>
        </div>
    </div>
</section>
