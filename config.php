<?php

/*	database connection details
 ******************************/
$db['host'] = "localhost";
$db['user'] = "root";
$db['pass'] = "";
$db['name'] = "phpipam";

/* glpi database connection details
LEAVE EMPTY IF NOT USING GLPI
************************************/
$db['glpi_host'] = "10.55.68.4";
$db['glpi_user'] = "phpipam";
$db['glpi_pass'] = "";
$db['glpi_name'] = "glpi";

/* glpi url
e.g. www.myglpi.com
192.168.1.1
************************************/
$glpiurl = '';

/* glpi discovery subnets
$glpisubnets = '192.168.132.0/24,127.0.0.1/30'
separate the subnets with a coma
**************************************************/
$glpisubnets = '';

/* glpi database connection details
LEAVE EMPTY IF NOT USING GLPI
************************************/
$db['nedi_host'] = "10.55.68.4";
$db['nedi_user'] = "phpipam";
$db['nedi_pass'] = "";
$db['nedi_name'] = "nedi";

/* glpi url
e.g. www.myglpi.com
192.168.1.1
************************************/
#$nediurl = '';

/* glpi discovery subnets
$glpisubnets = '192.168.132.0/24,127.0.0.1/30'
separate the subnets with a coma
**************************************************/
#$nedisubnets = '';

/**
 * php debugging on/off
 *
 * true  = SHOW all php errors
 * false = HIDE all php errors
 ******************************/
$debugging = true;

/**	
 *	BASE definition if phpipam 
 * 	is not in root directory (e.g. /phpipam/)
 *
 *  Also change 
 *	RewriteBase / in .htaccess
 ******************************/
define('BASE', "/phpipam/");

?>
