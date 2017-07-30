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
			//'system.settings' => [__CLASS__, 'getSettings'],
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
							$menu->add_link('admin', 'choice=none.abuse_admin', '//my.interserver.net/bower_components/webhostinghub-glyphs-icons/icons/development-16/Black/icon-spam.png', 'Kayako');
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
		$settings->add_text_setting('General', 'Kayako', 'abuse_imap_user', 'Kayako IMAP User:', 'Kayako IMAP Username', ABUSE_IMAP_USER);
		$settings->add_text_setting('General', 'Kayako', 'abuse_imap_pass', 'Kayako IMAP Pass:', 'Kayako IMAP Password', ABUSE_IMAP_PASS);
	}

}
