/**
 * Hook: useLogs — fetch paginated logs with filters.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { fetchLogs as apiFetchLogs } from '../api';

export default function useLogs( initialFilters = {} ) {
	const [ logs, setLogs ] = useState( [] );
	const [ total, setTotal ] = useState( 0 );
	const [ pages, setPages ] = useState( 0 );
	const [ page, setPage ] = useState( 1 );
	const [ filters, setFilters ] = useState( initialFilters );
	const [ isLoading, setIsLoading ] = useState( true );

	const load = useCallback( async () => {
		setIsLoading( true );
		try {
			const params = {
				page,
				per_page: 20,
				...filters,
			};
			// Remove empty params.
			Object.keys( params ).forEach( ( key ) => {
				if ( params[ key ] === '' || params[ key ] === undefined ) {
					delete params[ key ];
				}
			} );

			const data = await apiFetchLogs( params );
			setLogs( data.logs || [] );
			setTotal( data.total || 0 );
			setPages( data.pages || 0 );
		} catch {
			setLogs( [] );
		} finally {
			setIsLoading( false );
		}
	}, [ page, filters ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const updateFilters = useCallback(
		( newFilters ) => {
			setFilters( ( prev ) => ( { ...prev, ...newFilters } ) );
			setPage( 1 );
		},
		[]
	);

	return {
		logs,
		total,
		pages,
		page,
		setPage,
		filters,
		updateFilters,
		isLoading,
		refresh: load,
	};
}
