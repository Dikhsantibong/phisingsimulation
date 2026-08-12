---
paths:
  - 'resources/js/**'
---

# Js

## Regenerate Wayfinder with --with-form
The Vite plugin generates Wayfinder helpers with `formVariants: true` (see vite.config.ts). If you run `php artisan wayfinder:generate` manually, you MUST pass `--with-form`, otherwise the `.form()` helpers used across pages (login, settings, send-simulation) disappear and TS/build breaks.
