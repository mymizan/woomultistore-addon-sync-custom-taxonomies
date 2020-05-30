<?php
/**
 * Plugin Name: WooCommerce Multistore Sync Custom Taxonomies
 * Plugin URI: https://woomultistore.com
 * Description: Compatibility addon for syncing custom taxonomies by WooCommerce Multistore 
 * Author: WooCommerce Multistore
 * Version: 1.0.1
 * Author URI: https://woomultistore.com/
 **/


define( 'WOOMULTI_PLUGIN_NAME', 'WooCommerce Multistore Sync Custom Taxonomies' );
define( 'WOOMULTI_PLUGIN_VERSION', '1.0.1' );

class WOOMULTI_ADDON_CUSTOM_TAXONOMIES {

	public $taxonomies = array(
		'brand',
		'wine_type',
	);

	public function __construct() {
		if ( is_mulisite() ) {
			add_action('WOO_MSTORE_admin_product/slave_product_updated', array($this, 'sync_custom_taxonomies'), 10, 1);	
		} else {
			// Regular WordPress support
		}
		
	}

	public function sync_custom_taxonomies( $data ) {
		foreach ( $this->taxonomies as $tax ) {
			$terms_to_sync = array();

			$slave_blog_id = get_current_blog_id();
			restore_current_blog();

			//get the terms from parent
			$_terms = get_the_terms( $data['master_product']->get_id(),  $tax);


			if ( !empty($_terms) ) {
				foreach ( $_terms as $trm ) {
					$terms_to_sync[] = $trm->name;
				}
			}
			
			switch_to_blog( $slave_blog_id );

			wp_set_object_terms($data['slave_product']->get_id(), $terms_to_sync, $tax);
		}
	}
}


new WOOMULTI_ADDON_CUSTOM_TAXONOMIES();
