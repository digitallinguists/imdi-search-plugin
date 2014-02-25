<?php
$_opt_imdi_query = 'st_imdi_query';
$_opt_imdi_field = 'st_imdi_field';
$_opt_imdi_topnode = 'st_imdi_topnode';
$_opt_imdi_archive_url = 'st_imdi_archive_url';
$_opt_imdi_servlet_url = 'st_imdi_servlet_url';
$_opt_imdi_max_results = 'st_imdi_max_results';
$_opt_imdi_categories = 'a_imdi_categories';

$_opt_imdi_user_saved_session = 'imdi_user_saved_session';


add_action('admin_menu', 'add_imdisearch_options');




// set a standard set of categories on first run

// Options hook
function add_imdisearch_options() {
    if (function_exists('add_options_page')) {
		add_options_page('Imdi Archive Search', 'Imdi Archive Search', 8, 'imdisearch', 'imdisearch_options_subpanel');
    }
}


function options_enqueue() { 
		wp_register_script( 'imdi-archive-options', plugins_url( '/js/options_panel.js', __FILE__ ), array( 'jquery' ), '0.1.0', true );
		wp_register_style( 'imdi-archive-options', plugins_url( '/css/imdi_settings.css', __FILE__ ) );

    wp_enqueue_script( 'imdi-archive-options' );
    wp_enqueue_style( 'imdi-archive-options' );
}
add_action( 'admin_enqueue_scripts', 'options_enqueue' );


// Options panel and form processing
function imdisearch_options_subpanel() {

	echo "<h2>IMDI Archive Search Options</h2>";
        if (isset($_POST['info_update'])) {
		global $_opt_imdi_archive_url;
                global $_opt_imdi_servlet_url;
                global $_opt_imdi_field;
                global $_opt_imdi_topnode;
		global $_opt_imdi_max_results;
		global $_opt_imdi_categories;

                $the_archive_url = $_POST['imdi_archive_url'];
		$the_servlet_url = $_POST['imdi_servlet_url'];
                $the_field = $_POST['imdi_query_field'];
                $the_topnode = $_POST['imdi_query_topnode'];
		$the_max_results = $_POST['imdi_max_results'];

                update_option($_opt_imdi_field, $the_field);
                update_option($_opt_imdi_topnode, $the_topnode);
		update_option($_opt_imdi_max_results, $the_max_results);
                update_option($_opt_imdi_servlet_url, $the_servlet_url);
		update_option($_opt_imdi_archive_url, $the_archive_url);

		update_option($_opt_imdi_categories, json_decode(stripslashes_deep($_POST['imdi_categories'])));
        }
        _show_imdisearch_form();
}

function _show_imdisearch_form() {

	?>
<div class="wrap">
<form id="imdi_options" method="post">
<fieldset class="options">
<legend><?php _e('Setup') ?></legend>
<table width="100%" cellspacing="2" cellpadding="5" class="editform">
<tr valign="top">
<th scope="row"><label for="imdi_archive_url"><?php _e('URL of the IMDI archive:') ?></label></th>
<td><input type="text" name="imdi_archive_url" id="imdi_archive_url" size="50" value="<?php form_option('st_imdi_archive_url'); ?>"/></td>
</tr>
<tr valign="top">
<th scope="row"><label for="imdi_servlet_url"><?php _e('URL of the IMDI search servlet:') ?></label></th>
<td><input type="text" name="imdi_servlet_url" id="imdi_servlet_url" size="50" value="<?php form_option('st_imdi_servlet_url'); ?>"/></td> 
</tr>
<tr valign="top">
<th scope="row"><label for="imdi_query_field"><?php _e('Default IMDI field to search through for advanced search (e.g. Session.Name):') ?></label></th>
<td><input type="text" name="imdi_query_field" id="imdi_query_field" value="<?php form_option('st_imdi_field'); ?>"/></td> 
</tr> 
<tr valign="top">
<th scope="row"><label for="imdi_query_topnode"><?php _e('Return all results below node #:') ?></label></th>
<td><input type="text" name="imdi_query_topnode" id="imdi_query_topnode" size="7" value="<?php form_option('st_imdi_topnode'); ?>"/></td> 
</tr>
<tr valign="top">
<th scope="row"><label for="imdi_max_results"><?php _e('Maximum number of search results to return:') ?></label></th>
<td><input type="text" name="imdi_max_results" id="imdi_max_results" size="7" value="<?php form_option('st_imdi_max_results'); ?>"/></td>
</tr>
</table>

<h2>Categories</h2>

<?php 	//echo var_dump(get_option('a_imdi_categories'));?>

<div id="categories" style:"clear: both;">
<?php foreach (get_option('a_imdi_categories') as $category): ?>
	<div id="categoryRow"><?php echo json_encode($category); ?></div>
<?php endforeach; ?>
</div>
</p>
</fieldset>
<a href="#" id="imdi_add_cat">Add Category</a>
<p class="submit">
<input type="submit" name="info_update" value="<?php _e('Update Options') ?> &raquo;" />
</form>
</div>
	<?php
}
?>