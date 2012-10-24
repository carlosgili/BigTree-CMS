<?
	// Set the page title.
	$module_title = "Ignored 404s";

	$total = $admin->get404Total("ignored");
	$type = "ignored";
	$breadcrumb[] = array("link" => "dashboard/vitals-statistics/404/ignored/", "title" => "Ignored 404s");
	$delete_action = "unignore";
?>
<h1>
	<span class="page_404"></span>Ignored 404s
	<? include BigTree::path("admin/modules/dashboard/vitals-statistics/_jump.php"); ?>
</h1>
<? include BigTree::path("admin/modules/dashboard/vitals-statistics/404/_nav.php") ?>
<div class="table">
	<summary>
		<input type="search" class="form_search" placeholder="Search" id="404_search" />
		<span class="form_search_icon"></span>
		<ul id="view_paging" class="view_paging"></ul>
	</summary>
	<header>
		<span class="requests_404">Requests</span>
		<span class="url_404">404 URL</span>
		<span class="redirect_404">Redirect</span>
		<span class="ignore_404">Unignore</span>
		<span class="ignore_404">Delete</span>
	</header>
	<ul id="results">
		<? include BigTree::path("admin/ajax/dashboard/404/search.php") ?>
	</ul>
</div>