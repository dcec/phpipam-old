<?php

/**
 * External functions
 *
 */


/**
 * Get device from nedi index
 */

function getDevicesAddressFromNedi ($min = NULL,$max = NULL,$index,$days = 30,$index1 = NULL)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

	$days = (time() - ($days * 86400));

	if ($min and $max){
		$query    = 'select devices.device,devip,ifip,ifmac,interfaces.ifname,description,INET_NTOA(ifip) as ip,lastdis as lastSeen from devices left join interfaces on devices.device=interfaces.device left join networks on devices.device=networks.device and interfaces.ifname=networks.ifname where ((ifip >= "'. $min .'" and ifip <= "'. $max .'")or(devip >= "'. $min .'" and devip <= "'. $max .'" and ifip is null)) and lastdis >'.$days.' order by ifip,devip;';
	}else{
		$query    = 'select devices.device,devip,ifip,ifmac,interfaces.ifname,description,INET_NTOA(ifip) as ip,lastdis as lastSeen from devices left join interfaces on devices.device=interfaces.device left join networks on devices.device=networks.device and interfaces.ifname=networks.ifname where ifip > "0" and lastdis >'.$days.' order by ifip;';
	}
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		if (!$r[$index] && $index1){
			$devices[$r[$index1]]=$r;
		}else{
			$devices[$r[$index]]=$r;
		}
	}
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}


function getDevicesAddressFromGlpi ($min = NULL,$max = NULL,$index,$days = 30)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);  
	
	$days = (time() - ($days * 86400));
	
	if($min and $max){
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ip_src,last_ocs_conn,t.name as type_name from V_COMPUTER_NETWORKPORTS as n left join glpi_plugin_ocsinventoryng_ocslinks as l on l.computers_id = n.id';
		$query    .= ' left join glpi_computertypes as t on n.typeid = t.id left join V_COMPUTERS as c on n.id = c.id';
		$query    .= ' where INET_ATON(ipaddress) >= "'. $min .'" and INET_ATON(ipaddress) <= "'. $max .'" AND status = "online" AND (UNIX_TIMESTAMP( last_ocs_conn ) > '.$days.' OR sorgente = "GLPI");';
	}else{
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ip_src,last_ocs_conn,t.name as type_name from V_COMPUTER_NETWORKPORTS as n left join glpi_plugin_ocsinventoryng_ocslinks as l on l.computers_id = n.id';
		$query    .= ' left join glpi_computertypes as t on n.typeid = t.id left join V_COMPUTERS as c on n.id = c.id';
		$query    .= ' where INET_ATON(ipaddress) > "0" AND status = "online" AND (UNIX_TIMESTAMP( last_ocs_conn ) > '.$days.' OR sorgente = "GLPI") group by hostname;';
	}
	/* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
	if($min and $max){
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ipaddress as ip_src,t.name as type_name from V_NETWORK_NETWORKPORTS as n';
		$query    .= ' left join glpi_networkequipmenttypes as t on n.typeid = t.id left join V_NETWORK_DEVICES as c on n.id = c.id';
		$query    .= ' where sorgente = "GLPI" AND status = "online" and INET_ATON(ipaddress) >= "'. $min .'" and INET_ATON(ipaddress) <= "'. $max .'";';
	}else{
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ipaddress as ip_src,t.name as type_name from V_NETWORK_NETWORKPORTS as n';
		$query    .= ' left join glpi_networkequipmenttypes as t on n.typeid = t.id left join V_NETWORK_DEVICES as c on n.id = c.id';
		$query    .= ' where sorgente = "GLPI" AND status = "online" and INET_ATON(ipaddress) > "0" group by hostname;';
	}
	/* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    try { $resultc = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    } 
	
    /* close database connection */
    $database->close();	
	
	$type_result = getAllDeviceTypes();
	foreach($type_result as $t) {
		$type[$t['tname']]=$t['tid'];	
	}
	
	foreach($result as $r) {
		$devices[$r[$index]]=$r;
		if(array_key_exists($r['computertype'],$type)){
			$devices[$r[$index]]['type'] = $type[$r['computertype']];
		}
		if(array_key_exists($r['type_name'],$type)){
			$devices[$r[$index]]['type'] = $type[$r['type_name']];
		}
	}
	
	foreach($resultc as $r) {
		$devices[$r[$index]]=$r;
		if(array_key_exists($r['computertype'],$type)){
			$devices[$r[$index]]['type'] = $type[$r['computertype']];
		}
		if(array_key_exists($r['type_name'],$type)){
			$devices[$r[$index]]['type'] = $type[$r['type_name']];
		}
	}
	
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}

function getSubnetFromGlpi ($address,$netmask)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);  

    $query    = 'select * from glpi_ipnetworks where address = "'. $address .'" and netmask = "'. $netmask .'";';
    /* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		$devices=$r;
	}
	
    /* return true, else false */
    if (!$devices) 	{ return false; }
    else 			{ return $devices; }	
}

/**
 * Get all unique devices
 */
function getAllUniqueLines ($orderby = "hostname", $direction = "asc")
{


 global $database;

 /* get all vlans, descriptions and subnets */

 $query = "SELECT * from `devices` LEFT JOIN `deviceTypes` ON `devices`.`type` = `deviceTypes`.`tid` where tname like 'Line%' order by `devices`.`$orderby` $direction;";

 /* execute */
	print ("<div class='alert alert-danger'>"._('Error').": $query</div>");
 try { $devices = $database->getArray( $query ); }
 catch (Exception $e) {
 $error = $e->getMessage();
 print ("<div class='alert alert-danger'>"._('Error').": $error $query</div>");
 return false;
 }

 /* return unique devices */
 return $devices;
}

function getDieselVmware_cuspropsFromGlpi ($id)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);  

    $query    = 'select * from glpi_plugin_dieselvmware_cusprops where computers_id = "'. $id .'" ;';
    /* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		$props[]=$r;
	}
	
    /* return true, else false */
    if (!$props) 	{ return false; }
    else 			{ return $props; }	
}

function getLocationFromGlpi ()
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);  

    $query    = 'select * from glpi_locations;';
    /* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		$locations['name'][$r['name']]=$r;
		$locations['id'][$r['id']]=$r;
	}
	
    /* return true, else false */
    if (!$locations) 	{ return false; }
    else 			{ return $locations; }	
}

function updateSubnetOnGlpi($res,$address,$netmask)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);
	
    $temp = 'update `glpi_ipnetworks` set comment = "update by Ipam"';
	foreach($res as $k=>$r) {
		$temp .= ', `'.$k.'` = "'.$r.'"';
	}
	$temp .= ' where address = "'. $address .'" and netmask = "'. $netmask .'";';
	$query[] = $temp;
	
	# glue
    $query = implode("\n", $query);
	
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	//update
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}

function updateLocatiosOnGlpi($res,$id)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);
	
    $temp = 'update `glpi_locations` set comment = "update by Ipam"';
	foreach($res as $k=>$r) {
		$temp .= ', `'.$k.'` = "'.$r.'"';
	}
	$temp .= ' where id = "'. $id .'";';
	$query[] = $temp;
	
	# glue
    $query = implode("\n", $query);
	
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	//update
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}

function insertSubnetOnGlpi($res)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);
	
    $temp = 'insert into `glpi_ipnetworks` ';
	$row = '(`';
	$value = ') values ("';
	foreach($res as $k=>$r) {
		$row  .= $k.'`,`';
		$value .= $r.'","';
	}
	$row = substr($row, 0, -2);
	$value = substr($value, 0, -2);
	$temp .= $row.$value.');';
	$query[] = $temp;
	# glue
    $query = implode("\n", $query);
	
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	//update
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }
    # default ok
    return true;
}

function insertLocationsOnGlpi($res)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);
	
    $temp = 'insert into `glpi_locations` ';
	$row = '(`';
	$value = ') values ("';
	foreach($res as $k=>$r) {
		$row  .= $k.'`,`';
		$value .= $r.'","';
	}
	$row = substr($row, 0, -2);
	$value = substr($value, 0, -2);
	$temp .= $row.$value.');';
	$query[] = $temp;
	# glue
    $query = implode("\n", $query);
	
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	//update
    try { $res = $database->executeQuery( $query, true ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }
    # default ok
    return $res;
}

/**
 * Get device from nedi index
 */

function getDevicesFromNedi ($index,$where = NULL,$limit = true)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

	$DevVendor	= array(
    "b" => "Cisco",
    "c" => "Dell",
	"g" => "Hewlett-Packard",
	"r" => "Brocade",
	"o" => "Avaya",
	"y" => "Alcatel-Lucent",
	"p" => "Extreme Networks",
	"n" => "NetApp",
	"i" => "Ibm",
	"w" => "Radware",
	"f" => "F5",
	"s" => "Sun/Oracle",
	"t" => "Avocent/Emerson",
	"j" => "Juniper",
	"f" => "Fortinet",
	"v" => "VMware"
	);
	
    $query    = 'select UCASE(device) as device ,devip as ip_addr,type as model,description,icon from devices'; 
	if ($where){
		$query .= ' where device = "'.$where.'"';
	}
	if ($limit){
		$query .= ' order by device limit 1;';
	}else{
		$query .= ' where devip > 0 order by device;';
	}
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	foreach($result as $r) {
		$devices[$r[$index]] = $r;	
		$devices[$r[$index]]['type'] = DevTyp($r['icon']);
		#$vendor = DevVendor("",substr($r['icon'],2,1));
		#$devices[$r[$index]]['vendor'] = $vendor[0];
		$devices[$r[$index]]['vendor'] = $DevVendor[substr($r['icon'],2,1)];
	}
	
    /* return true, else false */
    if (!$devices) 	{ return false; }
    else 			{ return $devices; }	
}

//===================================================================
// Return Device id type based on icon
function DevTyp($i){
	
	$result = getAllDeviceTypes();
	foreach($result as $r) {
		$type[$r['tname']]=$r['tid'];	
	}
	
	if( preg_match('/^r[smb]/',$i) ){
		return $type['Router'];#"Router";
	}elseif( preg_match('/^w2/',$i) ){
		return $type['Switch'];#"Workgroup L2 Switch";
	}elseif( preg_match('/^w3/',$i) ){
		return $type['Switch'];#"Workgroup L3 Switch";
	}elseif( preg_match('/^c2/',$i) ){
		return $type['Switch'];#"Chassis L2 Switch";
	}elseif( preg_match('/^c3/',$i) ){
		return $type['Switch'];#"Chassis L3 Switch";
	}elseif( preg_match('/^fv/',$i) ){
		return $type['Firewall'];# "Virtual FW";
	}elseif( preg_match('/^fw/',$i) ){
		return $type['Firewall'];#"Firewall";
	}elseif( preg_match('/^vp/',$i) ){
		return $type['VPN Gateway'];#"VPN FW";
	}elseif( preg_match('/^ap/',$i) ){
		return $type['Appliance'];#"Appliance";
	}elseif( preg_match('/^cs/',$i) ){
		return $type['Appliance'];#"Contentswitch";
	}elseif( preg_match('/^lb/',$i) ){
		return $type['Load Balancer'];#"Loadbalancer";
	}elseif( preg_match('/^ic/',$i) ){
		return $type['Media Device'];#"IP Camera";
	}elseif( preg_match('/^iv/',$i) ){
		return $type['Media Device'];#"Video Conferencing";
	}elseif( preg_match('/^bs/',$i) ){
		return $type['Server'];#"Bladeserver Chassis";
	}elseif( preg_match('/^sp/',$i) ){
		return $type['Switch Processor'];#"Switch Processor";
	}elseif( preg_match('/^se/',$i) ){
		return $type['Sensor'];#"Sensor";
	}elseif( preg_match('/^sv/',$i) ){
		return $type['Server'];#"Server";
	}elseif( preg_match('/^ph/',$i) ){
		return $type['IP Phone'];#"IP Phone";
	}elseif( preg_match('/^at/',$i) ){
		return $type['IP Phone'];#"Voice Adapter";
	}elseif( preg_match('/^up/',$i) ){
		return $type['BAS'];#"UPS";
	}elseif( preg_match('/^pg/',$i) ){
		return $type['Printer'];#"B&W Printer";
	}elseif( preg_match('/^pc/',$i) ){
		return $type['Printer'];#"Color Printer";
	}elseif( preg_match('/^hv/',$i) ){
		return $type['Workstation'];#"Hypervisor";
	}elseif( preg_match('/^vs/',$i) ){
		return $type['Switch'];#"Virtual Switch";
	}elseif( preg_match('/^fc/',$i) ){
		return $type['Fiberchannel Switch'];#"Fibrechannel Switch";
	}elseif( preg_match('/^st/',$i) ){
		return $type['Server'];#"Storage";
	}elseif( preg_match('/^wc/',$i) ){
		return $type['Wireless'];#"Wireless Controller";
	}elseif( preg_match('/^wa/',$i) ){
		return $type['Wireless'];#"Wireless AP";
	}elseif( preg_match('/^wb/',$i) ){
		return $type['Wireless'];#"Wireless Bridge";
	}elseif( preg_match('/^ip/',$i) ){
		return $type['Ips/Ids'];#"Ips/Ids";
	}elseif( preg_match('/^kv/',$i) ){
		return $type['Kvm'];#"Kvm";
	}elseif( preg_match('/^md/',$i) ){
		return $type['Media'];#"Media";
	}else{
		return $type['Other'];
	}
}

function DevVendor($so,$ic=''){

	global $stco,$mlvl;

	$s = explode('.',$so);
	if( $ic == 'b' or $s[6] == 9 or $s[6] == 14179 ){
		return array('Cisco','cis');
	}elseif( $ic == 'c' or $s[6] == 674 or $s[6] == 6027 ){
		return array('Dell','de');
	}elseif( $ic == 'g' or $s[6] == 11 or $s[6] == 43 or $s[6] == 8744 or $s[6] == 25506  ){
		return array('Hewlett-Packard','hp');
	}elseif( $ic == 'r' or $s[6] == 1991 ){
		return array('Brocade','brc');
	}elseif( $ic == 'o' or $s[6] == 45 or $s[6] == 2272 ){
		return array('Avaya','ava');
	}elseif( $ic == 'y' or $s[6] == 6486 ){
		return array('Alcatel-Lucent','alu');
	}elseif( $ic == 'p' or $s[6] == 1916 ){
		return array('Extreme Networks','ext');
	}elseif( $ic == 'e' or $s[6] == 19746 ){
		return array('Emc2','emc');
	}elseif( $ic == 'n' or $s[6] == 789 ){
		return array('NetApp','nap');
	}elseif( $ic == 'i' or $s[6] == 182 ){
		return array('Ibm','ibm');
	}elseif( $ic == 'w' or $s[6] == 89 ){
		return array('Radware','rad');
	}elseif( $ic == 'f' or $s[6] == 3375 ){
		return array('F5','f5');
	}elseif( $ic == 's' ){
		return array('Sun/Oracle','ora');
	}elseif( $ic == 't' or $s[6] == 10418 ){
		return array('Avocent/Emerson','eme');
	}elseif( $ic == 'j' or $s[6] == 2636 or $s[6] == 3224 ){
		return array('Juniper','jun');
	}elseif( $s[6] == 12356){
		return array('Fortinet','for');
	}elseif( $ic == 'v' or $s[6] == 6876 ){
		return array('VMware','vm');
	}else{
		return array($mlvl['10'],'gend'); 
	}
}

/**
 * Get nodes from nedi index
 */

function getNodesFromNedi ($min,$max,$index,$days = 30)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

	$days = (time() - ($days * 86400));
	
	$query    = 'select nodip as ifip,mac as ifmac,INET_NTOA(nodip) as ip,lastseen as lastSeen from nodes where nodip > "'. $min .'" and nodip < "'. $max .'" and lastseen > '.$days.' order by nodip,lastSeen;';

    /* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		$devices[$r[$index]]=$r;	
	}
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}

/**
 * Get nodes from nedi index
 */

function getBalancedFromNedi ($min,$max,$index,$days = 30)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

	$days = (time() - ($days * 86400));
	
	$query    = 'select *,clip as ifip,INET_NTOA(clip) as ip from bpolicies LEFT JOIN bfarms USING (device,farm) where clip > "'. $min .'" and clip < "'. $max .'" order by clip;';

    /* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	$devices = array();
	foreach($result as $r) {
		if (!array_key_exists($r['ifip'], $devices) || !array_key_exists('farm', $devices[$r['ifip']]) || !array_key_exists($r['farm'], $devices[$r['ifip']]['farm'])) {
			$devices[$r['ifip']]['farm'][$r['farm']]=$r;
			if($r['rsip'] != ""){$devices[$r['ifip']]['farm'][$r['farm']]['bfarm'][$r['rsip']]=$r;}
		}else{
			if($r['rsip'] != ""){$devices[$r['ifip']]['farm'][$r['farm']]['bfarm'][$r['rsip']]=$r;}
		}
		if ($r['rsip'] != "" && (!array_key_exists($r['rsip'], $devices) || !array_key_exists('bfarm', $devices[$r['ifip']]) || !array_key_exists($r['farm'], $devices[$r['ifip']]['bfarm']))){
			$devices[$r['rsip']]['bfarm'][$r['farm']]=$r;
		}
		#if (!array_key_exists('device', $devices[$r['rsip']])){$devices[$r['rsip']]['device'] = $r['device'];}
		if (!array_key_exists('device', $devices[$r['ifip']])){$devices[$r['ifip']]['device'] = $r['device'];}
		#if (!array_key_exists('ifname', $devices[$r['rsip']])){$devices[$r['rsip']]['ifname'] = $r['ifname'];}
		if (!array_key_exists('ifname', $devices[$r['ifip']])){$devices[$r['ifip']]['ifname'] = $r['ifname'];}
	}
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}

function getNatFromNedi ($min,$max)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

	$days = (time() - ($days * 86400));
	
	$query    = 'select *,INET_NTOA(nip) as n_ip,INET_NTOA(mip) as m_ip from nats where ( nip >= "'. $min .'" and nip <= "'. $max .'" ) or ( mip >= "'. $min .'" and mip <= "'. $max .'" );';

    /* execute */
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
    /* close database connection */
    $database->close();	
	
	#ini_set('memory_limit', '512M');
	
	$devices = array();
	foreach($result as $r) {
		if ($r['type'] == "DIP"){
			for ($i = $r['mip']; $i <= $r['nip']; $i++) {
				$devices[$i]=$r;
				$devices[$i]['ifip']=$i;
				$devices[$i]['iptype']="dip";
			}
		}else{
			if (!array_key_exists($r['mip'], $devices)) {
				$devices[$r['mip']]=$r;
				$devices[$r['mip']]['ifip']=$r['mip'];
				$devices[$r['mip']]['iptype']="mip";
			}
			if (!array_key_exists($r['nip'], $devices)) {
				$devices[$r['nip']]=$r;
				$devices[$r['nip']]['ifip']=$r['nip'];
				$devices[$r['nip']]['iptype']="nip";
			}
			if($r['type'] == "VIP"){
				if (array_key_exists($r['mip'], $devices)) {
					$devices[$r['mip']]['vip'][$r['nip']]=$r;
				}
				if (array_key_exists($r['nip'], $devices)) {
					$devices[$r['nip']]['vip'][$r['mip']]=$r;
				}
			}
		}
	}
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}

/**
 * Get vlans from nedi index
 */
 
function getVansFromNedi ($id = '',$device = '',$name = '', $limit = true)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

    $query    = 'select * from vlans ';
	if ($id && $device) {
		$query .= 'where vlanid = "'.$id.'" and device = "'.$device.'"';
	}elseif ($id && $name) {
		$query .= 'where vlanid = "'.$id.'" and vlanname = "'.$name.'"';
	}elseif ($id && !$device && !$name) {
		$query .= 'where vlanid = "'.$id.'"';
	}
	$query .= ' order by vlanid';
	if (!$device && !$name && $limit) {$query .= ' limit 1';}
	$query .= ';';
	
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  

	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		$vlans[$r['vlanid']][$r['device']]=$r;	
	}
	
    /* return true, else false */
    if (!$vlans) 	{ return false; }
    else 			{ return $vlans; }	
}

function getSubnetDetailsByVlan ($id)
{

	    global $database;                                                                      
	    /* set query */
	    $query         = 'select * from `subnets` where `vlanId` = "'. $id .'";';
	    /* execute */
	    try { $SubnetDetails = $database->getArray( $query ); }
	    catch (Exception $e) { 
	        $error =  $e->getMessage(); 
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    } 
	    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
	    if(sizeof($SubnetDetails) > 0) {
	    	return($SubnetDetails[0]); 
	    }

}

/**
 * Get vlans from phpipam index
 */
 
function getVansbyIndex ()
{
	global $db;                                                                      # get variables from config file
	$database    = new database($db['host'], $db['user'], $db['pass'], $db['name']);

    $query    = 'select * from vlans order by vlanId;';

    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
    /* close database connection */
    $database->close();	
	
	foreach($result as $r) {
		$vlans[$r['number']][$r['switch']]=$r;	
	}
	
    /* return true, else false */
    if (!$vlans) 	{ return false; }
    else 			{ return $vlans; }	
}

/**
 * Get networks from nedi index
 */
 
function getNetworksFromNedi ($index,$min,$max)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

    $query    = 'select networks.*,ifdesc,alias,pvid from networks left join interfaces using (device,ifname)';
	if ($min && $max){
		$query .= ' where networks.ifip >= "'. $min .'" and networks.ifip <= "'. $max .'"';
	}
	$query .= ' order by ifip;';

    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error:$query</div>");
        return false;
    }  
	#print ("<div class='alert alert-info'>Query:$query</div>");
    /* close database connection */
    $database->close();	
	
	if ($index){
		foreach($result as $r) {
			if ($r['prefix']>0 && $r['prefix']<33){
			
			$cidr = Transform2long($r[ifip])."/".$r['prefix'];
			# verify input CIDR
			$errors = verifyCidr ($cidr,0);

			# die on errors
			if (sizeof($errors) != 0) { die('<div class="alert alert-danger alert-absolute">'._('Invalid input').': '.  $errors[0] .'</div>'); }

			if ($index=='subnet'){
				$subnet_det = calculateIpCalcResult($cidr);
				$subnet = Transform2decimal($subnet_det['Network']);
				$networks[$subnet]=$r;
				$networks[$subnet]['subnet']=$subnet;
			}else{
				$networks[$r[$index]]=$r;
				$subnet_det = calculateIpCalcResult($cidr);
				$subnet = Transform2decimal($subnet_det['Network']);
				$networks[$r[$index]]['subnet'] = $subnet;
			}
			}
		}
	}
	
    /* return true, else false */
    if (!$networks) 	{ return false; }
    else 			{ return $networks; }	
}

/**
 * Get device details index by ip
 */
 
function getDeviceIndexHostname ($index)
{
    global $db;                                                                      # get variables from config file
    /* set check query and get result */
    $database = new database ($db['host'], $db['user'], $db['pass'], $db['name']);
    $query = "select * from devices;";
    
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 

    /* close database connection */
    $database->close();
    
	foreach($result as $r) {
		$devices[$r[$index]]=$r;	
	}
	
    /* return true, else false */
    if (!$devices) 	{ return false; }
    else 			{ return $devices; }
	
}

/**
 * Get subnets details index by
 */
 
function getSubnetsIndex ($index)
{
    global $db;                                                                      # get variables from config file
    /* set check query and get result */
    $database = new database ($db['host'], $db['user'], $db['pass'], $db['name']);
    $query = "select * from subnets;";
    
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 

    /* close database connection */
    $database->close();
    
	foreach($result as $r) {
		$devices[$r[$index]]=$r;	
	}
	
    /* return true, else false */
    if (!$devices) 	{ return false; }
    else 			{ return $devices; }
	
}

/**
 * Get subnets details index by
 */
 
function getSubnetsIdPingSubnet ()
{
    global $db;                                                                      # get variables from config file
    /* set check query and get result */
    $database = new database ($db['host'], $db['user'], $db['pass'], $db['name']);
    $query = "select id from subnets where pingSubnet = 1;";
    
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 

    /* close database connection */
    $database->close();
	
    /* return true, else false */
    if (!$result) 	{ return false; }
    else 			{ return $result; }
	
}

function updateDeviceSection ($deviceId,$section)
{
    global $db;                                                                      # get variables from config file
    /* set check query and get result */
    $database = new database ($db['host'], $db['user'], $db['pass'], $db['name']);
    $query = "select * from devices where id = '".$deviceId."';";
    
    /* execute */
    try { $result = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }
	if($result[0]['sections'] != '') {
		$temp = explode(";", $result[0]['sections']);
		if(!in_array($section, $temp)){
			$query_i = "update `devices` set `sections` = '".$result[0]['sections'].";".$section."' where `id` = '".$deviceId."';";
		}	
	}else{
		$query_i = "update `devices` set `sections` = '".$section."' where `id` = '".$deviceId."';";
	}
	if ($query_i){
		try { $res = $database->executeQuery( $query_i ); }
		catch (Exception $e) { 
			$error =  $e->getMessage(); 
			print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
			return false;
		}
	
		/* close database connection */
		$database->close();
		return $result[0]['hostname'];
	}else{
		return false;
	}
}

/**
 * Update host lastSeen
 */
function updateLastSeenValue($lastseen)
{
    global $db;                                                                      # get variables from config file
    $database    = new database($db['host'], $db['user'], $db['pass'], $db['name']); 
    
	foreach($lastseen as $k=>$r) {
    /* get all vlans, descriptions and subnets */
		$temp = 'update `ipaddresses` set `lastSeen` = "'.$r['update'].'"';
		if ($r['state']){$temp .= ', `state` = "'.$r['state'].'"';}
		if ($r['mac']){$temp .= ', `mac` = "'.$r['mac'].'"';}
		if ($r['port']){$temp .= ', `port` = "'.$r['port'].'"';}
		if ($r['Address Type']){$temp .= ', `Address Type` = "'.$r['Address Type'].'"';}
		if ($r['description']){$r['description'] = mysqli_real_escape_string($database, $r['description']);print ("<div class='alert alert-info'>Query:".$r['description']."</div>\n");$temp .= ', `description` = "'.$r['description'].'"';}
		if ($r['switch']){$temp .= ', `switch` = "'.$r['switch'].'"';}
		$temp .= ' where `id` = "'.$r['id'].'";';
		$query[] = $temp;
	}
	# glue
    $query = implode("\n", $query);
	if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query</div>");}
	#print ("<div class='alert alert-info'>Query: $query</div>\n");
	//update
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}

function insertNediScanResults($res, $subnetId)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);    # open db
    
	/* First we need to get custom fields! */
	$myFields = getCustomFields('ipaddresses');

	$query = array();
    # set queries
    foreach($res as $ip) {
    	//escape strings
    	$ip['description'] = mysqli_real_escape_string($database, $ip['description']);
    	$myFieldsInsert['query']  = '';
		$myFieldsInsert['values'] = '';
		if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {	
				# empty?
				if(strlen($ip[$myField['name']])>0) {

					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", '". $ip[$myField['name']] . "'";
				}	
			}
			#print_r($myFieldsInsert);
		}
		$query_string = "insert into `ipaddresses` (`ip_addr`,`subnetId`,`description`,`dns_name`,`mac`,`switch`,`port`,`lastSeen` ". $myFieldsInsert['query'] .") ";
		$query_string .= "values ";
		$query_string .= "('".transform2decimal($ip['ip_addr'])."', '$subnetId', '$ip[description]', '$ip[dns_name]', '$ip[mac]', '$ip[switch]', '$ip[ifname]', NOW() ". $myFieldsInsert['values'] .");";
 
		if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query_string</div>");print "<pre> result";print_r($query_string);print "</pre>";}

		try { $database->executeQuery($query_string); }
		catch (Exception $e) { 
			$error =  $e->getMessage(); 
			continue;
		}
		
	}

    return true;
}

function insertNediSubnetsResults($res,$sectionId)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);    # open db
	
	$undId = getSubnetIdFromSubnetName('Undefined',$sectionId);
    # set queries
	
	#print ("<div class='alert alert-info'>Query:$undId,".$sectionId.",$subnetId</div>");
    foreach($res as $sub) {
    	//escape strings
    	#$sub['description'] = mysqli_real_escape_string($database, $ip['description']);

	    if($sub[mask]){
			if($undId){
				$query[] = "insert into `subnets` (`subnet`,`mask`,`description`,`sectionId`,`vrfId`, `masterSubnetId`,`vlanId`,`showName`,`editDate`,`Gateway`,`Device`,`port`) values ('$sub[subnet]', '$sub[mask]', '$sub[description]', '$sectionId','$sub[vrf]', '$undId', '$sub[vlanId]', '1', NOW(),'$sub[gateway]', '$sub[device]', '$sub[port]'); ";
			}else{
				$query[] = "insert into `subnets` (`subnet`,`mask`,`description`,`sectionId`,`vrfId`, `masterSubnetId`,`vlanId`,`showName`,`editDate`,`Gateway`,`Device`,`port`) values ('$sub[subnet]', '$sub[mask]', '$sub[description]', '".getSectionIdFromSectionName('Undefined')."','$sub[vrf]', '0', '$sub[vlanId]', '1', NOW(),'$sub[gateway]', '$sub[device]', '$sub[port]'); ";
			}
		}
	}
    # glue
    $query = implode("\n", $query);
	#print ("<div class='alert alert-info'>Query:$query</div>");
    # execute query
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}


function insertNediDevice($res,$sections = '')
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);    # open db
    
    # set queries
    foreach($res as $ip) {
    	//escape strings
    	$ip['description'] = mysqli_real_escape_string($database, $ip['description']);
    			
	    $query[] = "insert into `devices` (`hostname`,`ip_addr`, `type`, `vendor`, `model`, `description`, `sections`) values ('$ip[device]', '".Transform2long($ip[ip_addr])."', '$ip[type]', '$ip[vendor]', '$ip[model]', '$ip[description]', '$sections'); ";
    }
    # glue
    $query = implode("\n", $query);

    # execute query
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}

/**
 * Update host lastSeen
 */
function updateNediDevice($res,$sections = '')
{
    global $db;                                                                      # get variables from config file
    $database    = new database($db['host'], $db['user'], $db['pass'], $db['name']); 
    
	foreach($res as $k=>$r) {
    /* get all vlans, descriptions and subnets */
		$temp = 'update `devices` set editDate = Now()';
		if ($r['ip_addr']){$temp .= ', `ip_addr` = "'.$r['ip_addr'].'"';}
		if ($r['type']){$temp .= ', `type` = "'.$r['type'].'"';}
		if ($r['model']){$temp .= ', `model` = "'.$r['model'].'"';}
		if ($r['description']){$temp .= ', `description` = "'.$r['description'].'"';}
		$temp .= ' where `hostname` = "'.$k.'";';
		$query[] = $temp;
	}
	# glue
    $query = implode("\n", $query);
 
	//update
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}

function insertNediVlan($name,$number,$description,$switch)
{
    global $db;                                                                      # get variables from config file
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);    # open db
			
	$query[] = "insert into `vlans` (`name`,`number`, `description`, `switch`, `editDate`) values ('$name', '$number', '$description', '$switch', Now()); ";

    # glue
    $query = implode("\n", $query);

    # execute query
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print "<div class='alert alert-danger'>$error</div>";
        return false;
    }
    # default ok
    return true;
}
function printSite($site,$custom,$permission = 3,$class = 'editSITE')
{
	print '	<td class="name">'. $site['name'] .'</td>'. "\n";
	print '<input type="hidden" name="company" value="'.$site['company'] .'">';
	print '	<td class="location">'. $site['location'] .'</td>'. "\n";
	$master = subnetGetSITEdetailsById($site['masterSiteId']);
	if ($master['siteId']>0){
		print '	<td class="master">'. $master['name'] .'</td>'. "\n";
	}else{
		print '	<td class="master"></td>'. "\n";
	}
	
	if(sizeof($custom) > 0) {
		foreach($custom as $field) {

			print "<td class='customField hidden-xs hidden-sm'>";
					
			//booleans
			if($field['type']=="tinyint(1)")	{
				if($site[$field['name']] == "0")		{ print _("No"); }
				elseif($site[$field['name']] == "1")	{ print _("Yes"); }
			} 
			//text
			elseif($field['type']=="text") {
				if(strlen($site[$field['name']])>0)	{ print "<i class='fa fa-gray fa-comment' rel='tooltip' data-container='body' data-html='true' title='".str_replace("\n", "<br>", $site[$field['name']])."'>"; }
				else											{ print ""; }
			}
			else {
				print $site[$field['name']];
				
			}
			print "</td>"; 

		}
	}
	print "	<td class='actions'>";
	print "	<div class='btn-group'>";
	if($permission > "0"){print "		<button class='btn btn-xs btn-default ".$class."' data-action='add sub'   data-siteid='".$site['siteId']."'><i class='fa fa-plus'></i></button>";}
	if($permission > "1"){print "		<button class='btn btn-xs btn-default ".$class."' data-action='edit'   data-siteid='".$site['siteId']."'><i class='fa fa-pencil'></i></button>";}
	if($class == 'editSITE'){print "		<button class='btn btn-xs btn-default showSitePerm' data-action='show'   data-siteid='".$site['siteId']."'><i class='fa fa-tasks'></i></button>";}
	if($permission > "2"){print "		<button class='btn btn-xs btn-default ".$class."' data-action='delete' data-siteid='".$site['siteId']."'><i class='fa fa-times'></i></button>";}
	print "	</div>";
	print "	</td>";	
	print '</tr>'. "\n";
	
}

#####Site######

/**
 *	Update subnet permissions
 */
function updateSitePermissions ($site)
{
    global $database;   

    # replace special chars
    $site['permissions'] = mysqli_real_escape_string($database, $site['permissions']);

    # set querries for subnet and each slave
    foreach($site['slaves'] as $slave) {
    	$query .= "update `sites` set `permissions` = '$site[permissions]' where `siteId` = $slave;";	
    	
    	writeChangelog('site', 'perm_change', 'success', array(), array("permissions_change"=>"$site[permissions]", "siteId"=>$slave));
    }
    
	# execute
    try { $database->executeMultipleQuerries($query); }
    catch (Exception $e) { 
    	$error =  $e->getMessage(); 
    	print('<div class="alert alert-danger">'._('Error').': '.$error.'</div>');
    	return false;
    }
  
	/* return true if passed */
	return true;	
}

/* @SITE functions ---------------- */


/**
 * Update SITE details
 */
function updateSITEDetails($site, $lastId = false)
{
    global $database;

    /* set querry based on action */
    if($site['action'] == "add" || $site['action'] == "add sub") {
    
        # custom fields
        $myFields = getCustomFields('sites');
        $myFieldsInsert['query']  = '';
        $myFieldsInsert['values'] = '';
	
		if (!isset($_SESSION)) {  session_start(); }
		# redirect if not authenticated */
		if (empty($_SESSION['ipamusername'])) 	{ return "0"; }
		else									{ $username = $_SESSION['ipamusername']; }
		
		# get all user groups
		$user = getUserDetailsByName ($username);
		#print'<pre>';
		#print_r($user);
		#print'</pre>';
		#print ("<div class='alert alert-info'>Query:$user,".$user['groups']."</div>");
		$groups = json_decode($user['groups']);
		$masterSite = subnetGetSITEdetailsById ($site['masterSiteId']);
		if($user['role'] != "Administrator"){
			$siteP = json_decode($masterSite['permissions']);
			$sitePP = parseSectionPermissions($masterSite['permissions']);
			foreach($siteP as $sk=>$sp) {
				foreach($groups as $uk=>$up) {
					if($uk == $sk) {
						if($sp != "3") { $new = $sk; }
					}	
				}
			}
			$sitePP[$new] = "3";
			$masterSite['permissions'] = json_encode($sitePP);
		}
        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {	
				# empty?
				if(strlen($site[$myField['name']])==0) {		
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", NULL";
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", '". $site[$myField['name']] . "'";
				}
			}
		}
		$masterSite['permissions'] = mysqli_real_escape_string($database, $masterSite['permissions']);
    	$query  = 'insert into `sites` '. "\n";
    	$query .= '(`name`,`company`,`location`,`masterSiteId`,`permissions` '.$myFieldsInsert['query'].') values '. "\n";
   		$query .= '("'. $site['name'] .'", "'. $site['company'] .'", "'. $site['location'] .'", "'. $site['masterSiteId'] .'", "'. $masterSite['permissions'] .'" '. $myFieldsInsert['values'] .' ); '. "\n";

    }
    else if($site['action'] == "edit") {
    
        # custom fields
        $myFields = getCustomFields('sites');
        $myFieldsInsert['query']  = '';
	
        if(sizeof($myFields) > 0) {
			/* set inserts for custom */
			foreach($myFields as $myField) {
				if(strlen($site[$myField['name']])==0) {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = NULL ';				
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'` = "'.$site[$myField['name']].'" ';					
				}		
			}
		}
    
    	$query  = 'update `sites` set '. "\n";    
    	$query .= '`name` = "'. $site['name'] .'", `company` = "'. $site['company'] .'", `location` = "'. $site['location'] .'", `masterSiteId` = "'. $site['masterSiteId'] .'" '. "\n";   
    	$query .= $myFieldsInsert['query'];  
    	$query .= 'where `siteId` = "'. $site['siteId1'] .'";'. "\n";    
    }
    else if($site['action'] == "delete") {
    	$query  = 'delete from `sites` where `siteId` = "'. $site['siteId1'] .'";'. "\n";
    }
    
	#print ("<div class='alert alert-danger'>Query: ".$query.": $error</div>");
    /* execute */
    try { $res = $database->executeQuery( $query, true ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
   		updateLogTable ('SITE ' . $site['action'] .' failed ('. $site['name'] . ')'.$error, $log, 2);
    	return false;
    }
    
    # if delete also NULL all subnets!
    if($site['action'] == 'delete') {
	    $query = "update `subnets` set `siteId` = NULL where `siteId` = '$site[siteId]';";
	    /* execute */
	    try { $database->executeQuery( $query ); }
	    catch (Exception $e) {
    		$error =  $e->getMessage();
    		print ('<div class="alert alert-danger alert-absolute">'.$error.'</div>');
    	}
    }
    
    /* prepare log */ 
    $log = prepareLogFromArray ($site);
    
    /* return success */
    updateLogTable ('SITE ' . $site['action'] .' success ('. $site['name'] . ')', $log, 0);
    
    /* response */
    if($lastId)	{ return $res; }
    else		{ return true; }
}

/**
 * Get number of  users
 */
function getNumberOfLoggedInUser ()
{

    global $database; 
    /* set query, open db connection and fetch results */
    $query    = "select count(*) as count from logs where id IN (select * from (select MAX(id) from logs  WHERE `command` REGEXP 'logged' and username != '' group by username) as id) and `command` REGEXP 'logged in';";



    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }  
	   
    /* return results */
    return($details[0]['count']);
}

/**
 * Get number of  users
 */
function getLoggedInUser ()
{

    global $database; 
    /* set query, open db connection and fetch results */
    $query    = "select *from logs where id IN (select * from (select MAX(id) from logs  WHERE `command` REGEXP 'logged' and username != '' group by username) as id) and `command` REGEXP 'logged in' order by date desc;";



    /* execute */
    try { $array = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }  
	 
	foreach($array as $r) {
		$result[]=$r;
	}
    /* return results */
    return($result);
}

/**
 *	Check subnet permissions
 */
function checkSitePermission ($siteId)
{
    # open session and get username / pass
	if (!isset($_SESSION)) {  session_start(); }
    # redirect if not authenticated */
    if (empty($_SESSION['ipamusername'])) 	{ return "0"; }
    else									{ $username = $_SESSION['ipamusername']; }
    
	# get all user groups
	$user = getUserDetailsByName ($username);
	$groups = json_decode($user['groups']);
	
	# if user is admin then return 3, otherwise check
	if($user['role'] == "Administrator")	{ return "3"; }

	# get subnet permissions
	$site  = getSiteDetailsById($siteId);
	$siteP = json_decode($site['permissions']);
	
	# get section permissions
	#$section  = getSectionDetailsById($site['siteId']);
	#$sectionP = json_decode($section['permissions']);
	
	# default permission
	$out = 0;
	
	# for each group check permissions, save highest to $out
	if(sizeof($siteP) > 0) {
		foreach($siteP as $sk=>$sp) {
			# check each group if user is in it and if so check for permissions for that group
			foreach($groups as $uk=>$up) {
				if($uk == $sk) {
					if($sp > $out) { $out = $sp; }
				}	
			}
		}
	}
	else {
		$out = "0";
	}
	
	# return result
	return $out;
}

/**
 *	get whole tree path for subnetId - from parent all slaves
 *
 * 	if multi than create multidimensional array
 */
$removeSlaves = array();

function getAllSiteSlaves ($siteId, $multi = false) 
{
	global $removeSlaves;
	$end = false;			# breaks while
	
	$removeSlaves[] = $siteId;		# first

	# db


	global $database; 

	
	while($end == false) {
		
		/* get all immediate slaves */
		$query = "select * from `sites` where `masterSiteId` = '$siteId' order by `siteId` asc; ";    
		/* execute query */
		try { $slaves2 = $database->getArray( $query ); }
		catch (Exception $e) { 
        	$error =  $e->getMessage(); 
        	print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        	return false;
        }


		# we have more slaves
		if(sizeof($slaves2) != 0) {
			# recursive
			foreach($slaves2 as $slave) {
				$removeSlaves[] = $slave['id'];
				getAllSlaves ($slave['id']);
				$end = true;
			}
		}
		# no more slaves
		else {
			$end = true;
		}
	}
}

/**
 *	get SITE details by ID
 */
function getSITEbyname ($name) 
{


    global $database;                                                                      # get variables from config file
	/* execute query */
	$query = 'select * from `sites` where `name` = "'. $name .'";';
    
    /* execute */
    try { $site = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
   	
   	/* return false if none, else list */
	if(sizeof($site) == 0) 	{ return false; }
	else 					{ return $site; }
}

/**
 * Get details for requested subnet by ID
 */
function getSiteDetailsById ($siteId)
{
	# for changelog
	if($id=="subnetId") {
		return false;
	}
	# check if already in cache
	elseif($vtmp = checkCache("site", $id)) {
		return $vtmp;
	}
	# query
	else {
	
	    global $database;                                                                      
	    /* set query */
	    $query         = 'select * from `sites` where `siteId` = "'. $siteId .'";';
	    /* execute */
	    try { $SiteDetails = $database->getArray( $query ); }
	    catch (Exception $e) { 
	        $error =  $e->getMessage(); 
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    } 
	    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
	    if(sizeof($SiteDetails) > 0) { 
	    	writeCache('site', $siteId, $SiteDetails[0]);
	    	return($SiteDetails[0]); 
	    }
    	
	}
}

function siteContainsSlaves($subnetId)
{
	# we need new temp variable for empties
	$subnetIdtmp = $subnetId;
	if(strlen($subnetIdtmp)==0)	{ $subnetIdtmp="root"; }
	# check if already in cache
	if($vtmp = checkCache("sitecontainsslaves", $subnetIdtmp)) {
		return $vtmp;
	}
	# query
	else {
	    global $database;                                                                     
	    
	    /* get all ip addresses in subnet */
	    $query 		  = 'SELECT count(*) from `sites` where `masterSiteId` = "'. $subnetId .'";';    
	
	    /* execute */
	    try { $slaveSites = $database->getArray( $query ); }
	    catch (Exception $e) { 
	        $error =  $e->getMessage(); 
	        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
	        return false;
	    }
		
		if($slaveSites[0]['count(*)']) { writeCache("sitecontainsslaves", $subnetIdtmp, true);	return true; }
		else 							 { writeCache("sitecontainsslaves", $subnetIdtmp, false);	return false; }
	
	}
}

/**
 *	Print dropdown menu for sites in section!
 */
function printDropdownMenuBySite($subnetSiteId = "0") 
{
		# get all sites
		#$sites = fetchSites ($siteId);
		$sites = getAllSites();
		$html = array();
		$children = array();
		$rootId = 0;									# root is 0
		
		# on-the-fly
		#if(checkAdmin(false)){
		$tmp[1]['siteId'] = 'Add';
		$tmp[1]['name'] = _('+ Add new SITE');	
		$tmp[1]['masterSiteId'] = 0;
		
		if($_POST['action'] != "add") {array_unshift($sites, $tmp[1]);}
		#}
		# sites
		foreach ( $sites as $item )
			$children[$item['masterSiteId']][] = $item;
		
		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop  = !empty( $children[$rootId] );
		
		# initializing $parent as the root
		$parent = $rootId;
		
		#$parent_stackF = array();
		$parent_stack  = array();
		
		
		# structure
		$html[] = "<select name='siteId' class='form-control input-sm input-w-auto input-max-200'>";

		# subnets
		$html[] = "<optgroup label='"._("Sites")."'>";
		
		# display selected subnet as opened
		#$allParents = getAllSiteParents ($_REQUEST['siteId']);
		
		# root subnet
		#if(checkAdmin(false)){
			if(!isset($subnetSiteId) || $subnetSiteId==0) {
				$html[] = "<option value='0' selected='selected'>"._("Root site")."</option>";
			} else {
				$html[] = "<option value='0'>"._("Root site")."</option>";			
			}
		#}		
		# return table content (tr and td's) - subnets
		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat 
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = $repeat; }
							
			# count levels
			$count = count( $parent_stack ) + 1;
			
			# print table line if it exists and it is not folder
			if(strlen($option['value']['name']) > 0) { 
				# selected
				$permission = checkSitePermission ($option['value']['siteId']);
				$printSITE = $option['value']['name'];
				
				if(!empty($option['value']['company']) && strlen($option['value']['name']) < 25) { $printSITE .= " (".$option['value']['company'].")"; }
				
				if ($permission > 0 || $option['value']['siteId'] == "Add"){
				if($option['value']['siteId'] == $subnetSiteId) 	{ $html[] = "<option value='".$option['value']['siteId']."' selected='selected'>$repeat ".$printSITE."</option>"; }
				else 											{ $html[] = "<option value='".$option['value']['siteId']."'>$repeat ".$printSITE."</option>"; }
				}
			}
			
			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children[$option['value']['siteId']] ) ) {														
				array_push( $parent_stack, $option['value']['masterSiteId'] );
				$parent = $option['value']['siteId'];
			}
			# Last items
			else { }
		}
		$html[] = "</optgroup>";
		$html[] = "</select>";
		
		print implode( "\n", $html );
}

function printDropdownMenuByMasterSite($masterSiteId = "0",$SiteId = "0") 
{
		# get all sites
		#$sites = fetchSites ($siteId);
		$sites = getAllSites();
		$html = array();
		$children = array();
		$rootId = 0;									# root is 0
		
		# on-the-fly
		#if(checkAdmin(false)){
		$tmp[1]['siteId'] = 'Add';
		$tmp[1]['name'] = _('+ Add new SITE');	
		$tmp[1]['masterSiteId'] = 0;
		
		if($_POST['action'] != "add" || $_POST['action'] != "add sub") {array_unshift($sites, $tmp[1]);}
		#}
		# sites
		foreach ( $sites as $item )
			$children[$item['masterSiteId']][] = $item;
		
		#print'<pre>';
		#print_r($children);
		#print'</pre>';
		# loop will be false if the root has no children (i.e., an empty menu!)
		$loop  = !empty( $children[$rootId] );
		
		# initializing $parent as the root
		$parent = $rootId;
		
		#$parent_stackF = array();
		$parent_stack  = array();
		
		
		# structure
		$html[] = "<select name='masterSiteId' class='form-control input-sm input-w-auto input-max-200'>";

		# subnets
		$html[] = "<optgroup label='"._("Sites")."'>";
		
		# display selected subnet as opened
		#$allParents = getAllSiteParents ($_REQUEST['siteId']);
		
		# root subnet
		if(checkAdmin(false)){
			if(!isset($masterSiteId) || $masterSiteId==0) {
				$html[] = "<option value='0' selected='selected'>"._("Root site")."</option>";
			} else {
				$html[] = "<option value='0'>"._("Root site")."</option>";			
			}
		}		
		# return table content (tr and td's) - subnets
		while ( $loop && ( ( $option = each( $children[$parent] ) ) || ( $parent > $rootId ) ) )
		{
			# repeat 
			$repeat  = str_repeat( " - ", ( count($parent_stack)) );
			# dashes
			if(count($parent_stack) == 0)	{ $dash = ""; }
			else							{ $dash = $repeat; }
							
			# count levels
			$count = count( $parent_stack ) + 1;
			print "<p class='".$option['value']['name']."'>";
			# print table line if it exists and it is not folder
			if(strlen($option['value']['name']) > 0) { 
				# selected
				$permission = checkSitePermission ($option['value']['siteId']);
				$printSITE = $option['value']['name'];
				
				if(!empty($option['value']['company']) && strlen($option['value']['name']) < 25) { $printSITE .= " (".$option['value']['company'].")"; }
				
				
				if ($permission > 0 && $option['value']['siteId'] != $SiteId){
					if($option['value']['siteId'] == $masterSiteId) 	{ $html[] = "<option value='".$option['value']['siteId']."' selected='selected'>$repeat ".$printSITE."</option>"; }
					else 											{ $html[] = "<option value='".$option['value']['siteId']."'>$repeat ".$printSITE."</option>"; }
				}
			}
			
			if ( $option === false ) { $parent = array_pop( $parent_stack ); }
			# Has slave subnets
			elseif ( !empty( $children[$option['value']['siteId']] ) ) {														
				array_push( $parent_stack, $option['value']['masterSiteId'] );
				$parent = $option['value']['siteId'];
			}
			# Last items
			else { }
		}
		$html[] = "</optgroup>";
		$html[] = "</select>";
		
		print implode( "\n", $html );
}


/**
 *	get whole tree path for subnetId - from slave to parents
 */
function getAllSiteParents ($siteId) 
{
	$parents = array();
	$root = false;
	
	while($root == false) {
		$subd = getSiteDetailsById($siteId);		# get site details
		
		if($subd['masterSiteId'] != 0) {
			array_unshift($parents, $subd['masterSiteId']);
			$siteId  = $subd['masterSiteId'];
		}
		else {
			array_unshift($parents, $subd['masterSiteId']);
			$root = true;
		}
	}

	return $parents;
}

/**
 * Get all subnets in provided sectionId
 */
function fetchSites ($siteId, $orderType = "subnet", $orderBy = "asc" )
{
    global $database; 
    /* check for sorting in settings and override */
    $settings = getAllSettings();
    
    /* get section details to check for ordering */
    #$section = getSectionDetailsById ($siteId);
    
    // section ordering
   # if($section['subnetOrdering']!="default" && strlen($section['subnetOrdering'])>0 ) {
	#    $sort = explode(",", $section['subnetOrdering']);
	#    $orderType = $sort[0];
	#    $orderBy   = $sort[1];	    
   # }
    // default - set via settings
    #elseif(isset($settings['subnetOrdering']))	{
	#    $sort = explode(",", $settings['subnetOrdering']);
	#    $orderType = $sort[0];
	#    $orderBy   = $sort[1];
   # }

    /* set query, open db connection and fetch results */
    $query 	  = "select * from `sites` order by masterSiteId;"; # ORDER BY `isFolder` desc,`masterSubnetId`,`$orderType` $orderBy
    
    /* execute */
    try { $sites = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    $database->close();

    /* return subnets array */
    return($sites);
}

/**
 * Get SITE number form Id
 */
function subnetGetSITEdetailsById($siteId)
{
    global $database; 
    
    /* first update request */
    $query    = 'select * from `sites` where `siteId` = "'. $siteId .'";';

    /* execute */
    try { $site = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }   
  
	/* return site details if exists */
	if(sizeof($site) != 0) 	{ return $site[0]; }	
	else 					{ return false; }
}

function getAllSites($tools = false)
{
    global $database;
    
    # custom fields
    $myFields = getCustomFields('sites');     
    $myFieldsInsert['id']  = '';
	
    if(sizeof($myFields) > 0) {
		/* set inserts for custom */
		foreach($myFields as $myField) {			
			$myFieldsInsert['id']  .= ',`sites`.`'. $myField['name'] .'`';
		}
	}
		
    /* check if it came from tools and use different query! */
    if($tools) 	{ $query = 'SELECT siteId,name,company,location,permissions,masterSiteId'.$myFieldsInsert['id'].' FROM sites where used = 1 ORDER BY name ASC;'; }
    else 		{ $query = 'select * from `sites` where used = 1 order by `name` asc;'; }
	
    /* execute */
    try { $site = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }  
  
	/* return vlan details */
	return $site;
}

/**
 * Get sectionId for requested name - needed for hash page loading
 */
function getSubnetIdFromSubnetName ($subnetName,$sectionId) 
{
    global $db;                                                                      # get variables from config file
    /* set query, open db connection and fetch results */
    $query         = 'select id from subnets where description = "'. $subnetName .'" and sectionId = "'. $sectionId .'";';
    $database      = new database($db['host'], $db['user'], $db['pass'], $db['name']);
    #print ("<div class='alert alert-info'>Query:$query</div>");
    /* execute */
    try { $SubnetDetails = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }     
    $database->close();

    /* return subnet details - only 1st field! We cannot do getRow because we need associative array */
    return($SubnetDetails[0]['id']); 

}

function getIpAddrDetailsByip ($ip) 
{
    global $db;                                                                      # get variables from config file
    /* set query, open db connection and fetch results */
    $query    = 'select * from `ipaddresses` where `ip_addr` = "'. $ip .'";';
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);  

	#print ("<div class='alert alert-info'>Query:$query</div>");
    /* execute */
    try { $details = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    
    //we only fetch 1 field
    $details  = $details[0];
	//change IP address formatting to dotted(long)
	$details['ip_addr'] = Transform2long( $details['ip_addr'] ); 
	   
    /* return result */
    return($details);
}

function countIPaddresses () 
{
    global $db;                                                                      # get variables from config file
    $database    = new database($db['host'], $db['user'], $db['pass'], $db['name']); 
    
    /* get all vlans, descriptions and subnets */
    $query = 'SELECT switch,count(switch) as `count` FROM `ipaddresses` group by switch;'; 

    /* execute */
    try { $ip = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    }   
    
	foreach($ip as $r) {
		$count[$r['switch']] = $r['count'];
	}
    /* return vlans */
    return $count;
}

/**
 * Search VLANS
 */
function searchDevices ($searchterm)
{


    global $database;                                                                      

    # get custom device fields
    $myFields = getCustomFields('devices');
    $custom  = '';

    if(sizeof($myFields) > 0) {
		/* set inserts for custom */
		foreach($myFields as $myField) {			
			$custom  .= ' or `'.$myField['name'].'` like "%'.$searchterm.'%" ';
		}
	}
    /* set query */    
	$query = 'select * from `devices` LEFT JOIN `deviceTypes` ON `devices`.`type` = `deviceTypes`.`tid` where `hostname` like "%'. $searchterm .'%" or `ip_addr` like "%'. $searchterm .'%" or `vendor` like "%'. $searchterm .'%"';
	$query .= ' or `model` like "%'. $searchterm .'%" or `version` like "%'. $searchterm .'%" or `description` like "%'. $searchterm .'%"  or `tname` like "%'. $searchterm .'%" '.$custom.';';
    /* execute */
	#print ("<div class='alert alert-danger'>"._('Error').": $query</div>");
    try { $search = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    
    /* return result */
    return $search;
}

function getAllReservedIPrequests($days)
{
    global $db;                                                                      # get variables from config file
    /* set query, open db connection and fetch results */
    $query    = 'select *,DATE_ADD(editDate,INTERVAL 60 DAY) as endDate from requests left join ipaddresses on ipaddresses.requestId = requests.id where processed = 1 and ipaddresses.state = 2 and DATEDIFF(now(),editDate)>'.$days.';';
    $database = new database($db['host'], $db['user'], $db['pass'], $db['name']);  

	#print ("<div class='alert alert-danger'>".$query."</div>");
    /* execute */
    try { $activeRequests = $database->getArray( $query ); }
    catch (Exception $e) { 
        $error =  $e->getMessage(); 
        print ("<div class='alert alert-danger'>"._('Error').": $error</div>");
        return false;
    } 
    
    return $activeRequests;
}


?>