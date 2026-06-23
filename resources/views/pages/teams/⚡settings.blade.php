<?php

use App\Enums\TeamRole;
use App\Livewire\Forms\CreateInvitationForm;
use App\Livewire\Forms\DeleteTeamForm;
use App\Livewire\Forms\UpdateMemberRoleForm;
use App\Livewire\Forms\UpdateTeamForm;
use App\Models\Team;
use App\Models\User;
use App\Support\TeamPermissions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public UpdateTeamForm $teamForm;

    public DeleteTeamForm $deleteForm;

    public CreateInvitationForm $invitationForm;

    public UpdateMemberRoleForm $memberRoleForm;

    public function mount(): void
    {
        $this->teamForm->setTeam($this->team);
        $this->deleteForm->setTeam($this->team);
        $this->invitationForm->setTeam($this->team);
        $this->memberRoleForm->setTeam($this->team);
    }

    public function updateTeamName(): void
    {
        $team = $this->teamForm->save();

        $this->team = $team;

        Flux::toast(variant: 'success', text: 'Team updated.');

        $this->redirectRoute('teams.settings', ['team' => $team->fresh()->slug], navigate: true);
    }

    public function deleteTeam(): void
    {
        if (! $this->deleteForm->delete()) {
            return;
        }

        $this->redirectRoute('teams.switch', navigate: true);
    }

    public function editMember(int $userId): void
    {
        $member = $this->members->first(fn ($m) => $m->id === $userId);

        if (! $member) {
            return;
        }

        $this->memberRoleForm->setMember($userId, $member->pivot->role->value);
    }

    public function cancelEditMember(): void
    {
        $this->memberRoleForm->reset('memberId', 'role');
    }

    public function updateMemberRole(): void
    {
        $this->memberRoleForm->save();
    }

    public function removeMember(int $userId): void
    {
        Gate::authorize('removeMember', $this->team);

        $this->team->memberships()->where('user_id', $userId)->delete();

        $user = User::find($userId);

        if ($user && $user->isCurrentTeam($this->team)) {
            $user->switchTeam($user->personalTeam());
        }

        Flux::toast(variant: 'success', text: 'Member removed.');
    }

    public function createInvitation(): void
    {
        $this->invitationForm->save();

        Flux::toast(variant: 'success', text: 'Invitation sent.');
    }

    public function cancelInvitation(string $code): void
    {
        Gate::authorize('cancelInvitation', $this->team);

        $invitation = $this->team->invitations()->where('code', $code)->firstOrFail();

        abort_unless($invitation->team_id === $this->team->id, 404);

        $invitation->delete();
    }

    #[Computed]
    public function permissions(): TeamPermissions
    {
        return Auth::user()->toTeamPermissions($this->team);
    }

    #[Computed]
    public function ownerName(): ?string
    {
        return $this->team->owner()?->name;
    }

    #[Computed]
    public function availableRoles(): array
    {
        return TeamRole::assignable();
    }

    #[Computed]
    public function members()
    {
        return $this->team->members()->get();
    }

    #[Computed]
    public function invitations()
    {
        return $this->team->invitations()->whereNull('accepted_at')->get();
    }

    public function render()
    {
        return $this->view()->title($this->team->name);
    }
}; ?>

<section class="w-full max-w-xl">
    <flux:heading level="2">Team details</flux:heading>

    <form wire:submit="updateTeamName" class="mt-4">
        <flux:field>
            <flux:label>Owner</flux:label>
            <flux:input :value="$this->ownerName" type="text" required variant="filled" readonly />
        </flux:field>

        <flux:field class="mt-6">
            <flux:label>Team name</flux:label>
            <flux:input wire:model="teamForm.name" type="text" required data-test="team-name-input" :variant="!$this->permissions->canUpdateTeam ? 'filled' : null" :readonly="!$this->permissions->canUpdateTeam" />
            <flux:error name="teamForm.name" />
        </flux:field>

        <flux:spacer class="mt-6" />

        <flux:button type="submit" variant="primary" data-test="team-save-button" :disabled="!$this->permissions->canUpdateTeam">Update name</flux:button>
    </form>

    <flux:separator class="my-12" />

    <div class="flex items-center gap-2">
        <flux:heading level="2">Members</flux:heading>
        <flux:text size="sm">{{ $this->members->count() }}</flux:text>
    </div>

    <ul role="list" class="divide-y divide-zinc-100 dark:divide-zinc-700 max-sm:-mx-4">
        @foreach ($this->members as $member)
            <li class="py-6 max-sm:px-4" data-test="member-row">
                <div class="flex items-center gap-2">
                    <p class="font-medium">{{ $member->name }}</p>
                    <flux:badge color="fuchsia" size="sm">{{ $member->pivot->role->value }}</flux:badge>
                </div>
                <div class="flex flex-wrap gap-x-3 justify-between items-center mt-1">
                    <p>{{ $member->email }}</p>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" wire:click="editMember({{ $member->id }})" data-test="member-edit-button" :disabled="!$this->permissions->canUpdateMember || $member->pivot->role === \App\Enums\TeamRole::Owner">Edit role</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="removeMember({{ $member->id }})" wire:confirm="Remove {{ $member->name }} from this team?" data-test="member-remove-button" :disabled="!$this->permissions->canRemoveMember || $member->pivot->role === \App\Enums\TeamRole::Owner">
                            Remove
                        </flux:button>
                    </div>
                </div>
                @if ($this->memberRoleForm->memberId === $member->id)
                    <div class="mt-4 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <form wire:submit="updateMemberRole">
                            <flux:field>
                                <flux:label>Role</flux:label>
                                <flux:radio.group wire:model="memberRoleForm.role">
                                    @foreach ($this->availableRoles as $role)
                                        <flux:radio value="{{ $role['value'] }}" label="{{ $role['label'] }}" description="{{ $role['description'] }}" />
                                    @endforeach
                                </flux:radio.group>
                                <flux:error name="memberRoleForm.role" />
                            </flux:field>
                            <div class="mt-4 flex gap-2">
                                <flux:button type="submit" variant="primary" data-test="member-role-save">Save</flux:button>
                                <flux:button type="button" variant="ghost" wire:click="cancelEditMember">Cancel</flux:button>
                            </div>
                        </form>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    <flux:separator class="my-12" />

    <flux:heading level="2">Invite member</flux:heading>

    <form wire:submit="createInvitation" class="mt-4">
        <flux:fieldset :disabled="!$this->permissions->canCreateInvitation">
            <flux:field>
                <flux:label>Email address</flux:label>
                <flux:input wire:model="invitationForm.email" type="email" required autocomplete="email" data-test="invite-email" />
                <flux:error name="invitationForm.role" />
            </flux:field>

            <flux:field class="mt-6">
                <flux:label>Role</flux:label>
                <flux:radio.group wire:model="invitationForm.role" data-test="invite-role">
                    @foreach ($this->availableRoles as $role)
                        <flux:radio value="{{ $role['value'] }}" label="{{ $role['label'] }}" description="{{ $role['description'] }}" />
                    @endforeach
                </flux:radio.group>
            </flux:field>
        </flux:fieldset>

        <flux:spacer class="mt-6" />

        <flux:button type="submit" variant="primary" data-test="invite-submit" :disabled="!$this->permissions->canCreateInvitation">Send invitation</flux:button>
    </form>

    @if (filled($this->invitations) || $this->permissions->canCreateInvitation)
        <flux:separator class="my-12" />

        <div class="flex items-center gap-2">
            <flux:heading level="2">Pending invitations</flux:heading>
            <flux:text size="sm">{{ $this->invitations->count() }}</flux:text>
        </div>

        @if (filled($this->invitations))
            <ul role="list" class="divide-y divide-zinc-100 dark:divide-zinc-700 max-sm:-mx-4">
                @foreach ($this->invitations as $invitation)
                    <li class="max-sm:px-4 py-6" data-test="invitation-row">
                        <p class="font-medium">{{ $invitation->email }}</p>
                        <div class="flex flex-wrap items-center gap-x-3 justify-between mt-1">
                            <div>
                                <flux:text variant="strong">{{ $invitation->role->label() }}</flux:text>
                                <flux:text size="sm">@if($invitation->expires_at) @if($invitation->isExpired()) Expired {{ $invitation->expires_at->diffForHumans() }} @else Expires {{ $invitation->expires_at->diffForHumans() }} @endif @endif</flux:text>
                            </div>
                            <flux:button size="sm" variant="ghost" wire:click="cancelInvitation('{{ $invitation->code }}')" data-test="invitation-cancel-button" :disabled="!$this->permissions->canCancelInvitation">
                                Cancel
                            </flux:button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif

    <flux:separator class="my-12" />

    <flux:heading level="2">Delete team</flux:heading>

    <form wire:submit="deleteTeam" class="mt-4">
        <flux:fieldset :disabled="! $this->permissions->canDeleteTeam || $team->is_personal">
            <flux:field>
                <flux:label>Type "<span class="normal-case">{{ $team->name }}</span>" to confirm</flux:label>
                <flux:input wire:model="deleteForm.confirmName" type="text" required data-test="delete-team-name" />
                <flux:error name="deleteForm.confirmName" />
            </flux:field>
        </flux:fieldset>

        <flux:spacer class="mt-6" />

        <flux:button type="submit" variant="danger" data-test="delete-team-button" :disabled="! $this->permissions->canDeleteTeam || $team->is_personal">Delete team</flux:button>
    </form>
</section>
