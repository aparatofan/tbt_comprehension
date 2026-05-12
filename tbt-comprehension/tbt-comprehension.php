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

add_action( 'admin_menu', function () {
	add_management_page(
		__( 'TBT Comprehension', 'tbt-comprehension' ),
		__( 'TBT Comprehension', 'tbt-comprehension' ),
		'edit_posts',
		'tbt-comprehension',
		'tbt_comp_render_admin_page'
	);
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( $hook !== 'tools_page_tbt-comprehension' ) {
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
} );

function tbt_comp_render_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	?>
	<div class="wrap tbt-comp">
		<h1><?php esc_html_e( 'TBT Comprehension Table Generator', 'tbt-comprehension' ); ?></h1>
		<p class="tbt-subtitle">
			<?php esc_html_e( 'Paste your timestamped questions, optionally paste Q&A hints, choose a topic and click Generate. Copy the resulting HTML into a Custom HTML block in your post.', 'tbt-comprehension' ); ?>
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
							__( '(format: <code>0:18 Question text</code> &mdash; one per line)', 'tbt-comprehension' )
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
					<strong><?php esc_html_e( 'Hint Q&A (optional)', 'tbt-comprehension' ); ?></strong>
					<span class="tbt-hint-text">
						<?php
						echo wp_kses_post(
							__( '(paste 3 columns from Excel: <code>timestamp&nbsp;|&nbsp;question&nbsp;|&nbsp;answer</code>)', 'tbt-comprehension' )
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
