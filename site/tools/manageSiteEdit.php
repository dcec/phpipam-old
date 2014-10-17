<?php

/**
 *	Print all available VRFs and configurations
 ************************************************/

/* required functions */
require_once('../../functions/functions.php');
require_once('../../functions/functions-external.php');

/* verify that user is admin */
#checkAdmin();

/* get post */
$sitePost = $_POST;

/* get all available VRFs */
$site = subnetGetSITEdetailsById($_POST['siteId']); ####

$rootId = 0;
$sites = fetchSites ($_POST['siteId']);
foreach ( $sites as $item )
			$children[$item['masterSiteId']][] = $item;
$loop  = !empty( $children[$rootId] );

if ($_POST['action'] != "add") {
    $siteDataOld = subnetGetSITEdetailsById ($_POST['siteId']);
}

/* get custom fields */
$custom = getCustomFields('sites');

if ($_POST['action'] == "delete") 	{ $readonly = "readonly"; }
else 								{ $readonly = ""; }

/* set form name! */
if(isset($_POST['fromSubnet'])) { $formId = "siteManagementEditFromSubnet"; }
else 							{ $formId = "siteManagementEdit"; }


?>

<script type="text/javascript">
$(document).ready(function(){
     if ($("[rel=tooltip]").length) { $("[rel=tooltip]").tooltip(); }
});
</script>


<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('SITE'); ?></div>


<!-- content -->
<div class="pContent">
	<form id="<?php print $formId; ?>">
	
	<table id="siteManagementEdit2" class="table table-noborder table-condensed">
	<!-- name -->
	<tr>
		<td><?php print _('Name'); ?></td>
		<td>
			<input type="text" class="name form-control input-sm" name="name" placeholder="<?php print _('SITE name'); ?>" value="<?php if(isset($site['name'])) print $site['name']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- company  -->
	<tr>
		<td><?php print _('Company'); ?></td>
		<td>
			<input type="text" class="company form-control input-sm" name="company" placeholder="<?php print _('SITE company'); ?>" value="<?php if(isset($site['company'])) print $site['company']; ?>" <?php print $readonly; ?>>
		</td>
	</tr>

	<!-- location -->
	<tr>
		<td><?php print _('Location'); ?></td>
		<td>
			<input type="text" class="location form-control input-sm" name="location" placeholder="<?php print _('Location'); ?>" value="<?php if(isset($site['location'])) print $site['location']; ?>" <?php print $readonly; ?>>
			<?php
			if( ($_POST['action'] == "edit") || ($_POST['action'] == "delete") ) { print '<input type="hidden" name="siteId1" value="'. $_POST['siteId'] .'">'. "\n"; }
			?>
			<input type="hidden" name="action" value="<?php print $_POST['action']; ?>">
		</td>
	</tr>

    <!-- Master site -->
    <tr>
        <td><?php print _('Master Site'); ?></td>
        <td>
        	<?php printDropdownMenuByMasterSite($site['masterSiteId'],$site['siteId']); ?>
        </td>
        <td class="info2"><?php print _('Enter master site if you want to nest it under existing site, or select root to create root site'); ?>!</td>
    </tr>
	
	<!-- Custom -->
	<?php
	if(sizeof($custom) > 0) {

		print '<tr>';
		print '	<td colspan="2"><hr></td>';
		print '</tr>';

		foreach($custom as $field) {
		
			# replace spaces
		    $field['nameNew'] = str_replace(" ", "___", $field['name']);

			# required
			if($field['Null']=="NO")	{ $required = "*"; }
			else						{ $required = ""; }
			
			print '<tr>'. "\n";
			print '	<td>'. $field['name'] .' '.$required.'</td>'. "\n";
			print '	<td>'. "\n";
			
			//set type
			if(substr($field['type'], 0,3) == "set") {
				//parse values
				if($field['name'] == 'Country'){
					$tmp=array("Afghanistan","Albania","Algeria","Andorra","Angola","Antigua & Deps","Argentina","Armenia","Australia","Austria","Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bhutan","Bolivia","Bosnia Herzegovina","Botswana","Brazil","Brunei","Bulgaria","Burkina","Burundi","Cambodia","Cameroon","Canada","Cape Verde","Central African Rep","Chad","Chile","China","Colombia","Comoros","Congo","Congo Democratic Rep","Costa Rica","Croatia","Cuba","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","East Timor","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Ethiopia","Fiji","Finland","France","Gabon","Gambia","Georgia","Germany","Ghana","Greece","Grenada","Guatemala","Guinea","Guinea-Bissau","Guyana","Haiti","Honduras","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland Republic","Israel","Italy","Ivory Coast","Jamaica","Japan","Jordan","Kazakhstan","Kenya","Kiribati","Korea North","Korea South","Kosovo","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Mauritania","Mauritius","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montenegro","Morocco","Mozambique","Myanmar Burma","Namibia","Nauru","Nepal","Netherlands","New Zealand","Nicaragua","Niger","Nigeria","Norway","Oman","Pakistan","Palau","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Poland","Portugal","Qatar","Romania","Russian Federation","Rwanda","St Kitts & Nevis","St Lucia","Saint Vincent & the Grenadines","Samoa","San Marino","Sao Tome & Principe","Saudi Arabia","Senegal","Serbia","Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Sudan","Spain","Sri Lanka","Sudan","Suriname","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Togo","Tonga","Trinidad & Tobago","Tunisia","Turkey","Turkmenistan","Tuvalu","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States","Uruguay","Uzbekistan","Vanuatu","Vatican City","Venezuela","Vietnam","Yemen","Zambia","Zimbabwe");
				}else{
					$tmp = explode(",", str_replace(array("set(", ")", "'"), "", $field['type']));
				}
				
				//null
				if($field['Null']!="NO") { array_unshift($tmp, ""); }
								
				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
				foreach($tmp as $v) {
					if($v==$site[$field['name']])	{ print "<option value='$v' selected='selected'>$v</option>"; }
					else								{ print "<option value='$v'>$v</option>"; }
				}
				print "</select>";
			}
			//date and time picker
			elseif($field['type'] == "date" || $field['type'] == "datetime") {
				// just for first
				if($timeP==0) {
					print '<link rel="stylesheet" type="text/css" href="css/bootstrap/bootstrap-datetimepicker.min.css">';
					print '<script type="text/javascript" src="js/bootstrap-datetimepicker.min.js"></script>';
					print '<script type="text/javascript">';
					print '$(document).ready(function() {';
					//date only
					print '	$(".datepicker").datetimepicker( {pickDate: true, pickTime: false, pickSeconds: false });';
					//date + time
					print '	$(".datetimepicker").datetimepicker( { pickDate: true, pickTime: true } );';

					print '})';
					print '</script>';
				}
				$timeP++;
				
				//set size
				if($field['type'] == "date")	{ $size = 10; $class='datepicker';		$format = "yyyy-MM-dd"; }
				else							{ $size = 19; $class='datetimepicker';	$format = "yyyy-MM-dd"; }
								
				//field
				if(!isset($site[$field['name']]))	{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" '.$delete.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; }
				else								{ print ' <input type="text" class="'.$class.' form-control input-sm input-w-auto" data-format="'.$format.'" name="'. $field['nameNew'] .'" maxlength="'.$size.'" value="'. $site[$field['name']]. '" '.$delete.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; } 
			}	
			//boolean
			elseif($field['type'] == "tinyint(1)") {
				print "<select name='$field[nameNew]' class='form-control input-sm input-w-auto' rel='tooltip' data-placement='right' title='$field[Comment]'>";
				$tmp = array(0=>"No",1=>"Yes");
				//null
				if($field['Null']!="NO") { $tmp[2] = ""; }
				
				foreach($tmp as $k=>$v) {
					if(strlen($site[$field['name']])==0 && $k==2)	{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					elseif($k==$site[$field['name']])				{ print "<option value='$k' selected='selected'>"._($v)."</option>"; }
					else												{ print "<option value='$k'>"._($v)."</option>"; }
				}
				print "</select>";
			}	
			//text
			elseif($field['type'] == "text") {
				print ' <textarea class="form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" '.$delete.' rowspan=3 rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. $site[$field['name']]. '</textarea>'. "\n";
			}	
			//default - input field
			else {
				print ' <input type="text" class="ip_addr form-control input-sm" name="'. $field['nameNew'] .'" placeholder="'. $field['name'] .'" value="'. $site[$field['name']]. '" size="30" '.$delete.' rel="tooltip" data-placement="right" title="'.$field['Comment'].'">'. "\n"; 
			}
						
			print '	</td>'. "\n";
			print '</tr>'. "\n";		


		}
	}
	
	
	#print "<hr>";
	#print "<pre>";
		$sites = fetchSites ($siteId);
		
		$html = array();
		$children = array();
		$rootId = 0;									# root is 0

					
		# sites
		foreach ( $sites as $item ){
			#print_r($item);
			$children[$item['masterSiteId']][] = $item;
		}
		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop  = !empty( $children[$rootId] );
		
		# initializing $parent as the root
		$parent = $rootId;
		
		#$parent_stackF = array();
		$parent_stack  = array();
		
	
	#print_r($sites);
	#print "<hr>";
	#print_r($children);
	#print "</pre>";
	?>

	</table>
	</form>

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ print "<div class='alert alert-warning'><strong>"._('Warning').':</strong> '._('removing SITE will also remove SITE reference from belonging subnets')."!</div>"; }
		
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?> siteManagementEditFromSubnetButton" id="editSITEsubmittool"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>

	<!-- result -->
	<div class="<?php print $formId; ?>Result"></div>
</div>

