# Laravel Validation API

## Version

- Project: Laravel 12.30.1
- Documentation: https://laravel.com/docs/12.x/validation
- Consulted: 2026-06-19

## Nimbus usage

- `email_sender_name` is optional text with a maximum of 255 characters.
- `email_logo` is optional and must be a real raster image.
- SVG is intentionally excluded because Laravel's image validation rejects it by default due to its XSS risk.
- The upload size is limited to 2 MB.

## Decisions

- Validate file content, not only the filename extension.
- Keep the existing stored path when no replacement file is sent.
- A failed validation must not change database state or delete the current logo.

