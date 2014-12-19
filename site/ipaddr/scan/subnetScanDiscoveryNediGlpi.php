<?php

/*
 * Discover new hosts with NeDi
 *******************************/

/* required functions */
$dir = dirname(__FILE__);
require_once($dir.'/../../../functions/functions.php');
require_once($dir.'/../../../functions/functions-external.php'); 
include_once($dir.'/../../../functions/functions-mail.php');
$debug = $_POST['debug'];

$mysqli = @new mysqli($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']); 
/* check connection */
if ($mysqli->connect_errno) {
	/* die with error */
    if($_POST){die('<div class="alert alert-danger"><strong>'._('Database connection failed').'!</strong><br><hr>Error: '. mysqli_connect_error() .'</div>');}
	else{die();}
}

$subnetIds = array();
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

$devices = getDeviceIndexHostname('hostname');

foreach($subnetIds as $subnetId) {
	
	
	# get subnet details
	$subnet = getSubnetDetailsById ($subnetId);
	$addresses_temp = getIpAddressesBySubnetId ($subnetId);
	foreach($addresses_temp as $r) {
		$addresses[$r['ip_addr']]=$r;	
	}

	$calc = calculateSubnetDetailsNew ( transform2long($subnet['subnet']), $subnet['mask'], 0, 0, 0, 0 );
	$min = $subnet['subnet'];
	$max = $min + $calc['maxhosts'];

	if(!$_POST){print "Discovery ".transform2long($subnet['subnet'])."/".$subnet['mask']."\n";}
	
	$result = getDevicesAddressFromNedi($min,$max,'ifip',30,'devip');
	foreach($result as $k=>$n) {
		if(!$n['ifip'] && $n['devip']){
			$result[$k]['ifip'] = $result[$k]['devip'];
		}
	}
	$result_pre = $result;
	$nodes = getNodesFromNedi ($min,$max,'ifip');	
	$balanced = getBalancedFromNedi ($min,$max,'ifip');
	$nat = getNatFromNedi ($min,$max);
	$glpi = getDevicesAddressFromGlpi($min,$max,'ifip');
	
	// add nodes on nedi list
	foreach($nodes as $k=>$n) {
		if (!array_key_exists($k, $result)) {
			$result[$k]=$n;
		}
	}
	
	foreach($nat as $k=>$n) {
		if (!array_key_exists($k, $result)){
			if($k >= $min && $k <= $max){$result[$k]=$n;}
		}else{
			foreach($nat[$k] as $n=>$a) {
				if (!array_key_exists($n, $result[$k])){$result[$k][$n] = $nat[$k][$n];}
			}
		}
		
		if ($n['type'] == "DIP" && $k >= $min && $k <= $max) {
			$result[$k]=$n;
			$result[$k]['Address Type'] = "nat";			
			$result[$k]['description'] .= "DIP: ID ".$n['nmask'];
			#print "<div class='alert alert-info'>nat: ".$n['m_ip']." ".$n['type']." ".$result[$k]['description']."</div>";
		}
		if ($n['iptype'] == "mip" && $k >= $min && $k <= $max) {
			$result[$k]=$n;
			$result[$k]['Address Type'] = "nat";
			if($n['type'] == "MIP"){
				$ipdet = getIpAddrDetailsByip($n['nip']);
				if(array_key_exists('subnetId',$ipdet)){
					$subdet = getSubnetDetailsById ($ipdet['subnetId']);
					$devicedet = getDeviceById ($ipdet['switch']);
					$result[$k]['description'] .= "MIP TO: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$n['n_ip']."</a> ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
				}else{
					$result[$k]['description'] .= "MIP TO: ".$n['n_ip']."";
				}
			}
			if($n['type'] == "VIP"){
				foreach($n['vip'] as $i=>$v) {
					$ipdet = getIpAddrDetailsByip($v['nip']);
					if ($result[$k]['description']) { $result[$k]['description'] .= "<br>";}
					if(array_key_exists('subnetId',$ipdet)){
						$subdet = getSubnetDetailsById ($ipdet['subnetId']);
						$devicedet = getDeviceById ($ipdet['switch']);
						$result[$k]['description'] .= "VIP TO: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$v['n_ip']."</a>:".$v['mport']." ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
					}else{
						$result[$k]['description'] .= "VIP TO: ".$v['n_ip']."";
					}
				}
			}
		}

		
	}
	
	foreach($glpi as $k=>$n) {
	#print "<div class='alert alert-info'>nat: ".$result[$k]['description']."</div>";
		if (!array_key_exists($k, $result)){
			if($k >= $min && $k <= $max){$result[$k]=$n;}
			#print "<div class='alert alert-info'>not array_key_exists: ".$k."</div>";
		}else{
			#$result[$k]['glpi'] = $n;
			#$result[$k]['glpi']['commments'] = trim(preg_replace('/\s+/', ' ', $result[$k]['glpi']['commments']));
			#print "<div class='alert alert-info'>array_key_exists: ".$k."</div>";
			foreach($glpi[$k] as $n=>$a) {
				if (!array_key_exists($n, $result[$k])){$result[$k][$n] = $glpi[$k][$n];}
			}
			#print "<div class='alert alert-info'>array_key_exists: ".$result[$k]['commments']."</div>";
			$result[$k]['commments'] = trim(preg_replace('/\s+/', ' ', $result[$k]['commments']));
		}
	}
	
	#print "<pre>";
	#print_r($result);
	#print "</pre>";
	// remove already existing
	if($addresses){
		foreach($addresses as $k=>$a) {
			if (array_key_exists($k, $result)) {
				unset($result[$k]);
			}
		}
	}
	
	foreach($result as $k=>$n) {
		#print "<div class='alert alert-info'>nat: ".$result[$k]['description']."</div>";
		if(array_key_exists($k, $balanced)){
			foreach($balanced[$k] as $n=>$a) {
				if (!array_key_exists($n, $result[$k])){$result[$k][$n] = $balanced[$k][$n];}
			}
			$result[$k]['Address Type'] = "balanced";
			#if ($result[$k]['description']) { $result[$k]['description'] .= "<br>";}
			$descr = $result[$k]['description'];
			#$result[$k]['description'] .= "BALANCED: " . $result[$k]['farm']." ".$result[$k]['clpo'];
			if($result[$k]['farm']){
			
			foreach($result[$k]['farm'] as $i=>$f) {
				$result[$k]['hostname'] = $f['device'];
				$result[$k]['ifname'] = $f['ifname'];
				if ($descr) { $descr .= "<br>";}
				$descr .= "BALANCED: " . $i." ".$f['clpo'];
				if($f['bfarm']){
					foreach($f['bfarm'] as $b) {
						if ($descr) { $descr .= "<br>";}
						$ipdet = getIpAddrDetailsByip($b['rsip']);
						if(array_key_exists('subnetId',$ipdet)){
							$subdet = getSubnetDetailsById ($ipdet['subnetId']);
							$devicedet = getDeviceById ($ipdet['switch']);
							$descr .= "&nbsp;&nbsp;TO <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".Transform2long($b['rsip'])."</a> ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
						}else{
							$descr .= "&nbsp;&nbsp;TO ".Transform2long($b['rsip'])."";
						}
					}
				}
			#print "<div class='alert alert-info'>BAL:".transform2long($k).":".$descr."</div>";
			#if($addresses[$k]['description'] != $descr){$update[$k]['description'] = $descr;}
			}
			}
			$result[$k]['description'] = $descr;
			
		}
		if(array_key_exists($k, $nat) && $nat[$k]['iptype'] == "nip"){
			if($nat[$k]['type'] == "MIP"){
				$ipdet = getIpAddrDetailsByip($nat[$k]['mip']);
				if ($result[$k]['description']) { $result[$k]['description'] .= "<br>";}
				if(array_key_exists('subnetId',$ipdet)){
					$subdet = getSubnetDetailsById ($ipdet['subnetId']);
					$devicedet = getDeviceById ($ipdet['switch']);
					$result[$k]['description'] .= "MIPPED WITH: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$nat[$k]['m_ip']."</a> ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
				}else{
					$result[$k]['description'] .= "MIPPED WITH: ".$nat[$k]['m_ip']."";
				}				
			}
			if($nat[$k]['type'] == "VIP"){
				foreach($nat[$k]['vip'] as $i=>$v) {
					$ipdet = getIpAddrDetailsByip($v['mip']);
					if ($result[$k]['description']) { $result[$k]['description'] .= "<br>";}
					if(array_key_exists('subnetId',$ipdet)){
						$subdet = getSubnetDetailsById ($ipdet['subnetId']);
						$devicedet = getDeviceById ($ipdet['switch']);
						$result[$k]['description'] .= "VIPPED WITH: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$v['m_ip']."</a>:".$v['mport']." ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";					
					}else{
						$result[$k]['description'] .= "VIPPED WITH: ".$v['m_ip']."";
					}
				}
			}
		}
		#print "<div class='alert alert-info'>nat: ".$result[$k]['description']."</div>";
	}	

	// Add switch index and limit row
	$count=1;
	foreach($result as $k=>$r) {
		#if(array_key_exists('glpi', $r) && array_key_exists('hostname', $r['glpi'])) {
		#	$result[$k]['hostname'] = $r['glpi']['hostname'];
		#	$r['hostname'] = $r['glpi']['hostname'];
		#	$from_glpi = 1;
		#}else{
		#	$result[$k]['hostname'] = $r['device'];
		#	$r['hostname'] = $r['device'];
		#}
		if ($r['hostname']){
			#$update[$k]['id'] = $addresses[$k]['id'];
			#	if(!$addresses[$k]['mac']){$update[$k]['mac'] = $result[$k]['macaddress'];}
			#	if(!$addresses[$k]['port']){$update[$k]['port'] = $result[$k]['portname'];}
			if(!$result[$k]['ifname']){$result[$k]['ifname'] = $result[$k]['portname'];}
			if(!$result[$k]['mac']){$result[$k]['mac'] = $result[$k]['macaddress'];}
		}
		if (!$r['hostname'] && $r['device'] && !array_key_exists($r['device'], $devices)) {
			$device = getDevicesFromNedi ('device',$r['device']);
			if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Device: ".$device[$r['device']]['device']."</div>";}
			$device_add = $device[$r['device']];
			$device_add['hostname'] = $r['device'];
			$device_add['description'] = $device[$r['device']]['description'];
			$device_add['action'] = "add";$device_add['agent'] = "ScanNeDi";
			$device_add['ip_addr'] = Transform2long($device[$r['device']]['ip_addr']);
			$device_add['sections'] = $subnet['sectionId'];
			$device_add['siteId'] = $subnet['siteId'];
			updateDeviceDetails($device_add);
			$devices = getDeviceIndexHostname('hostname');
		}
		if ($r['hostname'] && !array_key_exists($r['hostname'], $devices)) {
			$device_add = $result[$k];
			$device_add['hostname'] = $r['hostname'];
			$device_add['action'] = "add";$device_add['agent'] = "ScanGlpi";
			$device_add['ip_addr'] = $result[$k]['ipaddress'];
			$device_add['sections'] = $subnet['sectionId'];
			$device_add['siteId'] = $subnet['siteId'];
			$device_add['glpi_id'] = $result[$k]['id'];
			$device_add['glpi_type'] = $result[$k]['tipo'];
			updateDeviceDetails($device_add);
			$devices = getDeviceIndexHostname('hostname');
		}
		if (!$r['hostname'] && $r['device']){
			$r['hostname'] = $r['device'];
		}
		if (array_key_exists($r['hostname'], $devices)) {
			#print "<div class='alert alert-info'>Update section on Device: ".$r['hostname']."</div>";
			$result[$k]['switch']=$devices[$r['hostname']]['id'];
			$dev_update = updateDeviceSection($devices[$r['hostname']]['id'],$subnetId);
			if( $dev_update && $_POST['debug']==1){
				print "<div class='alert alert-info'>Update section on Device: ".$dev_update."</div>";
			}
		}
		#if($subnet['description'] != ""){
		$result[$k]['subnetname'] = ($subnet['description'] != "")?$subnet['description']:transform2long($subnet['subnet'])."/".$subnet['mask'];
		#}else{
		#	$result[$k]['subnetname'] = transform2long($subnet['subnet'])."/".$subnet['mask'];
		#}
		$result[$k]['sectionId'] = $subnet['sectionId'];
		$result[$k]['subnetId'] = $subnet['id'];
		$total[$subnetId][$k] = $result[$k];
		if ($count>100){
			unset($result[$k]);
			if ($count==101){print "<div class='alert alert-info'>"._("Not all record are imported on this run")."!</div>";}
		}
		$count++;
	}
}

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
	print "<div class='alert alert-info'>"._("No alive host found for insert")."!</div>";
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
	print "	<th>"._("DnsName")."</th>";
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
		print "	<input type='hidden' name='mac$m' value=".rtrim(chunk_split(substr($ip['ifmac'],0,12),2,":"),":").">";
		print "	<input type='hidden' name='ifname$m' value=".$ip['ifname'].">";
		print "	<input type='hidden' name='switch$m' value=".$ip['switch'].">";
		print "	<input type='hidden' name='type$m' value=".$ip['Address Type'].">";
		print "	<input type='hidden' name='lifetime$m' value=".$ip['Lifetime'].">";
		print "</td>";
		//hostname
		print "<td>";
		print "	<input type='text' class='form-control input-sm' name='dns_name$m' value='".$dns."'>";
		print "</td>";
		print "<td>".$ip['hostname']."</td>";
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
	print "<pre> result_pre";
	print_r($devices);
	print "</pre>";
	print "<pre> result";
	print_r($result);
	print "</pre>";
	print "<pre> glpi";
	print_r($glpi);
	print "</pre>";
	print "<pre>";
	print_r($nat);
	print "</pre>";
}
}else{
	$settings = getAllSettings();
	foreach($total as $k=>$result) {
		foreach($result as $ip) {
			$subject = "";

			/* set title */
			$title = "New ip from Nedi and Glpi not defined";

			/* Preset content */
			#$content .= '&bull; '._('IP address').': ' . "\t" . $ip['ip_addr'] . '/' . $subnet['mask']. "\n";
			$content .= '&bull; '._('IP address').': ' . "\t" . transform2long($ip['ifip']). "\n";

			#$content .= '&bull; '._('Subnet').': ' . "\t" . $ip['subnetname']. "\n";
			$content .= '&bull; '._('Subnet').': ' . "\t<a href=\"".$settings['siteURL']."/subnets/".$ip['sectionId']."/".$ip['subnetId']."/\">".$ip['subnetname']."</a>\n";
			
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
				$content .= "&bull; "._('Port').":\t"			 . $ip['port'] . "\n";
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
	#print $content;
	$toemail = array();
	$toadmins = getAllAdminUsers ();
	foreach($toadmins as $toadmin) {
		array_push($toemail,$toadmin['email']);
	}
	$toemail = implode(',',$toemail);
	#print '<div class="alert alert-danger">'._('Sending mail failed').'!'.$sender['email'].'</div>';
	$content .= "\nThese new ip are not yet added!";
	if ($total){
		if(!sendIPnotifEmail($toemail, $title, $content))	{ print _('Sending mail failed')."!\n"; }
		else												{ print _('Sending mail succeeded')."!\n"; }
	}
}
?>