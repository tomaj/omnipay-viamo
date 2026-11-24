#!/usr/bin/env python3
"""Generate the hand-made SVG artwork of the documentation site (light and dark variants).

    python3 docs/scripts/art.py           # writes docs/public/{logo,illustrations,icons,diagrams}
    node docs/scripts/og-image.mjs        # then rasterizes og.png and the PNG/ICO favicons

The omnipay-* packages share one visual family: a 60 x 60 rounded square (rx 14) in the bank's colour with
a white glyph, flat shapes, system fonts. omnipay-viamo is the green one; its glyph is a "V"
tick next to a QR-code finder square.
"""

from __future__ import annotations

from pathlib import Path

PUBLIC = Path(__file__).resolve().parent.parent / "public"
FONT = "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
MONO = "ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"

THEMES = {
    "light": {
        "mark": "#0f9f55",
        "mark_dark": "#0b7f43",
        "glyph": "#ffffff",
        "bg": "#ffffff",
        "wash1": "#f3fbf6",
        "wash2": "#dcf5e6",
        "card": "#ffffff",
        "card_line": "#d5e6dc",
        "text": "#10261a",
        "muted": "#53675b",
        "brand": "#0b7f43",
        "accent": "#0f9f55",
        "soft": "#e3f6ea",
        "qr": "#10261a",
        "phone": "#10261a",
        "screen": "#ffffff",
        "arrow": "#0f9f55",
        "key2": "#0e7490",
        "lane": "#e6efe9",
    },
    "dark": {
        "mark": "#16b863",
        "mark_dark": "#0f9f55",
        "glyph": "#ffffff",
        "bg": "#1b1b1f",
        "wash1": "#13231a",
        "wash2": "#0f2e1d",
        "card": "#1f2a23",
        "card_line": "#2e4537",
        "text": "#e6f4ea",
        "muted": "#9db5a6",
        "brand": "#4ade80",
        "accent": "#22c55e",
        "soft": "#173524",
        "qr": "#e6f4ea",
        "phone": "#0b120e",
        "screen": "#18231c",
        "arrow": "#4ade80",
        "key2": "#38bdf8",
        "lane": "#2a3a30",
    },
}


def write(path: str, svg: str) -> None:
    target = PUBLIC / path
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(svg.strip() + "\n", encoding="utf-8")
    print(f"wrote {target.relative_to(PUBLIC.parent.parent)}")


# --- the mark -------------------------------------------------------------------------------

def mark(t: dict, x: float = 0, y: float = 0, size: float = 64) -> str:
    """The mark drawn in a 64 x 64 box, placed at (x, y) and scaled to `size`."""
    s = size / 64
    return f"""
  <g transform="translate({x} {y}) scale({s:g})">
    <rect x="2" y="2" width="60" height="60" rx="14" fill="{t['mark']}"/>
    <rect x="12" y="12" width="15" height="15" rx="3.5" fill="none" stroke="{t['glyph']}" stroke-width="3.2"/>
    <rect x="17" y="17" width="5" height="5" rx="1" fill="{t['glyph']}"/>
    <path d="M17 34 28.5 48 49 15.5" fill="none" stroke="{t['glyph']}" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
  </g>"""


def logo(theme: str) -> str:
    t = THEMES[theme]
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img">
  <title>Omnipay Viamo</title>{mark(t)}
</svg>"""


# --- a deterministic QR-like pattern --------------------------------------------------------

def qr(x: float, y: float, size: float, color: str, seed: int = 7) -> str:
    n = 25
    m = size / n
    finder = [(0, 0), (n - 7, 0), (0, n - 7)]

    def in_finder(cx: int, cy: int) -> bool:
        return any(fx - 1 <= cx <= fx + 7 and fy - 1 <= cy <= fy + 7 for fx, fy in finder)

    parts = []
    state = seed
    for cy in range(n):
        for cx in range(n):
            state = (state * 1103515245 + 12345) & 0x7FFFFFFF
            if in_finder(cx, cy) or (state >> 16) % 100 < 52:
                continue
            parts.append(f"M{x + cx * m:.1f} {y + cy * m:.1f}h{m:.1f}v{m:.1f}h-{m:.1f}z")
    finders = []
    for fx, fy in finder:
        ox, oy = x + fx * m, y + fy * m
        finders.append(
            f'<rect x="{ox + m / 2:.1f}" y="{oy + m / 2:.1f}" width="{6 * m:.1f}" height="{6 * m:.1f}" rx="{m:.1f}" fill="none" stroke="{color}" stroke-width="{m:.1f}"/>'
            f'<rect x="{ox + 2 * m:.1f}" y="{oy + 2 * m:.1f}" width="{3 * m:.1f}" height="{3 * m:.1f}" rx="{m * 0.6:.1f}" fill="{color}"/>'
        )
    return f'<path fill="{color}" d="{"".join(parts)}"/>' + "".join(finders)


# --- hero illustration ----------------------------------------------------------------------

def hero(theme: str) -> str:
    t = THEMES[theme]
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 560" role="img">
  <title>A shop shows a signed Viamo QR code, the customer pays in the Viamo app, and the shop verifies the return URL and the webhook</title>
  <defs>
    <linearGradient id="wash" x1="0" y1="0" x2="640" y2="560" gradientUnits="userSpaceOnUse">
      <stop stop-color="{t['wash1']}"/>
      <stop offset="1" stop-color="{t['wash2']}"/>
    </linearGradient>
    <marker id="head" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M1 1 9 5 1 9z" fill="{t['arrow']}"/>
    </marker>
    <marker id="head2" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M1 1 9 5 1 9z" fill="{t['key2']}"/>
    </marker>
  </defs>
  <rect width="640" height="560" rx="36" fill="url(#wash)"/>
  <g font-family="{FONT}">
    <!-- the shop's checkout page -->
    <rect x="34" y="52" width="300" height="290" rx="18" fill="{t['card']}" stroke="{t['card_line']}" stroke-width="1.5"/>
    <path d="M34 70a18 18 0 0 1 18-18h264a18 18 0 0 1 18 18v14H34z" fill="{t['soft']}"/>
    <circle cx="56" cy="69" r="5" fill="{t['card_line']}"/>
    <circle cx="72" cy="69" r="5" fill="{t['card_line']}"/>
    <circle cx="88" cy="69" r="5" fill="{t['card_line']}"/>
    <rect x="108" y="61" width="200" height="16" rx="8" fill="{t['card']}"/>
    <text x="120" y="73.5" font-size="11" fill="{t['muted']}">shop.example/checkout</text>
    <text x="56" y="118" font-size="13" fill="{t['muted']}">Order 2026000123</text>
    <text x="56" y="148" font-size="26" font-weight="700" fill="{t['text']}">24.90 €</text>
    <text x="56" y="176" font-size="12.5" fill="{t['muted']}">Scan with the</text>
    <text x="56" y="194" font-size="12.5" fill="{t['muted']}">Viamo app</text>
    <rect x="56" y="214" width="96" height="30" rx="15" fill="{t['accent']}"/>
    <text x="104" y="233.5" font-size="12" font-weight="600" fill="#ffffff" text-anchor="middle">purchase()</text>
    <rect x="196" y="106" width="122" height="122" rx="10" fill="#ffffff" stroke="{t['card_line']}"/>
    {qr(204, 114, 106, '#10261a')}
    <text x="56" y="296" font-size="11" fill="{t['muted']}" font-family="{MONO}">QP:1.0*BID:42*AM:24.90*</text>
    <text x="56" y="314" font-size="11" fill="{t['muted']}" font-family="{MONO}">CC:EUR*VS:2026000123*SIG:5B0A…</text>

    <!-- scan -->
    <path d="M318 172 C 360 172, 372 160, 404 160" fill="none" stroke="{t['arrow']}" stroke-width="2.5" stroke-dasharray="6 6" marker-end="url(#head)"/>
    <text x="352" y="150" font-size="12" font-weight="600" fill="{t['brand']}" text-anchor="middle">scan</text>

    <!-- the phone -->
    <rect x="412" y="40" width="190" height="370" rx="32" fill="{t['phone']}"/>
    <rect x="422" y="50" width="170" height="350" rx="24" fill="{t['screen']}"/>
    <rect x="482" y="58" width="50" height="8" rx="4" fill="{t['phone']}"/>
    <path d="M422 74a24 24 0 0 1 24-24h122a24 24 0 0 1 24 24v32H422z" fill="{t['mark']}"/>
    {mark({**t, 'mark': t['mark_dark']}, 436, 70, 30)}
    <text x="474" y="91" font-size="16" font-weight="700" fill="#ffffff">Viamo</text>
    <text x="507" y="146" font-size="12" fill="{t['muted']}" text-anchor="middle">Pay to Shop Example</text>
    <text x="507" y="184" font-size="30" font-weight="700" fill="{t['text']}" text-anchor="middle">24.90 €</text>
    <rect x="440" y="204" width="134" height="1" fill="{t['card_line']}"/>
    <text x="442" y="228" font-size="11" fill="{t['muted']}">VS</text>
    <text x="572" y="228" font-size="11" fill="{t['text']}" text-anchor="end">2026000123</text>
    <text x="442" y="250" font-size="11" fill="{t['muted']}">Message</text>
    <text x="572" y="250" font-size="11" fill="{t['text']}" text-anchor="end">Order 2026…</text>
    <rect x="440" y="300" width="134" height="40" rx="20" fill="{t['accent']}"/>
    <path d="M478 320l7 7 13-14" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="507" y="325" font-size="14" font-weight="700" fill="#ffffff">Pay</text>

    <!-- back to the shop -->
    <rect x="34" y="384" width="300" height="140" rx="18" fill="{t['card']}" stroke="{t['card_line']}" stroke-width="1.5"/>
    <text x="56" y="416" font-size="13" font-weight="700" fill="{t['text']}">Your shop</text>
    <rect x="56" y="432" width="256" height="34" rx="10" fill="{t['soft']}"/>
    <circle cx="74" cy="449" r="9" fill="{t['accent']}"/>
    <path d="M69.5 449l3 3 6-6" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="92" y="453.5" font-size="12.5" fill="{t['text']}" font-family="{MONO}">completePurchase()</text>
    <rect x="56" y="474" width="256" height="34" rx="10" fill="{t['soft']}"/>
    <circle cx="74" cy="491" r="9" fill="{t['key2']}"/>
    <path d="M69.5 491l3 3 6-6" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="92" y="495.5" font-size="12.5" fill="{t['text']}" font-family="{MONO}">webhook()</text>
    <text x="300" y="495.5" font-size="11" fill="{t['muted']}" text-anchor="end">paid</text>
    <text x="300" y="453.5" font-size="11" fill="{t['muted']}" text-anchor="end">RES:OK</text>

    <path d="M470 412 C 470 452, 420 449, 348 449" fill="none" stroke="{t['arrow']}" stroke-width="2.5" marker-end="url(#head)"/>
    <path d="M548 412 C 548 486, 460 491, 348 491" fill="none" stroke="{t['key2']}" stroke-width="2.5" marker-end="url(#head2)"/>
    <text x="352" y="475" font-size="11.5" font-weight="600" fill="{t['brand']}">return URL · key1</text>
    <text x="456" y="520" font-size="11.5" font-weight="600" fill="{t['key2']}">webhook · key2</text>
  </g>
</svg>"""


# --- feature icons --------------------------------------------------------------------------

ICONS = {
    "qr": lambda t: (
        f'<rect x="12" y="12" width="10" height="10" rx="2" fill="none" stroke="{t["brand"]}" stroke-width="2.5"/>'
        f'<rect x="26" y="12" width="10" height="10" rx="2" fill="none" stroke="{t["brand"]}" stroke-width="2.5"/>'
        f'<rect x="12" y="26" width="10" height="10" rx="2" fill="none" stroke="{t["brand"]}" stroke-width="2.5"/>'
        f'<path d="M27 27h3v3h-3zM32 32h3v3h-3zM27 33h3v3h-3zM33 26h3v3h-3z" fill="{t["brand"]}"/>'
    ),
    "verified": lambda t: (
        f'<path d="M24 10l12 4.5v8.5c0 7.5-5 12.5-12 15-7-2.5-12-7.5-12-15v-8.5z" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linejoin="round"/>'
        f'<path d="M18.5 24l4 4 7.5-8" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
    ),
    "webhook": lambda t: (
        f'<rect x="20" y="13" width="17" height="9" rx="2.5" fill="none" stroke="{t["brand"]}" stroke-width="2.5"/>'
        f'<rect x="20" y="26" width="17" height="9" rx="2.5" fill="none" stroke="{t["brand"]}" stroke-width="2.5"/>'
        f'<circle cx="32" cy="17.5" r="1.4" fill="{t["brand"]}"/><circle cx="32" cy="30.5" r="1.4" fill="{t["brand"]}"/>'
        f'<path d="M9 24h7m-3-3.5 3.5 3.5-3.5 3.5" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
    ),
    "key": lambda t: (
        f'<circle cx="18" cy="24" r="6" fill="none" stroke="{t["brand"]}" stroke-width="2.5"/>'
        f'<path d="M24 24h13m-4 0v5m-5-5v4" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
    ),
    "omnipay": lambda t: (
        f'<path d="M14 16h20v8a10 10 0 0 1-20 0z" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linejoin="round"/>'
        f'<path d="M19 9v7m10-7v7M24 34v5" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linecap="round"/>'
    ),
    "offline": lambda t: (
        f'<path d="M20 10h8m-6 0v9l-8 14a3 3 0 0 0 2.6 4.5h14.8A3 3 0 0 0 34 33l-8-14v-9" fill="none" stroke="{t["brand"]}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
        f'<path d="M16.5 29h15" stroke="{t["brand"]}" stroke-width="2.5" stroke-linecap="round"/>'
    ),
}


def icon(name: str, theme: str) -> str:
    t = THEMES[theme]
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" role="img">
  <title>{name}</title>
  <rect x="1" y="1" width="46" height="46" rx="12" fill="{t['soft']}"/>
  {ICONS[name](t)}
</svg>"""


# --- payment flow diagram -------------------------------------------------------------------

def flow(theme: str) -> str:
    t = THEMES[theme]
    lanes = [("Customer", "browser or phone", 110), ("Your shop", "omnipay-viamo", 350), ("Viamo app", "on the phone", 590), ("Viamo", "server", 830)]
    out = [f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 940 690" role="img">
  <title>Viamo payment flow: purchase, scan and confirm, webhook, return URL</title>
  <defs>
    <marker id="a" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M1 1 9 5 1 9z" fill="{t['arrow']}"/></marker>
    <marker id="b" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M1 1 9 5 1 9z" fill="{t['key2']}"/></marker>
    <marker id="c" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M1 1 9 5 1 9z" fill="{t['muted']}"/></marker>
  </defs>
  <rect width="940" height="690" rx="24" fill="{t['bg']}"/>
  <g font-family="{FONT}">"""]
    for name, sub, x in lanes:
        out.append(f"""
    <rect x="{x - 90}" y="24" width="180" height="56" rx="14" fill="{t['soft']}" stroke="{t['card_line']}"/>
    <text x="{x}" y="49" font-size="16" font-weight="700" fill="{t['text']}" text-anchor="middle">{name}</text>
    <text x="{x}" y="68" font-size="12" fill="{t['muted']}" text-anchor="middle">{sub}</text>
    <path d="M{x} 80V610" stroke="{t['lane']}" stroke-width="2" stroke-dasharray="4 6"/>""")

    def arrow(y: int, x1: int, x2: int, label: str, code: str = "", color: str = "", marker: str = "a", dashed: bool = False) -> None:
        color = color or t["arrow"]
        dash = ' stroke-dasharray="7 6"' if dashed else ""
        end = x2 - 8 if x2 > x1 else x2 + 8
        mid = (x1 + x2) / 2
        out.append(f"""
    <path d="M{x1} {y}H{end}" stroke="{color}" stroke-width="2.5"{dash} marker-end="url(#{marker})"/>
    <text x="{mid}" y="{y - 10}" font-size="13" font-weight="600" fill="{t['text']}" text-anchor="middle">{label}</text>""")
        if code:
            out.append(f"""
    <text x="{mid}" y="{y + 20}" font-size="11.5" fill="{t['muted']}" text-anchor="middle" font-family="{MONO}">{code}</text>""")

    def note(y: int, x: int, n: str, label: str, code: str) -> None:
        out.append(f"""
    <rect x="{x - 112}" y="{y - 22}" width="224" height="46" rx="12" fill="{t['card']}" stroke="{t['accent']}" stroke-width="1.5"/>
    <circle cx="{x - 90}" cy="{y + 1}" r="11" fill="{t['accent']}"/>
    <text x="{x - 90}" y="{y + 5.5}" font-size="12" font-weight="700" fill="#ffffff" text-anchor="middle">{n}</text>
    <text x="{x - 70}" y="{y - 2}" font-size="12.5" font-weight="700" fill="{t['text']}">{label}</text>
    <text x="{x - 70}" y="{y + 15}" font-size="11" fill="{t['muted']}" font-family="{MONO}">{code}</text>""")

    arrow(120, 110, 350, "checkout")
    note(160, 350, "1", "purchase()", "signs the request, key1")
    arrow(222, 350, 110, "QR code or app link", "viamo://?requestString=…*SIG:…")
    arrow(290, 110, 590, "scan the QR code or open the link", color=t["muted"], marker="c", dashed=True)
    arrow(350, 590, 830, "check SIG, customer confirms", color=t["muted"], marker="c", dashed=True)
    arrow(410, 830, 350, "POST webhook: JSON + signature", "rid vs e2e result amount id, key2", color=t["key2"], marker="b")
    note(452, 350, "2", "webhook()", "verifies key2, result OK")
    arrow(520, 590, 350, "GET rurl?responseString=…", "BID:42*VS:…*RES:OK*SIG:…, key1")
    note(580, 350, "3", "completePurchase()", "verifies BID and key1")
    out.append(f"""
    <rect x="50" y="628" width="840" height="42" rx="12" fill="{t['soft']}"/>
    <text x="470" y="654" font-size="13" fill="{t['text']}" text-anchor="middle">2 and 3 arrive in any order, or only one of them: find the order by VS, check the amount, mark it paid once.</text>
  </g>
</svg>""")
    return "".join(out)


# --- social card (rasterized to og.png by og-image.mjs) -----------------------------------

def og() -> str:
    t = THEMES["dark"]
    return f"""<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" role="img">
  <title>Omnipay Viamo: Viamo mobile payments for PHP</title>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1200" y2="630" gradientUnits="userSpaceOnUse">
      <stop stop-color="#0b1f14"/>
      <stop offset="1" stop-color="#0f3b22"/>
    </linearGradient>
  </defs>
  <rect width="1200" height="630" fill="url(#bg)"/>
  <circle cx="1040" cy="90" r="260" fill="#16b863" opacity="0.10"/>
  {mark(t, 80, 84, 112)}
  <g font-family="{FONT}">
    <text x="80" y="300" font-size="76" font-weight="800" fill="#ffffff">Omnipay Viamo</text>
    <text x="80" y="362" font-size="32" fill="#b9e8cb">Viamo mobile payments for PHP</text>
    <text x="80" y="420" font-size="25" fill="#8fbfa2">Signed QR codes and app links,</text>
    <text x="80" y="456" font-size="25" fill="#8fbfa2">verified return URLs and webhooks.</text>
    <rect x="80" y="506" width="580" height="56" rx="14" fill="#06140c" stroke="#245a3a"/>
    <text x="104" y="542" font-size="22" fill="#4ade80" font-family="{MONO}">composer require tomaj/omnipay-viamo</text>
  </g>
  <rect x="790" y="120" width="300" height="420" rx="36" fill="#06140c"/>
  <rect x="804" y="134" width="272" height="392" rx="26" fill="#ffffff"/>
  {qr(850, 200, 180, '#0b1f14')}
  <rect x="850" y="420" width="180" height="48" rx="24" fill="#0f9f55"/>
  <text x="940" y="451" font-size="20" font-weight="700" fill="#ffffff" text-anchor="middle" font-family="{FONT}">Pay 24.90 €</text>
</svg>"""


def main() -> None:
    write("logo.svg", logo("light"))
    write("logo-dark.svg", logo("dark"))
    write("favicon.svg", logo("light"))
    for theme in THEMES:
        suffix = "" if theme == "light" else "-dark"
        write(f"illustrations/hero-{theme}.svg", hero(theme))
        write(f"diagrams/payment-flow-{theme}.svg", flow(theme))
        for name in ICONS:
            write(f"icons/{name}{suffix}.svg", icon(name, theme))
    write("og.svg", og())


if __name__ == "__main__":
    main()
