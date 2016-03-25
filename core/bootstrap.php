<?php
	ini_set("log_errors","false");

	// Set some config vars automatically and setup some globals.
	$domain = rtrim($bigtree["config"]["domain"],"/");
	// This is set now in index.php but is left for backwards compatibility.
	$server_root = isset($server_root) ? $server_root : str_replace("core/bootstrap.php","",strtr(__FILE__, "\\", "/"));
	$site_root = $server_root."site/";
	$www_root = $bigtree["config"]["www_root"];
	$admin_root = $bigtree["config"]["admin_root"];
	$static_root = isset($bigtree["config"]["static_root"]) ? $bigtree["config"]["static_root"] : $www_root;
	$secure_root = str_replace("http://","https://",$www_root);
	
	define("WWW_ROOT",$www_root);
	define("STATIC_ROOT",$static_root);
	define("SECURE_ROOT",$secure_root);
	define("DOMAIN",$domain);
	define("SERVER_ROOT",$server_root);
	define("SITE_ROOT",$site_root);
	define("ADMIN_ROOT",$admin_root);

	// Adjust server parameters in case we're running on CloudFlare
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
		$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}

	// Set version
	include SERVER_ROOT."core/version.php";

	// Include required utility functions
	if (file_exists(SERVER_ROOT."custom/inc/bigtree/utils.php")) {
		include SERVER_ROOT."custom/inc/bigtree/utils.php";
	} else {
		include SERVER_ROOT."core/inc/bigtree/utils.php";
	}
	
	// Connect to MySQL and include the shorterner functions
	include BigTree::path("inc/bigtree/classes/SQL.php");
	include BigTree::path("inc/bigtree/compat/sql.php");
	
	// Setup our connections as disconnected by default.
	$bigtree["mysql_read_connection"] = "disconnected";
	$bigtree["mysql_write_connection"] = "disconnected";
	
	// Load Up BigTree!
	include BigTree::path("inc/bigtree/cms.php");
	if (defined("BIGTREE_CUSTOM_BASE_CLASS") && BIGTREE_CUSTOM_BASE_CLASS) {
		include SITE_ROOT.BIGTREE_CUSTOM_BASE_CLASS_PATH;
		eval("class BigTreeCMS extends ".BIGTREE_CUSTOM_BASE_CLASS." {}");
	} else {
		class BigTreeCMS extends BigTreeCMSBase {};
	}

	// Initialize DB instance
	BigTreeCMS::$DB = $db = new BigTree\SQL;

	// Setup admin class if it's custom, but don't instantiate the $admin var.
	if (defined("BIGTREE_CUSTOM_ADMIN_CLASS") && BIGTREE_CUSTOM_ADMIN_CLASS) {
		include_once SITE_ROOT.BIGTREE_CUSTOM_ADMIN_CLASS_PATH;
		eval("class BigTreeAdmin extends ".BIGTREE_CUSTOM_ADMIN_CLASS." {}");
	} else {
		class BigTreeAdmin extends BigTreeAdminBase {};
	}

	// Give BigTreeAdmin a copy of the DB
	BigTreeAdmin::$DB = $db;

	// Bootstrap CMS instance
	$cms = new BigTreeCMS;
