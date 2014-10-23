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
	#print "<div class='alert alert-info'>Update ".transform2long($subnet['subnet'])."/".$subnet['mask']." ".$calc['maxhosts']."</div>";
	if(!$_POST['debug']==1) {print "Update ".transform2long($subnet['subnet'])."/".$subnet['mask']."\n";}
	#$result = getDevicesAddressFromNedi($min,$max,'ifip');
	#$nodes = getNodesFromNedi ($min,$max,'ifip');
	#$devices = getDeviceIndexHostname('hostname');
	
	// add nodes on nedi list
	#foreach($nodes as $k=>$n) {
	#	if (!array_key_exists($k, $result)) {$result[$k]=$n;}
	#}
	
	if($addresses){
		foreach($addresses as $k=>$a) {
			if($addresses[$k]['excludePing']=="0" and $addresses[$k]['state']=="1") {
				$last = strtotime($addresses[$k]['lastSeen']);
				if(!$last){$last = 0;}
				$diff = (time() - $last )/ 86400;
				$edit = strtotime($addresses[$k]['editDate']);
				$ediff = (time() - $edit )/ 86400;
				#$addresses[$k]['editDiff'] = $ediff;
				if ($addresses[$k]['Lifetime'] == "30 Days"){$lifetime = 30;}
				if ($addresses[$k]['Lifetime'] == "1 Year"){$lifetime = 365;}
				#$addresses[$k]['Diff'] = $lifetime;
				if((($last > 0 and $diff > $lifetime) or ($last == "0" and $ediff > 60)) and $addresses[$k]['Lifetime'] !=  "Infinite"){
					$ip = getIpAddrDetailsById ($addresses[$k]['id']);
					$ip['action'] = "delete";
					if(!$_POST){$user = "Crontab";}else{$user = NULL;}
					if($ip['ip_addr'] > 0){
						if(!$_POST){
						/* execute insert / update / delete query */    
							if (!modifyIpAddress($ip)) {
								print date('Y-m-d H:i:s') . ': '._('Error deleting IP address $ip[ip_addr]')."!\n";
								updateLogTable ('Error '. $ip['action'] .' IP address '. $ip['ip_addr'], 'Error '. $ip['action'] .' IP address '. $ip['ip_addr'] .'<br>SubnetId: '. $ip['subnetId'], 2);
							}
							else {
								print date('Y-m-d H:i:s') . ': '._("IP $ip[ip_addr] $ip[action] successful")."!\n";
								updateLogTable ($ip['action'] .' of IP address '. $ip['ip_addr'] .' succesfull!', $ip['action'] .' of IP address '. $ip['ip_addr'] .' succesfull!<br>SubnetId: '. $ip['subnetId'], 0);
							}
						}
						$delete[$k] = $ip;
					}
				}
			}
		}
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
//empty
elseif(!isset($delete)) {
	print "<div class='alert alert-info'>"._('Subnet is empty')."</div>";
}
else {
	# order by IP address
	ksort($delete);

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
	foreach($delete as $k=>$r) {
		//set class
		#if($r['code']==0)		{ $class='success'; }
		#elseif($r['code']==100)	{ $class='warning'; }		
		#else					{ $class='danger'; }
	
		print "<tr class='danger'>";
		print "	<td>".$delete[$k]['ip_addr']."</td>";
		print "	<td>".$delete[$k]['description']."</td>";
		print "	<td>"._("Delete")."</td>";
		print "	<td>".$delete[$k]['dns_name']."</td>";

		print "</tr>";
	}
	
	print "</table>";
}
if($_POST['debug']==1) {
	print "<hr>";
	print "<pre>";
	print_r($addresses);
	print "</pre>";
	print "<pre>";
	print_r($delete);
	print "</pre>";
}
}
?>