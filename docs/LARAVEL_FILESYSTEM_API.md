# Laravel Filesystem API

## Version

- Project: Laravel 12.30.1
- Documentation: https://laravel.com/docs/12.x/filesystem
- Consulted: 2026-06-19

## Nimbus usage

- Store professional email logos on the configured `public` disk.
- Persist only the relative path in `users.email_logo_path`.
- Use `Storage::disk('public')` for storage, replacement, deletion, and tests.
- Production must expose `storage/app/public` through `public/storage` by running `php artisan storage:link` once.
- Email logos are served through Nimbus route `/email-logo/{filename}` so restrictive hosting configurations do not need to expose the `public/storage` symlink for this feature.

## Decisions

- Use generated filenames from Laravel instead of user-provided filenames.
- Delete an existing logo only after the replacement has been stored and the user record has been saved.
- Use `Storage::fake('public')` in tests so no real files are written.
- Only a filename currently referenced by `users.email_logo_path` can be served; replaced or unknown filenames return 404.
