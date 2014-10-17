<?php

/*
 * Print edit site
 *********************/

/* required functions */
require_once('../../functions/functions.php'); 

/* verify that user is logged in */
isUserAuthenticated(false);

/* verify that user is admin */
if (!checkAdmin()) die('');

/* verify post */
CheckReferrer();

/* get all groups */
$groups = getAllGroups();

/* get site details */
$site = getSiteDetailsById($_POST['siteId']);
?>



<!-- header -->
<div class="pHeader">
	<?php 
	print _('Manage site permissions');		
	?>
</div>

<!-- content -->
<div class="pContent">

	<?php 
	print _('Manage permissions for site'); ?> <?php print $site['name']." ($site[company])";
	?>
	<hr>

	<form id="editSitePermissions">
	<table class="editSitePermissions table table-noborder table-condensed table-hover">

	<?php
	# parse permissions
	if(strlen($site['permissions'])>1) {
		$permissons = parseSectionPermissions($site['permissions']);
	}
	else {
		$permissons = "";
	}

	# print each group
	if($groups) {
	foreach($groups as $g) {
		print "<tr>";
		print "	<td>$g[g_name]</td>";
		print "	<td>";
			
		print "<span class='checkbox inline noborder'>";			

		print "	<input type='radio' name='group$g[g_id]' value='0' checked> na";
		if($permissons[$g['g_id']] == "1")	{ print " <input type='radio' name='group$g[g_id]' value='1' checked> ro"; }			
		else								{ print " <input type='radio' name='group$g[g_id]' value='1'> ro"; }	
		if($permissons[$g['g_id']] == "2")	{ print " <input type='radio' name='group$g[g_id]' value='2' checked> rw"; }			
		else								{ print " <input type='radio' name='group$g[g_id]' value='2'> rw"; }			
		if($permissons[$g['g_id']] == "3")	{ print " <input type='radio' name='group$g[g_id]' value='3' checked> rwa"; }			
		else								{ print " <input type='radio' name='group$g[g_id]' value='3'> rwa"; }
		print "</span>";

		# hidden
		print "<input type='hidden' name='siteId' value='$_POST[siteId]'>";
		
		print "	</td>";
		print "</tr>";
	}
	} else {
		print "<tr>";
		print "	<td colspan='2'><span class='alert alert-info'>"._('No groups available')."</span></td>";
		print "</tr>";		
	}
	?>
     
    </table>
    </form> 
    
    <?php
    # print warning if slaves exist
    if(siteContainsSlaves($_POST['siteId'])) { print "<div class='alert alert-warning'>"._('Permissions for all nested sites will be overridden')."!</div>"; }
    ?>
    
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success editSitePermissionsSubmit"><i class="fa fa-check"></i> <?php print _('Set permissions'); ?></button>
	</div>

	<div class="editSitePermissionsResult"></div>
</div>