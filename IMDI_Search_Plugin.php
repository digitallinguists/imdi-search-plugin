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


// Initialize mustache template engine
require 'mustache.php/src/Mustache/Autoloader.php';
Mustache_Autoloader::register();

$m = NULL;
 

 
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
			'categories' => get_option($_opt_imdi_categories)
		);

		//echo "CAT".var_dump(get_option($_opt_imdi_categories));

		wp_localize_script( 'imdi-archive-search-plugin', 'imdi_archive_search_plugin_object', $args );
		
		/** Register our style */
		wp_register_style( 'imdi-archive-search-plugin', plugins_url( '/css/style.css', __FILE__ ) );
	

		/** Register wavesurfer for ELAN player */
		wp_register_script( 'wavesurfer', plugins_url( '/js/wavesurfer/wavesurfer.min.js', __FILE__ ));
		wp_register_script( 'wavesurfer-elan', plugins_url( '/js/wavesurfer/wavesurfer.elan.js', __FILE__ ));
		wp_register_script( 'elan-player', plugins_url( '/js/elan-player.js', __FILE__ ));

		/** history.js for legacy browser history control */

		wp_register_script( 'history.js', plugins_url( '/history.js/scripts/bundled/html4+html5/jquery.history.js', __FILE__));

		
		/** responsive table styles */
		wp_register_style('responsive_tables', plugins_url( '/css/resultstable.css', __FILE__ ));
	
/** register maps plugin */
		wp_register_script( 'leaflet_maps', 'http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.js');
		wp_register_style( 'leaflet_maps', 'http://cdn.leafletjs.com/leaflet-0.7.2/leaflet.css');


	}
	
	/**
	 * Outputs the shortcode for the plugin.
	 *
	 * @since 0.1.0
	 */
	public function shortcode() {
	
		/** Enqueue our scripts and styles */
		wp_enqueue_script( 'imdi-archive-search-plugin' );
		wp_enqueue_style( 'imdi-archive-search-plugin' );

		wp_enqueue_script( 'history.js' );

		wp_enqueue_script('leaflet_maps');
		wp_enqueue_style('leaflet_maps');

		wp_enqueue_style('responsive_tables');

		
		$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));

		$output =  $m->render('imdi_query', array('inputTitle' => __('Input', 'imdi'),
													'placeholderText' => __( 'Type your search term here', 'imdi' ),
													'submitButtonText' => __( 'Search the Archive', 'imdi' ),
													'resultsTitle' => __("Results", "imdi"),
													'categoriesTabTitle' => __("Categories", "imdi"),
													'fulltextSearchTabTitle' => __("Fulltext search", "imdi"),
													'advancedSearchTabTitle' => __("Advanced search", "imdi")
													));

		$_GET = stripslashes_deep($_GET);

		$output .= "<script type='text/javascript'> var _GET = " . (!empty($_GET)?json_encode($_GET):'null') ."; </script>";
 

		return $output;
	
	}

	public function shortcode_simplesearch() {

			$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));

		/** Enqueue our scripts and styles */
		wp_enqueue_script( 'imdi-archive-search-plugin' );
		wp_enqueue_style( 'imdi-archive-search-plugin' );

		return $m->render('simplesearch', array('searchButtonText' => __("search", "imdi")));
	}

	public function shortcode_resourcepage($atts) {

		$session_details = get_session_details($_GET["imdi_url"]);
		$resource_elem = $session_details->xpath('//Resources/*/ResourceLink[text()="' . $_GET['url'] . '"]');

		$m = new Mustache_Engine(array(
    		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/templates', array('extension' => '.html')),
		));

		$trans = array(
			'project' => __('Project', 'imdi'),
			'country' => __('Country', 'imdi'),
			'languages' => __('Languages', 'imdi'),
			'file' => __('File', 'imdi'),
			'goBackText' => __('Go back to search results', 'imdi'),
			);

		$output = '';
	
		switch ($atts['type']) {
			case 'video':
				return $m->render('video-page', array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					//'languages' => get_languages($_GET["imdi_session_url"]),
					'session_desc' => $session_details->Session->Description,
					'filename' => $_GET['url'],
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'trans' => $trans
				));
			break;
			case 'audio':
				return $m->render('audio-page', array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					//'languages' => get_languages($_GET["imdi_session_url"]),
					'session_desc' => $session_details->Session->Description,
					'filename' => $_GET['url'],
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'trans' => $trans
				));
			break;
			case 'image':
				return $m->render('image-page', array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					//'languages' => get_languages($_GET["imdi_session_url"]),
					'session_desc' => $session_details->Session->Description,
					'filename' => $_GET['url'],
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'trans' => $trans
				));
			break;	
			case 'text':
				return $m->render('text-page', array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					//'languages' => get_languages($_GET["imdi_session_url"]),
					'session_desc' => $session_details->Session->Description,
					'filename' => $_GET['url'],
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'trans' => $trans
				));
			break;
			case 'eaf':
				wp_enqueue_script('wavesurfer');
				wp_enqueue_script('wavesurfer-elan');
				wp_enqueue_script('elan-player');
				return $m->render('eaf-page', array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					//'languages' => get_languages($_GET["imdi_session_url"]),
					'session_desc' => $session_details->Session->Description,
					'filename' => $_GET['url'],
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'trans' => $trans
				));
			break;
			case 'pdf':
				return $m->render('pdf-page', array(
					'project' =>  $session_details->Session->MDGroup->Project->Title,
					'country' => $session_details->Session->MDGroup->Location->Country,
					//'languages' => get_languages($_GET["imdi_session_url"]),
					'session_desc' => $session_details->Session->Description,
					'filename' => $_GET['url'],
					'session_name' => $session_details->Session->Name,
					'session_title' => $session_details->Session->Title,
					'trans' => $trans
				));
			break;
			default:
				return __("No media type specified in shortcode", "imdi");
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

		if (substr($query_term, 0, 2) == "a:") {
		   $query_type = "advanced";
		   $query_term = str_replace("\\\\n", "\n", substr($query_term, 2));
		   echo $query_term;
		}
			
		$query_string = $servlet_url . 
			'?action=getMatches&first=' . $beginningAt . '&last='
			. strval(intval($beginningAt) + intval($max_results) -1) .  
			'&query=' 
			. rawurlencode($query_term) . 
			'&type='.$query_type.'&nodeid=MPI'
			. $query_topnode .  
			'%23&returnType=xml&includeUrl=true&includeTitle=true&includeResources=true';
			
		//echo htmlentities($query_string);

		$request = wp_remote_get( 
			$query_string,
				array(
				'timeout'	=> 200)
				);

		$response_code 	= wp_remote_retrieve_response_code( $request );
		$response_body 	= wp_remote_retrieve_body( $request );
		$response_xml = simplexml_load_string( $response_body );
			
		/** Bail out early if there are any errors */
		if ( 200 != $response_code || is_wp_error( $response_xml ) || is_null( $response_xml ) ) {
			echo $response_xml;
			die( __( 'No results', 'imdi' ) );
		}
				
		/** Calling function to generate the response output from the returned xml */		
		$response = generate_response_output( $response_xml );
		
		/** Send the response back to our script and die */
		echo json_encode( $response );
		die;
	
	}




	public function ajax_occurrences() {

		/** Do a security check first */
		//check_ajax_referer( 'imdi-archive-search-plugin-nonce', 'nonce' );

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
	
		// $request = wp_remote_get( 
		// 	$query_string,
		// 		array(
		// 		'timeout'	=> 400)
		// 		);

		// //echo "<![CDATA[" . var_dump($request) . "]]>";

		// $response_code 	= wp_remote_retrieve_response_code( $request );
		// $response_body 	= wp_remote_retrieve_body( $request );
	

		$response = http_get($query_string);
		$response_body = http_parse_message($response)->body;


		$response_xml = simplexml_load_string( $response_body );


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
	global $_opt_imdi_user_saved_session;


	$session_url = $_GET['session_url'];

	if (imdi_session_saved($session_url)) {
		// if session is already saved, delete it
		delete_user_meta(get_current_user_id(), $_opt_imdi_user_saved_session, $session_url);
		echo json_encode("<p>add bookmark</p>");
	} else {
		// else add it
		add_user_meta(get_current_user_id(), $_opt_imdi_user_saved_session, $session_url);
		echo json_encode("<p>remove bookmark</p>");
	}

	die();
}


 public function output_category($title, $items)	{

	global $m;

	return $m->render('category',
			array(
				"category" =>  $title,
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

      
	    	$a = array();

            $a['name'] = $session_details->Session->Name;
            $a['title']= $session_details->Session->Title;
	 


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


	    	foreach ($session_details->Session->MDGroup->Content->Languages->children() as $language) {
	    		$a['languages'] .= $language->Name . " ";
	    	}

	    	if ($a['languages'] == ' ') $a['languages'] = null;



	    	$a['description'] = $session_details->Session->Description;
	    	
	    	$a['imdi_session_url'] = $session_url;

	    	$a['saved_text'] = (imdi_session_saved($imdi_session_url) ? "remove bookmark" : "add bookmark");  

	    	$a['resources'] = array();   

	    	//$output .= "<br />hallo" . htmlentities(var_dump($session_details->Session->Resources->children())) . "<br />";


	    	if ($match != null) $resources = $match->resources;
	    	else $resources = $session_details->Session->Resources->children();

	    	foreach ($resources as $res)
            {	
            	//echo "addres: " . $var_dump($res);
                switch($res->format)
                {
                    case (strpos($res->format,"audio")):
                        $a['resources'][] = imdi_handle_audio($res, $session_url);
                        break;
                    case (strpos($res->format,"video")):
                        $a['resources'][] = imdi_handle_video($res,$session_url);
                        break;
                    case (strpos($res->format,"eaf")>0):
                        $a['resources'][] = imdi_handle_annotation($res,$session_url);
                    	break;
		    		case (strpos($res->format,"text")):
                        $a['resources'][] = imdi_handle_text($res,$session_url);
                        break;
                    case (strpos($res->format,"image")):
                        $a['resources'][] = imdi_handle_image($res,$session_url);
                        break;
                    case (strpos($res->format,"pdf")>0):
                        $a['resources'][] = imdi_handle_pdf($res,$session_url);
                        break;
                        default:
	           }

	        }

	       

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
								rawurlencode("a:" . $_GET['path'] . "=\"*" . $entry->Value . "*\""));
	}

	return $occurrences;

}

function generate_response_output ($xml) {
	global $_opt_imdi_max_results;

	$no_of_results = $xml->Result->MatchCount;
    if ($no_of_results > 0)
    {
	$match_list = array();
        foreach ($xml->Result->Match as $match)
        {
	    $a_match = new Match((string)$match->Name,(string)$match->Title,(string)$match->URL,(string)$match->AccessLevel);

	    // !! TODO RESOURCES !!
           
          foreach ($match->Resource as $res) {
          	$a_match->add_resource(new Resource($res, null));
          }
                    	
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
    output_matches($match_list, $no_of_results);
   
    // else
    // {
    //     echo __('no results');
    // }
	}
}
	
	function output_pagelist($no_of_results)
	{
		global $_opt_imdi_max_results;

		$max_results = get_option($_opt_imdi_max_results);
		global $m;

		$pages = array();

		$number_of_pages = floor($no_of_results /  $max_results);
		for ($i = 0; $i <= $number_of_pages; $i++) {
			$pages[] = array(
				"page_number" => $i+1,
				"page_link" => "?query=" . stripslashes($_GET['query']) . "&beginningAt=" . $i *  $max_results 
			);
		}

		echo $m->render('pageindex_snippet', array("pages" => $pages));

	}	

	function filter_parse($filter, $session_details) 
	{
		$country = (string)$session_details->Session->MDGroup->Location->Country;

		if (!$filter['country']) $filter['country'] = array();

		
		//echo "FILTER (" . var_dump($country_key) .", " .var_dump($filter) . ")";
		if (array_key_exists($country, $filter['country'])) {
			$filter['country'][$country]++;
		} else {
			$filter['country'][$country] = 1;
		}

		return $filter;
	}

	function output_matches($matches, $no_of_results) {

		output_pagelist($no_of_results);

		$results = array();
		$filter = array();

		global $m;
	 	foreach ($matches as $one_match)
        {

 			$imdi_session_url = $one_match->url;

 			$session_details = get_session_details($imdi_session_url);

 			$results[] = output_session($imdi_session_url, $one_match, $session_details);
 			$filter = filter_parse($filter, $session_details);     	
  		}

  		echo var_dump($filter);
  		echo $m->render('results', array(
  			"results" => $results,
  			"filter" => $filter));

  		output_pagelist($no_of_results);

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
   	} 
	else 
	{
      		$access =  $response["access.rights.read"];
      		if (($access === 'anyAuthenticatedUser') || ($access === 'everybody'))
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
   $request = xmlrpc_encode_request("LamusAPI.getNodeInfo", array((string)$url));
   $auth = base64_encode($_POST['user'].":".$_POST['token']);
   $context = stream_context_create(array('http' => array(
      'method' => "POST",
      'header' => "Content-Type: text/xml",
      'content' => $request
)));

   	$webservice="http://corpus1.mpi.nl/jkc/lamus/XmlRpcArchiveInfo";
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

function imdi_handle_video($res, $imdi_session_url) {
	global $m;

	return $m->render('ressource_snippet', array(	'listimageURL' => plugins_url() . '/IMDI-search-plugin/images/video.png',
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("video-page")->ID,
													'imdiURL' => $imdi_session_url,
													'imdiName' => $res->name,
													'imdiAccessLevel' => $res->access_level,
													'fileName' => $res->name,
													'url' => $res->url
													));
}

function imdi_handle_audio($res, $imdi_session_url) {
	global $m;

	return $m->render('ressource_snippet', array(	'listimageURL' => plugins_url() . '/IMDI-search-plugin/images/audio.png',
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("audio-page")->ID,
													'imdiURL' => $imdi_session_url,
													'imdiAccessLevel' => $res->access_level,
													'url' => $res->url,
													'fileName' => $res->name
													));
}

function imdi_handle_image($res, $imdi_session_url) {
 	global $m;

	return $m->render('ressource_snippet', array(	'listimageURL' => plugins_url() . '/IMDI-search-plugin/images/image.png',
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("image-page")->ID,
													'imdiURL' => $imdi_session_url,
													'imdiAccessLevel' => $res->access_level,
													'url' => $res->url,
													'fileName' => $res->name
													));
}

function imdi_handle_text($res, $imdi_session_url) {
	global $m;

	return $m->render('ressource_snippet', array(	'listimageURL' => plugins_url() . '/IMDI-search-plugin/images/text.png',
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("text-page")->ID,
													'imdiURL' => $imdi_session_url,
													'imdiAccessLevel' => $res->access_level,
													'url' => $res->url,
													'fileName' => $res->name
													));
}

function imdi_handle_pdf($res, $imdi_session_url) {
	global $m;

	return $m->render('ressource_snippet', array(	'listimageURL' => plugins_url() . '/IMDI-search-plugin/images/pdf.png',
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("pdf-page")->ID,
													'imdiURL' => $imdi_session_url,
													'imdiAccessLevel' => $res->access_level,
													'url' => $res->url,
													'fileName' => $res->name
													));
}

function imdi_handle_annotation($res, $imdi_session_url) {
	global $m;

	return $m->render('ressource_snippet', array(	'listimageURL' => plugins_url() . '/IMDI-search-plugin/images/text.png',
													'accessImage' => get_access_image($res->access_level),
													'blogRoot' => get_bloginfo('url'),
													'videoPageID' => get_page_by_path("eaf-page")->ID,
													'imdiURL' => $imdi_session_url,
													'imdiAccessLevel' => $res->access_level,
													'url' => $res->url,
													'fileName' => $res->name
													));
}

function get_access_image($imdi_resource_access_level) {
                switch($imdi_resource_access_level)
                {
                    case ($imdi_resource_access_level == 1):
                        return "<img src='" . plugins_url() . "/IMDI-search-plugin/images/access_level_1.png' title='" . __('Access level 1: openly accessible', 'imdi') . "' class='access_level_img'/>";
                    break;
                    case ($imdi_resource_access_level == 2):
                        return "<img src='" . plugins_url() . "/IMDI-search-plugin/images/access_level_2.png' title='" . __('Access level 2: accessible to registered users', 'imdi') . "' class='access_level_img'/>";
                    break;
                    case ($imdi_resource_access_level == 3):
                        return "<img src='" . plugins_url() . "/IMDI-search-plugin/images/access_level_3.png' title='" . __('Access level 3: access can be requested', 'imdi') . "' class='access_level_img'/>";
                    break;
                    case ($imdi_resource_access_level == 4):
                        return "<img src='" . plugins_url() . "/IMDI-search-plugin/images/access_level_4.png' title='" . __('Access level 4: not accessible', 'imdi') . "' class='access_level_img'/>";
                    break;
		}
}



function get_session_details($url)
{
	$body = http_parse_message(http_get($url))->body;
    $xml = simplexml_load_string($body);
    return $xml;

}

/** Instantiate the class */
$imdi_search_plugin = new IMDI_Search_Plugin;
  