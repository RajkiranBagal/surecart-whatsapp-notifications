/**
 * SettingsPage — API config + notification toggles + save.
 */
import {
	TextControl,
	Button,
	Notice,
	Spinner,
	Card,
	CardHeader,
	CardBody,
	CardFooter,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import useSettings from '../hooks/useSettings';
import NotificationToggle from '../components/NotificationToggle';

export default function SettingsPage() {
	const {
		settings,
		isLoading,
		isSaving,
		notice,
		setNotice,
		saveSettings,
		testConnection,
		sendTestMessage,
	} = useSettings();

	const [ form, setForm ] = useState( {} );
	const [ isTesting, setIsTesting ] = useState( false );
	const [ isSendingTest, setIsSendingTest ] = useState( false );

	useEffect( () => {
		if ( settings ) {
			setForm( { ...settings } );
		}
	}, [ settings ] );

	if ( isLoading ) {
		return (
			<div className="scwa-loading">
				<Spinner />
			</div>
		);
	}

	const updateField = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const handleSave = () => {
		saveSettings( form );
	};

	const handleTestConnection = async () => {
		setIsTesting( true );
		await testConnection();
		setIsTesting( false );
	};

	const handleSendTest = async () => {
		setIsSendingTest( true );
		await sendTestMessage();
		setIsSendingTest( false );
	};

	return (
		<div className="scwa-settings">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<Card className="scwa-settings-card">
				<CardHeader>
					<h3>{ __( 'API Configuration', 'scwa' ) }</h3>
				</CardHeader>
				<CardBody>
					<TextControl
						label={ __( 'Phone Number ID', 'scwa' ) }
						value={ form.phone_number_id || '' }
						onChange={ ( v ) =>
							updateField( 'phone_number_id', v )
						}
						help={ __(
							'The Phone Number ID from your Meta Business Suite.',
							'scwa'
						) }
					/>
					<TextControl
						label={ __( 'Business Account ID', 'scwa' ) }
						value={ form.business_account_id || '' }
						onChange={ ( v ) =>
							updateField( 'business_account_id', v )
						}
					/>
					<TextControl
						label={ __( 'API Access Token', 'scwa' ) }
						value={ form.api_access_token || '' }
						onChange={ ( v ) =>
							updateField( 'api_access_token', v )
						}
						help={ __(
							'Stored encrypted. Never exposed in the browser.',
							'scwa'
						) }
						type="password"
					/>
					<div className="scwa-settings-row">
						<TextControl
							label={ __( 'API Version', 'scwa' ) }
							value={ form.api_version || 'v21.0' }
							onChange={ ( v ) =>
								updateField( 'api_version', v )
							}
						/>
						<TextControl
							label={ __( 'Default Country Code', 'scwa' ) }
							value={ form.default_country_code || '91' }
							onChange={ ( v ) =>
								updateField( 'default_country_code', v )
							}
							help={ __(
								'Used when phone has no country code.',
								'scwa'
							) }
						/>
					</div>
					<div className="scwa-settings-actions">
						<Button
							variant="secondary"
							onClick={ handleTestConnection }
							disabled={ isTesting }
						>
							{ isTesting && <Spinner /> }
							{ __( 'Test Connection', 'scwa' ) }
						</Button>
						<Button
							variant="secondary"
							onClick={ handleSendTest }
							disabled={ isSendingTest }
						>
							{ isSendingTest && <Spinner /> }
							{ __( 'Send Test Message', 'scwa' ) }
						</Button>
					</div>
				</CardBody>
				<CardFooter className="scwa-settings-card__footer">
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ __( 'Save Settings', 'scwa' ) }
					</Button>
				</CardFooter>
			</Card>

			<Card className="scwa-settings-card">
				<CardHeader>
					<h3>{ __( 'Admin Notifications', 'scwa' ) }</h3>
				</CardHeader>
				<CardBody>
					<TextControl
						label={ __( 'Admin WhatsApp Number', 'scwa' ) }
						value={ form.admin_phone || '' }
						onChange={ ( v ) => updateField( 'admin_phone', v ) }
						help={ __(
							'Receives new order alerts. Must include country code (e.g., +919001234567).',
							'scwa'
						) }
					/>
				</CardBody>
				<CardFooter className="scwa-settings-card__footer">
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ __( 'Save Settings', 'scwa' ) }
					</Button>
				</CardFooter>
			</Card>

			<Card className="scwa-settings-card">
				<CardHeader>
					<h3>{ __( 'Notification Events', 'scwa' ) }</h3>
				</CardHeader>
				<CardBody>
					<NotificationToggle
						label={ __(
							'Order Confirmed \u2192 Customer',
							'scwa'
						) }
						description={ __(
							'WhatsApp sent when checkout is confirmed.',
							'scwa'
						) }
						checked={ form.enable_order_confirmed === '1' }
						onChange={ ( v ) =>
							updateField(
								'enable_order_confirmed',
								v ? '1' : '0'
							)
						}
					/>
					<NotificationToggle
						label={ __(
							'Fulfillment Created \u2192 Customer',
							'scwa'
						) }
						description={ __(
							'WhatsApp sent when order is shipped/fulfilled.',
							'scwa'
						) }
						checked={ form.enable_fulfillment_created === '1' }
						onChange={ ( v ) =>
							updateField(
								'enable_fulfillment_created',
								v ? '1' : '0'
							)
						}
					/>
					<NotificationToggle
						label={ __(
							'Refund Issued \u2192 Customer',
							'scwa'
						) }
						description={ __(
							'WhatsApp sent when a refund is processed.',
							'scwa'
						) }
						checked={ form.enable_refund_created === '1' }
						onChange={ ( v ) =>
							updateField(
								'enable_refund_created',
								v ? '1' : '0'
							)
						}
					/>
					<NotificationToggle
						label={ __(
							'New Order \u2192 Admin Alert',
							'scwa'
						) }
						description={ __(
							'WhatsApp sent to admin phone on new orders.',
							'scwa'
						) }
						checked={ form.enable_admin_new_order === '1' }
						onChange={ ( v ) =>
							updateField(
								'enable_admin_new_order',
								v ? '1' : '0'
							)
						}
					/>
				</CardBody>
				<CardFooter className="scwa-settings-card__footer">
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ __( 'Save Settings', 'scwa' ) }
					</Button>
				</CardFooter>
			</Card>
		</div>
	);
}
