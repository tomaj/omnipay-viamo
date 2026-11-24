import { defineConfig } from 'vitepress'
import { description, seoHead, siteUrl } from './seo'

const base = '/omnipay-viamo/'
const currentVersion = '5.x'
const repository = 'https://github.com/tomaj/omnipay-viamo'
const repositoryBlob = `${repository}/blob/master/`

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Omnipay Viamo',
  description,
  base,
  lang: 'en-US',
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

    nav: [
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
    ],

    sidebar: {
      '/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting started', link: '/guide/getting-started' },
            { text: 'Payment flow', link: '/guide/payment-flow' },
            { text: 'Configuration', link: '/guide/configuration' },
          ],
        },
        {
          text: 'Operations',
          items: [
            { text: 'Overview', link: '/guide/operations' },
            { text: 'purchase(): QR code and app link', link: '/guide/purchase' },
            { text: 'completePurchase(): return URL', link: '/guide/return-url' },
            { text: 'webhook(): notification', link: '/guide/webhook' },
          ],
        },
        {
          text: 'In depth',
          items: [
            { text: 'Signatures', link: '/guide/signatures' },
            { text: 'Error handling', link: '/guide/errors' },
            { text: 'Test mode', link: '/guide/test-mode' },
            { text: 'Testing your integration', link: '/guide/testing' },
          ],
        },
        {
          text: 'Examples',
          items: [
            { text: 'Overview', link: '/examples/' },
            { text: 'QR checkout', link: '/examples/qr-checkout' },
            { text: 'Return URL', link: '/examples/return-url' },
            { text: 'Webhook', link: '/examples/webhook' },
            { text: 'Shop with a fake Viamo', link: '/examples/shop' },
            { text: 'Signatures and errors', link: '/examples/signatures' },
          ],
        },
        {
          text: 'Reference',
          items: [
            { text: 'API reference', link: '/api/reference' },
            { text: 'FAQ', link: '/faq' },
            { text: 'Upgrading from 4.x', link: '/upgrade' },
            { text: 'Changelog', link: '/changelog' },
            { text: 'Security', link: '/security' },
            { text: 'Contributing', link: '/contributing' },
          ],
        },
      ],
    },

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
    },

    footer: {
      message: 'Released under the MIT License. Not affiliated with Viamo.',
      copyright: 'Copyright © 2018-2026 Tomas Majer',
    },
  },
})
