<?php
/**
 * Plugin Name: WooMultistore Sync Custom Taxonomies
 * Plugin URI: https://woomultistore.com
 * Description: Compatibility addon for syncing custom taxonomies by WooCommerce Multistore
 * Author: WooCommerce Multistore
 * Version: 1.0.1
 * Author URI: https://woomultistore.com/
 **/


define( 'WOOMULTI_PLUGIN_NAME', 'WooCommerce Multistore Sync Custom Taxonomies' );
define( 'WOOMULTI_PLUGIN_VERSION', '1.0.1' );

class WOOMULTI_ADDON_CUSTOM_TAXONOMIES {

	/**
	 * ***************************************************************************************************
	 * ***************************************************************************************************
	 * ***************************************************************************************************
	 * ***************************************************************************************************
	 *  Add your taxonomy slugs here. Do you change anything else, unless you know what you are doing.
	 * ***************************************************************************************************
	 * ***************************************************************************************************
	 * ***************************************************************************************************
	 * ***************************************************************************************************
	 */
	public $taxonomies = array(
		'brand', // remove this and add yours.
		'brands', // remove this and add yours.
	);

	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'WOO_MSTORE_admin_product/slave_product_updated', array( $this, 'sync_custom_taxonomies' ), 10, 1 );
		} else {
			// Regular WordPress support
			// Add ACF fields to product JSON.
			add_action( 'WOO_MSTORE_SYNC/process_json/product', array( $this, 'add_taxonomy_terms' ), 10, 3 );
			add_action( 'WOO_MSTORE_SYNC/sync_child/complete', array( $this, 'sync_taxonomy_terms' ), 10, 3 );
		}
	}

	public function add_taxonomy_terms( $product, $wc_product, $product_id ) {
		$custom_tax = array();

		foreach ( $this->taxonomies as $tax ) {
			$_terms              = get_the_terms( $product_id, $tax );
			$custom_tax [ $tax ] = array();

			foreach ( $_terms as $trm ) {
				$custom_tax [ $tax ][] = $trm->name;
			}
		}

		$product['custom_taxonomies'] = $custom_tax;

		return $product;

	}

	public function sync_taxonomy_terms( $wc_product_id, $parent_id, $product ) {
		if ( empty( $product['custom_taxonomies'] ) ) {
			return;
		}

		foreach ( $product['custom_taxonomies'] as $tax => $terms ) {
			wp_set_object_terms( $wc_product_id, $terms, $tax );
		}
	}

	/**
	 * Sync custom taxonomies on the multisite version.
	 */
	public function sync_custom_taxonomies( $data ) {
		foreach ( $this->taxonomies as $tax ) {
			$terms_to_sync = array();

			$slave_blog_id = get_current_blog_id();
			restore_current_blog();

			// get the terms from parent
			$_terms = get_the_terms( $data['master_product']->get_id(), $tax );

			if ( ! empty( $_terms ) ) {
				foreach ( $_terms as $trm ) {
					$terms_to_sync[] = $trm->name;
				}
			}

			switch_to_blog( $slave_blog_id );

			wp_set_object_terms( $data['slave_product']->get_id(), $terms_to_sync, $tax );
		}
	}

	/**
	 * Check if required plugins are active.
	 *
	 * @return void
	 */
	public function check_if_plugins_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'woocommerce-multistore/woocommerce-multistore.php' ) ) {
			return;
		}

		return true;
	}
}


new WOOMULTI_ADDON_CUSTOM_TAXONOMIES();
