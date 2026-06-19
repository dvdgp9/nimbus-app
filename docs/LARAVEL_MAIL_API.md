# Laravel Mail API

## Version

- Project: Laravel 12.30.1
- Documentation: https://laravel.com/docs/12.x/mail
- Consulted: 2026-06-19

## Nimbus usage

- Configure each reminder sender through `Illuminate\Mail\Mailables\Address` in the Mailable `Envelope`.
- Keep `config('mail.from.address')` as the authenticated technical address.
- Use the professional's optional `email_sender_name` only as the visible display name.
- Fall back to the professional profile name and then the global mail name.

## Decisions

- Do not make the sender address editable per professional because the configured domain must remain aligned with the delivery provider and its SPF/DKIM/DMARC setup.
- Apply the same envelope behavior to `AppointmentReminder` and `TemplatedReminder`.
- Verify both envelope data and rendered action colors without sending real email.

