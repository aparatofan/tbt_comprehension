# TBT Comprehension — Claude Code guidance

Keep this file concise. It is loaded at the start of every Claude Code session.

## Start here

- Work from the task; do not scan the repository by default.
- Check `git status`, use targeted search, and read only the relevant file sections.
- The plugin code lives under `tbt-comprehension/`; do not mistake the repository root for the deployable plugin folder.
- Do not change unrelated copy, generated HTML structure, styling, or formatting.
- Inspect the final diff before finishing.

## Project basics

- WordPress plugin for The Blue Tree.
- Main file: `tbt-comprehension/tbt-comprehension.php`.
- Assets: `tbt-comprehension/assets/`.
- Purpose: generate timestamped comprehension tables, optionally with hover-to-reveal hint answers, for copying into WordPress Custom HTML blocks.
- The admin tool appears under TBT Hub when `TBT_HUB_SLUG` exists and falls back to its own menu otherwise.
- The admin capability is `edit_posts`.

## Behavior to preserve

- Support both input flows: timestamped question lines and pasted three-column timestamp/question/answer data.
- Preserve topic/domain selection and the HTML output contract used in existing lessons.
- Keep generated output self-contained enough to paste into a WordPress Custom HTML block as designed.
- Admin assets must load only on this plugin's own page.
- TBT Hub integration must remain optional; the tool must stay reachable when Hub is inactive.
- Do not add database storage or a larger framework unless a task explicitly calls for it; this is intentionally a small generator.

## Security and WordPress rules

- Preserve capability checks around the admin page.
- Escape PHP-rendered admin output for its context.
- Treat pasted teacher input as untrusted before inserting it into generated HTML/preview DOM.
- Never commit secrets, FTP credentials, API keys, or local configuration.

## Coding style

- Follow the surrounding WordPress/PHP and vanilla-JS style.
- Prefer a small local change over a new abstraction for one-off behavior.
- Reuse existing selectors, helpers, topic values, hooks, and asset handles.
- Do not reformat the whole PHP/JS/CSS file while solving a focused issue.

## Validation

For PHP changes:

```bash
php -l tbt-comprehension/tbt-comprehension.php
```

For JavaScript changes, run `node --check` on each changed JS file when Node is available.

For generator changes, manually reason through both input modes and verify that generated HTML remains valid and copyable. Browser/Divi appearance still requires a live-site check when layout or hover behavior changes.

## Git and deployment

- `main` is the integration branch; use a focused feature branch.
- A push to `main` deploys `./tbt-comprehension/` to `/tbt-comprehension/` over FTPS.
- Markdown is excluded from the FTP upload, though a push to `main` still starts the workflow.
- Never change deployment paths or secrets unless the task is specifically about deployment.

## Context discipline

- Prefer targeted search + narrow reads over broad exploration.
- Read `readme.txt` only when the task needs user-facing plugin behavior or packaging context.
- Do not paste entire files or long generated HTML into the conversation when a relevant excerpt is enough.
- Finish with a brief summary of changes, checks run, and any live WordPress verification still needed.
- For a new unrelated task, prefer a fresh Claude Code session.
