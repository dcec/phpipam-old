<?php

/*
 * Discover new hosts with Nedi
 *******************************/

/* required functions */
require_once('../../../functions/functions.php'); 
require_once('../../../functions/functions-external.php');

if (!isset($_SESSION)) { session_start(); }
if(!$_POST){$_SESSION['ipamusername'] = "Crontab";}

/* verify that user is logged in */
isUserAuthenticated(true);

/* verify that user has write permissions for subnet */
$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
if($subnetPerm < 2) 		{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

# verify post
CheckReferrer();

# ok, lets get results form post array!
foreach($_REQUEST as $key=>$line) {
	// IP address
	if(substr($key, 0,2)=="ip") 			{ $res[substr($key, 2)]['ip_addr']  	= $line; }
	
	if(substr($key, 0,3)=="mac") 			{ $res[substr($key, 3)]['mac']  	= $line; }
	if(substr($key, 0,6)=="ifname") 		{ $res[substr($key, 6)]['ifname']  	= $line; }
	if(substr($key, 0,6)=="switch") 		{ $res[substr($key, 6)]['switch']  	= $line; }
	// description
	if(substr($key, 0,11)=="description") 	{ $res[substr($key, 11)]['description'] = $line; }
	// dns name 
	if(substr($key, 0,8)=="dns_name") 		{ $res[substr($key, 8)]['dns_name']  	= $line; }

	if(substr($key, 0,4)=="type") 			{ $res[substr($key, 4)]['Address Type']  	= $line; }
	if(substr($key, 0,8)=="lifetime") 		{ $res[substr($key, 8)]['Lifetime']  	= $line; }
	
	//verify that it is not already in table!
	if(substr($key, 0,2)=="ip") {
		if(checkDuplicate ($line, $_REQUEST['subnetId']) == true) {
			die ("<div class='alert alert-danger'>IP address $line already exists!</div>");
		}
	}
}

# insert entries
if(sizeof($res)>0) {
	if(insertNediScanResults($res, $_REQUEST['subnetId'])) {
		print "<div class='alert alert-success'>"._("Scan results added to database")."!</div>";
		foreach($res as $ip) {
			$ip['action'] = "add";$ip['agent'] = "NeDiGlpi";
			$log = prepareLogFromArray ($ip);
			updateLogTable ($ip['action'] .' of IP address '. $ip['ip_addr'] .' succesfull!', $log, 0);
		}
	}
}
# error
else {
	print "<div class='alert alert-danger'>"._("Error")."</div>";
	foreach($res as $ip) {
		$ip['action'] = "add";$ip['agent'] = "NeDiGlpi";
		updateLogTable ('Error '. $ip['action'] .' IP address '. $ip['ip_addr'], 'Error '. $ip['action'] .' IP address '. $ip['ip_addr'] .'<br>SubnetId: '. $ip['subnetId'], 2);
	}
}

?>