/**
 * EmptyState component — centered empty state with optional CTA.
 */
import { Button } from '@wordpress/components';

export default function EmptyState( {
	icon,
	title,
	description,
	ctaLabel,
	onCtaClick,
} ) {
	return (
		<div className="scwa-empty-state">
			{ icon && <div className="scwa-empty-state__icon">{ icon }</div> }
			<h3 className="scwa-empty-state__title">{ title }</h3>
			{ description && (
				<p className="scwa-empty-state__description">{ description }</p>
			) }
			{ ctaLabel && onCtaClick && (
				<Button variant="primary" onClick={ onCtaClick }>
					{ ctaLabel }
				</Button>
			) }
		</div>
	);
}
