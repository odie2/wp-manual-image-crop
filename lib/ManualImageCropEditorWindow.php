<?php
/**
 * Class responsible for rendering the cropping Window
 * @author tomasz
 *
 */
class ManualImageCropEditorWindow {

	private static $instance; 

	/**
	 * Returns the instance of the class [Singleton]
	 * @return ManualImageCropEditorWindow
	 */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ManualImageCropEditorWindow();
        }
        return self::$instance;
    }

	private function __construct() {

	}

	public function renderWindow() {
		?>
		<div class="mic-editor-wrapper">
            <h4><?php _e('Pick the image size:','microp'); ?></h4>
			<h2 class="nav-tab-wrapper">
			<?php
			global $_wp_additional_image_sizes;

			$imageSizes = get_intermediate_image_sizes();

			$editedSize = isset( $_GET['size'] ) ? $_GET['size'] : $imageSizes[0];
			
            $sizeLabels = apply_filters( 'image_size_names_choose', array(
                'thumbnail' => __('Thumbnail'),
                'medium'    => __('Medium'),
                'large'     => __('Large'),
                'full'      => __('Full Size'),
            ) );
            $sizeLabels = apply_filters( 'image_size_names_choose', array() );

			foreach ($imageSizes as $s) {
				if (isset($_wp_additional_image_sizes[$s])) {
					$cropMethod = $_wp_additional_image_sizes[$s]['crop'];
				} else {
					$cropMethod = get_option($s.'_crop');
				}
				if ($cropMethod == 0) {
					continue;
				}

                // Get user defined label for the size or just cleanup a bit
                $label = isset($sizeLabels[$s]) ? $sizeLabels[$s] : ucfirst( str_replace( '-', ' ', $s ) );
				echo '<a href="' . admin_url( 'admin-ajax.php' ) . '?action=mic_editor_window&size=' . $s . '&postId=' . $_GET['postId'] . '&width=940" class="mic-icon-' . $s . ' rm-crop-size-tab nav-tab ' . ( ($s == $editedSize) ? 'nav-tab-active' : '' ) .  '">' . $label . '</a>';
			}
			?>
			</h2>
			<div class="mic-left-col">
				<?php
				//reads the specific registered image size dimension
				if (isset($_wp_additional_image_sizes[$editedSize])) {
					$width = intval($_wp_additional_image_sizes[$editedSize]['width']);
					$height = intval($_wp_additional_image_sizes[$editedSize]['height']);
					$cropMethod = $_wp_additional_image_sizes[$editedSize]['crop'];
				} else {
					$width = get_option($editedSize.'_size_w');
					$height = get_option($editedSize.'_size_h');
					$cropMethod = get_option($editedSize.'_crop');
				}

				$uploadsDir = wp_upload_dir();

				$metaData = wp_get_attachment_metadata($_GET['postId']);

				$src_file_url = wp_get_attachment_image_src($_GET['postId'], 'full');
				if (!$src_file_url) {
					echo json_encode (array('status' => 'error', 'message' => 'wrong attachement' ) );
					exit;
				}
				$src_file = str_replace($uploadsDir['baseurl'], $uploadsDir['basedir'], $src_file_url[0]);
				$sizes = getimagesize($src_file);

				$previewWidth = min($sizes[0], 500);
				$previewHeight = min($sizes[1], 350);
				$previewRatio = 1;
				
				if ($sizes[1]  / 350 < $sizes[0] / 500) {
					$previewHeight = $sizes[1] * $previewWidth / $sizes[0] ;
					$previewRatio = $sizes[1] / $previewHeight;
				}else {
					$previewWidth = $sizes[0] * $previewHeight / $sizes[1];
					$previewRatio = $sizes[0] / $previewWidth;
				}
				
				$minWidth = min($width / $previewRatio, $previewWidth);
				$minHeight = min($height / $previewRatio, $previewHeight);
	
				if ($cropMethod == 1) {
					$aspectRatio = ($width / $height);
					if ($aspectRatio * $minWidth > $sizes[0]) {
						$aspectRatio = ($previewWidth / $minHeight);
					}else if (1 / $aspectRatio * $minHeight > $sizes[1]) {
						$aspectRatio = ($minWidth / $previewHeight);
					}
					
					if ($minWidth / $aspectRatio > $previewHeight) {
						$aspectRatio = $minWidth / $previewHeight;
					}
				}else {
					$aspectRatio = $sizes[0] / $sizes[1];
				}	


				$smallPreviewWidth = min($width, 180);
				$smallPreviewHeight = min($height, 180);
	
				if ($width > $height) {
					$smallPreviewHeight = $smallPreviewWidth * 1/ $aspectRatio;
				}else {
					$smallPreviewWidth = $smallPreviewHeight * $aspectRatio;
				}
				?>
				<div style="margin: auto; width: <?php echo $previewWidth; ?>px;"><img style="width: <?php echo $previewWidth; ?>px; height: <?php echo $previewHeight; ?>px;" id="jcrop_target" src="<?php echo wp_get_attachment_url($_GET['postId']); ?>"></div>
			</div>
			<div class="mic-right-col">
				<div>
					<?php _e('Original picture dimensions:','microp') ?> <strong><?php echo $sizes[0]; ?> x <?php echo $sizes[1]; ?> px</strong><br />
                    <?php _e('Target picture dimensions:','microp') ?> <strong><?php echo $width; ?>px x <?php echo $height; ?>px</strong> (<?php if ($cropMethod == 0) { _e('Soft proportional crop mode','microp'); }else { _e('Hard crop mode','microp'); } ?>)<br/>
					<input type="checkbox" id="mic-make-2x" checked="checked" /> <?php _e('Generate Retina/HiDPI (@2x):','microp') ?> <span id="mic-2x-status">No</span>
				</div>
				
				<div class="mic-52-col">
                    <?php _e('New image:','microp') ?><br />
					<div style="width: <?php echo $smallPreviewWidth; ?>px; height: <?php echo $smallPreviewHeight; ?>px; overflow: hidden; margin-left: 5px; float: right;">
						<img id="preview" src="<?php echo wp_get_attachment_url($_GET['postId']); ?>">
					</div>
				</div>
				
				<div class="mic-48-col">
					<?php _e('Previous image:','microp');
					$editedImage =  wp_get_attachment_image_src($_GET['postId'], $editedSize);
					?>
					<div style="width: <?php echo $smallPreviewWidth; ?>px; height: <?php echo $smallPreviewHeight; ?>px; overflow: hidden; margin-left: 5px;">
						<img id="micPreviousImage" style="max-width: <?php echo $smallPreviewWidth; ?>px; max-height: <?php echo $smallPreviewHeight; ?>px;" src="<?php echo $editedImage[0] . '?' . time(); ?>">
					</div>
				</div>
				
				<input id="micCropImage" class="button-primary button-large" type="button" value="<?php _e('Crop it!','microp') ?>" />
				<img src="<?php echo includes_url(); ?>js/thickbox/loadingAnimation.gif" id="micLoading" />
				<div id="micSuccessMessage" class="updated below-h2"><?php _e('The image has been cropped successfully','microp') ?></div>
				<div id="micFailureMessage" class="error below-h2"><?php _e('An Error has occured. Please try again or contact plugin\'s author.','microp') ?></div>
				
			</div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			mic_attachment_id = <?php echo $_GET['postId']; ?>;
			mic_edited_size = '<?php echo $editedSize; ?>';
			mic_preview_scale = <?php echo $previewRatio; ?>;
			
			$('#mic-make-2x').change(function() {$('#mic-2x-status').toggle()});
			
			setTimeout(function() { 
				$('#jcrop_target').Jcrop({
					onChange: showPreview,
					onSelect: showPreview,
					minSize: [<?php echo $minWidth; ?>, <?php echo $minHeight; ?>],
					maxSize: [<?php echo $previewWidth; ?>, <?php echo $previewHeight; ?>],
					<?php if ( isset( $metaData['micSelectedArea'][$editedSize] ) ) { ?>
						setSelect: [<?php echo max(0, $metaData['micSelectedArea'][$editedSize]['x']) ?>, <?php echo max(0, $metaData['micSelectedArea'][$editedSize]['y']) ?>, <?php echo max(0, $metaData['micSelectedArea'][$editedSize]['x']) + $metaData['micSelectedArea'][$editedSize]['w']; ?>, <?php echo max(0, $metaData['micSelectedArea'][$editedSize]['y']) + $metaData['micSelectedArea'][$editedSize]['h']; ?>],
					<?php }else { ?>
						setSelect: [<?php echo max(0, ($previewWidth - ($previewHeight * $aspectRatio)) / 2) ?>, <?php echo max(0, ($previewHeight - ($previewWidth / $aspectRatio)) / 2) ?>, <?php echo $previewWidth * $aspectRatio; ?>, <?php echo $previewHeight; ?>],
					<?php }?>
					aspectRatio: <?php echo $aspectRatio; ?>,
				}, function() {
					jcrop_api = this;
				});
			}, 300);

			function showPreview(coords) {
				var rx = <?php echo $smallPreviewWidth; ?> / coords.w;
				var ry = <?php echo $smallPreviewHeight; ?> / coords.h;

				$('#preview').css({
					width: Math.round(rx * <?php echo $previewWidth; ?>)+ 'px',
					height: Math.round(ry * <?php echo $previewHeight; ?>) + 'px',
					marginLeft: '-' + Math.round(rx * coords.x) + 'px',
					marginTop: '-' + Math.round(ry * coords.y) + 'px'
				});
				
				var mic_2xok = Math.round(coords.w*mic_preview_scale) > (<?php echo $smallPreviewWidth; ?> * 2);
				if(mic_2xok === true) {
				  $('#mic-2x-status').toggleClass('mic-ok', mic_2xok).html('Compatible');
				} else {
				  $('#mic-2x-status').toggleClass('mic-ok', mic_2xok).html('Source too small');
				}
				$('#mic-2x-status').show();
			}
		});
		</script>
		<?php
		die;
	}
}
