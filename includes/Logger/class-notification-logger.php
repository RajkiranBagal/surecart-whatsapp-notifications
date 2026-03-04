<?php
/**
 * Notification logger — DB operations on log table.
 *
 * @package SCWhatsApp\Logger
 */

namespace SCWhatsApp\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NotificationLogger
 */
class NotificationLogger {

	/**
	 * Get the log table name.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'scwa_notification_log';
	}

	/**
	 * Get the templates table name.
	 *
	 * @return string
	 */
	public static function get_templates_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'scwa_message_templates';
	}

	/**
	 * Create the log table.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type VARCHAR(50) NOT NULL,
			recipient_phone VARCHAR(20) NOT NULL,
			recipient_type VARCHAR(10) DEFAULT 'customer',
			order_id VARCHAR(100) DEFAULT '',
			template_name VARCHAR(100) DEFAULT '',
			message_body TEXT,
			status VARCHAR(20) NOT NULL,
			api_message_id VARCHAR(100) DEFAULT '',
			api_response TEXT,
			error_message TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_event_type (event_type),
			KEY idx_status (status),
			KEY idx_created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create the templates table.
	 */
	public static function create_templates_table(): void {
		global $wpdb;

		$table_name      = self::get_templates_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type VARCHAR(50) NOT NULL,
			recipient_type VARCHAR(10) DEFAULT 'customer',
			template_type VARCHAR(20) DEFAULT 'text',
			meta_template_name VARCHAR(100) DEFAULT '',
			meta_template_lang VARCHAR(10) DEFAULT 'en',
			message_body TEXT,
			is_enabled TINYINT(1) DEFAULT 1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_event_recipient (event_type, recipient_type)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false Inserted row ID or false.
	 */
	public static function log( array $data ) {
		global $wpdb;

		$defaults = array(
			'event_type'      => '',
			'recipient_phone' => '',
			'recipient_type'  => 'customer',
			'order_id'        => '',
			'template_name'   => '',
			'message_body'    => '',
			'status'          => '',
			'api_message_id'  => '',
			'api_response'    => '',
			'error_message'   => '',
			'created_at'      => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		// Ensure api_response is JSON string.
		if ( is_array( $data['api_response'] ) ) {
			$data['api_response'] = wp_json_encode( $data['api_response'] );
		}

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::get_table_name(),
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get paginated log entries.
	 *
	 * @param array $args Query arguments.
	 * @return array{logs: array, total: int}
	 */
	public static function get_logs( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'page'       => 1,
			'per_page'   => 20,
			'status'     => '',
			'event_type' => '',
			'order_id'   => '',
			'date_from'  => '',
			'date_to'    => '',
		);

		$args   = wp_parse_args( $args, $defaults );
		$table  = self::get_table_name();
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['event_type'] ) ) {
			$where[]  = 'event_type = %s';
			$values[] = $args['event_type'];
		}

		if ( ! empty( $args['order_id'] ) ) {
			$where[]  = 'order_id = %s';
			$values[] = $args['order_id'];
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'created_at >= %s';
			$values[] = $args['date_from'];
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'created_at <= %s';
			$values[] = $args['date_to'] . ' 23:59:59';
		}

		$where_clause = implode( ' AND ', $where );
		$offset       = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
		$limit        = absint( $args['per_page'] );

		// Count total.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
		if ( ! empty( $values ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		// Fetch rows.
		$query_values   = array_merge( $values, array( $limit, $offset ) );
		$rows_sql       = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$rows_sql       = $wpdb->prepare( $rows_sql, $query_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$logs           = $wpdb->get_results( $rows_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'logs'  => $logs ? $logs : array(),
			'total' => $total,
		);
	}

	/**
	 * Get a single log entry by ID.
	 *
	 * @param int $id Log entry ID.
	 * @return array|null
	 */
	public static function get_log( int $id ): ?array {
		global $wpdb;
		$table = self::get_table_name();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ? $row : null;
	}

	/**
	 * Check if a recent log exists for deduplication.
	 *
	 * @param string $event_type Event type.
	 * @param string $order_id   Order ID.
	 * @param int    $seconds    Lookback window in seconds.
	 * @return bool
	 */
	public static function has_recent( string $event_type, string $order_id, int $seconds = 60 ): bool {
		global $wpdb;
		$table = self::get_table_name();

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE event_type = %s AND order_id = %s AND created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$event_type,
				$order_id,
				gmdate( 'Y-m-d H:i:s', time() - $seconds )
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Get stats for the dashboard.
	 *
	 * @return array
	 */
	public static function get_stats(): array {
		global $wpdb;
		$table = self::get_table_name();

		// Status counts (all time).
		$status_counts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$counts = array(
			'sent'    => 0,
			'failed'  => 0,
			'skipped' => 0,
			'total'   => 0,
		);

		foreach ( $status_counts as $row ) {
			$counts[ $row['status'] ] = (int) $row['count'];
			$counts['total']         += (int) $row['count'];
		}

		// Status counts (last 7 days) for trend calculation.
		$seven_days_ago  = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$fourteen_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-14 days' ) );

		$current_week = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$seven_days_ago
			)
		);

		$previous_week = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$fourteen_days_ago,
				$seven_days_ago
			)
		);

		$trend = 0;
		if ( (int) $previous_week > 0 ) {
			$trend = round( ( ( (int) $current_week - (int) $previous_week ) / (int) $previous_week ) * 100 );
		}

		// Event breakdown (last 30 days).
		$thirty_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

		$event_counts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT event_type, COUNT(*) as count FROM {$table} WHERE created_at >= %s GROUP BY event_type ORDER BY count DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$thirty_days_ago
			),
			ARRAY_A
		);

		// Recent activity (last 5).
		$recent = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			"SELECT id, event_type, recipient_phone, recipient_type, order_id, status, created_at FROM {$table} ORDER BY created_at DESC LIMIT 5", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array(
			'counts'       => $counts,
			'trend'        => (int) $trend,
			'event_counts' => $event_counts ? $event_counts : array(),
			'recent'       => $recent ? $recent : array(),
		);
	}

	/**
	 * Clean up old log entries.
	 *
	 * @param int $days Days to retain (default: 90).
	 */
	public static function cleanup( int $days = 90 ): void {
		global $wpdb;
		$table    = self::get_table_name();
		$cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff
			)
		);
	}
}
