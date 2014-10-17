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
}

$devices = getDeviceIndexHostname('hostname');
	
#foreach($subnetIds as $subnetId) {
	# get subnet details
	#$subnet = getSubnetDetailsById ($subnetId);

	# get all existing IP addresses
	#$addresses_temp = getIpAddressesBySubnetId ($subnetId);

	#foreach($addresses_temp as $r) {
	#	$addresses[$r['ip_addr']]=$r;	
	#}

	#$calc = calculateSubnetDetailsNew ( transform2long($subnet['subnet']), $subnet['mask'], 0, 0, 0, 0 );
	#$min = $subnet['subnet'];
	#$max = $min + $calc['maxhosts'];

	$nedi = getDevicesFromNedi('device',NULL,false);
	#$nodes = getNodesFromNedi ($min,$max,'ifip');
	$glpi = getDevicesAddressFromGlpi('','','hostname');
	
	// add nodes on nedi list
	#foreach($nodes as $k=>$n) {
	#	if (!array_key_exists($k, $result)) {$result[$k]=$n;}
	#}
	
	if($devices){
		foreach($devices as $k=>$a) {
			$update = 0;
			if (is_array($nedi) and array_key_exists($k, $nedi) and !array_key_exists($k, $glpi)) {
				$device_up = $devices[$k];
				$devices[$k]['code'] = "0";
				$devices[$k]['agent'] = "NeDi";
				#$update = 0;
				if((!$devices[$k]['ip_addr'] and $nedi[$k]['ip_addr']) or ($devices[$k]['ip_addr'] != Transform2long($nedi[$k]['ip_addr']))){$device_up['ip_addr'] = Transform2long($nedi[$k]['ip_addr']);$update = 1;}
				if((!$devices[$k]['model'] and $nedi[$k]['model']) or ($devices[$k]['model'] != $nedi[$k]['model'])){$device_up['model'] = $nedi[$k]['model'];$update = 1;}
				if((!$devices[$k]['vendor'] and $nedi[$k]['vendor']) or ($devices[$k]['vendor'] != $nedi[$k]['vendor'])){$device_up['vendor'] = $nedi[$k]['vendor'];$update = 1;}
				if((!$devices[$k]['description'] and $nedi[$k]['description']) or ($devices[$k]['description'] != $nedi[$k]['description'])){$device_up['description'] = $nedi[$k]['description'];$update = 1;}
				if((!$devices[$k]['version'] and $nedi[$k]['version']) or ($devices[$k]['version'] != $nedi[$k]['version'])){$device_up['version'] = $nedi[$k]['version'];$update = 1;}
				if ($update == 1){
					$devices[$k]['code'] = "100";
					$device_up['action'] = "edit";
					$device_up['agent'] = "NeDi";
					$device_up['switchId'] = $a['id'];
					$up[$k] = $device_up;
					#updateDeviceDetails($device_up);
				}
				#$a['description'] $nedi[$k]['description'] = ;
				#print "<div class='alert alert-info'>Switch exist:".$k."</div>";
			} 
			if (is_array($glpi) and array_key_exists($k, $glpi)) {
				$device_up = $a;
				#$device_up['action'] = "edit";
				#$device_up['agent'] = "glpi";
				$devices[$k]['code'] = "0";
				$devices[$k]['agent'] = "glpi";
				#$update = 0;
				if((!$devices[$k]['ip_addr'] and $glpi[$k]['ip_src']) or ($devices[$k]['ip_addr'] != $glpi[$k]['ip_src'])){$device_up['ip_addr'] = $glpi[$k]['ip_src'];$update = 1;}
				if((!$devices[$k]['model'] and $glpi[$k]['computermodel']) or ($devices[$k]['model'] != $glpi[$k]['computermodel'])){$device_up['model'] = $glpi[$k]['computermodel'];$update = 1;}
				if((!$devices[$k]['vendor'] and $glpi[$k]['manufacturername']) or ($devices[$k]['vendor'] != $glpi[$k]['manufacturername'])){$device_up['vendor'] = $glpi[$k]['manufacturername'];$update = 1;}
				#if((!$devices[$k]['description'] and $glpi[$k]['description']) or ($devices[$k]['description'] != $glpi[$k]['description'])){$device_up['description'] = $glpi[$k]['description'];$update = 1;}
				if((!$devices[$k]['version'] and $glpi[$k]['version']) or ($devices[$k]['version'] != $glpi[$k]['version'])){$device_up['version'] = $glpi[$k]['version'];$update = 1;}
				if ($update == 1){
					$device_up['action'] = "edit";
					$device_up['agent'] = "glpi";
					$devices[$k]['code'] = "100";
					$device_up['switchId'] = $a['id'];
					$up_g[$k] = $device_up;
				}
			}
			if($device_up && $update == 1){
				#print "<pre>";
				#print_r($device_up);
				#print "</pre>";
				updateDeviceDetails($device_up);
			}
			if (is_array($nedi) and !array_key_exists($k, $nedi) and !array_key_exists($k, $glpi)) {
				$device_up = $a;
				$device_up['action'] = "delete";
				$devices[$k]['code'] = 10;
				$device_up['switchId'] = $a['id'];
				updateDeviceDetails($device_up);
				#print "<pre>";
				#print_r($device_up);
				#print "</pre>";
			}
			#}else{
			#	$devices[$k]['code'] = 0;
				
			#}
		}
	}
#}
	
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
elseif(!isset($devices)) {
	print "<div class='alert alert-info'>"._('Subnet is empty')."</div>";
}
else {
	# order by IP address
	ksort($devices);

	//table
	print "<table class='table table-condensed table-top'>";
	
	//headers
	print "<tr>";
	print "	<th>"._('hostname')."</th>";
	print "	<th>"._('IP')."</th>";
	print "	<th>"._('Description')."</th>";
	print "	<th>"._('Agent')."</th>";
	print "</tr>";
	
	//loop
	foreach($devices as $k=>$r) {
		//set class
		if($r['code']==0)		{ $class='success'; }
		elseif($r['code']==100)	{ $class='warning'; }		
		else					{ $class='danger'; }
	
		print "<tr class='".$class."'>";
		print "	<td>".$devices[$k]['hostname']."</td>";
		print "	<td>".$devices[$k]['ip_addr']."</td>";
		print "	<td>".$devices[$k]['description']."</td>";
		print "	<td>".$devices[$k]['agent']."</td>";

		print "</tr>";
	}
	
	print "</table>";
}
if($_POST['debug']==1) {
	print "<hr>";
	print "<pre>";
	print_r($devices);
	print "</pre>";
	print "<pre>";
	print_r($glpi);
	print "</pre>";
	print "<pre>up";
	print_r($nedi);
	print "</pre>";
}
}
?>