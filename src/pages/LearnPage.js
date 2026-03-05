/**
 * LearnPage — step-by-step setup guide with progress tracking.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Notice, ExternalLink } from '@wordpress/components';
import useSettings from '../hooks/useSettings';
import SetupStep from '../components/SetupStep';

const STORAGE_KEY = 'scwa_learn_progress';
const TOTAL_STEPS = 8;

function useLearnProgress() {
	const [ completed, setCompleted ] = useState( () => {
		try {
			const stored = localStorage.getItem( STORAGE_KEY );
			return stored ? JSON.parse( stored ) : {};
		} catch {
			return {};
		}
	} );

	const toggleStep = useCallback( ( step, value ) => {
		setCompleted( ( prev ) => {
			const next = { ...prev, [ step ]: value };
			try {
				localStorage.setItem( STORAGE_KEY, JSON.stringify( next ) );
			} catch {
				// Private browsing — silently ignore.
			}
			return next;
		} );
	}, [] );

	const completedCount = Object.values( completed ).filter( Boolean ).length;

	return { completed, toggleStep, completedCount };
}

export default function LearnPage( { onNavigate } ) {
	const { completed, toggleStep, completedCount } = useLearnProgress();
	const { settings } = useSettings();

	const apiConfigured = !! settings?.api_access_token_set;

	// Auto-detect steps 4 & 5.
	useEffect( () => {
		if ( apiConfigured ) {
			if ( ! completed[ 4 ] ) {
				toggleStep( 4, true );
			}
			if ( ! completed[ 5 ] ) {
				toggleStep( 5, true );
			}
		}
	}, [ apiConfigured ] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Accordion: one step open at a time; first incomplete step auto-opens.
	const firstIncomplete = Array.from( { length: TOTAL_STEPS }, ( _, i ) => i + 1 ).find(
		( n ) => ! completed[ n ]
	) || 1;
	const [ openStep, setOpenStep ] = useState( firstIncomplete );

	const handleToggleOpen = ( step ) => {
		setOpenStep( ( prev ) => ( prev === step ? null : step ) );
	};

	const progressPercent = Math.round( ( completedCount / TOTAL_STEPS ) * 100 );

	return (
		<div className="scwa-learn">
			<div className="scwa-learn-progress">
				<p className="scwa-learn-progress__text">
					{ completedCount } { __( 'of', 'scwa' ) } { TOTAL_STEPS }{ ' ' }
					{ __( 'steps completed', 'scwa' ) }
				</p>
				<div className="scwa-learn-progress__bar">
					<div
						className="scwa-learn-progress__fill"
						style={ { width: `${ progressPercent }%` } }
					/>
				</div>
			</div>

			{ /* Step 1 */ }
			<SetupStep
				number={ 1 }
				title={ __( 'Create a Meta (Facebook) App', 'scwa' ) }
				isComplete={ !! completed[ 1 ] }
				isOpen={ openStep === 1 }
				onToggleOpen={ () => handleToggleOpen( 1 ) }
				onToggleComplete={ ( v ) => toggleStep( 1, v ) }
			>
				<p>
					{ __( 'Go to the Meta for Developers site and create a new app.', 'scwa' ) }
				</p>
				<Notice status="info" isDismissible={ false }>
					{ __( 'You need a personal Facebook account to access the developer portal.', 'scwa' ) }
				</Notice>
				<Notice status="warning" isDismissible={ false }>
					{ __( 'Select the "Business" app type. Other types do not include the WhatsApp product.', 'scwa' ) }
				</Notice>
				<div className="scwa-setup-step__actions">
					<ExternalLink href="https://developers.facebook.com/apps/create/">
						{ __( 'Open Meta Developer Portal', 'scwa' ) }
					</ExternalLink>
				</div>
			</SetupStep>

			{ /* Step 2 */ }
			<SetupStep
				number={ 2 }
				title={ __( 'Set Up WhatsApp Business API', 'scwa' ) }
				isComplete={ !! completed[ 2 ] }
				isOpen={ openStep === 2 }
				onToggleOpen={ () => handleToggleOpen( 2 ) }
				onToggleComplete={ ( v ) => toggleStep( 2, v ) }
			>
				<p>
					{ __( 'In your Meta app dashboard, add the WhatsApp product. Then link or create a WhatsApp Business Account.', 'scwa' ) }
				</p>
				<Notice status="info" isDismissible={ false }>
					{ __( 'Meta provides a free test phone number you can use during development.', 'scwa' ) }
				</Notice>
			</SetupStep>

			{ /* Step 3 */ }
			<SetupStep
				number={ 3 }
				title={ __( 'Get Your API Credentials', 'scwa' ) }
				isComplete={ !! completed[ 3 ] }
				isOpen={ openStep === 3 }
				onToggleOpen={ () => handleToggleOpen( 3 ) }
				onToggleComplete={ ( v ) => toggleStep( 3, v ) }
			>
				<p>
					{ __( 'From the WhatsApp > API Setup page in your Meta app, copy the following:', 'scwa' ) }
				</p>
				<ul>
					<li><strong>{ __( 'Phone Number ID', 'scwa' ) }</strong></li>
					<li><strong>{ __( 'WhatsApp Business Account ID', 'scwa' ) }</strong></li>
					<li><strong>{ __( 'Permanent Access Token', 'scwa' ) }</strong> — { __( 'create via System Users in Business Settings', 'scwa' ) }</li>
				</ul>
				<Notice status="warning" isDismissible={ false }>
					{ __( 'The temporary token from the API Setup page expires in 24 hours. Always create a permanent token via System Users for production use.', 'scwa' ) }
				</Notice>
			</SetupStep>

			{ /* Step 4 */ }
			<SetupStep
				number={ 4 }
				title={ __( 'Configure Plugin Settings', 'scwa' ) }
				isComplete={ !! completed[ 4 ] }
				isOpen={ openStep === 4 }
				onToggleOpen={ () => handleToggleOpen( 4 ) }
				onToggleComplete={ ( v ) => toggleStep( 4, v ) }
				autoComplete={ apiConfigured }
			>
				<p>
					{ __( 'Paste your Phone Number ID, Business Account ID, and Access Token into the Settings tab.', 'scwa' ) }
				</p>
				{ apiConfigured && (
					<Notice status="success" isDismissible={ false }>
						{ __( 'API credentials detected — this step is complete.', 'scwa' ) }
					</Notice>
				) }
				<div className="scwa-setup-step__actions">
					<Button
						variant="secondary"
						onClick={ () => onNavigate?.( 'settings' ) }
					>
						{ __( 'Go to Settings', 'scwa' ) }
					</Button>
				</div>
			</SetupStep>

			{ /* Step 5 */ }
			<SetupStep
				number={ 5 }
				title={ __( 'Test Your Connection', 'scwa' ) }
				isComplete={ !! completed[ 5 ] }
				isOpen={ openStep === 5 }
				onToggleOpen={ () => handleToggleOpen( 5 ) }
				onToggleComplete={ ( v ) => toggleStep( 5, v ) }
				autoComplete={ apiConfigured }
			>
				<p>
					{ __( 'Click the "Test Connection" button in the Settings tab to verify your credentials work.', 'scwa' ) }
				</p>
				<Notice status="warning" isDismissible={ false }>
					{ __( 'The most common issue is an expired temporary token. If the test fails, check that you are using a permanent token.', 'scwa' ) }
				</Notice>
				{ apiConfigured && (
					<Notice status="success" isDismissible={ false }>
						{ __( 'API credentials detected — this step is complete.', 'scwa' ) }
					</Notice>
				) }
				<div className="scwa-setup-step__actions">
					<Button
						variant="secondary"
						onClick={ () => onNavigate?.( 'settings' ) }
					>
						{ __( 'Go to Settings', 'scwa' ) }
					</Button>
				</div>
			</SetupStep>

			{ /* Step 6 */ }
			<SetupStep
				number={ 6 }
				title={ __( 'Add Phone Block to Checkout', 'scwa' ) }
				isComplete={ !! completed[ 6 ] }
				isOpen={ openStep === 6 }
				onToggleOpen={ () => handleToggleOpen( 6 ) }
				onToggleComplete={ ( v ) => toggleStep( 6, v ) }
			>
				<p>
					{ __( 'In the SureCart form editor, add the built-in Phone block to your checkout form and enable the Required toggle.', 'scwa' ) }
				</p>
				<ol>
					<li>{ __( 'Open the SureCart form editor for your checkout form.', 'scwa' ) }</li>
					<li>{ __( 'Add the Phone block from the block inserter.', 'scwa' ) }</li>
					<li>{ __( 'In the block settings, enable the Required toggle.', 'scwa' ) }</li>
					<li>{ __( 'Save the form.', 'scwa' ) }</li>
				</ol>
				<Notice status="info" isDismissible={ false }>
					{ __( 'Without this block, customers will not have a phone number on file and will not receive WhatsApp messages.', 'scwa' ) }
				</Notice>
			</SetupStep>

			{ /* Step 7 */ }
			<SetupStep
				number={ 7 }
				title={ __( 'Set Up Admin Phone & Enable Notifications', 'scwa' ) }
				isComplete={ !! completed[ 7 ] }
				isOpen={ openStep === 7 }
				onToggleOpen={ () => handleToggleOpen( 7 ) }
				onToggleComplete={ ( v ) => toggleStep( 7, v ) }
			>
				<p>
					{ __( 'Enter your admin WhatsApp number (with country code, e.g. +919001234567) and toggle on the notification events you want to receive.', 'scwa' ) }
				</p>
				<div className="scwa-setup-step__actions">
					<Button
						variant="secondary"
						onClick={ () => onNavigate?.( 'settings' ) }
					>
						{ __( 'Go to Settings', 'scwa' ) }
					</Button>
				</div>
			</SetupStep>

			{ /* Step 8 */ }
			<SetupStep
				number={ 8 }
				title={ __( 'Customize Templates & Send Test', 'scwa' ) }
				isComplete={ !! completed[ 8 ] }
				isOpen={ openStep === 8 }
				onToggleOpen={ () => handleToggleOpen( 8 ) }
				onToggleComplete={ ( v ) => toggleStep( 8, v ) }
			>
				<p>
					{ __( 'Edit message templates in the Templates tab. Then send a test message from Settings to verify everything works end to end.', 'scwa' ) }
				</p>
				<Notice status="warning" isDismissible={ false }>
					{ __( "The test uses Meta's built-in 'hello_world' template. Custom templates are used for real orders.", 'scwa' ) }
				</Notice>
				<div className="scwa-setup-step__actions">
					<Button
						variant="secondary"
						onClick={ () => onNavigate?.( 'templates' ) }
					>
						{ __( 'Go to Templates', 'scwa' ) }
					</Button>
					<Button
						variant="secondary"
						onClick={ () => onNavigate?.( 'settings' ) }
					>
						{ __( 'Go to Settings', 'scwa' ) }
					</Button>
				</div>
			</SetupStep>
		</div>
	);
}
