<?php
	// If we're using a preset, the prefix may be there
	if (!empty($field["settings"]["preset"])) {
		if (!isset($bigtree["media_settings"])) {
			$bigtree["media_settings"] = $cms->getSetting("bigtree-internal-media-settings");
		}
		$preset = $bigtree["media_settings"]["presets"][$field["settings"]["preset"]];
		if (!empty($preset["preview_prefix"])) {
			$field["settings"]["preview_prefix"] = $preset["preview_prefix"];
		}
		if (!empty($preset["min_width"])) {
			$field["settings"]["min_width"] = $preset["min_width"];
		}
		if (!empty($preset["min_height"])) {
			$field["settings"]["min_height"] = $preset["min_height"];
		}
	}

	// Get min width/height designations
	$min_width = $field["settings"]["min_width"] ? intval($field["settings"]["min_width"]) : 0;
	$min_height = $field["settings"]["min_height"] ? intval($field["settings"]["min_height"]) : 0;
?>
<div class="<?php if (empty($field["settings"]["image"])) { ?>upload_field<?php } else { ?>image_field<?php } ?>">
	<input<?php if ($field["required"]) { ?> class="required"<?php } ?> type="file" tabindex="<?=$field["tabindex"]?>" name="<?=$field["key"]?>" data-min-width="<?=$min_width?>" data-min-height="<?=$min_height?>" <?php if (!empty($field["settings"]["image"])) { ?> accept="image/*" <?php } ?>/>
	<?php
		if (!isset($field["settings"]["image"]) || !$field["settings"]["image"]) {
			if ($field["value"]) {
				$pathinfo = BigTree::pathInfo($field["value"]);
	?>
	<div class="currently_file">
		<input type="hidden" name="<?=$field["key"]?>" value="<?=$field["value"]?>" />
		<strong>Currently:</strong> <a href="<?=$field["value"]?>" target="_blank"><?=$pathinfo["basename"]?></a> <a href="#" class="remove_resource">Remove</a>
	</div>
	<?php
			}
		} else {
			if ($field["value"]) {
				if ($field["settings"]["preview_prefix"]) {
					$preview_image = BigTree::prefixFile($field["value"],$field["settings"]["preview_prefix"]);
				} else {
					$preview_image = $field["value"];
				}
			} else {
				$preview_image = false;
			}

			// Generate the file manager restrictions
			$button_options = htmlspecialchars(json_encode(array(
				"minWidth" => $min_width,
				"minHeight" => $min_height,
				"currentlyKey" => $field["key"],
				"type" => "image"
			)));

			if (!defined("BIGTREE_FRONT_END_EDITOR") && !$bigtree["form"]["embedded"]) {
	?>
	<span class="or">OR</span>
	<a href="#<?=$field["id"]?>" data-options="<?=$button_options?>" class="button resource_browser_button"><span class="icon_images"></span>Browse</a>
	<?php
			}
	?>
	<br class="clear" />
	<div class="currently" id="<?=$field["id"]?>"<?php if (!$field["value"]) { ?> style="display: none;"<?php } ?>>
		<a href="#" class="remove_resource"></a>
		<div class="currently_wrapper">
			<?php if ($preview_image) { ?>
			<a href="<?=$field["value"]?>" target="_blank"><img src="<?=$preview_image?>" alt="" /></a>
			<?php } ?>
		</div>
		<label>CURRENT</label>
		<input type="hidden" name="<?=$field["key"]?>" value="<?=$field["value"]?>" />
	</div>
	<?php
		}
	?>
</div>