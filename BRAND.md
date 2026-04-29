# JBKlutse — Brand Reference

The single source of truth for colors, type, voice, and visuals across
**jbklutse.com** and every JBKlutse-related automation. When in doubt, this
file wins. Update this file before changing brand tokens in any individual
workflow — never the other way around.

> **Scope:** This guide governs JBKlutse Blog (jbklutse.com) and any
> automation that produces content on its behalf. It does NOT apply to
> JBKlutse Foundation (different brand, different audience) or to
> PrayerPrompt (separate brand entirely).

---

## 1. Brand essence

| | |
|---|---|
| **Name** | JBKlutse |
| **Tagline** | Consumer Tech Simplified |
| **Domain** | jbklutse.com |
| **Author** | John-Bunya Klutse ("JB") |
| **Audience** | Ghanaian and African tech-curious readers, builders, decision-makers |
| **What we are** | The friend in your phone who actually understands tech AND understands what life in Accra, Kumasi, Tamale is like |
| **What we are not** | A faceless tech blog. Silicon Valley-aping. Press-release re-skin. |

Personality in three words: **smart, Ghanaian, opinionated**.

---

## 2. Colors

| Token | Hex | RGB | Usage |
|---|---|---|---|
| Teal (primary) | `#008080` | `0, 128, 128` | Accents, CTAs, links, brand stripes, category pills |
| Dark grey | `#141818` | `20, 24, 24` | Backgrounds, body text on light surfaces |
| Black | `#000000` | `0, 0, 0` | High-contrast type, fallback for B&W docs |
| White | `#FFFFFF` | `255, 255, 255` | Type on dark surfaces, card foregrounds |

**Rules**
- Teal is an **accent**, never a flood-fill primary surface (don't make whole backgrounds teal — it gets aggressive at scale). Teal works best at < 25% of any composition's surface area.
- For social cards and dark UI, use Dark Grey (`#141818`) as the primary surface, never pure black. Pure black reads as cheap on phone screens.
- Light mode: White surface + Dark Grey type + Teal accents.
- Never introduce a new accent color without updating this file first. If a third-party design (sponsor, partner) needs to coexist, place their color in a clearly bordered region — don't blend.

**Gradient (for image overlays)**
```css
linear-gradient(180deg,
  rgba(20, 24, 24, 0.35) 0%,
  rgba(20, 24, 24, 0.55) 38%,
  rgba(20, 24, 24, 0.92) 70%,
  rgba(20, 24, 24, 0.98) 100%)
```
Used to darken the lower half of any photographic background so white headlines stay legible.

---

## 3. Typography

We use Google Fonts (free, performant, render reliably in HCTI and on the web).

| Role | Font | Weight | Source |
|---|---|---|---|
| Display / Headlines | **Archivo Black** | 900 | https://fonts.google.com/specimen/Archivo+Black |
| Brand wordmark, labels, UI | **Space Grotesk** | 500, 700 | https://fonts.google.com/specimen/Space+Grotesk |
| Body (web) | Inter | 400, 500, 600 | https://fonts.google.com/specimen/Inter |

**Why these:**
Archivo Black is heavy and distinctive — stops scrolls in social feeds. Space Grotesk has personality without being trendy. Inter is the safest body workhorse on the web. Together they read modern-tech without looking like every other Y Combinator blog.

**Fallbacks (always include in CSS):**
```css
font-family: 'Archivo Black', 'Helvetica Neue', Helvetica, Arial, sans-serif;
font-family: 'Space Grotesk', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
```

**Sizes (social card 1080×1350):**
- Title: 84px (auto-fits down to 52px for long headlines)
- Brand wordmark: 36px / `letter-spacing: 1px`
- Category pill: 18px / `letter-spacing: 3px` / uppercase
- Kicker: 16px / `letter-spacing: 6px` / uppercase
- Footer text: 26-30px

> **Note on Mont:** The original Foundation brand guide specified Mont Bold + Helvetica. Mont is paid/licensed and doesn't render reliably in HCTI. We've moved to Archivo Black + Space Grotesk for digital + automation use. Mont can still be used in print collateral if you have the license.

---

## 4. Logo

We use the **JBK mark** without the "Foundation" wordmark for jbklutse.com brand.

**Approved variations (in order of preference):**
1. JBK mark in **teal** on **dark grey** (primary)
2. JBK mark in **teal** on **white** (light surfaces)
3. JBK mark in **white** on **dark grey** (when teal would clash with surrounding image)
4. JBK mark in **black** on **white** (B&W documents only)

**Not allowed:**
- JBK on a teal background (insufficient contrast)
- JBK inside a circle, badge, or container (unless that container is the spec)
- JBK stretched, rotated, or recolored to anything outside the four palette tokens
- JBK on a busy photographic background without a darkened backdrop region behind it

### JBK mark — canonical asset

| | |
|---|---|
| **Format** | Transparent PNG (preferred) or SVG |
| **Color in asset** | Teal `#008080` only |
| **Local archive** | `C:\Sites\jbklutse-blog\assets\branding\jbk-mark-teal.png` (save the asset here for repo-tracked archive) |
| **Public URL (canonical)** | `https://www.jbklutse.com/wp-content/uploads/jbk-mark-teal.png` *(upload to WP media library — pick this exact filename for predictable URLs)* |
| **Sizing in social cards** | Render at `height: 64px; width: auto;` for 1080×1350 cards. The asset's aspect ratio (~13:8) means it lands at roughly 104×64 — visually balanced with the category pill on the right. |

### How to deploy the logo to automations

1. **Upload** the PNG to WP media library at `/wp-content/uploads/`. Confirm the resulting URL by hovering the file in Media → "Edit" → copy the file URL.
2. **Set** the URL in any automation's HTML-builder node. In the social-feed workflow, that's `wf1-detect-generate-approval.json` → `Code — Build HTML` node → top constant:
   ```js
   const JBK_LOGO_URL = 'https://www.jbklutse.com/wp-content/uploads/jbk-mark-teal.png';
   ```
3. **Re-push** the workflow (`bash scripts/push.sh ...`).
4. **Test** by manually triggering the webhook once — the next approval email's IG card preview will show the real mark instead of the wordmark.

### Wordmark substitute (used when JBK_LOGO_URL is empty)

Until the PNG is hosted, automations fall back to a typographic wordmark — `JBKLUTSE.` in Space Grotesk 700, white on dark, with the dot in teal:

```html
<div class="brand-wordmark">JBKLUTSE<span style="color:#008080">.</span></div>
```

This is the **default** in the social-feed automation, so the system works end-to-end before the logo is hosted. Once you set `JBK_LOGO_URL`, the wordmark disappears and the real mark takes over with no other changes.

---

## 5. Voice & tone

JBKlutse content is written in the voice of **JB** — a real person, not a brand committee. This is the same voice used across blog posts, social captions, newsletters, and any automated content speaking on behalf of jbklutse.com.

### Persona

- Ghanaian. Talk like a Ghanaian who happens to know tech deeply.
- Funny but not clownish. Smart but not show-offy. Warm but with edge.
- You have OPINIONS. You take sides. You roast bad products. You stan good ones.
- First-person "I" / "me" — never "we at JBKlutse." This is YOU on the internet.
- You assume your reader is intelligent but busy — explain like to a smart cousin who isn't a developer.

### Mandatory rules

1. **Plain English first.** No jargon walls. If a tech term needs explaining, explain it in one sentence inline.
2. **Ghanaian references when they fit, never as decoration.**
   - Currency in Cedis (GHS) when an article mentions a USD price — convert with the rate the article gives, otherwise note "around GHS X at today's rates" only if you're confident.
   - MoMo, MTN, Vodafone/Telecel, AirtelTigo, Hubtel, Expresspay — invoke when relevant.
   - Trotro, jollof, dumsor, ECG, Accra traffic — analogies only when they genuinely land. Forced is worse than none.
   - Pidgin: light, sparing — `chale`, `ein`, `oo`, `abi`. Max once per post. Never on X.
3. **Use contractions.** Write how you talk: "I'm not buying it." / "Here's the thing." / "So…"
4. **Opinion + evidence.** Don't just summarize — REACT. "This is dumb because…" / "Finally…" / "Watch how this plays out in Ghana."
5. **Conversation hooks on social.** Every social caption ends with a direct question, hot take, A/B poll, or tag-a-friend ask. "Let me know your thoughts!" is BANNED.
6. **No emojis on X.** Up to 2 on FB/IG/Threads, used like punctuation, never as decoration.
7. **Never invent facts.** If the source doesn't say it, you don't say it.

### Banned vocabulary

These read as marketing-speak and undermine the JB voice. Don't use any of:

| Banned | Use instead |
|---|---|
| leverage | use |
| streamline | simplify, speed up |
| empower | help, let, enable |
| unlock | open up, get access to, make possible |
| game-changer | (just describe what it actually does) |
| revolutionize / revolutionary | (describe the actual change) |
| mind-blowing | (describe what's actually surprising) |
| seamless | (describe the friction it removes) |
| disrupt / disruptive | (describe what it replaces) |
| cutting-edge | new, the latest |
| best-in-class | (just describe its merit) |

### Voice examples — good vs bad

**Bad (corporate, no personality):**
> We're excited to share that Samsung has announced new RAM technology that will revolutionize the budget smartphone market. This game-changer will empower consumers to access cutting-edge devices.

**Good (JB voice):**
> Samsung just stopped making the cheap RAM that goes in budget Android phones. Translation: your next Tecno or Infinix is about to cost more. By how much? Probably 8-12% by Q3 if the supply chain holds. Chale, this is bad news for anyone shopping under GHS 2,500.
>
> Here's the kicker — Samsung isn't doing this because they ran out of factories. They're doing it because AI servers pay better.
>
> So who wins? You buying premium or staying on your current phone?

---

## 6. Image specs

| Use case | Dimensions | Format | Where it lives |
|---|---|---|---|
| Article featured image | 1200 × 630 | JPG | WP media library (Gemini-generated) |
| OG share image | 1200 × 630 | JPG | Same as featured (Yoast/Rank Math auto-uses) |
| Social card — IG portrait | 1080 × 1350 | JPG | HCTI-rendered, used by social-feed automation |
| Social card — FB/X share | 1200 × 675 | JPG | HCTI-rendered (future automation) |
| Newsletter header | 1200 × 400 | JPG/PNG | Email-rendered |
| Favicon | 512 × 512 | PNG | Already at jbklutse.com favicon |

**Composition rule for any image with a headline overlay:**
Keep the lower 40% of the frame visually quiet (no faces, no busy text, no high-frequency texture) so the gradient + title can sit on top legibly. The Gemini prompt suffix below enforces this.

---

## 7. Gemini prompt style suffix

When Gemini generates featured images for articles, append this to whatever topical prompt your article-gen pipeline builds. It nudges output toward brand-aligned visuals:

```
Style: editorial photography or clean digital illustration. Color palette
anchored on teal (#008080), deep grey (#141818), and white — avoid heavy
reds, oranges, or warm pastels. Composition leaves the lower 40% of the
frame visually quiet (no faces, no dense text, no high-frequency detail
in that region) so a dark gradient and headline overlay can be applied
later. 16:9 aspect ratio, high contrast, minimal in-image text. Modern,
confident, slightly editorial mood — not stock-photo-cheery, not
sci-fi-glossy. Suitable for a Ghanaian tech publication.
```

Add this to your article-gen automation in the node that builds the Gemini prompt.

---

## 8. Reusable CSS tokens

For any HCTI template or web stylesheet, paste these tokens at the top:

```css
:root {
  /* Colors */
  --jbk-teal:        #008080;
  --jbk-dark:        #141818;
  --jbk-black:       #000000;
  --jbk-white:       #ffffff;
  --jbk-mute:        rgba(255, 255, 255, 0.55);

  /* Gradient — dark overlay on photo backgrounds */
  --jbk-gradient: linear-gradient(180deg,
    rgba(20, 24, 24, 0.35) 0%,
    rgba(20, 24, 24, 0.55) 38%,
    rgba(20, 24, 24, 0.92) 70%,
    rgba(20, 24, 24, 0.98) 100%);

  /* Type stacks */
  --jbk-display: 'Archivo Black', 'Helvetica Neue', Helvetica, Arial, sans-serif;
  --jbk-ui:      'Space Grotesk', system-ui, -apple-system, sans-serif;
  --jbk-body:    'Inter', system-ui, -apple-system, sans-serif;

  /* Spacing scale (8-pt grid) */
  --jbk-space-1: 8px;
  --jbk-space-2: 16px;
  --jbk-space-3: 24px;
  --jbk-space-4: 32px;
  --jbk-space-6: 48px;
  --jbk-space-8: 64px;
}
```

Google Fonts CSS link (always preconnect):

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
```

---

## 9. Hashtag & social conventions

- **Always** use `#jbklutse` as the **first** hashtag on every social post (FB, IG, Threads, X). Not `#JBKlutse`, not `#JBKlutseTech` — just `#jbklutse` lowercase.
- After `#jbklutse`, use only **topic-relevant** hashtags. No `#tech`, `#news`, `#technology`, `#viral`, `#instagood` filler.
- Hashtag count by platform: FB 3-5, IG 8-12, Threads 3-5, X 1-2.
- Mix global topical (`#AI`, `#SmartphonesGhana`) with niche/local (`#GhanaTech`, `#AccraTech`) on Instagram.
- Handle: `@jbklutse` on most platforms (verify per platform before assuming).

---

## 10. Applying this guide to a new automation

When building a new JBKlutse automation that produces visible content (image, caption, email, page), the checklist:

- [ ] Imports tokens from this file (don't redefine colors/fonts inline)
- [ ] Uses the JB voice rules (§5) for any generated text
- [ ] First hashtag on social = `#jbklutse` (enforced via post-validation, not just prompt instruction — see WF1's `Code — Parse + Validate Captions` for the pattern)
- [ ] Image specs per §6
- [ ] If using HCTI, follow the dark-grey-surface + teal-accent + Archivo-Black-headline pattern
- [ ] If using Gemini, includes the §7 style suffix
- [ ] Renders correctly on a 360px-wide phone screen (most Ghanaian readers are mobile-first)

When in doubt: open this file. If this file doesn't answer the question, ASK before improvising — and update this file with the answer once it's decided.

---

## 11. Change log

| Date | Change | Reason |
|---|---|---|
| 2026-04-28 | Initial draft. Locked Archivo Black + Space Grotesk in place of Mont/Helvetica for digital. Defined JB voice persona. Added Gemini style suffix. | Brand consolidation alongside social-feed automation launch. |
| 2026-04-28 | Documented JBK mark asset path + `JBK_LOGO_URL` swap mechanism in social-feed WF1. | Logo provided by JB; wired up swap-ready toggle. |

When updating tokens here, also: increment the change log, AND update any active automation that hard-codes the changed value. Search command:

```bash
grep -rn "008080\|141818\|Archivo Black\|Space Grotesk" C:/Sites/jbkluts-n8n/ C:/Sites/jbklutse-blog/
```
