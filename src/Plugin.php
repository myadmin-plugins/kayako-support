<?php

namespace Detain\MyAdminKayako;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminKayako
 */
class Plugin {

	public static $name = 'Kayako Plugin';
	public static $description = 'Allows handling of Kayako Ticket Support/Helpdesk System';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
					if (has_acl('client_billing'))
							$menu->add_link('admin', 'choice=none.abuse_admin', '/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Kayako');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.Kayako', '/../vendor/detain/myadmin-kayako-support/src/Kayako.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-kayako-support/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-kayako-support/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-kayako-support/src/abuse.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting('Support', 'Kayako', 'kayako_api_url', 'Kayako API URL:', 'Kayako API URL', KAYAKO_API_URL);
		$settings->add_text_setting('Support', 'Kayako', 'kayako_api_key', 'Kayako API Key:', 'Kayako API Key', KAYAKO_API_KEY);
		$settings->add_text_setting('Support', 'Kayako', 'kayako_api_secret', 'Kayako API Secret:', 'Kayako API Secret', KAYAKO_API_SECRET);
	}

}
