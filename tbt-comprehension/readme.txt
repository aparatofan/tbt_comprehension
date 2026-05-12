=== TBT Comprehension ===
Contributors: tbt
Tags: tables, comprehension, tooltip, education, learning
Requires at least: 5.5
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate timestamped comprehension question tables with optional hover tooltips that reveal hint answers. Built for learning posts that reference a video by timestamp.

== Description ==

TBT Comprehension adds a generator under **Tools → TBT Comprehension** in the WordPress admin. Paste a list of timestamped questions, choose the topic (LEARN ENGLISH / GENERAL INTEREST / PERSONAL DEVELOPMENT), and the plugin produces ready-to-paste HTML for a comprehension table with colour-coded numbering.

Optionally paste a 3-column Excel/CSV block of `timestamp | question | answer` hints. Any row whose timestamp matches a question gets a hover-able **?** icon that reveals the answer in a tooltip.

The generated HTML is self-contained: a small scoped `<style>` block is bundled with the table when at least one tooltip is present, so it works inside any Custom HTML block without theme tweaks.

== Installation ==

1. Upload the `tbt-comprehension` folder to `/wp-content/plugins/`.
2. Activate **TBT Comprehension** from the Plugins page.
3. Go to **Tools → TBT Comprehension** in the WordPress admin.

== Usage ==

1. **Questions** — paste one question per line in the format `0:18 Question text`. Use `*text*` or `_text_` to italicise terms.
2. **Hint Q&A (optional)** — paste 3 columns straight from Excel (`timestamp`, `question`, `answer`). Tabs or commas both work, and the first row may be a header.
3. Choose the **Topic** (sets the bullet colour).
4. Click **Generate table**, then **Copy HTML**.
5. In your post, insert a **Custom HTML** block and paste.

== Changelog ==

= 1.0.0 =
* Initial release: table generator ported from the standalone HTML tool, plus tooltip-by-timestamp hint feature.
