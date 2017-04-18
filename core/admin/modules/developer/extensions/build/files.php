<?php
	namespace BigTree;
?>
<div class="container">
	<header><p><?=Text::translate("Add additional files and tables to your extension.")?></p></header>
	<form method="post" action="<?=DEVELOPER_ROOT?>extensions/build/save-files/" class="module">
	  <?php CSRF::drawPOSTToken(); ?>
		<section>
			<article class="package_column package_column_double">
				<strong><?=Text::translate("Files")?></strong>
				<ul id="package_files">
					<?php
						foreach ((array)$_SESSION["bigtree_admin"]["developer"]["package"]["files"] as $file) {
							if (file_exists($file)) {
					?>
					<li>
						<input type="hidden" name="files[]" value="<?=htmlspecialchars($file)?>" />
						<a href="#" class="icon_small icon_small_delete"></a>
						<span><?=Text::replaceServerRoot($file)?></span>
					</li>
					<?php
							}
						}
					?>
				</ul>
				<div class="add_file adder">
					<a href="#"><span class="icon_small icon_small_folder"></span><?=Text::translate("Browse For File")?></a>
				</div>
			</article>
			<article class="package_column package_column_double package_column_last">
				<strong><?=Text::translate("Tables")?></strong>
				<ul>
					<?php
						$used_tables = array();
						foreach ((array)$_SESSION["bigtree_admin"]["developer"]["package"]["tables"] as $table) {
							list($table) = explode("#",$table);
							$used_tables[] = $table;
					?>
					<li>
						<input type="hidden" name="tables[]" value="<?=$table?>" />
						<a href="#<?=$table?>" class="icon_small icon_small_delete"></a>
						<?=$table?>
					</li>
					<?php
						}
					?>
				</ul>
				<div class="add_table adder">
					<a class="icon_small icon_small_add" href="#"></a>
					<label for="add_table_select" class="visually_hidden">Table</label>
					<select class="custom_control" id="add_table_select">
						<?php
							$tables = SQL::fetchAllSingle("SHOW TABLES");
							foreach ($tables as $table) {
								if (substr($table,0,8) != "bigtree_" && !in_array($table,$used_tables)) {
						?>
						<option value="<?=$table?>"><?=$table?></option>
						<?php
								}
							}
						?>
					</select>
				</div>
			</article>
		</section>
		<footer>
			<input type="submit" class="button blue" value="<?=Text::translate("Continue", true)?>" />
		</footer>
	</form>
</div>
<script>
	$(".add_table a").click(function() {
		var table_select = $("#add_table_select");
		var table = table_select.val();
		if (table) {
			var li = $("<li>");
			li.html('<input type="hidden" name="tables[]" value="' + table + '" /><a href="#' + table + '" class="icon_small icon_small_delete"></a>' + table);
			$(this).parent().parent().find("ul").append(li);
			// Remove from the select
			table_select.find("option[value='" + table + "']").remove();
		}
		return false;
	});

	$(".add_file a").click(function() {
		BigTreeFilesystemBrowser({
			directory: "",
			callback: function(data) {
				var li = $("<li>");
				li.html('<input type="hidden" name="files[]" value="<?=SERVER_ROOT?>' + data.directory + data.file + '" /><a href="#" class="icon_small icon_small_delete"></a>' + data.directory + data.file);
				$("#package_files").append(li);
			},
			disableCloud: true
		});
	});

	$(".package_column").on("click",".icon_small_delete",function() {
		// Get table name, add back to the dropdown
		var table = $(this).attr("href").substr(1);
		var option = $('<option value="' + table + '">' + table + '</option>');
		$("#add_table_select").append(option).sortSelect();
		// Remove it from the list
		$(this).parent().remove();
		return false;
	});
</script>