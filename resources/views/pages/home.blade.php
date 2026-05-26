<x-layouts::auth title="Welcome">
    <div class="grid lg:grid-cols-[1fr_320px] gap-x-12 lg:gap-x-16 gap-y-8">
        <div>
            <flux:heading level="1" class="lowercase">A simple password manager.</flux:heading>

            <p class="mt-1 max-w-prose">
                Manages passwords and credit cards. Quickly filter to find
                what you need. One-click copy for usernames, passwords,
                card numbers, and CVVs. Personal vault or team sharing.
                Works on any device with a browser. No download required.
            </p>

            <ul class="mt-6">
                <li><span class="font-semibold">search</span> — Quickly filter passwords and credit cards by name, username, or cardholder.</li>
                <li><span class="font-semibold">clipboard</span> — One-click copy for username, password, card number, and CVV.</li>
                <li><span class="font-semibold">share</span> — Personal vault, or share items with your team with granular access control.</li>
                <li><span class="font-semibold">access</span> — Works on any device with a browser. No download required.</li>
            </ul>

            <p class="mt-6 max-w-prose">
                Not a browser extension. Not an autofill companion. Use vault
                as your single source of truth for sensitive credentials. Let
                your browser or operating system handle autofill for commonly
                used passwords — that saves time and keeps things simple.
            </p>
        </div>

        @guest
            <div>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <flux:field class="max-w-sm">
                        <flux:label class="lowercase">Email address</flux:label>
                        <flux:input
                            name="email"
                            :value="old('email')"
                            type="email"
                            required
                            autofocus
                            autocomplete="email"
                        />
                        <flux:error name="email" />
                    </flux:field>
                    <flux:field class="mt-2 max-w-sm">
                        <flux:label class="lowercase">Password</flux:label>
                        <flux:input
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                        />
                        <flux:error name="password" />
                    </flux:field>
                    <div class="mt-4 flex items-center gap-x-4 lowercase">
                        <flux:checkbox name="remember" label="Remember me" :checked="old('remember')" />
                    </div>
                    <div class="mt-4">
                        <flux:button variant="primary" color="lime" type="submit" data-test="login-button" class="lowercase">
                            Sign in
                        </flux:button>
                    </div>
                </form>

                <div class="mt-2">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="hover:underline text-blue-700 visited:text-purple-700 dark:text-blue-400 dark:visited:text-purple-400 lowercase" wire:navigate>Reset password</a>
                    @endif
                </div>
            </div>
        @endguest
    </div>
</x-layouts::auth>
