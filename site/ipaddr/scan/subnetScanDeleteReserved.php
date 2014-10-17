<?php

/*
 * Update alive status of all hosts in subnet
 ***************************/

/* required functions */
$dir = dirname(__FILE__);
require_once($dir.'/../../../functions/functions.php');
require_once($dir.'/../../../functions/functions-external.php'); 
include_once($dir.'/../../../functions/functions-mail.php');


$mysqli = @new mysqli($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']); 
/* check connection */
if ($mysqli->connect_errno) {
	/* die with error */
	if($_POST){die('<div class="alert alert-danger"><strong>'._('Database connection failed').'!</strong><br><hr>Error: '. mysqli_connect_error() .'</div>');}
	else{die();}
}

	if($_POST){
	/* verify that user is logged in */
	isUserAuthenticated(true);

	/* verify that user has write permissions for subnet */
	#$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
	#if($subnetPerm < 2) 	{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

	/* verify post */
	CheckReferrer();	
	}
	$requests = getAllReservedIPrequests("60");
foreach($requests as $request) {
	$recipients_temp = explode(",", $request['requester']);
	$subject = "";
	
	/* get IP address id */
	$id = $request['id'];

	/* fetch all IP address details */
	$ip 	= getIpAddrDetailsById ($id);
	$subnet = getSubnetDetailsById ($ip['subnetId']);

	$cidr = $ip['ip_addr']."/".$subnet['mask'];
	# verify input CIDR
	$errors = verifyCidr ($cidr,0);
	$subdetail = calculateIpCalcResult($cidr);

	/* get VLAN details */
	$subnet['VLAN'] = subnetGetVLANdetailsById($subnet['vlanId']);
	$subnet['vlan'] = $subnet['VLAN']['number'];
	if(!empty($subnet['VLAN']['name'])) {
		$subnet['vlan'] .= ' ('. $subnet['VLAN']['name'] .')';
	}

	/* set title */
	$title = _('IP reserved inactive deleted').' : ' . $ip['ip_addr'];

	if(!empty($subnet['Gateway'])) {
		
	}

	/* Preset content */
	#$content .= '&bull; '._('IP address').': ' . "\t" . $ip['ip_addr'] . '/' . $subnet['mask']. "\n";
	$content .= '&bull; '._('IP address').': ' . "\t" . $ip['ip_addr']. "\n";

	$content .= '&bull; '._('IP Mask').': ' . "\t" . $subdetail['Subnet netmask']. "\n";

	if(!empty($subnet['Gateway'])) {
		$content .= '&bull; '._('IP Gateway').': ' . "\t" . $subnet['Gateway']. "\n";
	}
	# desc
	if(!empty($ip['description'])) {
	$content .= '&bull; '._('Description').':' . "\t" . $ip['description'] . "\n";
	}
	# hostname
	if(!empty($ip['dns_name'])) {
	$content .= '&bull; '._('Hostname').':' . "\t" 	 . $ip['dns_name'] . "\n";
	}
	# subnet desc
	if(!empty($subnet['description'])) {
	$content .= '&bull; '._('Subnet desc').': ' . "\t" . $subnet['description']. "\n";
	}
	# VLAN
	if(!empty($subnet['vlan'])) {
	$content .= '&bull; '._('VLAN').': ' . "\t\t" 	 . $subnet['vlan'] . "\n";
	}
	# Switch
	if(!empty($ip['switch'])) {
		# get device by id
		$device = getDeviceDetailsById($ip['switch']);
		$content .= "&bull; "._('Device').":\t\t"		 . $device['hostname'] . "\n";
	}
	# port
	if(!empty($ip['port'])) {
	$content .= "&bull; "._('Port').":\t"			 . $ip['port'] . "\n";
	}
	# custom
	$myFields = getCustomFields('ipaddresses');
	if(sizeof($myFields) > 0) {
		foreach($myFields as $myField) {
			if(!empty($ip[$myField['name']])) {
				$content .=  '&bull; '. $myField['name'] .":\t". $ip[$myField['name']] ."\n";
			}
		}
	}
	
	$content .= "\nThis ip has been deleted because it has never been active for 60 days after creation!";
	foreach ($recipients_temp as $rec) {
	//verify each email
	if(!checkEmail($rec)) {
		$errors[] = $rec;
	}
	
	$ip['action'] = "delete";
	
	if(!$_POST){$user = "Crontab";}else{$user = NULL;}
	
	/* execute insert / update / delete query */    
	if (!modifyIpAddress($ip)) {
		print '<div class="alert alert-danger">'._('Error inserting IP address').'!</div>';
	    updateLogTable ('Error '. $ip['action'] .' IP address '. $ip['ip_addr'], 'Error '. $ip['action'] .' IP address '. $ip['ip_addr'] .'<br>SubnetId: '. $ip['subnetId'], 2,$user);
	}
	else {
		print '<div class="alert alert-success">'._("IP $ip[action] successful").'!</div>';
		updateLogTable ($ip['action'] .' of IP address '. $ip['ip_addr'] .' succesfull!', $ip['action'] .' of IP address '. $ip['ip_addr'] .' succesfull!<br>SubnetId: '. $ip['subnetId'], 0,$user);
	}
		
	if(!$_POST){
	if (!$errors) {
	if(!sendIPnotifEmail($request['requester'], $title, $content))	{ print '<div class="alert alert-danger">'._('Sending mail failed').'!</div>'; }
	else																						{ print '<div class="alert alert-success">'._('Sending mail succeeded').'!</div>'; }
	}else{
		print '<div class="alert alert-danger">'._('Wrong recipients! (separate multiple with ,)').'</div>';
	}
	}
	if($_POST['debug']==1) {
	print "<hr>";
	print "<pre>";
	print_r($request['requester']);
	print "</pre>";
	print "<pre>";
	print_r($title);
	print "</pre>";
	print "<pre>";
	print_r($content);
	print "</pre>";
	
	}
}

}	

	
if($_POST){
?>


<h5><?php print _('Scan results');?>:</h5>
<hr>

<?php
# error?
if(isset($error)) {
	print "<div class='alert alert-danger'><strong>"._("Error").": </strong>$error</div>";
}
//empty
elseif(!isset($lastseen)) {
	print "<div class='alert alert-info'>"._('Subnet is empty')."</div>";
}
else {
	# order by IP address
	ksort($lastseen);

}
if($_POST['debug']==1) {
	print "<hr>";
	print "<pre>";
	print_r($requests);
	print "</pre>";
	#print "<pre>";
	#print_r($request['requester']);
	#print "</pre>";
	#print "<pre>";
	#print_r($title);
	#print "</pre>";
	#print "<pre>";
	#print_r($content);
	#print "</pre>";
	
}
}
?>