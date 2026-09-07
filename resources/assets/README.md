WebShell ships its CSS and JavaScript inside the Blade views so the UI works without a frontend build step.

Publish this directory only if you want to override assets:

```bash
php artisan vendor:publish --tag=webshell-assets
```
