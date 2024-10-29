<?php
/*
Plugin Name: Banner Ad Creator
Description: Helps you to create banner ads, place them anywhere and track the perfomace and interactions of the banners
Version: 1.1
Author: ThemeStones
Author URI: https://themestones.net/
Text Domain: ts-ads
Domain Path: /languages
*/

define( 'TS_ADS_URL', plugin_dir_url( __FILE__ ) );
define( 'TS_ADS_PATH', plugin_dir_path( __FILE__ ) );

require_once TS_ADS_PATH . '/includes/post-types.php';
require_once TS_ADS_PATH . '/includes/view.php';
require_once TS_ADS_PATH . '/includes/auto-ad-display.php';

class TS_Ads_Core {

	function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_shortcode( 'banner_ad', array( $this, 'shortcode' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

	}

	function load_textdomain() {
		load_plugin_textdomain( 'ts-ads', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
	}

	function scripts( $hook ) {

		if( ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) && get_post_type() == 'ts-banner-ad' ) {
			wp_enqueue_style( 'ts-ads-styles', TS_ADS_URL . 'assets/css/styles.css', array(), '1.0' );
			wp_enqueue_script( 'ts-ads-script', TS_ADS_URL . 'assets/js/scripts.js', array( 'jquery' ), '1.0', true );
		}

	}

	function shortcode( $atts ) {

		extract( shortcode_atts( array(
			'id' => '',
			'async' => 'yes',
		), $atts, 'banner_ad' ) );

		$return = '';

		$banner_post = get_post( $id );

		if( is_a( $banner_post, 'WP_Post' ) && get_post_type( $banner_post ) == 'ts-banner-ad' && get_post_status( $banner_post ) == 'publish' ) {
			$meta = get_post_meta( $id, '_ts_banner', true );
			$size = explode( 'x', $meta['size'] );
			$alignment = get_post_meta( $id, '_banner_display_alignment', true );
			$frontend_js = TS_ADS_URL . 'assets/js/ts-ads.js';
			$return .= '<script' . ( $async == 'yes' ? ' async' : '' ) . ' src="' . esc_url( $frontend_js ) . '"></script>';
			$return .= '<div class="ts-banner-ad" data-size="' . esc_attr( $meta['size'] ) . '" data-src="' . esc_url( home_url( '/?banner_ad=' . $id ) ) . '" data-align="' . esc_attr( $alignment ) . '"></div>';
		}

		return $return;

	}

	static function generate_shortcode( $id, $async = true ) {
		if( !$id ) {
			return;
		}
		if( is_a( $id, 'WP_Post' ) ) {
			$id = $id->ID;
		}
		if( $async ) {
			return '[banner_ad id=' . $id . ' async="yes"]';
		} else {
			return '[banner_ad id=' . $id . ' async="no"]';
		}
	}

}

new TS_Ads_Core;