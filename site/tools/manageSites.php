<?php

/**
 *	Print all available Sites and configurations
 ************************************************/

/* verify that user is admin */
#checkAdmin();

/* get all available VLANSs */
$sites = getAllSites ();

/* get custom fields */
$custom = getCustomFields('sites');
#print "<hr>";
#	print "<pre>";
#	print_r($sites);
	#print_r($siteP);
	#print_r($permission);
#	print "</pre>";
?>

<h4><?php print _('Manage Sites'); ?></h4>
<hr><br>

<!-- add new -->
<button class="btn btn-sm btn-default editSITEtool" data-action="add" data-siteid="" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add SITE'); ?></button>

<?php
/* first check if they exist! */
if(!$sites) {
	print '	<div class="alert alert-info alert-absolute">'._('No Sites configured').'!</div>'. "\n";
}
else {
?>

<table id="siteManagement" class="table table-striped table-top table-auto">
	<!-- headers -->
	<tr>
		<th><?php print _('Name'); ?></th>	
		<th><?php print _('Company'); ?></th>
		<th><?php print _('Location'); ?></th>
		<th><?php print _('Master Site'); ?></th>
		<?php
		
		if(sizeof($custom) > 0) {
			foreach($custom as $field) {
				print "<th class='customField hidden-xs hidden-sm'>$field[name]</th>";
			}
		}
		?>
		<th></th>
	</tr>

	<!-- Sites -->
	<?php
	foreach ($sites as $site) {
	$permission = checkSitePermission ($site['siteId']);
	if($permission != "0") {
	//print details
	print '<tr>'. "\n";
	
	print '	<td class="name">'. $site['name'] .'</td>'. "\n";
	print '	<td class="company">'. $site['company'] .'</td>'. "\n";
	print '	<td class="location">'. $site['location'] .'</td>'. "\n";
	$master = subnetGetSITEdetailsById($site['masterSiteId']);
	if ($master['siteId']>0){
		print '	<td class="master">'. $master['name'] .' ('. $master['company'] .')</td>'. "\n";
	}else{
		print '	<td class="master"></td>'. "\n";
	}
	
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {
			
			print "<td class='customField hidden-xs hidden-sm'>";
					
			//booleans
			if($field['type']=="tinyint(1)")	{
				if($site[$field['name']] == "0")		{ print _("No"); }
				elseif($site[$field['name']] == "1")	{ print _("Yes"); }
			} 
			//text
			elseif($field['type']=="text") {
				if(strlen($site[$field['name']])>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $site[$field['name']])."'>"; }
				else											{ print ""; }
			}
			else {
				print $site[$field['name']];
				
			}
			print "</td>"; 
		}
	}
	$permission = checkSitePermission ($site['siteId']);
	print "	<td class='actions ".$permission."'>";
	print "	<div class='btn-group'>";
	if($permission > "1"){print "		<button class='btn btn-xs btn-default editSITEtool' data-action='edit'   data-siteid='$site[siteId]'><i class='fa fa-pencil'></i></button>";}
	#print "		<button class='btn btn-xs btn-default showSitePerm' data-action='show'   data-siteid='$site[siteId]'><i class='fa fa-tasks'></i></button>";
	if($permission > "2"){print "		<button class='btn btn-xs btn-default editSITEtool' data-action='delete' data-siteid='$site[siteId]'><i class='fa fa-times'></i></button>";}
	print "	</div>";
	print "	</td>";	
	print '</tr>'. "\n";
	
	}
	}
}
?>
</table>