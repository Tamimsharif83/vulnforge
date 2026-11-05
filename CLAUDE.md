
## Frontend design rules

### Design system
- Read `./DESIGN.md` before creating or changing any UI. Use only its color, type, spacing, radius and shadow tokens; don't invent new values.
- Read `./PRODUCT.md` for audience and tone.
- Never overwrite or regenerate `DESIGN.md` or `PRODUCT.md` without asking first.

### Anti-slop
- Follow the impeccable skill. No purple-to-blue gradient headlines, no emoji icon tiles above headings (use SVG icons), no cards nested in cards.
- Before calling UI work done, run an impeccable audit + polish pass on the changed components.

### UX & accessibility
- Interactive targets at least 24x24px (44x44px on touch/mobile).
- Never disable zoom (no `user-scalable=no` or `maximum-scale=1`).
- Every async action or form shows a visible pending state and blocks double-submit.
- For full UI reviews, use the web-design-guidelines skill on the changed files.

### Visual QA (Playwright MCP)
- After a meaningful UI change (new page, section, component or layout change), open the dev server with Playwright MCP, screenshot at 1440px and 390px widths, and fix overflow, overlap, clipped text or broken layout before finishing.
- Skip this for small copy or style tweaks.
- Dev server: http://localhost:8088
