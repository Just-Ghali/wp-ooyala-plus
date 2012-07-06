<?php

class PD_Ooyala_Plus_Options {
	public static $instance;

	public function __construct() {
		self::$instance = $this;
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	public function add_menu_page() {
		add_options_page( __( 'Ooyala Plus Video Options', 'ooyalaplusvideo' ), __( 'Ooyala Plus', 'ooyalaplusvideo' ), 'manage_options', 'ooyala-plus-options', array( $this, 'render_options_page' ) );
	}

	public function settings_init() {
		register_setting( 'ooyala_settings', 'ooyala-plus', array( $this, 'sanitize_settings' ) );
		add_settings_section( 'ooyala-general', '', '__return_false', 'ooyala-plus-options' );
		add_settings_field( 'ooyala-partner-code', __( 'Partner Code (V1)', 'ooyalaplusvideo' ), array( $this, 'partner_code' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-partner-secret', __( 'Partner Secret (v1)', 'ooyalaplusvideo' ), array( $this, 'secret_code' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-api-key', __( 'API Key (v2)', 'ooyalaplusvideo' ), array( $this, 'api_key' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-api-secret', __( 'API Secret (v2)', 'ooyalaplusvideo' ), array( $this, 'api_secret' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-show-in-feed', __( 'Show link to blog post in feed', 'ooyalaplusvideo' ), array( $this, 'show_in_feed' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-video-width', __( 'Video object width', 'ooyalaplusvideo' ), array( $this, 'video_width' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-video-status', __( 'Default video status', 'ooyalaplusvideo' ), array( $this, 'video_status' ), 'ooyala-plus-options', 'ooyala-general' );
		// add dynamic label prefix for any user-selected dynamic labels
		add_settings_field( 'ooyala-dynamic-label-prefix', __( 'Default dynamic label prefix', 'ooyalaplusvideo' ), array( $this, 'dynamic_label_prefix' ), 'ooyala-plus-options', 'ooyala-general' );
		add_settings_field( 'ooyala-allow-featured-upload', __( 'Allow featured video on upload', 'ooyalaplusvideo' ), array( $this, 'allow_featured_upload' ), 'ooyala-plus-options', 'ooyala-general' );
	}

	public function partner_code() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['partner_code'] ) )
			$options['partner_code'] = '';
		?><input type="text" id="ooyala-partner-code" name="ooyala-plus[partner_code]" value="<?php echo esc_attr( $options['partner_code'] ); ?>" class="regular-text" /><?php
	}

	public function secret_code() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['secret_code'] ) )
			$options['secret_code'] = '';
		?><input type="text" id="ooyala-partner-secret" name="ooyala-plus[secret_code]" value="<?php echo esc_attr( $options['secret_code'] ); ?>" class="regular-text" /><?php
	}

	public function api_key() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['api_key'] ) )
			$options['api_key'] = '';
		?><input type="text" id="ooyala-api-key" name="ooyala-plus[api_key]" value="<?php echo esc_attr( $options['api_key'] ); ?>" class="regular-text" /><?php
	}

	public function api_secret() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['api_secret'] ) )
			$options['api_secret'] = '';
		?><input type="text" id="ooyala-api-secret" name="ooyala-plus[api_secret]" value="<?php echo esc_attr( $options['api_secret'] ); ?>" class="regular-text" /><?php
	}

	public function show_in_feed() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['show_in_feed'] ) )
			$options['show_in_feed'] = 0;
		?><input type="checkbox" id="ooyala-show-in-feed" name="ooyala-plus[show_in_feed]" onchange="this.value = (this.checked) ? '1' : '0';" value="<?php echo esc_attr( $options['show_in_feed'] ); ?>" <?php checked( $options['show_in_feed'] ); ?> />
		<span class="description"><?php echo esc_html( 'Video embedding in feeds is not yet available', 'ooyalaplusvideo' ); ?></span><?php
	}

	public function video_width() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['video_width'] ) )
			$options['video_width'] = 250;
		?><input type="text" id="ooyala-video-width" name="ooyala-plus[video_width]" value="<?php echo esc_attr( $options['video_width'] ); ?>" class="regular-text" /><?php
	}

	public function video_status() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['video_status'] ) )
			$options['video_status'] = 'pending';
		?><select id="ooyala-video-status" name="ooyala-plus[video_status]">
				<option value="pending" <?php selected( $options['video_status'], 'pending' ); ?>><?php _e( 'Pending', 'ooyalaplusvideo' ); ?></option>
				<option value="live" <?php selected( $options['video_status'], 'live' ); ?>><?php _e( 'Live', 'ooyalaplusvideo' ); ?></option>
		</select><?php
	}

	public function dynamic_label_prefix() {
		$options = get_option( 'ooyala-plus', array() );
		if ( ! isset( $options['dynamic_label_prefix'] ) )
			$options['dynamic_label_prefix'] = '/canada.com/';
		?><input type="text" id="ooyala-dynamic-label-prefix" name="ooyala-plus[dynamic_label_prefix]" value="<?php echo esc_attr( $options['dynamic_label_prefix'] ); ?>" class="regular-text" /><?php
	}

	public function allow_featured_upload() {
		$options = get_option( 'ooyala-plus', array() );
			if ( ! isset( $options['allow_featured_upload'] ) )
			$options['allow_featured_upload'] = 0;
		?><input type="checkbox" id="ooyala-allow-featured-upload" name="ooyala-plus[allow_featured_upload]" onchange="this.value = (this.checked) ? '1' : '0';"  value="<?php echo esc_attr( $options['allow_featured_upload'] ); ?>"  <?php checked( $options['allow_featured_upload'] ); ?> />
		<span class="description"><?php echo esc_html( 'Allow adding uploaded videos as featured before processing', 'ooyalaplusvideo' ); ?></span><?php
	}

	public function sanitize_settings( $options ) {
		foreach ( $options as $option_key => &$option_value ) {
			switch ( $option_key ) {
				case 'partner_code' :
				case 'secret_code' :
				case 'api_key' :
				case 'api_secret' :
				case 'video_status' :
				case 'dynamic_label_prefix' :
					$option_value = esc_attr( $option_value );
					break;

				case 'show_in_feed' :
				case 'allow_featured_upload' :
					$option_value = absint( $option_value );
					break;

				case 'video_width':
					$option_value = absint( $option_value );
					if ( $option_value > 800 )
						$option_value = 800;
					elseif ( $option_value < 250 )
						$option_value = 250;
					$options[$option_key] = $option_value;
					break;
			}
		}
		return $options;
	}

	public function render_options_page() { ?>
		<style type="text/css" media="screen">
			#icon-ooyala {
				background: transparent url(<?php echo plugins_url( 'img/ooyala-icon.png', __FILE__ ); ?>) no-repeat;
			}
		</style>

		<div class="wrap">
			<?php screen_icon( 'ooyala-plus' ); ?>
			<h2><?php _e( 'Ooyala Plus Settings', 'ooyalaplusvideo' ); ?></h2>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'ooyala_settings' );
					do_settings_sections( 'ooyala-plus-options' );
					submit_button();
				?>
			</form>
		</div><?php

	}
}

new PD_Ooyala_Plus_Options;