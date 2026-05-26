<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white text-base/6 sm:text-sm/6">
        <header>
            <nav class="flex items-end flex-wrap py-5">
                <div class="lg:w-64 lg:justify-end px-4 flex gap-x-3 flex-wrap">
                    <a href="{{ route('home') }}" class="text-zinc-500 dark:text-zinc-400" wire:navigate>
                        {{ Str::of(config('app.name'))->explode('-', 4)->last() }}
                        <sup>{{ Str::of(config('app.name'))->explode('-', 4)->take(3)->join('-') }}</sup>
                    </a>
                    <flux:link :href="route('dashboard')" variant="ghost" wire:navigate>{{ Auth::user()->currentTeam->name }}</flux:link>
                    <flux:link :href="route('teams.switch')" variant="ghost" wire:navigate>switch team</flux:link>
                </div>

                <div class="w-full lg:flex-1 flex-wrap flex px-4 gap-x-3 md:justify-between">
                    <div class="flex gap-x-3">
                        @if (Auth::user()->currentTeam)
                            <flux:link :href="route('passwords.index', ['current_team' => Auth::user()->currentTeam])" variant="ghost" class="lowercase" wire:navigate>passwords</flux:link>
                            <flux:link :href="route('credit-cards.index', ['current_team' => Auth::user()->currentTeam])" variant="ghost" class="lowercase" wire:navigate>cards</flux:link>
                            <flux:link :href="route('teams.settings', Auth::user()->currentTeam)" variant="ghost" class="lowercase" wire:navigate>settings</flux:link>
                        @endif
                    </div>

                    <div aria-hidden="true" class="flex-1"></div>

                    <div class="flex gap-x-1.5">
                        logged in as <flux:link :href="route('settings')" variant="ghost" class="lowercase" wire:navigate>{{ Auth::user()->email }}</flux:link>
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
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
