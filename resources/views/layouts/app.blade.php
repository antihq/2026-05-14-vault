<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-white dark:bg-zinc-900 antialiased text-zinc-800 dark:text-white text-base/6 sm:text-sm/6">
        <header class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <nav class="flex flex-wrap gap-y-1">
                <div class="lg:w-64 pl-1.5 lg:flex justify-end items-start hidden">
                    <flux:navbar class="-mb-px">
                        <flux:navbar.item :href="route('home')" :accent="true" class="text-zinc-800" wire:navigate>{{ config('app.name') }}</flux:navbar.item>
                    </flux:navbar>
                </div>

                <div class="w-full flex-1 flex-wrap flex px-1.5">
                    <flux:navbar class="pb-3">
                        <flux:navbar.item :href="route('home')" :accent="true" class="lg:hidden text-zinc-800" wire:navigate>{{ config('app.name') }}</flux:navbar.item>
                        <flux:navbar.item :href="route('dashboard')" :accent="true" class="text-zinc-800" wire:navigate>{{ Auth::user()->currentTeam->name }}</flux:navbar.item>
                        <flux:navbar.item :href="route('teams.switch')" :accent="true" class="text-zinc-800" wire:navigate>Switch team</flux:navbar.item>
                    </flux:navbar>

                    <div aria-hidden="true" class="flex-1"></div>

                    <flux:navbar class="-mb-px pb-3">
                        <flux:navbar.item :href="route('settings')" wire:navigate :accent="false" class="text-zinc-800 max-lg:hidden">Account</flux:navbar.item>
                        <form method="POST" action="{{ route('logout') }}" class="flex max-lg:hidden">
                            @csrf
                            <flux:navbar.item type="submit" class="text-zinc-800">Logout</flux:navbar.item>
                        </form>
                        <flux:dropdown class="lg:hidden">
                            <flux:navbar.item icon="ellipsis-horizontal" class="text-zinc-800" />
                            <flux:navmenu>
                                <flux:navmenu.item :href="route('settings')" wire:navigate :accent="false" class="text-zinc-800">Account</flux:navmenu.item>
                                <form method="POST" action="{{ route('logout') }}" class="flex">
                                    @csrf
                                    <flux:navmenu.item type="submit" class="text-zinc-800">Logout</flux:navmenu.item>
                                </form>
                            </flux:navmenu>
                        </flux:dropdown>
                    </flux:navbar>
                </div>
            </nav>
        </header>

        <header class="lg:pl-64 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <div class="px-1.5">
                <flux:navbar class="w-full -mb-px">
                    <flux:navbar.item :href="route('passwords.index', ['current_team' => Auth::user()->currentTeam])" :current="request()->routeIs('passwords.*')" class="text-zinc-800" wire:navigate>Passwords</flux:navbar.item>
                    <flux:navbar.item :href="route('credit-cards.index', ['current_team' => Auth::user()->currentTeam])" :current="request()->routeIs('credit-cards.*')" class="text-zinc-800" wire:navigate>Cards</flux:navbar.item>
                    <flux:navbar.item :href="route('teams.settings', Auth::user()->currentTeam)" :current="request()->routeIs('teams.settings')" class="text-zinc-800" wire:navigate>Settings</flux:navbar.item>
                </flux:navbar>
            </div>
        </header>

        <main class="lg:pl-64">
            <div class="px-4 py-6">
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
