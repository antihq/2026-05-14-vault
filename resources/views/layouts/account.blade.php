<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="font-mono bg-zinc-50 dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white text-base/6 sm:text-sm/6">
        <header>
            <nav class="flex items-end flex-wrap py-5">
                <div class="lg:w-64 lg:justify-end px-4 flex gap-x-3 flex-wrap">
                    <a href="{{ route('home') }}" wire:navigate>{{ config('app.name') }}</a>
                </div>

                <div class="flex-1 flex-wrap flex px-4">
                    <div class="flex gap-x-3">
                        <flux:link :href="route('dashboard')" class="lowercase" wire:navigate>dashboard</flux:link>
                    </div>

                    <div aria-hidden="true" class="flex-1"></div>

                    <div class="flex gap-x-3">
                        logged in as <flux:link :href="route('settings')" class="lowercase" wire:navigate>{{ Auth::user()->email }}</flux:link>
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <flux:button size="sm" variant="filled" type="submit" class="lowercase">logout</flux:button>
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
