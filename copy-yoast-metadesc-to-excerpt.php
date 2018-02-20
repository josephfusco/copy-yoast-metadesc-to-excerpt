<?php
/**
 * Plugin Name:    Copy Yoast Metadesc to Excerpt
 * Plugin URI:     http://github.com/josephfusco/copy-yoast-metadesc-to-excerpt
 * Description:    Copies all yoast metadesc value to the the post excerpt with the click of a button.
 * Version:        1.0.0
 * Author:         Joseph Fusco
 * Author URI:     https://josephfus.co
 * License:        GPLv2 or later
 * Text Domain:    cym2e
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Copy_Yoast_Metadesc_To_Excerpt {

	public function __construct() {
		$this->load_admin();
	}

	/**
	 * Load admin functionality.
	 */
	public function load_admin() {
		add_action( 'init', array( $this, 'plugin_init' ) );
	}

	/**
	 * Initialize wp-admin side of plugin.
	 */
	public function plugin_init() {
		if ( ! is_super_admin() ) {
			return;
		}

		add_action( 'admin_head', array( $this, 'action_javascript' ) );
		add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 2 );
		add_action( 'wp_ajax_cym2e_action', array( $this, 'process_form' ) );
	}

	/**
	 * Add link to settings page.
	 */
	public function plugin_meta_links( $links, $file ) {
		$plugin = plugin_basename( __FILE__ );

		// Create link.
		if ( $file == $plugin ) {
			return array_merge(
				$links,
				array( '<a href="' . admin_url( 'tools.php?page=self-destruct' ) . '">Settings</a>' )
			);
		}

		return $links;
	}

	/**
	 * Register submenu page.
	 */
	public function register_menu_page() {
		add_submenu_page(
			'tools.php',
			'Metadesc to Excerpt',
			'Metadesc to Excerpt',
			'manage_options',
			'cym2e',
			array( $this, 'submenu_page_cb' )
		);
	}

	/**
	 * Display a custom menu page.
	 */
	public function submenu_page_cb() {
		?>
		<div class="wrap">

			<h1>Copy Yoast Metadesc to Excerpt</h1>

			<p>It is recommended that you backup your site's database before proceeding.</p>
			<p>The following action will copy the yoast meta description to the post excerpt for <strong>ALL</strong> post types that contain a yoast metadesc.</p>

			<br>

			<form id="cym2e" action="" method="post" enctype="multipart/form-data">

				<p>
					<img src="<?php echo admin_url( 'images/spinner.gif' ); ?>" alt="loading" id="cym2e_loader" style="display:none">
					<input type="submit" name="copy" id="copy" class="button button-primary button-red" value="Copy">
				</p>

			</form>

		</div>
		<?php
	}

	/**
	 * Embed JS.
	 */
	public function action_javascript() {
		$ajax_nonce = wp_create_nonce( 'cym2e_ajax_nonce' );
		?>
		<script type="text/javascript">
		( function( $ ) {

			$( document ).ready( function( $ ) {

				var form   = $( '#cym2e' );
				var btn    = $( '#copy' );
				var loader = $( '#cym2e_loader' );

				form.submit( function( e ) {

					var user_confirm_code = $( '#user_confirm_code' ).val();
					var data = {
						action: 'cym2e_action',
						security: '<?php echo $ajax_nonce; ?>',
					};

					loader.show();
					btn.hide();

					e.preventDefault();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: data,
						success: function( response ) {
							loader.hide();
							btn.show();
							alert('Success');
						},
						error: function ( textStatus, errorThrown ) {
							loader.hide();
							btn.show();
							alert('Error');
						}
					})
				});
			});
		})( jQuery );
		</script>
		<?php
	}

	/**
	 * Process form data.
	 */
	public function process_form() {
		check_ajax_referer( 'cym2e_ajax_nonce', 'security' );

		$this->update_posts();

		exit();
	}

	/**
	 * Update all posts that have yoast metadesc.
	 */
	private function update_posts(){

		$my_posts = get_posts( array(
			'meta_key'    => '_yoast_wpseo_metadesc',
			'numberposts' => -1,
			'post_type'   => 'any',
		) );

		foreach ( $my_posts as $my_post ):

			$post_id    = $my_post->ID;
			$yoast_meta = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );

			$post_array = array(
				'ID'           => $post_id,
				'post_excerpt' => $yoast_meta
			);

			wp_update_post( $post_array );

		endforeach;
	}

}

$copy_yoast_metadesc_to_excerpt = new Copy_Yoast_Metadesc_To_Excerpt();
