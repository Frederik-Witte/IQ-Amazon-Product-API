<?php
/*
*	Plugin Name: IQ Amazon Product API Plugin
*	Plugin URI: https://github.com/iqdevelopmentde/iq-amazon-product-api
*	Description: Get Amazon products via shortcodes. Intended for developers who know how to code. Extended functionality like wrapping divs around products.
*	Version: 1.0
*	Author: Frederik Witte
*	Author URI: https://www.iq-dev.com
*	License: GPL2
*/

include_once('iq-amazon-product-api-handler.php');
add_shortcode( 'iqamazon', 'iq_amazon_shortcode' );


/*
 * Assign Global variables
 *
*/

$plugin_url = WP_PLUGIN_URL . '/iq-amazon-product-api';
$options = array();

/*
*	Add a link to our plugin in the admin menu
*	under 'Settings > IQ Amazon Product API'
*/
function iq_amazon_product_api_menu() {

	/*
	 * Use the add_options_page function
	 * add_options_page( $page_title, $menu_title, $capability, $menu-slug, $function )
	*/
	add_options_page(
	                 'IQ Amazon Plugin',
	                 'IQ Amazon',
	                 'manage_options',
	                 'iq_amazon_product_api',
	                 'iq_amazon_product_api_options_page'
	                 );

}
add_action( 'admin_menu', 'iq_amazon_product_api_menu' );



function iq_amazon_product_api_options_page() {

	if( !current_user_can( 'manage_options' ) ) {

		wp_die( 'You do not have sufficient permissions to access this page.' );

	}

	global $plugin_url;
	global $options;

	if( isset( $_POST['iq_amazon_form_submitted'] )) {

		$hidden_field = esc_html( $_POST['iq_amazon_form_submitted'] );

		if( $hidden_field == 'Y' ) {

			$iq_amazon_api_key = esc_html( $_POST['iq_amazon_api_key'] );
			$iq_amazon_secret_key = esc_html( $_POST['iq_amazon_secret_key'] );
			$iq_amazon_api_key_2 = esc_html( $_POST['iq_amazon_api_key_2'] );
			$iq_amazon_secret_key_2 = esc_html( $_POST['iq_amazon_secret_key_2'] );
			$iq_amazon_associate_tag = esc_html( $_POST['iq_amazon_associate_tag'] );
			$iq_ebay_campaign_id = esc_html( $_POST['iq_ebay_campaign_id'] );

			$options['iq_amazon_api_key'] = $iq_amazon_api_key;
			$options['iq_amazon_secret_key'] = $iq_amazon_secret_key;
			$options['iq_amazon_api_key_2'] = $iq_amazon_api_key_2;
			$options['iq_amazon_secret_key_2'] = $iq_amazon_secret_key_2;
			$options['iq_amazon_associate_tag'] = $iq_amazon_associate_tag;
			$options['iq_ebay_campaign_id'] = $iq_ebay_campaign_id;
			$options['last_updated'] = time();

			update_option( 'iq_amazon_product_api', $options );

		}

	}

	$options = get_option( 'iq_amazon_product_api' );
	if( $options != '' ) {

		$iq_amazon_api_key = $options['iq_amazon_api_key'];
		$iq_amazon_secret_key = $options['iq_amazon_secret_key'];
		$iq_amazon_api_key_2 = $options['iq_amazon_api_key_2'];
		$iq_amazon_secret_key_2 = $options['iq_amazon_secret_key_2'];
		$iq_amazon_associate_tag = $options['iq_amazon_associate_tag'];
		$iq_ebay_campaign_id = $options['iq_ebay_campaign_id'];

	}

	require( 'inc/options-page-wrapper.php' );

}

function iq_amazon_product_api_styles() {
	wp_enqueue_style( 'iq_amazon_product_api_styles', plugins_url( 'iq-amazon-product-api/iq-amazon-product-api.css' ));
}
add_action( 'admin_head', 'iq_amazon_product_api_styles' );

function iq_amazon_product_api_front_styles(){
	wp_register_style( 'iq_main_css', plugins_url( 'iq-amazon-product-api/iq-amazon-product-api.css' ) );
	wp_enqueue_style( 'iq_main_css' );
}
add_action( 'wp_enqueue_scripts', 'iq_amazon_product_api_front_styles' );

function iq_amazon_product_api_head() {
	global $options;
	$options = get_option( 'iq_amazon_product_api' );
	echo '<script>window._epn = {campaign:' . $options['iq_ebay_campaign_id'] . '};</script>';
	echo '<script src="https://epnt.ebay.com/static/epn-smart-tools.js"></script>';
}
// Add hook for admin <head></head>
// add_action('admin_head', 'my_custom_js');
// Add hook for front-end <head></head>
add_action('wp_head', 'iq_amazon_product_api_head');



 ?>
