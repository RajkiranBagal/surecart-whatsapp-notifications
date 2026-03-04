/**
 * DashboardPage — overview with stats, bar chart, recent activity.
 */
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useStats from '../hooks/useStats';
import useSettings from '../hooks/useSettings';
import StatCard from '../components/StatCard';
import ConnectionStatus from '../components/ConnectionStatus';
import EmptyState from '../components/EmptyState';

const EVENT_LABELS = {
	checkout_confirmed: __( 'Order Confirmed', 'scwa' ),
	fulfillment_created: __( 'Fulfillment', 'scwa' ),
	refund_created: __( 'Refund', 'scwa' ),
	admin_new_order: __( 'Admin Alert', 'scwa' ),
	test_message: __( 'Test Message', 'scwa' ),
};

const STATUS_ICONS = {
	sent: '\u25CF',
	failed: '\u2715',
	skipped: '\u2298',
};

function maskPhone( phone ) {
	if ( ! phone || phone.length < 8 ) {
		return phone || '\u2014';
	}
	return phone.slice( 0, 4 ) + '****' + phone.slice( -2 );
}

function timeAgo( dateStr ) {
	const now = new Date();
	const date = new Date( dateStr );
	const seconds = Math.floor( ( now - date ) / 1000 );
	if ( seconds < 60 ) {
		return __( 'just now', 'scwa' );
	}
	const minutes = Math.floor( seconds / 60 );
	if ( minutes < 60 ) {
		return `${ minutes }m ${ __( 'ago', 'scwa' ) }`;
	}
	const hours = Math.floor( minutes / 60 );
	if ( hours < 24 ) {
		return `${ hours }h ${ __( 'ago', 'scwa' ) }`;
	}
	const days = Math.floor( hours / 24 );
	return `${ days }d ${ __( 'ago', 'scwa' ) }`;
}

export default function DashboardPage( { onNavigate } ) {
	const { stats, isLoading: statsLoading } = useStats();
	const { settings, isLoading: settingsLoading, testConnection } =
		useSettings();

	if ( statsLoading || settingsLoading ) {
		return (
			<div className="scwa-loading">
				<Spinner />
			</div>
		);
	}

	const isConfigured = settings?.api_access_token_set;

	if ( ! isConfigured ) {
		return (
			<EmptyState
				icon="\uD83D\uDCF1"
				title={ __( 'Connect Your WhatsApp Business', 'scwa' ) }
				description={ __(
					'Enter your Meta Cloud API credentials in Settings to start sending WhatsApp notifications.',
					'scwa'
				) }
				ctaLabel={ __( 'Go to Settings', 'scwa' ) }
				onCtaClick={ () => onNavigate?.( 'settings' ) }
			/>
		);
	}

	const counts = stats?.counts || {
		sent: 0,
		failed: 0,
		skipped: 0,
		total: 0,
	};
	const trend = stats?.trend || 0;
	const eventCounts = stats?.event_counts || [];
	const recent = stats?.recent || [];
	const maxEvent =
		eventCounts.length > 0
			? Math.max( ...eventCounts.map( ( e ) => parseInt( e.count ) ) )
			: 1;

	return (
		<div className="scwa-dashboard">
			<ConnectionStatus
				connected={ isConfigured }
				phone={ settings?.admin_phone || '' }
				accountId={ settings?.business_account_id || '' }
				onTestConnection={ testConnection }
			/>

			<div className="scwa-stats-grid">
				<StatCard
					icon="\u2713"
					label={ __( 'Sent', 'scwa' ) }
					count={ counts.sent }
					trend={ trend }
					color="green"
				/>
				<StatCard
					icon="\u2715"
					label={ __( 'Failed', 'scwa' ) }
					count={ counts.failed }
					color="red"
				/>
				<StatCard
					icon="\u2298"
					label={ __( 'Skipped', 'scwa' ) }
					count={ counts.skipped }
					color="gray"
				/>
				<StatCard
					icon="\u2211"
					label={ __( 'Total', 'scwa' ) }
					count={ counts.total }
					color="blue"
				/>
			</div>

			{ eventCounts.length > 0 && (
				<div className="scwa-card">
					<h3>
						{ __( 'Messages by Event (last 30 days)', 'scwa' ) }
					</h3>
					<div className="scwa-bar-chart">
						{ eventCounts.map( ( item ) => (
							<div
								key={ item.event_type }
								className="scwa-bar-chart__row"
							>
								<span className="scwa-bar-chart__label">
									{ EVENT_LABELS[ item.event_type ] ||
										item.event_type }
								</span>
								<div className="scwa-bar-chart__bar-wrapper">
									<div
										className="scwa-bar-chart__bar"
										style={ {
											width: `${
												( parseInt( item.count ) /
													maxEvent ) *
												100
											}%`,
										} }
									/>
								</div>
								<span className="scwa-bar-chart__count">
									{ item.count }
								</span>
							</div>
						) ) }
					</div>
				</div>
			) }

			<div className="scwa-card">
				<h3>{ __( 'Recent Activity', 'scwa' ) }</h3>
				{ recent.length === 0 ? (
					<p className="scwa-text-muted">
						{ __( 'No notifications yet.', 'scwa' ) }
					</p>
				) : (
					<>
						<table className="scwa-log-table widefat">
							<thead>
								<tr>
									<th>{ __( 'Event', 'scwa' ) }</th>
									<th>{ __( 'Recipient', 'scwa' ) }</th>
									<th>{ __( 'Status', 'scwa' ) }</th>
									<th>{ __( 'Time', 'scwa' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ recent.map( ( log ) => (
									<tr key={ log.id }>
										<td>
											{
												EVENT_LABELS[
													log.event_type
												] || log.event_type
											}
										</td>
										<td>
											{ maskPhone(
												log.recipient_phone
											) }
										</td>
										<td>
											<span
												className={ `scwa-status--${ log.status }` }
											>
												{ STATUS_ICONS[
													log.status
												] || '' }{ ' ' }
												{ log.status
													.charAt( 0 )
													.toUpperCase() +
													log.status.slice( 1 ) }
											</span>
										</td>
										<td>
											{ timeAgo( log.created_at ) }
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
						<div className="scwa-card__footer">
							<button
								type="button"
								className="scwa-link-button"
								onClick={ () => onNavigate?.( 'logs' ) }
							>
								{ __( 'View All Logs \u2192', 'scwa' ) }
							</button>
						</div>
					</>
				) }
			</div>
		</div>
	);
}
