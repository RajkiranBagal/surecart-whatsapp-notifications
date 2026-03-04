/**
 * SureCart WhatsApp Notifications — Admin Entry Point.
 */
import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/admin.scss';

const root = document.getElementById( 'scwa-admin-root' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
