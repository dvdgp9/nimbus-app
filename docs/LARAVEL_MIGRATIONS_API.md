# Laravel Migrations API

## Version

- Project: Laravel 12.30.1
- Documentation: https://laravel.com/docs/12.x/migrations
- Consulted: 2026-06-19

## Nimbus usage

- Add nullable `email_sender_name` and `email_logo_path` string columns to `users`.
- The migration `down` method removes both columns.

## Equivalent SQL

The Laravel migration remains the source of truth. Equivalent MySQL SQL will be supplied to the user for environments where Artisan cannot run against the database.

```sql
ALTER TABLE `users`
    ADD COLUMN `email_sender_name` VARCHAR(255) NULL AFTER `name`,
    ADD COLUMN `email_logo_path` VARCHAR(255) NULL AFTER `email_sender_name`;
```

Rollback equivalente:

```sql
ALTER TABLE `users`
    DROP COLUMN `email_logo_path`,
    DROP COLUMN `email_sender_name`;
```
