import { defineConfig } from 'vitepress'

const description = 'Viamo mobile payments gateway for the Omnipay PHP payment processing library'
const siteUrl = 'https://tomaj.github.io/omnipay-viamo/'
const currentVersion = '5.x'
const repositoryBlob = 'https://github.com/tomaj/omnipay-viamo/blob/master/'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: 'Omnipay Viamo',
  description,
  base: '/omnipay-viamo/',
  lang: 'en-US',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/omnipay-viamo/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#0f766e' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Omnipay Viamo' }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:url', content: siteUrl }],
    ['meta', { property: 'og:image', content: `${siteUrl}logo.svg` }],
  ],

  lastUpdated: true,

  markdown: {
    // changelog.md and contributing.md include files from the repository root; their relative
    // links to other root files (UPGRADE-5.0.md, SECURITY.md, ...) point at GitHub instead.
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

  sitemap: {
    hostname: siteUrl,
  },

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'API', link: '/api/reference' },
      {
        text: currentVersion,
        items: [
          { text: `${currentVersion} (master)`, link: '/' },
          { text: 'Changelog', link: '/changelog' },
          { text: 'Upgrading to 5.0', link: `${repositoryBlob}UPGRADE-5.0.md` },
        ],
      },
      { text: 'Coverage', link: 'https://tomaj.github.io/omnipay-viamo/coverage/' },
      { text: 'Releases', link: 'https://github.com/tomaj/omnipay-viamo/releases' },
    ],

    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'Getting started', link: '/guide/getting-started' },
          { text: 'Configuration', link: '/guide/configuration' },
        ],
      },
      {
        text: 'Guide',
        items: [
          { text: 'Operations', link: '/guide/operations' },
          { text: 'Test mode', link: '/guide/test-mode' },
          { text: 'Signatures', link: '/guide/signatures' },
        ],
      },
      {
        text: 'Reference',
        items: [
          { text: 'API reference', link: '/api/reference' },
          { text: 'FAQ', link: '/faq' },
          { text: 'Changelog', link: '/changelog' },
          { text: 'Contributing', link: '/contributing' },
        ],
      },
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/tomaj/omnipay-viamo' },
    ],

    editLink: {
      pattern: 'https://github.com/tomaj/omnipay-viamo/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },

    search: {
      provider: 'local',
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2018-2026 Tomas Majer',
    },
  },
})
