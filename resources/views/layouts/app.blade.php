<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="font-mono bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white text-base/6 sm:text-sm/6">
        <header>
            <nav class="flex items-end flex-wrap py-5 gap-y-1">
                <div class="lg:w-64 lg:text-right px-4 gap-x-3">
                    <a href="{{ route('home') }}" wire:navigate>{{ config('app.name') }}</a>
                    <flux:link :href="route('dashboard')" wire:navigate :accent="false">{{ Auth::user()->currentTeam->name }}</flux:link>
                    <flux:link :href="route('teams.switch')" wire:navigate :accent="false" class="lowercase">switch team</flux:link>
                </div>

                <div class="w-full lg:flex-1 flex-wrap flex px-4 gap-x-3 gap-y-1 md:justify-between max-sm:flex-wrap-reverse">
                    <div class="flex gap-x-3">
                        @if (Auth::user()->currentTeam)
                            <flux:link :href="route('passwords.index', ['current_team' => Auth::user()->currentTeam])" class="lowercase" wire:navigate>passwords</flux:link>
                            <flux:link :href="route('credit-cards.index', ['current_team' => Auth::user()->currentTeam])" class="lowercase" wire:navigate>cards</flux:link>
                        @endif
                    </div>

                    <div aria-hidden="true" class="flex-1"></div>

                    <div class="flex flex-wrap gap-x-3 items-center">
                        <flux:link :href="route('teams.settings', Auth::user()->currentTeam)" class="lowercase" wire:navigate :accent="false">Settings</flux:link>
                        <flux:link :href="route('settings')" class="lowercase" wire:navigate :accent="false">Account</flux:link>
                        <form method="POST" action="{{ route('logout') }}" class="flex">
                            @csrf
                            <flux:button size="xs" variant="filled" type="submit" class="lowercase">logout</flux:button>
                        </form>
                    </div>
                </div>
            </nav>
        </header>

        <main class="lg:pl-64">
            <div class="p-4 pt-0">
                <div class="w-full max-w-6xl">
                    {{ $slot }}
                </div>
            </div>
        </main>

        @persist('toast')
            <flux:toast.group position="bottom center">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
