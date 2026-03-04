<?php
/**
 * Admin page — registers menu, enqueues React app.
 *
 * @package SCWhatsApp\Admin
 */

namespace SCWhatsApp\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminPage
 */
class AdminPage {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the admin menu page.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'WhatsApp Notifications', 'scwa' ),
			__( 'WhatsApp', 'scwa' ),
			'manage_options',
			'scwa-settings',
			array( $this, 'render_page' ),
			'dashicons-format-chat',
			58
		);
	}

	/**
	 * Render the admin page container.
	 */
	public function render_page(): void {
		echo '<div id="scwa-admin-root"></div>';
	}

	/**
	 * Enqueue React app assets on our admin page only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_scwa-settings' !== $hook_suffix ) {
			return;
		}

		$asset_file = SCWA_PLUGIN_DIR . 'build/index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: array(
				'dependencies' => array(),
				'version'      => SCWA_VERSION,
			);

		wp_enqueue_script(
			'scwa-admin',
			SCWA_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'scwa-admin',
			SCWA_PLUGIN_URL . 'build/index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_localize_script(
			'scwa-admin',
			'scwaAdmin',
			array(
				'restUrl'    => rest_url( 'scwa/v1/' ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'version'    => SCWA_VERSION,
				'adminUrl'   => admin_url(),
				'storeName'  => get_bloginfo( 'name' ),
			)
		);
	}
}
