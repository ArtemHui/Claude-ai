"""Assemble the standalone Verdant Lab preview page.

Pulls the plugin's real stylesheet and the seeded catalogue so the preview
matches the WordPress build rather than being a separate mock.
"""
import json
import pathlib

ROOT = pathlib.Path("/home/user/Claude-ai/wp-dev")
PLUGIN = ROOT / "wordpress/wp-content/plugins/review-hub"
SCRATCH = pathlib.Path(
    "/tmp/claude-0/-home-user-Claude-ai/4a986bdb-6ba2-598d-a663-02a397065d5a/scratchpad"
)
OUT = ROOT / "preview"

plugin_css = (PLUGIN / "assets/css/review-hub.css").read_text()
preview_js = (OUT / "preview.js").read_text()
data = json.loads((SCRATCH / "dataset.json").read_text())

# Trim payload to what the preview renders.
keep = {
    "id", "title", "brand", "rating", "price", "price_was", "currency",
    "cbd_mg", "thc_pct", "lab_tested", "regions", "categories", "concerns",
    "discount_code", "excerpt", "cat_slugs", "concern_slugs", "region_slugs",
    "ships_to", "review_count",
}
for group in ("products", "brands"):
    data[group] = [{k: v for k, v in item.items() if k in keep} for item in data[group]]

stats = {
    "products": len(data["products"]),
    "brands": len(data["brands"]),
    "lab": sum(1 for p in data["products"] if p["lab_tested"]),
    "avg": round(sum(p["rating"] for p in data["products"]) / len(data["products"]), 1),
}

ICONS = {
    "flower": '<path d="M12 3a3 3 0 013 3c0 1-.4 1.8-1 2.4A3 3 0 0121 11a3 3 0 01-3 3c-.6 0-1.2-.2-1.7-.5.4.6.7 1.3.7 2a3 3 0 01-6 0c0-.7.3-1.4.7-2-.5.3-1.1.5-1.7.5a3 3 0 01-3-3 3 3 0 013-3h.2A3.3 3.3 0 019 6a3 3 0 013-3z"/>',
    "oil": '<path d="M12 2s5 6.4 5 10a5 5 0 01-10 0c0-3.6 5-10 5-10z"/>',
    "vape": '<path d="M4 14h11a3 3 0 100-6h-1M4 14v3a1 1 0 001 1h9a1 1 0 001-1v-3M6 11V6"/>',
    "gummy": '<path d="M7 10a5 5 0 0110 0v6a3 3 0 01-3 3h-4a3 3 0 01-3-3v-6z"/>',
    "capsul": '<rect x="3" y="9" width="18" height="6" rx="3"/><path d="M12 9v6"/>',
    "cream": '<path d="M8 8h8v12H8z"/><path d="M10 8V5h4v3"/>',
    "pet": '<circle cx="8" cy="9" r="2"/><circle cx="16" cy="9" r="2"/><path d="M12 13c-3 0-5 2-5 4a2 2 0 002 2h6a2 2 0 002-2c0-2-2-4-5-4z"/>',
    "drink": '<path d="M6 4h12l-1.5 16h-9L6 4z"/><path d="M7 10h10"/>',
    "sleep": '<path d="M20 14a8 8 0 11-9-9 6.5 6.5 0 009 9z"/>',
    "pain": '<path d="M20 9a5 5 0 00-8-3 5 5 0 00-8 3c0 6 8 10 8 10s8-4 8-10z"/>',
    "energy": '<path d="M13 2L4 14h6l-1 8 9-12h-6l1-8z"/>',
    "calm": '<path d="M4 12h4l2-5 3 10 2-5h5"/>',
}


def icon(slug):
    for needle, path in ICONS.items():
        if needle in slug:
            return (
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
                'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
                f"{path}</svg>"
            )
    return (
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" '
        'aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8"/></svg>'
    )


def options(terms, placeholder):
    out = [f'<option value="">{placeholder}</option>']
    out += [f'<option value="{t["slug"]}">{t["name"]}</option>' for t in terms]
    return "".join(out)


def finder_options(terms):
    out = []
    for t in terms:
        out.append(
            f'<button type="button" class="rh-finder__option" data-value="{t["slug"]}">'
            f'<span class="rh-finder__option-icon">{icon(t["slug"])}</span>'
            f'<span>{t["name"]}</span></button>'
        )
    out.append(
        '<button type="button" class="rh-finder__option rh-finder__option--skip" data-value="">'
        "<span>No preference</span></button>"
    )
    return "".join(out)


chips = "".join(
    f'<button type="button" class="rh-category" data-slug="{t["slug"]}" aria-pressed="false">'
    f'<span class="rh-category__icon">{icon(t["slug"])}</span>'
    f'<span class="rh-category__name">{t["name"]}</span>'
    f'<span class="rh-category__count">{t["count"]}</span></button>'
    for t in data["taxonomies"]["categories"]
)

compare_choices = "".join(
    f'<label class="rh-compare__choice"><input type="checkbox" data-compare id="cmp-{b["id"]}" '
    f'value="{b["id"]}" /><span>{b["title"]}</span></label>'
    for b in sorted(data["brands"], key=lambda b: b["title"])
)

# Category-derived swatches stand in for photography that does not exist yet.
swatch_hues = {
    "flower": 128, "oil": 45, "vape": 190, "gummy": 330, "capsules": 265,
    "cream": 20, "pet": 95, "drink": 170,
}
swatch_css = "\n".join(
    f'.rh-card__placeholder[data-swatch="{slug}"]{{'
    f"background:radial-gradient(120% 120% at 25% 20%,hsl({hue} 38% 22%),hsl({hue} 30% 12%));}}"
    for slug, hue in swatch_hues.items()
)

EXTRA_CSS = """
html, body { background: var(--rh-base); }

body {
  margin: 0;
  font-family: var(--rh-font);
  color: var(--rh-text);
  -webkit-font-smoothing: antialiased;
}

.shell {
  max-width: 1240px;
  margin: 0 auto;
  padding-inline: 16px;
  padding-block: 0 96px;
}

.ribbon {
  background: var(--rh-raised);
  border-bottom: 1px solid var(--rh-border);
  padding-block: 10px;
}

.ribbon__inner {
  max-width: 1240px;
  margin: 0 auto;
  padding-inline: 16px;
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 6px 12px;
  font-size: 0.8125rem;
  color: var(--rh-text-muted);
}

.ribbon__tag {
  font-family: var(--rh-mono);
  font-size: 0.6875rem;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--rh-on-accent);
  background: var(--rh-accent);
  padding: 3px 8px;
  border-radius: 3px;
}

.masthead {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-block: 28px 12px;
}

.wordmark {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.wordmark span { color: var(--rh-accent); }

.masthead__note {
  font-family: var(--rh-mono);
  font-size: 0.6875rem;
  color: var(--rh-text-faint);
}

.lede {
  max-width: 62ch;
  margin: 0 0 32px;
  font-size: 1.0625rem;
  line-height: 1.6;
  color: var(--rh-text-muted);
}

h2.section {
  font-size: clamp(1.25rem, 3vw, 1.75rem);
  font-weight: 600;
  letter-spacing: -0.015em;
  margin: 56px 0 8px;
  text-wrap: balance;
}

.section-note {
  margin: 0 0 20px;
  font-size: 0.875rem;
  color: var(--rh-text-faint);
  max-width: 62ch;
}

/* Photography does not exist yet; a category-derived swatch stands in. */
.rh-card__placeholder {
  display: grid;
  place-items: center;
  color: rgba(233, 241, 236, 0.16);
}

.rh-card__placeholder svg { width: 38%; height: 38%; }

.rh-card__media {
  width: 100%;
  border: 0;
  padding: 0;
  cursor: pointer;
  font: inherit;
  color: inherit;
  text-align: left;
}

.rh-card__title button {
  background: none;
  border: 0;
  padding: 0;
  font: inherit;
  color: var(--rh-text);
  text-align: left;
  cursor: pointer;
}

.rh-card__title button:hover { color: var(--rh-accent); }

.rh-card__cta { border: 0; cursor: pointer; font: inherit; }

.rh-qv__media .rh-card__placeholder { position: static; height: 100%; }

/* display:grid on .rh-qv beats the UA [hidden] rule, leaving an invisible
   backdrop over the page that swallows every click. */
.rh-qv[hidden] { display: none; }

.swiper.is-fallback {
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
}

.swiper.is-fallback .swiper-wrapper { display: flex; gap: 16px; }
.swiper.is-fallback .swiper-slide { flex: 0 0 min(280px, 78vw); scroll-snap-align: start; }

#toast {
  position: fixed;
  left: 50%;
  bottom: calc(20px + env(safe-area-inset-bottom, 0px));
  transform: translate(-50%, 20px);
  z-index: 10002;
  max-width: min(460px, calc(100vw - 32px));
  padding: 12px 18px;
  background: var(--rh-raised);
  border: 1px solid var(--rh-border-strong);
  border-radius: 8px;
  color: var(--rh-text);
  font-size: 0.875rem;
  line-height: 1.45;
  box-shadow: 0 14px 40px rgba(0, 0, 0, 0.5);
  opacity: 0;
  pointer-events: none;
  transition: opacity 200ms var(--rh-ease), transform 200ms var(--rh-ease);
}

#toast.is-visible { opacity: 1; transform: translate(-50%, 0); }

.rh-sticky-finder { bottom: calc(20px + env(safe-area-inset-bottom, 0px)); }

.vl-gate {
  position: fixed;
  inset: 0;
  z-index: 100000;
  display: grid;
  place-items: center;
  background: rgba(4, 10, 8, 0.93);
  padding: 20px;
}

.vl-gate__panel {
  max-width: 440px;
  width: 100%;
  background: var(--rh-surface);
  border: 1px solid var(--rh-border-strong);
  border-radius: 12px;
  padding: 32px 26px;
  text-align: center;
}

.vl-gate__panel h2 { margin: 0 0 10px; font-size: 1.3125rem; }
.vl-gate__panel p { margin: 0 0 22px; font-size: 0.9375rem; color: var(--rh-text-muted); line-height: 1.55; }
.vl-gate__actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

.vl-gate__btn {
  padding: 11px 20px;
  border-radius: 6px;
  border: 1px solid var(--rh-border-strong);
  background: transparent;
  color: var(--rh-text-muted);
  font: inherit;
  font-size: 0.9375rem;
  cursor: pointer;
}

.vl-gate__btn--primary {
  background: var(--rh-accent);
  border-color: var(--rh-accent);
  color: var(--rh-on-accent);
  font-weight: 600;
}

.vl-gate__btn:focus-visible { outline: 2px solid var(--rh-accent); outline-offset: 2px; }

footer.colophon {
  margin-top: 72px;
  padding-top: 28px;
  border-top: 1px solid var(--rh-border);
  font-size: 0.8125rem;
  color: var(--rh-text-faint);
  line-height: 1.6;
}

footer.colophon strong { color: var(--rh-text-muted); font-weight: 600; }

@media (max-width: 640px) {
  h2.section { margin-top: 44px; }
  .rh-qv__panel { max-height: calc(100vh - 24px); }
}
"""

HTML = f"""<title>Verdant Lab</title>
<link rel="stylesheet" href="swiper.min.css">
<style>
{plugin_css}
{EXTRA_CSS}
{swatch_css}
</style>

<div class="ribbon">
  <div class="ribbon__inner">
    <span class="ribbon__tag">Preview</span>
    <span>Interactive preview of the Review Hub WordPress build &mdash; try filtering, quick view and the product finder. Offer links and click tracking run on the WordPress install, not here.</span>
  </div>
</div>

<div class="shell">
  <header class="masthead">
    <h1 class="wordmark">Verdant<span>&thinsp;Lab</span></h1>
    <span class="masthead__note">demo catalogue &middot; fictional brands</span>
  </header>

  <p class="lede">Independent assessments of hemp and CBD products, scored on lab transparency, potency and value. Every brand below is invented for this preview.</p>

  <div class="rh-stats">
    <div class="rh-stat"><span class="rh-stat__value" data-count="{stats['products']}" data-suffix="">{stats['products']}</span><span class="rh-stat__label">Products reviewed</span></div>
    <div class="rh-stat"><span class="rh-stat__value" data-count="{stats['brands']}" data-suffix="">{stats['brands']}</span><span class="rh-stat__label">Brands assessed</span></div>
    <div class="rh-stat"><span class="rh-stat__value" data-count="{stats['lab']}" data-suffix="">{stats['lab']}</span><span class="rh-stat__label">With lab reports</span></div>
    <div class="rh-stat"><span class="rh-stat__value" data-count="{stats['avg']}" data-suffix="/5">{stats['avg']}/5</span><span class="rh-stat__label">Average score</span></div>
  </div>

  <h2 class="section">Browse by format</h2>
  <p class="section-note">Tap a format to filter the catalogue. Tap again to remove it.</p>
  <div class="rh-categories">{chips}</div>

  <div class="rh-grid-wrap">
    <div class="rh-toolbar">
      <div class="rh-toolbar__facets">
        <label class="rh-field"><span class="rh-field__label">Goal</span>
          <select id="f-concern">{options(data['taxonomies']['concerns'], 'Any goal')}</select></label>
        <label class="rh-field"><span class="rh-field__label">Ships to</span>
          <select id="f-region">{options(data['taxonomies']['regions'], 'Any region')}</select></label>
        <label class="rh-field"><span class="rh-field__label">Minimum score</span>
          <select id="f-min_rating"><option value="0">Any score</option><option value="3">3.0+</option><option value="4">4.0+</option><option value="4.5">4.5+</option></select></label>
        <label class="rh-field"><span class="rh-field__label">Sort by</span>
          <select id="f-orderby"><option value="rating">Highest score</option><option value="price_asc">Price: low to high</option><option value="price_desc">Price: high to low</option><option value="recent">Recently reviewed</option></select></label>
        <label class="rh-check"><input type="checkbox" id="f-lab" /><span>Lab tested only</span></label>
      </div>
      <div class="rh-toolbar__meta">
        <span id="count">{stats['products']} results</span>
        <button type="button" class="rh-clear" id="clear" hidden>Clear filters</button>
      </div>
    </div>

    <div class="rh-pills" id="pills" hidden></div>
    <div class="rh-grid" id="results" aria-live="polite" aria-busy="false"></div>
    <div class="rh-grid__empty" id="empty" hidden><p>No products match those filters.</p></div>
  </div>

  <section class="rh-carousel">
    <header class="rh-carousel__head">
      <h2 class="rh-carousel__title">Highest rated this month</h2>
      <div class="rh-carousel__nav">
        <button type="button" class="rh-nav-btn" id="carousel-prev" aria-label="Previous"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
        <button type="button" class="rh-nav-btn" id="carousel-next" aria-label="Next"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      </div>
    </header>
    <div class="swiper" id="carousel"></div>
  </section>

  <h2 class="section">Not sure where to start?</h2>
  <p class="section-note">Three questions, then matched recommendations scored against your answers.</p>

  <div class="rh-finder" id="finder">
    <div class="rh-finder__panel">
      <div class="rh-finder__progress"><div class="rh-finder__bar" id="finder-bar" style="width:33%"></div></div>

      <fieldset class="rh-finder__step" data-step="0" data-key="category">
        <legend class="rh-finder__question">What format are you after?</legend>
        <div class="rh-finder__options">{finder_options(data['taxonomies']['categories'])}</div>
      </fieldset>

      <fieldset class="rh-finder__step" data-step="1" data-key="concern" hidden>
        <legend class="rh-finder__question">What are you hoping it helps with?</legend>
        <div class="rh-finder__options">{finder_options(data['taxonomies']['concerns'])}</div>
        <button type="button" class="rh-finder__back" data-finder-back>Back</button>
      </fieldset>

      <fieldset class="rh-finder__step" data-step="2" data-key="region" hidden>
        <legend class="rh-finder__question">Where should it ship to?</legend>
        <div class="rh-finder__options">{finder_options(data['taxonomies']['regions'])}</div>
        <button type="button" class="rh-finder__back" data-finder-back>Back</button>
      </fieldset>

      <div class="rh-finder__results" id="finder-results" hidden>
        <h3 class="rh-finder__results-title">Your matches</h3>
        <div class="rh-finder__list" id="finder-list"></div>
        <button type="button" class="rh-finder__restart" id="finder-restart">Start over</button>
      </div>
    </div>
  </div>

  <h2 class="section">Compare brands</h2>
  <p class="section-note">Pick two or three brands to see them side by side.</p>

  <div class="rh-compare">
    <div class="rh-compare__picker">{compare_choices}</div>
    <div class="rh-compare__table" id="compare-table"></div>
  </div>

  <footer class="colophon">
    <p><strong>This is a preview, not the deliverable.</strong> The real build is a WordPress plugin plus a child theme: content types for brands and reviews, server-rendered listings, and affiliate click tracking through <code>/go/{{id}}/</code>. This page bakes the catalogue in so it runs as a single static file.</p>
    <p>Brands, prices and scores are invented for demonstration. Nothing here describes a real company or product.</p>
  </footer>
</div>

<button type="button" class="rh-sticky-finder" id="sticky">
  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  <span>Find your product</span>
</button>

<div class="rh-qv" id="qv" hidden>
  <div class="rh-qv__backdrop" data-qv-close></div>
  <div class="rh-qv__panel" role="dialog" aria-modal="true" aria-labelledby="qv-title">
    <button type="button" class="rh-qv__close" id="qv-close" data-qv-close aria-label="Close">&times;</button>
    <div class="rh-qv__content" id="qv-content"></div>
  </div>
</div>

<div class="vl-gate" id="gate" hidden>
  <div class="vl-gate__panel" role="dialog" aria-modal="true" aria-labelledby="gate-title">
    <h2 id="gate-title">Are you 18 or over?</h2>
    <p>This site reviews hemp and CBD products. You must confirm your age to continue. Nothing is sold here.</p>
    <div class="vl-gate__actions">
      <button type="button" class="vl-gate__btn vl-gate__btn--primary" id="gate-yes">Yes, I am 18 or over</button>
      <button type="button" class="vl-gate__btn" id="gate-no">No</button>
    </div>
  </div>
</div>

<div id="toast" role="status" aria-live="polite"></div>

<script src="swiper.min.js"></script>
<script>window.VERDANT_DATA = {json.dumps(data, separators=(',', ':'))};</script>
<script>
{preview_js}
</script>
"""

(OUT / "index.html").write_text(HTML)
print("wrote", OUT / "index.html", len(HTML), "bytes")
