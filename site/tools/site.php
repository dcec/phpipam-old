<?php

/**
 * Script to display available Sites
 */

/* verify that user is authenticated! */
isUserAuthenticated ();

/* check if admin */
if(checkAdmin(false))	{ $admin = true; }

/* get all sites and subnet descriptions */
$sites = getAllSites (true);

/* get custom fields */
$custom = getCustomFields('sites');
unset($custom['ICCode']);
unset($custom['NamingC']);

# title
print "<h4>"._('Available Sites:')."</h4>";
print "<hr>";

if($admin) {
	print "<a class='btn btn-sm btn-default' href='administration/manageSites/' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> ". _('Manage')."</a>";
}else{
	print "<a class='btn btn-sm btn-default' href='tools/manageSites/' data-action='add'  data-switchid='' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> ". _('Manage')."</a>";
}

# table
print "<table id='sites' class='table table-striped table-condensed table-top'>";

/* headers */
print '<tr">' . "\n";
print ' <th>'._('Name').'</th>' . "\n";
#print ' <th>'._('Company').'</th>' . "\n";
print ' <th>'._('Location').'</th>' . "\n";
print ' <th>'._('Master Site').'</th>' . "\n";
if(sizeof($custom) > 0) {
	foreach($custom as $field) {
		print "	<th class='hidden-xs hidden-sm hidden-md'>$field[name]</th>";
	}
}
print '</tr>' . "\n";

$m = 0;


foreach ($sites as $site) {
	
	# new change detection
	if($m>0) {
		if($sites[$m]['name']==$sites[$m-1]['name'] &&  $sites[$m]['company']==$sites[$m-1]['company'] && $sites[$m]['location']==$sites[$m-1]['location'])	{ $change = 'nochange'; }
		else																																				{ $change = 'change'; }
	}
	# first
	else 																																						{ $change = 'change';	 }

	/* get section details */
	#$site = getSiteDetailsById($_POST['siteId']);

	/* check if it is master */
	if(!isset($site['masterSiteId'])) {
																				{ $masterSite = true;}
	}
	else {
		if( ($site['masterSiteId'] == 0) || (empty($site['masterSiteId'])) ) { $masterSite = true;}
		else 																	 { $masterSite = false;}	
	}

	# open session and get username / pass
	#if (!isset($_SESSION)) {  session_start(); }
    # redirect if not authenticated */
    #if (empty($_SESSION['ipamusername'])) 	{ return "0"; }
    #else									{ $username = $_SESSION['ipamusername']; }
    
	# get all user groups
	#$user = getUserDetailsByName ($username);
	#$groups = json_decode($user['groups']);
	
	# if user is admin then return 3, otherwise check
	#if($user['role'] == "Administrator")	{ return "3"; }

	# get subnet permissions
	#$site  = getSiteDetailsById($site['siteId']);
	#$siteP = json_decode($site['permissions']);
	
	# check permission
	$permission = checkSitePermission ($site['siteId']);
	
	#print "<hr>";
	#print "<pre>";
	#print_r($groups);
	#print_r($siteP);
	#print_r($permission);
	#print "</pre>";

	#print ("<div class='alert alert-info'>Query:$permission ".$site['name']."</div>");	
	
	if($permission != "0") {
		
		print "<tr class='$change'>";

		/* print first 3 only if change happened! */
		if($change == "change") {
			print ' <td>'. $site['name']         .'</td>' . "\n";
			#print ' <td>'. $site['company']           .'</td>' . "\n";
			print ' <td>'. $site['location'] .'</td>' . "\n";			
		}
		else {
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';	
		}
		
		$master = subnetGetSITEdetailsById($site['masterSiteId']);
		if ($master['siteId']>0){
			print '	<td class="master">'. $master['name'] .'</td>'. "\n";
		}else{
			print '	<td class="master"></td>'. "\n";
		}
		
        # custom
        if(sizeof($custom) > 0) {
	   		foreach($custom as $field) {

				print "<td class='hidden-xs hidden-sm hidden-md'>";
			
				//booleans
				if($field['type']=="tinyint(1)")	{
					if($site[$field['name']] == "0")		{ print _("No"); }
					elseif($site[$field['name']] == "1")	{ print _("Yes"); }
				} 
				//text
				elseif($field['type']=="text") {
					if(strlen($site[$field['name']])>0)		{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $site[$field['name']])."'>"; }
					else									{ print ""; }
				}
				else {
					print $site[$field['name']];
					
				}
				print "</td>"; 
	    	}
	    }    
	    print '</tr>' . "\n";
	}

	# next VLAN
	$m++;
}


print '</table>';
?>
