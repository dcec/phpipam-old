<?php

/**
 *
 * Script to verify user requested input and verify it
 *
 */
 
 
/* functions */
if(!function_exists("getSubnetDetailsById")) { require_once('../../functions/functions.php'); }

/* @mail functions ------------------- */
include_once('../../functions/functions-mail.php');

# First chech referer and requested with 
CheckReferrer();

/* get all posted variables */
$request = $_POST;

/* check for duplicate entry! needed only in case new IP address is added, otherwise the code is locked! */
if (checkDuplicate ($request['ip_addr'], $request['subnetId'])) {
	die ('<div class="alert alert-danger">'._('IP address').' '. $request['ip_addr'] .' '._('already existing in database').'!</div>');
}

/* first get subnet details */
$subnet = getSubnetDetailsById ($request['subnetId']);
$subnet2 = $subnet;												//for later check
$subnet['subnet'] = Transform2long ($subnet['subnet']);
$subnet = $subnet['subnet'] . "/" . $subnet['mask'];
			
/* verify email */
if(!checkEmail($request['requester']) ) 						{ die('<div class="alert alert-danger alert-nomargin alert-norounded">'._('Please provide valid email address').'! ('._('requester').': <del>'. $request['requester'] .'</del>)</div>');	 }

if(addNewRequest ($request)) {
	print '<div class="alert alert-success alert-nomargin alert-norounded">'._('Request submitted successfully').'!</div>';

	# send mail
	if(!sendIPReqEmail($request))	{ print '<div class="alert alert-danger alert-nomargin alert-norounded">'._('Sending mail for new IP request failed').'!</div>'; }
	else							{ print '<div class="alert alert-success alert-nomargin alert-norounded">'._('Sending mail for IP request succeeded').'!</div>'; }
}
else {
	print '<div class="alert alert-danger alert-nomargin alert-norounded">'._('Error submitting new IP address request').'!</div>';
}

?>