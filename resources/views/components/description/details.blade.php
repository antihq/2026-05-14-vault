@blaze

<dd
    {{ $attributes->except('class') }}
    @class([
        $attributes->get('class'),
        'pt-0 pb-4 text-zinc-800 sm:border-t sm:border-zinc-950/5 sm:py-4 sm:nth-2:border-none dark:text-white dark:sm:border-white/5',
    ])
>
    {{ $slot }}
</dd>
