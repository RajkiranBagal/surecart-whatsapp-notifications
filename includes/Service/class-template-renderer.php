<?php
/**
 * Template renderer — variable substitution engine.
 *
 * @package SCWhatsApp\Service
 */

namespace SCWhatsApp\Service;

use SCWhatsApp\Logger\NotificationLogger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TemplateRenderer
 */
class TemplateRenderer {

	/**
	 * Default message templates.
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		return array(
			'checkout_confirmed_customer'   => "Hi {{customer_name}},\n\nYour order #{{order_number}} for {{order_total}} has been confirmed!\n\nThank you for shopping at {{store_name}}.",
			'fulfillment_created_customer'  => "Hi {{customer_name}},\n\nGreat news! Your order #{{order_number}} has been shipped.\n\nTracking: {{tracking_number}}\n\nThank you for shopping at {{store_name}}.",
			'refund_created_customer'       => "Hi {{customer_name}},\n\nYour refund of {{refund_amount}} for order #{{order_number}} has been processed.\n\nIf you have any questions, please contact us.\n\n{{store_name}}",
			'admin_new_order'               => "New order received!\n\nOrder: #{{order_number}}\nCustomer: {{customer_name}}\nAmount: {{order_total}}\n\n{{store_name}}",
		);
	}

	/**
	 * Get available variables per event type.
	 *
	 * @return array
	 */
	public static function get_available_variables(): array {
		$common = array(
			'customer_name',
			'customer_first_name',
			'customer_email',
			'order_number',
			'order_total',
			'currency',
			'store_name',
			'checkout_url',
		);

		return array(
			'checkout_confirmed' => $common,
			'fulfillment_created' => array_merge( $common, array( 'tracking_number', 'tracking_url' ) ),
			'refund_created'     => array_merge( $common, array( 'refund_amount' ) ),
			'admin_new_order'    => $common,
		);
	}

	/**
	 * Seed default templates into the database.
	 */
	public static function seed_default_templates(): void {
		global $wpdb;

		$table    = NotificationLogger::get_templates_table_name();
		$defaults = self::get_defaults();

		$templates = array(
			array(
				'event_type'     => 'checkout_confirmed',
				'recipient_type' => 'customer',
				'message_body'   => $defaults['checkout_confirmed_customer'],
			),
			array(
				'event_type'     => 'fulfillment_created',
				'recipient_type' => 'customer',
				'message_body'   => $defaults['fulfillment_created_customer'],
			),
			array(
				'event_type'     => 'refund_created',
				'recipient_type' => 'customer',
				'message_body'   => $defaults['refund_created_customer'],
			),
			array(
				'event_type'     => 'admin_new_order',
				'recipient_type' => 'admin',
				'message_body'   => $defaults['admin_new_order'],
			),
		);

		foreach ( $templates as $template ) {
			// Only insert if not already exists.
			$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE event_type = %s AND recipient_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$template['event_type'],
					$template['recipient_type']
				)
			);

			if ( '0' === $exists || null === $exists ) {
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$table,
					array(
						'event_type'     => $template['event_type'],
						'recipient_type' => $template['recipient_type'],
						'template_type'  => 'text',
						'message_body'   => $template['message_body'],
						'is_enabled'     => 1,
						'created_at'     => current_time( 'mysql' ),
						'updated_at'     => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Get template for a specific event and recipient type.
	 *
	 * @param string $event_type     Event type.
	 * @param string $recipient_type Recipient type (customer|admin).
	 * @return array|null Template row or null.
	 */
	public static function get_template( string $event_type, string $recipient_type = 'customer' ): ?array {
		global $wpdb;
		$table = NotificationLogger::get_templates_table_name();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_type = %s AND recipient_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$event_type,
				$recipient_type
			),
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Get all templates.
	 *
	 * @return array
	 */
	public static function get_all_templates(): array {
		global $wpdb;
		$table = NotificationLogger::get_templates_table_name();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT * FROM {$table} ORDER BY event_type ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $rows ? $rows : array();
	}

	/**
	 * Save (upsert) a template.
	 *
	 * @param array $data Template data.
	 * @return bool
	 */
	public static function save_template( array $data ): bool {
		global $wpdb;
		$table = NotificationLogger::get_templates_table_name();

		$existing = self::get_template( $data['event_type'], $data['recipient_type'] ?? 'customer' );

		if ( $existing ) {
			$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$table,
				array(
					'template_type'      => $data['template_type'] ?? 'text',
					'meta_template_name' => $data['meta_template_name'] ?? '',
					'meta_template_lang' => $data['meta_template_lang'] ?? 'en',
					'message_body'       => $data['message_body'] ?? '',
					'is_enabled'         => isset( $data['is_enabled'] ) ? (int) $data['is_enabled'] : 1,
					'updated_at'         => current_time( 'mysql' ),
				),
				array(
					'event_type'     => $data['event_type'],
					'recipient_type' => $data['recipient_type'] ?? 'customer',
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s' ),
				array( '%s', '%s' )
			);
			return false !== $result;
		}

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$table,
			array(
				'event_type'         => $data['event_type'],
				'recipient_type'     => $data['recipient_type'] ?? 'customer',
				'template_type'      => $data['template_type'] ?? 'text',
				'meta_template_name' => $data['meta_template_name'] ?? '',
				'meta_template_lang' => $data['meta_template_lang'] ?? 'en',
				'message_body'       => $data['message_body'] ?? '',
				'is_enabled'         => isset( $data['is_enabled'] ) ? (int) $data['is_enabled'] : 1,
				'created_at'         => current_time( 'mysql' ),
				'updated_at'         => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Render a template by replacing {{variables}} with values.
	 *
	 * @param string $event_type     Event type.
	 * @param string $recipient_type Recipient type.
	 * @param array  $variables      Variable values.
	 * @return array{type: string, body: string, template_name: string, template_lang: string, params: array}
	 */
	public static function render( string $event_type, string $recipient_type, array $variables ): array {
		$template = self::get_template( $event_type, $recipient_type );

		// Fallback to defaults if no DB template.
		if ( ! $template ) {
			$defaults = self::get_defaults();
			$key      = $event_type . '_' . $recipient_type;
			$body     = $defaults[ $key ] ?? '';

			return array(
				'type'          => 'text',
				'body'          => self::substitute( $body, $variables ),
				'template_name' => '',
				'template_lang' => '',
				'params'        => array(),
			);
		}

		if ( 'meta_template' === $template['template_type'] ) {
			return array(
				'type'          => 'meta_template',
				'body'          => '',
				'template_name' => $template['meta_template_name'],
				'template_lang' => $template['meta_template_lang'],
				'params'        => array_values( $variables ),
			);
		}

		return array(
			'type'          => 'text',
			'body'          => self::substitute( $template['message_body'], $variables ),
			'template_name' => '',
			'template_lang' => '',
			'params'        => array(),
		);
	}

	/**
	 * Replace {{variable}} placeholders with values.
	 *
	 * @param string $template  Template string.
	 * @param array  $variables Variable key-value pairs.
	 * @return string
	 */
	public static function substitute( string $template, array $variables ): string {
		foreach ( $variables as $key => $value ) {
			$template = str_replace( '{{' . $key . '}}', (string) $value, $template );
		}
		return $template;
	}
}
