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

