---
layout: home

hero:
  name: Omnipay Viamo
  text: Viamo mobile payments for Omnipay
  tagline: Signed QR codes and app links, verified return URLs and webhooks, in PHP 8.4+
  image:
    src: /logo.svg
    alt: Omnipay Viamo
  actions:
    - theme: brand
      text: Get started
      link: /guide/getting-started
    - theme: alt
      text: Operations
      link: /guide/operations
    - theme: alt
      text: View on GitHub
      link: https://github.com/tomaj/omnipay-viamo

features:
  - title: QR code and app link
    details: purchase() signs the Viamo payment request and returns it as a base64 PNG QR code, a URL of Viamo's QR API and a viamo:// link for phones.
  - title: Verified results
    details: completePurchase() checks the merchant and the AES signature of the return URL; webhook() checks every signed field of the notification. Signatures are compared in constant time.
  - title: Omnipay gateway
    details: Create it with Omnipay::create('Viamo'), configure it once with your merchant id and keys, and use it like any other Omnipay gateway.
  - title: Tested against openssl
    details: The test suite checks the signatures against values computed with the openssl CLI, with 100% statement coverage and mutation testing.
---
