/**
 * LogsPage — filterable, paginated log viewer.
 */
import { SelectControl, Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useLogs from '../hooks/useLogs';
import LogTable from '../components/LogTable';
import EmptyState from '../components/EmptyState';
import { resendNotification } from '../api';

export default function LogsPage() {
	const {
		logs,
		total,
		pages,
		page,
		setPage,
		filters,
		updateFilters,
		isLoading,
		refresh,
	} = useLogs();

	const handleRetry = async ( id ) => {
		await resendNotification( id );
		refresh();
	};

	return (
		<div className="scwa-logs-page">
			<div className="scwa-logs-filters">
				<SelectControl
					label={ __( 'Event Type', 'scwa' ) }
					value={ filters.event_type || '' }
					onChange={ ( v ) => updateFilters( { event_type: v } ) }
					options={ [
						{ label: __( 'All Events', 'scwa' ), value: '' },
						{
							label: __( 'Order Confirmed', 'scwa' ),
							value: 'checkout_confirmed',
						},
						{
							label: __( 'Fulfillment', 'scwa' ),
							value: 'fulfillment_created',
						},
						{
							label: __( 'Refund', 'scwa' ),
							value: 'refund_created',
						},
						{
							label: __( 'Admin Alert', 'scwa' ),
							value: 'admin_new_order',
						},
						{
							label: __( 'Test Message', 'scwa' ),
							value: 'test_message',
						},
					] }
				/>
				<SelectControl
					label={ __( 'Status', 'scwa' ) }
					value={ filters.status || '' }
					onChange={ ( v ) => updateFilters( { status: v } ) }
					options={ [
						{ label: __( 'All', 'scwa' ), value: '' },
						{ label: __( 'Sent', 'scwa' ), value: 'sent' },
						{ label: __( 'Failed', 'scwa' ), value: 'failed' },
						{ label: __( 'Skipped', 'scwa' ), value: 'skipped' },
					] }
				/>
				<SelectControl
					label={ __( 'Date Range', 'scwa' ) }
					value={ filters.date_range || '' }
					onChange={ ( v ) => updateFilters( { date_range: v } ) }
					options={ [
						{ label: __( 'All Time', 'scwa' ), value: '' },
						{
							label: __( 'Last 7 days', 'scwa' ),
							value: '7days',
						},
						{
							label: __( 'Last 30 days', 'scwa' ),
							value: '30days',
						},
						{
							label: __( 'Last 90 days', 'scwa' ),
							value: '90days',
						},
					] }
				/>
			</div>

			{ isLoading ? (
				<div className="scwa-loading">
					<Spinner />
				</div>
			) : logs.length === 0 ? (
				<EmptyState
					icon="\uD83D\uDCCB"
					title={ __( 'No notifications yet', 'scwa' ) }
					description={ __(
						'Notifications will appear here once SureCart orders start coming in.',
						'scwa'
					) }
				/>
			) : (
				<>
					<LogTable logs={ logs } onRetry={ handleRetry } />

					<div className="scwa-pagination">
						<span className="scwa-pagination__info">
							{ `${ __( 'Showing', 'scwa' ) } ${
								( page - 1 ) * 20 + 1
							}-${ Math.min(
								page * 20,
								total
							) } ${ __( 'of', 'scwa' ) } ${ total }` }
						</span>
						<div className="scwa-pagination__buttons">
							<Button
								variant="secondary"
								disabled={ page <= 1 }
								onClick={ () => setPage( page - 1 ) }
							>
								{ '\u2190 ' + __( 'Prev', 'scwa' ) }
							</Button>
							{ Array.from(
								{ length: Math.min( pages, 5 ) },
								( _, i ) => {
									let pageNum;
									if ( pages <= 5 ) {
										pageNum = i + 1;
									} else if ( page <= 3 ) {
										pageNum = i + 1;
									} else if ( page >= pages - 2 ) {
										pageNum = pages - 4 + i;
									} else {
										pageNum = page - 2 + i;
									}
									return (
										<Button
											key={ pageNum }
											variant={
												pageNum === page
													? 'primary'
													: 'secondary'
											}
											onClick={ () =>
												setPage( pageNum )
											}
										>
											{ pageNum }
										</Button>
									);
								}
							) }
							<Button
								variant="secondary"
								disabled={ page >= pages }
								onClick={ () => setPage( page + 1 ) }
							>
								{ __( 'Next', 'scwa' ) + ' \u2192' }
							</Button>
						</div>
					</div>
				</>
			) }
		</div>
	);
}
