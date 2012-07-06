<?php
if ( ! class_exists( 'OoyalaPlusBacklotAPI' ) )
	require_once( dirname(__FILE__) . '/class-ooyala-backlot-api.php' );
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<title>Ooyala Video Plus</title>
<?php wp_print_scripts( array( 'jquery', 'ooyala-plus', 'ooyala-chosen', 'set-post-thumbnail' ) ); ?>
<?php wp_print_styles( array( 'global', 'media', 'wp-admin', 'colors', 'ooyala-chosen' ) ); ?>
<script type="text/javascript">
	var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	var postId = <?php echo absint( $_GET['post_id'] ); ?>;
	var ajax_nonce_ooyala = '<?php echo wp_create_nonce( 'ooyala-plus' ); ?>';
</script>

<script>
	jQuery(document).ready(function () {
		OV.Popup.init();
		OV.Popup.resizePop();

		//Make initial reqest to load last few videos
		OV.Popup.ooyalaRequest( 'last_few' );
	});
	jQuery(window).resize(function() {
	  	setTimeout(function ()	{ OV.Popup.resizePop(); }, 50);
	});
</script>
<style>
	body { min-width:300px !important; }
	.tablenav-pages a { font-weight: normal;}
	.ooyala-item {float:left; width:146px; padding:4px; border:1px solid #DFDFDF; margin:4px 10px; box-shadow: 2px 2px 2px #DFDFDF; cursor:pointer;}
	.ooyala-item .item-title {height: 60px;}
	.ooyala-item .photo { margin:4px; }
	.ooyala-item .photo img { width: 128px; height:72px}
	.ooyala-item .item-title {text-align:center;}
	#latest-link {font-size: 0.6em; padding-left:10px;}
	#ov-content-upload label {display:block}
	.selected-vid-area .wrap {margin-top: 20px; margin-bottom: 20px;}
	.hndle{font-family: Georgia,"Times New Roman",Times,serif;font-weight: normal;}
	#youtube-selected-box,.postbox{margin: 20px;}
	#youtube-selected-box .hndle span {margin: 10px; display: block;}
	.postbox .hndle span{margin: 10px; display: block;}
</style>
</head>
<body id="media-upload">
	<div id="media-upload-header">
		<ul id="sidemenu" class="ov-tabs">
			<li id="ov-tab-ooyala"><a class="current" href=""><?php _e('Ooyala video','ooyalaplusvideo'); ?></a></li>
			<li id="ov-tab-upload"><a href=""><?php _e('Upload to Ooyala','ooyalaplusvideo'); ?></a></li>
			<li id="ov-tab-youtube"><a href=""><?php _e('Add Youtube Video','ooyalaplusvideo'); ?></a></li>
		</ul>
	</div>
	<div class="ov-contents">
		<div id="ov-content-ooyala" class="ov-content">
		 	<form name="ooyala-requests-form" action="#">
				<p id="media-search" class="search-box">
					<img src="<?php echo $this->plugin_url; ?>img/ooyala100.png" style="vertical-align: middle; margin-right: 10px;"/>
					<select name="ooyalasearchfield" id="ov-search-field">
						<option value="embed_code" selected="selected">EmbedCode</option>
						<option value="description">Description</option>
						<option value="name">Name</option>
						<option value="labels">Label</option>
					</select>
					<label class="screen-reader-text" for="media-search-input"><?php _e('Search Keyword', 'ooyala_video');?></label>
					<input type="text" id="ov-search-term" name="ooyalasearch" value="">
					<input type="submit" name=""  id="ov-search-button" class="button" value="Search">
				</p>
				<div id="response-div">
					<h3 class="media-title"><?php _e('Loading...', 'ooyala_video');?></h3>
		      	</div>
			</form>
			<div class="postbox" id="ooyala-selected-box" style="display:none;">
				<h3 class="hndle"><span><?php _e('Selected video', 'ooyala_video');?></span></h3>
				<div class="inside">
					<table width="100%" border="0" class="selected-vid-area">
						<tr>
						  <td id="previewPlayerContainer" width="320"></td>
						  <td style="padding: 10px;">
							<div class="wrap"><a class="button-primary set-feature-video"><?php _e('Set as Featured Video', 'ooyala_video');?></a></div>
							<div class="wrap"><a class="button insert-short-code"><?php _e('Insert short code in post', 'ooyala_video');?></a></div>
							<?php if ( current_theme_supports( 'post-thumbnails' ) )
									echo '<div class="wrap"><a class="button set-feature-image">' . __('Use as featured image', 'ooyala_video') . '</a></div>'; ?>
						  </td>
						  <td><div class="ooyala-item">
							  <div class="photo"> <img class="preview-image"  width="128" height="72">
								<?php _e('embed code:', 'ooyala_video');?>
								<input name="selectedEmbedCode" class="preview-embedcode" type="text" value="kybzh1NDoyJkEDSVO4OLG6fBfQkNvTOA" readonly="readonly" style="width: 126px;"/>
							  </div>
							  <div class="preview-title" ></div>
							</div></td>
						</tr>
					</table>
				</div>
			</div>

		</div>
		<div id="ov-content-upload" class="ov-content"  style="display:none;margin:1em">
			<h3 class="media-title"><?php _e('Upload to Ooyala', 'ooyalaplusvideo' ); ?></h3>
			<?php
			// Define any default labels to assign and the dynamic label prefix
			// for any user-selected dynamic labels
			$options = get_option( 'ooyala-plus' );
			$status = empty( $options['video_status'] ) ? 'pending' : $options['video_status'];
			$params = array( 'status' => $status );
			// add dynamic label prefix if defined in options
			if( !empty( $options['dynamic_label_prefix'] ) )
				$params['dynamic[0]'] = $options['dynamic_label_prefix'];
			$param_string = OoyalaPlusBacklotAPI::signed_params($params);
		?>
	 	<fieldset style="float:left; width: 50%">
			<script src="//www.ooyala.com/partner/uploadButton?width=100&amp;height=20&amp;label=<?php echo ( urlencode( esc_attr__('Select File', 'ooyalaplusvideo') ) );?>"></script>
			<script>
			<?php
				if( $options['allow_featured_upload'] )
					echo "var allowFeaturedUpload = true;";
			?>
			var ooyalaParams = '<?php echo $param_string ?>';

			 function onOoyalaUploaderReady( )  {
		        try
		        {
		          ooyalaUploader.setParameters(ooyalaParams);
		        }
		        catch(e)
		        {
		          alert(e);
		        }

		        ooyalaUploader.addEventListener('fileSelected', 'ooyalaOnFileSelected');
		        ooyalaUploader.addEventListener('progress', 'ooyalaOnProgress');
		        ooyalaUploader.addEventListener('complete', 'ooyalaOnUploadComplete');
		        ooyalaUploader.addEventListener('error', 'ooyalaOnUploadError');
				ooyalaUploader.addEventListener('embedCodeReady', 'ooyalaOnEmbedCodeReady');

		        document.getElementById('uploadButton').disabled = false;
		      }
			</script>
		 	<p>
				<label><?php _e('Filename', 'ooyala_video');?></label>
				<input id="ooyala_file_name" size="40" />
			</p>
			<p>
				<label for><?php _e('Description', 'ooyala_video');?></label>
	        	<textarea id="ooyala_description" rows="5" cols="40"></textarea>
			</p>
			<p>
				<button id="uploadButton" onClick="return ooyalaStartUpload();"><?php _e('Upload!', 'ooyala_video');?></button>  <a id="ooyala-status"></a>
			</p>
		</fieldset>
		<!-- add labels -->
		<div style="float:right; width:50%">
			<?php
			$label_query = array( 'mode' => 'listLabels' );

			// get all labels from account or sublabels of parent label
			if( !empty( $options['dynamic_label_prefix'] ) )
				$label_query['label'] = $options['dynamic_label_prefix'];

			$labels_request = OoyalaPlusBacklotAPI::query( $label_query, 'labels' );

			$labels_results = simplexml_load_string( $labels_request );
			if ( !$labels_results )
				return new WP_Error( 'noresults', __( 'Malformed XML' , 'ooyalaplusvideo' ));
			$labels = $labels_results->label;
			if (!$labels)
				return new WP_Error( 'emptyresults', __( 'No videos found' , 'ooyalaplusvideo' ));
			$labels_output = '<select data-placeholder="Choose a label..." class="chzn-select" multiple style="width:350px;" tabindex="4" id="user-selected-lables">';
			foreach ( $labels as $label ) {
				$labels_output .= '<option movieCount="' . esc_attr( $label->attributes()->movieCount ) . '" id="' . esc_attr( $label->attributes()->id ) . '">' . esc_attr( $label ) . '</option>';
			}
			$labels_output .= '</select>';

			echo $labels_output;
			?>
		</div>
		<script>
		<?php if( !empty( $options['dynamic_label_prefix'] ) )
					echo "var ooyalaLabelPrefix = '" . $options['dynamic_label_prefix'] . "';"; ?>
		(function($){
			if(typeof ooyalaLabelPrefix != 'undefined'){
				// remove lable prefix for better UX.
				$('#user-selected-lables option').each(function(index) {
					$(this).text( function(index, text){
						return text.replace( "" + ooyalaLabelPrefix + "", "" );
					});
				});
			}
			// enhance select box
			$('.chzn-select').chosen();
			// store slected lables in ooyalaSelectedLables Array
			window.ooyalaSelectedLables = $('#user-selected-lables').val() || [];
			$('#user-selected-lables').change(function(){
				ooyalaSelectedLables = $(this).val() || [];
			});
		})(jQuery);
		</script>
		<!-- eof add labels -->
		<div id="ooyala-embedcode-ready"></div>
		</div>
		<!-- youtube video tab -->
		<div id="ov-content-youtube" class="ov-content"  style="display:none;margin:1em">
			<!--youtube content goes here.-->
			<form name="youtube-search-form" action="#">
				<p  class="search-box">
					<img src="<?php echo $this->plugin_url; ?>img/youtube-small.png" style="vertical-align: middle; margin-right: 10px;"/>
					<label class="screen-reader-text" for="youtube-search-term"><?php _e('Search Keyword', 'ooyala_video');?></label>
					<input type="text" id="youtube-search-term" name="youtubesearch" value="">
					<input type="submit" name=""  id="youtube-search-button" class="button" value="Search">
				</p>

				<div id="youtube-response-div"></div>

			 </form>
			<!--youtube youtube-selected-box -->
			<div id="youtube-selected-box" style="display:none;">
				<h3 class="hndle"><span><b><?php _e('Selected youtube video', 'ooyala_video');?></b></span></h3>
				<div class="inside">
					<table width="100%" border="0" class="selected-vid-area">
						<tr>
						  <td id="youtubePlayerContainer" width="320"></td>
						  <td style="padding: 10px;">
							<div class="wrap"><a class="button-primary" id="youtube-insert" ><?php _e('Add to Ooyala','ooyalaplusvideo'); ?></a></div>
						  </td>
						  <td><div class="ooyala-item youtube-item">
							  <div class="photo"> <img class="preview-image"  width="128" height="72"></div>
							  <div class="preview-title" ></div>
							</div></td>
						</tr>
					</table>
				</div>
			</div>
			<!-- eof youtube-selected-box -->
		</div>
		<!-- eof youtube video tab -->
	</div>
</div>
</body>
</html>
