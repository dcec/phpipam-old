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


function getDevicesAddressFromGlpi ($min = NULL,$max = NULL,$index)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);  

   # $query    = 'select devices.device,ifip,ifmac,interfaces.ifname,description,INET_NTOA(ifip) as ip,lastdis as lastSeen from devices left join interfaces on devices.device=interfaces.device left join networks on devices.device=networks.device and interfaces.ifname=networks.ifname where ifip > "'. $min .'" and ifip < "'. $max .'" order by ifip;';
	if($min and $max){
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ip_src,last_ocs_conn from V_COMPUTER_NETWORKPORTS as n left join glpi_plugin_ocsinventoryng_ocslinks as l on l.computers_id = n.id where INET_ATON(ipaddress) >= "'. $min .'" and INET_ATON(ipaddress) <= "'. $max .'";';
	}else{
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ip_src,last_ocs_conn from V_COMPUTER_NETWORKPORTS as n left join glpi_plugin_ocsinventoryng_ocslinks as l on l.computers_id = n.id where INET_ATON(ipaddress) > "0" group by hostname;';
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
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ipaddress as ip_src from V_NETWORK_NETWORKPORTS as n where sorgente = "GLPI" and INET_ATON(ipaddress) >= "'. $min .'" and INET_ATON(ipaddress) <= "'. $max .'";';
	}else{
		$query    = 'select n.*,INET_ATON(ipaddress) as ifip,ipaddress as ip_src from V_NETWORK_NETWORKPORTS as n where sorgente = "GLPI" and INET_ATON(ipaddress) > "0" group by hostname;';
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
		$devices[$r[$index]]['type'] = $type[$r['computertype']];
	}
	
	foreach($resultc as $r) {
		$devices[$r[$index]]=$r;
		$devices[$r[$index]]['type'] = $type[$r['computertype']];
	}
	
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}

function getSubnetFromGlpi ($address,$netmask)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['glpi_host'], $db['glpi_user'], $db['glpi_pass'], $db['glpi_name']);  

   # $query    = 'select devices.device,ifip,ifmac,interfaces.ifname,description,INET_NTOA(ifip) as ip,lastdis as lastSeen from devices left join interfaces on devices.device=interfaces.device left join networks on devices.device=networks.device and interfaces.ifname=networks.ifname where ifip > "'. $min .'" and ifip < "'. $max .'" order by ifip;';
    $query    = 'select * from glpi_ipnetworks where address = "'. $address .'" and netmask = "'. $netmask .'";';
    /* execute */
	#print ("<div class='alert alert-info'>Query:$query</div>");
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
	#print ("<div class='alert alert-info'>Query:$query $k</div>\n");
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
	
	#"insert into `devices` (`hostname`,`ip_addr`, `type`, `model`, `description`, `sections`) values ('$ip[device]', '".Transform2long($ip[ip_addr])."', '$ip[type]', '$ip[model]', '$ip[description]', '$sections'); ";
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
	#print ("<div class='alert alert-info'>Query:$query</div>\n");
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

/**
 * Get device from nedi index
 */

function getDevicesFromNedi ($index,$where = NULL,$limit = true)
{
	global $db;                                                                      # get variables from config file
	$database = new database($db['nedi_host'], $db['nedi_user'], $db['nedi_pass'], $db['nedi_name']);  

    $query    = 'select device,devip as ip_addr,type as model,description,icon from devices'; 
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
		$vendor = DevVendor("",substr($r['icon'],2,1));
		$devices[$r[$index]]['vendor'] = $vendor[0];
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
	#print ("<div class='alert alert-info'>Query:$query</div>\n");
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
		#if (!array_key_exists($r['ifip'], $devices)) {$devices[$r['ifip']]=array();}
		#if (!array_key_exists($r['ifip'], $devices)){$devices[$r['ifip']]=array();}
		#if (!array_key_exists('farm', $devices[$r['ifip']])){$devices[$r['ifip']]['farm']=array();}
		if (!array_key_exists($r['ifip'], $devices) || !array_key_exists('farm', $devices[$r['ifip']]) || !array_key_exists($r['farm'], $devices[$r['ifip']]['farm'])) {
			$devices[$r['ifip']]['farm'][$r['farm']]=$r;
			if($r['rsip'] != ""){$devices[$r['ifip']]['farm'][$r['farm']]['bfarm'][$r['rsip']]=$r;}
		}else{
			if($r['rsip'] != ""){$devices[$r['ifip']]['farm'][$r['farm']]['bfarm'][$r['rsip']]=$r;}
		}
		if ($r['rsip'] != "" && (!array_key_exists($r['rsip'], $devices) || !array_key_exists('bfarm', $devices[$r['ifip']]) || !array_key_exists($r['farm'], $devices[$r['ifip']]['bfarm']))){
			$devices[$r['rsip']]['bfarm'][$r['farm']]=$r;
		}
		
		#$devices[$r['ifip']]=$r;
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
	
	$query    = 'select *,INET_NTOA(nip) as n_ip,INET_NTOA(mip) as m_ip from nats where ( nip > "'. $min .'" and nip < "'. $max .'" ) or ( mip > "'. $min .'" and mip < "'. $max .'" );';

    /* execute */
	#print ("<div class='alert alert-info'>Query:$query</div>\n");
	
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
		#if($r['type'] == "VIP" && $devices[$r['nip']]['iptype'] == "nip"){
		#	$devices[$r['nip']]['mip'][$r['mip']]=$r;
		#}
	}
	
    /* return true, else false */
    if (!$devices) 	{ return array(); }
    else 			{ return $devices; }	
}

/**
 * Get vlans from nedi index
 */
 
function getVansFromNedi ($id = '',$device = '',$name = '')
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
	if (!$device && !$name) {$query .= ' limit 1';}
	$query .= ';';
	
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
	
	foreach($result as $r) {
		$vlans[$r['vlanid']]=$r;	
	}
	
    /* return true, else false */
    if (!$vlans) 	{ return false; }
    else 			{ return $vlans; }	
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
	#print ("<div class='alert alert-info'>Query:$query</div>");
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
				#$subnet=Transform2decimal(IPSubnetCalc(2, Transform2long($r[ifip])."/".$r['prefix']));
				$subnet_det = calculateIpCalcResult($cidr);
				$subnet = Transform2decimal($subnet_det['Network']);
				$networks[$subnet]=$r;
				$networks[$subnet]['subnet']=$subnet;
			}else{
				$networks[$r[$index]]=$r;
				#$networks[$r[$index]]['subnet']=Transform2decimal(IPSubnetCalc(2, Transform2long($r[ifip])."/".$r['prefix']));
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
				if(strlen($ip[$myField['name']])==0) {
					#$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					#$myFieldsInsert['values'] .= ", ''";				
				} else {
					$myFieldsInsert['query']  .= ', `'. $myField['name'] .'`';
					$myFieldsInsert['values'] .= ", '". $ip[$myField['name']] . "'";
				}	
			}
			#print_r($myFieldsInsert);
		}
		$query_string = "insert into `ipaddresses` (`ip_addr`,`subnetId`,`description`,`dns_name`,`mac`,`switch`,`port`,`lastSeen` ". $myFieldsInsert['query'] .") ";
		$query_string .= "values ";
		$query_string .= "('".transform2decimal($ip['ip_addr'])."', '$subnetId', '$ip[description]', '$ip[dns_name]', '$ip[mac]', '$ip[switch]', '$ip[ifname]', NOW() ". $myFieldsInsert['values'] .");";
	    #$query[] = "insert into `ipaddresses` (`ip_addr`,`subnetId`,`description`,`dns_name`,`mac`,`switch`,`port`,`lastSeen`) values ('".transform2decimal($ip['ip_addr'])."', '$subnetId', '$ip[description]', '$ip[dns_name]', '$ip[mac]', '$ip[switch]', '$ip[ifname]', NOW())";
#$query[] = $query_string . ";";
#		$query = implode("\n", $query);
		#print ("<div class='alert alert-info'>Query: $query_string</div>");
		if($GLOBALS['debug']==1) {print ("<div class='alert alert-info'>Query: $query_string</div>");print "<pre> result";print_r($query_string);print "</pre>";}
		#print_r(htmlspecialchars ($query));
		#$log = prepareLogFromArray ($ip);
		try { $database->executeQuery($query_string); }
		catch (Exception $e) { 
			$error =  $e->getMessage(); 
			#print "<div class='alert alert-danger'>$error</div>";
			#updateLogTable ('Failed to add new IP request', $log."\n".$error, 2);
			continue;
		}
		#updateLogTable ('New IP request added', $log, 1);
		
	}
    # glue
    

    # execute query
	
    # default ok
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
	#print ("<div class='alert alert-info'>Query:$query $k</div>\n");
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
    
    # set queries
  #  foreach($res as $vlan) {
    	//escape strings
    	#$ip['description'] = mysqli_real_escape_string($database, $ip['description']);
    			
	    $query[] = "insert into `vlans` (`name`,`number`, `description`, `switch`) values ('$name', '$number', '$description', '$switch'); ";
    #}
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

function IPSubnetCalc($intFn, $IPandMaskInput){
        /* 
        ' PROGRAM:
        ' IP Subnet Calculator Originally by Marcus Mansfield
        ' Converted into php and modified by frotthie
        ' 
        ' CHANGES:
        '
        ' INPUTS:
        ' Input Expected xx.xx.xx.xx/yy
        ' Where xx = decimal IPv4 Octet
        '       yy = BitMask Value ( 0 - 32 ex.31 )
        '
        '
        ' FUNCTIONS:
        ' 1 - Return Subnet Mask in dotted decimal
        ' 2 - Return Subnet Address in Dot. Dec.
        ' 3 - Return Broadcast Address in Dot. Dec.
        ' 4 - Return Lowest Client IP in Dot. Dec.
        ' 5 - Return Highest Client IP in Dot. Dec.
        ' 6 - Return Total Number of Host in the Network.
        ' 7 - Inverted Subnet Mask Dot. Dec.
        ' 8 - Returns network part after the input /
        */
        $IPAddress; $BitMask;
        $IPOctet1; $IPOctet2; $IPOctet3; $IPOctet4;
        $MaskOctet1; $MaskOctet2; $MaskOctet3; $MaskOctet4;
        $Dot1; $Dot2; $Dot3; $Slash1;
        $$IPNetwork1BIN; $IPNetwork2BIN; $IPNetwork3BIN; $IPNetwork4BIN;
    
        $Dot1 = strpos($IPandMaskInput, ".", 1);
        $Dot2 = strpos($IPandMaskInput, ".", ($Dot1 + 1));
        $Dot3 = strpos($IPandMaskInput, ".", ($Dot2 + 1));
        $Slash1 = strpos($IPandMaskInput, "/", 1);
            
        //Check Basic Format
        If ($Dot1 == 0 || $Dot2 == 0 || $Dot3 == 0 || $Slash1 == 0 ){
            $IPSubnetCalc = "Incorrect Format";
            return;
            //exit;
        }
        
        //Split IP into 8bit Octets
        $IPOctet1 = substr($IPandMaskInput, 0, $Dot1);
        $IPOctet2 = substr($IPandMaskInput, $Dot1 + 1, $Dot2 - $Dot1 - 1);
        $IPOctet3 = substr($IPandMaskInput, $Dot2 + 1, $Dot3 - $Dot2 - 1);
        $IPOctet4 = substr($IPandMaskInput, $Dot3 + 1, $Slash1 - $Dot3 - 1);
        $BitMask =  substr($IPandMaskInput, $Slash1 + 1);
        
        If ( ($IPOctet1 > 255) || ($IPOctet2 > 255) || ($IPOctet3 > 255) || ($IPOctet4 > 255) ){
            return "Value Error, Octet > 255";
        }
        If ( ($IPOctet1 < 0) || ($IPOctet2 < 0) || ($IPOctet3 < 0) || ($IPOctet4 < 0) ){
            return "Value Error, Octet < 0";
        }
        If ( ($BitMask < 8) || ($BitMask > 32) || ($BitMask == 31) ){
            return "Bitmask Error : Range 8 - 32, Excl. 31";
        }
        
        switch ($intFn){
            //' 1 - Return Subnet Mask in dotted decimal
            case 1:
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . "1";
                    } else {
                        $BuildBIN = $BuildBIN . "0";
                    }
                }
                //$BuildBIN = "1234567.1234567.1234567.1234567.";
                $MaskOctet1 = bin2dec(substr($BuildBIN, 0, 8));
                $MaskOctet2 = bin2dec(substr($BuildBIN, 8, 8));
                $MaskOctet3 = bin2dec(substr($BuildBIN, 16, 8));
                $MaskOctet4 = bin2dec(substr($BuildBIN, 24, 8));
                
                return $MaskOctet1 . "." . $MaskOctet2 . "." . $MaskOctet3 . "." . $MaskOctet4;
            break;
            //Subnet Dot. Dec.
            case 2:
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . "1";
                    } else {
                        $BuildBIN = $BuildBIN . "0";
                    }
                }
                //$BuildBIN = "1234567.1234567.1234567.1234567.";
                $MaskOctet1BIN = substr($BuildBIN, 0, 8);
                $MaskOctet2BIN = substr($BuildBIN, 8, 8);
                $MaskOctet3BIN = substr($BuildBIN, 16, 8);
                $MaskOctet4BIN = substr($BuildBIN, 24, 8);
                
                $IPOctet1BIN = dec2bin($IPOctet1, 8);
                $IPOctet2BIN = dec2bin($IPOctet2, 8);
                $IPOctet3BIN = dec2bin($IPOctet3, 8);
                $IPOctet4BIN = dec2bin($IPOctet4, 8);
                
                 //AND Binary Expressions.
                $IPNetwork1BIN = "";
                $IPNetwork2BIN = "";
                $IPNetwork3BIN = "";
                $IPNetwork4BIN = "";
                
                For ($iCounter = 0 ;$iCounter < 8; $iCounter ++){
                    If (substr($MaskOctet1BIN, $iCounter, 1) && substr($IPOctet1BIN, $iCounter, 1)) {
                        $IPNetwork1BIN = $IPNetwork1BIN . "1";
                    } else {
                        $IPNetwork1BIN = $IPNetwork1BIN . "0";
                    }
                    
                    If (substr($MaskOctet2BIN, $iCounter, 1) && substr($IPOctet2BIN, $iCounter, 1)) {
                        $IPNetwork2BIN = $IPNetwork2BIN . "1";
                    } else {
                        $IPNetwork2BIN = $IPNetwork2BIN . "0";
                    }
                    
                    If (substr($MaskOctet3BIN, $iCounter, 1) && substr($IPOctet3BIN, $iCounter, 1)) {
                        $IPNetwork3BIN = $IPNetwork3BIN . "1";
                    } else {
                        $IPNetwork3BIN = $IPNetwork3BIN . "0";
                    }
                    
                    If (substr($MaskOctet4BIN, $iCounter, 1) && substr($IPOctet4BIN, $iCounter, 1)) {
                        $IPNetwork4BIN = $IPNetwork4BIN . "1";
                    } else {
                        $IPNetwork4BIN = $IPNetwork4BIN . "0";
                    }
                }
                
                $MaskOctet1 = bin2dec($IPNetwork1BIN);
                $MaskOctet2 = bin2dec($IPNetwork2BIN);
                $MaskOctet3 = bin2dec($IPNetwork3BIN);
                $MaskOctet4 = bin2dec($IPNetwork4BIN);
                return $MaskOctet1 . "." . $MaskOctet2 . "." . $MaskOctet3 . "." . $MaskOctet4;
            break;
            //Broadcast Dot. Dec.
            case 3:
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . "1";
                    } else {
                        $BuildBIN = $BuildBIN . "0";
                    }
                }
                //$BuildBIN = "1234567.1234567.1234567.1234567.";
                $MaskOctet1BIN = substr($BuildBIN, 0, 8);
                $MaskOctet2BIN = substr($BuildBIN, 8, 8);
                $MaskOctet3BIN = substr($BuildBIN, 16, 8);
                $MaskOctet4BIN = substr($BuildBIN, 24, 8);
                
                $IPOctet1BIN = dec2bin($IPOctet1, 8);
                $IPOctet2BIN = dec2bin($IPOctet2, 8);
                $IPOctet3BIN = dec2bin($IPOctet3, 8);
                $IPOctet4BIN = dec2bin($IPOctet4, 8);
                
                //Create Full IP as Binary
                $IPFullBIN = $IPOctet1BIN . $IPOctet2BIN . $IPOctet3BIN . $IPOctet4BIN;
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . substr($IPFullBIN, $iCounter, 1);
                    } else {
                        $BuildBIN = $BuildBIN . "1";
                    }
                }
                
                $MaskOctet1 = bin2dec(substr($BuildBIN, 0, 8));
                $MaskOctet2 = bin2dec(substr($BuildBIN, 8, 8));
                $MaskOctet3 = bin2dec(substr($BuildBIN, 16, 8));
                $MaskOctet4 = bin2dec(substr($BuildBIN, 24, 8));
                
                return $MaskOctet1 . "." . $MaskOctet2 . "." . $MaskOctet3 . "." . $MaskOctet4;
            break;
            //Low IP Dot Dec
            Case 4 :
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . "1";
                    } else {
                        $BuildBIN = $BuildBIN . "0";
                    }
                }
                //$BuildBIN = "1234567.1234567.1234567.1234567.";
                $MaskOctet1BIN = substr($BuildBIN, 0, 8);
                $MaskOctet2BIN = substr($BuildBIN, 8, 8);
                $MaskOctet3BIN = substr($BuildBIN, 16, 8);
                $MaskOctet4BIN = substr($BuildBIN, 24, 8);
                
                $IPOctet1BIN = dec2bin($IPOctet1, 8);
                $IPOctet2BIN = dec2bin($IPOctet2, 8);
                $IPOctet3BIN = dec2bin($IPOctet3, 8);
                $IPOctet4BIN = dec2bin($IPOctet4, 8);
                
                //AND Binary Expressions.
                
                For ($iCounter = 0 ;$iCounter < 8; $iCounter ++){
                    If (substr($MaskOctet1BIN, $iCounter, 1) && substr($IPOctet1BIN, $iCounter, 1)) {
                        $IPNetwork1BIN = $IPNetwork1BIN . "1";
                    } else {
                        $IPNetwork1BIN = $IPNetwork1BIN . "0";
                    }
                    
                    If (substr($MaskOctet2BIN, $iCounter, 1) && substr($IPOctet2BIN, $iCounter, 1)) {
                        $IPNetwork2BIN = $IPNetwork2BIN . "1";
                    } else {
                        $IPNetwork2BIN = $IPNetwork2BIN . "0";
                    }
                    
                    If (substr($MaskOctet3BIN, $iCounter, 1) && substr($IPOctet3BIN, $iCounter, 1)) {
                        $IPNetwork3BIN = $IPNetwork3BIN . "1";
                    } else {
                        $IPNetwork3BIN = $IPNetwork3BIN . "0";
                    }
                    
                    If (substr($MaskOctet4BIN, $iCounter, 1) && substr($IPOctet4BIN, $iCounter, 1)) {
                        $IPNetwork4BIN = $IPNetwork4BIN . "1";
                    } else {
                        If ($iCounter == 7 ){
                            $IPNetwork4BIN = $IPNetwork4BIN . "1";
                        }else{
                            $IPNetwork4BIN = $IPNetwork4BIN . "0";
                        }
                    }
                }
                $MaskOctet1 = bin2dec($IPNetwork1BIN);
                $MaskOctet2 = bin2dec($IPNetwork2BIN);
                $MaskOctet3 = bin2dec($IPNetwork3BIN);
                $MaskOctet4 = bin2dec($IPNetwork4BIN);
                return $MaskOctet1 . "." . $MaskOctet2 . "." . $MaskOctet3 . "." . $MaskOctet4;
            break;
            //5 - Return Highest Client IP in Dot. Dec.
            Case 5 :
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . "1";
                    } else {
                        $BuildBIN = $BuildBIN . "0";
                    }
                }
                $MaskOctet1BIN = substr($BuildBIN, 0, 8);
                $MaskOctet2BIN = substr($BuildBIN, 8, 8);
                $MaskOctet3BIN = substr($BuildBIN, 16, 8);
                $MaskOctet4BIN = substr($BuildBIN, 24, 8);
                
                $IPOctet1BIN = dec2bin($IPOctet1, 8);
                $IPOctet2BIN = dec2bin($IPOctet2, 8);
                $IPOctet3BIN = dec2bin($IPOctet3, 8);
                $IPOctet4BIN = dec2bin($IPOctet4, 8);
                
            //Create Full IP as Binary
                $IPFullBIN = $IPOctet1BIN . $IPOctet2BIN . $IPOctet3BIN . $IPOctet4BIN;
                $BuildBIN = "";
                
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . substr($IPFullBIN, $iCounter, 1);
                    } else {
                        If ($iCounter == 31){
                            $BuildBIN = $BuildBIN . "0";
                        }else {
                            $BuildBIN = $BuildBIN . "1";
                        }
                    }
                }
                
                $MaskOctet1 = bin2dec(substr($BuildBIN, 0, 8));
                $MaskOctet2 = bin2dec(substr($BuildBIN, 8, 8));
                $MaskOctet3 = bin2dec(substr($BuildBIN, 16, 8));
                $MaskOctet4 = bin2dec(substr($BuildBIN, 24, 8));
                
                return $MaskOctet1 . "." . $MaskOctet2 . "." . $MaskOctet3 . "."  . $MaskOctet4;
            break;
            // 6 - Return Total Number of Host in the Network.
            Case 6 :
                $iZeroCounter = 1;
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter > $BitMask){
                        $iZeroCounter = $iZeroCounter + 1;
                    }
                }
                return pow(2,$iZeroCounter) - 2;
            break;
            //Inverted Subnet Mask Dot. Dec.
            Case 7 :
                $BuildBIN = "";
                for($iCounter = 0; $iCounter < 32; $iCounter++){
                    If ($iCounter < $BitMask){
                        $BuildBIN = $BuildBIN . "1";
                    } else {
                        $BuildBIN = $BuildBIN . "0";
                    }
                }
                $MaskOctet1 = bin2dec(substr($BuildBIN, 0, 8));
                $MaskOctet2 = bin2dec(substr($BuildBIN, 8, 8));
                $MaskOctet3 = bin2dec(substr($BuildBIN, 16, 8));
                $MaskOctet4 = bin2dec(substr($BuildBIN, 24, 8));
                return (255 - $MaskOctet1) . "." . (255 - $MaskOctet2) . "." . (255 - $MaskOctet3) . "."  . (255 - $MaskOctet4);
            break;
            //Returns network part after the input /
            Case 8 :
                    $temp = str_replace("/", "", substr($IPandMaskInput, $Slash1));
                    return $temp;
            break;
        }
        //echo "$Dot1, $Dot2, $Dot3, $Slash1";
        //echo "$IPOctet1, $IPOctet2, $IPOctet3, $IPOctet4, $BitMask ";
        
}
function dec2bin($dec, $len=0){
        $retVal = -1;
        if($dec >= 0 && $dec <= 255){
            for($b='',$r=$dec;$r>1;){
                $n = floor($r/2); $b = ($r-$n*2).$b; $r = $n; // $r%2 is inaccurate when using bigger values (like 11.435.168.214)!
            }
            $retVal=($r%2).$b;
            if(strlen($retVal) < $len){
                $hulp = "";
                for($i=strlen($retVal); $i<$len; $i++){
                    $hulp .= "0";
                }
                $retVal = $hulp . $retVal;
            }
        }
        return $retVal;
}
function bin2dec($binary, $output = false) {
        $N = 0;
        $o = "";
        list ( $rhs, $lhs ) = explode ( ".", $binary );
        $rhs = strrev ( $rhs );
        for($i = 0; $i < strlen ( $rhs ); $i ++) {
            $d = $rhs [$i] * pow ( 2, $i );
            $N = $d + $N;
            $o = ($d == 0) ? $o : $o . $d . " + ";
        }
        
        for($i = 0; $i < strlen ( $lhs ); $i ++) {
            $d = $lhs [$i] * pow ( 2, - ($i + 1) );
            $N = $d + $N;
            $o = ($d == 0) ? $o : $o . $d . " + ";
        }
        
        return ($output) ? substr ( $o, 0, - 3 ) . " = " . $N : $N;
}

?>