// Per-page SEO: title, canonical URL, Open Graph and Twitter tags, hreflang alternates and JSON-LD.
// Used by transformHead in config.mts.
import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import type { HeadConfig, PageData } from 'vitepress'

export const siteUrl = 'https://tomaj.github.io/omnipay-viamo/'
export const description = 'Viamo mobile payments gateway for the Omnipay PHP payment processing library: signed QR codes and app links, verified return URLs and webhooks.'
export const descriptionSk = 'Brána mobilných platieb Viamo pre PHP knižnicu Omnipay: podpísané QR kódy a odkazy do aplikácie, overené návratové URL a notifikácie (webhooky).'
const ogImage = `${siteUrl}og.png`
// Slovak pages get the social card with the Slovak tagline.
const ogImageSk = `${siteUrl}og-sk.png`
const siteName = 'Omnipay Viamo'
const repoUrl = 'https://github.com/tomaj/omnipay-viamo'
const author = { '@type': 'Person', '@id': `${siteUrl}#tomaj`, name: 'Tomas Majer', url: 'https://github.com/tomaj' }

export function pageUrl(relativePath: string): string {
  return siteUrl + relativePath.replace(/(^|\/)index\.md$/, '$1').replace(/\.md$/, '.html')
}

// Markdown to plain text, enough for JSON-LD answers.
function plain(markdown: string): string {
  return markdown
    .replace(/```[\s\S]*?```/g, '')
    .replace(/^<<< .*$/gm, '')
    .replace(/<[^>]+>/g, '')
    .replace(/\[([^\]]+)\]\([^)]*\)/g, '$1')
    .replace(/[`*_]/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

// Every `## ` heading of faq.md (and sk/faq.md) is a question.
function faqEntries(source: string): { q: string, a: string }[] {
  return ('\n' + source).split('\n## ').slice(1).map((part) => {
    const [q, ...rest] = part.split('\n')
    return { q: plain(q), a: plain(rest.join('\n')) }
  }).filter((e) => e.q && e.a)
}

// English pages live at the site root, Slovak pages mirror them under sk/.
export function localeOf(relativePath: string): { prefix: string, lang: string, ogLocale: string } {
  return relativePath.startsWith('sk/')
    ? { prefix: 'sk/', lang: 'sk-SK', ogLocale: 'sk_SK' }
    : { prefix: '', lang: 'en-US', ogLocale: 'en_US' }
}

// Section names for the breadcrumb, from the first path segment after the locale.
const sections: Record<string, Record<string, [string, string]>> = {
  '': {
    guide: ['Guide', 'guide/getting-started.html'],
    examples: ['Examples', 'examples/'],
    api: ['API reference', 'api/reference.html'],
  },
  'sk/': {
    guide: ['Príručka', 'guide/getting-started.html'],
    examples: ['Príklady', 'examples/'],
    api: ['Referencia API', 'api/reference.html'],
  },
}

// The same page in the other locale, when it exists, for hreflang links.
export function alternates(relativePath: string, srcDir: string): { lang: string, href: string }[] {
  const { prefix } = localeOf(relativePath)
  const base = relativePath.slice(prefix.length)
  const pairs: [string, string][] = [['en', base], ['sk', `sk/${base}`]]
  const found = pairs.filter(([, path]) => existsSync(resolve(srcDir, path)))
  if (found.length < 2) return []
  return [
    ...found.map(([lang, path]) => ({ lang, href: pageUrl(path) })),
    { lang: 'x-default', href: pageUrl(base) },
  ]
}

export function seoHead(page: PageData, srcDir: string): HeadConfig[] {
  if (page.isNotFound || !page.relativePath) return []
  const url = pageUrl(page.relativePath)
  const locale = localeOf(page.relativePath)
  const sk = locale.prefix === 'sk/'
  const siteDescription = sk ? descriptionSk : description
  const isHome = page.frontmatter.layout === 'home'
  const image = sk ? ogImageSk : ogImage
  const homeTitle = sk ? 'Omnipay Viamo - mobilné platby Viamo pre PHP' : 'Omnipay Viamo - Viamo mobile payments for PHP'
  const title = isHome ? homeTitle : `${page.title} | ${siteName}`
  const pageDescription = page.description || page.frontmatter.description || siteDescription
  const modified = page.lastUpdated ? new Date(page.lastUpdated).toISOString() : undefined

  const head: HeadConfig[] = [
    ['link', { rel: 'canonical', href: url }],
    ['meta', { property: 'og:type', content: isHome ? 'website' : 'article' }],
    ['meta', { property: 'og:site_name', content: siteName }],
    ['meta', { property: 'og:locale', content: locale.ogLocale }],
    ['meta', { property: 'og:locale:alternate', content: sk ? 'en_US' : 'sk_SK' }],
    ['meta', { property: 'og:title', content: title }],
    ['meta', { property: 'og:description', content: pageDescription }],
    ['meta', { property: 'og:url', content: url }],
    ['meta', { property: 'og:image', content: image }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:image:alt', content: sk ? 'Omnipay Viamo: mobilné platby Viamo pre PHP' : 'Omnipay Viamo: Viamo mobile payments for PHP' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: title }],
    ['meta', { name: 'twitter:description', content: pageDescription }],
    ['meta', { name: 'twitter:image', content: image }],
    ['meta', { name: 'author', content: author.name }],
  ]
  if (modified && !isHome) head.push(['meta', { property: 'article:modified_time', content: modified }])
  for (const alt of alternates(page.relativePath, srcDir)) {
    head.push(['link', { rel: 'alternate', hreflang: alt.lang, href: alt.href }])
  }

  const website = { '@type': 'WebSite', '@id': `${siteUrl}#website`, name: siteName, url: siteUrl, inLanguage: ['en-US', 'sk-SK'], publisher: { '@id': author['@id'] } }
  const graph: Record<string, unknown>[] = [author, website]
  if (isHome) {
    graph.push({
      '@type': 'SoftwareSourceCode',
      '@id': `${siteUrl}#software`,
      name: siteName,
      description: siteDescription,
      url: siteUrl + locale.prefix,
      inLanguage: locale.lang,
      codeRepository: repoUrl,
      programmingLanguage: { '@type': 'ComputerLanguage', name: 'PHP' },
      runtimePlatform: 'PHP 8.4',
      license: 'https://opensource.org/licenses/MIT',
      author: { '@id': author['@id'] },
      image,
    })
  } else {
    graph.push({
      '@type': 'TechArticle',
      '@id': `${url}#article`,
      headline: page.title,
      description: pageDescription,
      url,
      inLanguage: locale.lang,
      image,
      author: { '@id': author['@id'] },
      isPartOf: { '@id': `${siteUrl}#website` },
      ...(modified ? { dateModified: modified } : {}),
    })
    const home = siteUrl + locale.prefix
    const items = [{ name: siteName, item: home }]
    const section = sections[locale.prefix][page.relativePath.slice(locale.prefix.length).split('/')[0]]
    if (section && home + section[1] !== url) items.push({ name: section[0], item: home + section[1] })
    items.push({ name: page.title, item: url })
    graph.push({
      '@type': 'BreadcrumbList',
      itemListElement: items.map((entry, i) => ({ '@type': 'ListItem', position: i + 1, name: entry.name, item: entry.item })),
    })
  }
  if (page.relativePath === 'faq.md' || page.relativePath === 'sk/faq.md') {
    const source = readFileSync(resolve(srcDir, page.relativePath), 'utf8')
    graph.push({
      '@type': 'FAQPage',
      '@id': `${url}#faq`,
      mainEntity: faqEntries(source).map((e) => ({ '@type': 'Question', name: e.q, acceptedAnswer: { '@type': 'Answer', text: e.a } })),
    })
  }
  head.push(['script', { type: 'application/ld+json' }, JSON.stringify({ '@context': 'https://schema.org', '@graph': graph })])
  return head
}
