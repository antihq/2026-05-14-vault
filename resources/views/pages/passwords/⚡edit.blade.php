<?php

use App\Models\Password;
use App\Models\Team;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public ?int $moveToTeamId = null;

    public function mount(Team $current_team, Password $password): void
    {
        $this->teamModel = $current_team;
        $this->passwordModel = $password;
        $this->name = $password->name;
        $this->username = $password->username;
        $this->password = $password->password;
        $this->website = $password->website ?? '';
        $this->notes = $password->notes ?? '';

        $this->moveToTeamId = $this->movableTeams->first()?->id;
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

    /**
     * The teams the current user can move this password into
     * (every team they belong to, except the password's current team).
     */
    #[Computed]
    public function movableTeams()
    {
        return auth()->user()
            ->teams()
            ->where('teams.id', '!=', $this->teamModel->id)
            ->orderByRaw('LOWER(teams.name)')
            ->get(['teams.id', 'teams.name', 'teams.slug', 'teams.is_personal']);
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

    public function movePassword(): void
    {
        Gate::authorize('move', $this->passwordModel);

        $validated = $this->validate([
            'moveToTeamId' => ['required', 'integer', Rule::in($this->movableTeams->pluck('id'))],
        ], [
            'moveToTeamId.required' => 'Please choose a team to move this password to.',
            'moveToTeamId.in' => 'You can only move passwords to teams you belong to.',
        ]);

        $target = $this->movableTeams->firstWhere('id', $validated['moveToTeamId']);

        $this->passwordModel->update(['team_id' => $validated['moveToTeamId']]);

        Flux::toast(variant: 'success', text: "Password moved to {$target->name}.");

        $this->redirectRoute('passwords.show', ['current_team' => $target->slug, 'password' => $this->passwordModel], navigate: true);
    }

    public function render()
    {
        return $this->view()->title('Edit — ' . $this->passwordModel->name);
    }
}; ?>

<section class="w-full max-w-xl space-y-12">
    <div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('passwords.show', ['current_team' => $teamModel, 'password' => $passwordModel])">{{ $passwordModel->name }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Edit</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <form wire:submit="updatePassword" class="mt-4">
            <flux:card class="px-4 py-5">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model="name" type="text" required />
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
                    Update password
                </flux:button>
            </div>
        </form>
    </div>

    @can('move', $passwordModel)
        @if ($this->movableTeams->isNotEmpty())
            <flux:separator variant="subtle" />

            <div>
                <flux:heading level="2">Move to team</flux:heading>
                <flux:description class="mt-1">
                    Transfer this password to another team you belong to. Members of the current team will lose access.
                </flux:description>

                <form wire:submit="movePassword" class="mt-2">
                    <flux:card class="px-4 py-5">
                        <flux:field>
                            <flux:label>Destination team</flux:label>
                            <flux:select wire:model="moveToTeamId" placeholder="Choose a team...">
                                @foreach ($this->movableTeams as $team)
                                    <flux:select.option :value="$team->id">{{ $team->name }}{{ $team->is_personal ? ' (personal)' : '' }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="moveToTeamId" />
                        </flux:field>
                    </flux:card>
                    <div class="mt-4 flex">
                        <flux:spacer />
                        <flux:button type="submit" variant="primary" wire:confirm="Move this password to the selected team? Members of the current team will lose access.">
                            Move password
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    @endcan

    <flux:separator variant="subtle" />

    <div>
        <flux:heading level="2">Delete password</flux:heading>
        <flux:description class="mt-1">
            Permanently remove this password from the team. This cannot be undone.
        </flux:description>

        <form wire:submit="deletePassword" wire:confirm="Delete this password? This cannot be undone." class="mt-2">
            <flux:button type="submit" variant="danger">
                Delete password
            </flux:button>
        </form>
    </div>
</section>
