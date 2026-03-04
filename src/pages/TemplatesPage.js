/**
 * TemplatesPage — split-panel: event list + template editor.
 */
import { Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { fetchTemplates, saveTemplate as apiSaveTemplate } from '../api';
import TemplateEditor from '../components/TemplateEditor';
import EmptyState from '../components/EmptyState';

const EVENT_LABELS = {
	checkout_confirmed: __( 'Order Confirmed', 'scwa' ),
	fulfillment_created: __( 'Fulfillment Created', 'scwa' ),
	refund_created: __( 'Refund Issued', 'scwa' ),
	admin_new_order: __( 'Admin: New Order', 'scwa' ),
};

export default function TemplatesPage() {
	const [ templates, setTemplates ] = useState( [] );
	const [ variables, setVariables ] = useState( {} );
	const [ selectedEvent, setSelectedEvent ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	const load = useCallback( async () => {
		setIsLoading( true );
		try {
			const data = await fetchTemplates();
			setTemplates( data.templates || [] );
			setVariables( data.variables || {} );
			if ( data.templates?.length > 0 && ! selectedEvent ) {
				setSelectedEvent( data.templates[ 0 ].event_type );
			}
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Failed to load templates.', 'scwa' ),
			} );
		} finally {
			setIsLoading( false );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		load();
	}, [ load ] );

	const handleSave = async ( data ) => {
		setIsSaving( true );
		setNotice( null );
		try {
			const result = await apiSaveTemplate( data );
			setNotice( {
				type: result.success ? 'success' : 'error',
				message: result.message,
			} );
			if ( result.success ) {
				await load();
			}
		} catch {
			setNotice( {
				type: 'error',
				message: __( 'Failed to save template.', 'scwa' ),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	if ( isLoading ) {
		return (
			<div className="scwa-loading">
				<Spinner />
			</div>
		);
	}

	if ( templates.length === 0 ) {
		return (
			<EmptyState
				icon="\uD83D\uDCDD"
				title={ __( 'No templates found', 'scwa' ) }
				description={ __(
					'Templates will be created automatically when you activate the plugin.',
					'scwa'
				) }
			/>
		);
	}

	const selectedTemplate = templates.find(
		( t ) => t.event_type === selectedEvent
	);
	const eventVariables = variables[ selectedEvent ] || [];

	return (
		<div className="scwa-templates-page">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<p className="scwa-templates-page__intro">
				{ __(
					'Select an event to edit its message template:',
					'scwa'
				) }
			</p>

			<div className="scwa-template-editor">
				<div className="scwa-template-editor__nav">
					{ templates.map( ( t ) => (
						<button
							key={ t.event_type }
							type="button"
							className={ `scwa-template-editor__nav-item ${
								selectedEvent === t.event_type
									? 'scwa-template-editor__nav-item--active'
									: ''
							}` }
							onClick={ () =>
								setSelectedEvent( t.event_type )
							}
						>
							{ selectedEvent === t.event_type
								? '\u25B8 '
								: '' }
							{ EVENT_LABELS[ t.event_type ] || t.event_type }
						</button>
					) ) }
				</div>

				<TemplateEditor
					template={ selectedTemplate }
					variables={ eventVariables }
					onSave={ handleSave }
					isSaving={ isSaving }
				/>
			</div>
		</div>
	);
}
