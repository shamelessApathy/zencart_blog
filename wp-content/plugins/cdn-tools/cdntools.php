<?php
/*
Plugin Name: CDN Tools
Plugin URI: http://langui.sh/cdn-tools
Description: CDN Tools is a plugin designed to help you drastically speed up your blog's load time by loading content onto a distribution network.  You can use a commercial CDN or just load some of your larger JS libraries for free from Google's servers!  At this time Cloud Files is the only supported CDN.
Author: Paul Kehrer
Version: 1.0
Author URI: http://langui.sh/
*/ 

/*  Copyright 2009-2010 Paul Kehrer

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
ini_set('memory_limit', '64M'); //up the memory limit for large CDN uploads...probably need to make this a configurable pref
set_time_limit(300); //300 seconds max...

define('CDNTOOLS_VERSION','1.0');

$dir_array = explode(DIRECTORY_SEPARATOR,dirname(__FILE__));
$cdntools_dir_name = array_pop($dir_array);
define('CDNTOOLS_DIR_NAME',$cdntools_dir_name);

define('CDNTOOLS_DEBUG',false);



$cdntools = new cdntools();

class cdntools {
	private $wp_scripts = null;
	private $gscripts = null;
	private $cdn_options = null;
	private $cdntools_googleajax = null;
	private $cdntools_primarycdn = null;
	private $cdntools_authname = null;
	private $cdntools_authkey = null;
	private $cdntools_adminscripts = null;
	private $cdntools_sideload_uploads = null;
	private $cdntools_logretention = null;
	private $cdntools_servicenet = null;
	private $uploaded_file_path = null;
	private $deleted_file_path_arr = array();
	private $wp_upload_url_path = null;
	private $wp_upload_basedir = null;
	private $uploads_use_yearmonth_folders = null;
	private $cdntools_advanced_options = null;
	
	function __construct() {
		//set up our hooks
		$this->bind_actions();
		$this->bind_filters();
		$this->upgrade_checker();
		
		/*
		set up the scripts we can fetch from google's cdn.  while scriptaculous and 
		jqueryui are available, they aren't modular so let's ignore them.
		*/
		$this->gscripts = array(
			'dojo' => 'dojo.xd',
			'jquery' => 'jquery.min',
			'mootools' => 'mootools-yui-compressed',
			'prototype' => 'prototype'
		);
		
		//obtain our options
		$this->cdntools_googleajax = (defined('CDNTOOLS_GOOGLEAJAX'))?CDNTOOLS_GOOGLEAJAX:get_option('cdntools_googleajax');
		$this->cdntools_primarycdn = (defined('CDNTOOLS_PRIMARYCDN'))?CDNTOOLS_PRIMARYCDN:get_option('cdntools_primarycdn');
		$this->cdntools_authname = (defined('CDNTOOLS_AUTHNAME'))?CDNTOOLS_AUTHNAME:get_option('cdntools_authname');
		$this->cdntools_authkey = (defined('CDNTOOLS_AUTHKEY'))?CDNTOOLS_AUTHKEY:get_option('cdntools_authkey');
		$this->cdntools_adminscripts = get_option('cdntools_adminscripts');
		$this->cdntools_sideload_uploads = get_option('cdntools_sideload_uploads');
		$this->cdntools_logretention = (defined('CDNTOOLS_LOGRETENTION'))?CDNTOOLS_LOGRETENTION:get_option('cdntools_logretention');
		$this->cdntools_servicenet = get_option('cdntools_servicenet');
		$this->cdntools_advanced_options = get_option('cdntools_advanced_options');
		$wp_upload_dir = wp_upload_dir();
		$this->wp_upload_url_path = $wp_upload_dir['baseurl'];
		$this->wp_upload_basedir = $wp_upload_dir['basedir'];
		$this->uploads_use_yearmonth_folders = get_option('uploads_use_yearmonth_folders');
		if($this->cdntools_primarycdn != false) {
			require(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.CDNTOOLS_DIR_NAME.DIRECTORY_SEPARATOR."cdn_classes".DIRECTORY_SEPARATOR.
					"{$this->cdntools_primarycdn}".DIRECTORY_SEPARATOR."loader.php");
		}
	}
	
	function bind_actions() {
		add_action('wp_ajax_cdn_attachment_upload_ajax', array($this, 'cdn_attachment_upload_ajax'));
		if(!defined('CDNTOOLS_AUTHKEY')) {
			//create the admin page if no wp-config constant is defined
			add_action('admin_menu', array($this,'add_admin_pages'));  //create admin page
		}
		add_action('wp_default_scripts', array($this, 'rewrite_wp_default_scripts'),900);
		add_action('wp_head', array($this,'wp_head_action'));  //add a tag to denote cdntools use
		add_action('post-flash-upload-ui', array($this,'cdn_post_upload_ui'));
		add_action('post-html-upload-ui', array($this,'cdn_post_upload_ui'));
		add_action('add_attachment', array($this,'cdn_attachment_upload'));
		add_action('delete_attachment', array($this,'cdn_attachment_delete'));
		add_action('admin_init', array($this,'cdntools_admin_init'));
	}

	function bind_filters() {
		add_filter('init',array($this,'disable_script_concatenation'));
		add_filter( 'print_scripts_array',array($this,"jquery_noconflict"),100);
		add_filter('wp_handle_upload', array($this,'handle_upload_filter')); //grab data about uploads
	//	add_filter('attachment_fields_to_edit', array($this,'attachment_fields_to_edit_filter'));
		add_filter('the_content', array($this,'cdn_media_url_rewrite'), 1000);  //this needs to run after any other filter that may alter URLs
		add_filter('wp_generate_attachment_metadata', array($this,'cdn_upload_resized_images'), 10, 2);
		add_filter('update_attached_file', array($this,'update_attached_file'), 10, 2); //upload edited images
		add_filter('wp_update_attachment_metadata', array($this,'cdn_upload_resized_images')); //hook same function to upload the edited image resizes
		add_filter('post_thumbnail_html', array($this,'cdn_media_url_rewrite'), 1000, 5); //hook the thumbnail filter to rewrite for CDN (added in 2.9)
	}
	
	
	function upgrade_checker() {
		if (is_admin()) {
			$upgrade = false;
			if (get_option('cdntools_version') != CDNTOOLS_VERSION) {
				$upgrade = true;
			}
			if ($upgrade) {
				$this->upgrader();
				add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>".sprintf('Please go to your <a href="%s">CDN Tools settings</a> now as the latest release introduces substantial changes.  CDN rewrites are currently disabled!', admin_url('options-general.php?page=cdn-tools.php'))."</p></div>';" ) );
			}
		}
	}

	/*we need to call this function whenever the update takes place.  after cdntools_version 1.0 is saved*/
	function upgrader() {
		update_option('cdntools_version',CDNTOOLS_VERSION);
		update_option('cdntools_container_name','cdntools'); //add new option for 1.0 with default value
		delete_option('cdntools_prefix'); //removed in 1.0.  used in 0.99 and earlier
		delete_option('cdntools_loadedfiles'); //for JS.  we don't sideload JS from 0.9x on
		delete_option('cdntools_baseuris'); //can't delete until we finish the upgrade.  removed in 1.0
	}
	
	//this will capture image edited files and probably other stuff
	function update_attached_file($filepath,$attachment_id) {
		$full_filepath = $this->file_path($filepath); //make the file path absolute if required
		$this->cdn_attachment_upload($attachment_id,$full_filepath); //$attachment_id is the same as $post_id so let's pass that to our attachment_upload function
		return $filepath;
	}
	
	//wp 2.8 concatenates scripts in the admin panel and this messes up the google ajax rewriting.  hook init and disable it for now.  revisit at some point
	function disable_script_concatenation() {
		global $concatenate_scripts;
		$concatenate_scripts = false;
	}
	
	function cdn_upload_resized_images($metadata,$attachment_id=false) {
		//uses wp_generate_attachment_metadata filter hook
		//sizes is a multi-dimensional array with elements of this structure
		/*[thumbnail] => Array
			(
				[file] => picture-1-150x150.png
				[width] => 150
				[height] => 150
			)*/
		if(is_array($metadata) && isset($metadata['sizes'])) {
			$file_array = explode(DIRECTORY_SEPARATOR,$metadata['file']);
			foreach($metadata['sizes'] as $data) {
				array_pop($file_array);
				array_push($file_array,$data['file']);
				$full_filepath = $this->file_path(implode(DIRECTORY_SEPARATOR,$file_array));
				$this->cdn_attachment_upload($attachment_id,$full_filepath); //$attachment_id is the same as $post_id so let's pass that to our attachment_upload function
			}
		}
		return $metadata;
	}


	function handle_upload_filter($data) {
		//grab the data we need via filter.  hacky hack hack and against the idea of filters.
		$this->uploaded_file_path = $data['file'];
		return $data;
	}

	function cdn_media_url_rewrite($data) {
		global $post; //this is the post object.
		$post_id = $post->ID;
		$args = array(
			'post_type' => 'attachment',
			'numberposts' => -1,
			'post_status' => null,
			'post_parent' => $post_id
			); 
		$attachments = get_posts($args);
		$sideload_status = array();
		if ($attachments) {
			foreach ($attachments as $attachment) {
				$sideload_status[] = get_post_meta($attachment->ID,'_cdntools_sideload_status',true);
			}
		}
		//if no false entries are present in the array then all attachments uploaded successfully so we can rewrite this post.
		//caveat: resized attachments or edited images could fail and this would NOT catch it. they are metadata associated with 
		//attachments and i dunno what to do about them yet
		$sideload_status = (!in_array(false,$sideload_status))?true:false;
		
		if($sideload_status && (get_option('cdntools_baseuri')) && ($this->cdntools_primarycdn) && ($this->wp_upload_url_path != '') ) {
			//only rewrite if the media is sideloaded, a CDN is selected, we have a base uri, and the wp_upload_url_path is not empty.
			//if the wp_upload_url_path is empty then their uploads dir probably isn't writable, which makes WP not know wtf
			$file_upload_url = trailingslashit($this->wp_upload_url_path);
			$file_upload_url = str_replace('/','\/',str_replace('://','://(www.)?',$file_upload_url));
			preg_match_all('/'.$file_upload_url.'([^"\']+)/',$data,$matches);

			//if a CNAME is set, use that for cdnuri
			$cdntools_cname = get_option('cdntools_cname');
			if(strpos($cdntools_cname,'.') !== false) {
				//contains a period, probably a FQDN. we could test a bit better here but for now assume valid
				//assumes no http/https in front
				$cdntools_cdnuri = 'http://'.trailingslashit($cdntools_cname);
			} else {
				$cdntools_cdnuri = trailingslashit(get_option('cdntools_baseuri'));
			}

			$patterns = array();
			$replacements = array();
			foreach($matches[2] as $value) {
				if($this->cdntools_primarycdn == 'cloudfiles') { //this needs to be abstracted
					$object_name = $value;
				} elseif ($this->cdntools_primarycdn == 'amazon') {
					$container_name = $thebucket; //TODO: how to store the s3 bucket name?!
					$object_name = $value;
				}
				$replacements[] = $cdntools_cdnuri.$object_name;
			}
			foreach($matches[0] as $urls) {
			    $patterns[] = '/'.str_replace('/','\/',$urls).'/';
			}
			$data = preg_replace($patterns,$replacements,$data);
		}
		return $data;
	}
	
	//incomplete function.  rewrite
	function attachment_fields_to_edit_filter($data) {
		/*if(isset($data['url']['html'])) {
			$data['url']['html'] = "<input type='text' class='urlfield' name='attachments[boom][cdnurl]' value='" . attribute_escape($this->cdn_attachment_url('boom')). "' />".$data['url']['html'];
		}*/
		return $data;
	}
	
	function cdn_attachment_url($postid) {
		return "yeah";
	}
	

	//file_to_upload must be a string with absolute path.  in contrast to delete, you must call this multiple times if you want to upload multiple files
	function cdn_attachment_upload($post_id,$file_to_upload = null) {
		if($this->cdntools_sideload_uploads == 1 && $this->cdntools_authname != null & $this->cdntools_authkey != null && $this->cdntools_primarycdn) {
			if($file_to_upload == null) {
				//if no file string passed, use the one generated by the upload filter
				$file_to_upload = $this->uploaded_file_path;
			}
			$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
			$return_array = $cdn_loader->attachment_upload($file_to_upload, $this->cdntools_logretention);
			if (!is_array($return_array)) {
				update_post_meta($post_id,'_cdntools_sideload_status',false);
				return $return_array;
			} else {
				update_post_meta($post_id,'_cdntools_sideload_status',true);
				$cdntools_baseuri = $return_array['baseuri'];
			}
			update_option('cdntools_baseuri',$cdntools_baseuri);
			return true;
		}
	}
	
	function cdn_attachment_upload_ajax() {
		error_reporting(E_ERROR | E_WARNING | E_PARSE);
		ini_set('display_errors',1);
		$upload_return = $this->cdn_attachment_upload($_POST['post_id'],$_POST['path']);
		if ($upload_return !== true) {
			echo $upload_return; //return the bubbled up exception
		} else {
			echo 'true';
		}
	}
	
	//this function is mostly stolen from get_attached_file in post.php in wordpress.  it attempts to correct for absolute vs relative paths.
	//if the if conditional is met it's a relative path
	function file_path($file) {
		if ( 0 !== strpos($file, '/') && !preg_match('|^.:\\\|', $file) && ( ($uploads = wp_upload_dir()) && false === $uploads['error'] ) ) {
			$file = $uploads['basedir'] . "/$file"; 
		}
		if(strpos($file,$uploads['basedir']) === 0) {
			/*
			if we find an absolute path but the upload basedir isn't in the string, this likely means that the blog has 
			been moved from one host to another...example: user had path /var/www/html/wp-content/uploads/2009/file.png 
			but moves to a host with the data in /home/wwwuser/htdocs/wp-content/uploads.  now the paths are broken.  
			this isn't the fault of CDN Tools but we have to (try to) deal with WP's mess
			*/
			//we need to determine the substring before the relative upload dir, then fix the path.  this is not easy...
		}
		return $file;
	}


	//files_to_delete must be an array of absolute paths.
	//three scenarios to call this hook:
	//files to delete is non-null, which isn't used in the code right now
	//wp 2.7 (need deleted_file_path_arr)
	//wp 2.8 (need to fetch info using post_id)
	function cdn_attachment_delete($post_id,$files_to_delete = null) { //$post_id will not be useful until 2.8 but it is provided by the action hook
		if($this->cdntools_sideload_uploads == 1 && $this->cdntools_authname != null & $this->cdntools_authkey != null && $this->cdntools_primarycdn) {
			if(!is_array($files_to_delete)) {
				global $wpdb;
				$result = $wpdb->get_results( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attached_file' , $post_id ) );
				$files_to_delete = array();
				foreach($result as $attachment) {
					//we only need the basedir from our upload info var
					$fullpath = $this->wp_upload_basedir.DIRECTORY_SEPARATOR.$attachment->meta_value;
					$files_to_delete[] = $fullpath;

					//check for metadata that has resized images
					//consider using wp_delete_file filter here instead of these methods.  see line 2902 from wp-includes/post.php
					$metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_metadata',$post_id ) ) );
					if(is_array($metadata) && isset($metadata['sizes'])) {
						$file_array = explode(DIRECTORY_SEPARATOR,$metadata['file']);
						foreach($metadata['sizes'] as $data) {
							array_pop($file_array);
							array_push($file_array,$data['file']);
							$filepath = $this->file_path(implode(DIRECTORY_SEPARATOR,$file_array));
							$files_to_delete[] = $filepath;
						}
					}
					//check for edited image data.  yes i don't understand the meta_key name either
					//consider using wp_delete_file filter here instead of these methods.  see line 2902 from wp-includes/post.php
					$backup_metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_backup_sizes',$post_id ) ) );
					if(is_array($backup_metadata)) {
						foreach($backup_metadata as $data) {
							array_pop($file_array); //same file_array from the attachment_metadata
							array_push($file_array,$data['file']);
							$filepath = $this->file_path(implode(DIRECTORY_SEPARATOR,$file_array));
							$files_to_delete[] = $filepath;
						}
					}
				}
			}
			$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
			foreach($files_to_delete as $path) {
				$cdn_loader->attachment_delete($path);
			}
			$this->deleted_file_path_arr = array(); //empty it out.
		}
	}
	
	function cdn_post_upload_ui() {
		if($this->cdntools_sideload_uploads == 1 && $this->cdntools_authname != null & $this->cdntools_authkey != null && $this->cdntools_primarycdn) {
			echo '<p style="color:green">CDN Side Loading Enabled.  All files will be uploaded to your CDN as well as the local WP uploads directory.</p>';
		}
	}
		
	function cdn_upload_all_attachments() {
		global $wpdb;
		$result = $wpdb->get_results( $wpdb->prepare( "select post_id,meta_value from $wpdb->postmeta WHERE meta_key = %s", '_wp_attached_file' ) );
		$uploadinfo = array();
		foreach($result as $attachment) {
			//we only need the basedir from our upload info var
			$fullpath = $this->file_path($attachment->meta_value);
			$uploadinfo[] = array('post_id'=>$attachment->post_id,'path'=>$fullpath);
			
			//check for metadata that has resized images
			$metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_metadata',$attachment->post_id ) ) );
			if(is_array($metadata) && isset($metadata['sizes'])) {
				$file_array = explode(DIRECTORY_SEPARATOR,$metadata['file']);
				foreach($metadata['sizes'] as $data) {
					array_pop($file_array);
					array_push($file_array,$data['file']);
					$filepath = $this->file_path(implode(DIRECTORY_SEPARATOR,$file_array));
					$uploadinfo[] = array('post_id'=>$attachment->post_id,'path'=>$filepath);
				}
			}
			$backup_metadata = unserialize( $wpdb->get_var( $wpdb->prepare( "select meta_value from $wpdb->postmeta WHERE meta_key = %s and post_id = %d", '_wp_attachment_backup_sizes',$attachment->post_id ) ) );
			if(is_array($backup_metadata)) {
				foreach($backup_metadata as $data) {
					array_pop($file_array); //same file_array from the attachment_metadata
					array_push($file_array,$data['file']);
					$filepath = $this->file_path(implode(DIRECTORY_SEPARATOR,$file_array));
					$uploadinfo[] = array('post_id'=>$attachment->post_id,'path'=>$filepath);
				}
			}

		}
		return $this->array2json($uploadinfo);
	}
	
	/* 
	loop through all registered scripts and compare to the files we have loaded to the CDN.
	*/
	function rewrite_wp_default_scripts(&$wp_scripts) {
		//check to see if we need to use googleajax CDN.  google overrides previous cdn'd scripts
		if($this->cdntools_googleajax) {
			foreach($wp_scripts->registered as $object) {
				if(array_key_exists($object->handle,$this->gscripts)) {
					$libname = $object->handle;
					$jsname = $this->gscripts[$libname];
					$ver = $object->ver;
					$transport = (is_ssl())?'https':'http'; //make it ssl if the site is ssl.
					$object->src = "$transport://ajax.googleapis.com/ajax/libs/$libname/$ver/$jsname.js";
				}
			}
		}
	}
	
	function wp_head_action() {
		echo '<!--CDN Tools v'.CDNTOOLS_VERSION."-->\n";
	}
	
	function cdnify_css($content) {
		global $wp_styles;
		//nobody uses wp_styles yet...moving on. come back to this later
		//function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	}
		
	//inspiration from use-google-libraries
	function jquery_noconflict($js) {
		$jquery_key = array_search( 'jquery', $js );
		if ($jquery_key === false || $this->cdntools_googleajax == false) {
			return $js;
		}
		//register the no conflict script
		$cdntools_url = (empty($_SERVER['HTTPS'])) ? WP_CONTENT_URL.'/plugins/'.CDNTOOLS_DIR_NAME : str_replace("http://", "https://", WP_CONTENT_URL.'/plugins/'.CDNTOOLS_DIR_NAME);
		wp_register_script('jquery-noconflict',$cdntools_url.'/cdn_classes/jquery-noconflict.js');
		array_splice( $js, $jquery_key, 1, array('jquery','jquery-noconflict'));
		return $js;
	}
	

	function cdn_remove_container($container_name) {
		$cdn_loader = new cdn_loader($this->cdntools_authname,$this->cdntools_authkey,$this->cdntools_servicenet);
		$cdn_loader->remove_container($container_name);
		global $wpdb;
		$result = $wpdb->query( "delete from $wpdb->postmeta WHERE meta_key = '_cdntools_sideload_status'" );
	}
	
	function cdntools_admin_init() {
		if(CDNTOOLS_DEBUG == true) {
			ini_set('display_errors',1);
		}
		register_setting( 'cdn-options-group', 'cdntools_googleajax', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_primarycdn', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_authname', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_authkey', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_adminscripts', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_sideload_uploads', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_logretention', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_servicenet', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_acl_ref', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_acl_ua', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_advanced_options', 'absint' );
		register_setting( 'cdn-options-group', 'cdntools_container_name', 'wp_filter_nohtml_kses' );
		register_setting( 'cdn-options-group', 'cdntools_cname', 'wp_filter_nohtml_kses' );
	}
	

	function add_admin_pages() {
		add_submenu_page('options-general.php', "CDN Tools", "CDN Tools", 10, "cdn-tools.php", array($this,"settings_output"));
	}

	function settings_output() {
		global $wp_scripts;
		if ( isset( $_POST['action'] ) ) {
			//flush wp super cache
			if(function_exists('wp_cache_no_postid')) {
				wp_cache_no_postid(0);
			}
			switch ( $_POST['action'] ) {
				case 'cdn_remove_attachments':
					$this->cdn_remove_container(get_option('cdntools_container_name'));
					$this->cdntools_sideload_uploads = 0;
					update_option('cdntools_sideload_uploads',0);
					update_option('cdntools_baseuri','');
				break;
				case 'cdn_upload_all_attachments':
					$this->cdntools_sideload_uploads = 1;
					update_option('cdntools_sideload_uploads',1);
					$json_arr = $this->cdn_upload_all_attachments();
				break;
				case 'cdn_upload_all_files':
					$this->cdntools_sideload_uploads = 1;
					update_option('cdntools_sideload_uploads',1);
					$json_arr = $this->cdn_upload_all_attachments();
				break;
				case 'cdn_remove_all_files':
				$this->cdn_remove_container(get_option('cdntools_container_name'));
					$this->cdntools_sideload_uploads = 0;
					update_option('cdntools_sideload_uploads',0);
					update_option('cdntools_baseuri','');
				break;
				case 'reset_cdntools':
					delete_option('cdntools_googleajax');
					delete_option('cdntools_primarycdn');
					delete_option('cdntools_baseuri');
					delete_option('cdntools_authname');
					delete_option('cdntools_authkey');
					delete_option('cdntools_adminscripts');
					delete_option('cdntools_sideload_uploads');
					delete_option('cdntools_logretention');
					delete_option('cdntools_servicenet');
					delete_option('cdntools_advanced_options');
					delete_option('cdntools_container_name');
					delete_option('cdntools_cname');
					$this->cdntools_googleajax = null;
					$this->cdntools_primarycdn = null;
					$this->cdntools_authname = null;
					$this->cdntools_authkey = null;
					$this->cdntools_adminscripts = null;
					$this->cdntools_sideload_uploads = null;
					$this->cdntools_logretention = null;
					$this->cdntools_servicenet = null;
					$this->cdntools_advanced_options = null;
			}
		}
		
		?>
		<div class="wrap">
			<h2>CDN Tools</h2>
			By <b>Paul Kehrer</b> ( <a href="http://langui.sh" target="_blank">Blog</a> | <a href="http://twitter.com/reaperhulk" target="_blank">Twitter</a> )
			<br/>
			<p><i>Click any label for help!</i></p>
			<form method="post" action="options.php">
				<?php settings_fields('cdn-options-group'); ?>
				<table class="form-table">
					<tr>
						<th><a href="#" onclick="jQuery('#gajax-description').toggle();return false;" title="Click for help!">Use Google AJAX CDN:</a></th>
						<td><select name="cdntools_googleajax">
							<?php
							$true = null;
							$false = null;
							$set = 'selected="selected"';
							($this->cdntools_googleajax)?$true=$set:$false=$set;
							?>
							<option value="1" <?php echo $true?>>True</option>
							<option value="0" <?php echo $false?>>False</option>
							</select></td>
					</tr>
					<tr style="display:none" id="gajax-description">
						<td colspan="2">Google libraries will replace prototype, jquery, dojo, and mootools.  Google will trump any other CDN you have enabled as well.  This is free so you should enable this.</td>
					</tr>
					<tr>
						<th><a href="#" onclick="jQuery('#cdn-description').toggle();return false;" title="Click for help!">Primary CDN:</a></th>
						<td><select name="cdntools_primarycdn">
							<?php
							$amazon = null;
							$cloudfiles = null;
							$none = null;
							switch($this->cdntools_primarycdn) {
								case 'cloudfiles':
									$cloudfiles = 'selected="selected"';
									$cdn_name = 'Cloud Files';
								break;
								case 'cloudfront':
									$amazon = 'selected="selected"';
									$cdn_name = 'Amazon S3/CloudFront';
								break;
								default:
									$none = 'selected="selected"';
									$cdn_name = 'None Selected';
								break;
							}
							?>
							<option value="0" <?php echo $none?>>None</option>
							<option value="cloudfiles" <?php echo $cloudfiles?>>Cloud Files</option>
							<!--option value="amazon" <?php echo $amazon?>>Amazon S3/CloudFront</option-->
							</select></td>
					</tr>
					<tr style="display:none" id="cdn-description">
						<td colspan="2">Select none if you do not have a CDN account and wish to only use the Google CDN feature for JS.  Once you have selected a CDN and entered your credentials, click save changes and then click the "Load Files" button that appears near CDN Status.</td>
					</tr>
					<tr class="cdn-auth">
						<th>Username:</th>
						<td><input type="text" name="cdntools_authname" value="<?php echo $this->cdntools_authname; ?>" /></td>
					</tr>
					<tr class="cdn-auth">
						<th>API Key:</th>
						<td><input type="password" autocomplete="off" style="width:260px" name="cdntools_authkey" value="<?php echo $this->cdntools_authkey; ?>" /></td>
					</tr>
					<tr>
						<th><a href="#" onclick="jQuery('#advanced-description').toggle();return false;" title="Click for help!">Advanced Options:</a></th>
						<td><select name="cdntools_advanced_options">
							<?php
							$advanced_true = null;
							$advanced_false = null;
							if($this->cdntools_advanced_options) {
								$advanced_true = 'selected="selected"';
							} else {
								$advanced_false = 'selected="selected"';
							}
							?>
							<option value='0' <?php echo $advanced_false;?>>Disabled</option>
							<option value='1' <?php echo $advanced_true;?>>Enabled</option>
							</select>
						</td>
					</tr>
					<tr style="display:none" id="advanced-description">
						<td colspan="2">Enabling advanced options will allow you to modify settings that are outside the standard use cases.  You can also troubleshoot/reset various aspects of the plugin if you are having issues.</td>
					</tr>
					<?php
					if($this->cdntools_advanced_options) {
						$advanced_display = '';
					} else {
						$advanced_display = 'display:none';
					}
					?>
					<tr style="<?php echo $advanced_display; ?>">
						<th><a href="#" onclick="jQuery('#containername-description').toggle();return false;" title="Click for help!">CDN Container Name:</a></th>
						<td><input type="text" name="cdntools_container_name" value="<?php echo (get_option('cdntools_container_name'))?get_option('cdntools_container_name'):'cdntools';?>" />
						</td>
					</tr>
					<tr style="display:none" id="containername-description">
						<td colspan="2">This option is only useful if you want to load multiple blogs into a single CDN account.  Before changing this you should remove all files from the CDN or you will have issues!</td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<th><a href="#" onclick="jQuery('#sideload-description').toggle();return false;" title="Click for help!">Enable CDN Sideloading:</a></th>
						<?php
						$sideloadcheck = ($this->cdntools_sideload_uploads)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_sideload_uploads" value="1" <?php echo $sideloadcheck; ?> /></td>
					</tr>
					<tr style="display:none" id="sideload-description">
						<td colspan="2">Check this to enable side loading of uploads to your CDN.  Uploads will also be kept locally in the event that you wish to turn off the CDN/remove this plugin.  Once you configure your CDN you probably want to turn this on.</td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<th><a href="#" onclick="jQuery('#logretention-description').toggle();return false;" title="Click for help!">Enable CDN Log Retention:</a></th>
						<?php
						$logretentioncheck = ($this->cdntools_logretention)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_logretention" value="1" <?php echo $logretentioncheck; ?> /></td>
					</tr>
					<tr style="display:none" id="logretention-description">
						<td colspan="2">Check this to enable CDN log retention. If enabled, logs will be periodically (at unpredictable intervals) compressed and uploaded to a ".CDN_ACCESS_LOGS" container in the form of "container_name.YYYYMMDDHH-XXXX.gz". After enabling  or disabling this option, you will need to re-upload your attachments to Cloud Files (click the "Load Attachments" button below).</td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>">
						<th><a href="#" onclick="jQuery('#servicenet-description').toggle();return false;" title="Click for help!">Enable Servicenet Connection:</a></th>
						<?php
						$servicenetcheck = ($this->cdntools_servicenet)?'checked="checked"':'';
						?>
						<td><input type="checkbox" name="cdntools_servicenet" value="1" <?php echo $servicenetcheck; ?> /></td>
					</tr>
					<tr style="display:none" id="servicenet-description">
						<td colspan="2">Check this to enable the Servicenet connection to Cloud Files. If enabled, traffic to Cloud Files will use Rackspace's internal network to upload data to Cloud Files. This internal connection will almost always be faster than non-servicenet connections, and bandwidth on servicenet is free. However, servicenet connections will only work if this blog is hosted on a server that is hosted by Rackspace or Slicehost.  <b>Rackspace Cloud Sites does not currently support the servicenet.</b></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>;display:none;">
						<th><a href="#" onclick="jQuery('#acl-ref-description').toggle();return false;" title="Click for help!">Referrer Restriction:</a></th>
						<td><input type="text" name="cdntools_acl_ref" value="<?php echo get_option('cdntools_acl_ref')?>" style="width:260px" /></td>
					</tr>
					<tr style="display:none" id="acl-ref-description">
						<td colspan="2">If you specify a referrer restriction here clients that fail to send an acceptable referrer will be blocked.  For example, if you put the root path to your blog, then anyone who attempts to use your images on another domain (or send direct links of images to others) will be blocked.  <b>Do not use this unless you know exactly what you're doing.</b></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>;display:none;">
						<th><a href="#" onclick="jQuery('#acl-ua-description').toggle();return false;" title="Click for help!">UA Restriction:</a></th>
						<td><input type="text" name="cdntools_acl_ua" value="<?php echo get_option('cdntools_acl_ua')?>" style="width:260px" /></td>
					</tr>
					<tr style="display:none" id="acl-ua-description">
						<td colspan="2">If you specify a user agent restriction here clients that fail to send an acceptable user agent will be blocked.  For example, if you put "Mozilla" then only clients that send a user agent of Mozilla (this is every single end user browser, but no bots like GoogleBot) would be able to access your CDN content. <b>Do not use this unless you know exactly what you're doing.</b></td>
					</tr>
					<tr style="<?php echo $advanced_display; ?>;display:none;">
						<th><a href="#" onclick="jQuery('#cname-description').toggle();return false;" title="Click for help!">CDN CNAME:</a></th>
						<td><input type="text" name="cdntools_cname" value="<?php echo get_option('cdntools_cname')?>" style="width:260px" /></td>
					</tr>
					<tr style="display:none" id="cname-description">
						<td colspan="2">Cloud Files does not support CNAMES at this time, but when it does, enter your CNAME here to have your CDN URLs show up under your own domain. <b>Do not use this unless you know exactly what you're doing.</b></td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="action" value="update" />
							<input type="submit" class="button-primary" value="Save Changes" />
						</td>
					</tr>
				</table>
			</form>
				<?php 
				if (!function_exists('curl_init')) {
					echo '<p style="color:red">To upload files to the CDN, you must have curl support installed for PHP.</p>';
				}?>

 				<div id="cdn_status" style="width:75%">
					<table>
						<tr>
							<td style="font-size:24px;">CDN Status</td>
							<td style="font-size:24px"> | </td>
							<td style="color:blue;text-align:center;font-size:24px"><?php echo $cdn_name; ?></td>
							<td style="font-size:24px"> | </td>
							<td id="load_remove_button"><?php if ( !(get_option('cdntools_baseuri')) && !$none ) {
							?>
								<form method="post" action="">
									<input type="hidden" name="action" value="cdn_upload_all_files" />
									<p><input style="font-size:48em" type="submit" class="button-primary" value="Load Files" onclick="return(confirm('This action will load every WordPress attachment up into your CDN.  The page will take a bit to reload and then an AJAX progress meter will start.  Do not navigate away from the page during the upload!'))" /></p>
								</form>
							<?php
							} else if (!$none) { ?>
								<form method="post" action="">
									<input type="hidden" name="action" value="cdn_remove_all_files" />
									<p><input type="submit" class="button-primary" value="Remove Files" onclick="return(confirm('This action will remove all files from your CDN.  You should only do this if you wish to remove the plugin or to troubleshoot.  And yes, this will take awhile!'))" /></p>
								</form>
							<?php
							}?>
							</td>
							<td id="load_percent" style="color:green;font-weight:bold;font-size:24px;display:none">0.00%
							</td>
							<td id="loading" style="display:none">
								<img src="images/wpspin_light.gif" alt="" style="border:0" />
							</td>
						</tr>
					</table>
			<?php
			if ($none) {?>
				<p style="font-size:11px;color:#333333">Options to load/unload attachment data will appear here when you have configured a CDN for use.</p>
			<?php
			} else {
				$attachment_load_status = ( ( get_option('cdntools_baseuri') ) )?'<span style="color:green">Attachment Data Present</span>':'<span style="color:red">No Attachment Data</span>';
				$sideload_status = ($this->cdntools_sideload_uploads)?'<span style="color:green">Side Loading Enabled</span>':'<span style="color:red">Side Loading Disabled</span>';
				?>
					<p><?php echo $sideload_status.' | '.$attachment_load_status; ?></p>
				<?php
				if($this->cdntools_advanced_options) {
					$display = 'display:block;';
				} else {
					$display = 'display:none;';
				}
				?>
				<div style="<?php echo $display; ?>" id="cdntools_advanced_options_div">
					<p style="font-size:24px;color:orange">Other Advanced Tools</p>
					<form method="post" action="">
						<input type="hidden" name="action" value="cdn_upload_all_attachments" />
						<p><input type="submit" class="button-primary" value="Load Attachments" onclick="return(confirm('This action will load every wordpress attachment up into your CDN.  This uses AJAX so while the percentage is going up don\'t reload or navigate away!'))" /></p>
					</form>

					<form method="post" action="">
						<input type="hidden" name="action" value="cdn_remove_attachments" />
						<p><input type="submit" class="button-primary" value="Remove Attachments" onclick="return(confirm('Are you sure you want to remove all attachments from the CDN?  This will disable automatic sideloading as well.'))" /></p>
					</form>
					
					<form method="post" action="">
						<input type="hidden" name="action" value="reset_cdntools" />
						<p><input type="submit" class="button-primary" value="Reset CDN Tools" onclick="return(confirm('This will remove all settings and reset CDN Tools to a default state.  Before doing this you should remove all files from the CDN.  Sure you want to do it?'))" /></p>
					</form>
				</div>
			<?php
			}?>
			</div>
		</div>
		
		<?php if($json_arr) {?>
			<script type="text/javascript">
			
				uploadArr = eval('(<?php echo $json_arr; ?>)');
				activeNum = 0;
				numComplete = 0;
				itsBusted = 0;
				total = uploadArr.length;
				if (uploadArr.length > 0) {
					jQuery('#loading').show();
					jQuery('#load_percent').show();
					jQuery('#load_remove_button').hide();
					setConfirmUnload(true);
				}	

		
				fillConnectionQueue();

				//this will prevent too many ajax requests from stacking up.
				function fillConnectionQueue() {
					while(activeNum < 4 && uploadArr.length > 0 && itsBusted == 0) {
						var attachment = uploadArr.shift();
						activeNum++;
						queueAjax(attachment);
					}
				}

				function queueAjax(attachment) {
					jQuery.ajax({
						type: "post",
						url: "admin-ajax.php",
						data: 
						{
						'action': 'cdn_attachment_upload_ajax',
						'path': attachment['path'],
						'post_id': attachment['post_id'],
						'cookie': encodeURIComponent(document.cookie)
						},
						timeout: 95000,
						error: function(request,error) {
							alert('A timeout on upload has occurred.  Please contact the developer and provide them this information:\n'+error+'\n'+decodeURIComponent(this.data))
							activeNum--;
							fillConnectionQueue();
						},
						success: function(response) {
							activeNum--;
							fillConnectionQueue();
							//wp returns a 0 after all ajax calls for reasons that are beyond my
							//ability to comprehend.  let's strip it off.
							var truncated_response = response.substring(0, response.length - 1);
							if(truncated_response == 'true') {
								numComplete++;
								var percent_complete = Math.round(parseInt(numComplete)/parseInt(total) * 10000)/100;
								jQuery('#load_percent').html(percent_complete+'%');
								if(percent_complete == 100) {
									jQuery('#loading').hide();
									setConfirmUnload(false);
									window.location.href=window.location.href; //reload
								}
							} else {
								if(!confirm('A file failed to upload.  CDN Tools will behave inconsistently if all files do not upload successfully!  You should contact the developer with this info:\n'+truncated_response+'\n\n Click Okay to continue uploading, or cancel to abort.')) {
									itsBusted = 1;
									setConfirmUnload(false); //something failed, we don't want to have that confirm any more
								}
							}
				  		}
					});
				}
				function setConfirmUnload(val) {
					window.onbeforeunload = (val) ? unloadMessage : null;
				}
				
				function unloadMessage() {
					return 'Leaving this page will cause the content load to be incomplete!';
				}
			</script>
		<?php }?>
		
		
		
		<?php 
		if(CDNTOOLS_DEBUG == true) {?>
			<?php
			foreach($wp_scripts->registered as $object) {
				echo $object->handle.' '.$object->src.' ';
				print_r($object->deps);
				echo '<br>';
			}
		}
	}
	
	/*array2json provided by bin-co.com under BSD license*/
	private function array2json($arr) { 
		if(function_exists('json_encode')) return stripslashes(json_encode($arr)); //Latest versions of PHP already have this functionality. 
		$parts = array(); 
		$is_list = false; 

		//Find out if the given array is a numerical array 
		$keys = array_keys($arr); 
		$max_length = count($arr)-1; 
		if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1 
			$is_list = true; 
			for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position 
				if($i != $keys[$i]) { //A key fails at position check. 
					$is_list = false; //It is an associative array. 
					break; 
				} 
			} 
		} 

		foreach($arr as $key=>$value) { 
			if(is_array($value)) { //Custom handling for arrays 
				if($is_list) $parts[] = $this->array2json($value); /* :RECURSION: */ 
				else $parts[] = '"' . $key . '":' . $this->array2json($value); /* :RECURSION: */ 
			} else { 
				$str = ''; 
				if(!$is_list) $str = '"' . $key . '":'; 

				//Custom handling for multiple data types 
				if(is_numeric($value)) $str .= $value; //Numbers 
				elseif($value === false) $str .= 'false'; //The booleans 
				elseif($value === true) $str .= 'true'; 
				else $str .= '"' . addslashes($value) . '"'; //All other things 
				// :TODO: Is there any more datatype we should be in the lookout for? (Object?) 

				$parts[] = $str; 
			} 
		} 
		$json = implode(',',$parts); 

		if($is_list) return '[' . $json . ']';//Return numerical JSON 
		return '{' . $json . '}';//Return associative JSON 
	} 
}

?>
