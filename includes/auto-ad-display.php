<?php

class TS_Auto_Ads {

	function __construct() {

		add_action( 'the_content', array( $this, 'insert_ad' ), 10 );

	}

	function get_ad_ids( $position = 'top' ) {

		$query = new WP_Query( array(
			'post_type' => 'ts-banner-ad',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_show_on_post',
					'value' => '1',
				),
				array(
					'key' => '_banner_display_position',
					'value' => $position,
				), 
			),
		) );

		return $query->posts;

	}

	function insert_ad( $content ) {

		if( !is_single() ) {
			return $content;
		}

		$top_ads = $this->get_ad_ids( 'top' );
		$middle_ads = $this->get_ad_ids( 'middle' );
		$end_ads = $this->get_ad_ids( 'end' );

		if( is_array( $top_ads ) && ! empty( $top_ads ) ) {
			$id = array_rand( $top_ads );
			$id = $top_ads[$id];
			$shortcode = TS_Ads_Core::generate_shortcode( $id );
			$content = $shortcode . $content;
		}

		if( is_array( $middle_ads ) && ! empty( $middle_ads ) ) {
			$id = array_rand( $middle_ads );
			$id = $middle_ads[$id];
			$shortcode = TS_Ads_Core::generate_shortcode( $id );
			$content = explode( '</p>', $content );
			$mid = ceil( ( count( $content ) - 1 ) / 2 );
			$content[$mid] = $shortcode . $content[$mid];
			$content = implode( '</p>', $content );
		}

		if( is_array( $end_ads ) && ! empty( $end_ads ) ) {
			$id = array_rand( $end_ads );
			$id = $end_ads[$id];
			$shortcode = TS_Ads_Core::generate_shortcode( $id );
			$content .= $shortcode;
		}

		return $content;

	}

}

new TS_Auto_Ads;