<?php
/**
 * Plugin Name: SureCart WhatsApp Notifications
 * Plugin URI: https://developer.brainstormforce.com
 * Description: Send automated WhatsApp messages to customers and admins based on SureCart order lifecycle events using the Meta WhatsApp Cloud API.
 * Version: 1.0.0
 * Author: Brainstorm Force
 * Author URI: https://developer.brainstormforce.com
 * Text Domain: scwa
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SCWA_VERSION', '1.0.0' );
define( 'SCWA_PLUGIN_FILE', __FILE__ );
define( 'SCWA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCWA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCWA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SCWA_CHECKOUT_PHONE_META_KEY', 'scwa_whatsapp_phone' );

/**
 * PSR-4 style autoloader.
 *
 * Maps SCWhatsApp\{SubDir}\ClassName to includes/{SubDir}/class-classname.php
 */
spl_autoload_register(
	function ( string $class_name ): void {
		$namespace = 'SCWhatsApp';

		if ( strpos( $class_name, $namespace ) !== 0 ) {
			return;
		}

		$class       = str_replace( $namespace . '\\', '', $class_name );
		$class_parts = explode( '\\', $class );
		$class_file  = 'class-' . strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', array_pop( $class_parts ) ) ) . '.php';

		$path = SCWA_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR
			. implode( DIRECTORY_SEPARATOR, $class_parts ) . DIRECTORY_SEPARATOR . $class_file;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

/**
 * Main plugin class.
 */
final class SCWA_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — hooks only.
	 */
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'check_requirements' ) );
		add_filter( 'plugin_action_links_' . SCWA_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Plugin activation.
	 */
	public static function activate(): void {
		SCWhatsApp\Logger\NotificationLogger::create_table();
		SCWhatsApp\Logger\NotificationLogger::create_templates_table();
		set_transient( 'scwa_activation_redirect', true, 30 );

		$defaults = array(
			'scwa_phone_number_id'          => '',
			'scwa_business_account_id'      => '',
			'scwa_api_access_token'         => '',
			'scwa_api_version'              => 'v21.0',
			'scwa_default_country_code'     => '91',
			'scwa_admin_phone'              => '',
			'scwa_enable_order_confirmed'   => '1',
			'scwa_enable_fulfillment_created' => '1',
			'scwa_enable_refund_created'    => '0',
			'scwa_enable_admin_new_order'   => '1',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}

		SCWhatsApp\Service\TemplateRenderer::seed_default_templates();
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate(): void {
		delete_transient( 'scwa_connection_status' );
		wp_clear_scheduled_hook( 'scwa_cleanup_old_logs' );
	}

	/**
	 * Check that SureCart is active and connected.
	 */
	public function check_requirements(): void {
		if ( ! class_exists( '\SureCart\Rest\OrderRestServiceProvider' ) ) {
			add_action( 'admin_notices', array( $this, 'show_dependency_notice' ) );
			return;
		}

		$this->init();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init(): void {
		add_action( 'admin_init', array( $this, 'maybe_redirect_after_activation' ) );

		$admin_page = new SCWhatsApp\Admin\AdminPage();
		$admin_page->register();

		$rest_api = new SCWhatsApp\Admin\RestApi();
		add_action( 'rest_api_init', array( $rest_api, 'register_routes' ) );

		$dispatcher = new SCWhatsApp\Service\NotificationDispatcher();
		$dispatcher->register_hooks();

		// Schedule log cleanup cron.
		if ( ! wp_next_scheduled( 'scwa_cleanup_old_logs' ) ) {
			wp_schedule_event( time(), 'daily', 'scwa_cleanup_old_logs' );
		}
		add_action( 'scwa_cleanup_old_logs', array( SCWhatsApp\Logger\NotificationLogger::class, 'cleanup' ) );
	}

	/**
	 * Redirect to Learn tab on first activation.
	 */
	public function maybe_redirect_after_activation(): void {
		if ( ! get_transient( 'scwa_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'scwa_activation_redirect' );

		if ( wp_doing_ajax() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=scwa-settings&tab=learn' ) );
		exit;
	}

	/**
	 * Show admin notice when SureCart is not active.
	 */
	public function show_dependency_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				esc_html_e(
					'SureCart WhatsApp Notifications requires SureCart to be installed and activated.',
					'scwa'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Add settings link on plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=scwa-settings' ),
			esc_html__( 'Settings', 'scwa' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}
}

// Lifecycle hooks.
register_activation_hook( __FILE__, array( 'SCWA_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SCWA_Plugin', 'deactivate' ) );

// Boot.
SCWA_Plugin::get_instance();
