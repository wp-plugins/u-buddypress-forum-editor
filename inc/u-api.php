<?php

if( !class_exists('U_API') ){

class U_API {
	
	var $api = "http://urlless.com/api/";
	
	function plugin_admin_sidebar(){
		$key = 'u_plugin_admin_sidebar';
		//delete_transient($key);
		
		if( false !== $cache = get_transient($key) ){
			echo $cache;
			
		}else{
			$data = array(
				'action' => 'plugin-admin-sidebar',
				'key' => $key,
			);
			$res = wp_remote_post( $this->api, array('body'=>$data) );
			
			if ( is_wp_error( $res ) || 200 != wp_remote_retrieve_response_code( $res ) ) 
				return false;
			
			$res = wp_remote_retrieve_body( $res );
			
			if( preg_match('|'.md5($key).'|', $res) ){
				set_transient($key, $res, 60*60*24);
				echo $res;
			}
		}
	}
	
	function plugin_activation($plugin_id, $ver=''){
		$data = array(
			'action' => 'plugin-activation',
			'plugin_id' => $plugin_id,
			'ver' => $ver,
		);
		wp_remote_post( $this->api, array('body'=>$data) );
	}
	
	function plugin_deactivation($plugin_id, $ver=''){
		$data = array(
			'action' => 'plugin-deactivation',
			'plugin_id' => $plugin_id,
			'ver' => $ver,
		);
		wp_remote_post( $this->api, array('body'=>$data) );
	}

}

$GLOBALS['u_api'] = new U_API;

}

