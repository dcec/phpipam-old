<?php

/*
 * Discover new hosts with Nedi
 *******************************/

/* required functions */
require_once('../../../functions/functions.php'); 
require_once('../../../functions/functions-external.php');

/* verify that user is logged in */
isUserAuthenticated(true);

/* verify that user has write permissions for subnet */
$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
if($subnetPerm < 2) 		{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

# verify post
CheckReferrer();

# ok, lets get results form post array!
foreach($_REQUEST as $key=>$line) {
	if(substr($key, 0,6)=="subnet") 			{ $res[substr($key, 6)]['subnet']  	= $line; }	
	if(substr($key, 0,4)=="mask") 			{ $res[substr($key, 4)]['mask']  	= $line; }
	if(substr($key, 0,6)=="vlanId") 		{ $res[substr($key, 6)]['vlanId']  	= $line; }
	if(substr($key, 0,7)=="gateway") 		{ $res[substr($key, 7)]['gateway']  	= $line; }
	// description
	if(substr($key, 0,11)=="description") 	{ $res[substr($key, 11)]['description'] = $line; }
	// dns name 
	if(substr($key, 0,6)=="device") 		{ $res[substr($key, 6)]['device']  	= $line; }
	if(substr($key, 0,4)=="port") 			{ $res[substr($key, 4)]['port']  	= $line; }

	//verify that it is not already in table!
	#if(substr($key, 0,2)=="ip") {
	#	if(checkDuplicate ($line, $_REQUEST['subnetId']) == true) {
	#		die ("<div class='alert alert-danger'>IP address $line already exists!</div>");
	#	}
	#}
}

# insert entries
if(sizeof($res)>0) {
	if(insertNediSubnetsResults($res,$_REQUEST['subnetId'])) {
		print "<div class='alert alert-success'>"._("Scan results added to database")."!</div>";
	}
}
# error
else {
	print "<div class='alert alert-danger'>"._("Error")."</div>";
}

?>