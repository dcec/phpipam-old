<?php

/*
 * Print edit subnet
 *********************/

/* required functions */
if(!function_exists('getSubnetStatsDashboard')) {
	require_once('../../functions/functions.php'); 
}

/* verify that user is logged in */
isUserAuthenticated(false);
?>

<select name="siteId" class="form-control input-sm input-w-auto">
	<option disabled="disabled"><?php print _('Select SITE'); ?>:</option>
	<?php

		$sites = getAllSites();
		
		#$site['siteId'] = $subnetDataOld['siteId'];#
		if($_POST['action'] == "add") { $site['siteId'] = 0; }
		
		
		$rootId = 0;
		foreach ( $sites as $item )
				$children[$item['masterSiteId']][] = $item;
		$loop = !empty( $children[$rootId] );
		$parent = $rootId;
		$parent_stack = array();
		$allParentss = getAllSiteParents ($subnetDataOld['siteId']);
		
		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat 
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = $repeat; }
							
			# count levels
			$count = count( $parent_stack ) + 1;
			
			# print table line
			if(strlen($option['value']['subnet']) > 0) { 
				# selected
				#if (checkSubnetPermission($option['value']['id']) > 1){
				$permission = checkSitePermission ($site['siteId']);
				if(($permission != "0" || $site['siteId'] == 'Add') && $site['name']) {
				if($option['value']['id'] == $subnetMasterId) 	{ print '<option value="'. $option['value']['id'] .'" selected>$repeat '. $option['value']['description'] .'</option>'. "\n"; }
				else 											{ print '<option value="'. $option['value']['id'] .'">$repeat'. $option['value']['description'] .'</option>'. "\n"; }
				#if($subnetDataOld['siteId'] == $site['siteId']) { print '<option value="'. $option['value']['id'] .'" selected>$repeat '. $option['value']['description'] .'</option>'. "\n"; }
				#elseif($_POST['siteId'] == $site['siteId']) 	{ print '<option value="'. $site['siteId'] .'" selected>'. $printSITE .'</option>'. "\n"; }
				#else 											{ print '<option value="'. $option['value']['id'] .'">$repeat'. $option['value']['description'] .'</option>'. "\n"; }
				}
			}
			
			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children[$option['value']['id']] ) ) {														
				array_push( $parent_stack, $option['value']['masterSubnetId'] );
				$parent = $option['value']['id'];
			}
			# Last items
			else { }
		}
?>
</select>
