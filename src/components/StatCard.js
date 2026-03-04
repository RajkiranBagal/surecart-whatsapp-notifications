/**
 * StatCard component — metric card with count, trend, and color accent.
 */
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function StatCard( { icon, label, count, trend, color } ) {
	const trendClass =
		trend > 0 ? 'scwa-trend--up' : trend < 0 ? 'scwa-trend--down' : '';
	const trendSymbol = trend > 0 ? '▲' : trend < 0 ? '▼' : '—';

	return (
		<div className={ `scwa-stat-card scwa-stat-card--${ color }` }>
			<div className="scwa-stat-card__icon">
				<Icon icon={ icon } size={ 28 } />
			</div>
			<div className="scwa-stat-card__count">{ count }</div>
			<div className="scwa-stat-card__label">{ label }</div>
			{ trend !== undefined && (
				<div className={ `scwa-stat-card__trend ${ trendClass }` }>
					{ trendSymbol } { Math.abs( trend ) }%{ ' ' }
					{ __( '/7d', 'scwa' ) }
				</div>
			) }
		</div>
	);
}
