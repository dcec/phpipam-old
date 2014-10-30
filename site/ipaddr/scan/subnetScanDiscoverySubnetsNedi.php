<?php
/*
* Discover new hosts with NeDi
*******************************/
/* required functions */
require_once('../../../functions/functions.php');
require_once('../../../functions/functions-external.php');
/* verify that user is logged in */
isUserAuthenticated(true);
/* verify that user has write permissions for subnet */
$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
if($subnetPerm < 2) { die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }
# verify post */
CheckReferrer();
# get subnet details
#$subnet = getSubnetDetailsById ($_POST['subnetId']);
$subnet['subnet'] = $_POST['scanSubnet'];
$subnet['mask'] = $_POST['scanMask'];
#$addresses_temp = getIpAddressesBySubnetId ($_POST['subnetId']);
#foreach($addresses_temp as $r) {
# $addresses[$r['ip_addr']]=$r;
#}
$mysqli = @new mysqli($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);
/* check connection */
if ($mysqli->connect_errno) {
/* die with error */
die('<div class="alert alert-danger"><strong>'._('Database connection failed').'!</strong><br><hr>Error: '. mysqli_connect_error() .'</div>');
}
$min = '';
$max = '';
if ($_POST['scanSubnet'] && $_POST['scanMask']){
$calc = calculateSubnetDetailsNew ( $subnet['subnet'], $subnet['mask'], 0, 0, 0, 0 );
$min = Transform2decimal($subnet['subnet']);
$max = $min + $calc['maxhosts'];
}
$result = getNetworksFromNedi('subnet',$min,$max);
#$nedi_vlans = getVansFromNedi();
$tmp = $result;
$subnets = getSubnetsIndex('subnet');
$vlans = getVansbyIndex();
#$result = getDevicesAddressFromNedi($min,$max,'ifip');
#$nodes = getNodesFromNedi ($min,$max,'ifip');
$devices = getDeviceIndexHostname('hostname');
// add nodes on nedi list
#foreach($nodes as $k=>$n) {
# if (!array_key_exists($k, $result)) {$result[$k]=$n;}
#}
// remove already existing
if($subnets){
	foreach($subnets as $k=>$a) {
		if (array_key_exists($k, $result)) {
		#if($_POST['debug']==1) {print "<div class='alert alert-info'>Nedi mask: ".$result[$k]['mask']."; IPAM:".."</div>";}
			if ($subnets[$k]['mask'] == $result[$k]['prefix']){
				$up = 0;
				$update = $a;
				$update['action'] = "edit";
				$update['subnetId'] = $a['id'];
				$update['sectionIdNew'] = $a['sectionId'];

				if($subnets[$k]['description'] == "" && $subnets[$k]['description'] != $result[$k]['ifname']){$up = 1;$update['description'] = $result[$k]['ifname'];}		
				if($subnets[$k]['Gateway'] != transform2long($result[$k]['ifip'])){$up = 1;$update['Gateway'] = transform2long($result[$k]['ifip']);}
				if($subnets[$k]['Device'] != $result[$k]['device']){$up = 1;$update['Device'] = $result[$k]['device'];}
				if($subnets[$k]['port'] != $result[$k]['ifname']){$up = 1;$update['port'] = $result[$k]['ifname'];}
				
				$switch=$devices[$result[$k]['device']]['id'];		
				if ($result[$k]['device'] && $result[$k]['pvid'] && (!array_key_exists($result[$k]['pvid'], $vlans) || (array_key_exists($result[$k]['pvid'], $vlans) && !array_key_exists($switch, $vlans[$result[$k]['pvid']])))) {
					$nedi_vlans = getVansFromNedi($result[$k]['pvid'],$result[$k]['device']);
					if($result[$k]['pvid']>0){
						if($nedi_vlans[$result[$k]['pvid']]['vlanname']){
							insertNediVlan($nedi_vlans[$result[$k]['pvid']]['vlanname'],$result[$k]['pvid'],'',$switch);
							if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Vlans1: ".$result[$k]['pvid']." ".$nedi_vlans[$result[$k]['pvid']]['vlanname']." ".$switch."</div>";}
						}else{
							$nedi_vlans = getVansFromNedi($result[$k]['pvid'],'',$result[$k]['ifname']);
							if ($nedi_vlans) {
								insertNediVlan($result[$k]['ifname'],$result[$k]['pvid'],'',$switch);
								if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Vlans2: ".$result[$k]['pvid']." ".$result[$k]['ifname']." ".$switch."</div>";}
							}else{
								$nedi_vlans = getVansFromNedi($r['pvid']);
								insertNediVlan($result[$k]['ifname'],$result[$k]['pvid'],'',$switch);
								if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Vlans3: ".$result[$k]['pvid']." ".$result[$k]['ifname']." ".$switch."</div>";}
							}
						}
					}
					$vlans = getVansbyIndex();
				}
				if($subnets[$k]['vlanId'] != $vlans[$result[$k]['pvid']][$switch]['vlanId']){$up = 1;$update['vlanId'] = $vlans[$result[$k]['pvid']][$switch]['vlanId'];}
				if($up == 1){
					modifySubnetDetails ($update);
				}
				unset($result[$k]);
			}
		}
	}
}
// Add switch index and limit row
$count=1;
foreach($result as $k=>$r) {
	if ($r['device'] && !array_key_exists($r['device'], $devices)) {
		$device = getDevicesFromNedi ('device',$r['device']);
		if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Device: ".$device[$r['device']]['device']."</div>";}
		#insertNediDevice($device);
		$device_add = $device[$r['device']];
		$device_add['hostname'] = $r['device'];
		$device_add['description'] = $r['description'];
		$device_add['action'] = "add";$device_add['agent'] = "NeDi";
		$device_add['ip_addr'] = Transform2long($device[$r['device']][ip_addr]);
		$device_add['sections'] = $subnet['sectionId'];
		$device_add['siteId'] = $subnet['siteId'];
		#$device_add['sections'] = $sections;
		updateDeviceDetails($device_add);
		$devices = getDeviceIndexHostname('hostname');
	}
	if (array_key_exists($r['device'], $devices)) {
		$result[$k]['switch']=$devices[$r['device']]['id'];
		$dev_update = updateDeviceSection($devices[$r['device']]['id'],$_REQUEST['subnetId']);
		if( $dev_update && $_POST['debug']==1){
			print "<div class='alert alert-info'>Update section on Device: ".$dev_update."</div>";
		}
	}
	if ($r['device'] && $r['pvid'] && !array_key_exists($r['pvid'], $vlans)) {
		$nedi_vlans = getVansFromNedi($r['pvid'],$r['device']);	
		if($r['pvid']>0){
			if($nedi_vlans[$r['pvid']]['vlanname']){
				insertNediVlan($nedi_vlans[$r['pvid']]['vlanname'],$r['pvid'],'',$devices[$r['device']]['id']);
				if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Vlans: ".$r['pvid']." ".$nedi_vlans[$r['pvid']]['vlanname']."</div>";}
			}else{
				$nedi_vlans = getVansFromNedi($r['pvid'],'',$r['ifname']);
				if ($nedi_vlans) {
					insertNediVlan($r['ifname'],$r['pvid'],'',$devices[$r['device']]['id']);
					if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Vlans: ".$r['pvid']." ".$r['ifname']."</div>";}
				}else{
					$nedi_vlans = getVansFromNedi($r['pvid']);
					insertNediVlan($r['ifname'],$r['pvid'],'',$devices[$r['device']]['id']);
					if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Vlans: ".$r['pvid']." ".$r['ifname']."</div>";}
				}
			}
		}
		$vlans = getVansbyIndex();
	}
	if ($r['pvid']>0 && array_key_exists($result[$k]['switch'], $vlans[$r['pvid']])) {
		$result[$k]['vlan']=$vlans[$r['pvid']][$result[$k]['switch']]['vlanId'];
	}
	if ($count>100){
		unset($result[$k]);
		if ($count==101){print "<div class='alert alert-info'>"._("Not all record are imported on this run")."!</div>";}
	}
	$count++;
}
?>
<h5><?php print _('Scan results');?>:</h5>
<hr>
<?php
# error?
if(isset($error)) {
print "<div class='alert alert-danger'><strong>"._("Error").": </strong>$error</div>";
}
# wrong ping path
elseif($pingError) {
print '<div class="alert alert-danger">'._("Invalid ping path")."<hr>". _("You can set parameters for scan under functions/scan/config-scan.php").'</div>';
}
# empty
elseif(sizeof($result)==0) {
print "<div class='alert alert-info'>"._("No alive host found")."!</div>";
# errors?
if(isset($serr) && sizeof(@$serr)>0) {
print "<div class='alert alert-danger'>"._("Errors occured during scan")."! (".sizeof($serr)." errors)</div>";
}
}
# found alive
else {
print "<form name='".$_REQUEST['pingType']."Form' class='".$_REQUEST['pingType']."Form'>";
print "<table class='table table-striped table-top table-condensed'>";
// titles
print "<tr>";
print " <th>"._("Subnet")."</th>";
print " <th>"._("Mask")."</th>";
print " <th>"._("Description")."</th>";
print " <th>"._("Device")."</th>";
print " <th></th>";
print "</tr>";
// alive
$m=0;
foreach($result as $vlan) {
//resolve?
#if($scanDNSresolve) {
# $dns = gethostbyaddr ( transform2long($vlan['ifip']) );
#}
#else {
# $dns = transform2long($ip['ifip']);
#}
print "<tr class='result$m'>";
//ip
print "<td>".transform2long($vlan['subnet'])."</td>";
//description
print "<td>";
print " <input type='hidden' name='subnet$m' value=".$vlan['subnet'].">";
#print " <input type='text' class='form-control input-sm' name='mac$m' value=".rtrim(chunk_split($vlan['ifmac'],2,":"),":").">";
print " <input type='text' class='form-control input-sm' name='mask$m' value='".$vlan['prefix']."'>";
print " <input type='hidden' name='description$m' value=".$vlan['ifname'].">";
#print " <input type='hidden' name='mac$m' value=".rtrim(chunk_split(substr($vlan['ifmac'],0,12),2,":"),":").">";
print " <input type='hidden' name='vlanId$m' value=".$vlan['vlan'].">";
print " <input type='hidden' name='gateway$m' value=".transform2long($vlan['ifip']).">";
print "</td>";
//hostname
print "<td>";
print " <input type='text' class='form-control input-sm' name='port$m' value='".$vlan['ifname']."'>";
print "</td>";
print "<td>";
print " <input type='text' class='form-control input-sm' name='device$m' value=".$vlan['device'].">";
print "</td>";
//remove button
print "<td><a href='' class='btn btn-xs btn-danger resultRemove' data-target='result$m'><i class='fa fa-times'></i></a></td>";
print "</tr>";

$m++;
}
//result
print "<tr>";
print " <td colspan='5'>";
print "<div id='subnetScanAddResult'></div>";
print " </td>";
print "</tr>";	
//submit
print "<tr>";
print " <td colspan='4'>";
print " <a href='' class='btn btn-sm btn-success pull-right' id='saveScanResults' data-script='".$_REQUEST['pingType']."' data-subnetId='".$_REQUEST['subnetId']."'><i class='fa fa-plus'></i> "._("Add discovered subnets")."</a>";
print " </td>";
print "</tr>";
print "</table>";
print "</form>";
}
# debug?
if($_POST['debug']==1) {
print "<hr>";
print "<pre>";
print_r($tmp);
print "</pre>";
print "<pre>";
print_r($subnets);
print "</pre>";
print "<pre>";
print_r($vlans[$r['pvid']]);
print "</pre>";
}
?>