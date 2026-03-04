/**
 * StatCard component — metric card with count, trend, and color accent.
 */
import { __ } from '@wordpress/i18n';

export default function StatCard( { icon, label, count, trend, color } ) {
	const trendClass =
		trend > 0 ? 'scwa-trend--up' : trend < 0 ? 'scwa-trend--down' : '';
	const trendIcon = trend > 0 ? '\u25B2' : trend < 0 ? '\u25BC' : '\u2014';

	return (
		<div className={ `scwa-stat-card scwa-stat-card--${ color }` }>
			<div className="scwa-stat-card__icon">{ icon }</div>
			<div className="scwa-stat-card__count">{ count }</div>
			<div className="scwa-stat-card__label">{ label }</div>
			{ trend !== undefined && (
				<div className={ `scwa-stat-card__trend ${ trendClass }` }>
					{ trendIcon } { Math.abs( trend ) }%{ ' ' }
					{ __( '/7d', 'scwa' ) }
				</div>
			) }
		</div>
	);
}
