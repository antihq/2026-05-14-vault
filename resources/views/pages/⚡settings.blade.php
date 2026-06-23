<?php

use App\Livewire\Forms\DeleteAccountForm;
use App\Livewire\Forms\UpdatePasswordForm;
use App\Livewire\Forms\UpdateProfileForm;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.account'), Title('Settings')] class extends Component
{
    public UpdateProfileForm $profileForm;

    public UpdatePasswordForm $passwordForm;

    public DeleteAccountForm $deleteForm;

    public bool $twoFactorEnabled = false;

    public bool $canManageTwoFactor = false;

    public bool $showQrCode = false;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public string $disablePassword = '';

    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->profileForm->setUser(Auth::user());

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            $user = Auth::user();

            if (Fortify::confirmsTwoFactorAuthentication() && is_null($user->two_factor_confirmed_at)) {
                app(DisableTwoFactorAuthentication::class)($user);
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();

            if ($this->twoFactorEnabled) {
                $this->loadRecoveryCodes();
            }
        }
    }

    public function updateProfile(): void
    {
        $this->profileForm->save();

        Flux::toast(variant: 'success', text: 'Profile updated.');
    }

    public function updatePassword(): void
    {
        try {
            $this->passwordForm->save();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->passwordForm->reset();
            throw $e;
        }

        Flux::toast(variant: 'success', text: 'Password updated.');
    }

    public function deleteAccount(): void
    {
        $this->deleteForm->delete(app(\App\Livewire\Actions\Logout::class));

        $this->redirect('/', navigate: true);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: 'A new verification link has been sent to your email address.');
    }

    public function enableTwoFactor(): void
    {
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication($user);

        $this->loadSetupData($user);
        $this->showQrCode = true;
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validateOnly('code');

        $confirmTwoFactorAuthentication(Auth::user(), $this->code);

        $this->twoFactorEnabled = true;
        $this->showQrCode = false;
        $this->code = '';

        Flux::toast(variant: 'success', text: 'Two-factor authentication enabled.');
    }

    public function cancelTwoFactorSetup(): void
    {
        app(DisableTwoFactorAuthentication::class)(Auth::user());

        $this->showQrCode = false;
        $this->qrCodeSvg = '';
        $this->manualSetupKey = '';
        $this->code = '';
    }

    public function disableTwoFactor(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->validate([
            'disablePassword' => ['required', 'string', 'current_password'],
        ]);

        $disableTwoFactorAuthentication(Auth::user());

        $this->twoFactorEnabled = false;
        $this->disablePassword = '';
        $this->recoveryCodes = [];

        Flux::toast(variant: 'success', text: 'Two-factor authentication disabled.');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(Auth::user());

        $this->loadRecoveryCodes();
    }

    #[Computed]
    public function emailVerificationEnabled(): bool
    {
        return Auth::user() instanceof MustVerifyEmail;
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function twoFactorStatus(): string
    {
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_confirmed_at) {
            return 'Enabled on ' . $user->two_factor_confirmed_at->format('M j, Y');
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return 'Enabled';
        }

        return 'Disabled';
    }

    #[Computed]
    public function recoveryCodesRemaining(): int
    {
        $user = Auth::user();

        if (! $user->hasEnabledTwoFactorAuthentication() || ! $user->two_factor_recovery_codes) {
            return 0;
        }

        try {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

            return count($codes);
        } catch (\Throwable) {
            return 0;
        }
    }

    #[Computed]
    public function passwordRulesDescription(): array
    {
        return [
            'Minimum 8 characters',
            'at least one uppercase letter',
            'at least one lowercase letter',
            'at least one number',
        ];
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    #[Computed]
    public function requiresTwoFactorConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    private function loadSetupData($user): void
    {
        $user = $user->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new \Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (\Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');
            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    private function loadRecoveryCodes(): void
    {
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (\Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes.');
                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<section class="w-full max-w-xl">
    <flux:heading level="2">Profile</flux:heading>

    <form wire:submit="updateProfile" class="mt-4">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="profileForm.name" type="text" required autofocus autocomplete="name" />
            <flux:error name="profileForm.name" />
        </flux:field>

        <flux:field class="mt-6">
            <flux:label>Email</flux:label>
            <flux:input wire:model="profileForm.email" type="email" required autocomplete="email" />
            <flux:error name="profileForm.email" />

            @if ($this->hasUnverifiedEmail)
                <flux:button wire:click="resendVerificationNotification" variant="subtle" class="mt-2">
                    Resend verification email
                </flux:button>
            @endif

            @if ($this->emailVerificationEnabled && Auth::user()->hasVerifiedEmail() && $profileForm->email !== $profileForm->originalEmail)
                <p class="text-amber-600 dark:text-amber-400 mt-2">
                    Your email will be marked as unverified.
                </p>
            @endif
        </flux:field>

        <flux:spacer class="mt-6" />

        <flux:button type="submit" variant="primary" data-test="update-profile-button">
            Update profile
        </flux:button>
    </form>

    <flux:separator class="my-12" />

    <flux:heading level="2">Password</flux:heading>

    <form wire:submit="updatePassword" class="mt-4">
        <flux:field>
            <flux:label>Current password</flux:label>
            <flux:input wire:model="passwordForm.current_password" type="password" required autocomplete="current-password" viewable />
            <flux:error name="passwordForm.current_password" />
        </flux:field>

        <flux:field class="mt-6">
            <flux:label>New password</flux:label>
            <flux:input wire:model="passwordForm.password" type="password" required autocomplete="new-password" viewable />
            <flux:description>
                {{ implode(', ', $this->passwordRulesDescription) . '.' }}
            </flux:description>
            <flux:error name="passwordForm.password" />
        </flux:field>

        <flux:field class="mt-6">
            <flux:label>Confirm password</flux:label>
            <flux:input wire:model="passwordForm.password_confirmation" type="password" required autocomplete="new-password" viewable />
            <flux:error name="passwordForm.password_confirmation" />
        </flux:field>

        <flux:spacer class="mt-6" />

        <flux:button type="submit" variant="primary" data-test="update-password-button">
            Update password
        </flux:button>
    </form>

    <flux:separator class="my-12" />

    <flux:heading level="2">Two-factor authentication</flux:heading>

    <flux:fieldset :disabled="!$canManageTwoFactor" class="mt-4">
        @if ($twoFactorEnabled && ! $showQrCode)
            <x-description.list>
                <x-description.term>Status</x-description.term>
                <x-description.details>{{ $this->twoFactorStatus }}</x-description.details>

                <x-description.term>Recovery codes remaining</x-description.term>
                <x-description.details>
                    <span class="{{ $this->recoveryCodesRemaining <= 2 ? 'text-amber-600' : '' }}">
                        {{ $this->recoveryCodesRemaining . ' of 8 codes' }}
                    </span>
                </x-description.details>
            </x-description.list>

            <div class="mt-6 space-y-4">
                @error('recoveryCodes')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                @enderror

                @if (filled($recoveryCodes))
                    <div class="grid grid-cols-2 gap-x-8 gap-y-1 font-mono text-sm" role="list" aria-label="Recovery codes">
                        @foreach($recoveryCodes as $recoveryCode)
                            <div role="listitem" class="select-text" wire:loading.class="opacity-50 animate-pulse">
                                {{ $recoveryCode }}
                            </div>
                        @endforeach
                    </div>

                    <flux:button variant="danger" wire:click="regenerateRecoveryCodes">
                        Regenerate codes
                    </flux:button>
                @else
                    <flux:callout variant="warning" icon="exclamation-triangle" heading="No recovery codes found" />
                @endif
            </div>

            <form wire:submit="disableTwoFactor" class="mt-6">
                <flux:fieldset>
                    <flux:legend level="3">Disable two-factor authentication</flux:legend>

                    <flux:field class="mt-6">
                        <flux:label>Current password</flux:label>
                        <flux:input wire:model="disablePassword" type="password" required viewable />
                        <flux:error name="disablePassword" />
                    </flux:field>
                </flux:fieldset>

                <flux:spacer class="mt-6" />

                <flux:button variant="danger" type="submit" data-test="disable-two-factor-button">
                    Disable
                </flux:button>
            </form>
        @elseif ($showQrCode)
            <div>
                @error('setupData')
                    <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                @enderror

                <div>
                    {!! $qrCodeSvg !!}
                </div>

                <flux:field class="mt-6">
                    <flux:label>Setup key</flux:label>
                    <flux:input
                        :value="$manualSetupKey"
                        readonly
                        variant="filled"
                        copyable
                        input:class="font-mono"
                    />
                </flux:field>

                @if ($this->requiresTwoFactorConfirmation)
                    <flux:field class="mt-6">
                        <flux:label>Code</flux:label>
                        <flux:otp name="code" wire:model="code" length="6" />
                        <flux:error name="code" />
                    </flux:field>

                    <div class="mt-4 flex gap-1">
                        <flux:button wire:click="confirmTwoFactor" variant="primary">
                            Confirm
                        </flux:button>
                        <flux:button variant="subtle" wire:click="cancelTwoFactorSetup" type="button">
                            Cancel
                        </flux:button>
                    </div>
                @else
                    <div class="mt-6 flex gap-3">
                        <flux:button wire:click="confirmTwoFactor" variant="primary" :disabled="$errors->has('setupData')">
                            Enable
                        </flux:button>
                    </div>
                @endif
            </div>
        @else
            <div>
                <flux:button wire:click="enableTwoFactor" variant="primary">
                    Enable two-factor
                </flux:button>
            </div>
        @endif
    </flux:fieldset>

    <flux:separator class="my-12" />

    <div x-data>
        <flux:heading level="2">Appearance</flux:heading>

        <flux:radio.group x-model="$flux.appearance" class="mt-4">
            <flux:radio value="light" label="Light" />
            <flux:radio value="dark" label="Dark" />
            <flux:radio value="system" label="System" description="Follows your operating system preference" />
        </flux:radio.group>
    </div>

    <flux:separator class="my-12" />

    <flux:heading level="2">Delete account</flux:heading>

    <form wire:submit="deleteAccount" class="mt-4">
        <flux:fieldset :disabled="!$this->showDeleteUser">
            @if (! $this->showDeleteUser)
                <p class="text-amber-600 dark:text-amber-400 mb-6">
                    Email verification required to delete your account.
                </p>
            @endif

            <flux:field>
                <flux:label>Current password</flux:label>
                <flux:input wire:model="deleteForm.password" type="password" required viewable />
                <flux:error name="deleteForm.password" />
            </flux:field>
        </flux:fieldset>

        <flux:spacer class="mt-6" />

        <flux:button type="submit" variant="danger" data-test="delete-user-button" :disabled="!$this->showDeleteUser">
            Delete account
        </flux:button>
    </form>
</section>
