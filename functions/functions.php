<?php

/* @config file ------------------- */
require_once( dirname(__FILE__) . '/../config.php' );

/* @database functions ------------------- */
require_once( dirname(__FILE__) . '/dbfunctions.php' );

/* @debugging functions ------------------- */
ini_set('display_errors', 1);
if (!$debugging) { error_reporting(E_ERROR ^ E_WARNING); }
else			 { error_reporting(E_ALL ^ E_NOTICE); }

/* set caching array to store vlans, sections etc */
$cache = array();

/**
 * Translations
 *
 * 	recode .po to .mo > msgfmt env_cp.po -o env_cp.mo
 *	lang codes locale -a
 */

if(!isset($_SESSION)) { 								//fix for ajax-loaded windows
	/* set cookie parameters for max lifetime */
	/*
	ini_set('session.gc_maxlifetime', '86400');
	ini_set('session.save_path', '/tmp/php_sessions/');
	*/
	session_start();
}
 
/* Check if lang is set */
if(isset($_SESSION['ipamlanguage'])) {
	if(strlen($_SESSION['ipamlanguage'])>0) 	{ 
		putenv("LC_ALL=$_SESSION[ipamlanguage]");
		setlocale(LC_ALL, $_SESSION['ipamlanguage']);		// set language		
		bindtextdomain("phpipam", "./functions/locale");	// Specify location of translation tables
		textdomain("phpipam");								// Choose domain
	}	
}

/* open persistent DB connection */
$database = new database($db['host'], $db['user'], $db['pass'], $db['name'], NULL, false);

/* set latest version */
define("VERSION", "1.02");									//version changes if database structure changes
/* set latest revision */
define("REVISION", "005");									//revision always changes, verision only if database structure changes
/* set last possible upgrade */
define("LAST_POSSIBLE", "0.9");								//minimum required version to be able to upgrade


/* @general functions ------------------- */
include_once('functions-common.php');

/* @network functions ------------------- */
include_once('functions-network.php');

/* @tools functions --------------------- */
include_once('functions-tools.php');

/* @admin functions --------------------- */
include_once('functions-admin.php');

/* @upgrade functions ------------------- */
include_once('functions-upgrade.php');

/* @admin functions --------------------- */
include_once('functions-external.php');

?>