<?php
/**
* API Ticketing Functions
*
* @author Joe Huss <detain@interserver.net>
* @copyright 2019
* @package MyAdmin
* @category API
*/

/**
* creates a new ticket in our support system
*
* @param string $user_email client email address
* @param string $user_ip client ip address
* @param string $subject subject of the ticket
* @param string $product the product/service if any this is in reference to.
* @param string $body full content/description for the ticket
* @param string $box_auth_value encryption string?
* @return array returns an array containing the status/status_text result of adding a new ticket
*/
function openTicket($user_email, $user_ip, $subject, $product, $body, $box_auth_value)
{
	$result = [
		'status' => 'incomplete',
		'status_text' => '',
		'ticket_reference_id' => -1
	];
	if (!$user_email) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'User email is required. Please try again!';
		return $result;
	}
	if (!$user_ip) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'User IP is required. Please try again!';
		return $result;
	}
	if ($user_email && !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'User email is not valid. Please try again!';
		return $result;
	}
	if ($GLOBALS['tf']->ima != 'admin' && $GLOBALS['tf']->accounts->data['account_lid'] != $user_email) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Invalid Email Address - Does not match your account email!';
		return $result;
	}
	function_requirements('class.kyConfig');
	if ($product != '') {
		$body = "$product\n\n$body";
		if (mb_strlen($subject) < 30) {
			$subject = "$product : $subject";
		}
	}
	ini_set('default_socket_timeout', 600);
	try {
		function_requirements('class.kyConfig');
		kyConfig::set(new kyConfig(KAYAKO_API_URL, KAYAKO_API_KEY, KAYAKO_API_SECRET));
		kyConfig::get()->setDebugEnabled(false)->setTimeout(120);
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred setting config options. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		function_requirements('ticket_status_all');
		$default_status_id = ticket_status_all()->filterByTitle('Open')->first()->getId();
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred while getting default ticket status. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		function_requirements('ticket_priority_all');
		$default_priority_id = ticket_priority_all()->filterByTitle('Standard - 1 to 3 hour resolution')->first()->getId();
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred while getting default ticket resolution time. Please try again!';
		myadmin_log('api', 'info', json_encode($e), __LINE__, __FILE__);
		return $result;
	}
	try {
		function_requirements('ticket_type_all');
		$default_type_id = ticket_type_all()->filterByTitle('Issue')->first()->getId();
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred while getting default ticket type. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		kyTicket::setDefaults($default_status_id, $default_priority_id, $default_type_id);
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred while setting default settings for ticket. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		function_requirements('ticket_department_all');
		$kyDepartments = ticket_department_all();
		$department = $kyDepartments->filterByTitle('General')->first();
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred getting department. Please try again!';
		myadmin_log('api', 'info', json_encode($e), __LINE__, __FILE__);
		return $result;
	}
	try {
		$ticketResponse = kyTicket::createNewAuto($department, $user_email, $user_email, (string)$body, (string)$subject)->create();
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred creating ticket. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}

	if ($ticketResponse) {
		try {
			$box_auth_key = null;
			$ticketId = $ticketResponse->getDisplayId();
			$secretKey=KAYAKO_API_SECRET;
			$salt = mt_rand();
			$hashValue = hash_hmac('sha256', $salt, $secretKey, true);
			$signature = base64_encode($hashValue);
			$apiKey=KAYAKO_API_KEY;
			$api_url=KAYAKO_API_URL;
			if (!empty($box_auth_value)) {
				/*box authentication encrypt*/
				$box_auth_key = generateRandomString();
				$finalString = $box_auth_value.'+'.$box_auth_key;
				$box_auth_encrypted = $GLOBALS['tf']->encrypt($finalString);
				/*box authentication encrypt*/
				$post_data = null;
				$post_data = [
					'e'            => '/Tickets/TicketCustomField/' . $ticketId, 'qt2ks46z06b3' => $user_ip,
					'f8uv0tmehivo' => $box_auth_encrypted, '7rhmrc90ksht' => $box_auth_key, 'apikey' => $apiKey, 'salt' => $salt, 'signature' => $signature
				];
				$post_data = http_build_query($post_data, '', '&');
				$curl = curl_init($api_url);
				//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_URL, $api_url);
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
				curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
				$response = curl_exec($curl);//kayako response
				curl_close($curl);
				$result['status'] = 'success';
				$result['status_text'] = 'created ticket successfully.';
				$result['ticket_reference_id'] = $ticketId;
				return $result;
			} else {
				$result['status'] = 'success';
				$result['status_text'] = 'created ticket successfully.';
				$result['ticket_reference_id'] = $ticketId;
				return $result;
			}
		} catch (Exception $e) {
			$result['status'] = 'failed';
			$result['status_text'] = 'Kayako exception occurred updating root/admin pass. Please try again!';
			myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
			return $result;
		}
	} else {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako ticket creation failed';
		return $result;
	}
}

/**
* returns a paginated list of tickets
*
* @param int $page page number of tickets to list
* @param int $limit how many tickets to show per page
* @param null|string $status null for no status limit or limit to a specific status
* @return array returns an array containing the status/status_text and results
*/
function getTicketList($page =1, $limit = 10, $status = null)
{
	ini_set('default_socket_timeout', 3600);
	$module = 'vps';
	$error = false;
	$result = [
		'status' => 'incomplete',
		'status_text' => '',
		'tickets' => []
	];
	try {
		function_requirements('class.kyConfig');
		kyConfig::set(new kyConfig(KAYAKO_API_URL, KAYAKO_API_KEY, KAYAKO_API_SECRET));
		kyConfig::get()->setDebugEnabled(false)->setTimeout(120);
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred setting config options. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		function_requirements('ticket_status_all');
		$ticketStatuses = ticket_status_all();
		//myadmin_log('api', 'info', json_encode($ticketStatuses), __LINE__, __FILE__);
		foreach ($ticketStatuses as $ticketStatus) {
			$statusArray[$ticketStatus->id] = $ticketStatus->title;
			$new_status_array[$ticketStatus->title] = $ticketStatus->id;
		}
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred getting ticket statuses. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	//debug($statusArray);
	try {
		//myadmin_log('api', 'info', json_encode($statusArray), __LINE__, __FILE__);
		function_requirements('ticket_priority_all');
		$priorities = ticket_priority_all();
		//myadmin_log('api', 'info', json_encode($priorities), __LINE__, __FILE__);
		foreach ($priorities as $priority) {
			$priorityArray[$priority->id] = $priority->title;
		}
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred getting ticket priorities. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		$db = clone $GLOBALS['helpdesk_dbh'];
		$viewstatus = [4, 5, 6];
		//$filterByStatus = 'On Hold';
		$db->query("select * from swtickets where email='" . $db->real_escape($GLOBALS['tf']->accounts->data['account_lid']) . "' and ticketstatusid in (" . implode(',', $viewstatus) . ') ', __LINE__, __FILE__);
		$page_count = $db->num_rows() /$limit;
		$offset = ($page - 1) * $limit;
		//if(null === $status) {
		$db->query("select * from swtickets where email='" . $db->real_escape($GLOBALS['tf']->accounts->data['account_lid']) . "' and ticketstatusid in (" . implode(',', $viewstatus) . ') order by lastactivity desc limit '.$offset.', '.$limit.' ', __LINE__, __FILE__);
		//} else {
		//$db->query("select * from swtickets where email='" . $db->real_escape($GLOBALS['tf']->accounts->data['account_lid']) . "' and ticketstatusid = " . $new_status_array[$status] . ' order by lastactivity desc limit '.$offset.', '.$limit.' ', __LINE__, __FILE__);
		//}
		$idxV = 0;
		while ($db->next_record(MYSQL_ASSOC)) {
			$ticketArray[$idxV]['ticket_id'] = $db->Record['ticketid'];
			$ticketArray[$idxV]['ticket_reference_id'] = $db->Record['ticketmaskid'];
			$ticketArray[$idxV]['subject'] = $db->Record['subject'];
			$ticketArray[$idxV]['lastreplier'] = $db->Record['lastreplier'];
			$ticketArray[$idxV]['statustitle'] = $statusArray[$db->Record['ticketstatusid']];
			$ticketArray[$idxV]['prioritytitle'] = $priorityArray[$db->Record['priorityid']];
			$ticketArray[$idxV]['replies'] = $db->Record['repliestoresolution'];
			$ticketArray[$idxV]['lastactivity'] = $db->Record['lastactivity'];
			$idxV++;
		}
		$result['tickets'] = (isset($ticketArray) ? $ticketArray : '');
		$result['totalPages'] = (isset($page_count) ? $page_count : '');
		$result['status_text'] = 'List of tickets';
		$result['status'] = 'ok';
	} catch (Exception $e) {
		$result['status'] = 'failed';
		$result['status_text'] = 'Kayako exception occurred searching tickets. Please try again!';
	}
	return $result;
}

/**
* returns information for viewing a given ticket
*
* @param int $ticketID the id of the ticket to retrieve. you can use [getTicketList](#getticketlist) to get a list of your tickets
* @return array
*/
function viewTicket($ticketID)
{
	$result = [
		'status' => 'Incomplete',
		'status_text' => '',
		'result' => []
	];
	if (!$ticketID) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Ticket Reference ID is required';
		return $result;
	}
	function_requirements('class.kyConfig');
	ini_set('default_socket_timeout', 360);
	try {
		kyConfig::set(new kyConfig(KAYAKO_API_URL, KAYAKO_API_KEY, KAYAKO_API_SECRET));
		kyConfig::get()->setDebugEnabled(false)->setTimeout(120);
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred setting config options. Please try again!';
		return $result;
	}
	try {
		function_requirements('ticket_status_all');
		$ticketStatuses = ticket_status_all();
		foreach ($ticketStatuses as $ticketStatus) {
			$statusArray[$ticketStatus->id] = $ticketStatus->title;
		}
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred getting ticket status. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		function_requirements('ticket_priority_all');
		$priorities = ticket_priority_all();
		foreach ($priorities as $priority) {
			$priorityArray[$priority->id] = $priority->title;
		}
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred getting ticket priority. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		if ($GLOBALS['tf']->ima != 'admin' && $GLOBALS['tf']->accounts->data['account_lid'] != kyTicket::get($ticketID)->getUser()->getEmail()) {
			//dialog('Access Error', 'This is not a ticket owned by you.  If you feel you arrived here in error then please contact support@interserver.net');
			$result['status'] = 'Failed';
			$result['status_text'] = 'Kayako exception occurred getting ticket priority. Please try again!';
			myadmin_log('api', 'info', 'Denied view ticket because ' . kyTicket::get($ticketID)->getUser()->getEmail() . ' != ' . $GLOBALS['tf']->accounts->data['account_lid'], __LINE__, __FILE__);
			return $result;
		}
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Error loading ticket details!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		$ticketDetails = kyTicket::get($ticketID);
		$ticketDetailsArray['ticket_reference_id'] = $ticketDetails->displayid;
		$ticketDetailsArray['full_name'] = $ticketDetails->getFullName();
		$ticketDetailsArray['email'] = $ticketDetails->email;
		$ticketDetailsArray['subject'] = $ticketDetails->subject;
		$ticketDetailsArray['creationtime'] = $ticketDetails->creationtime;
		$ticketDetailsArray['statustitle'] = $statusArray[$ticketDetails->statusid];
		$ticketDetailsArray['prioritytitle'] = $priorityArray[$ticketDetails->priorityid];
		$ticketDetailsArray['lastactivity'] = $ticketDetails->lastactivity;
		foreach ($ticketDetails->getPosts() as $key => $ticket_post) {
			$ticketDetailsArray['posts'][$key]['email'] = $ticket_post->getEmail();
			$ticketDetailsArray['posts'][$key]['full_name'] = $ticket_post->getFullName();
			$ticketDetailsArray['posts'][$key]['dateline'] = $ticket_post->getDateline();
			$ticketDetailsArray['posts'][$key]['contents'] = $ticket_post->getContents();
		}
		$result['status'] = 'Success';
		$result['status_text'] = 'Ticket details';
		$result['result'] = isset($ticketDetailsArray) ? $ticketDetailsArray : '';
		return $result;
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred getting ticket details. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
}

/**
* adds a response to a ticket
*
* @param int $ticketID the id of the ticket to add a response to. you can use [getTicketList](#getticketlist) to get a list of your tickets
* @param string $content the message to add to the ticket
* @return array returns an array containing the status / status_text of adding a ticket response
*/
function ticketPost($ticketID, $content)
{
	if (!$ticketID) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Ticket Reference ID is required';
		return $result;
	}
	if (!$content) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Content is required';
		return $result;
	}
	// commenting out , curl not inited yet , no point in this code, would need to inject it in kyRESTClient around line 166 - Joe (detain@interserver.net)
	//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
	//curl_setopt($ch, CURLOPT_TIMEOUT, 400); //timeout in seconds
	function_requirements('class.kyConfig');
	try {
		kyConfig::set(new kyConfig(KAYAKO_API_URL, KAYAKO_API_KEY, KAYAKO_API_SECRET));
		kyConfig::get()->setDebugEnabled(false)->setTimeout(120);
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred setting configuration. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		if ($GLOBALS['tf']->ima != 'admin' && $GLOBALS['tf']->accounts->data['account_lid'] != kyTicket::get($ticketID)->getUser()->getEmail()) {
			//dialog('Access Error', 'This is not a ticket owned by you.  If you feel you arrived here in error then please contact support@interserver.net');
			$result['status'] = 'Failed';
			$result['status_text'] = 'Kayako exception occurred getting ticket priority. Please try again!';
			myadmin_log('api', 'info', 'Denied view ticket because ' . kyTicket::get($ticketID)->getUserId() . ' != ' . kyUser::search($GLOBALS['tf']->accounts->data['account_lid'])->current()->getId() . '(' . $GLOBALS['tf']->accounts->data['account_lid'] . ')', __LINE__, __FILE__);
			return $result;
		}
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Error loading ticket details!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		$ticket = kyTicket::get($ticketID);
		$user = $ticket->getUser();
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred getting ticket detail. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
	try {
		//add new post (user reply)
		$post = $ticket->newPost($user, $content)->create();
		if ($post) {
			$result['status'] = 'Success';
			$result['status_text'] = 'Post added successfully';
		} else {
			$result['status'] = 'Failed';
			$result['status_text'] = 'Exception occurred adding post.';
		}
		return $result;
	} catch (Exception $e) {
		$result['status'] = 'Failed';
		$result['status_text'] = 'Kayako exception occurred adding post. Please try again!';
		myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
		return $result;
	}
}
