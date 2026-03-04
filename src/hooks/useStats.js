/**
 * Hook: useStats — fetch dashboard statistics.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { fetchStats as apiFetchStats } from '../api';

export default function useStats() {
	const [ stats, setStats ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );

	const load = useCallback( async () => {
		setIsLoading( true );
		try {
			const data = await apiFetchStats();
			setStats( data );
		} catch {
			// Silently fail — dashboard will show empty state.
		} finally {
			setIsLoading( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	return { stats, isLoading, refresh: load };
}
