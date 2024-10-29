<?php

class TS_Ads_Post_Types {

	function __construct() {

		$this->notices = array();

		add_action( 'init', array( $this, 'init_actions' ), 10 );
		add_action( 'admin_notices', array( $this, 'show_notice' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'metabox' ) );
		add_action( 'save_post', array( $this, 'save_data' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
		add_filter( 'manage_ts-banner-ad_posts_columns', array( $this, 'add_cols' ) );
		add_action( 'manage_ts-banner-ad_posts_custom_column' , array( $this, 'column_view' ), 10, 2 );

	}

	function row_actions( $actions, $post ) {
		if( $post->post_type == 'ts-banner-ad' ) {
			$actions['clear-data hide-if-no-js'] = sprintf( '<a href="%1$s" class="clear-link" onclick="return confirm(\'%3$s\')">%2$s</a>', esc_attr( admin_url( 'edit.php?post_type=ts-banner-ad&clear_data=' . $post->ID ) ), esc_html__( 'Clear data', 'ts-ads' ), esc_attr__( 'Are you sure you want to clear all statistics of this banner?', 'ts-ads' ) );
		}
		return $actions;
	}

	function show_notice() {

		foreach( $this->notices as $notice ) {
			?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
				<p><?php echo wp_kses_post( $notice['text'] ); ?></p>
			</div>
			<?php
		}

	}

	function add_cols( $columns ) {
		$columns['codes'] = __( '<span class="dashicons dashicons-editor-code"></span> Codes', 'ts-ads' );
		$columns['views'] = __( '<span class="dashicons dashicons-visibility"></span> Views', 'ts-ads' );
		$columns['clicks'] = __( '<span class="dashicons dashicons-chart-bar"></span> Clicks', 'ts-ads' );

		unset( $columns['date'] );

		return $columns;
	}

	function column_view( $column, $post_id ) {

		$banner_unique = get_post_meta( $post_id, '_ts_banner_unique', true );

		if( !$banner_unique || empty( $banner_unique ) ) {
			$banner_unique = md5( $post_id . time() );
			update_post_meta( $post_id, '_ts_banner_unique', $banner_unique );
		}

		switch ( $column ) {

			case 'codes' :
			add_thickbox();
			$url = add_query_arg( array(
				'get_ts_banner_code' => $post_id,
				'TB_iframe' => 'true',
				'width' => 'true',
				'height' => 'true',
			), admin_url('/') );
			echo '<a href="' . esc_url( $url ) . '" class="thickbox button">' . esc_html__( 'Get Codes', 'ts-ads' ) . '</a>';
			break;

			case 'views' :

			$query = new WP_Query( array(
				'post_type' => 'ts-banner-action',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => '_ts_ad_action',
						'value' => 'view',
					),
					array(
						'key' => '_ts_ad_banner_id',
						'value' => $banner_unique,
					),
				),
			) );

			$total = 0;
			$session = 0;

			foreach( $query->posts as $view_action ) {
				$array = get_post_meta( $view_action->ID, '_ts_ad_views', true );
				if( is_array( $array ) ) {
					$total += count( $array );
				}
			}

			echo $total;
			break;

			case 'clicks' :

			$query = new WP_Query( array(
				'post_type' => 'ts-banner-action',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => '_ts_ad_action',
						'value' => 'click',
					),
					array(
						'key' => '_ts_ad_banner_id',
						'value' => $banner_unique,
					),
				),
			) );

			$total = 0;
			$session = 0;

			foreach( $query->posts as $view_action ) {
				$array = get_post_meta( $view_action->ID, '_ts_ad_clicks', true );
				if( is_array( $array ) ) {
					$total += count( $array );
				}
			}

			echo $total;
			break;

		}
	}

	function save_data( $post_id, $post ) {
		if( get_post_type( $post_id ) !== 'ts-banner-ad' ) {
			return;
		}
		if( isset( $_POST['_ts_banner'] ) && isset( $_POST['_ts_banner_nonce'] ) && wp_verify_nonce( $_POST['_ts_banner_nonce'], '_ts_banner_nonce' ) ) {
			$data = array();
			$sanitizer = array(
				'size' => 'sanitize_text_field',
				'link_target' => 'sanitize_text_field',
				'link' => 'esc_url',
				'type' => 'sanitize_text_field',
				'allowed_sites' => 'sanitize_textarea_field',
				'css' => array( $this, 'sanitize_code' ),
				'js' => array( $this, 'sanitize_code' ),
				'html' => array( $this, 'sanitize_code' ),
				'load_jq' => 'absint',
				'image_id' => 'absint',
			);
			foreach( $sanitizer as $key => $func ) {
				if( isset( $_POST['_ts_banner'][ $key ] ) ) {
					$data[ $key ] = call_user_func( $func, $_POST['_ts_banner'][ $key ] );
				}
			}
			if( isset( $_POST['banner-wysiwyg'] ) ) {
				$data['banner-wysiwyg'] = wp_kses_post( $_POST['banner-wysiwyg'] );
			}
			if( isset( $_POST['_banner_display_position'] ) ) {
				update_post_meta( $post_id, '_banner_display_position', sanitize_text_field( $_POST['_banner_display_position'] ) );
			}
			if( isset( $_POST['banner_display_alignment'] ) ) {
				update_post_meta( $post_id, '_banner_display_alignment', sanitize_text_field( $_POST['banner_display_alignment'] ) );
			}
			if( isset( $_POST['show_banner_on_post'] ) && $_POST['show_banner_on_post'] == 1 ) {
				update_post_meta( $post_id, '_show_on_post', true );
			} else {
				update_post_meta( $post_id, '_show_on_post', false );
			}
			update_post_meta( $post_id, '_ts_banner', $data );
		}
	}

	function check_clear() {

		global $pagenow;

		if( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ts-banner-ad' && isset( $_GET['clear_data'] ) ) {

			$banner_unique = get_post_meta( $_GET['clear_data'], '_ts_banner_unique', true );

			if( !$banner_unique || empty( $banner_unique ) ) {
				$banner_unique = md5( $_GET['clear_data'] . time() );
				update_post_meta( $_GET['clear_data'], '_ts_banner_unique', $banner_unique );
			}

			$query = new WP_Query( array(
				'post_type' => 'ts-banner-action',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => '_ts_ad_banner_id',
						'value' => $banner_unique,
					),
				),
			) );

			foreach( $query->posts as $to_delete ) {
				wp_delete_post( $to_delete->ID, true );
			}

			wp_redirect( add_query_arg( 'just_cleared_data', $_GET['clear_data'], remove_query_arg( 'clear_data' ) ) );

			exit;

		}

		if( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'ts-banner-ad' && isset( $_GET['just_cleared_data'] ) ) {
			$this->notices[] = array(
				'type' => 'success',
				'text' => wp_kses_post( sprintf( __( 'All statistics data were cleared for banner "%1$s"', 'ts-ads' ), get_the_title( $_GET['just_cleared_data'] ) ) ),
			);
		}

	}

	function sanitize_code( $content ) {
		return $content;
	}
	
	function init_actions() {

		$labels = array(
			'name' => _x( 'Banners', 'post type general name', 'ts-ads' ),
			'singular_name' => _x( 'Banner', 'post type singular name', 'ts-ads' ),
			'menu_name' => _x( 'Banner Ads', 'admin menu', 'ts-ads' ),
			'name_admin_bar' => _x( 'Banner', 'add new on admin bar', 'ts-ads' ),
			'add_new' => _x( 'Add New', 'banner', 'ts-ads' ),
			'add_new_item' => __( 'Add New Banner', 'ts-ads' ),
			'new_item' => __( 'New Banner', 'ts-ads' ),
			'edit_item' => __( 'Edit Banner', 'ts-ads' ),
			'view_item' => __( 'View Banner', 'ts-ads' ),
			'all_items' => __( 'All Banners', 'ts-ads' ),
			'search_items' => __( 'Search Banners', 'ts-ads' ),
			'parent_item_colon' => __( 'Parent Banners:', 'ts-ads' ),
			'not_found' => __( 'No banners found.', 'ts-ads' ),
			'not_found_in_trash' => __( 'No banners found in Trash.', 'ts-ads' )
		);

		$args = array(
			'labels' => $labels,
			'description' => __( 'Description.', 'ts-ads' ),
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => false,
			'rewrite' => array( 'slug' => 'banner' ),
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'menu_icon' => TS_ADS_URL . '/assets/images/logo.svg',
			'show_in_nav_menus' => false,
			'supports' => array( 'title', 'thumbnail' ),
		);

		register_post_type( 'ts-banner-ad', $args );

		register_post_type( 'ts-banner-action', array(
			'label' => __( 'Banner view/click data', 'ts-ads' ),
			'description' => '',
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => false,
			'capability_type' => 'post',
			'has_archive' => false,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array(),
		) );

		$this->check_clear();

	}

	function metabox() {
		add_meta_box( 'banner-ad-box', __( 'Banner Settings', 'ts-ads' ), array( $this, 'metabox_callback' ), 'ts-banner-ad', 'normal' );
	}

	function get_option( $name, $default = false, $ref_post = null ) {

		if( !is_null( $ref_post ) ) {
			$post = get_post( $ref_post );
			$meta = get_post_meta( $post->ID, '_ts_banner', true );
		} else {
			global $post;
			$meta = get_post_meta( $post->ID, '_ts_banner', true );
		}

		$meta = (array)$meta;

		if( isset( $meta[$name] ) ) {
			return $meta[$name];
		}

		return $default;

	}

	function metabox_callback() {
		
		wp_nonce_field( '_ts_banner_nonce', '_ts_banner_nonce' );

		$tabs = array(
			array( 'size-link', __( 'Size & Link', 'ts-ads' ), array( $this, 'cb_size_link' ) ),
			array( 'design', __( 'Design', 'ts-ads' ), array( $this, 'cb_design' ) ),
			array( 'display', __( 'Display Options', 'ts-ads' ), array( $this, 'cb_display' ) ),
		);

		?>
		<div class="ad-setting-tabs">
			<div class="tab-navigator">
				<ul>
					<?php
					foreach( $tabs as $tab ) {
						printf( '<li><a href="#tab-%s">%s</a></li>', esc_attr( $tab[0] ), esc_html( $tab[1] ) );
					}
					add_thickbox();
					global $post;
					$code_url = add_query_arg( array(
						'get_ts_banner_code' => $post->ID,
						'TB_iframe' => 'true',
						'width' => 'true',
						'height' => 'true',
					), admin_url('/') );
					?>
					<li class="icon right" title="<?php esc_attr_e( 'Preview Banner', 'ts-ads' ); ?>"><a href="<?php echo home_url( '/?banner_ad_preview=' . $post->ID ); ?>&TB_iframe=true&width=600&height=550" class="thickbox"><span class="dashicons dashicons-search"></span></a></li>
					<li class="icon right" title="<?php esc_attr_e( 'Get Codes', 'ts-ads' ); ?>"><a href="<?php echo esc_url( $code_url ); ?>" class="thickbox"><span class="dashicons dashicons-editor-code"></span></a></li>
				</ul>
			</div>
			<div class="tab-contents">
				<?php
				foreach( $tabs as $tab ) {
					printf( '<div class="tab-content" id="tab-%s">', esc_attr( $tab[0] ) );
					call_user_func( $tab[2] );
					echo '</div>';
				}
				?>
			</div>
		</div>
		<?php
	}

	function image_uploader( $id = 'image_id' ) {

		global $post;

		$upload_link = get_upload_iframe_src( 'image', $post->ID );

		$img_id = $this->get_option( $id );

		$img_src = wp_get_attachment_image_src( $img_id, 'full' );

		$have_img = is_array( $img_src );
		?>
		<div class="ts-banner-image-uploader" data-frame-title="<?php esc_attr_e( 'Select or Upload image', 'ts-ads' ); ?>" data-button-text="<?php esc_attr_e( 'Use this image', 'ts-ads' ); ?>">
			<div class="banner-img-container">
				<?php if ( $have_img ) : ?>
					<img src="<?php echo $img_src[0] ?>" alt="" style="max-width:100%;" />
				<?php endif; ?>
				<a class="delete-banner-img dashicons dashicons-no-alt <?php if ( ! $have_img  ) { echo 'hidden'; } ?>" href="#" title="<?php esc_attr_e( 'Remove this image', 'ts-ads' ) ?>"></a>
			</div>

			<div class="hide-if-no-js">
				<a class="upload-banner-img button button-primary" href="<?php echo esc_url( $upload_link ); ?>">
					<?php esc_html_e( 'Upload', 'ts-ads' ) ?>
				</a>
			</div>

			<input class="image-id-field" name="_ts_banner[<?php echo esc_attr( $id ); ?>]" type="hidden" value="<?php echo esc_attr( $img_id ); ?>" />
		</div>
		<?php
	}

	function cb_size_link() {
		$regular_sizes = array(
			'ssq-125x125' => __( 'Small Square', 'ts-ads' ),
			'msq-200x200' => __( 'Medium Square', 'ts-ads' ),
			'sqr-250x250' => __( 'Square', 'ts-ads' ),
			'mrc-350x250' => __( 'Medium Rectangle', 'ts-ads' ),
			'hfb-234x60' => __( 'Half Banner', 'ts-ads' ),
			'bnn-468x60' => __( 'Banner', 'ts-ads' ),
			'vbn-120x240' => __( 'Vertical Banner', 'ts-ads' ),
			'vrc-240x400' => __( 'Vertical Rectangle', 'ts-ads' ),
			'lbd-728x90' => __( 'Leaderboard', 'ts-ads' ),
			'bbd-728x300' => __( 'Billboard', 'ts-ads' ),
			'irc-300x250' => __( 'Inline Rectangle', 'ts-ads' ),
			'lrc-336x280' => __( 'Large Rectangle', 'ts-ads' ),
			'scs-120x600' => __( 'Skyscraper', 'ts-ads' ),
			'wsc-160x600' => __( 'Wide Skyscraper', 'ts-ads' ),
			'ews-240x600' => __( 'Extra Wide Skyscraper', 'ts-ads' ),
			'hpa-300x600' => __( 'Half-Page Ad', 'ts-ads' ),
			'lld-970x90' => __( 'Large Leaderboard', 'ts-ads' ),
		);

		$social_sizes = array(
			'fbc-828x315' => __( 'Facebook Cover', 'ts-ads' ),
			'fbp-1200x630' => __( 'Facebook Post', 'ts-ads' ),
			'fba-600x315' => __( 'Facebook Ad', 'ts-ads' ),
			'ttp-1024x512' => __( 'Twitter Post', 'ts-ads' ),
			'ttc-1500x500' => __( 'Twitter Cover', 'ts-ads' ),
			'inp-1080x1080' => __( 'Instagram Post', 'ts-ads' ),
			'ina-1200x628' => __( 'Instagram Ad', 'ts-ads' ),
			'pnp-600x1200' => __( 'Pinterest Post', 'ts-ads' ),
			'inc-1400x425' => __( 'LinkedIn Cover', 'ts-ads' ),
			'gpc-1080x608' => __( 'Google+ Cover', 'ts-ads' ),
			'scp-1080x1920' => __( 'Snapchat Post', 'ts-ads' ),
		);
		$current_size = $this->get_option( 'size' );
		$current_link = $this->get_option( 'link' );
		$current_target = $this->get_option( 'link_target' );
		?>
		<div class="banner-link-box">
			<table class="form-table">
				<tr>
					<th><label for="_ts_banner_link"><?php esc_html_e( 'Link', 'ts-ads' ); ?></label></th>
					<td><input type="text" name="_ts_banner[link]" id="_ts_banner_link" class="widefat" value="<?php echo esc_url( $current_link ); ?>"></td>
				</tr>
				<tr>
					<th><label for="_ts_banner_link_target"><?php esc_html_e( 'Link target', 'ts-ads' ); ?></label></th>
					<td>
						<select name="_ts_banner[link_target]" id="_ts_banner_link_target">
							<?php
							$targets = array(
								'_blank' => __( '(_blank) Open the link in a new window', 'ts-ads' ),
								'_self' => __( '(_self) Open the link in the same frame as it was clicked', 'ts-ads' ),
								'_parent' => __( '(_parent) Open the link in the parent frameset', 'ts-ads' ),
								'_top' => __( '(_top) Open the link in the full body of the window', 'ts-ads' ),
							);
							foreach( $targets as $target => $text ) {
								printf( '<option value="%s"%s>%s</option>', esc_attr( $target ), selected( $current_target, $target, false ), esc_html( $text ) );
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</div>
		<div class="banner-sizes-box">
			<h3 class="box-caption"><?php esc_html_e( 'Regular Sizes', 'ts-ads' ); ?></h3>
			<ul>
				<?php
				foreach( $regular_sizes as $size => $caption ) {
					$img = '';
					$args = explode( '-', $size );
					if( file_exists( TS_ADS_PATH . '/assets/images/ad-sizes/' . $args[0] . '.png' ) ) {
						$img = TS_ADS_URL . '/assets/images/ad-sizes/' . $args[0] . '.png';
					} elseif( file_exists( TS_ADS_PATH . '/assets/images/ad-sizes/' . $args[0] . '.jpg' ) ) {
						$img = TS_ADS_URL . '/assets/images/ad-sizes/' . $args[0] . '.jpg';
					}
					?>
					<li<?php if( $current_size == $args[1] ) { echo ' class="active"'; } ?>>
						<label<?php if( !empty( $img ) ) { echo ' class="image-available"'; } ?>>
							<?php
							if( !empty( $img ) ) {
								printf( '<img src="%s" />', esc_url( $img ) );
							}
							?>
							<input type="radio" name="_ts_banner[size]" value="<?php echo esc_attr( $args[1] ); ?>"<?php checked( $current_size, $args[1] ); ?>>
							<h5 class="size-dimensions"><?php echo esc_html( $args[1] ); ?></h5>
							<h4 class="size-caption"><?php echo esc_html( $caption ); ?></h4>
						</label>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<div class="banner-sizes-box">
			<h3 class="box-caption"><?php esc_html_e( 'Social Media Sizes', 'ts-ads' ); ?></h3>
			<ul>
				<?php
				foreach( $social_sizes as $size => $caption ) {
					$img = '';
					$args = explode( '-', $size );
					if( file_exists( TS_ADS_PATH . '/assets/images/ad-sizes/' . $args[0] . '.png' ) ) {
						$img = TS_ADS_URL . '/assets/images/ad-sizes/' . $args[0] . '.png';
					} elseif( file_exists( TS_ADS_PATH . '/assets/images/ad-sizes/' . $args[0] . '.jpg' ) ) {
						$img = TS_ADS_URL . '/assets/images/ad-sizes/' . $args[0] . '.jpg';
					}
					?>
					<li<?php if( $current_size == $args[1] ) { echo ' class="active"'; } ?>>
						<label<?php if( !empty( $img ) ) { echo ' class="image-available"'; } ?>>
							<?php
							if( !empty( $img ) ) {
								printf( '<img src="%s" />', esc_url( $img ) );
							}
							?>
							<input type="radio" name="_ts_banner[size]" value="<?php echo esc_attr( $args[1] ); ?>"<?php checked( $current_size, $args[1] ); ?>>
							<h5 class="size-dimensions"><?php echo esc_html( $args[1] ); ?></h5>
							<h4 class="size-caption"><?php echo esc_html( $caption ); ?></h4>
						</label>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}

	function cb_design() {
		$current_type = $this->get_option( 'type' );
		$current_css = $this->get_option( 'css' );
		$current_js = $this->get_option( 'js' );
		$current_html = $this->get_option( 'html' );
		$load_jq = $this->get_option( 'load_jq' );
		?>
		<table class="form-table tab-radio">
			<tr>
				<th><label><?php esc_html_e( 'Banner type', 'ts-ads' ); ?></label></th>
				<td>
					<label><input type="radio" name="_ts_banner[type]" value="image"<?php checked( $current_type, 'image' ); ?>> <?php esc_html_e( 'Image', 'ts-ads' ); ?>&nbsp;</label>
					<label><input type="radio" name="_ts_banner[type]" value="wysiwyg"<?php checked( $current_type, 'wysiwyg' ); ?>> <?php esc_html_e( 'Wysiwyg', 'ts-ads' ); ?>&nbsp;</label>
					<label><input type="radio" name="_ts_banner[type]" value="code"<?php checked( $current_type, 'code' ); ?>> <?php esc_html_e( 'HTML', 'ts-ads' ); ?>&nbsp;</label>
				</td>
			</tr>
			<tr data-tab-for="image">
				<th><label><?php esc_html_e( 'Select Image', 'ts-ads' ); ?></label></th>
				<td>
					<?php $this->image_uploader(); ?>
				</td>
			</tr>
		</table>
		<div data-tab-for="wysiwyg">
			<?php
			$wysiwyg = $this->get_option( 'banner-wysiwyg' );
			wp_editor( $wysiwyg, 'banner-wysiwyg', array( '' ) );
			?>
		</div>
		<h3><?php esc_html_e( 'Custom Codes', 'ts-ads' ); ?></h3>
		<p><?php _e( '<strong>NOTE:</strong> invalid codes may break your banner. Use carefully.' ); ?></p>
		<table class="form-table">
			<tr data-tab-for="code">
				<th>
					<label for="_ts_banner_html"><?php esc_html_e( 'HTML', 'ts-ads' ); ?></label>
					<p><?php esc_html_e( 'Paste HTML code within <body></body> tag' ); ?></p>
					<p><small><?php esc_html_e( 'Only applicable if using HTML banner type' ); ?></small></p>
				</th>
				<td>
					<textarea name="_ts_banner[html]" id="_ts_banner_html" cols="30" rows="10" class="widefat"><?php echo esc_textarea( $current_html ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="_ts_banner_css"><?php esc_html_e( 'CSS', 'ts-ads' ); ?></label>
					<p><?php esc_html_e( 'Paste CSS code to add inside the banner' ); ?></p>
					<em><?php esc_html_e( 'No html or javascript allowed' ); ?></em>
				</th>
				<td>
					<textarea name="_ts_banner[css]" id="_ts_banner_css" cols="30" rows="10" class="widefat"><?php echo esc_textarea( $current_css ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th>
					<label for="_ts_banner_js"><?php esc_html_e( 'JavaScript', 'ts-ads' ); ?></label>
					<p><?php esc_html_e( 'Paste JavaScript code to add inside the banner' ); ?></p>
					<em><?php esc_html_e( 'No html or CSS allowed' ); ?></em>
				</th>
				<td>
					<textarea name="_ts_banner[js]" id="_ts_banner_js" cols="30" rows="10" class="widefat"><?php echo esc_textarea( $current_js ); ?></textarea>
					<label><input type="checkbox" name="_ts_banner[load_jq]" value="1"<?php checked( $load_jq, 1 ); ?>> <?php esc_html_e( 'Load jQuery before this code', 'ts-ads' ); ?>&nbsp;</label>
				</td>
			</tr>
		</table>
		<?php
	}

	function cb_display() {
		global $post;
		$show_on_post = get_post_meta( $post->ID, '_show_on_post', true );
		$position = get_post_meta( $post->ID, '_banner_display_position', true );
		$alignment = get_post_meta( $post->ID, '_banner_display_alignment', true );
		?>
		<h1><?php esc_html_e( 'Automatic display', 'ts-ads' ); ?></h1>
		<table class="form-table">
			<tr>
				<th><label for="show_banner_on_post"><?php esc_html_e( 'Show this banner inside posts' ); ?></label></th>
				<td>
					<input type="checkbox" name="show_banner_on_post" id="show_banner_on_post" value="1"<?php checked( $show_on_post, true ); ?>>
				</td>
			</tr>
			<tr>
				<th>
					<label for="_banner_display_position"><?php esc_html_e( 'Display Position', 'ts-ads' ); ?></label>
				</th>
				<td>
					<select name="_banner_display_position" id="_banner_display_position">
						<option value="top"<?php selected( $position, 'top' ); ?>><?php esc_html_e( 'Top of post content', 'ts-ads' ); ?></option>
						<option value="middle"<?php selected( $position, 'middle' ); ?>><?php esc_html_e( 'Middle of post content', 'ts-ads' ); ?></option>
						<option value="end"<?php selected( $position, 'end' ); ?>><?php esc_html_e( 'End of post content', 'ts-ads' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<h1><?php esc_html_e( 'Shorcode display', 'ts-ads' ); ?></h1>
		<table class="form-table">
			<tr>
				<th>
					<label for="banner_display_alignment"><?php esc_html_e( 'Display Alignment', 'ts-ads' ); ?></label>
				</th>
				<td>
					<select name="banner_display_alignment" id="banner_display_alignment">
						<option value="inline"<?php selected( $alignment, 'inline' ); ?>><?php esc_html_e( 'Regular', 'ts-ads' ); ?></option>
						<option value="inline-left"<?php selected( $alignment, 'inline-left' ); ?>><?php esc_html_e( 'Inline Left', 'ts-ads' ); ?></option>
						<option value="inline-right"<?php selected( $alignment, 'inline-right' ); ?>><?php esc_html_e( 'Inline Right', 'ts-ads' ); ?></option>
						<option value="left"<?php selected( $alignment, 'left' ); ?>><?php esc_html_e( 'Left', 'ts-ads' ); ?></option>
						<option value="center"<?php selected( $alignment, 'center' ); ?>><?php esc_html_e( 'Center', 'ts-ads' ); ?></option>
						<option value="right"<?php selected( $alignment, 'right' ); ?>><?php esc_html_e( 'Right', 'ts-ads' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="banner_allowed_sites">
						<?php esc_html_e( 'Allowed Domains', 'ts-ads' ); ?><br>
						<small><em><?php esc_html_e( 'One domain each line. Use only an asterisk (*) here to allow on any domain.', 'ts-ads' ); ?></em></small><br>
						<small><em><?php esc_html_e( 'No need to put http/https/www. Example: yourdomain.com or sub.domain.com', 'ts-ads' ); ?></em></small>
						<small><em><?php esc_html_e( 'Home domain is allowed by default.', 'ts-ads' ); ?></em></small>
					</label>
				</th>
				<td>
					<textarea name="_ts_banner[allowed_sites]" id="banner_allowed_sites" class="widefat" rows="10"><?php echo esc_textarea( $this->get_option( 'allowed_sites' ) ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

}

new TS_Ads_Post_Types();