# GakusonTheme Workspace Instructions

## Purpose
- This repository is a custom WordPress theme.
- When working here, preserve the existing WordPress template structure and the SMACSS layering already used by the project.
- Treat `docs/3-17-yuki-design.md` and `docs/3-17-yuki-tickets.md` as the current source of truth for the 2026-03-17 feature work.

## Project Shape
- Theme templates live at the repo root, for example `front-page.php`, `single.php`, `category.php`, `tag.php`, `page.php`, `header.php`, `footer.php`, `sidebar.php`.
- Styles are organized under `smacss/` and bundled by entry files in `smacss/main/`.
- Frontend behavior is currently centered in `js/script.js`.
- The active theme CSS loaded by WordPress comes from `smacss/main/*.css`, not from the page-level CSS files in `smacss/page/`.

## WordPress Rules
- Respect the WordPress template hierarchy. Prefer the correct template file or hook over ad hoc routing.
- Keep templates thin. Move reusable logic into `functions.php` or reusable partials instead of duplicating loops and data shaping in templates.
- Prefer minimal, WordPress-native changes over large abstractions.
- Preserve existing markup as much as possible. Do not rewrite stable HTML structure unless the ticket specifically requires it.
- Do not aggressively unify template HTML/JS unless duplication is directly blocking the requested change.
- In this repository, SMACSS is not a reason by itself to refactor PHP templates into a component system.
- Do not declare new PHP functions inside template files.
- Do not register `add_action()` or `add_filter()` inside template files.
- Prefix new PHP helpers with `gakuson_`.
- Escape output by default.
  - Use `esc_html()` for plain text.
  - Use `esc_url()` for URLs.
  - Use `esc_attr()` for attributes.
  - Use `wp_kses_post()` only when HTML output is intentionally allowed.
- If using `WP_Query`, always restore state with `wp_reset_postdata()`.
- If changing the main search or archive query behavior, prefer `pre_get_posts` over template-local query hacks.
- Never hardcode secrets, tokens, or external endpoints directly in templates.
- Prefer core template tags and generated markup/classes before inventing custom wrappers:
  - `body_class()`
  - `post_class()`
  - `get_search_form()` / `searchform.php`
  - `wp_nav_menu()`
  - `the_content()` block markup (`wp-block-*`)
  - `wp_tag_cloud()`
- Add brief comments to non-trivial functions, hooks, and query logic so future work can follow the intent quickly.
- Comments should explain why the logic exists or what constraint it serves, not restate obvious syntax.

## SMACSS Rules
- Edit SCSS source, not only compiled CSS.
- Put styles in the narrowest correct layer:
  - `smacss/layout/` for page structure and layout primitives
  - `smacss/module/` for reusable UI blocks shared across pages
  - `smacss/page/` for page-specific styling
  - `smacss/state/` for open/active/visibility states
  - `smacss/main/` for entry files that assemble the layers
- If a style is only for one screen, keep it in `page/`; do not promote it to `module/` unless it is truly reused.
- When making style changes, ensure the corresponding `smacss/main/*.css` output is updated.
- Keep the existing naming style consistent. Do not introduce a different CSS methodology unless there is a strong reason.

## Design and Architecture Priorities
- Favor consistency with the top page visual language across archives, search, page chrome, and shared cards.
- Preserve article readability on `single.php`; content typography should not regress while aligning surrounding UI.
- Reuse WordPress-native class hooks first, and only extract shared markup when the gain is clearly larger than the migration cost.
- For this theme, "horizontal rollout" should usually mean applying the same visual rules across templates, not forcing all templates into one shared HTML partial.
- Avoid mixing data-fetching concerns, markup concerns, and styling concerns in one place when a helper or partial can separate them cleanly.

## Search Feature Defaults
- Search entry should come from the header modal UI.
- Search submits by GET and should use:
  - `s`
  - `category_name`
  - `tag`
- Prefer `get_search_form()` or `searchform.php` compatible implementation over a one-off hardcoded search form.
- Search results should be rendered by `search.php`.
- Search scope for v1 is posts only.
- Category and tag filters are single-select in v1 unless the task explicitly changes that requirement.

## Featured Carousel Defaults
- Featured carousel content is driven by a dedicated tag slug: `featured`.
- Maximum item count is 5.
- Behavior rules:
  - 0 items: hide the carousel
  - 1 item: render as a static hero
  - 2 or more items: enable slider behavior
- Excerpt rule:
  - use the manual excerpt when available
  - otherwise use a trimmed body excerpt

## External Sync Defaults
- Carousel sync to Xserver must be implemented outside templates, through helper functions/hooks.
- Use `wp-config.php` constants for configuration:
  - `GAKUSON_CAROUSEL_SYNC_URL`
  - `GAKUSON_CAROUSEL_SYNC_TOKEN`
- Do not add an admin GUI just for the featured tag selection; use the normal WordPress tag UI with the fixed slug `featured`.
- Sync failures must not break frontend rendering.
- Log or persist enough status to inspect the latest sync outcome.

## Reuse Before Rewrite
- Before editing multiple templates, check whether the same pattern already appears in:
  - `front-page.php`
  - `single.php`
  - `category.php`
  - `tag.php`
  - `page.php`
  - `sidebar.php`
- If the same markup appears in more than one place, first ask whether WordPress-native class hooks and small helper functions are enough.
- Extract a shared partial only when it clearly reduces maintenance cost without forcing a large template rewrite.

## Ticket Workflow
- Work one ticket at a time unless the user explicitly asks to batch multiple tickets.
- At the start of each ticket, reread `AGENTS.md`, `docs/3-17-yuki-design.md`, and `docs/3-17-yuki-tickets.md`.
- Work only on the requested ticket. Do not touch other tickets unless the user explicitly asks for it.
- Use `AgentLog/TK-xx.md` as the per-ticket operating log for this repository.
- Treat user review as a checkpoint between tickets.
- Stop after each review summary or re-review summary. Do not commit until the user explicitly approves that ticket.
- Use commit messages that start with the ticket id, for example:
  - `TK-03: implement header search modal`

### Initial Implementation
- Implement while checking `docs/3-17-yuki-design.md` and `docs/3-17-yuki-tickets.md`.
- Create or update `AgentLog/TK-xx.md` as work progresses.
- Each ticket log should include at least:
  - ticket id and title
  - goal
  - 変更内容
  - 判断理由
  - Touched files
  - Review notes / risks
  - Next steps
- After finishing the ticket work, prepare a user-facing review summary and stop. Do not commit.

### Review Follow-up
- When continuing a ticket after user review, reread `AGENTS.md`, `docs/3-17-yuki-design.md`, and `docs/3-17-yuki-tickets.md` before making changes.
- Apply only the minimum changes needed for the current review of that ticket.
- Append the response details and remaining risks to the same `AgentLog/TK-xx.md`.
- After the review fixes, prepare a re-review summary and stop. Do not commit.

### Approved Finalization
- When the user says the ticket is approved, reread `AGENTS.md` and the related docs before finalizing.
- Confirm the diff stays within the approved ticket scope.
- If needed, do only light cleanup that stays within the same ticket.
- Create one git commit for that ticket with message format `TK-xx: ...`.
- Append the final summary, validation performed, remaining risks, and commit hash to `AgentLog/TK-xx.md`.
- Stop after finalizing that ticket. Do not begin the next ticket unless the user explicitly asks.

## Safety Notes
- This repository contains some mojibake in terminal output. Avoid bulk text rewrites unless the task specifically asks for content cleanup.
- When reading `.md` files in the terminal, always specify UTF-8 encoding explicitly each time, for example `Get-Content -Encoding UTF8`.
- Keep changes scoped. Do not opportunistically refactor unrelated files.
- If working on styles, verify both desktop and mobile behavior.
- If changing JS-driven UI, verify ARIA attributes and keyboard behavior as part of the task.

## Validation Expectations
- For template or PHP changes, at minimum sanity-check affected routes.
- For style changes, regenerate the relevant CSS and verify the affected screens.
- For search changes, check keyword-only, category-only, tag-only, combined, and zero-result cases.
- For carousel changes, check 0, 1, and multiple featured posts.
