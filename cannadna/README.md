# CannaDNA — CBD review hub

A WordPress build for an affiliate CBD/hemp review site. Two pieces:

| Component | What it is |
|---|---|
| `plugins/review-hub` | Standalone plugin. Content types, filtering, carousels, product finder, brand comparison, affiliate click tracking. Works with any theme. |
| `themes/verdant-lab` | Child theme of Twenty Twenty-Five. Dark canvas, page width, age gate, affiliate disclosure. |

The split is deliberate: the plugin owns the data and the interactive components, the
theme owns the page. You can drop the plugin into an existing theme and only the
canvas styling will differ.

## Install

1. Upload `plugins/review-hub` to `wp-content/plugins/` and activate.
   Activation registers the content types and creates the click-tracking table.
2. Upload `themes/verdant-lab` to `wp-content/themes/` and activate
   (requires the Twenty Twenty-Five parent theme).
3. Visit **Settings → Permalinks** and save once, so the `/go/{id}/` tracking
   route registers.

## Shortcodes

| Shortcode | Purpose |
|---|---|
| `[rh_stats]` | Stat band. Counts are queried from published content. |
| `[rh_categories target="rh-main-grid"]` | Category chips that filter the grid with that `id`. |
| `[rh_grid id="rh-main-grid" per_page="12"]` | Faceted listing: goal, region, minimum score, lab-tested, sorting. |
| `[rh_carousel title="Top rated" per_page="10" autoplay="false"]` | Swiper carousel. |
| `[rh_finder mode="inline"]` | Guided finder. `mode="modal"` renders a trigger button instead. |
| `[rh_compare]` | Side-by-side comparison of up to three brands. |

## Content model

**Product Review** (`rh_product`) and **Brand** (`rh_brand`), sharing three
taxonomies: Product Type (`rh_category`), Goal (`rh_concern`), and Availability
(`rh_region`).

Each review carries a rating, brand, price and pre-discount price, currency,
affiliate URL, discount code, CBD mg, THC %, and a lab-tested flag. These are
plain post meta with a metabox — no ACF or other plugin dependency.

## Notes on specific decisions

**Stat counters read from the database.** They are rendered server-side from
`wp_count_posts` and a rating average, so they cannot render as a placeholder
zero. The count-up animation is layered on top of already-correct markup, and is
skipped under `prefers-reduced-motion`.

**Affiliate clicks route through `/go/{id}/`.** The destination is always looked
up from post meta by ID; a URL in the request is never used as a redirect
target, so the endpoint cannot be used as an open redirect. Clicks are logged to
`{prefix}_review_hub_clicks` with timestamp, referrer and an optional `?src=`
label. Outbound links carry `rel="nofollow sponsored noopener"`.

**Libraries are bundled, not loaded from a CDN.** Swiper 11, AOS 2.3.4 and
GLightbox 3 ship inside the plugin. No visitor IP is sent to a third-party CDN,
which matters for EU traffic, and the site does not break if a CDN is blocked.

**System fonts, not Google Fonts.** Embedding Google Fonts has been ruled a GDPR
problem in the EU, and this vertical skews EU. It also removes a render-blocking
request. Swap in a self-hosted face if you want a distinct brand voice.

**Scroll-reveal is not applied to product cards.** AOS leaves elements at
`opacity: 0` until scrolled into view. On product cards that means a crawler or
a visitor who jumps down the page sees nothing, so the reveal is limited to the
decorative category chips. This was caught in testing — see below.

**Listings render server-side first.** The grid and carousels are full HTML on
load and are only replaced once a filter changes, so the page works without JS
and stays indexable.

## Local development

Requires PHP 8.x with `pdo_sqlite`, and Node only if you want to re-vendor the
libraries.

```bash
# WordPress core + SQLite drop-in, then:
php wp-cli.phar eval-file dev/seed.php --allow-root --path=wordpress
php -S 127.0.0.1:8080 -t wordpress dev/router.php
```

`dev/seed.php` creates taxonomy terms, five fictional brands and sixteen product
reviews. The brands are invented. Real brand names are deliberately absent:
ratings and lab-testing claims about real companies need actual editorial
review, not generated filler.

## Tests

`dev/test_site.py` drives the site with Playwright — 33 checks covering the age
gate, filtering, facets, carousel, the finder wizard, comparison, click
tracking, redirect safety, and responsive behaviour at 1440 / 834 / 390px.

```bash
pip install playwright && python3 dev/test_site.py
```

Three of these exist because they caught real bugs:

- **`no product card is left invisible`** — AOS was hiding 6 of 8 grid cards and
  all carousel cards at `opacity: 0`.
- **`JS-rendered card matches server-rendered markup`** — the PHP and JS card
  renderers had drifted, producing "Lab / Verified" server-side and
  "Lab / Lab tested" after filtering.
- **`no card content is clipped`** — the "View offer" CTA overflowed its card at
  tablet column widths.

## Not done yet

- Product detail templates (single review / single brand layouts)
- GLightbox is bundled and initialised for `.rh-lightbox` but no gallery markup
  uses it yet
- An admin screen for the click log (data is recorded; reporting is not built)
- Real content, images, and brand naming
