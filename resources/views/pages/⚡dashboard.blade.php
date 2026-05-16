<?php

use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    public Team $teamModel;

    public function mount(Team $current_team): void
    {
        $this->teamModel = $current_team;
    }

    #[Computed]
    public function securityPosture(): array
    {
        $user = Auth::user();

        return [
            'twoFactorEnabled' => $user->two_factor_confirmed_at !== null,
            'activeSessions' => $user->activeSessionsCount(),
            'memberCount' => $this->teamModel->members()->count(),
            'pendingInvites' => $this->pendingInvitations->count(),
            'expiredCards' => $this->expiredCards->count(),
        ];
    }

    #[Computed]
    public function passwordAgeBuckets(): array
    {
        $passwords = $this->teamModel->passwords()->select('id', 'updated_at')->get();
        $total = $passwords->count();

        $buckets = [
            ['label' => '< 30 days', 'count' => $passwords->where('updated_at', '>=', now()->subDays(30))->count()],
            ['label' => '30–90 days', 'count' => $passwords->where('updated_at', '>=', now()->subDays(90))->where('updated_at', '<', now()->subDays(30))->count()],
            ['label' => '90–180 days', 'count' => $passwords->where('updated_at', '>=', now()->subDays(180))->where('updated_at', '<', now()->subDays(90))->count()],
            ['label' => '> 180 days', 'count' => $passwords->where('updated_at', '<', now()->subDays(180))->count()],
        ];

        return ['buckets' => $buckets, 'total' => $total];
    }

    #[Computed]
    public function recentItems()
    {
        $passwords = $this->teamModel->passwords()
            ->select('id', 'name', 'username', 'updated_at')
            ->latest('updated_at')
            ->limit(25)
            ->get()
            ->each(fn ($p) => $p->type = 'password')
            ->each(fn ($p) => $p->key = $p->username);

        $creditCards = $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date', 'updated_at')
            ->latest('updated_at')
            ->limit(25)
            ->get()
            ->each(fn ($card) => $card->type = 'credit_card')
            ->each(fn ($card) => $card->key = '•••• •••• •••• ' . ($card->last_four ?? '    '));

        return $passwords->merge($creditCards)->sortByDesc('updated_at')->take(25)->values();
    }

    #[Computed]
    public function expiredCards()
    {
        return $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date')
            ->get()
            ->filter(fn ($card) => $card->isExpired)
            ->values();
    }

    #[Computed]
    public function expiringSoonCards()
    {
        return $this->teamModel->creditCards()
            ->select('id', 'name', 'last_four', 'expiry_date')
            ->get()
            ->filter(function ($card) {
                if ($card->isExpired) {
                    return false;
                }

                $expiry = Carbon::createFromFormat('m/y', $card->expiry_date)->endOfMonth()->endOfDay();

                return $expiry->isFuture() && $expiry->diffInDays(now()) <= 60;
            })
            ->values();
    }

    #[Computed]
    public function teamMembers()
    {
        return $this->teamModel->memberships()->with('user')->get();
    }

    #[Computed]
    public function pendingInvitations()
    {
        return $this->teamModel->invitations()
            ->with('inviter')
            ->whereNull('accepted_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->get();
    }

    public function itemRoute(object $item): string
    {
        return $item->type === 'password'
            ? route('passwords.show', [$this->teamModel, $item->id])
            : route('credit-cards.show', [$this->teamModel, $item->id]);
    }
}; ?>

<section class="w-full space-y-10">
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
        <span class="flex items-center gap-1.5">
            <span class="inline-block size-2 rounded-full {{ $this->securityPosture['twoFactorEnabled'] ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
            2FA: {{ $this->securityPosture['twoFactorEnabled'] ? 'ON' : 'OFF' }}
        </span>
        <span>Sessions: {{ $this->securityPosture['activeSessions'] }}</span>
        <span>Members: {{ $this->securityPosture['memberCount'] }}</span>
        @if ($this->securityPosture['pendingInvites'] > 0)
            <span>Invites pending: {{ $this->securityPosture['pendingInvites'] }}</span>
        @endif
        @if ($this->securityPosture['expiredCards'] > 0)
            <span class="text-red-600 dark:text-red-400">Expired cards: {{ $this->securityPosture['expiredCards'] }}</span>
        @endif
    </div>

    @if ($this->passwordAgeBuckets['total'] > 0)
        <div>
            <flux:heading size="lg" level="2">Password age</flux:heading>
            <div class="mt-3 space-y-2">
                @foreach ($this->passwordAgeBuckets['buckets'] as $bucket)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-24 text-zinc-500 dark:text-zinc-400">{{ $bucket['label'] }}</span>
                        <div class="flex-1">
                            <div class="h-4 rounded bg-zinc-100 dark:bg-zinc-800">
                                @if ($bucket['count'] > 0)
                                    <div class="h-full rounded bg-zinc-950/15 dark:bg-white/15" style="width: {{ ($bucket['count'] / $this->passwordAgeBuckets['total']) * 100 }}%"></div>
                                @endif
                            </div>
                        </div>
                        <span class="w-8 text-right tabular-nums">{{ $bucket['count'] }}</span>
                    </div>
                @endforeach
                <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400 pt-1">
                    <span class="w-24">Total</span>
                    <div class="flex-1"></div>
                    <span class="w-8 text-right tabular-nums">{{ $this->passwordAgeBuckets['total'] }}</span>
                </div>
            </div>
        </div>
    @endif

    @if ($this->recentItems->isNotEmpty())
        <div>
            <flux:heading size="lg" level="2">Recent items</flux:heading>
            <div class="mt-3">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Type</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Key</flux:table.column>
                        <flux:table.column>Updated</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentItems as $item)
                            <flux:table.row :key="'{$item->type}-{$item->id}'">
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate :first="true" />
                                    <flux:badge size="sm" inset="top bottom">{{ $item->type === 'password' ? 'Password' : 'Credit card' }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                    {{ $item->name }}
                                </flux:table.cell>
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                    {{ $item->key }}
                                </flux:table.cell>
                                <flux:table.cell class="relative whitespace-nowrap">
                                    <x-table-row-link :href="$this->itemRoute($item)" wire:navigate />
                                    {{ $item->updated_at->diffForHumans() }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif

    @if ($this->expiredCards->isNotEmpty() || $this->expiringSoonCards->isNotEmpty())
        <div>
            <flux:heading size="lg" level="2">Card expiry</flux:heading>
            <div class="mt-3">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Number</flux:table.column>
                        <flux:table.column>Expires</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->expiredCards->merge($this->expiringSoonCards) as $card)
                            <flux:table.row>
                                <flux:table.cell>
                                    @if ($card->isExpired)
                                        <span class="text-sm text-red-600 dark:text-red-400 font-medium">Expired</span>
                                    @else
                                        <span class="text-sm text-amber-600 dark:text-amber-400 font-medium">Expiring soon</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="route('credit-cards.show', [$teamModel, $card])" wire:navigate :first="true" />
                                    {{ $card->name }}
                                </flux:table.cell>
                                <flux:table.cell class="relative">
                                    <x-table-row-link :href="route('credit-cards.show', [$teamModel, $card])" wire:navigate />
                                    {{ $card->maskedNumber }}
                                </flux:table.cell>
                                <flux:table.cell class="relative whitespace-nowrap">
                                    <x-table-row-link :href="route('credit-cards.show', [$teamModel, $card])" wire:navigate />
                                    {{ $card->expiry_date }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif

    <div>
        <flux:heading size="lg" level="2">Team</flux:heading>
        <div class="mt-3">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Role</flux:table.column>
                    <flux:table.column>Joined</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->teamMembers as $membership)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <div class="flex size-6 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800 text-xs font-medium">
                                        {{ $membership->user->initials() }}
                                    </div>
                                    {{ $membership->user->name }}
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm">{{ $membership->role->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                                {{ $membership->created_at->format('M j, Y') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    @if ($this->pendingInvitations->isNotEmpty())
        <div>
            <flux:heading size="lg" level="2">Pending invitations</flux:heading>
            <div class="mt-3">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Role</flux:table.column>
                        <flux:table.column>Invited by</flux:table.column>
                        <flux:table.column>Expires</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->pendingInvitations as $invitation)
                            <flux:table.row>
                                <flux:table.cell>{{ $invitation->email }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm">{{ $invitation->role->label() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-zinc-500 dark:text-zinc-400">
                                    {{ $invitation->inviter?->name ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                                    {{ $invitation->expires_at?->format('M j, Y') ?? '—' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    @endif
</section>
