<?php
/**
 * REST API routes.
 *
 * @package SCWhatsApp\Admin
 */

namespace SCWhatsApp\Admin;

use SCWhatsApp\Provider\Encryption;
use SCWhatsApp\Provider\MetaCloudApiProvider;
use SCWhatsApp\Logger\NotificationLogger;
use SCWhatsApp\Service\TemplateRenderer;
use SCWhatsApp\Service\PhoneNormalizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestApi
 */
class RestApi {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private const NAMESPACE = 'scwa/v1';

	/**
	 * Register all REST routes.
	 */
	public function register_routes(): void {
		// Settings.
		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Test connection.
		register_rest_route(
			self::NAMESPACE,
			'/test-connection',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_connection' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Test message.
		register_rest_route(
			self::NAMESPACE,
			'/test-message',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send_test_message' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Logs.
		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_logs' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'page'       => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page'   => array(
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
					'status'     => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'event_type' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'date_range' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Resend a log entry.
		register_rest_route(
			self::NAMESPACE,
			'/logs/(?P<id>\d+)/resend',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'resend_notification' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Stats.
		register_rest_route(
			self::NAMESPACE,
			'/stats',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Templates.
		register_rest_route(
			self::NAMESPACE,
			'/templates',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_template' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Permission callback — require manage_options.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /settings — return all settings (token masked).
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings(): \WP_REST_Response {
		$token = get_option( 'scwa_api_access_token', '' );

		return new \WP_REST_Response(
			array(
				'phone_number_id'          => get_option( 'scwa_phone_number_id', '' ),
				'business_account_id'      => get_option( 'scwa_business_account_id', '' ),
				'api_access_token'         => ! empty( $token ) ? '••••••••••••••••' : '',
				'api_access_token_set'     => ! empty( $token ),
				'api_version'              => get_option( 'scwa_api_version', 'v21.0' ),
				'default_country_code'     => get_option( 'scwa_default_country_code', '91' ),
				'admin_phone'              => get_option( 'scwa_admin_phone', '' ),
				'enable_order_confirmed'   => get_option( 'scwa_enable_order_confirmed', '1' ),
				'enable_fulfillment_created' => get_option( 'scwa_enable_fulfillment_created', '1' ),
				'enable_refund_created'    => get_option( 'scwa_enable_refund_created', '0' ),
				'enable_admin_new_order'   => get_option( 'scwa_enable_admin_new_order', '1' ),
			),
			200
		);
	}

	/**
	 * POST /settings — save settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		$text_fields = array(
			'phone_number_id'     => 'scwa_phone_number_id',
			'business_account_id' => 'scwa_business_account_id',
			'api_version'         => 'scwa_api_version',
			'admin_phone'         => 'scwa_admin_phone',
		);

		foreach ( $text_fields as $param => $option ) {
			if ( isset( $params[ $param ] ) ) {
				update_option( $option, sanitize_text_field( $params[ $param ] ) );
			}
		}

		// Country code — numeric only.
		if ( isset( $params['default_country_code'] ) ) {
			update_option( 'scwa_default_country_code', absint( $params['default_country_code'] ) );
		}

		// Access token — encrypt before saving. Skip if masked value.
		if ( isset( $params['api_access_token'] ) && strpos( $params['api_access_token'], '••' ) === false ) {
			$encrypted = Encryption::encrypt( sanitize_text_field( $params['api_access_token'] ) );
			update_option( 'scwa_api_access_token', $encrypted );
		}

		// Toggle fields.
		$toggles = array(
			'enable_order_confirmed'   => 'scwa_enable_order_confirmed',
			'enable_fulfillment_created' => 'scwa_enable_fulfillment_created',
			'enable_refund_created'    => 'scwa_enable_refund_created',
			'enable_admin_new_order'   => 'scwa_enable_admin_new_order',
		);

		foreach ( $toggles as $param => $option ) {
			if ( isset( $params[ $param ] ) ) {
				update_option( $option, $params[ $param ] ? '1' : '0' );
			}
		}

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * POST /test-connection — verify API credentials.
	 *
	 * @return \WP_REST_Response
	 */
	public function test_connection(): \WP_REST_Response {
		$provider = $this->get_provider();

		if ( ! $provider ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API credentials are not configured.', 'scwa' ),
				),
				200
			);
		}

		$result = $provider->verify_connection();

		return new \WP_REST_Response(
			array(
				'success' => $result['success'],
				'message' => $result['success']
					? __( 'Connection successful. Business profile verified.', 'scwa' )
					: $result['error'],
				'data'    => $result['data'],
			),
			200
		);
	}

	/**
	 * POST /test-message — send a test WhatsApp message.
	 *
	 * @return \WP_REST_Response
	 */
	public function send_test_message(): \WP_REST_Response {
		$admin_phone = get_option( 'scwa_admin_phone', '' );

		if ( empty( $admin_phone ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Admin phone number is not configured.', 'scwa' ),
				),
				200
			);
		}

		$provider = $this->get_provider();
		if ( ! $provider ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API credentials are not configured.', 'scwa' ),
				),
				200
			);
		}

		$default_country = get_option( 'scwa_default_country_code', '91' );
		$normalized      = PhoneNormalizer::normalize( $admin_phone, $default_country );

		if ( empty( $normalized ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Admin phone number is invalid.', 'scwa' ),
				),
				200
			);
		}

		$message = __( 'Template: hello_world (en_US)', 'scwa' );
		$result  = $provider->send_template( $normalized, 'hello_world', 'en_US', array() );

		NotificationLogger::log(
			array(
				'event_type'      => 'test_message',
				'recipient_phone' => $normalized,
				'recipient_type'  => 'admin',
				'order_id'        => '',
				'template_name'   => 'hello_world',
				'message_body'    => $message,
				'status'          => $result['success'] ? 'sent' : 'failed',
				'api_message_id'  => $result['message_id'] ?? '',
				'api_response'    => $result['raw_response'] ?? array(),
				'error_message'   => $result['error'] ?? '',
			)
		);

		return new \WP_REST_Response(
			array(
				'success' => $result['success'],
				'message' => $result['success']
					? __( 'Test message sent successfully!', 'scwa' )
					: $result['error'],
			),
			200
		);
	}

	/**
	 * GET /logs — paginated logs.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_logs( \WP_REST_Request $request ): \WP_REST_Response {
		$args = array(
			'page'       => $request->get_param( 'page' ),
			'per_page'   => $request->get_param( 'per_page' ),
			'status'     => $request->get_param( 'status' ),
			'event_type' => $request->get_param( 'event_type' ),
		);

		// Handle date range.
		$date_range = $request->get_param( 'date_range' );
		if ( ! empty( $date_range ) ) {
			switch ( $date_range ) {
				case '7days':
					$args['date_from'] = gmdate( 'Y-m-d', strtotime( '-7 days' ) );
					break;
				case '30days':
					$args['date_from'] = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
					break;
				case '90days':
					$args['date_from'] = gmdate( 'Y-m-d', strtotime( '-90 days' ) );
					break;
			}
		}

		$result = NotificationLogger::get_logs( $args );

		return new \WP_REST_Response(
			array(
				'logs'     => $result['logs'],
				'total'    => $result['total'],
				'page'     => (int) $args['page'],
				'per_page' => (int) $args['per_page'],
				'pages'    => ceil( $result['total'] / max( 1, (int) $args['per_page'] ) ),
			),
			200
		);
	}

	/**
	 * POST /logs/{id}/resend — retry a failed notification.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function resend_notification( \WP_REST_Request $request ): \WP_REST_Response {
		$id  = (int) $request->get_param( 'id' );
		$log = NotificationLogger::get_log( $id );

		if ( ! $log ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Log entry not found.', 'scwa' ),
				),
				404
			);
		}

		$provider = $this->get_provider();
		if ( ! $provider ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'API credentials are not configured.', 'scwa' ),
				),
				200
			);
		}

		$result = $provider->send_text( $log['recipient_phone'], $log['message_body'] );

		NotificationLogger::log(
			array(
				'event_type'      => $log['event_type'],
				'recipient_phone' => $log['recipient_phone'],
				'recipient_type'  => $log['recipient_type'],
				'order_id'        => $log['order_id'],
				'template_name'   => $log['template_name'],
				'message_body'    => $log['message_body'],
				'status'          => $result['success'] ? 'sent' : 'failed',
				'api_message_id'  => $result['message_id'] ?? '',
				'api_response'    => $result['raw_response'] ?? array(),
				'error_message'   => $result['error'] ?? '',
			)
		);

		return new \WP_REST_Response(
			array(
				'success' => $result['success'],
				'message' => $result['success']
					? __( 'Notification resent successfully!', 'scwa' )
					: $result['error'],
			),
			200
		);
	}

	/**
	 * GET /stats — dashboard statistics.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_stats(): \WP_REST_Response {
		return new \WP_REST_Response( NotificationLogger::get_stats(), 200 );
	}

	/**
	 * GET /templates — all message templates.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_templates(): \WP_REST_Response {
		$templates = TemplateRenderer::get_all_templates();
		$variables = TemplateRenderer::get_available_variables();

		return new \WP_REST_Response(
			array(
				'templates' => $templates,
				'variables' => $variables,
			),
			200
		);
	}

	/**
	 * POST /templates — save a message template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_template( \WP_REST_Request $request ): \WP_REST_Response {
		$params = $request->get_json_params();

		if ( empty( $params['event_type'] ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Event type is required.', 'scwa' ),
				),
				400
			);
		}

		$data = array(
			'event_type'         => sanitize_text_field( $params['event_type'] ),
			'recipient_type'     => sanitize_text_field( $params['recipient_type'] ?? 'customer' ),
			'template_type'      => sanitize_text_field( $params['template_type'] ?? 'text' ),
			'meta_template_name' => sanitize_text_field( $params['meta_template_name'] ?? '' ),
			'meta_template_lang' => sanitize_text_field( $params['meta_template_lang'] ?? 'en' ),
			'message_body'       => sanitize_textarea_field( $params['message_body'] ?? '' ),
			'is_enabled'         => isset( $params['is_enabled'] ) ? (int) $params['is_enabled'] : 1,
		);

		$saved = TemplateRenderer::save_template( $data );

		return new \WP_REST_Response(
			array(
				'success' => $saved,
				'message' => $saved
					? __( 'Template saved successfully.', 'scwa' )
					: __( 'Failed to save template.', 'scwa' ),
			),
			$saved ? 200 : 500
		);
	}

	/**
	 * Get the WhatsApp API provider instance.
	 *
	 * @return MetaCloudApiProvider|null
	 */
	private function get_provider(): ?MetaCloudApiProvider {
		$phone_id    = get_option( 'scwa_phone_number_id', '' );
		$token_enc   = get_option( 'scwa_api_access_token', '' );
		$api_version = get_option( 'scwa_api_version', 'v21.0' );

		if ( empty( $phone_id ) || empty( $token_enc ) ) {
			return null;
		}

		$token = Encryption::decrypt( $token_enc );
		if ( false === $token || empty( $token ) ) {
			return null;
		}

		return new MetaCloudApiProvider( $phone_id, $token, $api_version );
	}
}
