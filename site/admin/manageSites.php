<?php

/**
 *	Print all available Sites and configurations
 ************************************************/

/* verify that user is admin */
checkAdmin();

/* get all available VLANSs */
$sites = getAllSites ();

/* get custom fields */
$custom = getCustomFields('sites');

unset($custom['ICCode']);
unset($custom['NamingC']);
unset($custom['Mail']);
unset($custom['Phone']);

?>

<h4><?php print _('Manage Sites'); ?></h4>
<hr><br>

<!-- add new -->
<button class="btn btn-sm btn-default editSITE" data-action="add" data-siteid="" style="margin-bottom:10px;"><i class="fa fa-plus"></i> <?php print _('Add SITE'); ?></button>

<?php
	#print "<pre>";
	#print_r($custom);
	#print "</pre>";
/* first check if they exist! */
if(!$sites) {
	print '	<div class="alert alert-info alert-absolute">'._('No Sites configured').'!</div>'. "\n";
}
else {

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
	
	$m = 0;
	
	print '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
	

	#print "</div>";

	
	
	while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
	{
	
		$count = count( $parent_stack ) + 1;
		
	//print details
	if(strlen($option['value']['name']) > 0) { 
		if($count==1) {
			print '<div class="panel panel-default">';
			print '<div class="panel-heading" role="tab" id="heading'.$m.'">';
			print '<h4 class="panel-title">';
			print '<a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapse'.$m.'" aria-expanded="false" aria-controls="collapse'.$m.'">';
			print $option['value']['name'].' Count: '.(1 + sizeof($children[$option['value']['siteId']])).'</a></h4></div>';
			print '<div id="collapse'.$m.'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'.$m.'">';
			print '<div class="panel-body">';
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
				<?php
				
			printSite($option['value'],$custom);
			$m++;
			
		}else {
			printSite($option['value'],$custom);
		}
		
	
	}
			if ( $option === false ) { $parent = array_pop( $parent_stack );}
			# Has slave subnets
			elseif ( !empty( $children[$option['value']['siteId']] ) ) {														
				array_push( $parent_stack, $option['value']['masterSiteId'] );
				$parent = $option['value']['siteId'];
			}
			# Last items
			else { 
			
			}
		if(count( $parent_stack ) == 0){print '</table>';print '</div></div></div>';}
	
	}
	print "</div>";
}
?>
</table>