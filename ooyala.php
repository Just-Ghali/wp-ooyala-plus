<?php
/**
 * Plugin Name: Ooyala Video Plus
 * Description: Extends feature functionality off <a href="http://www.ooyala.com/wordpressplugin/">Ooyala Video</a> to include Youtube videos, monetizable and async shortcodes, appending labels to video uploads.
 * Version: 1.0
 * Author: Mahmoud Ghali ( Ghali™ )
 * License: MIT
 *
 * Contact mail: mahmoud.ghali@gmail.com
 *
 * Note: this Plugin is based on

  		Plugin Name - Ooyala Video
 		Plugin URI - http://www.ooyala.com/wordpressplugin/
 		Description - Easy Embedding of Ooyala Videos based off an Ooyala Account as defined in the <a href="options-general.php?page=ooyala-plus-options"> plugin settings</a>.
 		Version - 1.5
		License - GPL
		Author - David Searle

 *
 */
require_once( dirname(__FILE__) . '/class-wp-ooyala-backlot-api.php' );

class Ooyala_Plus_Video {

	const VIDEOS_PER_PAGE = 8;
	var $plugin_dir;
	var $plugin_url;
	var $partner_code;
	var $secret_code;

	/**
	 * Singleton
	 */
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			load_plugin_textdomain( 'ooyalaplusvideo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$instance = new Ooyala_Plus_Video;
		}

		return $instance;
	}

	/**
	 * Constructor
	 */
	function __construct() {

		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		if ( is_admin() ) {
			require_once( dirname( __FILE__ ) . '/ooyala-plus-options.php' );

			$partner_code = get_option( 'ooyalaplusvideo_partnercode' );
			if ( $partner_code ) {
				$secret_code  = get_option( 'ooyalaplusvideo_secretcode' );
				$show_in_feed = get_option( 'ooyalaplusvideo_showinfeed' );
				$video_width  = get_option( 'ooyalaplusvideo_width' );

				$options = array(
					'partner_code' => $partner_code,
					'secret_code'  => $secret_code,
					'show_in_feed' => $show_in_feed,
					'video_width'  => $video_width
				);
				update_option( 'ooyala-plus', $options );
				delete_option( 'ooyalaplusvideo_partnercode' );
				delete_option( 'ooyalaplusvideo_secretcode' );
				delete_option( 'ooyalaplusvideo_showinfeed' );
				delete_option( 'ooyalaplusvideo_width' );
			} else {
				$options = get_option( 'ooyala-plus', array( 'partner_code' => '', 'secret_code' => '' ) );
				$this->partner_code = $options['partner_code'];
				$this->secret_code  = $options['secret_code'];
			}
		}

		add_action( 'admin_menu', 				array( &$this, 'add_media_page' 	) );
		add_action( 'admin_init', 				array( &$this, 'register_script' 	) );
		add_action( 'media_buttons', 			array( &$this, 'media_button'		), 999 );
		add_action( 'wp_ajax_ooyala_popup', 	array( &$this, 'popup' 				) );
		add_action( 'wp_ajax_ooyala_set', 		array( &$this, 'ooyala_set' 		) );
		add_action( 'wp_ajax_ooyala_request', 	array( &$this, 'ooyala_request' 	) );
		// add youtube wp ajax action
		add_action( 'wp_ajax_ooyala_add_youtube', 	array( &$this, 'ooyala_add_youtube' ) );
		add_action( 'wp_ajax_ooyala_set_featured_video', array( &$this, 'ooyala_set_featured_video' ) );
		// add ooyala Script
		wp_enqueue_script( 'ooyala_async_js', $this->plugin_url . 'js/ooyala-async.js', array( 'jquery' ) , '1.0.0' );
		// add shortcode
		add_shortcode( 'ooyala', array(&$this, 'shortcode') );
	}

	function Ooyala_Plus_Video() {
		$this->__construct();
	}

	/**
	* Migrate the secret and partner code from the config.php file, if exists.
	* Only runs on plugin activation if option is not set.
	*/
	function migrate_config() {

		// Check no options are set yet
		if ( false === get_option( 'ooyalaplusvideo_partnercode' ) && false === get_option( 'ooyalaplusvideo_secretcode' ) ) {
			$config_file = dirname(__FILE__).'/config.php';

			if ( file_exists( $config_file ) ) {
				include_once( $config_file );
				$options = array(
					'partner_code'  => defined( 'OOYALA_PARTNER_CODE' ) ? esc_attr( 'OOYALA_PARTNER_CODE' ) : '',
					'parner_secret' => defined( 'OOYALA_SECRET_CODE'  ) ? esc_attr( 'OOYALA_SECRET_CODE'  ) : ''
				);
				update_option( 'ooyala-plus', $options );
			}
		}
	}

	/**
	 * Registers and localizes the plugin javascript
	 */
	function register_script() {
		wp_register_script( 'ooyala-plus', $this->plugin_url . 'js/ooyala.js', array( 'jquery' ), '1.4' );
		wp_localize_script( 'ooyala-plus', 'ooyalaL10n', array(
			'latest_videos' => __( 'Latest Videos', 'ooyalaplusvideo' ),
			'search_results' => __( 'Search Results', 'ooyalaplusvideo' ),
			'done' => __( 'Done!', 'ooyalaplusvideo' ),
			'upload_error' => __( 'Upload Error', 'ooyalaplusvideo' ),
			'use_as_featured' => __( 'Use as featured image', 'ooyalaplusvideo' ),
		) );
		//Register multiselect box scripts and styles
		wp_register_script( 'ooyala-chosen', $this->plugin_url . 'js/chosen.jquery.min.js', array( 'jquery' ) );
		wp_register_style( 'ooyala-chosen', $this->plugin_url . 'css/chosen.css' );
	}

	/**
	 * Shortcode Callback
	 * @param array $atts Shortcode attributes
	 */
	function shortcode( $atts ) {

		/* Example shortcodes:
		  Legacy: [ooyala NtsSDByMjoSnp4x3NibMn32Aj640M8hbJ]
		  Updated: [ooyala code="NtsSDByMjoSnp4x3NibMn32Aj640M8hbJ" width="222" ]
		*/

		extract(shortcode_atts(array(
			'width' => '',
			'code' => '',
			'autoplay' => 'false',
			'callback' => 'recieveOoyalaEvent',
			'wmode' => 'opaque',
			'title' => '',
			'image' => '',
			), $atts
		));

		$options = get_option( 'ooyala-plus' );
		if ( empty($width) )
			$width = $options['video_width'];
		if ( empty($width) )
			$width = $GLOBALS['content_width'];
		if ( empty($width) )
				$width = 500;

		$width = (int) $width;
		$height = floor( $width*9/16 );
		$autoplay = (bool) $autoplay ? '1' : '0';
		$sanitized_embed = sanitize_key( $code );
		$wmode = in_array( $wmode, array( 'window', 'transparent', 'opaque', 'gpu', 'direct' ) ) ? $wmode : 'opaque';
		$callback = preg_match( '/[^\w]/', $callback ) ? '' : sanitize_text_field( $callback ); // // sanitize a bit because we don't want nasty things

		if ( empty( $code ) )
			if ( isset( $atts[0] ) )
				$code = $atts[0];
			else
				return '<!--Error: Ooyala shortcode is missing the code attribute -->';

		if( preg_match( "/[^a-z^A-Z^0-9^\-^\_]/i", $code ) )
			return '<!--Error: Ooyala shortcode attribute contains illegal characters -->';

		$output = '';

		if ( ! is_feed() ) {

			/**
			 * @todo Have Virgilio add a higher z-index than the <p> tags
			 * @todo Remove <a> tag from around the video thumbnail.
			 */


			if ( empty( $title ) && empty( $image ) ) {

				$output .= '<div style="z-index:9;" class="ooyala-video" id="' . esc_attr( $code ) . '" data-autoplay="' . esc_attr( $autoplay ) . '" data-callback="' . esc_attr( $callback ) . '" >';

			} else {

				$output .= '<div style="z-index:9;" class="story-inline-item ooyala-video" id="' . esc_attr( $code ) . '" data-autoplay="' . esc_attr( $autoplay ) . '" data-callback="' . esc_attr( $callback ) . '" data-onclick="true" ><div class="inline-video">';

				if( ! empty( $image ) )
					$output .= '<div class="inline-video-thumb"><a><img src="' . esc_url_raw( $image ) . '" /></a></div><div class="clear"></div>';
				if( ! empty( $title ) )
					$output .= '<div class="inline-video-title">' . esc_attr( $title ) . '</div>';

				$output .= '</div>';

			}

			$output .= '</div>';


		} elseif ( $options['show_in_feed'] ) {
			$output = __( '[There is a video that cannot be displayed in this feed. ', 'ooyalaplusvideo' ) . '<a href="' . get_permalink() . '">' . __( 'Visit the blog entry to see the video.]', 'ooyalaplusvideo' ) . '</a>';
		}

		return $output;
	}


	/**
	 * Add options page
	 */
	function add_media_page() {
		add_media_page( __( 'Ooyala Plus', 'ooyalaplusvideo' ), __( 'Ooyala Plus Video', 'ooyalaplusvideo' ), 'upload_files', 'ooyala-browser', array( &$this, 'media_page' ) );
	}

	/**
	 * Adds the Ooyala button to the media upload
	 */
	function media_button() {

		global $post_ID, $temp_ID;
		$iframe_post_id = (int) ( 0 == $post_ID ? $temp_ID : $post_ID );

		$title = esc_attr__( 'Embed Ooyala Video', 'ooyalaplusvideo' );
		$plugin_url = esc_url( $this->plugin_url );
		$site_url = admin_url( "/admin-ajax.php?post_id=$iframe_post_id&amp;ooyala=popup&amp;action=ooyala_popup&amp;TB_iframe=true&amp;width=768" );
		echo '<a href="' . $site_url . '&id=add_form" class="thickbox" title="' . $title . '"><img src="' . $plugin_url . 'img/ooyalavideo-button.png" alt="' . $title . '" width="13" height="12" /></a>';
	}


	/**
	 * Callback for ajax popup call. Outputs ooyala-popup.php
	 */
	function popup() {
		require_once( $this->plugin_dir . 'ooyala-popup.php' );
		die();
	}

	/**
	 * Adds a .jpg extension to the filename (for use with filenames retrieved from the thumbnail api)
	 * Called by set_thumbnail()
	 * @param string $filename
	 * @return filename with added jpg extension
	 */
	function add_extension( $filename ) {
	    $info = pathinfo($filename);
	    $ext  = empty($info['extension']) ? '.jpg' : '.' . $info['extension'];
	    $name = basename($filename, $ext);
	    return $name . $ext;
	}

	/**
	 * Sets an external URL as post featured image ('thumbnail')
	 * Contains most of core media_sideload_image(), modified to allow fetching of files with no extension
	 *
	 * @param string $url
	 * @param int $_post_ID
	 * @return $thumbnail_id - id of the thumbnail attachment post id
	 */
	function set_thumbnail( $url,  $_post_id ) {

		if ( !current_user_can( 'edit_post', $_post_id ) )
			die( '-1' );

		if ( empty( $_post_id) )
			die( '0');

		add_filter('sanitize_file_name', array(&$this, 'add_extension' ) );

		// Download file to temp location
		$tmp = download_url( $url );
		remove_filter('sanitize_file_name', array(&$this, 'add_extension' ) );

		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $tmp, $matches);
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff
		$thumbnail_id = media_handle_sideload( $file_array, $_post_id, '' );

		// If error storing permanently, unlink
		if ( is_wp_error($thumbnail_id) ) {
			@unlink($file_array['tmp_name']);
			return false;
		}

		return $thumbnail_id;
	}

	/**
	 * Ajax callback that sets a post thumbnail based on an ooyala embed id
	 *
	 * output html block for the meta box (from _wp_post_thumbnail_html() )
	 */
	function ooyala_set() {
		global $post_ID;

		$nonce = isset( $_POST ['_wpnonce'] ) ?  $_POST['_wpnonce'] : '';

		if (! wp_verify_nonce($nonce, 'ooyala-plus') )
		 	die('Security check');

		$_post_id = absint( $_POST['postid'] );

		// Make sure the global is set, otherwise the nonce check in set_post_thumbnail() will fail
		$post_ID = (int) $_post_id;

		//Let's set the thumbnails size
		if ( isset($_wp_additional_image_sizes['post-thumbnail']) ) {
			$thumbnail_width = $_wp_additional_image_sizes['post-thumbnail']['width'];
			$thumbnail_height = $_wp_additional_image_sizes['post-thumbnail']['height'];
		}
		else {
			$thumbnail_width = 640;
			$thumbnail_height = 640;
		}

		$url = isset( $_POST['img'] ) ? esc_attr( $_POST['img'] ) : '';
		$thumbnail_id = $this->set_thumbnail( $url, $_post_id );

		if ( false !== $thumbnail_id ) {
			set_post_thumbnail( $_post_id, $thumbnail_id );
			die( _wp_post_thumbnail_html( $thumbnail_id ) );
		}

	}

	/**
	 * Ajax callback that handles the request to Ooyala API from the Ooyala popup
	 *
	 * @uses WP_Ooyala_Backlot_Plus->query() to run the queries
	 * @uses WP_Ooyala_Backlot_Plus->print_results() to output the results
	 */
	function ooyala_request() {
		global $_wp_additional_image_sizes;

		if ( !isset( $_GET['ooyala'] ) )
			die('-1');

		$do = $_GET['ooyala'];

		$limit = Ooyala_Plus_Video::VIDEOS_PER_PAGE;

		$key_word = isset( $_GET['key_word'] ) ? esc_attr( $_GET['key_word'] ) : '';
		$field = isset( $_GET['search_field'] ) ? esc_attr( $_GET['search_field'] ) : 'description';
		$pageid = isset( $_GET['pageid'] ) ? $_GET['pageid'] : '';
		$backlot = new WP_Ooyala_Backlot_Plus( get_option( 'ooyala-plus' ) );
		switch( $do ) {
			case 'search':
				if ( '' != $pageid &&  '' != $key_word ) {
					$backlot->query( array(
						'where'        => $field . "='" . $key_word . "' AND status='live'",
						'orderby'      => 'created_at descending',
						'limit'        => $limit,
						'papage_token' => absint( $pageid )
					) );
				} else if ( '' != $key_word ) {
					$backlot->query( array(
						'where'   => $field . "='" . $key_word . "' AND status='live'",
						'orderby' => 'created_at descending',
						'limit'   => $limit,
					) );
				}
				else {
					echo 'Please enter a search term!';
					die();
				}
			break;
	 		case 'last_few':
				if ( !empty( $pageid) ) {
					$backlot->query( array(
						'where'      => "status='live'",
						'orderby'    => 'created_at descending',
						'limit'      => $limit,
						'page_token' => absint( $pageid )
					));
				} else {
					$backlot->query( array(
						'where'   => "status='live'",
						'orderby' => 'created_at descending',
						'limit'   => $limit
					) );
				}
			break;
		}
		die();
	}

	/**
	 * Ajax callback that handles the request to Ooyala API from the youtube tab
	 *
	 * @uses WP_Ooyala_Backlot_Plus->add_youtube() to add youtube videos to backlot
	 */
	function ooyala_add_youtube(){

		$nonce = isset( $_POST ['_wpnonce'] ) ?  $_POST['_wpnonce'] : '';

		if (! wp_verify_nonce($nonce, 'ooyala-plus') )
		 	die('Security check');

		$backlot = new WP_Ooyala_Backlot_Plus( get_option( 'ooyala-plus' ) );
		$backlot->add_youtube( (string)$_POST['id'], (string)$_POST['name'] );

		die();

	}

	/**
	 * Ajax callback to set featured video
	 *
	 * @uses	update_post_meta()  Updates the post meta custom field
	 *
	 * @return	boolean  true on success and false on failure
	 *
	 * @todo Move cdc_featured_video_id feild name to the opions page, for better portability. and create a meta box through the plugin.
	 */
	function ooyala_set_featured_video() {

		$nonce = isset( $_POST ['_wpnonce'] ) ?  $_POST['_wpnonce'] : '';

		if (! wp_verify_nonce($nonce, 'ooyala-plus') )
		 	die('Security check');

		$_post_id  = absint( $_POST['postid'] );

		if ( !isset( $_POST['embedcode'] ) )
			die('-1');

		// Update featured video image if available
		if ( isset( $_POST['image'] ) )
			update_post_meta( $_post_id, 'cdc_featured_video_image', esc_attr( $_POST['image'] ) );

		// Update featured video title if available
		if ( isset( $_POST['title'] ) )
			update_post_meta( $_post_id, 'cdc_featured_video_title', esc_attr( $_POST['title'] ) );

		// Update featured video Description if available
		if ( isset( $_POST['title'] ) )
			update_post_meta( $_post_id, 'cdc_featured_video_description', esc_attr( $_POST['description'] ) );

		$set_feature = update_post_meta( $_post_id, 'cdc_featured_video_id', esc_attr( $_POST['embedcode'] ) );

		die( $set_feature );

	}

	function media_page() {
		require_once( dirname( __FILE__ ) . '/ooyala-browser.php' );
	}
}

//Run option migration on activation
register_activation_hook( __FILE__ , array( 'Ooyala_Plus_Video', 'migrate_config' ) );

//Launch
add_action( 'init', array( 'Ooyala_Plus_Video', 'init' ) );
