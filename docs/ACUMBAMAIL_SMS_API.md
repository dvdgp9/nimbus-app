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
