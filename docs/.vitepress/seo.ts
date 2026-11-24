// Per-page SEO: title, canonical URL, Open Graph and Twitter tags, and JSON-LD.
// Used by transformHead in config.mts.
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import type { HeadConfig, PageData } from 'vitepress'

export const siteUrl = 'https://tomaj.github.io/omnipay-viamo/'
export const description = 'Viamo mobile payments gateway for the Omnipay PHP payment processing library: signed QR codes and app links, verified return URLs and webhooks.'
const ogImage = `${siteUrl}og.png`
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

// Every `## ` heading of faq.md is a question.
function faqEntries(source: string): { q: string, a: string }[] {
  return ('\n' + source).split('\n## ').slice(1).map((part) => {
    const [q, ...rest] = part.split('\n')
    return { q: plain(q), a: plain(rest.join('\n')) }
  }).filter((e) => e.q && e.a)
}

const sections: Record<string, [string, string]> = {
  guide: ['Guide', 'guide/getting-started.html'],
  examples: ['Examples', 'examples/'],
  api: ['API reference', 'api/reference.html'],
}

export function seoHead(page: PageData, srcDir: string): HeadConfig[] {
  if (page.isNotFound || !page.relativePath) return []
  const url = pageUrl(page.relativePath)
  const isHome = page.frontmatter.layout === 'home'
  const title = isHome ? 'Omnipay Viamo - Viamo mobile payments for PHP' : `${page.title} | ${siteName}`
  const pageDescription = page.description || page.frontmatter.description || description
  const modified = page.lastUpdated ? new Date(page.lastUpdated).toISOString() : undefined

  const head: HeadConfig[] = [
    ['link', { rel: 'canonical', href: url }],
    ['meta', { property: 'og:type', content: isHome ? 'website' : 'article' }],
    ['meta', { property: 'og:site_name', content: siteName }],
    ['meta', { property: 'og:locale', content: 'en_US' }],
    ['meta', { property: 'og:title', content: title }],
    ['meta', { property: 'og:description', content: pageDescription }],
    ['meta', { property: 'og:url', content: url }],
    ['meta', { property: 'og:image', content: ogImage }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:image:alt', content: 'Omnipay Viamo: Viamo mobile payments for PHP' }],
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: title }],
    ['meta', { name: 'twitter:description', content: pageDescription }],
    ['meta', { name: 'twitter:image', content: ogImage }],
    ['meta', { name: 'author', content: author.name }],
  ]
  if (modified && !isHome) head.push(['meta', { property: 'article:modified_time', content: modified }])

  const website = { '@type': 'WebSite', '@id': `${siteUrl}#website`, name: siteName, url: siteUrl, inLanguage: 'en-US', publisher: { '@id': author['@id'] } }
  const graph: Record<string, unknown>[] = [author, website]
  if (isHome) {
    graph.push({
      '@type': 'SoftwareSourceCode',
      '@id': `${siteUrl}#software`,
      name: siteName,
      description,
      url: siteUrl,
      codeRepository: repoUrl,
      programmingLanguage: { '@type': 'ComputerLanguage', name: 'PHP' },
      runtimePlatform: 'PHP 8.4',
      license: 'https://opensource.org/licenses/MIT',
      author: { '@id': author['@id'] },
      image: ogImage,
    })
  } else {
    graph.push({
      '@type': 'TechArticle',
      '@id': `${url}#article`,
      headline: page.title,
      description: pageDescription,
      url,
      inLanguage: 'en-US',
      image: ogImage,
      author: { '@id': author['@id'] },
      isPartOf: { '@id': `${siteUrl}#website` },
      ...(modified ? { dateModified: modified } : {}),
    })
    const items = [{ name: siteName, item: siteUrl }]
    const section = sections[page.relativePath.split('/')[0]]
    if (section && siteUrl + section[1] !== url) items.push({ name: section[0], item: siteUrl + section[1] })
    items.push({ name: page.title, item: url })
    graph.push({
      '@type': 'BreadcrumbList',
      itemListElement: items.map((entry, i) => ({ '@type': 'ListItem', position: i + 1, name: entry.name, item: entry.item })),
    })
  }
  if (page.relativePath === 'faq.md') {
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
