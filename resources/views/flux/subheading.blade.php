@blaze(fold: true)

@props([
    'size' => 'base',
])

@php
$classes = Flux::classes()
    ->add(match ($size) {
        'xl' => 'text-lg',
        'lg' => 'text-base',
        default => 'text-base/6 sm:text-sm/6',
        'sm' => 'text-sm/5 sm:text-xs/5',
    })
    ->add('[:where(&)]:text-zinc-500 [:where(&)]:dark:text-white/70')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-subheading>
    {{ $slot }}
</div>
