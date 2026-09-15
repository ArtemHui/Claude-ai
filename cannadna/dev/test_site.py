"""Drive the Review Hub demo site and verify each interactive component."""
from playwright.sync_api import sync_playwright

BASE = "http://localhost:8080/"
SHOT = "/tmp/claude-0/-home-user-Claude-ai/4a986bdb-6ba2-598d-a663-02a397065d5a/scratchpad/shots"

results = []


def check(name, condition, detail=""):
    results.append((name, condition, detail))
    print(f"{'PASS' if condition else 'FAIL'}  {name}" + (f"  — {detail}" if detail else ""))


with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path="/opt/pw-browsers/chromium")
    page = browser.new_page(viewport={"width": 1440, "height": 1000})

    errors = []
    page.on("pageerror", lambda e: errors.append(str(e)))
    page.on("console", lambda m: errors.append(m.text) if m.type == "error" else None)

    page.goto(BASE)
    page.wait_for_load_state("networkidle")

    # --- age gate (compliance) ---------------------------------------------
    gate = page.locator("#vl-gate")
    check("age gate blocks content on first visit", gate.is_visible())
    check("age gate traps focus on confirm button",
          page.evaluate("document.activeElement && document.activeElement.id") == "vl-gate-yes")
    page.locator("#vl-gate-yes").click()
    page.wait_for_timeout(400)
    check("age gate dismisses after confirmation", gate.count() == 0)

    page.reload()
    page.wait_for_load_state("networkidle")
    check("age gate stays dismissed on repeat visit", page.locator("#vl-gate").count() == 0)

    # --- stats -------------------------------------------------------------
    stats = page.locator(".rh-stat__value").all_inner_texts()
    check("stat counters show real values (not 0)",
          any(s.strip() not in ("0", "0/5", "") for s in stats), f"values={stats}")

    # --- baseline grid -----------------------------------------------------
    baseline = page.locator("[data-rh-results] .rh-card").count()
    check("grid renders cards server-side", baseline > 0, f"{baseline} cards")

    # --- category chip filtering ------------------------------------------
    page.locator(".rh-category", has_text="Oils").first.click()
    page.wait_for_timeout(900)
    after_chip = page.locator("[data-rh-results] .rh-card").count()
    label = page.locator("[data-rh-count-label]").inner_text()
    check("category chip filters the grid", after_chip != baseline and after_chip > 0,
          f"{baseline} -> {after_chip} ({label})")

    pressed = page.locator('.rh-category[aria-pressed="true"]').count()
    check("selected chip reflects pressed state", pressed == 1, f"{pressed} pressed")

    # --- clear filters -----------------------------------------------------
    page.locator("[data-rh-clear]").click()
    page.wait_for_timeout(900)
    cleared = page.locator("[data-rh-results] .rh-card").count()
    check("clear filters restores full grid", cleared == baseline, f"{cleared} cards")

    # --- dropdown facet ----------------------------------------------------
    page.locator('[data-rh-filter="min_rating"]').select_option("4.5")
    page.wait_for_timeout(900)
    high = page.locator("[data-rh-results] .rh-card").count()
    scores = [float(s) for s in page.locator(".rh-card__score").all_inner_texts()[:high] if s]
    check("min-rating facet filters correctly",
          high > 0 and all(s >= 4.5 for s in scores), f"{high} cards, scores={scores}")

    # --- lab-tested checkbox ----------------------------------------------
    page.locator('[data-rh-filter="min_rating"]').select_option("0")
    page.wait_for_timeout(700)
    page.locator('[data-rh-filter="lab_tested"]').check()
    page.wait_for_timeout(900)
    lab = page.locator("[data-rh-results] .rh-card").count()
    verified = page.locator("[data-rh-results] .rh-card__verified").count()
    check("lab-tested filter returns only verified items",
          lab > 0 and verified == lab, f"{lab} cards, {verified} verified")
    page.locator('[data-rh-filter="lab_tested"]').uncheck()
    page.wait_for_timeout(700)

    # --- parity between server-rendered and JS-rendered cards --------------
    def card_signature(pg):
        """Structural signature of the first card: tag/class skeleton + labels."""
        return pg.evaluate("""() => {
            const card = document.querySelector('[data-rh-results] .rh-card');
            if (!card) return null;
            const walk = (el) => [...el.children].map(c =>
                c.tagName + '.' + (c.className || '') +
                (c.children.length ? '(' + walk(c).join(',') + ')' : '')
            );
            const specs = [...card.querySelectorAll('.rh-card__spec dt, .rh-card__spec dd')]
                .map(n => n.textContent.trim());
            return JSON.stringify({ skeleton: walk(card), specs });
        }""")

    page.goto(BASE)
    page.wait_for_load_state("networkidle")
    server_sig = card_signature(page)

    # Force a JS re-render that returns the same first card.
    page.locator('[data-rh-filter="orderby"]').select_option("rating")
    page.wait_for_timeout(1000)
    js_sig = card_signature(page)

    check("JS-rendered card matches server-rendered markup",
          server_sig == js_sig,
          "" if server_sig == js_sig else f"server={server_sig}\n        js={js_sig}")

    # --- carousel ----------------------------------------------------------
    check("swiper initialised", page.locator(".swiper-initialized").count() > 0)
    first_before = page.locator(".rh-carousel .swiper-slide").first.bounding_box()
    page.locator("[data-rh-next]").first.click()
    page.wait_for_timeout(800)
    first_after = page.locator(".rh-carousel .swiper-slide").first.bounding_box()
    check("carousel next button advances slides",
          first_before and first_after and first_before["x"] != first_after["x"],
          f"x {first_before['x']:.0f} -> {first_after['x']:.0f}")

    # --- finder wizard -----------------------------------------------------
    finder = page.locator("[data-rh-finder]")
    finder.locator('[data-rh-step="0"] .rh-finder__option', has_text="Oils").first.click()
    page.wait_for_timeout(400)
    step1_visible = finder.locator('[data-rh-step="1"]').is_visible()
    check("finder advances to step 2", step1_visible)

    finder.locator('[data-rh-step="1"] .rh-finder__option', has_text="Sleep").first.click()
    page.wait_for_timeout(400)
    check("finder advances to step 3", finder.locator('[data-rh-step="2"]').is_visible())

    finder.locator('[data-rh-step="2"] .rh-finder__option', has_text="EU only").first.click()
    page.wait_for_timeout(1500)

    matches = finder.locator("[data-rh-finder-list] .rh-card").count()
    check("finder returns recommendations", matches > 0, f"{matches} matches")
    check("finder shows match scoring",
          finder.locator(".rh-finder__match").count() > 0,
          finder.locator(".rh-finder__match").first.inner_text() if finder.locator(".rh-finder__match").count() else "")

    # progress bar advanced
    width = finder.locator("[data-rh-finder-bar]").get_attribute("style")
    check("progress bar advanced to final state", "100%" in (width or ""), width)

    finder.locator("[data-rh-finder-restart]").click()
    page.wait_for_timeout(400)
    check("finder restart returns to step 1", finder.locator('[data-rh-step="0"]').is_visible())

    # --- comparison --------------------------------------------------------
    boxes = page.locator("[data-rh-compare-id]")
    boxes.nth(0).check()
    boxes.nth(1).check()
    page.wait_for_timeout(1200)
    cols = page.locator(".rh-compare__table thead th").count()
    rows = page.locator(".rh-compare__table tbody tr").count()
    check("comparison table builds for 2 brands", cols == 3 and rows >= 4,
          f"{cols} cols, {rows} rows")

    boxes.nth(2).check()
    page.wait_for_timeout(1200)
    cols3 = page.locator(".rh-compare__table thead th").count()
    check("comparison supports 3 brands", cols3 == 4, f"{cols3} cols")

    boxes.nth(3).click()
    page.wait_for_timeout(600)
    check("comparison caps at 3 brands", not boxes.nth(3).is_checked())

    # --- quick view modal --------------------------------------------------
    page.goto(BASE)
    page.wait_for_load_state("networkidle")
    page.wait_for_timeout(600)

    page.locator("[data-rh-quickview]").first.click()
    page.wait_for_timeout(1400)
    qv = page.locator(".rh-qv__panel")
    check("quick view opens without page load", qv.is_visible())
    check("quick view loads item detail",
          page.locator(".rh-qv__title").count() > 0,
          page.locator(".rh-qv__title").inner_text() if page.locator(".rh-qv__title").count() else "")
    # Design tokens must resolve outside the shortcode wrappers: the modal is
    # appended to <body>, and scoped tokens left it with no background.
    panel_bg = qv.evaluate("el => getComputedStyle(el).backgroundColor")
    check("quick view panel has a solid background",
          panel_bg not in ("rgba(0, 0, 0, 0)", "transparent"), panel_bg)
    cta_bg = page.locator(".rh-qv__actions .rh-card__cta").first.evaluate(
        "el => getComputedStyle(el).backgroundColor")
    check("quick view CTA keeps its accent fill",
          cta_bg not in ("rgba(0, 0, 0, 0)", "transparent"), cta_bg)

    meter = page.locator(".rh-qv__meter-fill")
    check("score meter animates to a width",
          meter.count() > 0 and meter.evaluate("el => parseFloat(getComputedStyle(el).width)") > 0)
    check("quick view CTA is tracked",
          "/go/" in (page.locator(".rh-qv__actions .rh-card__cta").first.get_attribute("href") or ""))
    page.keyboard.press("Escape")
    page.wait_for_timeout(400)
    check("quick view closes on Escape", page.locator(".rh-qv__panel").count() == 0)

    # --- motion system ------------------------------------------------------
    def reveal_state(pg):
        return pg.evaluate("""() => {
            const cards = [...document.querySelectorAll('.rh-card')];
            return {
                total: cards.length,
                hidden: cards.filter(c => parseFloat(getComputedStyle(c).opacity) < 1).length,
                marked: cards.filter(c => c.classList.contains('rh-reveal')).length
            };
        }""")

    page.goto(BASE)
    page.wait_for_load_state("networkidle")
    page.wait_for_timeout(300)
    early = reveal_state(page)
    check("reveal animation is active (below-fold cards start hidden)",
          early["marked"] > 0 and early["hidden"] > 0, str(early))

    # Failsafe must reveal everything even though nothing was scrolled.
    page.wait_for_timeout(3200)
    late = reveal_state(page)
    check("failsafe reveals all cards without any scrolling",
          late["hidden"] == 0, str(late))

    # Content must be visible with JS disabled entirely.
    nojs = browser.new_context(java_script_enabled=False, viewport={"width": 1440, "height": 1000})
    nojs_page = nojs.new_page()
    nojs_page.goto(BASE)
    nojs_page.wait_for_timeout(600)
    nojs_hidden = nojs_page.evaluate("""() => [...document.querySelectorAll('.rh-card')]
        .filter(c => parseFloat(getComputedStyle(c).opacity) < 1).length""")
    nojs_cards = nojs_page.locator(".rh-card").count()
    check("cards render and stay visible with JS disabled",
          nojs_cards > 0 and nojs_hidden == 0, f"{nojs_cards} cards, {nojs_hidden} hidden")
    nojs.close()

    # --- filter pills + URL state ------------------------------------------
    page.locator(".rh-category", has_text="Oils").first.click()
    page.wait_for_timeout(1200)
    check("active filter shown as a pill", page.locator(".rh-pill").count() > 0,
          page.locator(".rh-pill").first.inner_text() if page.locator(".rh-pill").count() else "")
    check("filter state written to URL", "category=oil" in page.url, page.url)

    # Default select options ("Any score" = value "0") must not become pills.
    pill_labels = page.locator(".rh-pill").all_inner_texts()
    check("default select options do not become pills",
          not any("any" in p.lower() for p in pill_labels),
          str([p.split("\n")[0] for p in pill_labels]))

    filtered_count = page.locator("[data-rh-results] .rh-card").count()
    page.go_back()
    page.wait_for_timeout(1200)
    check("back button restores unfiltered view",
          page.locator("[data-rh-results] .rh-card").count() != filtered_count
          and "category=" not in page.url, page.url)

    # Shareable URL: a fresh load with params should come back filtered.
    shared = browser.new_page(viewport={"width": 1440, "height": 1000})
    shared.goto(BASE + "?category=oil")
    shared.wait_for_load_state("networkidle")
    if shared.locator("#vl-gate-yes").count():
        shared.locator("#vl-gate-yes").click()
    shared.wait_for_timeout(1500)
    check("shared filtered URL restores the filter",
          shared.locator(".rh-pill").count() > 0
          and shared.locator('.rh-category[aria-pressed="true"]').count() > 0,
          f'{shared.locator(".rh-pill").count()} pills')
    shared.close()

    # removing the pill clears the filter
    page.goto(BASE + "?category=oil")
    page.wait_for_load_state("networkidle")
    page.wait_for_timeout(1500)
    page.locator(".rh-pill").first.click()
    page.wait_for_timeout(1200)
    check("removing a pill clears that filter", page.locator(".rh-pill").count() == 0)

    # --- sticky finder CTA --------------------------------------------------
    page.evaluate("window.scrollTo(0, 400)")
    page.wait_for_timeout(900)
    sticky = page.locator("[data-rh-sticky-finder]")
    check("sticky finder CTA exists", sticky.count() > 0)
    check("sticky finder becomes visible when finder is off-screen",
          sticky.evaluate("el => el.classList.contains('is-visible')"))
    sticky.click()
    page.wait_for_timeout(1400)
    check("sticky CTA scrolls the finder into view",
          page.locator("[data-rh-finder]").first.evaluate(
              "el => { const r = el.getBoundingClientRect();"
              "return r.top < window.innerHeight && r.bottom > 0; }"))

    # --- affiliate click tracking -----------------------------------------
    offer = page.locator(".rh-card__cta").first
    href = offer.get_attribute("href")
    rel = offer.get_attribute("rel")
    check("offer CTA routes through tracker", "/go/" in (href or ""), href)
    check("offer CTA has nofollow sponsored rel",
          "nofollow" in (rel or "") and "sponsored" in (rel or ""), rel)

    resp = page.request.get(href, max_redirects=0)
    check("tracker redirects to partner URL",
          resp.status in (301, 302) and "example.com" in (resp.headers.get("location") or ""),
          f"{resp.status} -> {resp.headers.get('location')}")

    # open redirect must not be possible
    bad = page.request.get(BASE + "go/999999/", max_redirects=0)
    check("unknown offer id does not redirect offsite",
          bad.status in (301, 302) and "localhost" in (bad.headers.get("location") or ""),
          f"{bad.status} -> {bad.headers.get('location')}")

    page.screenshot(path=f"{SHOT}/desktop.png", full_page=True)

    # --- iPad ---------------------------------------------------------------
    ipad = browser.new_page(viewport={"width": 834, "height": 1112}, is_mobile=True,
                            has_touch=True, device_scale_factor=2)
    ipad.goto(BASE)
    ipad.wait_for_load_state("networkidle")
    if ipad.locator("#vl-gate-yes").count():
        ipad.locator("#vl-gate-yes").click()
    # Wait past the reveal failsafe so the settled state is measured.
    ipad.wait_for_timeout(3200)
    ipad.wait_for_timeout(800)
    scroll_w = ipad.evaluate("document.documentElement.scrollWidth")
    client_w = ipad.evaluate("document.documentElement.clientWidth")
    check("no horizontal overflow on iPad", scroll_w <= client_w + 1, f"{scroll_w} vs {client_w}")

    # Product content must be visible without scrolling it into view: a card
    # left at opacity 0 by a scroll-reveal is invisible to crawlers and to
    # anyone who jumps down the page.
    opacities = ipad.evaluate(
        "() => [...document.querySelectorAll('.rh-card')].map(c => getComputedStyle(c).opacity)"
    )
    hidden = [o for o in opacities if float(o) < 1]
    check("no product card is left invisible on iPad",
          not hidden, f"{len(hidden)}/{len(opacities)} cards at opacity<1")

    cols = ipad.evaluate(
        "getComputedStyle(document.querySelector('[data-rh-results]')).gridTemplateColumns"
    )
    check("iPad grid uses 3 columns", len(cols.split()) >= 3, cols)

    # A CTA wider than its card gets clipped by the card's overflow:hidden.
    clipped = ipad.evaluate("""() => [...document.querySelectorAll('.rh-card')]
        .filter(c => c.scrollWidth > c.clientWidth + 1).length""")
    check("no card content is clipped on iPad", clipped == 0, f"{clipped} clipped")
    ipad.screenshot(path=f"{SHOT}/ipad.png", full_page=True)

    # --- phone --------------------------------------------------------------
    phone = browser.new_page(viewport={"width": 390, "height": 844}, is_mobile=True, has_touch=True)
    phone.goto(BASE)
    phone.wait_for_load_state("networkidle")
    if phone.locator("#vl-gate-yes").count():
        phone.locator("#vl-gate-yes").click()
    phone.wait_for_timeout(3200)
    phone.wait_for_timeout(800)
    sw = phone.evaluate("document.documentElement.scrollWidth")
    cw = phone.evaluate("document.documentElement.clientWidth")
    check("no horizontal overflow on phone", sw <= cw + 1, f"{sw} vs {cw}")
    phone.screenshot(path=f"{SHOT}/phone.png", full_page=True)

    check("no uncaught JS errors", len(errors) == 0, "; ".join(errors[:3]))

    browser.close()

passed = sum(1 for _, ok, _ in results if ok)
print(f"\n{passed}/{len(results)} checks passed")
if passed != len(results):
    print("FAILURES: " + ", ".join(n for n, ok, _ in results if not ok))
