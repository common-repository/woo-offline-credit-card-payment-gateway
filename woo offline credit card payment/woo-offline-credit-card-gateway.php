<?php
/*
Plugin Name: Woo Offline Credit Card Payment Gateway
Plugin URI: 
Description: Extends WooCommerce by add the Offline Credit Card Payment
Version: 1.0
Author: James Mike
Author URI: http://liking.pe.hu/
*/

// define Gateway Class and add Payment Gateway to WooCommerce
add_action( 'plugins_loaded', 'offline_credit_card_init', 0 );
function offline_credit_card_init() {
	// if WC_Payment_Gateway class doesn't exist
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// include gateway class that we have make
	include_once( 'woo-offline-credit-card.php' );

	//add it to WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'offline_credit_card_gateway' );
	function offline_credit_card_gateway( $methods ) {
		
	    //define mentod with class name 
		$methods[] = 'Offline_Credit_Card';
		return $methods;
	}
}

// add the custom links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'offline_credit_card_action_links' );
function offline_credit_card_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'spyr-authorizenet-aim' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );	
}


