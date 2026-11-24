import { defineConfig } from 'vitepress'
import { description, descriptionSk, seoHead, siteUrl } from './seo'

const base = '/omnipay-viamo/'
const currentVersion = '5.x'
const repository = 'https://github.com/tomaj/omnipay-viamo'
const repositoryBlob = `${repository}/blob/master/`

const nav = [
  { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
  { text: 'Examples', link: '/examples/', activeMatch: '/examples/' },
  { text: 'API', link: '/api/reference', activeMatch: '/api/' },
  {
    text: currentVersion,
    items: [
      { text: `${currentVersion} (master)`, link: '/' },
      { text: 'Upgrading from 4.x', link: '/upgrade' },
      { text: 'Changelog', link: '/changelog' },
      { text: 'Releases', link: `${repository}/releases` },
      { text: 'Code coverage', link: `${siteUrl}coverage/` },
    ],
  },
]

const navSk = [
  { text: 'Príručka', link: '/sk/guide/getting-started', activeMatch: '/sk/guide/' },
  { text: 'Príklady', link: '/sk/examples/', activeMatch: '/sk/examples/' },
  { text: 'API', link: '/sk/api/reference', activeMatch: '/sk/api/' },
  {
    text: currentVersion,
    items: [
      { text: `${currentVersion} (master)`, link: '/sk/' },
      { text: 'Prechod zo 4.x', link: '/sk/upgrade' },
      { text: 'Zoznam zmien', link: '/sk/changelog' },
      { text: 'Vydania', link: `${repository}/releases` },
      { text: 'Pokrytie kódu testami', link: `${siteUrl}coverage/` },
    ],
  },
]

// One sidebar per locale with the same pages in the same order.
function sidebarFor(prefix: string, t: Record<string, string>) {
  return [
    {
      text: t.introduction,
      items: [
        { text: t.gettingStarted, link: `${prefix}/guide/getting-started` },
        { text: t.paymentFlow, link: `${prefix}/guide/payment-flow` },
        { text: t.configuration, link: `${prefix}/guide/configuration` },
      ],
    },
    {
      text: t.operations,
      items: [
        { text: t.overview, link: `${prefix}/guide/operations` },
        { text: t.purchase, link: `${prefix}/guide/purchase` },
        { text: t.returnUrl, link: `${prefix}/guide/return-url` },
        { text: t.webhook, link: `${prefix}/guide/webhook` },
      ],
    },
    {
      text: t.inDepth,
      items: [
        { text: t.signatures, link: `${prefix}/guide/signatures` },
        { text: t.errors, link: `${prefix}/guide/errors` },
        { text: t.testMode, link: `${prefix}/guide/test-mode` },
        { text: t.testing, link: `${prefix}/guide/testing` },
      ],
    },
    {
      text: t.examples,
      items: [
        { text: t.overview, link: `${prefix}/examples/` },
        { text: t.qrCheckout, link: `${prefix}/examples/qr-checkout` },
        { text: t.exReturnUrl, link: `${prefix}/examples/return-url` },
        { text: t.exWebhook, link: `${prefix}/examples/webhook` },
        { text: t.shop, link: `${prefix}/examples/shop` },
        { text: t.exSignatures, link: `${prefix}/examples/signatures` },
      ],
    },
    {
      text: t.reference,
      items: [
        { text: t.api, link: `${prefix}/api/reference` },
        { text: t.faq, link: `${prefix}/faq` },
        { text: t.upgrade, link: `${prefix}/upgrade` },
        { text: t.changelog, link: `${prefix}/changelog` },
        { text: t.security, link: `${prefix}/security` },
        { text: t.contributing, link: `${prefix}/contributing` },
      ],
    },
  ]
}

const sidebar = sidebarFor('', {
  introduction: 'Introduction',
  gettingStarted: 'Getting started',
  paymentFlow: 'Payment flow',
  configuration: 'Configuration',
  operations: 'Operations',
  overview: 'Overview',
  purchase: 'purchase(): QR code and app link',
  returnUrl: 'completePurchase(): return URL',
  webhook: 'webhook(): notification',
  inDepth: 'In depth',
  signatures: 'Signatures',
  errors: 'Error handling',
  testMode: 'Test mode',
  testing: 'Testing your integration',
  examples: 'Examples',
  qrCheckout: 'QR checkout',
  exReturnUrl: 'Return URL',
  exWebhook: 'Webhook',
  shop: 'Shop with a fake Viamo',
  exSignatures: 'Signatures and errors',
  reference: 'Reference',
  api: 'API reference',
  faq: 'FAQ',
  upgrade: 'Upgrading from 4.x',
  changelog: 'Changelog',
  security: 'Security',
  contributing: 'Contributing',
})

const sidebarSk = sidebarFor('/sk', {
  introduction: 'Úvod',
  gettingStarted: 'Začíname',
  paymentFlow: 'Priebeh platby',
  configuration: 'Konfigurácia',
  operations: 'Operácie',
  overview: 'Prehľad',
  purchase: 'purchase(): QR kód a odkaz do aplikácie',
  returnUrl: 'completePurchase(): návratová URL',
  webhook: 'webhook(): notifikácia',
  inDepth: 'Podrobne',
  signatures: 'Podpisy',
  errors: 'Spracovanie chýb',
  testMode: 'Testovací režim',
  testing: 'Testovanie integrácie',
  examples: 'Príklady',
  qrCheckout: 'Platba cez QR kód',
  exReturnUrl: 'Návratová URL',
  exWebhook: 'Webhook',
  shop: 'E-shop s falošným Viamom',
  exSignatures: 'Podpisy a chyby',
  reference: 'Referencia',
  api: 'Referencia API',
  faq: 'Časté otázky',
  upgrade: 'Prechod zo 4.x',
  changelog: 'Zoznam zmien',
  security: 'Bezpečnosť',
  contributing: 'Ako prispieť',
})

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Omnipay Viamo',
  description,
  base,
  cleanUrls: false,

  head: [
    ['link', { rel: 'icon', href: `${base}favicon.ico`, sizes: '48x48' }],
    ['link', { rel: 'icon', type: 'image/svg+xml', href: `${base}favicon.svg` }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '32x32', href: `${base}favicon-32.png` }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '16x16', href: `${base}favicon-16.png` }],
    ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: `${base}apple-touch-icon.png` }],
    ['link', { rel: 'manifest', href: `${base}site.webmanifest` }],
    ['meta', { name: 'theme-color', content: '#0f9f55' }],
  ],

  lastUpdated: true,

  sitemap: {
    hostname: siteUrl,
  },

  transformHead({ pageData, siteConfig }) {
    return seoHead(pageData, siteConfig.srcDir)
  },

  // English is the default locale at the site root; Slovak mirrors every page under /sk/.
  locales: {
    root: {
      label: 'English',
      lang: 'en-US',
      description,
    },
    sk: {
      label: 'Slovenčina',
      lang: 'sk-SK',
      link: '/sk/',
      description: descriptionSk,
      themeConfig: {
        nav: navSk,
        sidebar: sidebarSk,
        outline: { level: [2, 3], label: 'Na tejto stránke' },
        docFooter: {
          prev: 'Predchádzajúca stránka',
          next: 'Nasledujúca stránka',
        },
        editLink: {
          pattern: `${repository}/edit/master/docs/:path`,
          text: 'Upraviť túto stránku na GitHube',
        },
        lastUpdated: {
          text: 'Naposledy upravené',
        },
        darkModeSwitchLabel: 'Vzhľad',
        lightModeSwitchTitle: 'Prepnúť na svetlý režim',
        darkModeSwitchTitle: 'Prepnúť na tmavý režim',
        sidebarMenuLabel: 'Ponuka',
        returnToTopLabel: 'Späť na začiatok',
        langMenuLabel: 'Zmeniť jazyk',
        skipToContentLabel: 'Preskočiť na obsah',
        footer: {
          message: 'Vydané pod licenciou MIT. Projekt nie je spojený so spoločnosťou Viamo.',
          copyright: 'Copyright © 2018-2026 Tomas Majer',
        },
        notFound: {
          code: '404',
          title: 'STRÁNKA SA NENAŠLA',
          quote: 'Táto stránka neexistuje alebo sa presunula. Skúste vyhľadávanie alebo začnite od úvodu.',
          linkLabel: 'prejsť na úvod',
          linkText: 'Späť na úvod',
        },
      },
    },
  },

  markdown: {
    // changelog.md, upgrade.md, security.md and contributing.md include files from the repository
    // root; their relative links to other root files (UPGRADE-5.0.md, SECURITY.md, ...) point at GitHub.
    config(md) {
      md.core.ruler.push('repository-root-links', (state) => {
        for (const token of state.tokens) {
          for (const child of token.children ?? []) {
            const href = child.type === 'link_open' ? child.attrGet('href') : null
            if (href !== null && /^[A-Z][A-Z0-9_.-]*\.md(#.*)?$/.test(href)) {
              child.attrSet('href', repositoryBlob + href)
            }
          }
        }
      })
    },
  },

  themeConfig: {
    logo: {
      light: '/logo.svg',
      dark: '/logo-dark.svg',
      alt: 'Omnipay Viamo',
    },

    nav,
    sidebar,

    socialLinks: [
      { icon: 'github', link: repository },
    ],

    editLink: {
      pattern: `${repository}/edit/master/docs/:path`,
      text: 'Edit this page on GitHub',
    },

    lastUpdated: {
      text: 'Last updated',
    },

    outline: {
      level: [2, 3],
    },

    search: {
      provider: 'local',
      options: {
        locales: {
          sk: {
            translations: {
              button: {
                buttonText: 'Hľadať',
                buttonAriaLabel: 'Hľadať',
              },
              modal: {
                displayDetails: 'Zobraziť podrobný zoznam',
                resetButtonTitle: 'Vymazať hľadanie',
                backButtonTitle: 'Zavrieť hľadanie',
                noResultsText: 'Žiadne výsledky pre',
                footer: {
                  selectText: 'vybrať',
                  selectKeyAriaLabel: 'enter',
                  navigateText: 'prejsť',
                  navigateUpKeyAriaLabel: 'šípka hore',
                  navigateDownKeyAriaLabel: 'šípka dole',
                  closeText: 'zavrieť',
                  closeKeyAriaLabel: 'escape',
                },
              },
            },
          },
        },
      },
    },

    footer: {
      message: 'Released under the MIT License. Not affiliated with Viamo.',
      copyright: 'Copyright © 2018-2026 Tomas Majer',
    },
  },
})
