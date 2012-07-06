if (!window.OV) {
	window.OV = {};
}

OV.Popup = function($)  {

	function switchTabs(whichTab) {
			$('.ov-tabs li a').removeClass('current');
			$('#ov-tab-'+ whichTab +' a').addClass('current');
			$('.ov-content').hide();
			$('#ov-content-' + whichTab).show();
	}

	function setThumbnail( image, link ) {

		var data = {
				action: 'ooyala_set',
				ooyala: 'thumbnail',
				img: image,
				postid: postId,
				_wpnonce: ajax_nonce_ooyala
		};

		var win = window.dialogArguments || opener || parent || top;

		$.post( ajaxurl, data, function(response) {
			link.text( setPostThumbnailL10n.setThumbnail );
			if ( response == '0' ) {
				alert( setPostThumbnailL10n.error );
			} else {
				link.text( setPostThumbnailL10n.done );
				link.fadeOut( 2000 );
				win.jQuery('#postimagediv .inside').html(response);			}
		});
	}


	function insertShortcode( vid , title, img ) {

		var shortcode = '[ooyala code="' + vid + '" image="' + img + '" title="' + title + '" ]';

		var win = window.dialogArguments || opener || parent || top;
		var isVisual = (typeof win.tinyMCE != "undefined") && win.tinyMCE.activeEditor && !win.tinyMCE.activeEditor.isHidden();
		if (isVisual) {
			win.tinyMCE.activeEditor.execCommand('mceInsertContent', false, shortcode);
		} else {
			var currentContent = $('#content', window.parent.document).val();
			if ( typeof currentContent == 'undefined' )
			 	currentContent = '';
			$( '#content', window.parent.document ).val( currentContent + shortcode );
		}
		self.parent.tb_remove();
	}
	/**
	 * @type object object that holds youtube tab variables.
	 */
	var youtube ={

		searchTerm : "",

	}
	/**
	 * Search youtube videos
	 *
	 * @param   {String} function search youtube api for videos example feed: https://gdata.youtube.com/feeds/api/videos?q=football+-soccer&orderby=published&start-index=11&max-results=10&v=2&alt=jsonc
	 *
	 */
	function searchYoutube( term, startIndex ) {
		startIndex = ( startIndex ) ? startIndex : 0;
		if( term !== null ){
			youtube.searchTerm = term.replace(/ /g,"+");
		}
		var url = "https://gdata.youtube.com/feeds/api/videos?q=" + youtube.searchTerm + "&orderby=published&start-index=" + startIndex + "&max-results=8&v=2&alt=jsonc&callback=OV.Popup.youtubeResult";
		$.getScript(url);
	}
	/**
	 * Youtube search results Print function delegate
	 *
	 * @param   {object} r Response object from youtube
	 *
	 */
	function youtubeSearchResults( r ){

		var preButton = ( r.data.startIndex > r.data.itemsPerPage ) ? '<a href="#' + ( r.data.startIndex - r.data.itemsPerPage ) + '" class="youtube-paging" >« Prev</a>' : '';
		var html = '<h3 class="media-title">Youtube Search for :' + youtube.searchTerm + '</h3>';
			html += '<div class="tablenav"><div class="tablenav-pages">' + preButton + '<a href="#' + ( r.data.startIndex + r.data.itemsPerPage ) + '" class="youtube-paging" >Next »</a></div></div>';
			html += '<div id="youtube-items">';
			$.each(r.data.items, function(index, item) {
				html += '<div id="youtube-item-' + item.id + '" class="ooyala-item youtube-item add-to-ooyala" data-thumb="' + item.thumbnail.hqDefault + '" title="' + item.title + '" data-id="' + item.id + '" >';
					html += '<div class="item-title"><a href="#" title="' + item.title + '" data-id="' + item.id + '" class="add-ooyala">' + item.title + '</a></div>';
					html += '<div class="photo">';
						html += '<a href="#"><img src="' + item.thumbnail.hqDefault + '"></a>';
					html += '</div>';
				html += '</div>';
			});
			html += '</div><!-- eof youtube-items -->';
		$('#youtube-response-div').html(html);
	}
	/**
	 * Select a youtube item to add to ooyala
	 *
	 * @param   {object} obj youtube video item from search results
	 *
	 */
	function selectYoutubeVideo( obj ) {
		var id = $(obj).attr('data-id');
		var title = $(obj).attr('title');
		var thumb = $(obj).attr('data-thumb');
		var player = '<iframe width="320" height="180" src="http://www.youtube.com/embed/' + id + '" frameborder="0" allowfullscreen></iframe>';
		$('#youtube-items .youtube-item').css({ opacity: 1 });
		$('#youtube-item-'+id).css({ opacity: 0.5 });
		$('#youtubePlayerContainer').html( player );
		$('#youtube-selected-box .youtube-item img.preview-image').attr('src', thumb );
		$('#youtube-selected-box .youtube-item .preview-title').text( title );
		$('#youtube-insert').attr('data-thumb', thumb ).attr('data-id', id ).attr('title', title );
		$('#youtube-selected-box').show();
	}
	/**
	 * Callback function after youtube video is added to ooyala
	 *
	 * @param   {object} function Write response from ooyala to youtube browser.
	 *
	 */
	function addYoutubeCallback(response){

		$( "#youtube-selected-box" ).html( response );
		container = "youOoyalaPreviewPlayerContainer";
		var embedcode = $("#youtube-selected-box .set-feature-video").attr( "data-id" );
		if(embedcode){
			var js,
				src = 'http://player.ooyala.com/player.js?callback=receivePreviewPlayerEvent&embedCode=' + embedcode + '&playerContainerId=youOoyalaPreviewPlayerContainer&playerId=youOoyalaPreviewPlayer&autoplay=0&width=320&height=180&wmode=transparent&version=2';
			js = document.createElement('script');
			js.async = true;
			js.src = src;
			document.getElementsByTagName('body')[0].appendChild(js);
		}
	}
	/**
	 * Ajax call to add youtube video to ooyala.
	 *
	 * @uses	addYoutubeCallback
	 * @uses	ooyala_add_youtube action
	 * @param   {string} youtubeId    ID of youtube video
	 * @param   {Type} youtubeName Title of youtube video, this will sync with youtube later on too
	 *
	 */
	function addYoutubeToOoyala( youtubeId, youtubeName ) {

		var data = {
				action: 'ooyala_add_youtube',
				ooyala: 'youtube',
				id: youtubeId,
				name: youtubeName,
				postid: postId,
				_wpnonce: ajax_nonce_ooyala
		};

		$.post( ajaxurl, data, function(response) {
			if ( response == '0' ) {
				alert( "something went wrong" );
			} else {

				addYoutubeCallback(response);

			}
		});
	}
	/**
	 * Ajax Call to set feature video custom feild
	 *
	 * @param   {string} embedCode ooyala embedcode
	 *
	 */
	function setFeatureVideo( embedCode, link ){
		var data = {
				action: 'ooyala_set_featured_video',
				embedcode: embedCode,
				postid: postId,
				_wpnonce: ajax_nonce_ooyala
		};

		if($(link).attr("title")){
			data.title = $(link).attr("title");
		}

		if($(link).attr("data-thumb"))
			data.image = $(link).attr("data-thumb");

		if($(link).attr("data-description"))
			data.description = $(link).attr("data-description");

		var win = window.dialogArguments || opener || parent || top;

		$.post( ajaxurl, data, function(response) {
			if ( response == '0' ) {
				alert( "something went wrong" );
			} else {

				link.text( "Added as featured video" );
				link.fadeOut( 2000 );
				win.jQuery('#cdc_featured_video_id .inside input.cdc_featured_video_id_input_class').val( data.embedcode );
				win.jQuery('#cdc_featured_video_id .inside input.cdc_featured_video_title_input_class').val( data.title );
				win.jQuery('#cdc_featured_video_id .inside input.cdc_featured_video_image_input_class').val( data.image );
				win.jQuery('#cdc_featured_video_id .inside input.cdc_featured_video_description_input_class').val( data.description );
			}
		});
	}

	/**
	 * load and set the ooyala preview player
	 *
	 * @param   {string} code embedCode of ooyala video
	 *
	 */
	function setPreviewEmbedCode( code , player){
		var playerId = (player) ? player : "previewPlayer";
		var div = (player) ? player + "Container" : "previewPlayerContainer";

		$('#ooyala-selected-box').show();
		if( document.getElementById( 'previewPlayer' ) ){
			document.getElementById( 'previewPlayer' ).setQueryStringParameters( {embedCode:code} );
		}else{
			var js,
				id = 'previewPlayerSrpt',
				src = 'http://player.ooyala.com/player.js?callback=receivePreviewPlayerEvent&embedCode=' + code + '&playerContainerId=' + div + '&playerId=' + playerId + '&autoplay=0&width=320&height=180&wmode=transparent&version=2';
			js = document.createElement('script');
			js.id = id;
			js.async = true;
			js.src = src;
			document.getElementsByTagName('body')[0].appendChild(js);
		}
	}

	return {
		youtubeResult: function( r ){
			youtubeSearchResults(r);
		},
		ooyalaRequest: function( what, searchTerm, searchField, pageId ) {

			if ( 'paging' == what ) {
				previousRequest = $('#response-div').data('previousRequest');
				searchTerm = previousRequest.searchTerm;
				searchField = previousRequest.searchField;
				what = previousRequest.what;
			}

			searchTerm = ( searchTerm == '' ) ? '' : searchTerm;
			searchField = ( searchField == '' ) ? '' : searchField;
			pageId =  ( pageId == '' ) ? '0' : pageId;
			postId = ( postId == '' ) ? '0' : postId;

			//Let's store this search in case we get a subsequent paging request
			$('#response-div').data( 'previousRequest', {searchTerm: searchTerm, what: what, searchField: searchField});

			var data = {
					action: 'ooyala_request',
					ooyala_ids: $('#ooyala-ids').val(),
					ooyala: what,
					key_word: searchTerm,
					search_field: searchField,
					pageid: pageId,
			};

			$.get( ajaxurl, data, function(response) {

					var latestLink = '<span id="latest-link">(<a href="#" id="ov-last-few">'+ooyalaL10n.latest_videos+'</a>)</span>';
					var title = (data.ooyala == 'search') ?  ooyalaL10n.search_results + latestLink : ooyalaL10n.latest_videos;
					var htmlTitle = '<h3 class="media-title">'+title+'</h3>';
					$('#response-div').html(htmlTitle + response);
			});
		},
		resizePop: function () {
			try {
				//Thickbox won't resize for some reason, we are manually doing it here
				var totalWidth = $('body', window.parent.document).width();
				var totalHeight = $('body', window.parent.document).height();
				var isIE6 = typeof document.body.style.maxHeight === "undefined";

				$('#TB_window, #TB_iframeContent', window.parent.document).css('width', '768px');
				$('#TB_window', window.parent.document).css({ left: (totalWidth-768)/2 + 'px', top: '23px', position: 'absolute', marginLeft: '0' });
				if ( ! isIE6 ) { // take away IE6
					$('#TB_window, #TB_iframeContent', window.parent.document).css('height', (totalHeight-73) + 'px');
				}
			} catch(e) {
				if (debug) {
					console.log("resizePop(): " + e);
				}
			}
		},
		init: function () {
			// Scroll to top of page
			window.parent.scroll(0,0);

			/**
			 * Tabs
			 */
			$('#ov-tab-ooyala a').click(function () {
				switchTabs('ooyala');
				return false;
			});
			$('#ov-tab-upload a').click(function () {
				switchTabs('upload');
				return false;
			});
			$('#ov-tab-youtube a').click(function () {
				switchTabs('youtube');
				return false;
			});

			/**
			 * Ooyala search screen
			 */
			// Bind ooyala search button
			$('#ov-search-button').click(function () {
				OV.Popup.ooyalaRequest('search', $('#ov-search-term').val(), $('#ov-search-field').val() );
				return false;
			});
			// Bind most recent link on ooyala search screen
			$('#ov-last-few').live('click', function () {
				OV.Popup.ooyalaRequest('last_few');
				$('#ov-content-upload h3').text('Lastest videos');
				return false;
			});
			// Bind ooyala search screen pagination
			$('.ooyala-paging').live( 'click', function(e) {
				e.preventDefault();
				pageId = $(this).attr('href').substring(1);
				OV.Popup.ooyalaRequest( 'paging','', '', pageId );
				return false;
			});
			// Bind ooyala video items click
			$('#ooyala-items .ooyala-item').live( 'click', function(e) {
				var ecode = $(this).attr('data-embedcode');
				var thumb = $(this).attr('data-thumb');
				var title = $(this).attr('title');
				var description = $(this).attr('data-description');
				setPreviewEmbedCode(ecode);
				$('#ooyala-selected-box .set-feature-video, #ooyala-selected-box .insert-short-code').attr( "data-thumb", thumb ).attr( "data-embedcode", ecode ).attr( "title", title ).attr( 'data-description', description );
				$('#ooyala-selected-box .set-feature-image').attr( 'data-thumb', thumb );
				//set preview
				$('#ooyala-selected-box .preview-image').attr( 'src', thumb );
				$('#ooyala-selected-box .preview-embedcode').val( ecode );
				$('#ooyala-selected-box .preview-title').text( title );

			});

			/**
			 * Youtube screen
			 */
			// bind youtube searchYoutube button and reset startIndex to 1
			$('#youtube-search-button').click(function () {
				searchYoutube( $('#youtube-search-term').val(), 1 );
				return false;
			});
			// bind paginating for youtube
			$('.youtube-paging').live( 'click', function(e) {
				e.preventDefault();
				var startIndex = $(this).attr('href').substring(1);
				searchYoutube( null, startIndex );
				return false;
			});
			// bind youtube video item
			$('.youtube-item.add-to-ooyala').live( 'click', function(e) {
				e.preventDefault();
				selectYoutubeVideo($(this));

			});
			// bind youtube insert in ooyala button
			$('#youtube-insert').click( function() {
				var id = $(this).attr( 'data-id' );
				var title = $(this).attr( 'title' );
				if ( id != '' && title != '' ){
					addYoutubeToOoyala( id, title );
				}
				return false;
			});

			/**
			 * buttons
			 */
			// bind set as feature video link
			$('.set-feature-video').live( 'click', function(e){
				e.preventDefault();
				var embedCode = $(this).attr('data-embedcode');
				setFeatureVideo( embedCode, $(this));
			});
			// bind insert shortcode link
			$('.insert-short-code').live( 'click', function() {
				var embedCode = $(this).attr('data-embedcode');
				var title = $(this).attr('title');
				var thumb = $(this).attr('data-thumb');
				if ( embedCode != '')
					insertShortcode( embedCode, title, thumb );
				return false;
			});
			// set as feature image
			$('.set-feature-image').live( 'click', function(e) {
				var link = $(this);
				e.preventDefault();

				var image = $(this).attr('data-thumb');

				link.text(setPostThumbnailL10n.saving);
				setThumbnail( image, link );
			});
			// Bind close button click
			$('#ooyala-close').click( function(e) {
				e.preventDefault();
				self.parent.tb_remove();
				return false;
			});

			// <a> prevent default class
			$('a.disable').live( 'click', function(e) {
				e.preventDefault();
				return false;
			});

		}
	};

}(jQuery);

/* Uploader Functions */

function ooyalaOnFileSelected(file) {
	jQuery('#ooyala_file_name').val( file.name );
}
function ooyalaOnProgress(event) {
	jQuery('#ooyala-status').html( (parseInt(event.ratio * 10000) / 100) + '%' );
}
function ooyalaOnUploadComplete() {
	jQuery('#ooyala-status').html( ooyalaL10n.done );
	jQuery('#uploadButton').attr('disabled', false);
	jQuery('#ooyala-embedcode-ready').show();
}
function ooyalaOnUploadError(text) {
	jQuery('#ooyala-status').html( ooyalaL10n.upload_error +': ' + text );
}

function ooyalaOnEmbedCodeReady( embedCode ){
	if(allowFeaturedUpload){

		var message = '<br/><p>Your video has been uploaded to your Ooyala Backlot account with the following embedcode: <br /><br /><b>' + embedCode + '</b></p>';
		message += '<p>You can add this video as a feature video but it will not be viewable until it finishes processing.<br /><br />IT IS HIGHLY RECOMMENDED THAT YOU <b>DO NOT</b> SET THIS VIDEO AS FEATURED. INSTEAD, LET IT PROCESS AND COME BACK TO SET IT AS A FEATURED VIDEO IN 1 HOUR.</p>';
		message += ('<a class="button set-feature-video" data-embedcode="' + embedCode + '" >Set as Featured Video</a>');
		jQuery('#ooyala-embedcode-ready').html( message ).hide();

	}
}

function ooyalaStartUpload() {
	try {
  		ooyalaUploader.setTitle( jQuery('#ooyala_file_name').val() );
		ooyalaUploader.setDescription( jQuery('#ooyala_description').val() );
		// add dynamic lables if selected
		if( ooyalaSelectedLables.length > 0 ){
			jQuery(ooyalaSelectedLables).each(function(index, value) {
				if ( typeof ooyalaLabelPrefix != 'undefined' ){
					ooyalaUploader.addDynamicLabel( ooyalaLabelPrefix + value );
				} else {
					ooyalaUploader.addDynamicLabel( value );
				}
			});
		}
		var errorText = ooyalaUploader.validate();
  		if (errorText) {
    		alert(errorText);
    		return false;
  		}
		jQuery('#uploadButton').attr('disabled', true);
  		ooyalaUploader.upload();
	} catch(e) {
  		alert(e);
	}
	return false;
}