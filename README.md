# Vault

## Local setup

### Berkeley Mono font

This project uses [Berkeley Mono](https://berkeleygraphics.com/typefaces/berkeley-mono/)
(a commercial font) as its monospace typeface. The font files are **not** committed
to this repository due to licensing — `resources/fonts/` is gitignored.

Before running `npm run dev` or `npm run build`, you must obtain Berkeley Mono and
place the variable file at:

```
resources/fonts/BerkeleyMonoVariable.woff2
```

Without it, the Vite build will fail to resolve the `@font-face` `url()` reference
in `resources/css/app.css`.

During deployment, the `upload-fonts` task in `Envoy.blade.php` copies this file
from your local machine to the server (`vault.antihq.com`) before `npm run build` runs.
