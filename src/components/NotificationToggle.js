/**
 * NotificationToggle component — toggle switch with label and description.
 */
import { ToggleControl } from '@wordpress/components';

export default function NotificationToggle( {
	label,
	description,
	checked,
	onChange,
} ) {
	return (
		<div className="scwa-notification-toggle">
			<ToggleControl
				label={ label }
				help={ description }
				checked={ checked }
				onChange={ onChange }
			/>
		</div>
	);
}
