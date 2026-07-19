<?php
/**
 * Plugin Name: TBT Comprehension
 * Description: Generates timestamped comprehension tables for posts, with optional hover tooltips that reveal hint answers for selected questions.
 * Version:     1.0.0
 * Author:      TBT
 * License:     GPLv2 or later
 * Text Domain: tbt-comprehension
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TBT_COMP_VERSION', '1.0.0' );
define( 'TBT_COMP_URL', plugin_dir_url( __FILE__ ) );
define( 'TBT_COMP_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Hook suffix of our admin page, captured at registration so the enqueue
 * callback can match it whether we live under the TBT hub or the fallback
 * top-level menu. (The old hard-coded `tools_page_…` suffix broke as soon as
 * the parent changed.)
 *
 * @var string
 */
$GLOBALS['tbt_comp_page_hook'] = '';

add_action( 'admin_menu', 'tbt_comp_register_menu' );
/**
 * Register the admin page: under the TBT hub when active, otherwise a
 * top-level menu of its own so the tool is never unreachable.
 */
function tbt_comp_register_menu() {
	if ( defined( 'TBT_HUB_SLUG' ) ) {
		$hook = add_submenu_page(
			TBT_HUB_SLUG,
			__( 'TBT Comprehension', 'tbt-comprehension' ),
			__( 'TBT Comprehension', 'tbt-comprehension' ),
			'edit_posts',
			'tbt-comprehension',
			'tbt_comp_render_admin_page'
		);
	} else {
		$hook = add_menu_page(
			__( 'TBT Comprehension', 'tbt-comprehension' ),
			__( 'TBT Comprehension', 'tbt-comprehension' ),
			'edit_posts',
			'tbt-comprehension',
			'tbt_comp_render_admin_page',
			'dashicons-editor-table',
			3
		);
	}
	$GLOBALS['tbt_comp_page_hook'] = $hook;
}

add_action( 'admin_enqueue_scripts', 'tbt_comp_admin_enqueue' );
/**
 * Enqueue the generator assets only on our own admin page.
 *
 * @param string $hook Current admin page hook suffix.
 */
function tbt_comp_admin_enqueue( $hook ) {
	if ( empty( $GLOBALS['tbt_comp_page_hook'] ) || $hook !== $GLOBALS['tbt_comp_page_hook'] ) {
		return;
	}
	wp_enqueue_style(
		'tbt-comp-admin',
		TBT_COMP_URL . 'assets/admin.css',
		array(),
		TBT_COMP_VERSION
	);
	wp_enqueue_script(
		'tbt-comp-admin',
		TBT_COMP_URL . 'assets/admin.js',
		array(),
		TBT_COMP_VERSION,
		true
	);
}

/**
 * Register this plugin on the TBT Hub Overview page.
 *
 * @param array $items Existing hub items.
 * @return array
 */
function tbt_comp_register_hub_item( $items ) {
	$items[] = array(
		'slug'        => 'tbt-comprehension',
		'title'       => 'TBT Comprehension',
		'description' => 'Generate timestamped comprehension tables with optional hover-to-reveal hint answers.',
		'capability'  => 'edit_posts',
	);
	return $items;
}
add_filter( 'tbt_hub_items', 'tbt_comp_register_hub_item' );

function tbt_comp_render_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	?>
	<div class="wrap tbt-comp">
		<h1><?php esc_html_e( 'TBT Comprehension Table Generator', 'tbt-comprehension' ); ?></h1>
		<p class="tbt-subtitle">
			<?php esc_html_e( 'You can either (a) paste timestamped questions on the left, or (b) paste 3 columns straight from Excel on the right (timestamp, question, answer). Choose a topic, click Generate, then copy the HTML into a Custom HTML block in your post.', 'tbt-comprehension' ); ?>
		</p>

		<div class="tbt-controls">
			<label for="tbt-topic"><strong><?php esc_html_e( 'Topic:', 'tbt-comprehension' ); ?></strong></label>
			<select id="tbt-topic">
				<option value="learn_english"><?php esc_html_e( 'LEARN ENGLISH', 'tbt-comprehension' ); ?></option>
				<option value="general_interest"><?php esc_html_e( 'GENERAL INTEREST', 'tbt-comprehension' ); ?></option>
				<option value="personal_development"><?php esc_html_e( 'PERSONAL DEVELOPMENT', 'tbt-comprehension' ); ?></option>
			</select>
			<span id="tbt-swatch" class="tbt-swatch"></span>
			<span id="tbt-hex-label" class="tbt-hex-label"></span>
		</div>

		<div class="tbt-grid">
			<div class="tbt-col">
				<label for="tbt-input">
					<strong><?php esc_html_e( 'Questions', 'tbt-comprehension' ); ?></strong>
					<span class="tbt-hint-text">
						<?php
						echo wp_kses_post(
							__( '(optional if the Q&A box is filled. Format: <code>0:18 Question text</code> &mdash; one per line)', 'tbt-comprehension' )
						);
						?>
					</span>
				</label>
				<textarea id="tbt-input" placeholder="0:18 Is empathy a commonly known term?
0:39 What are we going to look into in this video series?
0:55 What is the definition of the word &quot;empathy&quot;?
1:07 What does it mean to emotionally resonate with someone?"></textarea>
			</div>

			<div class="tbt-col">
				<label for="tbt-qa">
					<strong><?php esc_html_e( 'Hint Q&A', 'tbt-comprehension' ); ?></strong>
					<span class="tbt-hint-text">
						<?php
						echo wp_kses_post(
							__( '(paste 3 columns from Excel: <code>timestamp&nbsp;|&nbsp;question&nbsp;|&nbsp;answer</code>. If the Questions box is empty, the table is built from here.)', 'tbt-comprehension' )
						);
						?>
					</span>
				</label>
				<textarea id="tbt-qa" placeholder="0:18&#9;Is empathy a commonly known term?&#9;Not really &mdash; most people use the word loosely.
0:55&#9;What is the definition of the word &quot;empathy&quot;?&#9;The ability to understand and share another person's feelings."></textarea>
			</div>
		</div>

		<div class="tbt-buttons">
			<button type="button" class="button button-primary" id="tbt-generate">
				<?php esc_html_e( 'Generate table', 'tbt-comprehension' ); ?>
			</button>
			<button type="button" class="button" id="tbt-copy">
				<?php esc_html_e( 'Copy HTML', 'tbt-comprehension' ); ?>
			</button>
			<button type="button" class="button" id="tbt-example">
				<?php esc_html_e( 'Load example', 'tbt-comprehension' ); ?>
			</button>
			<span id="tbt-status" class="tbt-status"></span>
		</div>

		<div class="tbt-output-section">
			<div class="tbt-output-label"><?php esc_html_e( 'Live preview', 'tbt-comprehension' ); ?></div>
			<div id="tbt-preview" class="tbt-preview-box">
				<em class="tbt-muted"><?php esc_html_e( 'Preview will appear here.', 'tbt-comprehension' ); ?></em>
			</div>
		</div>

		<div class="tbt-output-section">
			<div class="tbt-output-label"><?php esc_html_e( 'HTML code (paste into a Custom HTML block in WordPress)', 'tbt-comprehension' ); ?></div>
			<pre id="tbt-html-output" class="tbt-html-output"><em class="tbt-muted"><?php esc_html_e( 'HTML will appear here.', 'tbt-comprehension' ); ?></em></pre>
		</div>

		<details class="tbt-tips">
			<summary><?php esc_html_e( 'Tips & formatting options', 'tbt-comprehension' ); ?></summary>
			<ul>
				<li><?php echo wp_kses_post( __( 'Each line of the <strong>Questions</strong> box must start with a timestamp like <code>0:18</code> or <code>12:05</code>, followed by a space, then the question.', 'tbt-comprehension' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'For italicised foreign terms (e.g. <em>ikigai</em>, <em>prot&eacute;g&eacute;</em>), wrap them in asterisks <code>*ikigai*</code> or underscores <code>_ikigai_</code>.', 'tbt-comprehension' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'Bullet colours: <strong>LEARN ENGLISH</strong> = #660000, <strong>GENERAL INTEREST</strong> = #663366, <strong>PERSONAL DEVELOPMENT</strong> = #006600.', 'tbt-comprehension' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The <strong>Hint Q&A</strong> box accepts 3 columns: timestamp, question, answer. Paste straight from Excel (tab-separated) or paste a CSV. The first row may be a header.', 'tbt-comprehension' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'Hint rows are matched to question rows by timestamp (<code>0:13</code> and <code>00:13</code> are treated as the same). Matching rows get a hover-able <strong>?</strong> icon that reveals the answer.', 'tbt-comprehension' ) ); ?></li>
				<li><?php echo wp_kses_post( __( 'The generated HTML is self-contained: it includes a small scoped <code>&lt;style&gt;</code> block for the tooltip so it works inside any Custom HTML block.', 'tbt-comprehension' ) ); ?></li>
			</ul>
		</details>
	</div>
	<?php
}
