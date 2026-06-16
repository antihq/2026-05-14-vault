<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-zinc-50 dark:bg-zinc-900 antialiased text-zinc-800 dark:text-white text-sm/6">
        <header class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <nav class="flex flex-wrap gap-y-1">
                <div class="lg:w-64 pl-1.5 lg:flex justify-end items-start hidden">
                    <flux:navbar class="-mb-px">
                        <flux:navbar.item :href="route('home')" :accent="true" class="text-zinc-800" wire:navigate>{{ config('app.name') }}</flux:navbar.item>
                    </flux:navbar>
                </div>

                <div class="w-full flex-1 flex-wrap flex px-1.5">
                    <flux:navbar class="pb-3">
                        <flux:navbar.item :href="route('home')" :accent="true" class="lg:hidden" wire:navigate>{{ config('app.name') }}</flux:navbar.item>
                    </flux:navbar>

                    <div aria-hidden="true" class="flex-1"></div>

                    <flux:navbar class="-mb-px pb-3">
                        @guest
                            @if (Route::has('login'))
                                <flux:navbar.item :href="route('login')" :accent="false" class="text-zinc-800" wire:navigate>Sign in</flux:navbar.item>
                            @endif

                            @if (Route::has('register'))
                                <flux:navbar.item :href="route('register')" :accent="false" class="text-zinc-800" wire:navigate>Create account</flux:navbar.item>
                            @endif
                        @endguest

                        @auth
                            <flux:navbar.item :href="route('dashboard')" :accent="false" class="text-zinc-800" wire:navigate>Dashboard</flux:navbar.item>
                        @endauth
                    </flux:navbar>
                </div>
            </nav>
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
