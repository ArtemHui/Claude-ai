# Foothold — scam victim support landing page

A single static page for a service helping people who have already been
scammed. No build step, no dependencies, no server: one `index.html`.

Published: https://claude.ai/artifact/FSbnyrGEeKRPzGBeYmNQx6

## The name

*Foothold* — regaining stable ground after being knocked off it. It deliberately
promises **footing, not refunds**. "Recovery" language is what the fraudsters who
target this same audience use, so the brand avoids it.

## Who lands here

Someone who was scammed, often within hours, frequently at night, usually
ashamed, and quite possibly being targeted a second time already. Three
consequences shape the whole page:

1. **Immediate use comes before persuasion.** The triage selector sits high on the
   page, not below a marketing narrative. Someone mid-crisis gets the steps
   without reading anything first.
2. **Shame reduction is a design goal, not a nicety.** Most victims never report,
   and embarrassment is the reason. The page says plainly that it is not their
   fault and explains the mechanism, because that is what makes people act.
3. **The page must prove it is not a second scam.** Hence the recovery-fraud
   warning, the explicit "what we cannot do" section, and the standing promise in
   the footer about what the service will never do.

## Why the recovery-fraud warning is prominent

Scam-victim assistance is one of the most fraud-infested categories online.
Recovery scams specifically target people who have just lost money, promising
retrieval for an upfront fee — and the operators often work from lists of known
victims. Any legitimate service in this space is competing against that
reputation. Warning about it openly is both the ethical choice and the strongest
available trust signal.

That is also why the page makes **no promise of fund recovery anywhere**, and
says so directly.

## Content accuracy

The triage advice is genuine and specific per route: authorised push payment
terminology for bank transfers, chargeback and purchase protection for cards,
issuer contact for unspent gift card balances, exchange notification and
transaction hashes for crypto, email-password-first ordering after remote access,
credit freezes after data loss, evidence preservation before confrontation in
romance fraud, and regulator reporting for investment fraud.

Nothing invented: no testimonials, case counts, accreditations, partner logos or
phone numbers.

## Placeholders to replace

| What | Where |
|---|---|
| Contact routes, hours, fee model, registration | the `.placeholder` block, id `#contact` |
| Brand name and mark | `.brand` in header and footer, plus `<title>` |
| Fraud reporting body | region-neutral wording throughout ("your national fraud reporting service") |

State the fee model explicitly, even if it is only "the first conversation is
free". Visitors arriving here have just lost money and are primed to suspect a
second trap; ambiguity about cost loses them faster than a high price would.

## Interaction

**Triage selector** — eight routes (bank transfer, card, gift cards, crypto,
remote access, personal data, someone trusted, investment). Each reveals a
time-sensitivity banner, five ordered steps, and a closing note. Single-select,
toggles closed, `aria-pressed` and `aria-live` for assistive technology.

No libraries. Light and dark themes, reduced-motion respected.

## Hosting

Any static host: Netlify, Vercel, Cloudflare Pages, GitHub Pages, or plain
nginx. The only external request is the Google Fonts stylesheet; self-host
Newsreader and Source Sans 3 if you want zero third-party requests.

## Legal

The footer carries a general-information disclaimer and directs people to their
bank and national fraud reporting service as free routes that do not require the
service. Have a solicitor review the fee model and any claim of outcome before
launch, particularly if operating in a regulated market.
