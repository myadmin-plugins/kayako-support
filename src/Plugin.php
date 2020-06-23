<?php

namespace Detain\MyAdminKayako;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminKayako
 */
class Plugin
{
	public static $name = 'Kayako Plugin';
	public static $description = 'Allows handling of Kayako Ticket Support/Helpdesk System';
	public static $help = '';
	public static $type = 'plugin';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			'api.register' => [__CLASS__, 'apiRegister'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			'system.settings' => [__CLASS__, 'getSettings'],
			//'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function apiRegister(GenericEvent $event)
	{
		/**
		 * @var \ServiceHandler $subject
		 */
		//$subject = $event->getSubject();
        api_register_array_array('getTicketList_tickets', 'getTicketList_ticket');
		api_register_array('getTicketList_ticket', ['ticket_id' => 'string', 'ticket_reference_id' => 'string', 'subject' => 'string', 'lastreplier' => 'string', 'statustitle' => 'string', 'prioritytitle' => 'string', 'replies' => 'string', 'lastactivity' => 'string']);
		api_register_array('getTicketList_return', ['status' => 'string', 'status_text' => 'string', 'totalPages' => 'string', 'tickets' => 'tns:getTicketList_tickets']);
		api_register_array('openTicket_return', ['status' => 'string', 'status_text' => 'string', 'ticket_reference_id' => 'int']);
		api_register_array_array('postsArray', 'postsDetail');
        api_register_array('postsDetail', ['email' => 'string', 'full_name' => 'string', 'dateline' => 'string', 'contents' => 'string']);
		api_register_array('view_ticketdetail_array', ['ticket_reference_id' => 'string', 'full_name' => 'string', 'email' => 'string', 'subject' => 'string', 'creationtime' => 'string', 'statustitle' => 'string', 'prioritytitle' => 'string', 'lastactivity' => 'string', 'posts' => 'postsArray']);
		api_register_array('view_ticket_return', ['status' => 'string', 'status_text' => 'string', 'result' => 'tns:view_ticketdetail_array']);
		api_register_array('ticket_post_return', ['status' => 'string', 'status_text' => 'string']);
		api_register('getTicketList', ['page' =>'int', 'limit' => 'int', 'status' => 'string'], ['return' => 'getTicketList_return'], 'Returns a list of any tickets in the system.');
		api_register('openTicket', ['user_email' => 'string', 'user_ip' => 'string', 'subject' => 'string', 'product' => 'string', 'body' => 'string', 'box_auth_value' => 'string'], ['return' => 'openTicket_return'], 'This command creates a new ticket in our system.');
		api_register('viewTicket', ['ticketID' => 'string'], ['return' => 'view_ticket_return'], 'View/Retrieve information about the given ticketID.');
		api_register('ticketPost', ['ticketID' => 'string', 'content' => 'string'], ['return' => 'ticket_post_return'], 'This commands adds the content parameter as a response/reply to an existing ticket specified by ticketID.');
		//api_multi_register('getTicketList', ['sid' => 'string', 'user_email' => 'string'], ['return' => 'tns:result_status'], 'Gets ticket list');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			function_requirements('has_acl');
			if (has_acl('client_billing')) {
			}
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_requirement('class.Kayako', '/../vendor/detain/myadmin-kayako-support/src/Kayako.php');
		$loader->add_requirement('deactivate_kcare', '/../vendor/detain/myadmin-kayako-support/src/abuse.inc.php');
		$loader->add_requirement('deactivate_abuse', '/../vendor/detain/myadmin-kayako-support/src/abuse.inc.php');
		$loader->add_requirement('get_abuse_licenses', '/../vendor/detain/myadmin-kayako-support/src/abuse.inc.php');
		$loader->add_requirement('openTicket', '/../vendor/detain/myadmin-kayako-support/src/api.php');
		$loader->add_requirement('getTicketList', '/../vendor/detain/myadmin-kayako-support/src/api.php');
		$loader->add_requirement('viewTicket', '/../vendor/detain/myadmin-kayako-support/src/api.php');
		$loader->add_requirement('ticketPost', '/../vendor/detain/myadmin-kayako-support/src/api.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_text_setting(_('Support'), _('Kayako'), 'kayako_api_url', _('Kayako API URL'), _('Kayako API URL'), KAYAKO_API_URL);
		$settings->add_text_setting(_('Support'), _('Kayako'), 'kayako_api_key', _('Kayako API Key'), _('Kayako API Key'), KAYAKO_API_KEY);
		$settings->add_text_setting(_('Support'), _('Kayako'), 'kayako_api_secret', _('Kayako API Secret'), _('Kayako API Secret'), KAYAKO_API_SECRET);
	}
}
