// Rasterize the hand-made SVGs: public/og.svg to public/og.png (1200 x 630, the social card),
// and public/favicon.svg to the PNG icons, apple-touch-icon.png and favicon.ico.
// Run after `python3 scripts/art.py`:  CHROME_PATH=/path/to/chrome node scripts/og-image.mjs
import { readFileSync, writeFileSync } from 'node:fs'
import puppeteer from 'puppeteer-core'

const pub = (name) => new URL(`../public/${name}`, import.meta.url)
const defaultChrome = process.platform === 'darwin'
  ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
  : '/usr/bin/google-chrome'
const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || defaultChrome, args: ['--no-sandbox'] })
const page = await browser.newPage()

async function render(svgFile, width, height, transparent) {
  const svg = readFileSync(pub(svgFile), 'utf8').replace('<svg', `<svg width="${width}" height="${height}"`)
  await page.setViewport({ width, height })
  await page.setContent(`<html><body style="margin:0;background:${transparent ? 'transparent' : '#fff'}">${svg}</body></html>`)
  return Buffer.from(await page.screenshot({ omitBackground: transparent, clip: { x: 0, y: 0, width, height } }))
}

writeFileSync(pub('og.png'), await render('og.svg', 1200, 630, false))
const icons = { 'favicon-16.png': 16, 'favicon-32.png': 32, 'apple-touch-icon.png': 180, 'icon-192.png': 192, 'icon-512.png': 512 }
const png = {}
for (const [name, size] of Object.entries(icons)) {
  png[size] = await render('favicon.svg', size, size, true)
  writeFileSync(pub(name), png[size])
}
png[48] = await render('favicon.svg', 48, 48, true)
await browser.close()

// favicon.ico with 16, 32 and 48 px PNG images (the ICO container allows embedded PNG).
const sizes = [16, 32, 48]
const header = Buffer.alloc(6 + 16 * sizes.length)
header.writeUInt16LE(0, 0)
header.writeUInt16LE(1, 2)
header.writeUInt16LE(sizes.length, 4)
let offset = header.length
sizes.forEach((size, i) => {
  const entry = 6 + 16 * i
  header.writeUInt8(size, entry)
  header.writeUInt8(size, entry + 1)
  header.writeUInt16LE(1, entry + 4)
  header.writeUInt16LE(32, entry + 6)
  header.writeUInt32LE(png[size].length, entry + 8)
  header.writeUInt32LE(offset, entry + 12)
  offset += png[size].length
})
writeFileSync(pub('favicon.ico'), Buffer.concat([header, ...sizes.map((size) => png[size])]))
console.log('public/og.png, the PNG icons and favicon.ico written')
