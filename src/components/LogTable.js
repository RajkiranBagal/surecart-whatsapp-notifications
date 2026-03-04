/**
 * LogTable component — table with status badges, masked phones, expandable detail.
 */
import { Button, Card, CardBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const STATUS_ICONS = {
	sent: { icon: '\u25CF', cls: 'scwa-status--sent', label: 'Sent' },
	failed: { icon: '\u2715', cls: 'scwa-status--failed', label: 'Failed' },
	skipped: { icon: '\u2298', cls: 'scwa-status--skipped', label: 'Skipped' },
};

const EVENT_LABELS = {
	checkout_confirmed: 'Order Confirmed',
	fulfillment_created: 'Fulfillment',
	refund_created: 'Refund',
	admin_new_order: 'Admin Alert',
	test_message: 'Test Message',
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

export default function LogTable( { logs, onRetry } ) {
	const [ expandedId, setExpandedId ] = useState( null );
	const [ retryingId, setRetryingId ] = useState( null );

	const handleRetry = async ( id ) => {
		setRetryingId( id );
		await onRetry( id );
		setRetryingId( null );
	};

	if ( ! logs || logs.length === 0 ) {
		return null;
	}

	return (
		<div className="scwa-log-table-wrapper">
			<table className="scwa-log-table widefat">
				<thead>
					<tr>
						<th>{ __( 'Event', 'scwa' ) }</th>
						<th>{ __( 'Phone', 'scwa' ) }</th>
						<th>{ __( 'Order', 'scwa' ) }</th>
						<th>{ __( 'Status', 'scwa' ) }</th>
						<th>{ __( 'Time', 'scwa' ) }</th>
						<th>{ __( 'Action', 'scwa' ) }</th>
					</tr>
				</thead>
				<tbody>
					{ logs.map( ( log ) => {
						const status = STATUS_ICONS[ log.status ] || STATUS_ICONS.skipped;
						const isExpanded = expandedId === log.id;

						return [
							<tr
								key={ log.id }
								className={ `scwa-log-row ${
									isExpanded ? 'scwa-log-row--expanded' : ''
								}` }
								onClick={ () =>
									setExpandedId(
										isExpanded ? null : log.id
									)
								}
								style={ { cursor: 'pointer' } }
							>
								<td>
									{
										EVENT_LABELS[ log.event_type ] ||
										log.event_type
									}
								</td>
								<td>
									{ maskPhone( log.recipient_phone ) }
								</td>
								<td>{ log.order_id || '\u2014' }</td>
								<td>
									<span className={ status.cls }>
										{ status.icon } { status.label }
									</span>
								</td>
								<td>{ timeAgo( log.created_at ) }</td>
								<td>
									{ log.status === 'failed' && (
										<Button
											variant="tertiary"
											className="scwa-retry-btn"
											onClick={ ( e ) => {
												e.stopPropagation();
												handleRetry( log.id );
											} }
											disabled={
												retryingId === log.id
											}
										>
											{ '\u21BB' }
										</Button>
									) }
								</td>
							</tr>,
							isExpanded && (
								<tr
									key={ `${ log.id }-detail` }
									className="scwa-log-detail-row"
								>
									<td colSpan="6">
										<Card>
											<CardBody>
												<div className="scwa-log-detail">
													<div className="scwa-log-detail__row">
														<strong>
															{ __(
																'Event:',
																'scwa'
															) }
														</strong>{ ' ' }
														{
															EVENT_LABELS[
																log.event_type
															] ||
															log.event_type
														}
													</div>
													<div className="scwa-log-detail__row">
														<strong>
															{ __(
																'Order:',
																'scwa'
															) }
														</strong>{ ' ' }
														{
															log.order_id ||
															'\u2014'
														}
													</div>
													<div className="scwa-log-detail__row">
														<strong>
															{ __(
																'Phone:',
																'scwa'
															) }
														</strong>{ ' ' }
														{
															log.recipient_phone
														}
													</div>
													<div className="scwa-log-detail__row">
														<strong>
															{ __(
																'Status:',
																'scwa'
															) }
														</strong>{ ' ' }
														<span
															className={
																status.cls
															}
														>
															{ status.icon }{ ' ' }
															{ status.label }
														</span>
													</div>
													<div className="scwa-log-detail__row">
														<strong>
															{ __(
																'Template:',
																'scwa'
															) }
														</strong>{ ' ' }
														{
															log.template_name ||
															'\u2014'
														}
													</div>
													{ log.message_body && (
														<div className="scwa-log-detail__row">
															<strong>
																{ __(
																	'Message:',
																	'scwa'
																) }
															</strong>
															<pre className="scwa-log-detail__message">
																{
																	log.message_body
																}
															</pre>
														</div>
													) }
													{ log.error_message && (
														<div className="scwa-log-detail__row scwa-log-detail__error">
															<strong>
																{ __(
																	'Error:',
																	'scwa'
																) }
															</strong>{ ' ' }
															{
																log.error_message
															}
														</div>
													) }
													{ log.api_response && (
														<div className="scwa-log-detail__row">
															<strong>
																{ __(
																	'API Response:',
																	'scwa'
																) }
															</strong>
															<pre className="scwa-log-detail__json">
																{
																	typeof log.api_response ===
																	'string'
																		? log.api_response
																		: JSON.stringify(
																				log.api_response,
																				null,
																				2
																		  )
																}
															</pre>
														</div>
													) }
													<div className="scwa-log-detail__row">
														<strong>
															{ __(
																'Time:',
																'scwa'
															) }
														</strong>{ ' ' }
														{ log.created_at }
													</div>
													{ log.status ===
														'failed' && (
														<div className="scwa-log-detail__actions">
															<Button
																variant="secondary"
																onClick={ () =>
																	handleRetry(
																		log.id
																	)
																}
																disabled={
																	retryingId ===
																	log.id
																}
															>
																{ __(
																	'Retry Send',
																	'scwa'
																) }
															</Button>
														</div>
													) }
												</div>
											</CardBody>
										</Card>
									</td>
								</tr>
							),
						];
					} ) }
				</tbody>
			</table>
		</div>
	);
}
