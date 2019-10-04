<?php

/**
 * Plugin Name: Add Product Widget
 * Description: This plugin allows you to add the products to the cart from the widget. 
 * Author: Tyche Softwares
 * Version: 1.0
 * Contributor: Moxa Jogani
 *
 * @package Add-Products-Widget
 */

include_once('ap-widget.php');

/**
 * Add product widget class
 *
 * @since 1.0
 */
class add_product_widget {

	/**
	 * Default Constructor
	 *
	 * @since 1.0
	 */

	public function __construct() {
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			// Register and load the widget
			add_action('widgets_init', array(&$this, 'ap_load_widget'));
		}
	}

	function ap_load_widget() {
		register_widget('ap_widget');
	}
}
$add_product_widget = new add_product_widget();