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
unset($custom['ICCode']);
unset($custom['NamingC']);

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
		<!-- <th><?php # print _('Company'); ?></th> -->
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
	$html = array();
	$children = array();
	$rootId = 0;									# root is 0
	
	foreach ( $sites as $item )
			$children[$item['masterSiteId']][] = $item;
			
	$loop  = !empty( $children[$rootId] );
		
		# initializing $parent as the root
		$parent = $rootId;
		
		#$parent_stackF = array();
		$parent_stack  = array();
	
	#print "<pre>";
	#print_r($children);
	#print "</pre>";
	
	while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
	{
	$permission = checkSitePermission ($option['value']['siteId']);
	#if($permission != "0") {
	#if($permission > "0"){
	#	print "<pre>";
	#print_r($option);
	#print "</pre>";
		# repeat 
		$repeat  = str_repeat( " - ", ( count($parent_stack)) );
		# dashes
		if(count($parent_stack) == 0)	{ $dash = ""; }
		else							{ $dash = "-"; }

		if(count($parent_stack) == 0) {
			$margin = "0px";
			$padding = "0px";
		}
		else {
			# padding
			$padding = "10px";			

			# margin
			$margin  = (count($parent_stack) * 10) -10;
			$margin  = $margin *2;
			$margin  = $margin."px";				
		}
	
		$count = count( $parent_stack ) + 1;
		
	//print details
	if(strlen($option['value']['name']) > 0) { 
	if($permission > "0"){
	print '<tr>'. "\n";
	if($count==1) {
		print '	<td class="name '.$permission.'">'. $option['value']['name'] .'</td>'. "\n";
	}else {
		print "	<td class='level$count ".$permission."'><span class='structure' style='padding-left:$padding; margin-left:$margin;'></span><i class='fa fa-angle-right'></i>".$option['value']['name']."</td>";
	}
	#print '	<td class="company">'. $option['value']['company'] .'</td>'. "\n";
	print '<input type="hidden" name="company" value="'.$option['value']['company'] .'">';
	print '	<td class="location">'. $option['value']['location'] .'</td>'. "\n";
	$master = subnetGetSITEdetailsById($option['value']['masterSiteId']);
	if ($master['siteId']>0){
		print '	<td class="master">'. $master['name'] .'</td>'. "\n";
	}else{
		print '	<td class="master"></td>'. "\n";
	}
	
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {

			print "<td class='customField hidden-xs hidden-sm'>";
					
			//booleans
			if($field['type']=="tinyint(1)")	{
				if($option['value'][$field['name']] == "0")		{ print _("No"); }
				elseif($option['value'][$field['name']] == "1")	{ print _("Yes"); }
			} 
			//text
			elseif($field['type']=="text") {
				if(strlen($option['value'][$field['name']])>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $option['value'][$field['name']])."'>"; }
				else											{ print ""; }
			}
			else {
				print $option['value'][$field['name']];
				
			}
			print "</td>"; 

		}
	}
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	if($permission > "0"){print "		<button class='btn btn-xs btn-default editSITEtool' data-action='add sub'   data-siteid='".$option[value][siteId]."'><i class='fa fa-plus'></i></button>";}
	if($permission > "1"){print "		<button class='btn btn-xs btn-default editSITEtool' data-action='edit'   data-siteid='".$option[value][siteId]."'><i class='fa fa-pencil'></i></button>";}
	if($permission > "2"){print "		<button class='btn btn-xs btn-default showSitePerm' data-action='show'   data-siteid='".$option[value][siteId]."'><i class='fa fa-tasks'></i></button>";}
	if($permission > "2"){print "		<button class='btn btn-xs btn-default editSITEtool' data-action='delete' data-siteid='".$option[value][siteId]."'><i class='fa fa-times'></i></button>";}
	print "	</div>";
	print "	</td>";	
	print '</tr>'. "\n";
	}
	}
			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children[$option['value']['siteId']] ) ) {														
				array_push( $parent_stack, $option['value']['masterSiteId'] );
				$parent = $option['value']['siteId'];
			}
			# Last items
			else { }
	
	}
	#}
}
?>
</table>