# Short news-brief decisions

Source: `seo-analysis-output/thin_originals_with_gsc.csv` (GSC window 2025-09-17 → 2026-04-27).
Rule of thumb: expand only when GSC shows a striking-distance position (top 20) AND ≥50 impressions, or top-10 with non-zero clicks. Everything else lets the existing 60-day auto-expire rule retire it.

## Expand (5)

These have real demand and rank close enough that expansion + internal links + structured data should yield gains.

| URL | Imp | Pos | CTR | Why expand |
|---|---:|---:|---:|---|
| /ges-introduces-reset-feature-on-promotion-portal/ | 916 | 10.6 | 0.66% | Page 1 edge; large impression volume; CTR weak — needs better title + FAQ |
| /ges-releases-2025-26-school-prospectus-for-day-and-boarding-students/ | 697 | 8.6 | 2.30% | Already top 10; ride the curve with deeper coverage + downloadable list |
| /kenyas-safaricom-fires-113-staff-over-breaches/ | 194 | 8.1 | 2.06% | Top 10 with clicks; expand with HR/legal context |
| /government-bans-new-biometric-systems-ghana-card/ | 84 | 6.5 | 0% | Excellent position, zero CTR — title/snippet rewrite + Q&A section |
| /csa-draft-cybersecurity-amendment-bill-2025/ | 112 | 11.3 | 1.79% | Striking distance; expand with full bill summary + comment-deadline CTA |

## Let auto-expire (15)

Position too weak, or impressions too thin to justify rewriting. Existing 60-day undated-post rule will soft-retire them to noindex.

| URL | Imp | Pos | Reason |
|---|---:|---:|---|
| /ghana-armed-forces-announces-2025-nationwide-recruitment-across-all-16-regions/ | 191 | 30.5 | Position too weak |
| /2025-shs-placement-released/ | 110 | 42.2 | Off page 4; cannibalises with prospectus piece anyway |
| /adobe-products-now-on-sale-in-ghana-and-nigeria/ | 70 | 11.9 | Borderline, but evergreen-ish — leave indexed, no expand |
| /bank-of-ghana-reduces-policy-rate-to-21-5/ | 46 | 58.1 | Position too weak |
| /mobile-money-still-dominates-ghanas-financial-sector/ | 39 | 47.0 | Position too weak |
| /dstvs-canal-owners-to-take-african-content-global/ | 26 | 18.2 | Low impressions |
| /why-ghanaians-will-pay-more-for-electricity/ | 23 | 25.5 | Time-bound; let expire |
| /openai-launches-chatgpt-pulse-ai-feature/ | 12 | 52.2 | Position too weak |
| /meta-launches-vibes-ai-videos-for-creative-content/ | 10 | 45.4 | Position too weak |
| /ghana-begins-nationwide-hpv-vaccination-for-girls/ | 9 | 43.6 | Position too weak |
| /nia-sets-up-help-desk-for-national-service-personnel/ | 8 | 7.9 | Decent position but ~zero demand |
| /nigeria-to-tax-remote-workers-from-january-2026/ | 7 | 19.9 | Low impressions |
| /kenya-and-nigeria-partner-to-expand-broadband-access/ | 5 | 44.8 | Position too weak |
| /tourism-minister-commends-joe-mettles-new-creative-arts-studio/ | 4 | 23.3 | Off-topic + thin |
| /meta-introduces-message-translations-feature-on-whatsapp/ | <3 | n/a | Below GSC threshold |

## Cyber Tips dailies — separate workstream

All 13 dailies (Oct 8–18, 20, 27–29) → merged into Cybersecurity Awareness Month pillar with 301 redirects. See `cyber-tips-redirects.csv`.
