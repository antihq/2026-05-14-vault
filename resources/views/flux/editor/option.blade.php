@blaze(fold: true)

<ui-option {{ $attributes }} class="h-8 px-2 flex items-center gap-2 rounded-lg text-base/6 sm:text-sm/6 font-medium text-zinc-800 dark:text-white data-active:bg-zinc-50 dark:data-active:bg-zinc-600 [&>svg]:text-zinc-400 [&[data-active]>svg]:text-zinc-800 dark:[&[data-active]>svg]:text-white">
    {{ $slot }}
</ui-option>
