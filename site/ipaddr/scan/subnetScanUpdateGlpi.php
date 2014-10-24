<?php

/*
 * Update alive status of all hosts in subnet
 ***************************/

/* required functions */
$dir = dirname(__FILE__);
require_once($dir.'/../../../functions/functions.php');
require_once($dir.'/../../../functions/functions-external.php'); 

if (!isset($_SESSION)) { session_start(); }
if(!$_POST){$_SESSION['ipamusername'] = "Crontab";}
$debug = $_POST['debug'];

$mysqli = @new mysqli($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']); 
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
}

$devices = getDeviceIndexHostname('hostname');
$devices_id = getDeviceIndexHostname('id');
$settings = getAllSettings();
$statuses = explode(";", $settings['pingStatus']);
	
foreach($subnetIds as $subnetId) {
	# get subnet details
	$subnet = getSubnetDetailsById ($subnetId);
	
	$cidr = transform2long($subnet['subnet'])."/".$subnet['mask'];
	# verify input CIDR
	$errors = verifyCidr ($cidr,0);
	$subdetail = calculateIpCalcResult($cidr);

	$subnet_glpi = getSubnetFromGlpi ($subdetail['Network'],$subdetail['Subnet netmask']);
	
	if ($subnet_glpi){
		if( $subnet['description'] != $subnet_glpi['name']){
			$glpi_update['name']=$subnet['description'];
			$glpi_update['completename']=$subnet['description'];
			updateSubnetOnGlpi($glpi_update,$subdetail['Network'],$subdetail['Subnet netmask']);
			#update
		}
	}else{
		$glpi_update['name']=$subnet['description'];
		$glpi_update['completename']=$subnet['description'];
		$glpi_update['level'] = 1;
		$glpi_update['addressable'] = 1;
		if($subdetail['Type']=="IPv4"){$glpi_update['version']="4";}
		$glpi_update['address']=$subdetail['Network'];
		if($subdetail['Type']=="IPv4"){
			$glpi_update['address_2']="65535";
			$glpi_update['address_3']=$subnet['subnet'];
		}
		$glpi_update['netmask']=$subdetail['Subnet netmask'];
		if($subdetail['Type']=="IPv4"){
			$glpi_update['netmask_0']="4294967295";
			$glpi_update['netmask_1']="4294967295";
			$glpi_update['netmask_2']="4294967295";
			$glpi_update['netmask_3']=(4294967295 - $subdetail['Number of hosts'] - 1);
		}
		$glpi_update['gateway']=$subnet['Gateway'];
		if($subdetail['Type']=="IPv4"){
			$glpi_update['gateway_2']="65535";
			$glpi_update['gateway_3']=Transform2decimal($subdetail['Subnet netmask']);
		}
		$glpi_update['comment'] = "Added by Ipam";
		insertSubnetOnGlpi($glpi_update);
	}
	
	#print "Array: ".$r['id']."\n";
	# get all existing IP addresses
	$addresses_temp = getIpAddressesBySubnetId ($subnetId);

	foreach($addresses_temp as $r) {
		$addresses[$r['ip_addr']]=$r;	
	}
	$calc = calculateSubnetDetailsNew ( transform2long($subnet['subnet']), $subnet['mask'], 0, 0, 0, 0 );
	$min = $subnet['subnet'];
	$max = $min + $calc['maxhosts'];
	if(!$_POST['debug']==1) {print "Update ".transform2long($subnet['subnet'])."/".$subnet['mask']."\n";}
	$result = getDevicesAddressFromGlpi($min,$max,'ifip');
	#$nodes = getNodesFromNedi ($min,$max,'ifip');
	
	// add nodes on nedi list
	#foreach($nodes as $k=>$n) {
	#	if (!array_key_exists($k, $result)) {$result[$k]=$n;}
	#}
	
	#if($addresses){
		foreach($addresses as $k=>$a) {
			$device = array();
			if($addresses[$k]['excludePing']=="0" ) {
				if (is_array($result) and array_key_exists($k, $result)) {
					#$lastseen[$a['id']] = $last;
					#$last = date("Y-m-d H:i:s",$result[$k]['lastSeen']);
					$lastseen[$k]['lastSeen'] = $result[$k]['last_ocs_conn'];
					#$lastseen[$k]['id'] = $addresses[$k]['id'];
					#$lastseen[$k]['last'] = $last;
					#$lastseen[$k]['last1'] = $addresses[$k]['lastSeen'];
					if (($lastseen[$k]['lastSeen'] != $addresses[$k]['lastSeen'])or $_POST['debug']==1){
						#$update[$k]['update'] = $last;
						#$update[$k]['id'] = $addresses[$k]['id']; ## update non più usato
						
						$ip = $a; 
						$ip['action'] = "edit" ;
						$ip['ip_addr'] = Transform2long( $a['ip_addr']) ;
						if($addresses[$k]['state'] == 2){$update[$k]['state'] = 1;$ip['state'] = 1;}
						
						if($lastseen[$k]['lastSeen'] != $addresses[$k]['lastSeen']){
						$update[$k]['lastSeen'] = $lastseen[$k]['lastSeen'];
						$ip['lastSeen'] = $lastseen[$k]['lastSeen'];
						#print "<div class='alert alert-info'>".$lastseen[$k]['lastSeen'] ." != ". $addresses[$k]['lastSeen']."</div>";
						}
						if(!$addresses[$k]['mac']){$update[$k]['mac'] = $result[$k]['macaddress'];$ip['mac'] = $result[$k]['macaddress'];}
						if(!$addresses[$k]['port']){$update[$k]['port'] = $result[$k]['portname'];$ip['port'] = $result[$k]['portname'];}
						if($addresses[$k]['description'] != $result[$k]['commments'] && !preg_match("/Swap:/", $result[$k]['commments'])){
							$update[$k]['description'] = $result[$k]['commments'];$ip['description'] = $result[$k]['commments'];
							print ("<div class='alert alert-info'>".$addresses[$k]['description'] ."!=". $result[$k]['commments']."</div>");
						}
						#if(!$addresses[$k]['description']){$update[$k]['description'] = $result[$k]['manufacturername'];$ip['description'] = $result[$k]['manufacturername'];}commments
						if($addresses[$k]['switch'] == 0 or ( $devices_id and !array_key_exists($addresses[$k]['switch'],$devices_id)) or ! $devices_id){
							if ($result[$k]['hostname'] && (( $devices and !array_key_exists($result[$k]['hostname'], $devices)) or ! $devices)) {
								print "<div class='alert alert-info'>Switch not exist:".$result[$k]['hostname']."</div>";
								#$device = getDevicesFromNedi ('device',$result[$k]['hostname']);
								#$device[$result[$k]['hostname']]['device'] = $result[$k]['hostname'];
								#$device[$result[$k]['hostname']]['ip_addr'] = $result[$k]['ip_addr'];;
								#$device[$result[$k]['hostname']]['type'] = $result[$k]['type'];
								#$device[$result[$k]['hostname']]['model'] = $result[$k]['model'];
								#$device[$result[$k]['hostname']]['description'] = $result[$k]['description'];
								#insertNediDevice($device,$subnet['sectionId']);
								$device_add = $result[$k];
								$device_add['hostname'] = $result[$k]['hostname'];
								#$device_add['description'] = $r['description'];
								$device_add['action'] = "add";$device_add['agent'] = "glpi";
								$device_add['ip_addr'] = $result[$k]['ip_src'];
								$device_add['sections'] = $subnet['sectionId'];
								$device_add['siteId'] = $subnet['siteId'];
								$device_add['type'] = "10";
								$device_add['vendor'] = $result[$k]['manufacturername'];
								updateDeviceDetails($device_add);
								$devices = getDeviceIndexHostname('hostname');	
							}
							if ($devices and array_key_exists($result[$k]['hostname'], $devices)) {
								#print "<div class='alert alert-info'>Switch exist:".$result[$k]['hostname']."</div>";
								#$update[$k]['switch']=$devices[$result[$k]['hostname']]['id'];
								$ip['switch']=$devices[$result[$k]['hostname']]['id'];
								$dev_update = updateDeviceSection($devices[$r['device']]['id'],$subnetId);
							}
						}else{
							if ($result[$k]['hostname']) {
								if($devices[$result[$k]['hostname']]['type']){$type = getTypeDetailsById($devices[$result[$k]['hostname']]['type']);}
								#print ("<div class='alert alert-info'>Query:".$type.":".$result[$k]['computertype'].":".$devices[$result[$k]['hostname']]['type']."</div>\n");
								if ($devices[$result[$k]['hostname']]['ip_addr'] != $result[$k]['ip_addr']) {$device[$result[$k]['hostname']]['ip_addr'] = $result[$k]['ip_addr'];}
								if ($type != $result[$k]['type']) {$device[$result[$k]['hostname']]['type'] = $result[$k]['type'];}
								if ($devices[$result[$k]['hostname']]['model'] != $result[$k]['model']) {$device[$result[$k]['hostname']]['model'] = $result[$k]['model'];}
								#if ($devices[$result[$k]['hostname']]['description'] != $result[$k]['commments']) {$device[$result[$k]['hostname']]['description'] = $result[$k]['commments'];}
								if (count($device) > 0) {updateNediDevice($device,$subnet['sectionId']);}
							}
						}
						if ($update){
							modifyIpAddress($ip);
							#print "<div class='alert alert-info'>".$lastseen[$k]['lastSeen'] ." !!!= ". $addresses[$k]['lastSeen']."</div>";
						}
					}
					#$lastseen[$k]['status'] = "Online";$lastseen[$k]['code']=0;
					$tDiff = time() - strtotime($lastseen[$k]['lastSeen']);
					#$lastseen[$k]['diff'] = $tDiff;
					if($tDiff < $statuses[0]){$lastseen[$k]['status'] = "Online";$lastseen[$k]['code']=0;}
					elseif($tDiff < $statuses[1]){$lastseen[$k]['status'] = "Offline";$lastseen[$k]['code']=1;}
					elseif($tDiff < 2592000){$lastseen[$k]['status'] = "Error";$lastseen[$k]['code']=2;}
					elseif($addresses[$k]['lastSeen'] == "0000-00-00 00:00:00") {$lastseen[$k]['status'] = "Not checked";$lastseen[$k]['code'] = 100;}
					else{$lastseen[$k]['status'] = "Not checked";$lastseen[$k]['code'] = 100;}
					#$lastseen[$k]['id'] = $addresses[$k]['id'];
				}else{
					$lastseen[$k]['status'] = "Error";
					$lastseen[$k]['code']=2; 
				}
				
			}
			#else{$lastseen[$k]['status'] = "Excluded form check";$lastseen[$k]['code'] = 100;}
		}
	#}
}	
if($update){
	#foreach($update as $ip) {
	#	modifyIpAddress($ip);
	#}
	#updateLastSeenValue($update);
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
	print "<pre>addresses";
	print_r($addresses);
	print "</pre>";
	print "<pre>subnet_glpi";
	print_r($ip);
	print "</pre>";
	print "<pre>update";
	print_r($update);
	print "</pre>";
	print "<pre>glpi_update";
	print_r($glpi_update);
	print "</pre>";
	print "<pre>";
	print_r($result);
	print "</pre>";
}
}
?>