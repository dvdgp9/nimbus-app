# Acumbamail SMS API

## Reference

- Official endpoint documentation: https://acumbamail.com/apidoc/function/sendSMS/
- Official API status documentation: https://acumbamail.com/apidoc/
- Official product/API page: https://acumbamail.com/api-sms/
- Consulted: 2026-08-31

## Nimbus usage

- Endpoint: `POST https://acumbamail.com/api/1/sendSMS/`.
- Form fields: `auth_token` and `messages`.
- `messages` contains a JSON array with `recipient`, `body`, and `sender`.
- The official `sendSMS` example returns one result per message. `status: 0` is a successful result with `id` and `credits`; a non-zero result includes a descriptive `error` value.
- The public documentation does not define a complete mapping for non-zero message status values. Diagnostics must preserve and inspect `messages[n].error` rather than infer a cause from the number alone.
- At HTTP level, Acumbamail documents `400` as an invalid argument, `401` as failed authentication, `429` as rate limiting, and `500` as a server error.

## Remaining balance (`getCreditsSMS`)

- Official doc: https://acumbamail.com/apidoc/function/getCreditsSMS/ (consulted 2026-10-14; it gives no example or field names).
- Endpoint: `POST https://acumbamail.com/api/1/getCreditsSMS/` with `auth_token`.
- Real response checked against production on 2026-10-14: `{"Creditos": 70132, "sms_espana": 701}`.
- `Creditos` is an internal unit, not a number of SMS. `sms_espana` = `Creditos` / per-SMS cost to Spain, which means **100 credits = 1 single-segment SMS to Spain**.
- A reminder with N segments costs N SMS (N × 100 credits). Destinations that charge double cost twice that.
- `getCredits()` currently reads only `Creditos`; any user-facing balance or low-balance threshold should use `sms_espana`.

## Line break behavior

- Nimbus does not remove line breaks from an SMS template.
- PHP `json_encode()` serializes line feeds as `\n` inside the JSON representation.
- A test inspects the outgoing request and verifies that decoding `messages` restores the exact multiline body.
- Acumbamail's public page does not document carrier-specific rendering of line breaks, so final display must also be checked with a real SMS.

## Encoding and credit calculation

- Official reference: https://soporte.acumbamail.com/article/99-crear-campanas-de-sms/
- Standard messages use one credit up to 160 characters. Concatenated parts after the first add one credit per 153 characters.
- Unicode messages use one credit up to 70 characters. Concatenated parts after the first add one credit per 67 characters.
- Acumbamail explicitly treats accents, emoji, `é`, and `ñ` as Unicode for billing.
- Some destinations charge two base credits per SMS segment. The actual normalized country prefix therefore matters in addition to encoding and length.
- A visible balance expressed as a number of Spanish SMS does not imply that every personalized reminder costs one credit; one long Unicode reminder can consume several credits.
- Nimbus estimates the expanded message with the same limits in both PHP and the template editor. The recommended template uses a numeric date and one management shortlink so a Spanish reminder fits in one standard segment.
- New shortlink tokens use 32 random base-62 characters. Existing longer links remain valid.
- `manage_link` points to a safe GET page that offers confirmation, cancellation, and rescheduling. This replaces three URLs in economical templates without removing any patient action.

## Recipient format and delivery diagnostics

- Nimbus normalizes Spanish formats such as `600111222`, `+34600111222`, `34600111222`, and `0034600111222` to `+34600111222`.
- International numbers must include their country prefix.
- Acumbamail may return the successful status as `0` or `"0"`; Nimbus accepts both.
- API acceptance does not prove handset delivery. Check **Reports > Individual sends** in Acumbamail for `Delivered`, `Sent`, or `Undelivered`.
- Nimbus stores the provider ID and logs partial delivery when email succeeds but SMS fails.

## Safe handling of delayed responses (2026-10-27)

- `sendSMS/` performs one HTTP request with a 60-second timeout. It never uses the retry helper: a timeout can happen after Acumbamail accepted and delivered the message.
- Only a numeric non-zero per-message status with a descriptive `error` is classified as an explicit rejection. Timeouts, HTTP gateway errors, malformed responses and missing message IDs raise `SmsDeliveryUncertainException`.
- A reminder communication is persisted as `pending` before the HTTP request. Uncertain outcomes stay pending, including a process crash or a failure to save the ID after acceptance. Later reminder ticks cannot create another SMS for that appointment/date while this row exists.
- `getSMSQuickSubscriberReport/` is a read-only endpoint accepting `auth_token`, `start_date` and `end_date` (`YYYY-MM-DD HH:MM`). Official reference: https://acumbamail.com/apidoc/function/getSMSQuickSubscriberReport/ (consulted 2026-10-27).
- Its response is a list with `sms_id`, `sender`, `phone`, `sent_at`, `status`, `delivered_at` and `sms_content`. Nimbus matches the normalized phone, exact original message and send time to recover the provider ID. The observed delivered status is `Entregado`.
- `nimbus:reconcile-sms` runs every 15 minutes, including after a patient confirms or cancels. It only reads reports, updates communications and alerts the professional; it does not send SMS. `--dry-run` lists the counts without contacting Acumbamail or sending email.
- Report snapshots are shared briefly between pending communications to respect the documented limit of ten report calls/minute. A missing or unavailable report keeps the communication pending; absence is not proof of rejection.
- After 30 minutes without confirmation, Nimbus emails the professional once to review the individual send report. The notification marker is persisted on the communication so later ticks do not repeat the alert. A late report match can still resolve it.
- Explicitly rejected sends retain the existing bounded retry behavior. Balance/report lookups may retry because they do not send messages.
- Existing `pending`, `sent` and `delivered` enum values are reused; no schema migration is required. Deploy the new exception, reconciler, command, email class/template and updated services/schedule together.
