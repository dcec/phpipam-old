<?php

/*
 * Discover new hosts with Glpi
 *******************************/

/* required functions */
$dir = dirname(__FILE__);
require_once($dir.'/../../../functions/functions.php');
require_once($dir.'/../../../functions/functions-external.php');
include_once($dir.'/../../../functions/functions-mail.php');

/* verify that user is logged in */
#isUserAuthenticated(true);

/* verify that user has write permissions for subnet */
#$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
#if($subnetPerm < 2) 		{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

# verify post */
#CheckReferrer();

# get subnet details
#$subnet = getSubnetDetailsById ($_POST['subnetId']);
#$addresses_temp = getIpAddressesBySubnetId ($_POST['subnetId']);
#foreach($addresses_temp as $r) {
#	$addresses[$r['ip_addr']]=$r;	
#}

$mysqli = @new mysqli($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']); 
/* check connection */
if ($mysqli->connect_errno) {
	/* die with error */
    if($_POST){die('<div class="alert alert-danger"><strong>'._('Database connection failed').'!</strong><br><hr>Error: '. mysqli_connect_error() .'</div>');}
	else{die();}
}

$subnetIds = array();
$total = array();

if($_POST){

	/* verify that user is logged in */
	isUserAuthenticated(true);

	/* verify that user has write permissions for subnet */
	$subnetPerm = checkSubnetPermission ($_REQUEST['subnetId']);
	if($subnetPerm < 2) 		{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

	# verify post */
	CheckReferrer();
	array_push($subnetIds,$_POST['subnetId']);
}else{

	if($argv[1]){
		array_push($subnetIds,$argv[1]);
	}else{
		foreach(getSubnetsIdPingSubnet() as $r) {
			array_push($subnetIds,$r['id']);	
		}
	}
}


foreach($subnetIds as $subnetId) {

	# get subnet details
	$subnet = getSubnetDetailsById ($subnetId);
	$addresses_temp = getIpAddressesBySubnetId ($subnetId);
	foreach($addresses_temp as $r) {
		$addresses[$r['ip_addr']]=$r;	
	}
	#print "Array: ".$r['id']."\n";
	$calc = calculateSubnetDetailsNew ( transform2long($subnet['subnet']), $subnet['mask'], 0, 0, 0, 0 );
	$min = $subnet['subnet'];
	$max = $min + $calc['maxhosts'];

	$result = getDevicesAddressFromGlpi($min,$max,'ifip');
	#$nodes = getNodesFromNedi ($min,$max,'ifip');	
	$devices = getDeviceIndexHostname('hostname');
	
	// add nodes on nedi list
	#foreach($nodes as $k=>$n) {
	#	if (!array_key_exists($k, $result)) {$result[$k]=$n;}
	#}
	// remove already existing
	if($addresses){
	foreach($addresses as $k=>$a) {
		if (array_key_exists($k, $result)) {
			unset($result[$k]);
		}
		if ($a['excludePing']!="0"){
			unset($result[$k]);
		}
	}
	}
	#print_r($addresses);
	// Add switch index and limit row
	$count=1;
		foreach($result as $k=>$r) {
				$update[$k]['id'] = $addresses[$k]['id'];
				if(!$addresses[$k]['mac']){$update[$k]['mac'] = $result[$k]['macaddress'];}
				if(!$addresses[$k]['port']){$update[$k]['port'] = $result[$k]['portname'];}
				if ($r['hostname'] && !array_key_exists($r['hostname'], $devices)) {
					#$device = getDevicesFromNedi ('device',$r['device']);
					if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Device: ".$r['hostname']."</div>";}
					#$device[$result[$k]['hostname']]['device'] = $result[$k]['hostname'];
					#$device[$result[$k]['hostname']]['ip_addr'] = $result[$k]['ip_addr'];;
					#$device[$result[$k]['hostname']]['type'] = $result[$k]['type'];
					#$device[$result[$k]['hostname']]['model'] = $result[$k]['model'];
					#$device[$result[$k]['hostname']]['description'] = $result[$k]['description'];
					#$device[$result[$k]['hostname']]['description'] = $result[$k]['description'];
					
					$device_add = $result[$k];
					$device_add['hostname'] = $r['hostname'];
					#$device_add['description'] = $r['description'];
					$device_add['action'] = "add";$device_add['agent'] = "glpi";
					$device_add['ip_addr'] = $result[$k]['ipaddress'];
					$device_add['sections'] = $subnet['sectionId'];
					$device_add['siteId'] = $subnet['siteId'];
					#$device_add['sections'] = $sections;
					updateDeviceDetails($device_add);
			
					#insertNediDevice($device,$subnet['sectionId']);
					$devices = getDeviceIndexHostname('hostname');
				}
				if (array_key_exists($r['hostname'], $devices)) {
					#$result[$k]['switch']=$devices[$r['device']]['id'];
					$update[$k]['switch']=$devices[$result[$k]['hostname']]['id'];
					#$dev_update = updateDeviceSection($devices[$r['device']]['id'],$subnetId);
					$dev_update = updateDeviceSection($devices[$r['hostname']]['id'],$_REQUEST['subnetId']);
					if( $dev_update && $_POST['debug']==1){
						print "<div class='alert alert-info'>Update section on Device: ".$dev_update."</div>";
					}
				}
				$result[$k]['switch']=$devices[$r['hostname']]['id'];
				$result[$k]['subnetname'] = $subnet['description'];
				$total[$subnetId][$k] = $result[$k];
				if ($count>100){
					unset($result[$k]);
					if ($count==101){print "<div class='alert alert-info'>"._("Not all record are imported on this run")."!</div>";}
				}
				$count++;
			#}
		}
	#}
}
if($update){updateLastSeenValue($update);}
if($_POST){
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
	print "	<th>"._("IP")."</th>";
	print "	<th>"._("Description")."</th>";
	print "	<th>"._("Hostname")."</th>";
	print "	<th></th>";
	print "</tr>";
	
	// alive
	$m=0;
	foreach($result as $ip) {
	
		//resolve?
		#if($scanDNSresolve) {
			$dns = gethostbyaddr ( transform2long($ip['ifip']) );
		#}
		#else {
		#	$dns = transform2long($ip['ifip']);
		#}
		
		print "<tr class='result$m'>";
		//ip
		print "<td>".transform2long($ip['ifip'])."</td>";
		//description
		print "<td>";
		#print "	<input type='text' class='form-control input-sm' name='mac$m' value=".rtrim(chunk_split($ip['ifmac'],2,":"),":").">";
		print "	<input type='text' class='form-control input-sm' name='description$m' value='".$ip['description']."'>";
		print "	<input type='hidden' name='ip$m' value=".transform2long($ip['ifip']).">";
		print "	<input type='hidden' name='mac$m' value=".$ip['macaddress'].">";
		print "	<input type='hidden' name='ifname$m' value=".$ip['portname'].">";
		print "	<input type='hidden' name='switch$m' value=".$ip['switch'].">";
		print "</td>";
		//hostname
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='dns_name$m' value='".$dns."'>";
		print "</td>";
		//remove button
		print 	"<td><a href='' class='btn btn-xs btn-danger resultRemove' data-target='result$m'><i class='fa fa-times'></i></a></td>";
		print "</tr>";
		
		$m++;
	}
	
	//result
	print "<tr>";
	print "	<td colspan='4'>";
	print "<div id='subnetScanAddResult'></div>";
	print "	</td>";
	print "</tr>";	
	
	//submit
	print "<tr>";
	print "	<td colspan='4'>";
	print "		<a href='' class='btn btn-sm btn-success pull-right' id='saveScanResults' data-script='".$_REQUEST['pingType']."' data-subnetId='".$_REQUEST['subnetId']."'><i class='fa fa-plus'></i> "._("Add discovered hosts")."</a>";
	print "	</td>";
	print "</tr>";
	
	print "</table>";
	print "</form>";
}


# debug?
if($_POST['debug']==1) {
	print "<hr>";
	print "<pre>";
	print_r($subnet);
	print "</pre>";
	print "<pre>";
	print_r($result);
	print "</pre>";
	print "<hr>";
	print "<pre>";
	print_r($addresses);
	print "</pre>";
	print "<pre>";
	print_r($devices);
	print "</pre>";
}
}else{
	#if($result){
	#print_r($total);
	foreach($total as $k=>$result) {
		foreach($result as $ip) {
			$subject = "";
			#print "Debug:".$ip['ip_addr'];
			/* set title */
			$title = "New ip from Glpi not defined";

			/* Preset content */
			#$content .= '&bull; '._('IP address').': ' . "\t" . $ip['ip_addr'] . '/' . $subnet['mask']. "\n";
			$content .= '&bull; '._('IP address').': ' . "\t" . transform2long($ip['ifip']). "\n";
			
			$content .= '&bull; '._('Subnet').': ' . "\t" . $ip['subnetname']. "\n";

			#$content .= '&bull; '._('IP Mask').': ' . "\t" . $subdetail['Subnet netmask']. "\n";

			#if(!empty($subnet['Gateway'])) {
			#	$content .= '&bull; '._('IP Gateway').': ' . "\t" . $subnet['Gateway']. "\n";
			#}
			# desc
			if(!empty($ip['description'])) {
				$content .= '&bull; '._('Description').':' . "\t" . $ip['description'] . "\n";
			}
			# hostname
			$dns = gethostbyaddr ( transform2long($ip['ifip']) );
			if(!empty($dns)) {
				$content .= '&bull; '._('Hostname').':' . "\t" 	 . $dns . "\n";
			}
			# subnet desc
			#if(!empty($subnet['description'])) {
			#$content .= '&bull; '._('Subnet desc').': ' . "\t" . $subnet['description']. "\n";
			#}
			# VLAN
			#if(!empty($subnet['vlan'])) {
			#$content .= '&bull; '._('VLAN').': ' . "\t\t" 	 . $subnet['vlan'] . "\n";
			#}
			# Switch
			#if(!empty($ip['switch'])) {
			#	# get device by id
			#	$device = getDeviceDetailsById($ip['switch']);
			#	$content .= "&bull; "._('Device').":\t\t"		 . $device['hostname'] . "\n";
			#}
			# port
			if(!empty($ip['port'])) {
				$content .= "&bull; "._('Port').":\t"			 . $ip['portname'] . "\n";
			}
			# custom
			$myFields = getCustomFields('ipaddresses');
			if(sizeof($myFields) > 0) {
				foreach($myFields as $myField) {
					if(!empty($ip[$myField['name']])) {
						$content .=  '&bull; '. $myField['name'] .":\t". $ip[$myField['name']] ."\n";
					}
				}
			}
			$content .= "\n";
		}
	}
	
	$toemail = array();
	$toadmins = getAllAdminUsers ();
	foreach($toadmins as $toadmin) {
		array_push($toemail,$toadmin['email']);
	}
	$toemail = implode(',',$toemail);
	#print $content;
	#print '<div class="alert alert-danger">'._('Sending mail failed').'!'.$sender['email'].'</div>';
	$content .= "\nThese new ip are not yet added!";
	if(!sendIPnotifEmail($toemail, $title, $content))	{ print '<div class="alert alert-danger">'._('Sending mail failed').'!'.$sender['email'].'</div>\n'; }
	else																						{ print '<div class="alert alert-success">'._('Sending mail succeeded').'!</div>\n'; }
}
?>