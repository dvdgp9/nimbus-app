# Google Calendar Event Colors API

## References

- Colors resource: https://developers.google.com/workspace/calendar/api/v3/reference/colors
- EventColor enum: https://developers.google.com/apps-script/reference/calendar/event-color
- Consulted: 2026-06-19

## Nimbus usage

- Google event resources expose an optional `colorId`.
- The standard yellow event color is ID `5`, named **Banana** in Google Calendar.
- Nimbus stores the event-level color in `appointments.google_color_id`.
- Yellow appointments require an explicit professional decision before any patient reminder is sent.

## Safety decisions

- Email links open a review page; they do not mutate data on GET.
- The decision is submitted by POST using a temporary signed URL and CSRF protection.
- This prevents automated email link scanners from confirming or cancelling appointments.
- Confirmation reuses the existing locked reminder service to reduce duplicate sends.

## Equivalent MySQL SQL

```sql
ALTER TABLE `appointments`
    ADD COLUMN `google_color_id` VARCHAR(10) NULL AFTER `message_code`,
    ADD COLUMN `professional_review_notified_at` TIMESTAMP NULL AFTER `unknown_patient_notified`,
    ADD COLUMN `professional_reviewed_at` TIMESTAMP NULL AFTER `professional_review_notified_at`,
    ADD COLUMN `professional_review_decision` VARCHAR(20) NULL AFTER `professional_reviewed_at`;
```

Rollback:

```sql
ALTER TABLE `appointments`
    DROP COLUMN `professional_review_decision`,
    DROP COLUMN `professional_reviewed_at`,
    DROP COLUMN `professional_review_notified_at`,
    DROP COLUMN `google_color_id`;
```
