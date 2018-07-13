<?php
	// Updating BigTree can make a lot of queries happen which can cause memory issues with debug on.
	define("BIGTREE_NO_QUERY_LOG", true);

	$current_revision = $cms->getSetting("bigtree-internal-revision");

	while ($current_revision < BIGTREE_REVISION) {
		$current_revision++;
		if (function_exists("_local_bigtree_update_".$current_revision)) {
			call_user_func("_local_bigtree_update_$current_revision");
		}
	}

	$admin->updateSettingValue("bigtree-internal-revision",BIGTREE_REVISION);
?>
<div class="container">
	<section>
		<p>BigTree has been updated to <?=BIGTREE_VERSION?>.</p>
	</section>
</div>
<?php
	// BigTree 4.0b5 update -- REVISION 1
	function _local_bigtree_update_1() {
		global $cms,$admin;

		// Update settings to make the value LONGTEXT
		sqlquery("ALTER TABLE `bigtree_settings` CHANGE `value` `value` LONGTEXT");

		// Drop the css/javascript columns from bigtree_module_forms and add preprocess
		sqlquery("ALTER TABLE `bigtree_module_forms` ADD COLUMN `preprocess` varchar(255) NOT NULL AFTER `title`, DROP COLUMN `javascript`, DROP COLUMN `css`");

		// Add the "trunk" column to bigtree_pages
		sqlquery("ALTER TABLE `bigtree_pages` ADD COLUMN `trunk` char(2) NOT NULL AFTER `id`");
		sqlquery("UPDATE `bigtree_pages` SET `trunk` = 'on' WHERE id = '0'");

		// Move Google Analytics information into a single setting
		$ga_email = $cms->getSetting("bigtree-internal-google-analytics-email");
		$ga_password = $cms->getSetting("bigtree-internal-google-analytics-password");
		$ga_profile = $cms->getSetting("bigtree-internal-google-analytics-profile");

		$admin->createSetting(array(
			"id" => "bigtree-internal-google-analytics",
			"system" => "on",
			"encrypted" => "on"
		));
		$admin->updateSettingValue("bigtree-internal-google-analytics",array(
			"email" => $ga_email,
			"password" => $ga_password,
			"profile" => $ga_profile
		));


		// Update the upload service setting to be encrypted.
		$admin->updateSetting("bigtree-internal-upload-service",array(
			"id" => "bigtree-internal-upload-service",
			"system" => "on",
			"encrypted" => "on"
		));
		$us = $cms->getSetting("bigtree-internal-upload-service");

		// Move Rackspace into the main upload service
		$rs_containers = $cms->getSetting("bigtree-internal-rackspace-containers");
		$rs_keys = $cms->getSetting("bigtree-internal-rackspace-containers");

		$us["rackspace"] = array(
			"containers" => $rs_containers,
			"keys" => $rs_keys
		);

		// Move Amazon S3 into the main upload service
		$s3_buckets = $cms->getSetting("bigtree-internal-s3-buckets");
		$s3_keys = $cms->getSetting("bigtree-internal-s3-keys");

		$us["s3"] = array(
			"buckets" => $s3_buckets,
			"keys" => $s3_keys
		);

		// Update the upload service value.
		$admin->updateSettingValue("bigtree-internal-upload-service",$us);

		// Create the revision counter
		$admin->createSetting(array(
			"id" => "bigtree-internal-revision",
			"system" => "on"
		));

		// Delete all the old settings.
		sqlquery("DELETE FROM bigtree_settings WHERE id = 'bigtree-internal-google-analytics-email' OR id = 'bigtree-internal-google-analytics-password' OR id = 'bigtree-internal-google-analytics-profile' OR id = 'bigtree-internal-rackspace-keys' OR id = 'bigtree-internal-rackspace-containers' OR id = 'bigtree-internal-s3-buckets' OR id = 'bigtree-internal-s3-keys'");
	}

	// BigTree 4.0b7 update -- REVISION 5
	function _local_bigtree_update_5() {
		// Fixes AES_ENCRYPT not encoding things properly.
		sqlquery("ALTER TABLE `bigtree_settings` CHANGE `value` `value` longblob NOT NULL");

		// Adds the ability to make a field type available for Settings.
		sqlquery("ALTER TABLE `bigtree_field_types` ADD COLUMN `settings` char(2) NOT NULL AFTER `callouts`");

		// Remove uncached.
		sqlquery("ALTER TABLE `bigtree_module_views` DROP COLUMN `uncached`");

		// Adds the ability to set options on a setting.
		sqlquery("ALTER TABLE `bigtree_settings` ADD COLUMN `options` text NOT NULL AFTER `type`");

		// Alter the module view cache table so that it can be used for custom view caching
		sqlquery("ALTER TABLE `bigtree_module_view_cache` CHANGE `view` `view` varchar(255) NOT NULL");
	}

	// BigTree 4.0b7 update -- REVISION 6
	function _local_bigtree_update_6() {
		// Allows null values for module groups and resource folders.
		sqlquery("ALTER TABLE `bigtree_modules` CHANGE `group` `group` int(11) UNSIGNED DEFAULT NULL");
		sqlquery("ALTER TABLE `bigtree_resources` CHANGE `folder` `folder` int(11) UNSIGNED DEFAULT NULL");
	}

	// BigTree 4.0RC1 update -- REVISION 7
	function _local_bigtree_update_7() {
		// Allow forms to set their return view manually.
		sqlquery("ALTER TABLE `bigtree_module_forms` ADD COLUMN `return_view` INT(11) UNSIGNED AFTER `default_position`");
	}
	
	// BigTree 4.0RC2 update -- REVISION 8
	function _local_bigtree_update_8() {
		// Remove image an description columns from modules.
		sqlquery("ALTER TABLE `bigtree_modules` DROP COLUMN `image`");
		sqlquery("ALTER TABLE `bigtree_modules` DROP COLUMN `description`");
		/// Remove locked column from pages.
		sqlquery("ALTER TABLE `bigtree_pages` DROP COLUMN `locked`");
	}
	
	// BigTree 4.0RC2 update -- REVISION 9
	function _local_bigtree_update_9() {
		sqlquery("ALTER TABLE `bigtree_tags_rel` ADD COLUMN `table` VARCHAR(255) NOT NULL AFTER `module`");
		// Figure out the table for all the modules and change the tags to be related to the table instead of the module.
		$q = sqlquery("SELECT * FROM bigtree_modules");
		while ($f = sqlfetch($q)) {
			if (class_exists($f["class"])) {
				$test = new $f["class"];
				$table = sqlescape($test->Table);
				sqlquery("UPDATE `bigtree_tags_rel` SET `table` = '$table' WHERE module = '".$f["id"]."'");
			}
		}
		sqlquery("UPDATE `bigtree_tags_rel` SET `table` = 'bigtree_pages' WHERE module = 0");
		// And drop the module column.
		sqlquery("ALTER TABLE `bigtree_tags_rel` DROP COLUMN `module`");
	}
	
	// BigTree 4.0RC2 update -- REVISION 10
	function _local_bigtree_update_10() {
		sqlquery("ALTER TABLE `bigtree_modules` ADD COLUMN `icon` VARCHAR(255) NOT NULL AFTER `class`");
	}
	
	// BigTree 4.0RC2 update -- REVISION 11
	function _local_bigtree_update_11() {
		// Got rid of the dropdown for Modules.
		sqlquery("ALTER TABLE `bigtree_module_groups` DROP COLUMN `in_nav`");
		// New Analytics stuff requires that we redo everything.
		sqlquery("UPDATE `bigtree_settings` SET value = '' WHERE id = 'bigtree-internal-google-analytics'");
	}

	// BigTree 4.0RC2 update -- REVISION 12
	function _local_bigtree_update_12() {
		// Add the return_url column to bigtree_module_forms.
		sqlquery("ALTER TABLE `bigtree_module_forms` ADD COLUMN `return_url` VARCHAR(255) NOT NULL AFTER `return_view`");
	}

	// BigTree 4.0RC2 update -- REVISION 13
	function _local_bigtree_update_13() {
		// Delete the "package" column from templates.
		sqlquery("ALTER TABLE `bigtree_templates` DROP COLUMN `package`");
	}

	// BigTree 4.0RC2 update -- REVISION 14
	function _local_bigtree_update_14() {
		// Allow NULL as an option for the item_id in bigtree_pending_changes
		sqlquery("ALTER TABLE `bigtree_pending_changes` CHANGE `item_id` `item_id` INT(11) UNSIGNED DEFAULT NULL");
		// Fix anything that had a 0 before as the item_id and wasn't pages.
		sqlquery("UPDATE `bigtree_pending_changes` SET item_id = NULL WHERE item_id = 0 AND `table` != 'bigtree_pages'");
	}

	// BigTree 4.0RC2 update -- REVISION 15
	function _local_bigtree_update_15() {
		// Adds the setting to disable tagging in pages
		global $admin;
		$admin->createSetting(array("id" => "bigtree-internal-disable-page-tagging", "type" => "checkbox", "name" => "Disable Tags in Pages"));
		// Adds a column to module forms to disable tagging.
		sqlquery("ALTER TABLE `bigtree_module_forms` ADD COLUMN `tagging` CHAR(2) NOT NULL AFTER `return_url`");
		// Default to tagging being on since it wasn't an option to turn it off previously.
		sqlquery("UPDATE `bigtree_module_forms` SET `tagging` = 'on'");
	}

	// BigTree 4.0RC2 update -- REVISION 16
	function _local_bigtree_update_16() {
		// Adds a sort column to the view cache
		sqlquery("ALTER TABLE `bigtree_module_view_cache` ADD COLUMN `sort_field` VARCHAR(255) NOT NULL AFTER `group_field`");
		// Force all the views to update their cache.
		sqlquery("TRUNCATE TABLE `bigtree_module_view_cache`");
	}

	// BigTree 4.0RC2 update -- REVISION 18
	function _local_bigtree_update_18() {
		// Adds a sort column to the view cache
		sqlquery("ALTER TABLE `bigtree_module_view_cache` ADD COLUMN `published_gbp_field` TEXT NOT NULL AFTER `gbp_field`");
		// Force all the views to update their cache.
		sqlquery("TRUNCATE TABLE `bigtree_module_view_cache`");
	}

	// BigTree 4.0RC3 update -- REVISION 19
	function _local_bigtree_update_19() {
		// Add the new caches table
		sqlquery("CREATE TABLE `bigtree_caches` (`identifier` varchar(255) NOT NULL DEFAULT '', `key` varchar(255) NOT NULL DEFAULT '', `value` longtext, `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY `identifier` (`identifier`), KEY `key` (`key`), KEY `timestamp` (`timestamp`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
	}

	// BigTree 4.0 update -- REVISION 20
	function _local_bigtree_update_20() {
		// Replace "menu" types with Array of Items
		$options = sqlescape('{"fields":[{"key":"title","title":"Title","type":"text"},{"key":"link","title":"URL (include http://)","type":"text"}]}');
		sqlquery("UPDATE `bigtree_settings` SET `type` = 'array', `options` = '$options' WHERE `type` = 'menu'");

		// Replace "many_to_many" with "many-to-many"
		$mtm_find = sqlescape('"type":"many_to_many"');
		$mtm_replace = sqlescape('"type":"many-to-many"');
		sqlquery("UPDATE `bigtree_module_forms` SET `fields` = REPLACE(`fields`,'$mtm_find','$mtm_replace')");
	}

	// BigTree 4.0 update -- REVISION 21
	function _local_bigtree_update_21() {
		global $bigtree;
		// Fix widths on module view actions
		$q = sqlquery("SELECT * FROM bigtree_module_views");
		while ($f = sqlfetch($q)) {
			$actions = json_decode($f["actions"],true);
			$extra_width = count($actions) * 22; // From 62px to 40px per action.
			$fields = json_decode($f["fields"],true);
			foreach ($fields as &$field) {
				if ($field["width"]) {
					$field["width"] += floor($extra_width / count($fields));
				}
			}
			$fields = sqlescape(json_encode($fields));
			sqlquery("UPDATE bigtree_module_views SET `fields` = '$fields' WHERE id = '".$f["id"]."'");
		}
	}

	// BigTree 4.0.1 update -- REVISION 22
	function _local_bigtree_update_22() {
		global $admin;

		// Go through all views and figure out what kind of data is in each column.
		$q = sqlquery("SELECT id FROM bigtree_module_views");
		while ($f = sqlfetch($q)) {
			$admin->updateModuleViewColumnNumericStatus(BigTreeAutoModule::getView($f["id"]));
		}
	}

	// BigTree 4.1 update -- REVISION 100 (incrementing x00 digit for a .1 release)
	function _local_bigtree_update_100() {
		global $cms;

		// Turn off foreign keys for the update
		sqlquery("SET SESSION foreign_key_checks = 0");

		// MD5 for not duplicating resources that are already uploaded, allocation table for tracking resource usage
		sqlquery("ALTER TABLE `bigtree_resources` ADD COLUMN `md5` VARCHAR(255) NOT NULL AFTER `file`");
		sqlquery("CREATE TABLE `bigtree_resource_allocation` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `module` varchar(255) DEFAULT NULL, `entry` varchar(255) DEFAULT NULL, `resource` int(11) unsigned DEFAULT NULL, `updated_at` datetime NOT NULL, PRIMARY KEY (`id`), KEY `resource` (`resource`), KEY `updated_at` (`updated_at`), CONSTRAINT `bigtree_resource_allocation_ibfk_1` FOREIGN KEY (`resource`) REFERENCES `bigtree_resources` (`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
		// SEO Invisible for passing X-Robots headers
		sqlquery("ALTER TABLE `bigtree_pages` ADD COLUMN `seo_invisible` CHAR(2) NOT NULL AFTER `meta_description`");
		// Per Page Setting
		sqlquery("INSERT INTO `bigtree_settings` (`id`, `value`, `type`, `options`, `name`, `description`, `locked`, `system`, `encrypted`) VALUES ('bigtree-internal-per-page', X'3135', 'text', '', 'Number of Items Per Page', '<p>This should be a numeric amount and controls the number of items per page in areas such as views, settings, users, etc.</p>', 'on', '', '')");
		// Module reports
		sqlquery("CREATE TABLE `bigtree_module_reports` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `module` int(11) unsigned DEFAULT NULL, `title` varchar(255) NOT NULL DEFAULT '', `table` varchar(255) NOT NULL, `type` varchar(255) NOT NULL, `filters` text NOT NULL, `fields` text NOT NULL, `parser` varchar(255) NOT NULL DEFAULT '', `view` int(11) unsigned DEFAULT NULL, PRIMARY KEY (`id`), KEY `view` (`view`), KEY `module` (`module`), CONSTRAINT `bigtree_module_reports_ibfk_2` FOREIGN KEY (`module`) REFERENCES `bigtree_modules` (`id`) ON DELETE CASCADE, CONSTRAINT `bigtree_module_reports_ibfk_1` FOREIGN KEY (`view`) REFERENCES `bigtree_module_views` (`id`) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		sqlquery("ALTER TABLE `bigtree_module_actions` ADD COLUMN `report` int(11) unsigned NULL AFTER `view`");
		// Embeddable Module Forms
		sqlquery("CREATE TABLE `bigtree_module_embeds` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `module` int(11) unsigned DEFAULT NULL, `title` varchar(255) NOT NULL, `preprocess` varchar(255) NOT NULL, `callback` varchar(255) NOT NULL, `table` varchar(255) NOT NULL, `fields` text NOT NULL, `default_position` varchar(255) NOT NULL, `default_pending` char(2) NOT NULL, `css` varchar(255) NOT NULL, `hash` varchar(255) NOT NULL DEFAULT '', `redirect_url` varchar(255) NOT NULL DEFAULT '', `thank_you_message` text NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		// Callout groups
		sqlquery("CREATE TABLE `bigtree_callout_groups` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		sqlquery("ALTER TABLE `bigtree_callouts` ADD COLUMN `group` int(11) unsigned NULL AFTER `position`");
		// Create the extensions table (packages for now, 4.2 prep for extensions)
		sqlquery("CREATE TABLE `bigtree_extensions` (`id` varchar(255) NOT NULL DEFAULT '', `type` varchar(255) DEFAULT NULL, `name` varchar(255) DEFAULT NULL, `version` varchar(255) DEFAULT NULL, `last_updated` datetime DEFAULT NULL, `manifest` LONGTEXT DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		// Get all templates with callouts enabled and provide them with a new resource instead
		$tq = sqlquery("SELECT * FROM bigtree_templates WHERE callouts_enabled = 'on'");
		while ($template = sqlfetch($tq)) {
			$resources = json_decode($template["resources"],true);
			// See if we have a "callouts" resource already
			$found = false;
			foreach ($resources as $r) {
				if ($r["id"] == "callouts") {
					$found = true;
				}
			}
			// If we already have callouts, use 4.0-callouts
			if ($found) {
				$resources[] = array("id" => "4.0-callouts","title" => "Callouts","type" => "callouts");
			} else {
				$resources[] = array("id" => "callouts","title" => "Callouts","type" => "callouts");
			}
			$resources = sqlescape(json_encode($resources));
			sqlquery("UPDATE bigtree_templates SET resources = '$resources' WHERE id = '".sqlescape($template["id"])."'");
	
			// Find pages that use this template
			$q = sqlquery("SELECT * FROM bigtree_pages WHERE template = '".sqlescape($template["id"])."' AND callouts != '[]'");
			while ($f = sqlfetch($q)) {
				$resources = json_decode($f["resources"],true);
				$callouts = json_decode($f["callouts"],true);
				if ($found) {
					$resources["4.0-callouts"] = $callouts;
				} else {
					$resources["callouts"] = $callouts;
				}
				$resources = sqlescape(json_encode($resources));
				sqlquery("UPDATE bigtree_pages SET resources = '$resources' WHERE id = '".$f["id"]."'");
			}
		}

		// Switch storage settings
		$storage_settings = $cms->getSetting("bigtree-internal-storage");
		if ($storage_settings["service"] == "s3") {
			$cloud = new BigTreeCloudStorage;
			$cloud->Settings["amazon"] = array("key" => $storage_settings["s3"]["keys"]["access_key_id"],"secret" => $storage_settings["s3"]["keys"]["secret_access_key"]);
			unset($cloud);
		} elseif ($storage_settings["service"] == "rackspace") {
			$cloud = new BigTreeCloudStorage;
			$cloud->Settings["rackspace"] = array("api_key" => $storage_settings["rackspace"]["keys"]["api_key"],"username" => $storage_settings["rackspace"]["keys"]["username"]);
			unset($cloud);
		}
		sqlquery("DELETE FROM bigtree_settings WHERE id = 'bigtree-internal-storage'");

		// Adjust module relationships better so that we can just delete a module and have everything cascade delete
		sqlquery("ALTER TABLE bigtree_module_forms ADD COLUMN `module` INT(11) unsigned AFTER `id`");
		sqlquery("ALTER TABLE bigtree_module_forms ADD FOREIGN KEY (module) REFERENCES `bigtree_modules` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_module_views ADD COLUMN `module` INT(11) unsigned AFTER `id`");
		sqlquery("ALTER TABLE bigtree_module_views ADD FOREIGN KEY (module) REFERENCES `bigtree_modules` (id) ON DELETE CASCADE");
		// Find all the relevant forms / views / reports and assign them to their proper module.
		$q = sqlquery("SELECT * FROM bigtree_module_actions");
		while ($f = sqlfetch($q)) {
			sqlquery("UPDATE bigtree_module_forms SET module = '".$f["module"]."' WHERE id = '".$f["form"]."'");
			sqlquery("UPDATE bigtree_module_views SET module = '".$f["module"]."' WHERE id = '".$f["view"]."'");
		}

		// Adjust Module Views to use a related form instead of handling suffix tracking
		sqlquery("ALTER TABLE bigtree_module_views ADD COLUMN `related_form` INT(11) unsigned");
		sqlquery("ALTER TABLE bigtree_module_views ADD FOREIGN KEY (related_form) REFERENCES `bigtree_module_forms` (id) ON DELETE SET NULL");

		$q = sqlquery("SELECT * FROM bigtree_module_views WHERE suffix != ''");
		while ($f = sqlfetch($q)) {
			$suffix = sqlescape($f["suffix"]);
			// Find the related form
			$form = sqlfetch(sqlquery("SELECT form FROM bigtree_module_actions WHERE module = '".$f["module"]."' AND (route = 'add-$suffix' OR route = 'edit-$suffix')"));
			if ($form["id"]) {
				sqlquery("UPDATE bigtree_module_views SET related_form = '".$form["id"]."' WHERE id = '".$f["id"]."'");
			}
		}

		// Unused columns
		sqlquery("ALTER TABLE `bigtree_module_forms` DROP COLUMN `positioning`");
		sqlquery("ALTER TABLE `bigtree_templates` DROP COLUMN `image`");
		sqlquery("ALTER TABLE `bigtree_templates` DROP COLUMN `callouts_enabled`");
		sqlquery("ALTER TABLE `bigtree_templates` DROP COLUMN `description`");
		sqlquery("ALTER TABLE `bigtree_pages` DROP COLUMN `callouts`");
		sqlquery("ALTER TABLE `bigtree_page_revisions` DROP COLUMN `callouts`");
		sqlquery("ALTER TABLE `bigtree_module_views` DROP COLUMN `suffix`");

		// Reinstate foreign keys
		sqlquery("SET SESSION foreign_key_checks = 1");
	}

	// BigTree 4.1.1 update -- REVISION 101
	function _local_bigtree_update_101() {
		sqlquery("ALTER TABLE bigtree_caches CHANGE `key` `key` VARCHAR(10000)");
		$storage = new BigTreeStorage;
		if (is_array($storage->Settings->Files)) {
			foreach ($storage->Settings->Files as $file) {
				sqlquery("INSERT INTO bigtree_caches (`identifier`,`key`,`value`) VALUES ('org.bigtreecms.cloudfiles','".sqlescape($file["path"])."','".sqlescape(json_encode($file))."')");
			}
		}
		unset($storage->Settings->Files);
	}

	// BigTree 4.1.1 update -- REVISION 102
	function _local_bigtree_update_102() {
		sqlquery("ALTER TABLE bigtree_field_types ADD COLUMN `use_cases` TEXT NOT NULL AFTER `name`");
		sqlquery("ALTER TABLE bigtree_field_types ADD COLUMN `self_draw` CHAR(2) NULL AFTER `use_cases`");
		$q = sqlquery("SELECT * FROM bigtree_field_types");
		while ($f = sqlfetch($q)) {
			$use_cases = sqlescape(json_encode(array(
				"templates" => $f["pages"],
				"modules" => $f["modules"],
				"callouts" => $f["callouts"],
				"settings" => $f["settings"]
			)));
			sqlquery("UPDATE bigtree_field_types SET use_cases = '$use_cases' WHERE id = '".sqlescape($f["id"])."'");
		}
		sqlquery("ALTER TABLE bigtree_field_types DROP `pages`, DROP `modules`, DROP `callouts`, DROP `settings`");
	}

	// BigTree 4.1.1 update -- REVISION 103
	function _local_bigtree_update_103() {
		global $cms;
		// Converting resource thumbnail sizes to a properly editable feature and naming it better.
		$current = $cms->getSetting("resource-thumbnail-sizes");
		$thumbs = json_decode($current,true);
		$value = array();
		foreach (array_filter((array)$thumbs) as $title => $info) {
			$value[] = array("title" => $title,"prefix" => $info["prefix"],"width" => $info["width"],"height" => $info["height"]);
		}
		sqlquery("INSERT INTO bigtree_settings (`id`,`value`,`type`,`options`,`name`,`locked`) VALUES ('bigtree-file-manager-thumbnail-sizes','".sqlescape(json_encode($value))."','array','".sqlescape('{"fields":[{"key":"title","title":"Title","type":"text"},{"key":"prefix","title":"File Prefix (i.e. thumb_)","type":"text"},{"key":"width","title":"Width","type":"text"},{"key":"height","title":"Height","type":"text"}]}')."','File Manager Thumbnail Sizes','on')");
		sqlquery("DELETE FROM bigtree_settings WHERE id = 'resource-thumbnail-sizes'");
	}

	// BigTree 4.2 update -- REVISION 200
	function _local_bigtree_update_200() {
		global $cms,$admin;

		// Drop unused comments column
		sqlquery("ALTER TABLE bigtree_pending_changes DROP COLUMN `comments`");

		// Add extension columns
		sqlquery("ALTER TABLE bigtree_callouts ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_callouts ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_feeds ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_feeds ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_field_types ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_field_types ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_modules ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_modules ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_module_groups ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_module_groups ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_settings ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_settings ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");
		sqlquery("ALTER TABLE bigtree_templates ADD COLUMN `extension` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_templates ADD FOREIGN KEY (extension) REFERENCES `bigtree_extensions` (id) ON DELETE CASCADE");

		// New publish_hook column, consolidate other hooks into one column
		sqlquery("ALTER TABLE bigtree_pending_changes ADD COLUMN `publish_hook` VARCHAR(255)");
		sqlquery("ALTER TABLE bigtree_module_forms ADD COLUMN `hooks` TEXT");
		sqlquery("ALTER TABLE bigtree_module_embeds ADD COLUMN `hooks` TEXT");
		$q = sqlquery("SELECT * FROM bigtree_module_forms");
		while ($f = sqlfetch($q)) {
			$hooks = array();
			$hooks["pre"] = $f["preprocess"];
			$hooks["post"] = $f["callback"];
			$hooks["publish"] = "";
			sqlquery("UPDATE bigtree_module_forms SET hooks = '".BigTree::json($hooks,true)."' WHERE id = '".$f["id"]."'");
		}
		$q = sqlquery("SELECT * FROM bigtree_module_embeds");
		while ($f = sqlfetch($q)) {
			$hooks = array();
			$hooks["pre"] = $f["preprocess"];
			$hooks["post"] = $f["callback"];
			$hooks["publish"] = "";
			sqlquery("UPDATE bigtree_module_embeds SET hooks = '".BigTree::json($hooks,true)."' WHERE id = '".$f["id"]."'");
		}
		sqlquery("ALTER TABLE bigtree_module_forms DROP COLUMN `preprocess`");
		sqlquery("ALTER TABLE bigtree_module_forms DROP COLUMN `callback`");
		sqlquery("ALTER TABLE bigtree_module_embeds DROP COLUMN `preprocess`");
		sqlquery("ALTER TABLE bigtree_module_embeds DROP COLUMN `callback`");

		// Adjust groups/callouts for multi-support -- first we drop the foreign key
		$table_desc = BigTree::describeTable("bigtree_callouts");
		foreach ($table_desc["foreign_keys"] as $name => $definition) {
			if ($definition["local_columns"][0] === "group") {
				sqlquery("ALTER TABLE bigtree_callouts DROP FOREIGN KEY `$name`");
			}
		}
		// Add the field to the groups
		sqlquery("ALTER TABLE bigtree_callout_groups ADD COLUMN `callouts` TEXT AFTER `name`");
		// Find all the callouts in each group
		$q = sqlquery("SELECT * FROM bigtree_callout_groups");
		while ($f = sqlfetch($q)) {
			$callouts = array();
			$qq = sqlquery("SELECT * FROM bigtree_callouts WHERE `group` = '".$f["id"]."' ORDER BY position DESC, id ASC");
			while ($ff = sqlfetch($qq)) {
				$callouts[] = $ff["id"];
			}
			sqlquery("UPDATE bigtree_callout_groups SET `callouts` = '".BigTree::json($callouts,true)."' WHERE id = '".$f["id"]."'");
		}
		// Drop the group column
		sqlquery("ALTER TABLE bigtree_callouts DROP COLUMN `group`");

		// Security policy setting
		sqlquery("INSERT INTO `bigtree_settings` (`id`,`value`,`system`) VALUES ('bigtree-internal-security-policy','{}','on')");
		sqlquery("CREATE TABLE `bigtree_login_attempts` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `ip` int(11) DEFAULT NULL, `user` int(11) DEFAULT NULL, `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		sqlquery("CREATE TABLE `bigtree_login_bans` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `ip` int(11) DEFAULT NULL, `user` int(11) DEFAULT NULL, `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP, `expires` datetime DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		// Media settings
		sqlquery("INSERT INTO `bigtree_settings` (`id`,`value`,`system`) VALUES ('bigtree-internal-media-settings','{}','on')");

		// New field types
		@unlink(SERVER_ROOT."cache/bigtree-form-field-types.json");

		// Setup an anonymous function for converting a resource set
		$resource_converter = function($resources) {
			$new_resources = array();
			foreach ($resources as $item) {
				// Array of Items no longer exists, switching to Matrix
				if ($item["type"] == "array") {
					$item["type"] = "matrix";
					$item["columns"] = array();
					$x = 0;
					foreach ($item["fields"] as $field) {
						$x++;
						$item["columns"][] = array(
							"id" => $field["key"],
							"type" => $field["type"],
							"title" => $field["title"],
							"display_title" => ($x == 1) ? "on" : ""
						);
					}
					unset($item["fields"]);
				}
				$r = array(
					"id" => $item["id"],
					"type" => $item["type"],
					"title" => $item["title"],
					"subtitle" => $item["subtitle"],
					"options" => array()
				);
				foreach ($item as $key => $val) {
					if ($key != "id" && $key != "title" && $key != "subtitle" && $key != "type") {
						$r["options"][$key] = $val;
					}
				}
				$new_resources[] = $r;
			}
			return BigTree::json($new_resources,true);
		};
		$field_converter = function($fields) {
			$new_fields = array();
			foreach ($fields as $id => $field) {
				// Array of Items no longer exists, switching to Matrix
				if ($field["type"] == "array") {
					$field["type"] = "matrix";
					$field["columns"] = array();
					$x = 0;
					foreach ($field["fields"] as $subfield) {
						$x++;
						$field["columns"][] = array(
							"id" => $subfield["key"],
							"type" => $subfield["type"],
							"title" => $subfield["title"],
							"display_title" => ($x == 1) ? "on" : ""
						);
					}
					unset($field["fields"]);
				}
				$r = array(
					"column" => $id,
					"type" => $field["type"],
					"title" => $field["title"],
					"subtitle" => $field["subtitle"],
					"options" => array()
				);
				foreach ($field as $key => $val) {
					if ($key != "id" && $key != "title" && $key != "subtitle" && $key != "type") {
						$r["options"][$key] = $val;
					}
				}
				$new_fields[] = $r;
			}
			return $new_fields;
		};

		// New resource format to be less restrictive on option names
		$q = sqlquery("SELECT * FROM bigtree_callouts");
		while ($f = sqlfetch($q)) {
			$resources = $resource_converter(json_decode($f["resources"],true));
			sqlquery("UPDATE bigtree_callouts SET resources = '$resources' WHERE id = '".$f["id"]."'");
		}
		$q = sqlquery("SELECT * FROM bigtree_templates");
		while ($f = sqlfetch($q)) {
			$resources = $resource_converter(json_decode($f["resources"],true));
			sqlquery("UPDATE bigtree_templates SET resources = '$resources' WHERE id = '".$f["id"]."'");
		}
		// Forms and Embedded Forms
		$q = sqlquery("SELECT * FROM bigtree_module_forms");
		while ($f = sqlfetch($q)) {
			$fields = $field_converter(json_decode($f["fields"],true));
			sqlquery("UPDATE bigtree_module_forms SET fields = '".BigTree::json($fields,true)."' WHERE id = '".$f["id"]."'");
		}
		$q = sqlquery("SELECT * FROM bigtree_module_embeds");
		while ($f = sqlfetch($q)) {
			$fields = $field_converter(json_decode($f["fields"],true));
			sqlquery("UPDATE bigtree_module_embeds SET fields = '".BigTree::json($fields,true)."' WHERE id = '".$f["id"]."'");
		}
		// Settings
		$q = sqlquery("SELECT * FROM bigtree_settings WHERE type = 'array'");
		while ($f = sqlfetch($q)) {
			// Update settings options to turn array into matrix
			$options = json_decode($f["options"],true);
			$options["columns"] = array();
			$x = 0;
			foreach ($options["fields"] as $field) {
				$x++;
				$options["columns"][] = array(
					"id" => $field["key"],
					"type" => $field["type"],
					"title" => $field["title"],
					"display_title" => ($x == 1) ? "on" : ""
				);
				if ($x == 1) {
					$display_key = $field["key"];
				}
			}
			unset($options["fields"]);

			// Update the value to set an internal title key
			$value = BigTreeCMS::getSetting($f["id"]);
			foreach ($value as &$entry) {
				$entry["__internal-title"] = $entry[$display_key];
			}
			unset($entry);

			// Update type/options
			sqlquery("UPDATE bigtree_settings SET type = 'matrix', options = '".BigTree::json($options,true)."' WHERE id = '".$f["id"]."'");
			// Update value separately
			BigTreeAdmin::updateSettingValue($f["id"],$value);
		}
	}

	// BigTree 4.2.1 update -- REVISION 201
	function _local_bigtree_update_201() {
		setcookie("bigtree_admin[password]","",time()-3600,str_replace(DOMAIN,"",WWW_ROOT));
		sqlquery("CREATE TABLE `bigtree_user_sessions` (`id` varchar(255) NOT NULL DEFAULT '', `email` varchar(255) DEFAULT NULL, `chain` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`), KEY `email` (`email`), KEY `chain` (`chain`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	}

	// BigTree 4.2.10 update -- REVISION 202
	function _local_bigtree_update_202() {
		sqlquery("ALTER TABLE `bigtree_pending_changes` CHANGE COLUMN `user` `user` int(11) unsigned NULL");
	}

	// BigTree 4.2.17 update -- REVISION 203
	function _local_bigtree_update_203() {
		sqlquery("ALTER TABLE `bigtree_user_sessions` ADD COLUMN `csrf_token` VARCHAR(255) NULL");
		sqlquery("ALTER TABLE `bigtree_user_sessions` ADD COLUMN `csrf_token_field` VARCHAR(255) NULL");
		sqlquery("DELETE FROM bigtree_user_sessions");
	}

	// BigTree 4.2.19 update -- REVISION 204
	function _local_bigtree_update_204() {
		sqlquery("ALTER TABLE `bigtree_404s` ADD COLUMN `site_key` VARCHAR(255) NULL");
	}

	// BigTree 4.2.20 update -- REVISION 205
	function _local_bigtree_update_205() {
		// 4.2.17 broke the 404 list to add duplicates a plenty
		$q = sqlquery("SELECT COUNT(*) AS `count`, `id`, `broken_url` FROM bigtree_404s WHERE `redirect_url` != '' GROUP BY `broken_url`");

		// Grab the ones with redirect URLs first as we don't want to mistakenly get the wrong one
		while ($f = sqlfetch($q)) {
			sqlquery("DELETE FROM bigtree_404s WHERE `broken_url` = '".sqlescape($f["broken_url"])."' AND `id` != '".$f["id"]."'");
			sqlquery("UPDATE bigtree_404s SET `requests` = '".$f["count"]."' WHERE `id` = '".$f["id"]."'");
		}

		// Now get ones without redirect URLs, doesn't matter which ID
		$q = sqlquery("SELECT COUNT(*) AS `count`, `id`, `broken_url` FROM bigtree_404s WHERE `redirect_url` = '' GROUP BY `broken_url`");

		while ($f = sqlfetch($q)) {
			sqlquery("DELETE FROM bigtree_404s WHERE `broken_url` = '".sqlescape($f["broken_url"])."' AND `id` != '".$f["id"]."'");
			sqlquery("UPDATE bigtree_404s SET `requests` = '".$f["count"]."' WHERE `id` = '".$f["id"]."'");
		}
	}

	// BigTree 4.2.20 update -- REVISION 206
	function _local_bigtree_update_206() {
		sqlquery("ALTER TABLE `bigtree_404s` ADD COLUMN `get_vars` VARCHAR(255) NOT NULL AFTER `broken_url`");
	}

	// BigTree 4.2.20 update -- REVISION 207
	function _local_bigtree_update_207() {
		sqlquery("ALTER TABLE `bigtree_users` ADD COLUMN `2fa_secret` VARCHAR(255) NOT NULL AFTER `password`");
		sqlquery("ALTER TABLE `bigtree_users` ADD COLUMN `2fa_login_token` VARCHAR(255) NOT NULL AFTER `2fa_secret`");
	}

	// BigTree 4.2.20 update -- REVISION 208
	function _local_bigtree_update_208() {
		// Clean up duplicate 404s again
		_local_bigtree_update_205();
	}

	// BigTree 4.2.20 update -- REVISION 209
	function _local_bigtree_update_209() {
		// Add a setting for storing the contact information of deleted users for use in audits
		sqlquery("INSERT INTO bigtree_settings (`id`, `value`, `system`) VALUES ('bigtree-internal-deleted-users', '[]', 'on')");

		// Drop the foreign key constraint on the audit trail which previously deleted trails for non-existant users
		$table_description = BigTree::describeTable("bigtree_audit_trail");

		foreach ($table_description["foreign_keys"] as $key => $data) {
			if ($data["local_columns"][0] == "user") {
				sqlquery("ALTER TABLE `bigtree_audit_trail` DROP FOREIGN KEY `$key`");
			}
		}
	}

	// BigTree 4.2.22 update -- REVISION 210
	function _local_bigtree_update_210() {
		// Add a location column to resources
		sqlquery("ALTER TABLE `bigtree_resources` ADD COLUMN `location` VARCHAR(255) NOT NULL AFTER `id`");

		// Try to infer the location of existing resources
		$q = sqlquery("SELECT * FROM bigtree_resources");

		while ($resource = sqlfetch($q)) {
			if (strpos($resource["file"], "{staticroot}") !== false) {
				$location = "local";
			} else {
				$location = "cloud";
			}

			sqlquery("UPDATE bigtree_resources SET location = '$location' WHERE id = '".$resource["id"]."'");
		}
	}

	// BigTree 4.3 update -- REVISION 300
	function _local_bigtree_update_300() {
		// Add a usage count column to tags
		SQL::query("ALTER TABLE `bigtree_tags` ADD COLUMN `usage_count` int(11) unsigned NOT NULL AFTER `route`");

		// Add a setting for storing file metadata information
		SQL::query("INSERT INTO bigtree_settings (`id`, `value`, `system`) VALUES ('bigtree-file-metadata-fields', '[]', 'on')");

		// Add new file manager columns
		SQL::query("ALTER TABLE `bigtree_resources` ADD COLUMN `metadata` LONGTEXT NOT NULL AFTER `type`");
		SQL::query("ALTER TABLE `bigtree_resources` ADD COLUMN `is_video` CHAR(2) NOT NULL AFTER `is_image`");
		SQL::query("ALTER TABLE `bigtree_resources` ADD COLUMN `mimetype` VARCHAR(255) NOT NULL AFTER `type`");
		SQL::query("ALTER TABLE `bigtree_resources` ADD COLUMN `size` INT(11) NOT NULL AFTER `width`");

		// Add the file manager preset to media presets
		$settings = BigTreeCMS::getSetting("bigtree-internal-media-settings");
		$settings["presets"]["default"] = json_decode('{"name":"File Manager Preset","min_width":"1440","min_height":"1080","preview_prefix":"classic-xxsml-","crops":[{"prefix":"ultrawide-xlrg-","width":"1440","height":"617","grayscale":"","thumbs":{"1":{"prefix":"ultrawide-xxsml-","width":"300","height":"","grayscale":""},"2":{"prefix":"ultrawide-xsml-","width":"500","height":"","grayscale":""},"3":{"prefix":"ultrawide-sml-","width":"740","height":"","grayscale":""},"4":{"prefix":"ultrawide-med-","width":"980","height":"","grayscale":""},"5":{"prefix":"ultrawide-lrg-","width":"1220","height":"","grayscale":""}}},{"prefix":"wide-xlrg-","width":"1440","height":"810","grayscale":"","thumbs":{"6":{"prefix":"wide-xxsml-","width":"300","height":"","grayscale":""},"7":{"prefix":"wide-xsml-","width":"500","height":"","grayscale":""},"8":{"prefix":"wide-sml-","width":"740","height":"","grayscale":""},"9":{"prefix":"wide-med-","width":"980","height":"","grayscale":""},"10":{"prefix":"wide-lrg-","width":"1220","height":"","grayscale":""}}},{"prefix":"full-xlrg-","width":"1440","height":"1080","grayscale":"","thumbs":{"11":{"prefix":"full-xxsml-","width":"300","height":"","grayscale":""},"12":{"prefix":"full-xsml-","width":"500","height":"","grayscale":""},"13":{"prefix":"full-sml-","width":"740","height":"","grayscale":""},"14":{"prefix":"full-med-","width":"980","height":"","grayscale":""},"15":{"prefix":"full-lrg-","width":"1220","height":"","grayscale":""}}},{"prefix":"square-med-","width":"980","height":"980","grayscale":"","thumbs":{"16":{"prefix":"square-xxsml-","width":"300","height":"","grayscale":""},"17":{"prefix":"square-xsml-","width":"500","height":"","grayscale":""},"18":{"prefix":"square-sml-","width":"740","height":"","grayscale":""}}},{"prefix":"classic-xlrg-","width":"1440","height":"960","grayscale":"","thumbs":{"19":{"prefix":"classic-xxsml-","width":"300","height":"","grayscale":""},"20":{"prefix":"classic-xsml-","width":"500","height":"","grayscale":""},"21":{"prefix":"classic-sml-","width":"740","height":"","grayscale":""},"22":{"prefix":"classic-med-","width":"980","height":"","grayscale":""},"23":{"prefix":"classic-lrg-","width":"1220","height":"","grayscale":""}}},{"prefix":"portrait-full-med-","width":"735","height":"980","grayscale":"","thumbs":{"24":{"prefix":"portrait-full-xxsml-","width":"","height":"300","grayscale":""},"25":{"prefix":"portrait-full-xsml-","width":"","height":"500","grayscale":""},"26":{"prefix":"portrait-full-sml-","width":"","height":"740","grayscale":""}}},{"prefix":"portrait-classic-med-","width":"654","height":"980","grayscale":"","thumbs":{"27":{"prefix":"portrait-classic-xxsml-","width":"","height":"300","grayscale":""},"28":{"prefix":"portrait-classic-xsml-","width":"","height":"500","grayscale":""},"29":{"prefix":"portrait-classic-sml-","width":"","height":"740","grayscale":""}}}],"thumbs":[{"prefix":"","width":"3000","height":"3000","grayscale":""}],"id":"default"}', true);
		BigTreeAdmin::updateSettingValue("bigtree-internal-media-settings", $settings);

		// Delete the field type cache
		@unlink(SERVER_ROOT."cache/bigtree-form-field-types.json");
	}

	// BigTree 4.3 update -- REVISION 301
	function _local_bigtree_update_301() {
		SQL::query("ALTER TABLE `bigtree_settings` CHANGE COLUMN `options` `settings` LONGTEXT");
	}

	// BigTree 4.3 update -- REVISION 302
	function _local_bigtree_update_302() {
		SQL::query("ALTER TABLE `bigtree_feeds` CHANGE COLUMN `options` `settings` LONGTEXT");
		SQL::query("ALTER TABLE `bigtree_module_views` CHANGE COLUMN `options` `settings` LONGTEXT");
	}

	// BigTree 4.3 update -- REVISION 303
	function _local_bigtree_update_303() {
		SQL::query("ALTER TABLE `bigtree_users` ADD COLUMN `new_hash` CHAR(2) NOT NULL AFTER `password`");
	}

	// BigTree 4.3 update -- REVISION 304
	function _local_bigtree_update_304() {
		SQL::query("CREATE TABLE `bigtree_sessions` (`id` varchar(32) NOT NULL,`last_accessed` int(10) unsigned DEFAULT NULL,`data` longtext,`is_login` char(2) NOT NULL DEFAULT '',PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
	}

	// BigTree 4.3 update -- REVISION 305
	function _local_bigtree_update_305() {
		SQL::query("ALTER TABLE `bigtree_sessions` ADD COLUMN `logged_in_user` int(11) unsigned DEFAULT NULL AFTER `is_login`");
		SQL::query("ALTER TABLE `bigtree_sessions` ADD CONSTRAINT fk_logged_in_user FOREIGN KEY (`logged_in_user`) REFERENCES bigtree_users(id) ON DELETE CASCADE");
	}

	// BigTree 4.3 update -- REVISION 306
	function _local_bigtree_update_306() {
		SQL::query("ALTER TABLE `bigtree_resources` ADD COLUMN `video_data` longtext DEFAULT NULL AFTER `thumbs`");
		SQL::query("ALTER TABLE `bigtree_resources` CHANGE COLUMN `size` `size` int(11) unsigned DEFAULT NULL");
		SQL::query("ALTER TABLE `bigtree_resources` CHANGE COLUMN `height` `height` int(11) unsigned DEFAULT NULL");
		SQL::query("ALTER TABLE `bigtree_resources` CHANGE COLUMN `width` `width` int(11) unsigned DEFAULT NULL");
		SQL::query("ALTER TABLE `bigtree_resources` CHANGE COLUMN `md5` `md5` varchar(255) DEFAULT NULL");
		SQL::query("ALTER TABLE `bigtree_resources` CHANGE COLUMN `mimetype` `mimetype` varchar(255) DEFAULT NULL");
		SQL::query("ALTER TABLE `bigtree_resources` CHANGE COLUMN `location` `location` varchar(255) DEFAULT NULL");
		SQL::query("ALTER TABLE `bigtree_resources` DROP COLUMN `list_thumb_margin`");
	}
	
	// BigTree 4.3 update -- REVISION 307
	function _local_bigtree_update_307() {
		// Set region for AWS if it's configured
		$setting_value = BigTreeCMS::getSetting("bigtree-internal-cloud-storage");
		
		if (!empty($setting_value["amazon"]["key"])) {
			$setting_value["amazon"]["region"] = "us-east-1";
			$admin->updateSettingValue("bigtree-internal-cloud-storage", $setting_value);
		}

		$storage = new BigTreeStorage;
		$local_storage = new BigTreeStorage(true);
		
		// Generate list preview images for the new file manager and fix the thumbs array
		$resources = SQL::fetchAll("SELECT * FROM bigtree_resources WHERE is_image = 'on'");
		
		foreach ($resources as $resource) {
			$source = str_replace("{staticroot}", SITE_ROOT, $resource["file"]);

			if (strpos($source, "//") === 0) {
				$source = "https:".$source;
			}

			$thumbs = json_decode($resource["thumbs"], true);
			$basename = pathinfo($source, PATHINFO_BASENAME);
			$extension = pathinfo($source, PATHINFO_EXTENSION);
			$temp_file = SERVER_ROOT."cache/".BigTree::getAvailableFileName(SERVER_ROOT."cache/", "temp.$extension");
			
			// See if this is an earlier 4.3 upload or a 4.2 or lower
			$is_43 = (count(array_filter(array_keys($thumbs), "is_int")) == count($thumbs));

			if ($is_43) {
				$fixed_thumbs = [];

				foreach ($thumbs as $key => $prefix) {
					if (!is_array($prefix)) {
						// Make a copy to get height/width
						if (BigTree::copyFile(BigTree::prefixFile($source, $prefix), $temp_file)) {
							list($width, $height) = getimagesize($temp_file);
							$fixed_thumbs[$prefix] = ["width" => $width, "height" => $height];
						}
					} else {
						$fixed_thumbs[$key] = $prefix;
					}
				}

				$fixed_crops = [];
				$crops = json_decode($resource["crops"], true);

				foreach ($crops as $key => $prefix) {
					if (!is_array($prefix)) {
						// Make a copy to get height/width
						if (BigTree::copyFile(BigTree::prefixFile($source, $prefix), $temp_file)) {
							list($width, $height) = getimagesize($temp_file);
							$fixed_crops[$prefix] = ["width" => $width, "height" => $height];
						}
					} else {
						$fixed_crops[$key] = $prefix;
					}
				}
				
				SQL::update("bigtree_resources", $resource["id"], ["thumbs" => $fixed_thumbs, "crops" => $fixed_crops]);
			} else {
				BigTree::centerCrop($source, $temp_file, 100, 100);
				
				if ($resource["location"] == "local") {
					$local_storage->store($temp_file, $basename, "files/resources/list-preview/");
				} else {
					$storage->store($temp_file, $basename, "files/resources/list-preview/");
				}
				
				$fixed_thumbs = [];
				
				foreach ($thumbs as $prefix => $location) {
					// Make a copy to get height/width
					if (BigTree::copyFile(BigTree::prefixFile($source, $prefix), $temp_file)) {
						list($width, $height) = getimagesize($temp_file);
						$fixed_thumbs[$prefix] = ["width" => $width, "height" => $height];
					}
				}
				
				SQL::update("bigtree_resources", $resource["id"], ["thumbs" => $fixed_thumbs]);
			}
			
			@unlink($temp_file);
		}
		
		// BigTree 4.3 update -- REVISION 308
		function _local_bigtree_update_308() {
			SQL::query("CREATE TABLE `bigtree_open_graph` ( `id` int(11) unsigned NOT NULL AUTO_INCREMENT, `table` varchar(255) NOT NULL DEFAULT '', `entry` int(11) unsigned NOT NULL, `type` varchar(255) DEFAULT NULL, `title` varchar(255) DEFAULT NULL, `description` text, `image` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			SQL::query("ALTER TABLE `bigtree_module_forms` ADD COLUMN `open_graph` CHAR(2) NOT NULL AFTER `return_url`");
			SQL::query("ALTER TABLE `bigtree_pending_changes` ADD COLUMN `open_graph_changes` LONGTEXT NOT NULL AFTER `tags_changes`");
		}
		
	}
