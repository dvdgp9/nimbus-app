# cPanel Email Authentication Audit

## Scope

- Application: `https://nimbus.wthefox.com`
- SMTP sender: `noreply@nimbus.wthefox.com`
- SMTP host: `nimbus.wthefox.com:587`
- Mail transport: authenticated SMTP through the cPanel server
- Audited: 2026-06-19

## Official reference

- cPanel Email Deliverability: https://docs.cpanel.net/cpanel/email/email-deliverability-in-cpanel/
- cPanel Zone Editor: https://docs.cpanel.net/cpanel/domains/zone-editor/

## Public DNS status

### SPF

Present on `nimbus.wthefox.com`:

```txt
v=spf1 +a +mx +ip4:51.77.153.59 include:hl105.lucushost.org include:spf.lucushost.org ~all
```

The `a` mechanism authorizes `178.33.162.219`; the explicit IP and LucusHost includes cover the provider's other sending paths.

### DKIM

Present on `default._domainkey.nimbus.wthefox.com`:

```txt
v=DKIM1; k=rsa; p=<public key managed by cPanel>
```

The published key length is consistent with a 2048-bit RSA key. Never copy the private DKIM key outside cPanel.

### DMARC

Present on `_dmarc.nimbus.wthefox.com`:

```txt
v=DMARC1; p=quarantine; rua=mailto:citas@nimbus.wthefox.com
```

This policy asks receivers to quarantine messages that fail aligned SPF and DKIM checks.

### PTR and SMTP identity

- `178.33.162.219` PTR: `hl105.lucushost.org`
- `hl105.lucushost.org` resolves back to the provider IPs.
- SMTP banner after STARTTLS: `hl105.lucushost.org`

## cPanel procedure

1. Open **Email > Email Deliverability**.
2. Locate `nimbus.wthefox.com` and open **Manage**.
3. Confirm DKIM and SPF both show **Valid**. Use **Repair** only if cPanel reports a concrete error.
4. Confirm DMARC is present only after DKIM and SPF are valid.
5. If cPanel says it is not authoritative, publish its exact suggested TXT names and values at the authoritative DNS provider. Current nameservers are `ns1/ns2/ns3.lucushost.com`.
6. Do not create a second SPF record. A domain must have one SPF TXT policy; merge authorized senders into that record.

## Required message-level check

DNS presence does not prove that a particular message was signed or aligned. In Gmail, open a received reminder and choose **Show original / Mostrar original**. Verify:

- `SPF: PASS`
- `DKIM: PASS` with `d=nimbus.wthefox.com` and usually `s=default`
- `DMARC: PASS`
- `Return-Path` is aligned with `nimbus.wthefox.com`
- `Received` identifies the expected LucusHost server/IP

If all three pass and the message still lands in spam, likely causes are shared-IP reputation, low/new sending history, complaint/engagement signals, or message composition. Nimbus currently marks every reminder as high priority (`X-Priority: 1`, `Importance: High`), which should be evaluated after the authentication results are known.

## Real-message result and cleanup

A Gmail delivery confirmed:

- SPF: PASS from `51.77.153.59`
- DKIM: PASS for `nimbus.wthefox.com`
- DMARC: PASS
- Delivery time: one second

The `noreply@nimbus.wthefox.com` sender is intentional because patients interact through the confirmation and cancellation buttons. Button clicks are useful engagement signals, but filtering remains recipient-specific.

After confirming authentication, Nimbus removed these nonessential custom headers from reminders and appointment-status notifications:

- `X-Priority`
- `X-MSMail-Priority`
- `Importance`
- `X-Mailer`
- `List-Unsubscribe` pointing to the non-reply mailbox

Standard Laravel/Symfony headers and SMTP authentication remain unchanged.
