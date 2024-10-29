<?php

class TS_Ads_View {

	function __construct() {
		add_action( 'template_redirect', array( $this, 'query_catcher' ), 100 );
		add_action( 'admin_init', array( $this, 'admin_query_catcher' ), 100 );
	}

	function set_session_data( $key = '', $data = '' ) {
		if( isset( $_SESSION ) ) {
			if( empty( $key ) || empty( $data ) ) {
				return false;
			}
			$_SESSION[$key] = $data;
			return true;
		}
		return  false;
	}

	function get_session_data( $key = '' ) {
		if( isset( $_SESSION ) ) {
			if( isset( $_SESSION[$key] ) ) {
				return $_SESSION[$key];
			}
			return null;
		}
		return null;
	}
	
	function query_catcher() {
		if( isset( $_GET['banner_ad'] ) ) {
			$banner_post = get_post( $_GET['banner_ad'] );
			if( is_a( $banner_post, 'WP_Post' ) && get_post_type( $banner_post ) == 'ts-banner-ad' && get_post_status( $banner_post ) == 'publish' ) {
				$this->show_banner( $banner_post );
			}
		}
		if( isset( $_GET['banner_ad_preview'] ) ) {
			$banner_post = get_post( $_GET['banner_ad_preview'] );
			if( is_a( $banner_post, 'WP_Post' ) && get_post_type( $banner_post ) == 'ts-banner-ad' && get_post_status( $banner_post ) == 'publish' ) {
				$this->preview_banner( $banner_post );
			}
		}
		if( isset( $_REQUEST['ts_ad_action'] ) && isset( $_REQUEST['ts_ad_id'] ) && $_REQUEST['ts_ad_action'] == 'click' ) {
			$banner_post = get_post( base64_decode( $_REQUEST['ts_ad_id'] ) );
			if( is_a( $banner_post, 'WP_Post' ) && get_post_type( $banner_post ) == 'ts-banner-ad' && get_post_status( $banner_post ) == 'publish' ) {
				$this->handle_click( $banner_post );
			}
		}
	}

	function admin_query_catcher() {
		if( isset( $_GET['get_ts_banner_code'] ) && !empty( $_GET['get_ts_banner_code'] ) ) {
			$post_id = $_GET['get_ts_banner_code'];
			?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?php esc_html_e( 'Your banner code', 'ts-ads' ); ?></title>
	<style>
		* {
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
		}
		input, textarea {
			width: 100%;
			background: #36363e;
			padding: 10px;
			color: #d5ff72;
			border: none;
			font-family: monospace;
			border-left: 13px solid #51515d;
			margin: 0 0 15px;
		}
		textarea {
			height: 100px;
			max-width: 100%;
			min-width: 100%;
		}
		label {
			cursor: pointer;
		}
		h6 {
			color: #443f3f;
			margin: 0 0 6px;
			font-family: sans-serif;
		}
		h6 em {
			color: #989898;
		}
		html {
			background: #e8e8e8;
		}
		body {
			background: #fff;
			padding: 35px;
			padding-bottom: 20px;
			border: 1px solid #d8d8d8;
			margin: 50px;
		}
	</style>
</head>
<body>
	<label><h6><?php esc_html_e( 'Shortcode Sync:', 'ts-ads' ); ?></h6>
	<input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly="readonly" value="<?php echo esc_attr( '[banner_ad id="' . $post_id . '"]' ) ?>" /></label>
	<label><h6><?php esc_html_e( 'Shortcode Async:', 'ts-ads' ); ?></h6>
	<input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly="readonly" value="<?php echo esc_attr( '[banner_ad id="' . $post_id . '" async="yes"]' ) ?>" /></label>
	<label><h6><?php esc_html_e( 'HTML Sync:', 'ts-ads' ); ?> <em><?php esc_html_e( '(Works anywhere)' ); ?></em></h6>
	<textarea onClick="this.setSelectionRange(0, this.value.length)" readonly="readonly"><?php echo esc_textarea( $this->get_html_clode( $post_id ) ); ?></textarea></label>
	<label><h6><?php esc_html_e( 'HTML Async:', 'ts-ads' ); ?> <em><?php esc_html_e( '(Works anywhere)' ); ?></em></h6>
	<textarea onClick="this.setSelectionRange(0, this.value.length)" readonly="readonly"><?php echo esc_textarea( $this->get_html_clode( $post_id, true ) ); ?></textarea></label>
	<label><h6><?php esc_html_e( 'iFrame:', 'ts-ads' ); ?> <em><?php esc_html_e( '(Experimental)' ); ?></em></h6>
	<textarea onClick="this.setSelectionRange(0, this.value.length)" readonly="readonly"><?php echo esc_textarea( $this->get_html_clode( $post_id, true, true ) ); ?></textarea></label>
</body>
</html><?php
			exit;
		}
	}

	function get_html_clode( $id,  $async = false, $iframe = false ) {

		$banner_post = get_post( $id );

		$return = '';

		if( is_a( $banner_post, 'WP_Post' ) && get_post_type( $banner_post ) == 'ts-banner-ad' && get_post_status( $banner_post ) == 'publish' ) {
			$meta = get_post_meta( $id, '_ts_banner', true );
			$size = explode( 'x', $meta['size'] );
			$alignment = get_post_meta( $id, '_banner_display_alignment', true );
			$frontend_js = TS_ADS_URL . 'assets/js/ts-ads.js';
			$return .= '<script' . ( $async == true ? ' async' : '' ) . ' src="' . esc_url( $frontend_js ) . '"></script>';
			$return .= '<div class="ts-banner-ad" data-size="' . esc_attr( $meta['size'] ) . '" data-src="' . esc_url( home_url( '/?banner_ad=' . $id ) ) . '" data-align="' . esc_attr( $alignment ) . '"></div>';
			if( $iframe == true ) {
				$return = '<div class="ts-banner-ad" data-size="200x200" style="max-width: '. esc_attr( $size[0] ) .'px;border: none;height: '. esc_attr( $size[1] ) .'px;float: left;"><iframe src="'. esc_url( home_url( '/?banner_ad=' . $id ) ) .'" height="100%" width="100%" frameborder="no"></iframe></div>';
			}
		}

		return $return;

	}

	function halt() {
		?><!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<style>
			html, body {
				margin: 0;
				height: 100%;
				background: #ebeaf1;
				font-family: sans-serif;
				color: #bdb9b9;
				position: relative;
			}
			p {
				position: absolute;
				left: 0;
				right: 0;
				top: 50%;
				line-height: 0;
				margin: 0;
				text-align: center;
			}
			</style>
		</head>
		<body>
			<p><em><?php echo esc_html( apply_filters( 'ts_banner_halted_output', __( 'Unable to show banner', 'ts-ads' ) ) ); ?></em></p>
		</body>
		</html>
		<?php
		die;
	}

	function get_ip() {
		if( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return apply_filters( 'ts_banner_user_ip', $ip );
	}

	function handle_click( $banner_post ) {
		global $post;
		$post = $banner_post;
		setup_postdata( $post );
		$meta = get_post_meta( get_the_ID(), '_ts_banner', true );
		if( !( isset( $_GET['is_preview'] ) && $_GET['is_preview'] == 1 ) ) {
			$this->count_click( $banner_post );
		}
		wp_redirect( apply_filters( 'ts_banner_url', $meta['link'] ) );
		exit;
	}

	function count_ad( $banner_post ) {
		if( is_user_logged_in() ) {
			$uid = get_current_user_id();
			$utype = 'known';
		} else {
			$uid = $this->get_ip();
			$utype = 'unknown';
		}

		$banner_unique = get_post_meta( $banner_post->ID, '_ts_banner_unique', true );

		if( !$banner_unique || empty( $banner_unique ) ) {
			$banner_unique = md5( $banner_post->ID . time() );
			update_post_meta( $banner_post->ID, '_ts_banner_unique', $banner_unique );
		}

		$query = new WP_Query( array(
			'post_type' => 'ts-banner-action',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_ts_ad_action',
					'compare' => '=',
					'value' => 'view',
				),
				array(
					'key' => '_ts_ad_user',
					'compare' => '=',
					'value' => $uid,
				),
				array(
					'key' => '_ts_ad_user_type',
					'compare' => '=',
					'value' => $utype,
				),
				array(
					'key' => '_ts_ad_banner_id',
					'compare' => '=',
					'value' => $banner_unique,
				),
			),
		) );

		if( $query->have_posts() ) {
			$pid = $query->posts[0]->ID;
		} else {
			$pid = wp_insert_post( array(
				'post_type' => 'ts-banner-action',
				'post_author' => 0,
				'post_status' => 'publish',
				'meta_input' => array(
					'_ts_ad_banner_id' => $banner_unique,
					'_ts_ad_action' => 'view',
					'_ts_ad_user' => $uid,
					'_ts_ad_user_type' => $utype,
				),
			), true );
		}

		$stat = array(
			'time' => time('U'),
			'referer' => $_SERVER['HTTP_REFERER'],
			'browser' => $_SERVER['HTTP_USER_AGENT'],
		);

		if( !is_wp_error( $pid ) && $pid ) {
			$meta = get_post_meta( $pid, '_ts_ad_views', true );
			if( !is_array( $meta ) ) {
				$meta = array();
			}
			$meta[] = $stat;
			update_post_meta( $pid, '_ts_ad_views', $meta );
			if( class_exists( 'WP_Session' ) ) {
				$session_meta = get_post_meta( $pid, '_ts_ad_session_views', true );
				if( !is_array( $session_meta ) ) {
					$session_meta = array();
				}
				$session_key = 'done_' . $uid . $pid;
				$session_checked = $this->get_session_data( $session_key );
				if( $session_checked !== true ) {
					$this->set_session_data( $session_key, true );
					$session_meta[] = $stat;
					update_post_meta( $pid, '_ts_ad_session_views', $session_meta );
				}
			}
			do_action( 'ts_ad_viewed', $banner_post->ID, $pid, $stat );
			return $pid;
		}
		return false;
	}

	function count_click( $banner_post ) {
		if( !isset( $_SERVER['HTTP_REFERER'] ) ) {
			return false;
		}
		if( !isset( $_REQUEST['ts_ad_referer'] ) || !isset( $_REQUEST['ts_ads_secure_token'] ) ) {
			return false;
		}
		if( !wp_verify_nonce( $_REQUEST['ts_ads_secure_token'], 'ts_ads_secure_token' ) ) {
			return false;
		}
		if( is_user_logged_in() ) {
			$uid = get_current_user_id();
			$utype = 'known';
		} else {
			$uid = $this->get_ip();
			$utype = 'unknown';
		}

		$banner_unique = get_post_meta( $banner_post->ID, '_ts_banner_unique', true );

		if( !$banner_unique || empty( $banner_unique ) ) {
			$banner_unique = md5( $banner_post->ID . time() );
			update_post_meta( $banner_post->ID, '_ts_banner_unique', $banner_unique );
		}

		$query = new WP_Query( array(
			'post_type' => 'ts-banner-action',
			'posts_per_page' => 1,
			'post_status' => 'publish',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_ts_ad_action',
					'value' => 'click',
				),
				array(
					'key' => '_ts_ad_user',
					'value' => $uid,
				),
				array(
					'key' => '_ts_ad_user_type',
					'value' => $utype,
				),
				array(
					'key' => '_ts_ad_banner_id',
					'value' => $banner_unique,
				),
			),
		) );

		if( $query->have_posts() ) {
			$pid = $query->posts[0]->ID;
		} else {
			$pid = wp_insert_post( array(
				'post_type' => 'ts-banner-action',
				'post_author' => 0,
				'post_status' => 'publish',
				'meta_input' => array(
					'_ts_ad_banner_id' => $banner_unique,
					'_ts_ad_action' => 'click',
					'_ts_ad_user' => $uid,
					'_ts_ad_user_type' => $utype,
				),
			), true );
		}

		$stat = array(
			'time' => time('U'),
			'referer' => base64_decode( $_REQUEST['ts_ad_referer'] ),
			'browser' => $_SERVER['HTTP_USER_AGENT'],
		);

		if( !is_wp_error( $pid ) && $pid ) {
			$meta = get_post_meta( $pid, '_ts_ad_clicks', true );
			if( !is_array( $meta ) ) {
				$meta = array();
			}
			$meta[] = $stat;
			update_post_meta( $pid, '_ts_ad_clicks', $meta );

			if( class_exists( 'WP_Session' ) ) {
				$session_meta = get_post_meta( $pid, '_ts_ad_session_clicks', true );
				if( !is_array( $session_meta ) ) {
					$session_meta = array();
				}
				$session_key = 'done_' . $uid . $pid;
				$session_checked = $this->get_session_data( $session_key );
				if( $session_checked !== true ) {
					$this->set_session_data( $session_key, true );
					$session_meta[] = $stat;
					update_post_meta( $pid, '_ts_ad_session_clicks', $session_meta );
				}
			}
			do_action( 'ts_ad_clicked', $banner_post->ID, $pid, $stat );
			return $pid;
		}
		return false;
	}

	function allowed_to_show_ad( $post ) {

		$requested = parse_url( preg_replace( "/\/\/www\./", '//', $_SERVER['HTTP_REFERER'] ), PHP_URL_HOST );
		$home_url = parse_url( preg_replace( "/\/\/www\./", '//', home_url( '/' ) ), PHP_URL_HOST );

		$meta = get_post_meta( $post->ID, '_ts_banner', true );
		$allowed_sites = $meta['allowed_sites'];

		if( trim( $allowed_sites ) == '*' ) {
			return true;
		}

		$lines = explode( "\n", $allowed_sites );

		$sites = apply_filters( 'ts_banner_ads_common_allowed_sites', array() );

		foreach( $lines as $line ) {
			if( trim( $line ) === '' ) {
				continue;
			}
			$the_url = preg_replace( "/\/\/www\./", '//', esc_url( trim( preg_replace( "![\r\n]+!", '', $line ) ) ) );
			$sites[] = parse_url( $the_url, PHP_URL_HOST );
		}

		$sites = apply_filters( 'ts_banner_ads_allowed_sites', $sites );

		if( in_array( $requested , $sites ) ) {
			return true;
		}

		return false;

	}

	function preview_banner( $banner_post ) {
		?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Banner Preview</title>
</head>
<body>
	<h3><?php esc_html_e( 'This preview is the last published version of the banner', 'ts-ads' ); ?></h3>
	<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Tempore, similique, ipsam. Minus non tenetur suscipit, magnam iure repellat repellendus error molestiae? Tempora repellendus, amet explicabo.</p>
	<?php
	echo do_shortcode( '[banner_ad id=' . $banner_post->ID . ' async="no"]' );
	?>
	<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Tempore, similique, ipsam. Minus non tenetur suscipit, magnam iure repellat repellendus error molestiae? Tempora repellendus, amet explicabo.</p>
</body>
</html><?php
	exit;
	}

	function show_banner( $banner_post ) {
		global $post;
		$is_preview = false;
		$post = $banner_post;
		setup_postdata( $post );
		$meta = get_post_meta( get_the_ID(), '_ts_banner', true );
		$size = explode( 'x', $meta['size'] );
		if( !isset( $_SERVER['HTTP_REFERER'] ) ) {
			$this->halt();
		} elseif( $this->allowed_to_show_ad( $post ) === false ) {
			$this->halt();
		}
		if( strpos( $_SERVER['HTTP_REFERER'], 'banner_ad_preview' ) !== false ) {
			$is_preview = true;
		} else {
			$this->count_ad( $banner_post );
		}
		?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Ads by ThemeStones</title>
	<style>
		html,
		body {
			margin: 0;
			padding: 0;
			height: 100%;
			width: 100%;
			overflow: hidden;
			position: relative;
		}
		#ad-container {
			height: 100%;
			width: 100%;
			position: relative;
		}
		.ad-image {
			object-fit: cover;
			height: 100%;
			width: 100%;
		}
		#click-link {
			position: absolute;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
		}
		button.click-ad {
			position: absolute;
			top: 0;
			right: 0;
			bottom: 0;
			left: 0;
			display: block;
			width: 100%;
			-webkit-appearance: none;
			-moz-appearance: none;
			appearance: none;
			border: none;
			background: none;
			cursor: pointer;
			z-index: 999999;
		}
		*:focus {
			outline: none;
		}
	</style>
	<?php
	if( !empty( $meta['css'] ) ) {
		echo '<style>' . apply_filters( 'ts_banner_css', $meta['css'] ) . '</style>';
	}
	do_action( 'ts_banner_head', $banner_post->ID );
	?>
</head>
<body>
	<?php
	do_action( 'ts_banner_header', $banner_post->ID );
	global $wp_scripts;
	$jquery = home_url( $wp_scripts->registered['jquery-core']->src );
	?>
	<div id="ad-container">
<?php
$type = $meta['type'];
switch( $type ) {
	case 'code';
		echo apply_filters( 'ts_banner_html', $meta['html'] );
		break;
	case 'wysiwyg';
		echo wp_kses_post( apply_filters( 'ts_banner_wysiwyg', $meta['banner-wysiwyg'] ) );
		break;
	case 'image';
		$image_id = $meta['image_id'];
		echo apply_filters( 'ts_banner_image', wp_get_attachment_image( $image_id, 'full', false, array(
			'class' => 'ad-image'
		) ) );
		break;
}
?>
		<?php if( !$is_preview ) : ?>
		<a class="click-ad" id="click-link" target="<?php echo esc_attr( $meta['link_target'] ); ?>" href="<?php
			$nonce = wp_create_nonce( 'ts_ads_secure_token' );
			echo esc_url( add_query_arg( array(
				'ts_ad_id' => base64_encode( get_the_ID() ),
				'ts_ads_secure_token' => $nonce,
				'ts_ad_referer' => base64_encode( esc_url( $_SERVER['HTTP_REFERER'] ) ),
				'ts_ad_action' => 'click',
			), home_url('/') ) );
		?>"></a>
		<?php else: ?>
		<button type="button" class="click-ad"></button>
		<?php endif; ?>
	</div>
	<?php
	if( isset( $meta['load_jq'] ) && $meta['load_jq'] ) {
		echo '<script src="' . esc_url( $jquery ) . '"></script>';
	}
	if( !empty( $meta['js'] ) ) {
		echo '<script>' . apply_filters( 'ts_banner_js', $meta['js'] ) . '</script>';
	}
	do_action( 'ts_banner_footer', $banner_post->ID );
	?>
</body>
</html><?php
		die;
	}

}

new TS_Ads_View;