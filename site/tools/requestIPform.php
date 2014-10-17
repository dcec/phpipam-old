<?php
require_once('../../functions/functions.php');
$mail = getActiveUserDetails();
?>
<!-- header -->
<div class="pHeader"><?php print _('IP request form');?></div>

<!-- content -->
<div class="pContent editIPAddress">

	<form name="requestIP" id="requestIP">

	<table id="requestIP" class="table table-striped table-condensed">

	<tr>
		<td><?php print _('IP address proposed');?> *</td>
		<td>
			<?php  
			require_once('../../functions/functions.php');
			# get first IP address
			$first = transform2long (getFirstAvailableIPAddress ($_POST['subnetId']));
			# get subnet details
			$subnet = getSubnetDetailsById($_POST['subnetId']);
			?>
			<div class="input-group">
			<input type="text" name="ip_addr" class="ip_addr form-control" size="30" value="<?php print $first; ?>">
			<span class="input-group-addon">
    			<i class="fa fa-gray fa-info" rel="tooltip" data-html='true' data-placement="left" title="<?php print _('You can add,edit or delete multiple IP addresses<br>by specifying IP range (e.g. 10.10.0.0-10.10.0.25)'); ?>"></i>
    		</span>
			</div>
			
			<input type="hidden" name="subnetId" value="<?php print $subnet['id']; ?>">
		</td>
	</tr>

	<!-- description -->
	<tr>
		<td><?php print _('Description');?></td>
		<td><input class="form-control" type="text" name="description" size="30" placeholder="<?php print _('Enter description');?>"></td>
	</tr>

	<!-- DNS name -->
	<tr>
		<td><?php print _('DNS name');?></td>
		<td><input type="text" class="form-control" name="dns_name" size="30" placeholder="<?php print _('hostname');?>"></td>
	</tr>

	<!-- owner -->
	<tr class="owner">
		<td><?php print _('Owner');?></td>
		<td>	
		<!-- autocomplete -->
		<input type="text" class="form-control" name="owner" id="owner" size="30" placeholder="<?php print _('Owner of IP address');?>" value="<?php print $mail['real_name']; ?>"></td>
	</tr>

	<!-- requester -->
	<tr>
		<td><?php print _('Requester email');?> *</td>
		<td>
			<input type="text" class="form-control" name="requester" size="30" placeholder="<?php print _('your email address');?>" value="<?php print $mail['email']; ?>"></textarea>
		</td>
	</tr>

	<!-- comment -->
	<tr>
		<td><?php print _('Additional comment');?></td>
		<td style="padding-right:20px;">
			<textarea name="comment" class="form-control" rows="2" style="width:100%;" placeholder="<?php print _('Enter additional details for request if they are needed');?>"></textarea>
		</td>
	</tr>


	</table>
	</form>

</div>

<!-- footer -->
<div class="pFooter">
	<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel');?></button>
	<button class="btn btn-sm btn-default" id="requestIPAddressSubmit"><?php print _('Request IP');?></button>
	<!-- result  -->
	<div id="requestIPresult"></div>
</div>
