<?php
/**
 * WordPress Class for interfacing with the Ooyola Backlot API v1
 *
 * @since 1.0
 *
 * @package Ooyala Plus
 * @subpackage API
 *
 * @see http://www.ooyala.com/support/docs/backlot_api
 *
 * @todo This Class is to communicate with backlot apis v1, currently this is only used for the upload widget available on ooyala-popup.php
 * and we should be migrating this to utilize V2 APIs once ooyala releases a new upload widget.
 *
 * Note: This Class is based on OoyalaBacklotAPI Class in Ooyala Video plugin http://www.ooyala.com/wordpressplugin/
 */
class OoyalaPlusBacklotAPI  {

	/**
	 * Sign Oooyala API Parameters
	 *
	 * @param Array $params Array of request parameters to be signed
	 *
	 * @return String    Parameters and signature
	 */
	static function signed_params( $params ) {
		$ooyala_video = Ooyala_Plus_Video::init();


    	if ( !array_key_exists( 'expires', $params ) ) {
      		$params['expires'] = time() + 900;  // 15 minutes
    	}

	    $string_to_sign = $ooyala_video->secret_code;
	    $param_string = 'pcode=' . $ooyala_video->partner_code;
	    $keys = array_keys( $params );
	    sort( $keys );

	    foreach ($keys as $key) {
	      $string_to_sign .= $key.'='.$params[$key];
	      $param_string .= '&'.rawurlencode( $key ) . '='. rawurlencode( $params[$key] );
	    }

	    $digest = hash('sha256', $string_to_sign, true);
	    $signature = preg_replace( '#=+$#', '', trim( base64_encode( $digest ) ) );
	    return $param_string.'&signature='.rawurlencode( $signature );
  	}

	/**
	 * Query Ooyala
	 *
	 * @param Array $params       Array of request parameters to be signed
	 * @param String $request_type String identifying the ooyala api request type.
	 *
	 * @return String    Xml with the ooyala api response
	 */
	static function query( $params, $request_type = 'query' ) {

		$ooyala_video = Ooyala_Plus_Video::init();

		if ( empty( $ooyala_video->partner_code ) || empty( $ooyala_video->partner_code ) )
			return new WP_Error( 'no_api_codes', sprintf( __('Please set your API codes in the <a href="%s" target="_blank">Ooyala Plus Video</a> Settings page'), menu_page_url( 'ooyalaplusvideo_options', false ) ) );
		else
			return OoyalaPlusBacklotAPI::send_request( $request_type, $params );
	}

	/**
	 * Sends Ooyala Api request
	 *
	 * @param String $request_type Ooyala api request type
	 * @param Array $params       Array of Ooyala api parameters
	 *
	 * @return String    Xml with the ooyala api response
	 */
	private static function send_request( $request_type, $params ) {

		$ooyala_video = Ooyala_Plus_Video::init();

		// Add an expire time of 1 day unless otherwise specified.
		// Floor the time to 15 second increments so we get better
		// cache efficiency and reduce our request time.
		if (!array_key_exists('expires', $params)) {
		  $params['expires'] = (floor(time() / 15) * 15) + 900;
		}

		$string_to_sign = $ooyala_video->secret_code;
		$url = 'http://api.ooyala.com/partner/' . $request_type . '?pcode='.$ooyala_video->partner_code;

		$keys = array_keys($params);
		sort($keys);

		foreach ($keys as $key) {
		  $string_to_sign .= $key . '=' . $params[$key];
		  $url .= '&'.rawurlencode($key) . '=' . rawurlencode( $params[$key] );
		}

		$digest = hash( 'sha256', $string_to_sign, true );
		$signature = preg_replace( '#=+$#', '', trim( base64_encode( $digest ) ) );
		$url .= '&signature='.rawurlencode( $signature );
		$timeout = apply_filters( 'ooyala_http_request_timeout', 10 );

		$response = wp_remote_get( $url, array( 'timeout' => $timeout ) );

		if ( is_wp_error( $response ) )
			return $response;
		else
			return wp_remote_retrieve_body( $response ) ;
	}

	/**
	 * get promo thumbnail from response
	 *
	 * @param String $xml Response XML
	 *
	 * @return String    URL to thumbnail
	 */
	static function get_promo_thumbnail( $xml ) {

		$results = simplexml_load_string( $xml );
		if ( !$results )
			return new WP_Error( 'noresults', __( 'Malformed XML' , 'ooyalaplusvideo'));

		return $results->promoThumbnail;
	}

	/**
	 * Formats and outputs Ooyala query results into html
	 *
	 * @param String $xml Response xml string
	 *
	 */
	static function print_results( $xml, $do = NULL ) {
		$results = simplexml_load_string( $xml );
		if ( !$results )
			return new WP_Error( 'noresults', __( 'Malformed XML' , 'ooyalaplusvideo'));

		$items = $results->item;
		if (!$items)
			return new WP_Error( 'emptyresults', __( 'No videos found' , 'ooyalaplusvideo'));

		$page_id = (int) $results->attributes()->pageID;
		$next_page_id = (int) $results->attributes()->nextPageID;
		$limit = (int) $results->attributes()->limit;
		$total = (int) $results->attributes()->totalResults;
		$previous_page_id = max( 0, (int) $page_id - (int) $limit);

		$prev_link =  $previous_page_id != $page_id ? '<a href="#' . $previous_page_id . '" class="prev page-numbers ooyala-paging">&laquo; Previous</a>' : '';
		$next_link = $next_page_id > 0 ? '<a href="#' . $next_page_id . '" class="next page-numbers ooyala-paging">Next &raquo;</a>' : '';

		//prev-next block
		if ( !empty( $prev_link) || !empty( $next_link ) )
			echo '<div class="tablenav"><div class="tablenav-pages">' . $prev_link.$next_link . '</div></div>';

		//Items
		$output = '<div id="ooyala-items">';
		foreach ( $items as $item ) {
		$output.='
		<div id="ooyala-item-' . esc_attr( $item->embedCode ). '" class="ooyala-item">
			<div class="item-title"><a href="#" title="' . esc_attr( $item->embedCode ) .'" class="use-shortcode">' . esc_attr( $item->title ) .'</a></div>
			<div class="photo">
				<a href="#" title="' . esc_attr( $item->embedCode ) .'" class="use-shortcode"><img src="' . esc_url( $item->thumbnail ) . '"></a>';

		if ( current_theme_supports('post-thumbnails') )
			$output.='	<p><a href="#" class="use-featured">Use as featured image</a></p>';
		$output.='
			</div>
		</div>';
		}

		$output.='</div><div style="clear:both;"></div>';
		echo $output;
	}
}
