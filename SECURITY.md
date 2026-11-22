# Security Policy

## Supported Versions

Security fixes are provided for the lines below.

| Version | Supported |
| ------- | --------- |
| 5.x     | Yes: bug fixes and security fixes (after the 5.0.0 release) |
| 4.x     | Yes until 5.0.0 is released, then security fixes only for six months |
| < 4.0   | No |

`master` holds the upcoming 5.0.0. When it is released, 4.x gets fixes for security vulnerabilities only, for six months after the 5.0.0 release; a fix is then released from a branch created from the `4.0.0` tag. Versions older than 4.0 do not receive security updates.

## Reporting a Vulnerability

Do not open a public GitHub issue for a security vulnerability.

The preferred way to report a vulnerability is [GitHub private vulnerability reporting](https://github.com/tomaj/omnipay-viamo/security/advisories/new).

If you cannot use private vulnerability reporting, email the details to **tomasmajer@gmail.com**.

Please include:

- A description of the vulnerability
- Steps to reproduce, or a proof of concept
- The omnipay-viamo version and the operation involved (`purchase()`, `completePurchase()`, `webhook()`)
- The potential impact
- A suggested fix, if you have one

### Response

You will receive an acknowledgement within 14 days.

### Coordinated disclosure

We follow coordinated disclosure. Please give us time to prepare a fix before discussing the issue in public.

When a fix is ready, we publish a [GitHub Security Advisory](https://github.com/tomaj/omnipay-viamo/security/advisories) and record the security fix in the [CHANGELOG](CHANGELOG.md).

## Security considerations

omnipay-viamo signs payment requests and verifies the payment results that Viamo sends back:

- `key1` signs the payment request and verifies the return URL, `key2` verifies the webhook. Keep both keys in the environment or a secret store; never commit them or log them.
- Always confirm a payment through `completePurchase()` or `webhook()`: a payment is paid only when `isSuccessful()` returns `true`. Do not trust query parameters or webhook bodies that you did not pass through the library.
- The signature covers the fields Viamo signs, not your order. After a successful response, look up the order by its variable symbol (`getVs()`) and compare the amount and state with your own records before you mark it paid, and make the handling idempotent (the webhook and the return URL can both arrive, and webhooks can be repeated).
- Serve the return URL and the webhook endpoint over HTTPS.
