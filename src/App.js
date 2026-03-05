/**
 * App — top-level router with TabPanel navigation.
 */
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import DashboardPage from './pages/DashboardPage';
import LearnPage from './pages/LearnPage';
import SettingsPage from './pages/SettingsPage';
import TemplatesPage from './pages/TemplatesPage';
import LogsPage from './pages/LogsPage';

const TABS = [
	{
		name: 'dashboard',
		title: __( 'Dashboard', 'scwa' ),
		className: 'scwa-tab',
	},
	{
		name: 'learn',
		title: __( 'Learn', 'scwa' ),
		className: 'scwa-tab',
	},
	{
		name: 'settings',
		title: __( 'Settings', 'scwa' ),
		className: 'scwa-tab',
	},
	{
		name: 'templates',
		title: __( 'Templates', 'scwa' ),
		className: 'scwa-tab',
	},
	{
		name: 'logs',
		title: __( 'Logs', 'scwa' ),
		className: 'scwa-tab',
	},
];

function getInitialTab() {
	const params = new URLSearchParams( window.location.search );
	const tab = params.get( 'tab' );
	if ( tab && TABS.some( ( t ) => t.name === tab ) ) {
		return tab;
	}
	return 'dashboard';
}

export default function App() {
	const [ activeTab, setActiveTab ] = useState( getInitialTab );

	const handleNavigate = useCallback( ( tabName ) => {
		setActiveTab( tabName );
	}, [] );

	const renderTab = ( tab ) => {
		switch ( tab.name ) {
			case 'dashboard':
				return <DashboardPage onNavigate={ handleNavigate } />;
			case 'learn':
				return <LearnPage onNavigate={ handleNavigate } />;
			case 'settings':
				return <SettingsPage />;
			case 'templates':
				return <TemplatesPage />;
			case 'logs':
				return <LogsPage />;
			default:
				return null;
		}
	};

	return (
		<div className="scwa-admin wrap">
			<h1 className="scwa-admin__title">
				{ __( 'WhatsApp Notifications', 'scwa' ) }
			</h1>
			<TabPanel
				className="scwa-tab-panel"
				tabs={ TABS }
				onSelect={ handleNavigate }
				initialTabName={ activeTab }
			>
				{ ( tab ) => (
					<div className="scwa-page scwa-page-enter-active">
						{ renderTab( tab ) }
					</div>
				) }
			</TabPanel>
		</div>
	);
}
