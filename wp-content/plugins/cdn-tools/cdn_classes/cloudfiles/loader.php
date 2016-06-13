<?php

require(dirname(__FILE__).'/cloudfiles.php');

class cdn_loader {
	private $authname = null;
	private $authkey = null;
	private $upload_path = null;
	private $uploads_use_yearmonth_folders = null;
	private $conn = null;
	
	function __construct($authname,$authkey,$use_servicenet=False) {
		$this->authname = $authname;
		$this->authkey = $authkey;
		$wpuploadinfo = wp_upload_dir();
		//we only need the basedir
		$this->upload_path = $wpuploadinfo['basedir'];
		$this->uploads_use_yearmonth_folders = get_option('uploads_use_yearmonth_folders');
		$this->authconn($use_servicenet);
		
	}
	
	public function authconn($use_servicenet) {
		@session_start(); //start a session in case we don't have one. suppress errors so E_NOTICE won't fire even with E_ALL error_reporting
		$auth = new CF_Authentication($this->authname,$this->authkey);
		if(isset($_SESSION['cf_credentials']) && (time()-$_SESSION['cf_credentials_timestamp']) < 1200) {
			extract($_SESSION['cf_credentials']);
			try {
				$auth->load_cached_credentials($auth_token, $storage_url, $cdnm_url);
			}
			catch(SyntaxException $e) {
				echo $e->getMessage();
				return false;
			}
		} else {
			//no cached credentials, we need to re-auth
			try {
				$auth->authenticate();
			}
			catch(AuthenticationException $e) {
				//for some reason this returns two error msgs.  one is echo'd even without the below echo
				echo $e->getMessage();
				return false;
			}
			$_SESSION['cf_credentials'] = $auth->export_credentials();
			$_SESSION['cf_credentials_timestamp'] = time();
		}
		try {
			$this->conn = new CF_Connection($auth,$servicenet=$use_servicenet);
		}
		catch(AuthenticationException $e) {
			echo $e->getMessage();
			return false;
		}
	}
	
	public function load_js_files($filestoload) {
		global $wp_scripts;
		
		$wp_js = $this->conn->create_container(CDNTOOLS_PREFIX.'wp_js');
		
		$loadedfiles = array();
		foreach($wp_scripts->registered as $scriptobj) {
			if(in_array($scriptobj->handle,$filestoload)) {
				$object = $wp_js->create_object($scriptobj->handle.'.js');
				$object->load_from_filename(ABSPATH.$scriptobj->src);
				$loadedfiles[] = $scriptobj->handle;
			}
		}
		$baseuri = $wp_js->make_public(86400); //ttl of one day
		return array($baseuri,$loadedfiles);
	}

	public function attachment_upload($filepath,$keep_logs=False) {
		//consider modifying filepath to be relative here and attaching the upload_path + directory separator by default
		$file = str_replace($this->upload_path.DIRECTORY_SEPARATOR,'',$filepath);

		//need try/catch blocks here, all possible exceptions!
		$wp_uploads = $this->conn->create_container(get_option('cdntools_container_name'));
		$cdntools_acl_ref = get_option('cdntools_acl_ref');
		if(trim($cdntools_acl_ref) != '') {
			$wp_uploads->acl_referrer($cdntools_acl_ref);
		}
		$cdntools_acl_ref = get_option('cdntools_acl_ua');
		if(trim($cdntools_acl_ua) != '') {
			$wp_uploads->acl_referrer($cdntools_acl_ua);
		}
		try {
			$wp_uploads->create_paths($file);
		}
		catch(InvalidResponseException $e) {
			return $e->getMessage().'failed create_paths '.$file;
		}
		$wp_uploads->log_retention($keep_logs);
		$object = $wp_uploads->create_object($file);
		try {
			$object->load_from_filename($filepath);
		}
		catch(InvalidResponseException $e) {
			return $e->getMessage().'failed load_from_filename '.$filepath;
		}
		catch(IOException $e) {
			return $e->getMessage();
		}
		$baseuri = $wp_uploads->make_public(172800); //ttl of two days
		return array('baseuri'=>$baseuri,'container_name'=>$container_name,'object_name'=>$object_name);
	}

	public function attachment_delete($filepath) {
		$file = str_replace($this->upload_path.DIRECTORY_SEPARATOR,'',$filepath);
		//need try/catch blocks here
		$wp_uploads = $this->conn->create_container(get_option('cdntools_container_name'));
		try {
			$object = $wp_uploads->delete_object($file);
		}
		catch(NoSuchObjectException $e) {
			//we don't care about something not existing when we try to delete it, so let's just 
			//eat the exception. yum yum yum
		}
	}
	
	public function load_css_files($filestoload) {
		//stub
	}
		
	public function remove_container($container_name) {
		$wp_js = $this->conn->create_container($container_name);
		
		//check for objects and remove them all if they exist
		$existing_objects = $wp_js->list_objects();
		if(is_array($existing_objects)) {
			foreach($existing_objects as $name) {
				$wp_js->delete_object($name);
			}
		}
		$this->conn->delete_container($container_name);
		return true;
	}
}