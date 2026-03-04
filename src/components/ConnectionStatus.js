/**
 * ConnectionStatus component — green/red badge + test connection button.
 */
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

export default function ConnectionStatus( {
	connected,
	phone,
	accountId,
	onTestConnection,
} ) {
	const [ isTesting, setIsTesting ] = useState( false );

	const handleTest = async () => {
		setIsTesting( true );
		await onTestConnection();
		setIsTesting( false );
	};

	return (
		<div
			className={ `scwa-connection-status scwa-connection-status--${
				connected ? 'connected' : 'disconnected'
			}` }
		>
			<div className="scwa-connection-status__info">
				<span className="scwa-connection-status__dot" />
				<span className="scwa-connection-status__text">
					{ connected
						? __( 'Connected to Meta Cloud API', 'scwa' )
						: __( 'Not connected', 'scwa' ) }
				</span>
				{ connected && phone && (
					<span className="scwa-connection-status__details">
						{ __( 'Phone:', 'scwa' ) } { phone }
						{ accountId &&
							` \u00B7 ${ __(
								'Account:',
								'scwa'
							) } ${ accountId }` }
					</span>
				) }
			</div>
			<Button variant="secondary" onClick={ handleTest } disabled={ isTesting }>
				{ isTesting && <Spinner /> }
				{ __( 'Test Connection', 'scwa' ) }
			</Button>
		</div>
	);
}
