<?php
/**
 * WordPress Class for interfacing with the Ooyola Backlot API v2
 *
 * @since 1.0
 * @author Ghali™
 *
 * @package Ooyala Plus
 * @subpackage API
 *
 * @see http://api.ooyala.com/docs/v2
 *
 * Note: This Class is based on WP_Ooyala_Backlot Class in Ooyala Video plugin http://www.ooyala.com/wordpressplugin/
 */
class WP_Ooyala_Backlot_Plus {
	var $partner_code;
	var $api_key;
	var $api_secret;

	public function __construct( $args ) {
		$this->partner_code = $args['partner_code'];
		$this->api_key = $args['api_key'];
		$this->api_secret = $args['api_secret'];
	}

	/**
	 * Sign ooyala api request
	 *
	 * @param Array $request Request object to be made
	 * @param Array $params  Array of the different query string parameters to be added to the request
	 *
	 * @return String    Signature string
	 */
	private function sign_request( $request, $params ) {
		$defaults = array(
			'api_key' => $this->api_key,
			'expires' => time() + 900,
		);
		$params = wp_parse_args( $params, $defaults );

		$signature = $this->api_secret . $request['method'] . $request['path'];
		ksort( $params );
		foreach ( $params as $key => $val )
			$signature .= $key . '=' . $val;

		$signature .= empty( $request['body'] ) ? '' : $request['body'];

		$signature = hash( 'sha256', $signature, true );
	    $signature = preg_replace( '#=+$#', '', trim( base64_encode( $signature ) ) );

		return $signature;
	}

	/**
	 * Send a patch request to ooyala API
	 *
	 * @param Object $body A json object with ooyala api actions
	 * @param String $path Oooyala Api object path after /v2/assets
	 *
	 * @return Array    with body response and request status code.
	 */
	public function update( $body, $path ) {
		global $wp_version;
		$params = array(
			'api_key' => $this->api_key,
			'expires' => time() + 900
		);
		$path = '/v2/assets/' . $path;
		$params['signature'] = $this->sign_request( array( 'path' => $path, 'method' => 'PATCH', 'body' => $body ), $params );
		foreach ( $params as &$param )
			$param = rawurlencode( $param );

		$url = add_query_arg( $params, 'https://api.ooyala.com' . $path );

		if ( $wp_version >= 3.4 )
			return wp_remote_request( $url, array( 'headers' => array( 'Content-Type' => 'application/json' ), 'method' => 'PATCH', 'body' => $body, 'timeout' => apply_filters( 'ooyala_http_request_timeout', 10 ) ) );

		// Workaround for core bug - http://core.trac.wordpress.org/ticket/18589
		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "PATCH" );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $body );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );

		return array( 'body' => $response, 'response' => array( 'code' => $status ) );
	}

	/**
	 * add Youtube video to ooyala backlot account
	 * [POST] /v2/assets
	 * {
	 *  "name": "My asset on YouTube",
	 *	"asset_type": "youtube",
	 *	"youtube_id": "oHg5SJYRHA0"
	 * }
	 * @return Type    Description
	 */
	public function add_youtube( $youtube_id, $youtube_name, $return = false ){

		$body = array(
			'name' => $youtube_name,
			'asset_type' => 'youtube',
			'youtube_id' => $youtube_id
		);
		$params = array(
			'api_key' => $this->api_key,
			'expires' => time() + 900
		);
		$path = '/v2/assets';
		$params['signature'] = $this->sign_request( array( 'path' => $path, 'method' => 'POST', 'body' => json_encode($body) ), $params );
		foreach ( $params as &$param )
			$param = rawurlencode( $param );

		$url = add_query_arg( $params, 'http://api.ooyala.com' . $path );

		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'timeout' => apply_filters( 'ooyala_http_request_timeout', 60 ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body' => json_encode($body)
			)
		);

		if ( $return )
			return $response;

		if( is_wp_error( $response ) ) {
		   echo 'Something went wrong!';
		} else if ( 200 == wp_remote_retrieve_response_code( $response ) ){
			$ooyala_asset = json_decode( wp_remote_retrieve_body( $response ) );
			$output = '<h3 class="hndle"><span>Youtube video added to Ooyala</span></h3>';
			$output .= '<div class="inside">
				<table width="100%" border="0" class="selected-vid-area">
					<tr>
					  <td width="320" id ="youOoyalaPreviewPlayerContainer" ></td>
					  <td style="padding: 10px;">
						<div class="wrap"><a class="button-primary set-feature-video" data-embedcode="' . esc_attr( $ooyala_asset->embed_code ) . '" data-thumb="' . esc_attr( $ooyala_asset->preview_image_url ) . '" data-description="' . esc_attr( $ooyala_asset->description ) . '" title="' . esc_attr( $ooyala_asset->name ) . '" >Set as Featured Video</a></div>
						<div class="wrap"><a class="button insert-short-code" data-embedcode="' . esc_attr( $ooyala_asset->embed_code ) . '" data-thumb="' . esc_attr( $ooyala_asset->preview_image_url ) . '" title="' . esc_attr( $ooyala_asset->name ) . '" >Insert short code in post</a></div>
					  </td>
					  <td><div class="ooyala-item">
						  <div class="photo">
							<img class="preview-image" src="' . esc_url( $ooyala_asset->preview_image_url ) . '" width="128" height="72">embed code:<input name="selectedEmbedCode" class="preview-embedcode" type="text" value="' . esc_attr( $ooyala_asset->embed_code ) . '" readonly="readonly" style="width: 126px;"/>
						  </div>
						  <div class="preview-title" >' . esc_attr( $ooyala_asset->name ) . '</div>
						</div></td>
					</tr>
				</table>
			</div>';
			echo $output ;
		}else {
			$output = '<h3 class="hndle"><span><b> Youtube video was NOT added </b></span></h3><div class="inside"><p> Message: <pre> <b>';
			$output .= wp_remote_retrieve_body( $response );
			$output .= '</b></pre></p></div>';
			echo $output;
		}

	}

	/**
	 * Query Oooyala Assets with a get method.
	 *
	 * @param Array $params  Array of the different query string parameters to be added to the request
	 * @param Array $request Request Array.
	 * @param Boolean $return  If true the response would be returned else it will print the html response. Default is False
	 *
	 * @return String    Response from the api
	 */
	public function query( $params, $request = array(), $return = false ) {
		$default_request = array(
			'method' => 'GET',
			'path'   => '/v2/assets'
		);
		$default_params = array(
			'api_key' => $this->api_key,
			'expires' => time() + 900,
			'where'   => "status='live'",
			'limit'   => 8,
			'orderby' => 'created_at descending'
		);
		$params = wp_parse_args( $params, $default_params );
		$request = wp_parse_args( $request, $default_request );

		$params['signature'] = $this->sign_request( $request, $params );
		foreach ( $params as &$param )
			$param = rawurlencode( $param );

		$url = add_query_arg( $params, 'http://api.ooyala.com' . $request['path'] );

		$response = wp_remote_get( $url, array( 'timeout' => apply_filters( 'ooyala_http_request_timeout', 60 ) ) );


		if ( $return )
			return $response;
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			$this->render_popup( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Formats and outputs Ooyala query results into html
	 *
	 * @param String $response JSON encoded response code
	 *
	 */
	private function render_popup( $response ) {
		$videos = json_decode( $response );

		if ( empty( $videos->items ) ) {
			_e( 'No videos found.', 'ooyalaplusvideo' );
			return;
		}

		$output = $page_token = $next = '';
		if ( !empty( $videos->next_page ) ) {
			parse_str( urldecode( $videos->next_page ) );
			$next = '<a href="#' . $page_token . '" class="next page-numbers ooyala-paging">Next &raquo;</a>';
		}

		$ids = isset( $_REQUEST['ooyala_ids'] ) ? $_REQUEST['ooyala_ids'] : '';
		$ids = explode( ',', $ids );

		if ( $page_token ) {
			if ( in_array( $page_token, $ids ) ) {
				$key = array_keys( $ids, $page_token );
				$key = $key[0];
				$prev_token = $key > 1 ? $ids[ $key - 2 ] : '-1';
			} else {
				$c = count( $ids );
				$prev_token = $c > 1 ? $ids[ count( $ids ) - 2 ] : -1;
				$ids[] = $page_token;
			}
		} else {
			$prev_token = $ids[ count( $ids ) - 2 ];
		}

		if ( $next || $prev_token != -1 ) {
			$output .= '<div class="tablenav"><div class="tablenav-pages">';
			if ( $prev_token != -1 )
				$output .= '<a href="#' . $prev_token . '" class="prev page-numbers ooyala-paging">&laquo; Prev</a>';

			if ( $next )
				$output .= $next;

			$output .= '</div></div>';
		}

		$ids = implode( ',', $ids );
		$output .= '<input type="hidden" id="ooyala-ids" value="' . esc_attr( $ids ) . '" />';


		$output .= '<div id="ooyala-items">';
		foreach ( $videos->items as $video ) {
			$output .= '
			<div id="ooyala-item-' . esc_attr( $video->embed_code ) . '" title="' . esc_attr( $video->name ) .'" data-thumb="' . esc_url( $video->preview_image_url ) . '" data-embedcode="' . esc_attr( $video->embed_code ) . '" data-description="' . esc_attr( $video->description ) . '" class="ooyala-item">
				<div class="photo">
					<img src="' . esc_url( $video->preview_image_url ) . '">
				</div>
				<div class="item-title"><a href="#" title="' . esc_attr( $video->name ) .'" data-thumb="' . esc_url( $video->preview_image_url ) . '" data-embedcode="' . esc_attr( $video->embed_code ) . '" class="disable">' . esc_attr( $video->name ) .'</a></div>
			</div>';
		}
		$output.='</div><div style="clear:both;"></div>';
		echo $output;
	}

}