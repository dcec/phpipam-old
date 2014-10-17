<?php

/*
 * Update alive status of all hosts in subnet
 ***************************/

/* required functions */
$dir = dirname(__FILE__);
require_once($dir.'/../../../functions/functions.php');
require_once($dir.'/../../../functions/functions-external.php'); 
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
	if($subnetPerm < 2) 	{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

	/* verify post */
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
	if($argv[2]){$_POST['debug'] = 1;}
}

$devices = getDeviceIndexHostname('hostname');
$devices_id = getDeviceIndexHostname('id');
$settings = getAllSettings();
$statuses = explode(";", $settings['pingStatus']);
					
foreach($subnetIds as $subnetId) {
	# get subnet details
	$subnet = getSubnetDetailsById ($subnetId);

	# get all existing IP addresses
	$addresses_temp = getIpAddressesBySubnetId ($subnetId);

	foreach($addresses_temp as $r) {
		$addresses[$r['ip_addr']]=$r;	
	}

	$calc = calculateSubnetDetailsNew ( transform2long($subnet['subnet']), $subnet['mask'], 0, 0, 0, 0 );
	$min = $subnet['subnet'];
	$max = $min + $calc['maxhosts'];
	if(!$_POST['debug']==1) {print "Update ".transform2long($subnet['subnet'])."/".$subnet['mask']."\n";}
	$result = getDevicesAddressFromNedi($min,$max,'ifip',30,'devip');
	$result_pre = $result;
	$nodes = getNodesFromNedi ($min,$max,'ifip');
	#$devices = getDeviceIndexHostname('hostname');
	$balanced = getBalancedFromNedi ($min,$max,'ifip');
	$nat = getNatFromNedi ($min,$max);
	
	// add nodes on nedi list
	foreach($nodes as $k=>$n) {
		if (!array_key_exists($k, $result)) {
			$result[$k]=$n;
		}
	}
	
	foreach($nat as $k=>$n) {
		if (!array_key_exists($k, $result)){
			$result[$k]=$n;
		}else{
			foreach($nat[$k] as $n=>$a) {
				if (!array_key_exists($n, $result[$k])){$result[$k][$n] = $nat[$k][$n];}
			}
		}	
		if ($n['iptype'] == "mip" && $k >= $min && $k <= $max) {
			$result[$k]=$n;
			$result[$k]['Address Type'] = "nat";
			#print "<div class='alert alert-info'>".$k."</div>";
			if($n['type'] == "MIP"){
				$ipdet = getIpAddrDetailsByip($n['nip']);
				if(array_key_exists('subnetId',$ipdet)){
					$subdet = getSubnetDetailsById ($ipdet['subnetId']);
					$devicedet = getDeviceById ($ipdet['switch']);
					$result[$k]['description'] = "MIP TO: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$n['n_ip']."</a> ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
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
	
	foreach($result as $k=>$n) {
		if(array_key_exists($k, $balanced)){
			foreach($balanced[$k] as $n=>$a) {
				if (!array_key_exists($n, $result[$k])){$result[$k][$n] = $balanced[$k][$n];}
			}
			$result[$k]['Address Type'] = "balanced";
		}
		if(array_key_exists($k, $nat) && $nat[$k]['iptype'] == "nip"){
		#if (array_key_exists($k, $nat) && $nat[$k]['iptype'] == "nip") {
			foreach($nat[$k] as $n=>$a) {
				if (!array_key_exists($n, $result[$k])){$result[$k][$n] = $nat[$k][$n];}
			}	
		}
	}
	#foreach($balanced as $k=>$n) {
	#	if (!array_key_exists($k, $result)) {$result[$k]=$n;}
	#}
	if($addresses){
		foreach($addresses as $k=>$a) {
			if($addresses[$k]['excludePing']=="0" ) {
				if (array_key_exists($k, $result)) {
					#$lastseen[$a['id']] = $last;
					$last = date("Y-m-d H:i:s",$result[$k]['lastSeen']);
					$lastseen[$k]['lastSeen'] = $last;
					#$lastseen[$k]['id'] = $addresses[$k]['id'];
					$lastseen[$k]['last'] = $last;
					$lastseen[$k]['last1'] = $addresses[$k]['lastSeen'];
					if ($last != $addresses[$k]['lastSeen'] or $_POST['debug']==1){
						#print "<div class='alert alert-info'>".$subnet['subnet']."</div>";
						$update[$k]['update'] = $last;
						$update[$k]['id'] = $addresses[$k]['id'];
						if($addresses[$k]['state'] == 2){$update[$k]['state'] = 1;}
						if(!$addresses[$k]['mac'] && $result[$k]['ifmac']){$update[$k]['mac'] = rtrim(chunk_split(substr($result[$k]['ifmac'],0,12),2,":"),":");}
						if(!$addresses[$k]['port'] && $result[$k]['ifname']){$update[$k]['port'] = $result[$k]['ifname'];}
						$descr = "";
						if($result[$k]['farm']){
							foreach($result[$k]['farm'] as $i=>$f) {
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
						if($result[$k]['bfarm']){
							foreach($result[$k]['bfarm'] as $i=>$f) {
								if ($descr) { $descr .= "<br>";}
								$descr .= "BALANCED ON: " . $i." ".$f['clpo'];
								#foreach($f['farm'] as $b) {
									if ($descr) { $descr .= "<br>";}
									$ipdet = getIpAddrDetailsByip($f['clip']);
									if(array_key_exists('subnetId',$ipdet)){
										$subdet = getSubnetDetailsById ($ipdet['subnetId']);
										$devicedet = getDeviceById ($ipdet['switch']);
										$descr .= "&nbsp;&nbsp;WITH <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".Transform2long($f['clip'])."</a> ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
									}else{
										$descr .= "&nbsp;&nbsp;WITH ".Transform2long($f['clip'])."";
									}
								#}
								#print "<div class='alert alert-info'>BALANCED ON:".transform2long($k).":".$descr."</div>";
							}
						}
						
						if($result[$k]['iptype'] && $result[$k]['iptype'] == "nip"){
							if($result[$k]['type'] == "MIP"){
								$ipdet = getIpAddrDetailsByip($result[$k]['mip']);
								if ($descr) { $descr .= "<br>";}
								if(array_key_exists('subnetId',$ipdet)){
									$subdet = getSubnetDetailsById ($ipdet['subnetId']);
									$devicedet = getDeviceById ($ipdet['switch']);
									#$result[$k]['ipdet'] = $ipdet;
									#$result[$k]['subdet'] = $subdet;
									#$result[$k]['devicedet'] = $devicedet;
									$descr .= "MIPPED WITH: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$result[$k]['m_ip']."</a> ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";
									#if($addresses[$k]['description'] != $descr){$update[$k]['description'] = $descr;}
								}else{
									$descr .= "MIPPED WITH: ".$result[$k]['m_ip']."";
								}
							}						
							if($result[$k]['type'] == "VIP"){
								foreach($result[$k]['vip'] as $i=>$v) {
									$ipdet = getIpAddrDetailsByip($v['mip']);
									if ($descr) { $descr .= "<br>";}
									if(array_key_exists('subnetId',$ipdet)){
										$subdet = getSubnetDetailsById ($ipdet['subnetId']);
										$devicedet = getDeviceById ($ipdet['switch']);
										$descr .= "VIPPED WITH: <a href=\"subnets/".$subdet['sectionId']."/".$subdet['id']."/ipdetails/".$ipdet['id']."/\">".$v['m_ip']."</a>:".$v['mport']." ON <a href=\"tools/devices/hosts/".$ipdet['switch']."/\">".$devicedet['hostname']."</a>";					
									}else{
										$descr .= "VIPPED WITH: ".$v['m_ip']."";
									}
								}
							}
							
						}
						if($result[$k]['iptype'] && $result[$k]['iptype'] == "mip"){
							if($addresses[$k]['description'] != $result[$k]['description']){$descr = $result[$k]['description'];}
							#print "<div class='alert alert-info'>mip".$descr."</div>";
						}
						#print "<div class='alert alert-info'>".$descr."</div>";
						if($addresses[$k]['description'] != $descr && ($result[$k]['farm'] || $result[$k]['bfarm'] || $result[$k]['iptype'])){
							print "<div class='alert alert-info'>".$addresses[$k]['description']." <> ".$descr."</div>";
							$update[$k]['description'] = $descr;}
						#if($addresses[$k]['description'] != && $result[$k]['farm']){$update[$k]['description'] = "BALANCED: " . $result[$k]['farm']." ".$result[$k]['clpo'];}
						if($addresses[$k]['switch'] == 0 or ( $devices_id and !array_key_exists($addresses[$k]['switch'],$devices_id)) or ( $devices_id and $devices_id[$addresses[$k]['switch']]['hostname'] == "") or !$devices_id){
							#print "<div class='alert alert-info'>$k ".$devices_id[$addresses[$k]['switch']]['ip_addr']."</div>";
							if ($result[$k]['device'] && (( $devices and !array_key_exists($result[$k]['device'], $devices)) || ( $devices_id and empty($devices_id[$addresses[$k]['switch']]['hostname'])))) {
								print "<div class='alert alert-info'>".$devices_id[$addresses[$k]['switch']]['id']."</div>";
								$device = getDevicesFromNedi ('device',$result[$k]['device']);
								#insertNediDevice($device);
								$device_add = $device[$result[$k]['device']];
								$device_add['hostname'] = $result[$k]['device'];
								$device_add['description'] = $result[$k]['description'];
								$device_add['action'] = "add";$device_add['agent'] = "NeDi";
								$device_add['ip_addr'] = Transform2long($device[$result[$k]['device']][ip_addr]);
								$device_add['sections'] = $subnet['sectionId'];
								$device_add['siteId'] = $subnet['siteId'];
								#$device_add['sections'] = $sections;
								if($device_add['hostname'] != ""){
									updateDeviceDetails($device_add);
									$devices = getDeviceIndexHostname('hostname');
								}	
							}
							if ($devices_id and array_key_exists($result[$k]['device'], $devices)) {
								$update[$k]['switch']=$devices[$result[$k]['device']]['id'];
								#$dev_update = updateDeviceSection($devices[$r['device']]['id'],$subnetId);
							}
						}
					}
					$tDiff = time() - strtotime($lastseen[$k]['lastSeen']);
					#$lastseen[$k]['diff'] = $tDiff;
					if($tDiff < $statuses[0]){$lastseen[$k]['status'] = "Online";$lastseen[$k]['code']=0;}
					elseif($tDiff < $statuses[1]){
						$code = pingHost (transform2long($addresses[$k]['ip_addr']), 1, 1, false);
						//Online
						if($code == "0") {
							$update[$k]['update'] = date("Y-m-d H:i:s");
							$update[$k]['id'] = $addresses[$k]['id'];
							$lastseen[$k]['status'] = "Online";$lastseen[$k]['code']=0;
						} else {
							$lastseen[$k]['status'] = "Offline";$lastseen[$k]['code']=1;
						}
					
					#$lastseen[$k]['status'] = "Offline";$lastseen[$k]['code']=1;
					}
					elseif($tDiff < 2592000){
						$code = pingHost (transform2long($addresses[$k]['ip_addr']), 1, 1, false);
						//Online
						if($code == "0") {
							$update[$k]['update'] = date("Y-m-d H:i:s");
							$update[$k]['id'] = $addresses[$k]['id'];
							$lastseen[$k]['status'] = "Online";$lastseen[$k]['code']=0;
						} else {
							$lastseen[$k]['status'] = "Error";$lastseen[$k]['code']=2;
						}
					#$lastseen[$k]['status'] = "Error";$lastseen[$k]['code']=2;
					}
					elseif($addresses[$k]['lastSeen'] == "0000-00-00 00:00:00") {$lastseen[$k]['status'] = "Not checked";$lastseen[$k]['code'] = 100;}
					else{$lastseen[$k]['status'] = "Not checked";$lastseen[$k]['code'] = 100;}
				}else{
					$lastseen[$k]['status'] = "Error";
					$lastseen[$k]['code']=2; 
				}
				$lastseen[$k]['id'] = $addresses[$k]['id'];
			}
			#else{$lastseen[$k]['status'] = "Excluded form check";$lastseen[$k]['code'] = 100;}
		}
	}
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
//empty
elseif(!isset($lastseen)) {
	print "<div class='alert alert-info'>"._('Subnet is empty')."</div>";
}
else {
	# order by IP address
	ksort($lastseen);

	//table
	print "<table class='table table-condensed table-top'>";
	
	//headers
	print "<tr>";
	print "	<th>"._('IP')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('status')."</th>";
	print "	<th>"._('hostname')."</th>";
	print "</tr>";
	
	//loop
	foreach($lastseen as $k=>$r) {
		//set class
		if($r['code']==0)		{ $class='success'; }
		elseif($r['code']==100)	{ $class='warning'; }		
		else					{ $class='danger'; }
	
		print "<tr class='$class'>";
		print "	<td>".transform2long($k)."</td>";
		print "	<td>".$addresses[$k]['description']."</td>";
		print "	<td>"._("$r[status]")."</td>";
		print "	<td>".$addresses[$k]['dns_name']."</td>";

		print "</tr>";
	}
	
	print "</table>";
}
if($_POST['debug']==1) {
	print "<hr>";
	print "<pre> update";
	print_r($result_pre);
	print "</pre>";
	print "<pre>result";
	print_r($result);
	print "</pre>";
	print "<pre>nat";
	print_r($nat);
	print "</pre>";
	print "<pre addresses>";
	print_r($addresses);
	print "</pre>";
	print "<pre>";
	print_r($balanced);
	print "</pre>";
}
}
?>