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
if($subnetPerm < 2) 		{ die('<div class="alert alert-danger">'._('You do not have permissions to modify hosts in this subnet').'!</div>'); }

# verify post */
CheckReferrer();

# get subnet details
$subnet = getSubnetDetailsById ($_POST['subnetId']);
$addresses_temp = getIpAddressesBySubnetId ($_POST['subnetId']);
foreach($addresses_temp as $r) {
	$addresses[$r['ip_addr']]=$r;	
}

$mysqli = @new mysqli($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']); 
/* check connection */
if ($mysqli->connect_errno) {
	/* die with error */
    die('<div class="alert alert-danger"><strong>'._('Database connection failed').'!</strong><br><hr>Error: '. mysqli_connect_error() .'</div>');
}

	$calc = calculateSubnetDetailsNew ( transform2long($subnet['subnet']), $subnet['mask'], 0, 0, 0, 0 );
	$min = $subnet['subnet'];
	$max = $min + $calc['maxhosts'];

	$result = getDevicesAddressFromNedi($min,$max,'ifip',30,'devip');
	$nodes = getNodesFromNedi ($min,$max,'ifip');	
	$devices = getDeviceIndexHostname('hostname');
	
	// add nodes on nedi list
	foreach($nodes as $k=>$n) {
		if (!array_key_exists($k, $result)) {$result[$k]=$n;}
	}
	// remove already existing
	if($addresses){
	foreach($addresses as $k=>$a) {
		if (array_key_exists($k, $result)) {
			unset($result[$k]);
		}
	}
	}

	// Add switch index and limit row
	$count=1;
	foreach($result as $k=>$r) {
		if ($r['device'] && !array_key_exists($r['device'], $devices)) {
			$device = getDevicesFromNedi ('device',$r['device']);
			if($_POST['debug']==1) {print "<div class='alert alert-info'>Inserted Device: ".$device[$r['device']]['device']."</div>";}
			insertNediDevice($device);
			$devices = getDeviceIndexHostname('hostname');
		}
		if (array_key_exists($r['device'], $devices)) {
			$result[$k]['switch']=$devices[$r['device']]['id'];
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
		print "	<input type='hidden' name='mac$m' value=".rtrim(chunk_split(substr($ip['ifmac'],0,12),2,":"),":").">";
		print "	<input type='hidden' name='ifname$m' value=".$ip['ifname'].">";
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
	print_r($result);
	print "</pre>";
}
?>