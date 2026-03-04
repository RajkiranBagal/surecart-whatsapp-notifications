/**
 * TemplateEditor component — textarea + variable chips + live preview.
 */
import {
	TextareaControl,
	TextControl,
	RadioControl,
	Button,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';

const SAMPLE_DATA = {
	customer_name: 'John Doe',
	customer_first_name: 'John',
	customer_email: 'john@example.com',
	order_number: 'SC-1234',
	order_total: '$99.00',
	currency: 'USD',
	store_name: window.scwaAdmin?.storeName || 'My Store',
	checkout_url: window.location.origin,
	tracking_number: 'TRK-987654321',
	tracking_url: 'https://tracking.example.com/987654321',
	refund_amount: '$49.00',
};

export default function TemplateEditor( {
	template,
	variables,
	onSave,
	isSaving,
} ) {
	const [ templateType, setTemplateType ] = useState(
		template?.template_type || 'text'
	);
	const [ messageBody, setMessageBody ] = useState(
		template?.message_body || ''
	);
	const [ metaName, setMetaName ] = useState(
		template?.meta_template_name || ''
	);
	const [ metaLang, setMetaLang ] = useState(
		template?.meta_template_lang || 'en'
	);
	const textareaRef = useRef( null );

	useEffect( () => {
		if ( template ) {
			setTemplateType( template.template_type || 'text' );
			setMessageBody( template.message_body || '' );
			setMetaName( template.meta_template_name || '' );
			setMetaLang( template.meta_template_lang || 'en' );
		}
	}, [ template ] );

	const insertVariable = ( variable ) => {
		const tag = `{{${ variable }}}`;
		const textarea = textareaRef.current?.querySelector( 'textarea' );
		if ( textarea ) {
			const start = textarea.selectionStart;
			const end = textarea.selectionEnd;
			const newValue =
				messageBody.substring( 0, start ) +
				tag +
				messageBody.substring( end );
			setMessageBody( newValue );
			// Restore cursor after the inserted tag.
			setTimeout( () => {
				textarea.focus();
				textarea.setSelectionRange(
					start + tag.length,
					start + tag.length
				);
			}, 0 );
		} else {
			setMessageBody( messageBody + tag );
		}
	};

	const renderPreview = () => {
		let preview = messageBody;
		Object.entries( SAMPLE_DATA ).forEach( ( [ key, value ] ) => {
			preview = preview.replace(
				new RegExp( `\\{\\{${ key }\\}\\}`, 'g' ),
				value
			);
		} );
		return preview;
	};

	const handleSave = () => {
		onSave( {
			event_type: template.event_type,
			recipient_type: template.recipient_type,
			template_type: templateType,
			message_body: messageBody,
			meta_template_name: metaName,
			meta_template_lang: metaLang,
			is_enabled: template.is_enabled,
		} );
	};

	if ( ! template ) {
		return null;
	}

	const eventLabels = {
		checkout_confirmed: __( 'Order Confirmed', 'scwa' ),
		fulfillment_created: __( 'Fulfillment Created', 'scwa' ),
		refund_created: __( 'Refund Issued', 'scwa' ),
		admin_new_order: __( 'Admin: New Order', 'scwa' ),
	};

	return (
		<div className="scwa-template-editor__content">
			<h3>
				{ __( 'Template:', 'scwa' ) }{ ' ' }
				{ eventLabels[ template.event_type ] || template.event_type }
			</h3>
			<p className="scwa-template-editor__recipient">
				{ __( 'Recipient:', 'scwa' ) }{ ' ' }
				{ template.recipient_type === 'admin'
					? __( 'Admin', 'scwa' )
					: __( 'Customer', 'scwa' ) }
			</p>

			<RadioControl
				label={ __( 'Template Type', 'scwa' ) }
				selected={ templateType }
				options={ [
					{
						label: __( 'Text Message', 'scwa' ),
						value: 'text',
					},
					{
						label: __( 'Meta Pre-Approved Template', 'scwa' ),
						value: 'meta_template',
					},
				] }
				onChange={ setTemplateType }
			/>

			{ templateType === 'text' ? (
				<>
					<div ref={ textareaRef }>
						<TextareaControl
							label={ __( 'Message Body', 'scwa' ) }
							value={ messageBody }
							onChange={ setMessageBody }
							rows={ 8 }
						/>
					</div>

					<div className="scwa-variable-chips">
						<p className="scwa-variable-chips__label">
							{ __(
								'Available Variables (click to insert):',
								'scwa'
							) }
						</p>
						<div className="scwa-variable-chips__list">
							{ ( variables || [] ).map( ( v ) => (
								<button
									key={ v }
									type="button"
									className="scwa-variable-chip"
									onClick={ () => insertVariable( v ) }
								>
									{ `{{${ v }}}` }
								</button>
							) ) }
						</div>
					</div>

					<div className="scwa-preview">
						<p className="scwa-preview__label">
							{ __( 'Live Preview', 'scwa' ) }
						</p>
						<div className="scwa-preview-bubble">
							{ renderPreview()
								.split( '\n' )
								.map( ( line, i ) => (
									<span key={ i }>
										{ line }
										<br />
									</span>
								) ) }
						</div>
					</div>
				</>
			) : (
				<>
					<TextControl
						label={ __( 'Template Name', 'scwa' ) }
						value={ metaName }
						onChange={ setMetaName }
						help={ __(
							'The exact name of your pre-approved Meta template.',
							'scwa'
						) }
					/>
					<TextControl
						label={ __( 'Language Code', 'scwa' ) }
						value={ metaLang }
						onChange={ setMetaLang }
						help={ __( 'e.g., en, en_US, hi', 'scwa' ) }
					/>
				</>
			) }

			<div className="scwa-template-editor__actions">
				<Button
					variant="primary"
					onClick={ handleSave }
					disabled={ isSaving }
				>
					{ isSaving && <Spinner /> }
					{ __( 'Save Template', 'scwa' ) }
				</Button>
			</div>
		</div>
	);
}
