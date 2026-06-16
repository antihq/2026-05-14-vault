@blaze

<dl
    {{ $attributes->except('class') }}
    @class([
        $attributes->get('class'),
        'grid grid-cols-1 text-sm sm:grid-cols-[min(50%,--spacing(64))_auto]',
    ])
>
    {{ $slot }}
</dl>
