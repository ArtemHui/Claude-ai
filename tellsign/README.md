# Tellsign — anti-scam landing page

A single static page for a scam-recognition and response practice. No build step,
no dependencies, no server: one `index.html` that can be hosted anywhere that
serves files.

Published preview: https://claude.ai/artifact/S7i3xzYQ818XPED94PgwgD

## Concept

The hero is the artifact of a scam rather than a padlock or shield: a realistic
bank SMS whose phrases are tappable, each one explaining the job it does. The
page earns credibility by demonstrating the expertise instead of asserting it.

The visual direction is deliberately light and editorial, against the
near-black/acid-green convention of the category. The people most often targeted
by these scams are older and less technical, and a fear-styled interface reads as
less trustworthy to them. Body type is Public Sans — the typeface designed for
government public communication.

## Deliberately absent

No testimonials, client logos, certification badges, partner marks or user
counts. On a site about fraud, invented trust signals are the technique the page
warns against, and one discovered fake would end the brand's credibility. The
credentials section is an explicit placeholder awaiting real details.

All of the advice is accurate: the non-existent "safe account", spoofable caller
ID and sender IDs, irreversible payment rails (transfer, crypto, gift cards), and
isolation as the operative mechanism.

## Placeholders to replace

| What | Where |
|---|---|
| Brand name and mark | `.brand` in the header and footer, plus `<title>` |
| Credentials, accreditations, contact | the `.placeholder` block near the foot |
| Service descriptions | the three `.services` articles |
| Fraud reporting body | currently region-neutral ("your national fraud reporting service") |

## Interaction

- **Message inspector** — five markers in the hero SMS, one explanation at a time,
  keyboard operable via `aria-expanded`.
- **Spot check** — four messages judged scam or genuine, each revealing why, with
  a running score.

Both work without any library. Motion respects `prefers-reduced-motion`, and the
page renders in light and dark themes.

## Hosting

Any static host: Netlify, Vercel, Cloudflare Pages, GitHub Pages, or plain
nginx/Apache. Copy `index.html` and point the domain at it. The only external
request is the Google Fonts stylesheet; self-host the two families if you would
rather the page make no third-party request at all (worth doing for EU traffic).
