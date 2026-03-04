/**
 * REST API wrappers.
 */
import apiFetch from '@wordpress/api-fetch';

const BASE = 'scwa/v1';

export const fetchSettings = () =>
	apiFetch( { path: `${ BASE }/settings` } );

export const saveSettings = ( data ) =>
	apiFetch( {
		path: `${ BASE }/settings`,
		method: 'POST',
		data,
	} );

export const testConnection = () =>
	apiFetch( {
		path: `${ BASE }/test-connection`,
		method: 'POST',
	} );

export const sendTestMessage = () =>
	apiFetch( {
		path: `${ BASE }/test-message`,
		method: 'POST',
	} );

export const fetchStats = () =>
	apiFetch( { path: `${ BASE }/stats` } );

export const fetchLogs = ( params = {} ) => {
	const query = new URLSearchParams( params ).toString();
	return apiFetch( { path: `${ BASE }/logs?${ query }` } );
};

export const resendNotification = ( id ) =>
	apiFetch( {
		path: `${ BASE }/logs/${ id }/resend`,
		method: 'POST',
	} );

export const fetchTemplates = () =>
	apiFetch( { path: `${ BASE }/templates` } );

export const saveTemplate = ( data ) =>
	apiFetch( {
		path: `${ BASE }/templates`,
		method: 'POST',
		data,
	} );
