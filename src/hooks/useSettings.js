/**
 * Hook: useSettings — fetch/save settings, test connection, send test message.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	fetchSettings as apiFetchSettings,
	saveSettings as apiSaveSettings,
	testConnection as apiTestConnection,
	sendTestMessage as apiSendTestMessage,
} from '../api';

export default function useSettings() {
	const [ settings, setSettings ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	const load = useCallback( async () => {
		setIsLoading( true );
		try {
			const data = await apiFetchSettings();
			setSettings( data );
		} catch {
			setNotice( { type: 'error', message: 'Failed to load settings.' } );
		} finally {
			setIsLoading( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const save = useCallback(
		async ( data ) => {
			setIsSaving( true );
			setNotice( null );
			try {
				await apiSaveSettings( data );
				setNotice( {
					type: 'success',
					message: 'Settings saved successfully.',
				} );
				// Reload to get masked token etc.
				await load();
			} catch {
				setNotice( {
					type: 'error',
					message: 'Failed to save settings.',
				} );
			} finally {
				setIsSaving( false );
			}
		},
		[ load ]
	);

	const testConn = useCallback( async () => {
		setNotice( null );
		try {
			const result = await apiTestConnection();
			setNotice( {
				type: result.success ? 'success' : 'error',
				message: result.message,
			} );
			return result;
		} catch {
			setNotice( {
				type: 'error',
				message: 'Connection test failed.',
			} );
			return { success: false };
		}
	}, [] );

	const testMsg = useCallback( async () => {
		setNotice( null );
		try {
			const result = await apiSendTestMessage();
			setNotice( {
				type: result.success ? 'success' : 'error',
				message: result.message,
			} );
			return result;
		} catch {
			setNotice( {
				type: 'error',
				message: 'Failed to send test message.',
			} );
			return { success: false };
		}
	}, [] );

	return {
		settings,
		setSettings,
		isLoading,
		isSaving,
		notice,
		setNotice,
		saveSettings: save,
		testConnection: testConn,
		sendTestMessage: testMsg,
	};
}
