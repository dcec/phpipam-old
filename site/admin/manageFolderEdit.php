<?php

/*
 * Print edit folder
 *********************/

/* required functions */
require_once('../../functions/functions.php'); 

/* verify that user is logged in */
isUserAuthenticated(false);

/* verify that user has permissions if add */
if($_POST['action'] == "add") {
	$sectionPerm = checkSectionPermission ($_POST['sectionId']);
	if($sectionPerm != 3 && $subnetPerm != 3) {
		die("<div class='pHeader'>"._('Error')."</div><div class='pContent'><div class='alert alert-danger'>"._('You do not have permissions to add new folder in this section')."!</div></div><div class='pFooter'><button class='btn btn-sm btn-default hidePopups'>"._('Close')."</button>");
	}
}
/* otherwise check subnet permission */
else {
	$subnetPerm = checkSubnetPermission ($_POST['subnetId']);
	if($subnetPerm != 3) {
		die("<div class='pHeader'>"._('Error')."</div><div class='pContent'><div class='alert alert-danger'>"._('You do not have permissions to add edit/delete this folder')."!</div></div><div class='pFooter'><button class='btn btn-sm btn-default hidePopups'>"._('Close')."</button>");
	}
}

/* verify post */
CheckReferrer();

/* get all site settings */
$settings = getAllSettings();

# we are editing or deleting existing subnet, get old details
if ($_POST['action'] != "add") {
    $subnetDataOld = getSubnetDetailsById ($_POST['subnetId']);
}
# we are adding new subnet - get section details
else {
	# for selecting master subnet if added from subnet details!
	if(strlen($_REQUEST['subnetId']) > 0) {
    	$tempData = getSubnetDetailsById ($_POST['subnetId']);	
    	$subnetDataOld['masterSubnetId'] = $tempData['id'];			// same master subnet ID for nested
    	$subnetDataOld['vlanId'] 		 = $tempData['vlanId'];		// same default vlan for nested
    	$subnetDataOld['vrfId'] 		 = $tempData['vrfId'];		// same default vrf for nested
	}
	$sectionName = getSectionDetailsById ($_POST['sectionId']);
}

/* get custom subnet fields */
$customSubnetFields = getCustomFields('subnets');


# set readonly flag
if($_POST['action'] == "edit" || $_POST['action'] == "delete")	{ $readonly = true; }
else															{ $readonly = false; }
?>



<!-- header -->
<div class="pHeader"><?php print ucwords(_("$_POST[action]")); ?> <?php print _('folder'); ?></div>


<!-- content -->
<div class="pContent">

	<form id="editFolderDetails">
	<table class="editSubnetDetails table table-noborder table-condensed">

    <!-- name -->
    <tr>
        <td class="middle"><?php print _('Name'); ?></td>
        <td>
            <input type="text" class="form-control input-sm input-w-250" id="field-description" name="description" value="<?php print @$subnetDataOld['description']; ?>">
        </td>
        <td class="info2"><?php print _('Enter folder name'); ?></td>
    </tr>  

    <?php if($_POST['action'] != "add") { ?>
    <!-- section -->
    <tr>
        <td class="middle"><?php print _('Section'); ?></td>
        <td>
        	<select name="sectionIdNew" class="form-control input-sm input-w-auto">
            	<?php
           		$sections = fetchSections();
            
            	foreach($sections as $section) {
            		/* selected? */
					if (checkSectionPermission ($section['id']) > 0){
            		if($_POST['sectionId'] == $section['id']) { print '<option value="'. $section['id'] .'" selected>'. $section['name'] .'</option>'. "\n"; }
            		else 									  { print '<option value="'. $section['id'] .'">'. $section['name'] .'</option>'. "\n"; }
					}
            	}
            ?>
            </select>
        	
        	</select>
        </td>
        <td class="info2"><?php print _('Move to different section'); ?></td>
    </tr>  
    <?php } ?>
    
    <!-- Master subnet -->
    <tr>
        <td><?php print _('Master Folder'); ?></td>
        <td>
        	<?php printDropdownMenuBySectionFolders($_POST['sectionId'], $subnetDataOld['masterSubnetId']); ?>
        </td>
        <td class="info2"><?php print _('Enter master folder if you want to nest it under existing folder, or select root to create root folder'); ?>!</td>
    </tr>
    
    <!-- hidden values -->
    <input type="hidden" name="sectionId"       value="<?php print $_POST['sectionId'];    ?>">
    <input type="hidden" name="subnetId"        value="<?php print $_POST['subnetId'];     ?>">       
    <input type="hidden" name="action"    		value="<?php print $_POST['action']; ?>">
	<input type="hidden" name="vlanId" 			value="0">
	<input type="hidden" name="vrfId" 			value="0">


    <?php
	# custom Subnet fields
    if(sizeof($customSubnetFields) > 0) {
    	print "<tr>";
    	print "	<td colspan='3' class='hr'><hr></td>";
    	print "</tr>";
	    foreach($customSubnetFields as $field) {
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
		# retain newlines
	    #	$subnetDataOld[$field['name']] = str_replace("\n", "\\n", $subnetDataOld[$field['name']]);
	    	
		#    print "<tr>";
		#    print "	<td class='middle'>$field[name]</td>";
		#    print "	<td colspan='2'>";
		#    print "	<input type='text' class='form-control input-sm' id='field-$field[nameNew]' name='$field[nameNew]' value='".$subnetDataOld[$field['name']]."' placeholder='".$subnetDataOld[$field['name']]."'>";
		#    print " </td>";
		#    print "</tr>";
	   # }
    }
    
    # divider
    print "<tr>";
    print "	<td colspan='3' class='hr'><hr></td>";
    print "</tr>";
    ?>
    
    </table>
    </form> 
    
    <?php
    # warning if delete
    if($_POST['action'] == "delete") {
	    print "<div class='alert alert-warning' style='margin-top:0px;'><strong>"._('Warning')."</strong><br>"._('Removing subnets will delete ALL underlaying subnets and belonging IP addresses')."!</div>";
    }
    ?>


</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<?php
		//if action == edit and location = IPaddresses print also delete form
		if(($_POST['action'] == "edit") && ($_POST['location'] == "IPaddresses") ) {
			print "<button class='btn btn-sm btn-default btn-danger editFolderSubmitDelete' data-action='delete' data-subnetId='$subnetDataOld[id]'><i class='fa fa-trash-o'></i> "._('Delete folder')."</button>";
		}
		?>
		<button class="btn btn-sm btn-default editFolderSubmit <?php if($_POST['action']=="delete") print "btn-danger"; else print "btn-success"; ?>"><i class="<?php if($_POST['action']=="add") { print "fa fa-plus"; } else if ($_POST['action']=="delete") { print "fa fa-trash-o"; } else { print "fa fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	
	<div class="manageFolderEditResult"></div>
</div>