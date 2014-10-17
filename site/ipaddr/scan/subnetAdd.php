<?php

/*
 * Scan subnet for new hosts
 ***************************/

/* required functions */
require_once('../../../functions/functions.php'); 

/* verify that user is logged in */
isUserAuthenticated(false);

/* verify that user has write permissions for subnet */
$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
if($subnetPerm < 2) 								{ die('<div class="pHeader">Error</div><div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div><div class="pFooter"><button class="btn btn-sm btn-default hidePopups">'._('Cancel').'</button></div>'); }

/* verify post */
CheckReferrer();

# get all site settings
$settings = getAllSettings();

# get subnet details
#$subnet = getSubnetDetailsById ($_POST['subnetId']);
$sectionName = getSectionDetailsById ($_POST['sectionId']);

# IPv6 is not supported
if ( IdentifyAddress( $subnet['subnet'] ) == "IPv6") { die('<div class="pHeader">Error</div><div class="alert alert-danger">'._('IPv6 scanning is not supported').'!</div><div class="pFooter"><button class="btn btn-sm btn-default hidePopups">'._('Cancel').'</button></div>'); }

# get all IP addresses
$ip_addr = getIpAddressesBySubnetId ($_POST['subnetId']);
?>


<!-- header -->
<div class="pHeader"><?php print _('Add subnet'); ?></div>


<!-- content -->
<div class="pContent">

	<table class="table table-noborder table-condensed">

    <!-- subnet -->
    <tr>
        <td class="middle"><?php print _('Sections'); ?></td>
        <td><?php print $sectionName['name']; ?></td>
    </tr>
    
    <!-- Scan type -->
    <tr>
    	<td><?php print _('Select Scan type'); ?></td>
    	<td>
    		<select name="scanType" id="scanType" class="form-control input-sm input-w-auto">
    			<!-- Discovery scans -->
	    		<optgroup label="<?php print _('Discovery scans');?>">
					<?php if ($settings['enableNEDI'] == 1) {
						print "<option value='DiscoverySubnetsNedi'>NeDi "._('Subnets')."</option>";
					}
					if ($settings['enableGLPI'] == 1) {
					#	print "<option value='DiscoverySubnetsGlpi'>Glpi "._('Subnets')."</option>";
					} ?>
<!-- 		    		<option value="DiscoveryNmap">NMap <?php print _('scan');?></option> -->
	    		</optgroup>
    			<!-- Status update scans -->
	    		<optgroup label="<?php print _('Status update scans');?>">
					<?php if ($settings['enableNEDI'] == 1) {
					#	print "<option value='UpdateSubnetsNedi'>NeDi "._('Subnets')."</option>";
						print "<option value='UpdateDevice'>NeDi "._('Devices')."</option>";
					}
					if ($settings['enableGLPI'] == 1) {
					#	print "<option value='UpdateSubnetsGlpi'>Glpi "._('Subnets')."</option>";
					} ?>
<!-- 		    		<option value="UpdateNmap">NMap <?php print _('scan');?></option> -->
	    		</optgroup>
			</select>
    	</td>
    </tr>
    
	<tr>
        <td class="middle"><?php print _('Subnet'); ?></td>
        <td>
            <input type="text" class="form-control input-sm input-w-150" name="scanSubnet"   placeholder="<?php print _('subnet'); ?>">
        </td>
		<td>/</td>
		<td>
            <input type="text" class="form-control input-sm input-w-100" name="scanMask"   placeholder="<?php print _('mask'); ?>">
        </td>
        <td class="info2">
        	<?php print _('Enter subnet in CIDR format (e.g. 192.168.1.1/24)'); ?>
        </td>
    </tr>
	
    
    <tbody style="border:0px;">
    <tr>
    	<td><?php print _('Debug');?></td>	
    	<td>
    		<input type="checkbox" name="debug">
    	</td>
    </tr>
    </tbody>
        
    </table>

    <!-- warning -->
    <div class="alert alert-warning alert-block" id="alert-scan">
    &middot; <?php print _('Discovery scans discover new subnets/vlans');?><br>
    </div>
    
    <!-- result -->
	<div id="subnetScanResult"></div>

</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default btn-success" id="subnetScanSubmit" data-subnetId='<?php print $_POST['sectionId']; ?>'><i class="fa fa-gears"></i> <?php print _('Scan subnets'); ?></button>
	</div>

	<div class="subnetTruncateResult"></div>
</div>