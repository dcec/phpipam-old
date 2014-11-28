<?php 

/**
 * Script to edit SITE details
 *******************************/

/* required functions */
require_once('../../functions/functions.php'); 

/* verify that user is admin */
if (!checkAdmin()) die('');

/* get modified details */
$site = $_POST;

/* get settings */
$settings = getAllSettings ();

/* if it already exist DIE! */
if($settings['siteDuplicate'] == "0") {
if($site['action'] == "add") {
	if(!getSITEbyName($site['name'])) 	{ }
	else 									{ die('<div class="alert alert-danger">'._('Site already exists').'!</div>'); }	
}
}

//custom
$myFields = getCustomFields('sites');
if(sizeof($myFields) > 0) {
	foreach($myFields as $myField) {
		# replace possible ___ back to spaces!
		$myField['nameTest']      = str_replace(" ", "___", $myField['name']);
		
		if(isset($_POST[$myField['nameTest']])) { $site[$myField['name']] = $site[$myField['nameTest']];}
	}
}

/* sanitize post! */
#$site['name'] 		 = htmlentities($site['name'], ENT_COMPAT | ENT_HTML401, "UTF-8");			# prevent XSS
#$site['company'] 	 = htmlentities($site['company'], ENT_COMPAT | ENT_HTML401, "UTF-8");		# prevent XSS
#$site['location'] = htmlentities($site['location'], ENT_COMPAT | ENT_HTML401, "UTF-8");			# prevent XSS

/* Hostname must be present! */
if($site['name'] == "") 					{ die('<div class="alert alert-danger">'._('Name is mandatory').'!</div>'); }

/* update details */
if($site['action']=="add") {
	if(!updateSITEDetails($site, true)) 	{ print('<div class="alert alert-danger"  >'._("Failed to $site[action] Site").'!</div>'); }
	else 										{ print('<div class="alert alert-success">'._("Site $site[action] successfull").'!</div><p id="siteidforonthefly" style="display:none">'.$id.'</p>'); }	
} else {
	if(!updateSITEDetails($site, false)) 		{ print('<div class="alert alert-danger"  >'._("Failed to $site[action] Site").'!</div>'); }
	else 										{ print('<div class="alert alert-success">'._("Site $site[action] successfull").'!</div><p id="siteidforonthefly" style="display:none">'.$id.'</p>'); }
}

?>