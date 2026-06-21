# Acumbamail SMS API

## Reference

- Official product/API page: https://acumbamail.com/api-sms/
- Consulted: 2026-06-19

## Nimbus usage

- Endpoint: `POST https://acumbamail.com/api/1/sendSMS/`.
- Form fields: `auth_token` and `messages`.
- `messages` contains a JSON array with `recipient`, `body`, and `sender`.

## Line break behavior

- Nimbus does not remove line breaks from an SMS template.
- PHP `json_encode()` serializes line feeds as `\n` inside the JSON representation.
- A test inspects the outgoing request and verifies that decoding `messages` restores the exact multiline body.
- Acumbamail's public page does not document carrier-specific rendering of line breaks, so final display must also be checked with a real SMS.

## Recipient format and delivery diagnostics

- Nimbus normalizes Spanish formats such as `600111222`, `+34600111222`, `34600111222`, and `0034600111222` to `+34600111222`.
- International numbers must include their country prefix.
- Acumbamail may return the successful status as `0` or `"0"`; Nimbus accepts both.
- API acceptance does not prove handset delivery. Check **Reports > Individual sends** in Acumbamail for `Delivered`, `Sent`, or `Undelivered`.
- Nimbus stores the provider ID and logs partial delivery when email succeeds but SMS fails.
