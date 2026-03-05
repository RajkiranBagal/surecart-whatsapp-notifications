/**
 * SetupStep — collapsible accordion step for the Learn page.
 */
import { __ } from '@wordpress/i18n';
import { chevronDown, check } from '@wordpress/icons';
import { Icon } from '@wordpress/components';

export default function SetupStep( {
	number,
	title,
	isComplete,
	isOpen,
	onToggleOpen,
	onToggleComplete,
	autoComplete,
	children,
} ) {
	const modifiers = [
		'scwa-setup-step',
		isComplete && 'scwa-setup-step--complete',
		isOpen && 'scwa-setup-step--open',
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div className={ modifiers }>
			<button
				type="button"
				className="scwa-setup-step__header"
				onClick={ onToggleOpen }
				aria-expanded={ isOpen }
			>
				<span className="scwa-setup-step__number">
					{ isComplete ? (
						<Icon icon={ check } size={ 16 } />
					) : (
						number
					) }
				</span>
				<span className="scwa-setup-step__title">{ title }</span>
				<span className="scwa-setup-step__chevron">
					<Icon icon={ chevronDown } size={ 20 } />
				</span>
			</button>
			<div
				className="scwa-setup-step__body"
				style={ {
					maxHeight: isOpen ? '600px' : '0',
				} }
			>
				<div className="scwa-setup-step__content">
					{ children }
					{ ! autoComplete && (
						<label className="scwa-setup-step__checkbox">
							<input
								type="checkbox"
								checked={ isComplete }
								onChange={ ( e ) =>
									onToggleComplete( e.target.checked )
								}
							/>
							{ __( 'Mark as complete', 'scwa' ) }
						</label>
					) }
				</div>
			</div>
		</div>
	);
}
