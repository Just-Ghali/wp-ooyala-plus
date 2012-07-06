<style type="text/css" media="screen">
	#icon-ooyala {
		background: transparent url(<?php echo plugins_url( 'img/ooyala-icon.png', __FILE__ ); ?>) no-repeat;
	}
	.tablenav-pages a { font-weight: normal;}
	.ooyala-item {float:left; height:175px; width:265px; padding:4px; border:1px solid #DFDFDF; margin:4px; box-shadow: 2px 2px 2px #DFDFDF;}
	.ooyala-item .item-title {height: 20px;}
	.ooyala-item .photo { margin:4px; }
	.ooyala-item .photo img { width: 256px; height:144px}
	.ooyala-item .item-title {text-align:center;}
</style>

<div class="wrap">
	<?php screen_icon( 'ooyala-plus' ); ?>
	<h2>
		<?php _e( 'Ooyala Video Browser', 'ooyalaplusvideo' ); ?>
	</h2>
	<?php

	$backlot = new WP_Ooyala_Backlot_Plus( get_option( 'ooyala-plus' ) );

	if ( isset( $_POST['submit'] ) ) {
		$response = $backlot->update(
			json_encode(
				array(
					'name' => sanitize_text_field( $_POST['ooyala']['title'] ),
					'embed_code' => sanitize_text_field( $_POST['ooyala']['embed'] ),
					'description' => sanitize_text_field( $_POST['ooyala']['description'] )
				)
			),
			sanitize_text_field( $_POST['ooyala']['embed' ] )
		);
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			echo '<div id="message" class="updated below-h2"><p>' . __( 'Video updated.', 'ooyalaplusvideo' ) . '</p></div>';
		else
			echo '<div id="message" class="error below-h2"><p>' . __( 'There was an error communicating with Ooyala API.', 'ooyalaplusvideo' ) . '</p></div>';
	} elseif ( !empty( $_GET['edit'] ) ) {
		$response = $backlot->query(
			array(
				'where' => "embed_code='" . esc_attr( $_GET['edit'] ) . "'"
			),
			array(),
			true
		);
	} else {
		$key_word = isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '';
		$field = isset( $_GET['ooyalasearchfield'] ) ? esc_attr( $_GET['ooyalasearchfield'] ) : '';

		if ( $key_word && $field )
			$where = $field . "='" . $key_word . "' AND status='live'";
		else
			$where = "status='live'";

		$response = $backlot->query( array(
			'where'   => $where,
			'orderby' => 'created_at descending',
			'limit'   => 16,
		), array(), true );


		if ( 200 != wp_remote_retrieve_response_code( $response ) )
			echo '<div id="message" class="error below-h2"><p>' . __( 'Ooyala API is currently unavailable.', 'ooyala-plus' ) . '</p></div>';
	} ?>

	<form id="ooyala-search">
		<input type="hidden" name="page" value="ooyala-browser" />
		<p class="">
			<select name="ooyalasearchfield" id="ov-search-field">
				<option value="description" selected="selected"><?php esc_attr_e( 'Description', 'ooyalaplusvideo' ); ?></option>
				<option value="name"><?php esc_attr_e( 'Name', 'ooyalaplusvideo' ); ?></option>
			</select>
			<label class="screen-reader-text" for="ooyala-search-input"><?php esc_html_e( 'Search', 'ooyalaplusvideo' ); ?></label>
			<input type="text" id="ooyala-search-input" name="s" value="" />
			<?php submit_button( __( 'Search', 'ooyalaplusvideo' ), 'secondary', 'ooyala-search', false ) ?>
		</p>
	</form>

	<?php if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
		$videos = json_decode( wp_remote_retrieve_body( $response ) );
		$videos = stripslashes_deep( $videos );
		if ( empty( $videos->items ) )
			_e( 'No videos found.', 'ooyalaplusvideo' );

		if ( !empty( $_GET['edit'] ) ) :
			$video = isset( $_POST['submit'] ) ? $videos : $videos->items[0]; ?>
			<form id="ooyala-edit-video" method="post" action="<?php echo add_query_arg( 'edit', sanitize_text_field( $_GET['edit'] ), menu_page_url( 'ooyala-browser', false ) ); ?>">
				<input type="hidden" name="ooyala[embed]" value="<?php echo esc_attr( $video->embed_code ); ?>" />
				<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Title', 'ooyalavide' ); ?></th>
						<td><input type="text" id="ooyala-title" name="ooyala[title]" value="<?php echo esc_attr( $video->name ); ?>" class="regular-text"></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Description', 'ooyalavide' ); ?></th>
						<td><textarea rows="4" cols="40" id="ooyala-description" tabindex="6" name="ooyala[description]"><?php echo esc_textarea( $video->description ); ?></textarea></td>
					</tr>
				</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		<?php else :

			$output = '<div id="ooyala-items">';

			foreach ( $videos->items as $video ) {
				$edit_url = add_query_arg( 'edit', $video->embed_code, menu_page_url( 'ooyala-browser', false ) );
				$output .= '
				<div id="ooyala-item-' . esc_attr( $video->embed_code ) . '" class="ooyala-item">
					<div class="item-title"><a href="' . esc_url( $edit_url ) . '" title="' . esc_attr( $video->embed_code ) .'" class="use-shortcode">' . esc_attr( $video->name ) .'</a></div>
					<div class="photo">
						<a href="' . esc_url( $edit_url ) . '" title="' . esc_attr( $video->embed_code ) .'" class="use-shortcode"><img src="' . esc_url( $video->preview_image_url ) . '"></a>
					</div>
				</div>';
			}
			$output.='</div><div style="clear:both;"></div>';
			echo $output;
		endif;
	} ?>
</div>