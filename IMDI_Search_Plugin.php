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

$m = NULL;
 
function imdi_the_title( $title, $id ) {

		if ($id == get_page_by_path("imdi-resource")->ID)
			return $_GET['title'];

	return $title;
}

function imdi_wp_title( $title, $sep) {
	if (is_page('imdi-resource'))
		return $_GET['title'];
	else return $title;
}
add_filter( 'wp_title', 'imdi_wp_title', 10, 2 );
add_filter( 'the_title', 'imdi_the_title', 10, 2 );

 
 
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

		//add_action( 'wp_ajax_IMDI_make_media_thumbnail, array( $this, 'ajax_make_media_thumbnail' ) );				
		//add_action( 'wp_ajax_nopriv_IMDI_make_media_thumbnail', array( $this, 'ajax_make_media_thumbnailr_' ) );		


		add_action( 'wp_ajax_IMDI_toggle_save_session', array( $this, 'ajax_toggle_save_session' ) );				

	}
	
	/**
	 * Loads all the stuff to make the plugin run.
	 *
	 * @since 0.1.0
	 */
	public function init() {
	
	global $m; 

	$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
	));

		/** Load the plugin textdomain for internationalizing strings */
		load_plugin_textdomain( 'imdi', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_shortcode( 'imdi-archive-search-plugin', array( $this, 'shortcode' ) );
		add_shortcode( 'imdi-archive-simplesearch', array( $this, 'shortcode_simplesearch'));
		add_shortcode( 'imdi-archive-resource-page', array($this, 'shortcode_resourcepage'));
		add_shortcode( 'imdi-archive-user-bookmarks', array($this, 'shortcode_userbookmarks'));

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
		wp_register_script( 'wavesurfer-elan', plugins_url( '/js/wavesurfer/wavesurfer.elan.js', __FILE__ ));
		wp_register_script( 'wavesurfer-timeline', plugins_url( '/js/wavesurfer/wavesurfer.timeline.js', __FILE__ ));
		wp_register_script( 'elan-player', plugins_url( '/js/elan-player.js', __FILE__ ));

		/** history.js for legacy browser history control */

		wp_register_script( 'history.js', plugins_url( '/vendor/js/jquery.history.js', __FILE__));

		
		/** responsive table styles */
		wp_register_style('responsive_tables', plugins_url( '/css/resultstable.css', __FILE__ ));

		/** jQuery UI components */
		wp_register_script( 'jquery-ui', plugins_url( '/vendor/js/jquery-ui-1.10.4.custom.min.js', __FILE__ ));
		wp_register_style('jquery-ui', plugins_url( '/vendor/css/imdi-theme/jquery-ui-1.10.4.custom.css', __FILE__ ));
	
/** register maps plugin */
		wp_register_script( 'leaflet_maps', plugins_url('/vendor/js/leaflet.js', __FILE__));
		wp_register_style( 'leaflet_maps', plugins_url('/vendor/css/leaflet.css', __FILE__));


	}
	
	/**
	 * Outputs the shortcode for the plugin.
	 *
	 * @since 0.1.0
	 */
	public function shortcode() {

		wp_enqueue_script('jquery');

		wp_enqueue_script('jquery-ui');
		wp_enqueue_style('jquery-ui');
	
		/** Enqueue our scripts and styles */
		wp_enqueue_script( 'imdi-archive-search-plugin' );
		wp_enqueue_style( 'imdi-archive-search-plugin' );

		wp_enqueue_script( 'history.js' );

		wp_enqueue_script('leaflet_maps');
		wp_enqueue_style('leaflet_maps');

		
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

	public function shortcode_resourcepage($atts) {

		wp_enqueue_script( 'jquery' );
		wp_enqueue_style( 'imdi-archive-search-plugin' );


		$session_details = get_session_details($_GET["imdi_url"]);
		$xpath = '/a:METATRANSCRIPT/a:Session/a:Resources/*/a:ResourceLink[contains(., "' . $_GET['filename'] . '")]/..';
		$resource_elem = $session_details->xpath($xpath);
		$resource_elem = $resource_elem[0];

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
			'accessDeniedText' => __('This resource is not publicly accessible.', 'imdi')
			);
	
	
		    foreach ($session_details->Session->MDGroup->Content->Languages->children() as $language) {
	    		if ($languages) $languages .= ", "; 
	    		$languages .= $language->Name;
	    	}
	    	if ($languages == ' ') $languages = null;

		$standard_params = array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					'languages' => $languages,
					'session_desc' => $session_details->Session->Description,
					'filename' => http_build_url($_GET['imdi_url'], array("path"=>(string)$resource_elem->ResourceLink), HTTP_URL_JOIN_PATH),
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'size' => ($resource_elem->Size > 0) ? formatSizeUnits($resource_elem->Size) : false,
					'format' => $resource_elem->Format,
					'trans' => $translated_text,
					'access' => is_accessible(http_build_url($_GET['imdi_url'], array("path"=>(string)$resource_elem->ResourceLink), HTTP_URL_JOIN_PATH))
				);

		switch ((string)$resource_elem->Format) {
			case (strpos((string)$resource_elem->Format, "video")):
				if ($resource_elem->Format == "video/x-mpeg1")
					return $m->render('resource_view_default', $standard_params);
				return $m->render('resource_view_video', $standard_params);
			break;
			case strpos((string)$resource_elem->Format, "audio"):
				if ($resource_elem->Format == "audio/x-wav")
					return $m->render('resource_view_wav', $standard_params);
				return $m->render('resource_view_audio', $standard_params);

			break;
			case strpos((string)$resource_elem->Format, "image"):
				return $m->render('resource_view_image', $standard_params);
			break;	
			case strpos((string)$resource_elem->Format, "eaf")>0: {
				wp_enqueue_script('wavesurfer');
				wp_enqueue_script('wavesurfer-elan');
				wp_enqueue_script('wavesurfer-timeline');


				$audio_files = array();
				$file_types = array("audio/mp4", "audio/mp3", "audio/mpeg", "audio/ogg", "audio/x-wav", "video/mp4", "video/mpeg", "video/avi");
				foreach ($file_types as $type) {
					foreach($session_details->Session->Resources->MediaFile as $media_file) {
						if ($media_file->Format == $type) $audio_files[] = array (
								"url" => plugins_url("IMDI-search-plugin") . "/csproxy.php?csurl=" . 
									http_build_url($_GET['imdi_url'], array("path"=>(string)$media_file->ResourceLink), HTTP_URL_JOIN_PATH),
								"type" => $type
							);
					}
				}


				wp_localize_script('elan-player', 'imdi_elan_player_object', array(
						"audio_files" => $audio_files,
						"eaf_url" => plugins_url("IMDI-search-plugin") . "/csproxy.php?csurl=" . $standard_params['filename']
					));
				wp_enqueue_script('elan-player');

				$standard_params['trans'] = array_merge($standard_params['trans'], array(
						"play" => __("Play", "imdi"),
						"pause" => __("Pause", "imdi")
					));

				return $m->render('resource_view_eaf', $standard_params);
			}
			break;
			case strpos((string)$resource_elem->Format, "text"):
				return $m->render('resource_view_text', $standard_params);

			break;
			
			case strpos((string)$resource_elem->Format, "pdf")>0:
				return $m->render('resource_view_pdf', $standard_params);

			break;
			default:
				return __("Unknown media type", "imdi").": ".(string)$resource_elem->Format . "(".strpos((string)$resource_elem->Format, "video") .")";
		}

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
		$max_results = get_option($_opt_imdi_max_results);

		if ( ! isset( $_GET['beginningAt'] ) || isset( $_GET['beginningAt'] ) && empty( $_GET['beginningAt'] ) )
			$beginningAt = '0';
		else
			$beginningAt = $_GET['beginningAt']; 		

		/** Die early if there is no search term to look for */
		if ( ! isset( $_GET['query'] ) || isset( $_GET['query'] ) && empty( $_GET['query'] ) )
			die( __( 'No search term was entered...', 'imdi' ) );
		
		/** Now that we are verified, let's make our request to get the data */
		if ( ! class_exists( 'WP_Http' ) )
			require( ABSPATH . WPINC . '/class-http.php' );
		
		/** Args we want to send to the plugin API (the request must be an object) */	
		$query_term = (stripslashes( $_GET['query']));


		$query_type = "simple";

		if (substr($query_term, 0, 4) == "a%3A") {
		   $query_type = "advanced";
		   $query_term = str_replace("%5C%5Cn", "%0A", substr($query_term, 4));

		   //echo $query_term;
		}
			
		$query_string = $servlet_url . 
			'?action=getMatches&first=' . $beginningAt . '&last='
			. strval(intval($beginningAt) + ($max_results - 1)) . 
			'&query=' 
			. $query_term . 
			'&type='.$query_type.'&nodeid=MPI'
			. $query_topnode .  
			'%23&returnType=xml&includeUrl=true&includeTitle=true&includeResources=true';
			
		//echo htmlentities($query_string);

		$request = wp_remote_get( 
			$query_string,
				array(
				'timeout'	=> 200,
				'sslverify' => false)
				);

		//echo($query_string);

		$response_code 	= wp_remote_retrieve_response_code( $request );
		$response_body 	= wp_remote_retrieve_body( $request );
		$response_xml = simplexml_load_string( $response_body );
			
		/** Bail out early if there are any errors */
		if ( 200 != $response_code || is_wp_error( $response_xml ) || is_null( $response_xml ) ) {
			//echo $response_xml;
			//echo $response_body;
			die( __( 'No results', 'imdi' ) );
		}
				
		/** Calling function to generate the response output from the returned xml */		
		$response = generate_response_output( $response_xml );
		
		/** Send the response back to our script and die */
		echo json_encode( array(
			'html' => $response,
			'query_url' => $query_string,
			));
		die;
	
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

	function output_session($session_url, $match, $session_details) {

			global $m;
			global $_opt_imdi_archive_url;

      
	    	$a = array();
	    	
	    	$a['plugin_url'] = plugin_dir_url( __FILE__ );

            $a['name'] = $session_details->Session->Name;
            $a['id'] = str_replace(' ', '', $session_details->Session->Name);
            $a['title']= $session_details->Session->Title;
            	if ($a['title'] == '') $a[title] = false; 
	 

	    	$a['project'] = $session_details->Session->MDGroup->Project->Name;

	    	if ($a['project'] == '' ) $a['project'] = null;
	    	else {
		    	$a['project_title'] = $session_details->Session->MDGroup->Project->Title;
		    	$a['project_desc'] = $session_details->Session->MDGroup->Project->Description;
		    	$a['project_contact_name'] = $session_details->Session->MDGroup->Project->Contact->Name;
		    	$a['project_contact_address'] = $session_details->Session->MDGroup->Project->Contact->Address;
		    	$a['project_contact_org'] = $session_details->Session->MDGroup->Project->Contact->Organisation;
		    	$a['project_contact_email'] = $session_details->Session->MDGroup->Project->Contact->Email;
	    	}	

			$a['country'] = $session_details->Session->MDGroup->Location->Country;
	    	if ($a['country'] == '' ) $a['country'] = null;
	    	else {
		    	
		    	
		    	$a['continent'] = $session_details->Session->MDGroup->Location->Continent;
		    	$a['region'] = $session_details->Session->MDGroup->Location->Region;
	    		$a['address'] = $session_details->Session->MDGroup->Location->Address;
	    	}

		if ($session_details->Session->MDGroup->Content->Languages->children)
		{
	    		foreach ($session_details->Session->MDGroup->Content->Languages->children as $language) {
	    			if ($a['languages']) $a['languages'] .= ", "; 
	    			$a['languages'] .= $language->Name;
	    		}
	    	
		}
		if ($a['languages'] == ' ') $a['languages'] = null;



	    	$a['description'] = $session_details->Session->Description;
	    	
	    	$a['imdi_session_url'] = $session_url;

	    	$a['saved_text'] = (imdi_session_saved($imdi_session_url) ? "remove bookmark" : "add bookmark");  

	    	$a['resources'] = array();   

	    	//$output .= "<br />hallo" . htmlentities(var_dump($session_details->Session->Resources->children())) . "<br />";

	    	$a['imdi-browser-link'] = get_option($_opt_imdi_archive_url) . "/ds/imdi_browser/?openhandle=".$session_details['ArchiveHandle'];


	    	if ($match != null) $resources = $match->resources;
	    	else $resources = $session_details->Session->Resources->children();

	    	foreach ($resources as $res)
            {	

            	$res_page_title = $session_details->Session->Title;
            	if (empty($res_page_title)) $res_page_title = $session_details->Session->Name;
            	 $a['resources'][] = imdi_handle_resource($res,$session_url, $res_page_title);
	        }

	       $a['trans'] = array(
	       		"openInIMDIBrowser" => __("Open in IMDI Browser", 'imdi')
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
				"page_link" => "?query=" . rawurlencode(stripslashes($_GET['query'])) . "&beginningAt=" . $i  * $max_results,
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

# test accessibility for current user using ArchiveNodeInfo
function is_accessible($url) {
   	global $_opt_imdi_archive_url;
	$archive_url = get_option($_opt_imdi_archive_url);
	$request = xmlrpc_encode_request("LamusAPI.getNodeInfo", array((string)$url));
   	$auth = base64_encode($_POST['user'].":".$_POST['token']);
   	$context = stream_context_create(array('http' => array(
      	'method' => "POST",
      	'header' => "Content-Type: text/xml",
      	'content' => $request
)));

   	$webservice = $archive_url . "/jkc/lamus/XmlRpcArchiveInfo";
	$file = file_get_contents($webservice, false, $context);
	$response = xmlrpc_decode($file);
	if (xmlrpc_is_fault($response)) 
	{
      		echo "xmlrpc: $response[faultString] ($response[faultCode])";
      		echo $url;
   	} 
	else 
	{
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
			// foreach ($xml->ResourceLink->attributes() as $attr => $val) {
			// 	if ($attr == "ArchiveHandle") $this->handle = $val; 
			// } 
		} else if ($xml->getName() == "Resource") {
			$this->name = $xml->Name;
			$this->format = $xml->Format;
			$this->access_level = $xml->AccessLevel;
			$this->url = $xml->URL;

		}
		return $this;
	}
}

function imdi_handle_resource($res, $imdi_session_url, $title) {
	global $m;


	return $m->render('ressource_snippet', array(	'listimageURL' => get_resource_icon($res->format),
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("imdi-resource")->ID,
													'imdiURL' => $imdi_session_url,
													'fileName' => $res->name,
													'title' => $title
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
		"src" => plugin_dir_url( __FILE__ ) . $image,
		"text" => $text."/".$imdi_resource_access_level
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

		$request = wp_remote_get( 
			$url,
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
 		echo "DETAILS FAILED ".$url;
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
  
