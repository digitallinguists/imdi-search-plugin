<?php
/*
Plugin Name: IMDI Archive Search Plugin CCeH 
Plugin URI: http://tla.mpi.nl
Description: Searching in an IMDI archive via the REST API (Ajax version).
Author: Paul Trilsbeek & Alex König
Author URI: http://tla.mpi.nl
Version: 0.1.0-cceh
License: GNU General Public License v2.0 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
Text Domain: imdi
*/

/*
	Using Thomas Griffin's Sample Ajax Plugin as a template.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * IMDI Archive Search plugin class.
 *
 * @since 0.1.0
 *
 * @package	IMDI Archive Search Plugin
 * @author	Paul Trilsbeek & Alex König
 */
 
 require 'IMDI_settings.php';

 require 'http_build_url.php';


// define the bcn_breadcrumb_trail_object callback
function add_resource_trail( $bcn_breadcrumb_trail ) 
{
	$breadcrumbs = null;
	$referer_page = get_page(url_to_postid(wp_get_referer()));

	if (has_shortcode(get_the_content(), 'imdi-archive-resource-page') == "imdi_resource") {
		if (has_shortcode($referer_page->post_content, 'imdi-archive-search-plugin')) {
			$project_title = (string) get_resource_info()['parent_details']->Session->MDGroup->Project->Title;
			$breadcrumbs = array(new bcn_breadcrumb($project_title), new bcn_breadcrumb(__("Search results"), '', array(), $url = wp_get_referer()));
		} else {
			if (
				has_shortcode($referer_page->post_content, 'imdi-sessions') ||
				has_shortcode($referer_page->post_content, 'imdi-project') ||
				has_shortcode($referer_page->post_content, 'imdi-query') ||
				has_shortcode($referer_page->post_content, 'imdi-archive-search-plugin'))
			{
				$breadcrumbs = array(new bcn_breadcrumb($referer_page->post_title, '', array(), $url = wp_get_referer()));
			} else {
				$project_title = get_resource_info()['parent_details']->Session->MDGroup->Project->Title;
				$breadcrumbs = array(new bcn_breadcrumb($session_details->Session->MDGroup->Project->Title, '', array())); 
			}
		}
		array_splice($bcn_breadcrumb_trail->trail, 1, 0, $breadcrumbs);
	}
	
} 
        

function imdi_activation_hook() {
	if (get_option('a_imdi_categories') == '') 	
		update_option('a_imdi_categories', array(
					array(
						'name' => "Countries",
						'type' => "occurrences",
						'path' => "Session.MDGroup.Location.Country"
						),
					array(
						'name' => "Projects",
						'type' => "occurrences",
						'path' => "Session.MDGroup.Project.Name"
						)
				)
	);
}
register_activation_hook(__FILE__, 'imdi_activation_hook');


// Initialize mustache template engine
require 'vendor/php/Mustache/Autoloader.php';
Mustache_Autoloader::register();

$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
	));

$imdi_resource_node_info = NULL;

function get_resource_info() {
		global $imdi_resource_node_info;
		global $wp_query;

		if ($imdi_resource_node_info == NULL) {
			if ((substr($wp_query->query_vars['node'], 0,3) == "MPI") && (substr($wp_query->query_vars['node'], -3) != "%23"))
				$wp_query->query_vars['node'] .= "%23";

			$node = get_node_info(urldecode($wp_query->query_vars['node']));
			$parent = get_node_info($node['node.parents'][0]);

			$imdi_resource_node_info = Array(
					'node' => $node,
					'parent' => $parent,
					'parent_details' => get_session_details($parent['file.uri'])
				);
		}

		return $imdi_resource_node_info;
}

function get_resource_node_title() {

	$node = get_resource_info();
	if ($node['parent']['node.title'] != "")
		return $node['parent']['node.title'];
	else
		return $node['parent']['node.name'];
}


function imdi_wp_title( $title, $sep) {
	if (is_page('imdi-resource'))
		return get_resource_node_title();
	else 
		return $title;
}

function imdi_the_title( $title, $id ) {

		if (($id == get_page_by_title("imdi-resource")->ID) && (is_page('imdi-resource')))
			return get_resource_node_title();
		else
			return $title;
}

add_filter( 'wp_title', 'imdi_wp_title', 10, 2 );
add_filter( 'the_title', 'imdi_the_title', 10, 2 );



// function resource_node_rewrite_tag() {
// 	add_rewrite_tag('%node%', '([^&]+)');
// }
// function resource_node_rewrite() {
// 	$res_page_id = get_page_by_path("imdi-resource")->ID;
// 	add_rewrite_rule('imdi-resource/node/([^/]+)$', 'index.php?p=$matches[1]&node=abc', top);
// }

// add_action('init', 'resource_node_rewrite_tag');
// add_action('init', 'resource_node_rewrite');

// add_action( 'init', 'imdi_permalinks' );
// function imdi_permalinks() {
    
// }
// add_filter( 'query_vars', 'imdi_query_vars' );
// function imdi_query_vars( $query_vars ) {
//     $query_vars[] = 'node';
//     return $query_vars;
// }

function imdi_projectresources_add_var($content) {
  // assuming you have created a page/post entitled 'debug'

	$js = "";

  	if(
  		has_shortcode($content, 'imdi-project') ||
  		has_shortcode($content, 'imdi-sessions') ||
  		has_shortcode($content, 'imdi-nodes')
  	  ) {

  		$js .= "<script type='text/javascript'>";
		$js .= "var imdi_requests = [];"; 
		$js .= "</script>";

  	}

    return $js.$content;
}
add_filter( 'the_content', 'imdi_projectresources_add_var' );



function imdi_plugin_activate() {
  imdi_plugin_rules();
  flush_rewrite_rules();
 }

 function imdi_plugin_deactivate() {
  flush_rewrite_rules();
 }

 function imdi_plugin_rules() {
 	global $wp;
    $wp->add_query_var( 'node' );
	add_rewrite_tag('%node%','([^&]+)','node=');

	add_rewrite_rule(
        'node/([^/]*)/?$',
        'index.php?pagename=browse/imdi-resource&node=$matches[1]',
        'top'
	);
	add_rewrite_rule(
        'node/([1-9]+)/([^/]*)/?$',
        'index.php?pagename=browse/imdi-resource&node=$matches[1]%2F$matches[2]',
        'top'
	);

}




 
 //register activation function
 register_activation_hook(__FILE__, 'imdi_plugin_activate');
 //register deactivation function
 register_deactivation_hook(__FILE__, 'imdi_plugin_deactivate');
 //add rewrite rules in case another plugin flushes rules
 add_action('init', 'imdi_plugin_rules');
 //add plugin query vars (product_id) to wordpress
 //add_filter('query_vars', 'imdi_plugin_query_vars');


 
class IMDI_Search_Plugin {

	/**
	 * Holds a copy of the object for easy reference.
	 *
	 * @since 0.1.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Constructor. Hooks all interactions and such for the class.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		/** Store the object in a static prop	erty */
		self::$instance = $this;

		/** Hook everything into the plugins_loaded hook */
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		
		/** Handle our AJAX submissions */
		add_action( 'wp_ajax_search_IMDI_archive', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_search_IMDI_archive', array( $this, 'ajax' ) );

		add_action( 'wp_ajax_IMDI_get_occurrences', array( $this, 'ajax_occurrences' ) );				
		add_action( 'wp_ajax_nopriv_IMDI_get_occurrences', array( $this, 'ajax_occurrences' ) );		

		add_action( 'wp_ajax_IMDI_output_category', array( $this, 'ajax_output_category' ) );				
		add_action( 'wp_ajax_nopriv_IMDI_output_category', array( $this, 'ajax_output_category' ) );

		add_action( 'wp_ajax_IMDI_get_nodes', array( $this, 'ajax_get_nodes' ) );				
		add_action( 'wp_ajax_nopriv_IMDI_get_nodes', array( $this, 'ajax_get_nodes' ) );		

		add_action( 'wp_ajax_IMDI_elanvtt', array( $this, 'ajax_elanvtt' ) );				
		add_action( 'wp_ajax_nopriv_IMDI_elanvtt', array( $this, 'ajax_elanvtt' ) );

		//add_action( 'wp_ajax_IMDI_make_media_thumbnail, array( $this, 'ajax_make_media_thumbnail' ) );				
		//add_action( 'wp_ajax_nopriv_IMDI_make_media_thumbnail', array( $this, 'ajax_make_media_thumbnailr_' ) );		


		add_filter( 'the_title', array( $this, 'set_custom_title'), 10, 2); 

		add_action( 'wp_ajax_IMDI_toggle_save_session', array( $this, 'ajax_toggle_save_session' ) );				

	}
	
	/**
	 * Loads all the stuff to make the plugin run.
	 *
	 * @since 0.1.0
	 */
	public function init() {
	
		/** Load the plugin textdomain for internationalizing strings */
		load_plugin_textdomain( 'imdi', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_shortcode( 'imdi-archive-search-plugin', array( $this, 'shortcode' ) );
		add_shortcode( 'imdi-archive-simplesearch', array( $this, 'shortcode_simplesearch'));
		add_shortcode( 'imdi-archive-resource-page', array($this, 'shortcode_resourcepage'));
		add_shortcode( 'imdi-archive-user-bookmarks', array($this, 'shortcode_userbookmarks'));
		//add_shortcode( 'imdi-archive-project-resources', array($this, 'shortcode_projectresources'));
		add_shortcode( 'imdi-project', array($this, 'shortcode_imdi_project'));
		add_shortcode( 'imdi-query', array($this, 'shortcode_imdi_query'));
		add_shortcode( 'imdi-sessions', array($this, 'shortcode_imdi_sessions'));


	}
	
	/**
	 * Registers the scripts and styles for the plugin.
	 *
	 * @since 0.1.0
	 */
	public function scripts() {


	global $_opt_imdi_categories;
		/** Register and localize our script - we will enqueue it later */
		wp_register_script( 'imdi-archive-search-plugin', plugins_url( '/js/ajax.js', __FILE__ ), array( 'jquery' ), '0.1.0', true );
		$args = array(
			'error'		=> __( 'An unknown error occurred. Please try again!', 'imdi' ),
			'nonce'		=> wp_create_nonce( 'imdi-archive-search-plugin-nonce' ),
			'searching'	=> __( 'Searching the archive...', 'imdi' ),
			'spinner'	=> admin_url( 'images/loading.gif' ),
			'url'		=> admin_url( 'admin-ajax.php' ),
			'plugin_url' => plugins_url('//IMDI-search-plugin//'),
			'categories' => get_option($_opt_imdi_categories)
		);

		//echo "CAT".var_dump(get_option($_opt_imdi_categories));

		wp_localize_script( 'imdi-archive-search-plugin', 'imdi_archive_search_plugin_object', $args );
		
		/** Register our style */
		wp_register_style( 'imdi-archive-search-plugin', plugins_url( '/css/style.css', __FILE__ ) );
	

		/** Register wavesurfer for ELAN player */
		wp_register_script( 'wavesurfer', plugins_url( '/js/wavesurfer/wavesurfer.min.js', __FILE__ ));
		wp_register_script( 'wavesurfer-elan', plugins_url( '/js/wavesurfer/plugin/wavesurfer.elan.js', __FILE__ ));
		wp_register_script( 'wavesurfer-timeline', plugins_url( '/js/wavesurfer/plugin/wavesurfer.timeline.min.js', __FILE__ ));
		wp_register_script( 'wavesurfer-regions', plugins_url( '/js/wavesurfer/plugin/wavesurfer.regions.min.js', __FILE__ ));
		wp_register_script( 'wavesurfer-minimap', plugins_url( '/js/wavesurfer/plugin/wavesurfer.minimap.min.js', __FILE__ ));
		wp_register_script( 'elan-player', plugins_url( '/js/elan-player.js', __FILE__ ));



		/** history.js for legacy browser history control */

		wp_register_script( 'history.js', plugins_url( '/vendor/js/jquery.history.js', __FILE__));

		
		/** responsive table styles */
		wp_register_style('responsive_tables', plugins_url( '/css/resultstable.css', __FILE__ ));

		/** jQuery UI components */
		wp_register_script( 'jquery-ui', plugins_url( '/vendor/js/jquery-ui-1.10.4.custom.min.js', __FILE__ ));
		wp_register_style('jquery-ui', plugins_url( '/vendor/css/imdi-theme/jquery-ui-1.10.4.custom.css', __FILE__ ));

		wp_register_script( 'data-tables', plugins_url( '/js/jquery.dataTables.min.js', __FILE__ ));
		wp_register_style( 'data-tables', plugins_url( '/css/jquery.dataTables.css', __FILE__ ));

		wp_register_script( 'data-tables-colvis', plugins_url( '/js/dataTables.colVis.js', __FILE__ ));
		wp_register_style( 'data-tables-colvis-jquery-ui', plugins_url( '/css/dataTables.colvis.jqueryui.css', __FILE__ ));
		wp_register_style( 'data-tables-colvis', plugins_url( '/css/dataTables.colVis.css', __FILE__ ));

		wp_register_script('video.js', '//vjs.zencdn.net/4.12/video.js');
		wp_register_style('video.js', '//vjs.zencdn.net/4.12/video-js.css');


		wp_register_script('video.js-overlay', '//players.brightcove.net/videojs-overlay/lib/videojs-overlay.js');
		wp_register_style('video.js-overlay', '//players.brightcove.net/videojs-overlay/lib/videojs-overlay.css');
		wp_register_script('please.js', '//cdnjs.cloudflare.com/ajax/libs/pleasejs/0.2.0/Please.min.js');

/** register maps plugin */
		wp_register_script( 'leaflet_maps', plugins_url('/vendor/js/leaflet.js', __FILE__));
		wp_register_style( 'leaflet_maps', plugins_url('/vendor/css/leaflet.css', __FILE__));


	}
	
	/**
	 * Outputs the shortcode for the plugin.
	 *
	 * @since 0.1.0
	 */

	public function imdi_enqueue_scripts() {
		wp_enqueue_script('jquery');

		wp_enqueue_script('jquery-ui');
		wp_enqueue_style('jquery-ui');
	
		/** Enqueue our scripts and styles */
		wp_enqueue_script( 'imdi-archive-search-plugin' );
		wp_enqueue_style( 'imdi-archive-search-plugin' );

		wp_enqueue_script( 'history.js' );

		wp_enqueue_script('leaflet_maps');
		wp_enqueue_style('leaflet_maps');
	}

	public function shortcode() {

		$this->imdi_enqueue_scripts();

		$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));

		$output =  $m->render('imdi_query', array('inputTitle' => __('Input', 'imdi'),
													'placeholderText' => __( 'Enter search term here', 'imdi' ),
													'submitButtonText' => __( 'Search the Archive', 'imdi' ),
													'categoriesTabTitle' => __("Categories", "imdi"),
													'fulltextSearchTabTitle' => __("Keyword search", "imdi"),
													'advancedSearchTabTitle' => __("Advanced search", "imdi"),
													'onlyWithRessourcesText' => __("Only show results with freely accessible resources")
													));

		$_GET = stripslashes_deep($_GET);

		$output .= "<script type='text/javascript'> var _GET = " . (!empty($_GET)?json_encode($_GET):'null') ."; </script>";
 

		return $output;
	
	}


	private $resource_block_counter = 0;

	// public function shortcode_projectresources($atts, $content = null) {
		
	// 	$output =  "<div id=\"results-area\"></div>";
	// 	$output .= "<script type='text/javascript'>";
	// 	//$output .= "var imdi_requests = [];"; 

	// 	$output .= wp_strip_all_tags(force_balance_tags(do_shortcode(shortcode_unautop($content))));

	// 	$output .= "</script>";

	// 	$this->imdi_enqueue_scripts();

	// 	return html_entity_decode(force_balance_tags($output));
	// }

	public function shortcode_imdi_project($atts) {

		if (!isset($atts['project'])) {
			return __( "alert('ERROR: project attribute not set');" );
		}

		$output =  "<div id=\"results-area-$this->resource_block_counter\"></div>";
		$output .= "<script type='text/javascript'>";

		$request['type'] = 'query';
		$request['query'] =  "a:Session.MDGroup.Project.Name=\"*" . $atts['project']. "*\"";
		$request['limit'] = isset($atts['limit']) ? $atts['limit'] : 10;

		$output .= sprintf("imdi_requests.push(%s);", json_encode($request));
		$output .= "</script>";

		$this->imdi_enqueue_scripts();
		$this->resource_block_counter++;
		return $output;
	}

	public function shortcode_imdi_query($atts) {

		$output =  "<div id=\"results-area-$this->resource_block_counter\"></div>";
		$output .= "<script type='text/javascript'>";


		if (!isset($atts['query']) || !isset($atts['path'])) {
			return __( "alert('ERROR: project or path/query attributes not set');" );
		}

		$request['type'] = 'query';
		$request['query'] =  "a:" . $atts['path'] . "=\"*" . $atts['query']. "*\"";
		$request['limit'] = isset($atts['limit']) ? $atts['limit'] : 10;

		$output .= sprintf("imdi_requests.push(%s);", json_encode($request));
		$output .= "</script>";

		$this->imdi_enqueue_scripts();
		$this->resource_block_counter++;
		return $output;
	}

	public function shortcode_imdi_sessions($atts) {

		$output =  "<div id=\"results-area-$this->resource_block_counter\"></div>";
		$output .= "<script type='text/javascript'>";

		$request['type'] = 'nodes';
		$request['node_ids'] = $atts['node_ids'];

		$request['res_only'] = isset($atts['res_only']) ? filter_var( $atts['res_only'], FILTER_VALIDATE_BOOLEAN ) : false;

		$output .= sprintf("imdi_requests.push(%s);", json_encode($request));
		$output .= "</script>";

		$this->imdi_enqueue_scripts();
		$this->resource_block_counter++;
		return $output;
	}



	public function shortcode_simplesearch($atts) {

		$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));

		/** Enqueue our scripts and styles */
		//wp_enqueue_script( 'imdi-archive-search-plugin' );
		//wp_enqueue_style( 'imdi-archive-search-plugin' );

		return $m->render('simplesearch', array(
			'searchButtonText' => $atts['button'],
			'placeholderText' => $atts['placeholder'])
		);
	}

	public function set_custom_title( $title, $id = null ) {
		
		return $title;
	}

	public function shortcode_resourcepage($atts) {


		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'imdi-archive-search-plugin' );

		
		$node = get_resource_info();

		$resource_url = $node['node']['file.uri'];
		$imdi_url = $node['parent']['file.uri'];


		$session_details = $node['parent_details'];

		// $xpath = "/a:METATRANSCRIPT/a:Session/a:Resources/*/a:ResourceLink[@ArchiveHandle='".$_GET['res']."']/..";
		// $resource_elem = $session_details->xpath($xpath);
		// $resource_elem = $resource_elem[0];

		// $resource_link = $resource_elem->ResourceLink;

		$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));

		$translated_text = array(
			'project' => __('Project', 'imdi'),
			'country' => __('Country', 'imdi'),
			'languages' => __('Languages', 'imdi'),
			'file' => __('File', 'imdi'),
			'goBackText' => __('Go back to search results', 'imdi'),
			'downloadInstructions' => __('To download, right click on link and select \'Save as...\'', 'imdi'),
			'getResourceText' => __('Get Resource:', 'imdi'),
			'accessDeniedText' => __('This resource is not publicly accessible.', 'imdi'),
			'permalinkText' => __('Permalink this Resource'),
			'sessionDetails' => __('Session Details'),
			'resources' => __('Resources'), 
			'preview' => __('Preview')			
			);
	
	
		    foreach ($session_details->Session->MDGroup->Content->Languages->children() as $language) {
	    		if ($languages) $languages .= ", "; 
	    		$languages .= $language->Name;
	    	}
	    	if ($languages == ' ') $languages = null;

	    $hdlString = $session_details['ArchiveHandle'];

	    $resources = array();
	    foreach ($session_details->Session->Resources->children() as $res) {

	    	$resources[] = imdi_handle_resource(new Resource($res, $imdi_url), $imdi_url, "blub");
	    }
		
	    $access = is_accessible($node['node']);

	    $type = imdi_get_resource_type($node['node']['node.mimetype']);
	    $title = $session_details->Session->MDGroup->Project->Title;
		$standard_params = array(
					'project' =>  $title,
					'project_class' => str_replace(' ', '_', $title),
					'country' => $session_details->Session->MDGroup->Location->Country,
					'languages' => $languages,
					'session_desc' => $session_details->Session->Description,
					'filename' => $resource_url,
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session¸->Title,
					'size' => ($node['node']['file.size'] > 0) ? formatSizeUnits($node['node']['file.size']) : false,
					'format' => $node['node']['node.mimetype'],
					'trans' => $translated_text,
					'access' => $access, // TODO
					'permalink' => "http://hdl.handle.net/" . substr($hdlString, strpos($hdlString, ':') + 1) . "@view",
					'resources' => $resources,
					'icon' => get_resource_icon($node['node']['node.mimetype']),
					'name' => $node['node']['node.name'],
					$type => true && $access
					
				);


		switch ($type) {
			case "video":

				wp_enqueue_script('please.js');

				$eaf_url = null;
				$eaf_id = null;
				foreach($session_details->Session->Resources->WrittenResource as $media_file) {
						if ($media_file->Format == "text/x-eaf+xml") {
							$eaf_url = http_build_url($imdi_url, array("path"=>(string)$media_file->ResourceLink), HTTP_URL_JOIN_PATH);
							$eaf_id = (string) $media_file->ResourceLink["ArchiveHandle"];
							break;
						}
				}

				wp_localize_script('elan-player', 'imdi_elan_player_object', array(
									"audio_files" => $audio_files,
									"eaf_url" => $eaf_url,
									"media_type" => "video",
									"eaf_id" => $eaf_id,
									"tier" => "Transkription A"
								));
				wp_enqueue_script('elan-player');

				$xml = file_get_contents($eaf_url);
				$xml = new SimpleXMLElement($xml);

				$tiers = $xml->xpath("/ANNOTATION_DOCUMENT/TIER/@TIER_ID");


				$standard_params['trans'] = array_merge($standard_params['trans'], array(
						"changeColors" => __("change colors", "imdi"),
						"showElan" => __("Show ELAN Annotations", "imdi")
					));

				$standard_params['tiers'] = $tiers;
				$standard_params['eaf_id'] = $eaf_id;
				break;


			case "eaf":
				
				wp_enqueue_script('wavesurfer');
				wp_enqueue_script('wavesurfer-elan');
				wp_enqueue_script('wavesurfer-timeline');
				wp_enqueue_script('wavesurfer-regions');
				wp_enqueue_script('wavesurfer-minimap');

				wp_enqueue_script('data-tables');
				wp_enqueue_style('data-tables');
				
				wp_enqueue_script('data-tables-colvis');
				wp_enqueue_style('data-tables-colvis');
				wp_enqueue_style('data-tables-colvis-jquery-ui');


				$audio_files = array();
				$file_types = array("audio/mp4", "audio/mp3", "audio/mpeg", "audio/ogg", "audio/x-wav", "video/mp4", "video/mpeg", "video/avi");
				foreach ($file_types as $type) {
					foreach($session_details->Session->Resources->MediaFile as $media_file) {
						if ($media_file->Format == $type) $audio_files[] = array (
								"url" => http_build_url($imdi_url, array("path"=>(string)$media_file->ResourceLink), HTTP_URL_JOIN_PATH),
								"type" => $type
							);
					}
				}


				wp_localize_script('elan-player', 'imdi_elan_player_object', array(
						"audio_files" => $audio_files,
						"eaf_url" => $resource_url,
						"media_type" => "audio"

					));
				wp_enqueue_script('elan-player');

				$standard_params['trans'] = array_merge($standard_params['trans'], array(
						"play" => __("Play", "imdi"),
						"pause" => __("Pause", "imdi"),
						"zoomIn" => __("Zoom In", "imdi"),
						"zoomOut" => __("Zoom Out", "imdi")
					));
			break;
		}

		return $m->render('resource_view', $standard_params);

	}

	public function shortcode_userbookmarks() {
		global $m;

		$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));


	 	global $_opt_imdi_user_saved_session;

	 	$user_saved_sessions = get_user_meta(get_current_user_id(), $_opt_imdi_user_saved_session);

	 	$output = '';

	 	foreach ($user_saved_sessions as $session_url)  {
	 		$output .= output_session($session_url, null);
	 	}

	 	return $output;
	}
		

	/**
	 * Handles the AJAX request for getting plugin data from the WordPress Plugin API.
	 *
	 * @since 0.1.0
	 */
	public function ajax() {
	
		/** Do a security check first */
		check_ajax_referer( 'imdi-archive-search-plugin-nonce', 'nonce' );
	
		/** Get servlet URL and topnode from plugin options */	
		global $_opt_imdi_servlet_url;
		global $_opt_imdi_topnode;
		global $_opt_imdi_max_results;


    	$query_topnode = get_option($_opt_imdi_topnode);
    	$servlet_url = get_option($_opt_imdi_servlet_url);

		if ( ! isset( $_GET['beginningAt'] ) || isset( $_GET['beginningAt'] ) && empty( $_GET['beginningAt'] ) )
			$beginningAt = '0';
		else
			$beginningAt = $_GET['beginningAt']; 

		if ( ! isset( $_GET['limit'] ) || isset( $_GET['limit'] ) && empty( $_GET['limit'] ) )
			$max_results = get_option($_opt_imdi_max_results);
		else
			$max_results = $_GET['limit'];		

		if ( ! isset( $_GET['details'] ) || isset( $_GET['details'] ) && empty( $_GET['details'] ) )
			$details = $_GET['details'];
		else
			$details = true;	

		/** Die early if there is no search term to look for */
		if ( ! isset( $_GET['query'] ) || isset( $_GET['query'] ) && empty( $_GET['query'] ) )
			die( __( 'No search term was entered...', 'imdi' ) );
		
		/** Now that we are verified, let's make our request to get the data */
		if ( ! class_exists( 'WP_Http' ) )
			require( ABSPATH . WPINC . '/class-http.php' );
		
		/** Args we want to send to the plugin API (the request must be an object) */	
		$query_term = (stripslashes( $_GET['query']));


		$query_type = "simple";

		if (substr($query_term, 0, 2) == "a:") {
		   $query_type = "advanced";
		   $query_term = str_replace("\\\\n", "\n", substr($query_term, 2));

		   //echo $query_term;
		}
			
		$query_string = $servlet_url . 
			'?action=getMatches&first=' . $beginningAt . '&last='
			. strval(intval($beginningAt) + ($max_results - 1)) . 
			'&query=' 
			. urlencode($query_term) . 
			'&type='.$query_type.'&nodeid=MPI'
			. $query_topnode .  
			'%23&returnType=xml&includeUrl=true&includeTitle=true&includeResources=true';
			
		//echo $query_string;

		$request = wp_remote_get( 
			$query_string,
				array(
				'timeout'	=> 200,
				'sslverify' => false)
				);


		$response_code 	= wp_remote_retrieve_response_code( $request );
		$response_body 	= wp_remote_retrieve_body( $request );
		$response_xml = simplexml_load_string( $response_body );
			
		/** Bail out early if there are any errors */
		// if ( 200 != $response_code || is_wp_error( $response_xml ) || is_null( $response_xml ) ) {
		// 	die( __( 'No results', 'imdi' ) );
		// }
				
		/** Calling function to generate the response output from the returned xml */		
		$response = generate_response_output( $response_xml );

		/** Send the response back to our script and die */
		echo json_encode( array(
			'html' => $response,
			'query_url' => $query_string,
			));
		die;
	
	}

	public function ajax_get_nodes() {

		$nodes = explode(',', $_GET['node_ids']);
		$urls = Array();
		foreach ($nodes as $node) {
			$url = get_node_info($node)['file.uri'];
			$urls[] = $url;
			$output .= output_session($url, null, get_session_details($url), $_GET['res_only']);
		}


		echo json_encode( array(
			'html' => $output,
			'node_ids' => $_GET['node_ids'],
			'urls' => $urls
			));

		die();

	}



	public function ajax_occurrences() {

		/** Do a security check first */
		check_ajax_referer( 'imdi-archive-search-plugin-nonce', 'nonce' );

		global $_opt_imdi_servlet_url;
		global $_opt_imdi_topnode;


		$query_topnode = get_option($_opt_imdi_topnode);
    	$servlet_url = get_option($_opt_imdi_servlet_url);

    	if ( ! isset( $_GET['path'] ) || isset( $_GET['path'] ) && empty( $_GET['path'] ) )
			die( __( 'Get ocurrences: no path', 'imdi' ) );

		/** Now that we are verified, let's make our request to get the data */
		if ( ! class_exists( 'WP_Http' ) )
			require( ABSPATH . WPINC . '/class-http.php' );
	
		$path = (stripslashes( $_GET['path']));

		$query_string = $servlet_url . 
			'?request=getOccurrences&' .
			'path=' . $path . '&'.
			'nodeid=MPI' . $query_topnode . '%23'; 
	
		$request = wp_remote_get( 
			$query_string,
				array(
				'timeout'	=> 400,
				'sslverify' => false)
				);

		// //echo "<![CDATA[" . var_dump($request) . "]]>";

		$response_code 	= wp_remote_retrieve_response_code( $request );
		$response_body 	= wp_remote_retrieve_body( $request );
	

		// $response = http_get($query_string);
		// $response_body = http_parse_message($response)->body;


		$response_xml = simplexml_load_string( str_replace("&", "&amp;", $response_body));

		//echo $response_body;
		//echo $query_string;
		/** Bail out early if there are any errors */
		// if ( 200 != $response_code || is_wp_error( $response_xml ) || is_null( $response_xml ) ) {
		// 	echo $response_xml;
		// 	die( __( 'No results', 'imdi-archive-search-plugin' ) );
		// }

		/** Calling function to generate the response output from the returned xml */		

		if($_GET['responseType'] == 'category')  
			$response_output = $this->output_category($_GET['title'], parse_occurrences( $response_xml ));
		if ($_GET['responseType'] == 'autocomplete') {
			$response_output = array();
			foreach($response_xml->MetadataCVEntry as $entry) {
				//$item['name'] = $entry->DisplayName;
				$response_output[] = (string) $entry->DisplayName;
			}
		}

		echo json_encode($response_output);
		die;
	}	


	public function ajax_output_category() {

		$items = json_decode(stripslashes_deep($_GET['items']), true);

		foreach ($items as & $item) {
			$item['href'] = "?query=" . $item['query'];
		}

		echo json_encode($this->output_category($_GET['title'], $items));
		die();
	}


function ajax_toggle_save_session() {
	// global $_opt_imdi_user_saved_session;


	// $session_url = $_GET['session_url'];

	// if (imdi_session_saved($session_url)) {
	// 	// if session is already saved, delete it
	// 	delete_user_meta(get_current_user_id(), $_opt_imdi_user_saved_session, $session_url);
	// 	echo json_encode("<p>add bookmark</p>");
	// } else {
	// 	// else add it
	// 	add_user_meta(get_current_user_id(), $_opt_imdi_user_saved_session, $session_url);
	// 	echo json_encode("<p>remove bookmark</p>");
	// }

	die();
}

function ajax_elanvtt() {
	
	$node = get_node_info(urldecode($_GET['node']));
	$tier_id = urldecode($_GET['tier']);
	$uri = $node['file.uri'];

	$xml = file_get_contents($uri);
	$xml = new SimpleXMLElement($xml);

	$timeslots = $xml->xpath("/ANNOTATION_DOCUMENT/TIME_ORDER")[0];

	//echo "WEBVTT\n\n\n";

	function formatMilliseconds($milliseconds) {
    $seconds = floor($milliseconds / 1000);
    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $milliseconds = $milliseconds % 1000;
    $seconds = $seconds % 60;
    $minutes = $minutes % 60;

    $format = '%u:%02u:%02u.%03u';
    $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
    return rtrim($time, '0');
	}

	function get_timestring($alignable_annotation, $timeslots) {
			$ts_ref_1 = $alignable_annotation["TIME_SLOT_REF1"];
			$ts_ref_2 = $alignable_annotation["TIME_SLOT_REF2"];
			$timeslot_start = $timeslots->xpath("TIME_SLOT[@TIME_SLOT_ID='$ts_ref_1']")[0];
			$timeslot_end = $timeslots->xpath("TIME_SLOT[@TIME_SLOT_ID='$ts_ref_2']")[0];

			//return formatMilliseconds($timeslot_start["TIME_VALUE"]). " --> " . formatMilliseconds($timeslot_end["TIME_VALUE"]) . "\n";
		return array(
			"start" => floatval($timeslot_start["TIME_VALUE"]),
			"end" => floatval($timeslot_end["TIME_VALUE"])
		);
	}

	$cues = array();

	$tier = $xml->xpath("/ANNOTATION_DOCUMENT/TIER[@TIER_ID='$tier_id']")[0];
	foreach($tier->xpath("ANNOTATION/child::node()") as $annotation) {
		$cue = null;
		if($annotation->getName() == "ALIGNABLE_ANNOTATION") {
			//echo get_timestring($annotation, $timeslots);
			//echo "<c.$tier_id>".$annotation->ANNOTATION_VALUE .  "\n<v.Wurst>hallo\n\n";
			$cue = get_timestring($annotation, $timeslots);
			$cue["content"] = (string) $annotation->ANNOTATION_VALUE;
		} else if ($annotation->getName() == "REF_ANNOTATION") {
			$annotation_id = $annotation["ANNOTATION_REF"];
			$annotation_ref = $xml->xpath("//ALIGNABLE_ANNOTATION[@ANNOTATION_ID='$annotation_id']")[0];
			//echo get_timestring($annotation_ref, $timeslots);
			//echo "<v.$tier_id> ".$annotation->ANNOTATION_VALUE . "\n<v.Wurst>hallo\n\n";
			$cue = get_timestring($annotation_ref, $timeslots);
			$cue["content"] = (string) $annotation->ANNOTATION_VALUE;
		}

		if ($cue) array_push($cues, $cue);
	}

	echo json_encode($cues);

	die();
}

// function ajax_elanvtt() {
	
// 	$node = get_node_info(urldecode($_GET['node']));
// 	$tier_id = urldecode($_GET['tier']);
// 	$uri = $node['file.uri'];

// 	$xml = file_get_contents($uri);
// 	$xml = new SimpleXMLElement($xml);

// 	$timeslots = $xml->xpath("/ANNOTATION_DOCUMENT/TIME_ORDER")[0];

// 	echo "WEBVTT\n\n\n";

// 	function formatMilliseconds($milliseconds) {
//     $seconds = floor($milliseconds / 1000);
//     $minutes = floor($seconds / 60);
//     $hours = floor($minutes / 60);
//     $milliseconds = $milliseconds % 1000;
//     $seconds = $seconds % 60;
//     $minutes = $minutes % 60;

//     $format = '%u:%02u:%02u.%03u';
//     $time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
//     return rtrim($time, '0');
// 	}

// 	function get_timestring($alignable_annotation, $timeslots) {
// 			$ts_ref_1 = $alignable_annotation["TIME_SLOT_REF1"];
// 			$ts_ref_2 = $alignable_annotation["TIME_SLOT_REF2"];
// 			$timeslot_start = $timeslots->xpath("TIME_SLOT[@TIME_SLOT_ID='$ts_ref_1']")[0];
// 			$timeslot_end = $timeslots->xpath("TIME_SLOT[@TIME_SLOT_ID='$ts_ref_2']")[0];

// 			return formatMilliseconds($timeslot_start["TIME_VALUE"]). " --> " . formatMilliseconds($timeslot_end["TIME_VALUE"]) . "\n";
// 	}

// 	$tier = $xml->xpath("/ANNOTATION_DOCUMENT/TIER[@TIER_ID='$tier_id']")[0];
// 	foreach($tier->xpath("ANNOTATION/child::node()") as $annotation) {
// 		if($annotation->getName() == "ALIGNABLE_ANNOTATION") {
// 			echo get_timestring($annotation, $timeslots);
// 			echo "<c.$tier_id>".$annotation->ANNOTATION_VALUE .  "\n<v.Wurst>hallo\n\n";
// 		} else if ($annotation->getName() == "REF_ANNOTATION") {
// 			$annotation_id = $annotation["ANNOTATION_REF"];
// 			$annotation_ref = $xml->xpath("//ALIGNABLE_ANNOTATION[@ANNOTATION_ID='$annotation_id']")[0];
// 			echo get_timestring($annotation_ref, $timeslots);
// 			echo "<v.$tier_id> ".$annotation->ANNOTATION_VALUE . "\n<v.Wurst>hallo\n\n";
// 		}
// 	}


// 	die();
// }



 public function output_category($title, $items)	{

	global $m;

		foreach ($items as & $item) {
			$item['href'] =  $item['href'].'&cat='.rawurlencode($title . "~~" . $item['name']);
		}

	$theTitle = $title;
	if (function_exists("qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage")) 
		$theTitle = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);

	return $m->render('category',
			array(
				"category" =>  $theTitle,
				"occurrences" => $items
	));
}

	/**
	 * Getter method for retrieving the object instance.
	 *
	 * @since 0.1.0
	 */
	public static function get_instance() {
	
		return self::$instance;
	
	}
	

}

	function output_session($session_url, $match, $session_details, $res_only = false) {

			global $m;
			global $_opt_imdi_archive_url;

      
	    	$a = array();
	    	
	    	$a['plugin_url'] = plugin_dir_url( __FILE__ );
	    	$a['class'] = "res_only";

	    	if (!$res_only) {
	    		$a['name'] = $session_details->Session->Name;
            	$a['title']= $session_details->Session->Title;
            		if ($a['title'] == '') $a[title] = false; 

            	$a['imdi-browser-link'] = get_option($_opt_imdi_archive_url) . "/ds/asv/?openhandle=".$session_details['ArchiveHandle'];
	    		$hdlString = $session_details['ArchiveHandle'];
	    		$a['permalink'] = "http://hdl.handle.net/" . substr($hdlString, strpos($hdlString, ':') + 1) . "@view";
	    		$a['class'] = "res";
	    	}
            


            $a['id'] = str_replace(' ', '', $session_details->Session->Name);
            
	 		
	 		if ($_GET['details']) {

	    		$a['details']['project'] = $session_details->Session->MDGroup->Project->Name;

	    		if ($a['details']['project'] == '' ) $a['details']['project'] = null;
	    		else {
	    			$a['details']['project_title'] = $session_details->Session->MDGroup->Project->Title;
	    			$a['details']['project_desc'] = $session_details->Session->MDGroup->Project->Description;
	    			$a['details']['project_contact_name'] = $session_details->Session->MDGroup->Project->Contact->Name;
	    			$a['details']['project_contact_address'] = $session_details->Session->MDGroup->Project->Contact->Address;
	    			$a['details']['project_contact_org'] = $session_details->Session->MDGroup->Project->Contact->Organisation;
	    			$a['details']['project_contact_email'] = $session_details->Session->MDGroup->Project->Contact->Email;
	    		}	

	    		$a['details']['country'] = $session_details->Session->MDGroup->Location->Country;
	    		if ($a['details']['country'] == '' ) $a['details']['country'] = null;
	    		else {


	    			$a['details']['continent'] = $session_details->Session->MDGroup->Location->Continent;
	    			$a['details']['region'] = $session_details->Session->MDGroup->Location->Region;
	    			$a['details']['address'] = $session_details->Session->MDGroup->Location->Address;
	    		}

	    		if ($session_details->Session->MDGroup->Content->Languages->children)
	    		{
	    			foreach ($session_details->Session->MDGroup->Content->Languages->children as $language) {
	    				if ($a['details']['languages']) $a['details']['languages'] .= ", "; 
	    				$a['details']['languages'] .= $language->Name;
	    			}

	    		}
	    		if ($a['details']['languages'] == ' ') $a['languages'] = null;

			}

			// echo var_dump($_GET);
			// echo "wurst";

	    	$a['description'] = $session_details->Session->Description;
	    	
	    	$a['imdi_session_url'] = $session_url;

	    	$a['saved_text'] = (imdi_session_saved($imdi_session_url) ? "remove bookmark" : "add bookmark");  

	    	$a['resources'] = array();   

	    	$resources = null;
	    	if ($match != null) $resources = $match->resources;
	    	else {
	    		$resources = array();
	    		foreach ($session_details->Session->Resources->children() as $res) {
	    			$resources[] = new Resource($res, $imdi_session_url);
	    		}
	    	}

	    	foreach ($resources as $res)
            {	

            	$res_page_title = $session_details->Session->Title;
            	if (empty($res_page_title)) $res_page_title = $session_details->Session->Name;
            	 $a['resources'][] = imdi_handle_resource($res,$session_details['ArchiveHandle'], $res_page_title);
	        }

	       $a['trans'] = array(
	       		"openInIMDIBrowser" => __("Open in IMDI Browser", 'imdi'),
	       		"permalink" => __("Permalink")
	       	);

	    	$output .= $m->render('session_details', $a);
	    	return $output;	
		}


	 function imdi_session_saved($session_url) {

	 	global $_opt_imdi_user_saved_session;

		$user_saved_sessions = get_user_meta(get_current_user_id(), $_opt_imdi_user_saved_session);
		if (@in_array($session_url, $user_saved_sessions)) return true;
			else return false;
	}	



function parse_occurrences($xml)
{

	$occurrences = array();

	foreach ($xml->MetadataCVEntry as $entry) {
		$occurrences[] = array( "name" => $entry->DisplayName,
								"count" => $entry->NumberOfOccurrences,
								"href" => 	"?query=" . 
								rawurlencode("a:" . $_GET['path'] . "=\"*" . str_replace(array('ó'), '?', $entry->Value) . "*\""));
	}

	return $occurrences;

}

function generate_response_output ($xml) {
	global $_opt_imdi_max_results;
	$max_results = get_option($_opt_imdi_max_results);

	$no_of_results = $xml->Result->MatchCount;
    if ($no_of_results > 0)
    {
	$match_list = array();

		$counter = 0;

        foreach ($xml->Result->Match as $match)
        {

        	$counter++;
	    $a_match = new Match((string)$match->Name,(string)$match->Title,(string)$match->URL,(string)$match->AccessLevel);

	    // !! TODO RESOURCES !!
           
          foreach ($match->Resource as $res) {
          	$a_match->add_resource(new Resource($res, null));
          }


          // if ($_GET['onlyRes'] == 'checked') {
          // 	if (empty($a_match->resources)) continue;
          // }
                    	
	// 	{
 //            if ( $show_only_accessible == false )
 //            	 {	
 //            	 $a_res = new Resource((string) $res->Name,(string) $res->Format,(string) $res->URL,(string) $res->AccessLevel);
	// 			 $a_match->add_resource($a_res);
	// 			 echo $a_match->resouces->name;
	// 			 }
 //            else 
	// 			 {
	// 				if (is_accessible($res->URL))
	// 					{
	// 					$a_res = new Resource((string) $res->Name,(string) $res->Format,(string) $res->URL,(string) $res->AccessLevel);
	// 					$a_match->add_resource($a_res);
	// 					echo $a_match->resouces->name;
 //                		}
 //            		}
	// 			 }    
	// if (!empty($a_match->resources))
	//    {


	 	  $match_list[] = $a_match;
	//    }
	// }
	}

    return output_matches($match_list, $no_of_results);
   
    // else
    // {
    //     echo __('no results');
    // }
	}
}
	
	function generate_pagelist($no_of_results)
	{
		global $_opt_imdi_max_results;

		$max_results = get_option($_opt_imdi_max_results);
		global $m;

		$pages = array();

		$number_of_pages = floor($no_of_results /  $max_results);
		for ($i = 0; $i <= $number_of_pages; $i++) {
			$pages[] = array(
				"page_number" => $i + 1,
				"page_link" => "?query=" . rawurlencode(stripslashes($_GET['query']))
				. "&beginningAt=" . $i  * $max_results
				. "&cat=" . $_GET['cat']
				. "&constraints=" . $_GET['constraints'],
				"active_page_class" => (floor($_GET['beginningAt'] / $max_results) == $i) ? "imdi-active-page" : ""
			);
		}

		return $m->render('pageindex_snippet', array("pages" => $pages));

	}	

	// function filter_parse($filter, $session_details) 
	// {
	// 	$country = (string)$session_details->Session->MDGroup->Location->Country;

	// 	if (!$filter['country']) $filter['country'] = array();

		
	// 	//echo "FILTER (" . var_dump($country_key) .", " .var_dump($filter) . ")";
	// 	if (array_key_exists($country, $filter['country'])) {
	// 		$filter['country'][$country]++;
	// 	} else {
	// 		$filter['country'][$country] = 1;
	// 	}

	// 	return $filter;
	// }

	function output_matches($matches, $no_of_results) {
		$pages = generate_pagelist($no_of_results);

		$results = array();
		//$filter = array();

		global $m;
	 	foreach ($matches as $one_match)
        {

 			$imdi_session_url = $one_match->url;
			
 			$session_details = get_session_details($imdi_session_url);



 			$results[] = output_session($imdi_session_url, $one_match, $session_details);
 			//$filter = filter_parse($filter, $session_details);     	
  		}

  		$output = $m->render('results', array(
  			"results" => $results,
  			"number" => "wurst",
  			"pagelist" => $pages,
  			'resultsTitle' => sprintf(__("%s sessions found.", "imdi"), $no_of_results) /*,
  			"filter" => $filter*/));

  		return $output;
}

function get_node_info($nodeID) {
	   	

	global $_opt_imdi_archive_url;
	$archive_url = get_option($_opt_imdi_archive_url);
	$request = xmlrpc_encode_request("LamusAPI.getNodeInfo", array((string)$nodeID));
   	$auth = base64_encode($_POST['user'].":".$_POST['token']);
   	$context = stream_context_create(array('http' => array(
      	'method' => "POST",
      	'header' => "Content-Type: text/xml",
      	'content' => $request
)));

   	$webservice = $archive_url . "/jkc/lamus/XmlRpcArchiveInfo";
	$file = file_get_contents($webservice, false, $context);

	return xmlrpc_decode($file);
}

# test accessibility for current user using ArchiveNodeInfo
function is_accessible($response) {

		$access =  $response["access.rights.read"];
      		if (/*($access === 'anyAuthenticatedUser') ||*/ ($access === 'everybody'))
      		{ return true; }
		else
      		{
      			global $current_user;
			get_currentuserinfo();
      			$user = $current_user->user_login;
			#$user = 'seba@mpi.nl';
      			$pos = strpos($access,$user);
			if($pos === false) 
			{ return false; }
			else
			{ 					
				return true; 
			}	
		}
}

# get NodeID from URL
function get_nodeID($url) {
   $archive_url = get_option($_opt_imdi_archive_url);

   $request = xmlrpc_encode_request("LamusAPI.getNodeInfo", array((string)$url));
   $auth = base64_encode($_POST['user'].":".$_POST['token']);
   $context = stream_context_create(array('http' => array(
      'method' => "POST",
      'header' => "Content-Type: text/xml",
      'content' => $request
)));

   	$webservice= get_option($_opt_imdi_archive_url) ."/jkc/lamus/XmlRpcArchiveInfo";
	$file = file_get_contents($webservice, false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) 
	{
      		echo "xmlrpc: $response[faultString] ($response[faultCode])";
   	} 
	else 
	{
      		$nodeID =  $response["query.internalid"];
      		return $nodeID;
	}
}


class Match {
	public $name = '';
	public $title = '';
	public $url = '';
	public $access_level = '';
	public $resources = array();


	function __construct($a_name,$a_title,$an_url,$an_access_level) 
	{
		$this->name = $a_name;
		$this->title = $a_title;
		$this->url = $an_url;
		$this->access_level = $an_access_level;
		$this->resources = array();
	}
	function add_resource($a_resource)
	{
		$this->resources[] = $a_resource;
	}
}

class Resource {
	public $name = '';
	public $format = '';
	public $url = '';
	public $access_level = '';
	public $handle = '';
	public $id = '';

	function __construct($xml, $imdi_session_url) 
	{

		if (($xml->getName() == "MediaFile") ||  ($xml->getName() == "WrittenResource")) { 
			$this->name = basename($xml->ResourceLink);
			$this->format = $xml->Format;
			$this->url = http_build_url($imdi_session_url, array("path"=>$xml->ResourceLink));
			$this->access_level = 1;//$an_access_level;
			$this->id = substr($xml->ResourceLink['ArchiveHandle'], 4);

		} else if ($xml->getName() == "Resource") {
			$this->name = $xml->Name;
			$this->format = $xml->Format;
			$this->access_level = $xml->AccessLevel;
			$this->url = $xml->URL;
			$this->id = $xml->Id;

		}

		return $this;
	}
}

function imdi_get_resource_type($mime) {
	
	switch($mime) {

			case "text/x-eaf+xml": return "eaf";
			case "application/pdf": return "pdf";	
			case (preg_match('/^text/', $mime) ? true : false): return "text";
			case (preg_match('/^audio/', $mime) ? true : false): return "audio";
			case (preg_match('/^image/', $mime) ? true : false): return "image";
			case (preg_match('/video\/(?!mpeg-1)/', $mime) ? true : false): return "video";

			default:
				return "unknown";
		} 
}

function imdi_handle_resource($res, $imdi_session_url, $title) {
	global $m;

	$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
	));

	// switch ($node['node']['node.mimetype']) {
		// 	case (strpos((string)$node['node']['node.mimetype'], "video")):
		// 		if ($imdi_resource_node_info['node.mimetype'] == "video/x-mpeg1")
		// 			return $m->render('resource_view_default', $standard_params);
		// 		return $m->render('resource_view_video', $standard_params);
		// 	break;
		// 	case strpos((string)$node['node']['node.mimetype'], "audio"):
		// 		if ($node['node']['node.mimetype'] == "audio/x-wav")
		// 			return $m->render('resource_view_wav', $standard_params);
		// 		return $m->render('resource_view_audio', $standard_params);

		// 	break;
		// 	case strpos((string)$node['node']['node.mimetype'], "image"):
		// 		return $m->render('resource_view_image', $standard_params);
		// 	break;	
		// 	case strpos((string)$node['node']['node.mimetype'], "eaf")>0: {
		// 		wp_enqueue_script('wavesurfer');
		// 		wp_enqueue_script('wavesurfer-elan');
		// 		wp_enqueue_script('wavesurfer-timeline');


		// 		$audio_files = array();
		// 		$file_types = array("audio/mp4", "audio/mp3", "audio/mpeg", "audio/ogg", "audio/x-wav", "video/mp4", "video/mpeg", "video/avi");
		// 		foreach ($file_types as $type) {
		// 			foreach($session_details->Session->Resources->MediaFile as $media_file) {
		// 				if ($media_file->Format == $type) $audio_files[] = array (
		// 						"url" => http_build_url($imdi_url, array("path"=>(string)$media_file->ResourceLink), HTTP_URL_JOIN_PATH),
		// 						"type" => $type
		// 					);
		// 			}
		// 		}


		// 		wp_localize_script('elan-player', 'imdi_elan_player_object', array(
		// 				"audio_files" => $audio_files,
		// 				"eaf_url" => $resource_url
		// 			));
		// 		wp_enqueue_script('elan-player');

		// 		$standard_params['trans'] = array_merge($standard_params['trans'], array(
		// 				"play" => __("Play", "imdi"),
		// 				"pause" => __("Pause", "imdi")
		// 			));

		// 		return $m->render('resource_view_eaf', $standard_params);
		// 	}
		// 	break;
		// 	case strpos((string)$node['node']['node.mimetype'], "text"):
		// 		return $m->render('resource_view_text', $standard_params);

		// 	break;
			
		// 	case strpos((string)$node['node']['node.mimetype'], "pdf")>0:
		// 		return $m->render('resource_view_pdf', $standard_params);

		// 	break;
		// 	default:
		// 		return __("Unknown media type", "imdi").": ".(string)$node['node']['node.mimetype'] . "(".strpos($node['node'], "video") .")";
		// }

	return $m->render('ressource_snippet', array(	'listimageURL' => get_resource_icon($res->format),
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("imdi-resource")->ID,
													'imdiURL' => $imdi_session_url,
													'fileName' => $res->name,
													'title' => $title,
													'id' => $res->id,
													));
}

function get_access_image($imdi_resource_access_level) {
                switch($imdi_resource_access_level)
                {
                    case 1:
                    	$image = "access_level_1.png";
                    	$text = __('Access level 1: openly accessible', 'imdi');
                    break;
                    case 2:
                    	$image = "access_level_2.png";
                        $text = __('Access level 2: accessible to registered users', 'imdi');
                    break;
                    case 3:
                    	$image = "access_level_3.png";
                        $text = __('Access level 3: access can be requested', 'imdi');
                    break;
                    case 4:
                    	$image = "access_level_4.png";
                        $text = __('Access level 4: not accessible', 'imdi');
                    break;
                    default:
                    	return false;
		}

	return array(
		"src" => plugin_dir_url( __FILE__ ) . "/images/" . $image,
		"text" => $text." (".$imdi_resource_access_level.")"
		);
}

function get_resource_icon($type) {
	switch ($type) {
		case "image/png": $icon = "png.png"; break;
		case "image/tiff": $icon = "tiff.png"; break;
		case "image/jpeg": $icon = "jpg.png"; break;
		case "video/x-mpeg1": 
		case "video/x-mpeg2":
			$icon = "mpg.png";
		break;
		case "video/mp4": case "audio/mp4": $icon = "mp4.png"; break;
		case "audio/x-wav": $icon = "wav.png"; break;
		case "text/x-eaf+xml": $icon = "imdi_eaf.png"; break;
		case "application/pdf": $icon = "pdf.png"; break;
		case "text/html": $icon = "html.png"; break;
		default: $icon = "_blank.png";
	}

	return plugin_dir_url( __FILE__ ) . "images/icons/" . $icon;
}

function get_session_details($url)
{

		
		$the_url = $url;
		if (strpos($url, 0, 4) == "http") {
			$response = get_node_info($url);
			$the_url = $response['file.uri'];
		}

		$request = wp_remote_get( 
			$the_url,
				array(
				'timeout'	=> 400,
				'sslverify' => false)
				);

		#echo $url;
		#echo "<![CDATA[" . var_dump($request) . "]]>"; die;

		$response_code 	= wp_remote_retrieve_response_code( $request );
		$response_body 	= wp_remote_retrieve_body( $request );

    $xml = simplexml_load_string($response_body);
 
 	if (!$xml) {
 		echo "DETAILS FAILED: ".$url;
		global $wp_query;
 		echo "<pre>";
 		var_dump($wp_query->query_vars);
 		echo $response_body;
		echo $response_code;
		echo $the_url;
		echo "</pre>";
 		die;
 		//echo var_dump($request);
 	}

    $xml->RegisterXPathNamespace("a", "http://www.mpi.nl/IMDI/Schema/IMDI");
    return $xml;

}

  function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}

/** Instantiate the class */
$imdi_search_plugin = new IMDI_Search_Plugin;
  
