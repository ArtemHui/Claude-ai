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

---

# Case log (CRM)

`crm.html` — a private case log for the practice. Published separately:
https://claude.ai/artifact/TxeSarVMeUWjPfkJ6RdADA

Add people you are helping, track status, keep dated notes, and export to CSV.
Data persists server-side via the artifact `db` capability and syncs live across
your devices.

## Access

Declaring `db` makes the artifact organization-internal — it cannot be shared by
public link. Access rules are tightened beyond the default:

```
rules: [{ path: "", read: "admin", write: "admin" }]
```

Only the owner and people given **Can edit** can read the case data at all. A
viewer with "Can interact" sees an empty list rather than client records. This is
least privilege on purpose: the default would have let any signed-in viewer of
the artifact read every case.

## Data model

One document per case in the `clients` collection:

| Field | Notes |
|---|---|
| `name` | required |
| `contact` | one route, phone or email |
| `scamType` | matches the landing page's eight routes, plus `other` |
| `status` | `new` → `contacted` → `active` → `submitted` → `closed` |
| `amount`, `occurredOn`, `summary` | all optional |
| `notes[]` | `{at, text}`, appended, newest shown first |
| `createdAt`, `updatedAt` | ISO timestamps |

Notes live inside the case document rather than in their own collection, which
keeps the document count low against the 5,000-document cap.

## Deliberately not stored

There are no fields for card numbers, bank login details, passwords, one-time
codes or identity document numbers, and the page says so on screen. You do not
need them to act on a case, and a list of scam victims with financial identifiers
attached is the single most valuable thing a fraudster could steal from this
business.

## Before real client data goes in

This is a working tool, but the data is sensitive personal information about
crime victims, and in some jurisdictions details of criminal offences carry extra
restrictions.

- Decide a retention period and delete closed cases when it passes.
- Confirm you are comfortable with the data residing in the artifact store, and
  check whether your regulator or insurer expects a data processing agreement.
- Export to CSV regularly — it is your backup and your exit route.
- Keep sharing restricted to people who genuinely need the records.

If any of that does not hold, an established CRM with a data processing
agreement, or a self-hosted database you control, is the more appropriate home.

## Access log

A second tab in the CRM records who opens the tool. Each visit is written to
`access/<userId>` — one document per person, holding their recent sessions.

| Captured | Source |
|---|---|
| Who | `user` capability, `profile` scope. Only the opaque id is stored; the display name is resolved at render time, never written to the store. |
| When | Session start, last activity, and a view count |
| Where (approximate) | IANA timezone reported by the browser, e.g. `Europe/Warsaw` |
| Device | Browser family, OS and screen size, derived from the user-agent |

A reload within 30 minutes on the same device and timezone extends the current
session rather than appending a new one, so a refresh does not read as a fresh
sign-in. Sessions are pruned to the most recent 40 per person, keeping each
document well inside the 256 KiB body limit.

### What it deliberately cannot do

- **No IP address, no city.** A published page never sees the visitor's IP, and
  the artifact sandbox blocks network calls to any geolocation service. Timezone
  is the only location signal obtainable, and it is coarse and user-changeable.
  Browser GPS (`navigator.geolocation`) would give real coordinates but prompts
  for permission on every visit and is far more invasive than this needs.
- **No email addresses.** This workspace does not release the `email` scope to
  pages, so people are identified by display name only.
- **Not a security audit trail.** It records what the page observes. Treat it as
  an operational record of who used the tool; the real access boundary is the
  Share menu and the `admin` read/write rule.

The log is itself personal data about your staff — include it in whatever
retention policy you set for the case records.
