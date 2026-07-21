<?php
/*
  Plugin Name: Hura Apps Photos
	Version: 1.5
  Description: Showing your Facebook Photos, Facebook Albums on your WordPress website.
  Author: Hura Apps
  Author URI: https://www.huraapps.com
 */

class Hura_Apps_Photos {

	function __construct() {
		add_action("admin_menu", array($this, 'add_menu_item'));
		add_shortcode('hmakfbalbum', array($this, 'HMAK_Facebook_Album_Shortcode'));
		add_shortcode('hmakfbphoto', array($this, 'HMAK_Facebook_Photo_Shortcode'));
		add_action('init', array($this, 'register_blocks'));
		add_action( 'wp_enqueue_scripts', array($this, 'adding_styles'));		
		add_action('admin_enqueue_scripts', array($this,'custom_css_mce_button'));
		add_action( 'admin_head', array($this, 'custom_mce_button'));		
		add_action( 'admin_init', function() {
			register_setting( 'hmak-facebook-photos-plugin-settings', 'facebook_album_fb_app_token' );
		});			
	}
	
	function create_upload_folder() {	 
		$upload = wp_upload_dir();
		$upload_dir = trailingslashit($upload['basedir']) . 'huraapps-photos';
		if (! is_dir($upload_dir)) {
		   wp_mkdir_p($upload_dir);
		}

		return $upload_dir;
	}
	
	function custom_mce_button() {
		if ( !current_user_can( 'edit_posts' ) || !current_user_can( 'edit_pages' ) ) {
			return false;
		}
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array($this, 'custom_tinymce_plugin' ));
			add_filter( 'mce_buttons', array($this, 'register_mce_button' ));
		}
	}	

	function custom_tinymce_plugin( $plugin_array ) {
		$plugin_array['custom_mce_button'] = plugins_url('/editor_plugin.js', __FILE__);
		return $plugin_array;
	}	

	function register_mce_button( $buttons ) {
		array_push( $buttons, 'custom_mce_button' );
		return $buttons;
	}
		
	function custom_css_mce_button() {
		wp_enqueue_style('symple_shortcodes-tc', plugins_url('/admin.css', __FILE__));
	}	

	function check_cURL(){
		return function_exists('curl_version') || function_exists('wp_remote_get');
	}
	
	function isSafari($ua) {
		return preg_match("/^((?!chrome).)*safari/i",$ua) && stripos($ua,' version/')!==false && stripos($ua,'mqqbrowser')===false;
	}

	function get_user_agent() {
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			return (string) $_SERVER['HTTP_USER_AGENT'];
		}

		return '';
	}

	function should_prefer_webp() {
		return !$this->isSafari($this->get_user_agent());
	}

	function sanitize_facebook_id($id) {
		$id = preg_replace('/[^0-9]/', '', (string) $id);

		return $id;
	}

	function get_cache_file($prefix, $id) {
		$upload_dir = $this->create_upload_folder();

		return trailingslashit($upload_dir) . $prefix . '_' . $id;
	}

	function read_cache_file($cache_file) {
		if (!file_exists($cache_file)) {
			return null;
		}

		$raw = file_get_contents($cache_file);
		if ($raw === false || $raw === '') {
			return null;
		}

		$decoded = base64_decode($raw, true);
		if ($decoded === false) {
			return null;
		}

		$data = @unserialize($decoded);

		if ($data === false && $decoded !== serialize(false)) {
			return null;
		}

		return $data;
	}

	function write_cache_file($cache_file, $data) {
		if ($data === null) {
			return;
		}

		file_put_contents($cache_file, base64_encode(serialize($data)), LOCK_EX);
	}

	function render_picture_html($jpg_source, $webp_source, $alt_text) {
		$jpg_source = (string) $jpg_source;
		$webp_source = (string) $webp_source;
		$alt_text = (string) $alt_text;

		if ($jpg_source === '') {
			return '';
		}

		$markup = '<picture>';
		if ($webp_source !== '') {
			$markup .= '<source srcset="' . esc_url($webp_source) . '" type="image/webp">';
		}
		$markup .= '<source srcset="' . esc_url($jpg_source) . '" type="image/jpeg">';
		$markup .= '<img src="' . esc_url($jpg_source) . '" alt="' . esc_attr($alt_text) . '">';
		$markup .= '</picture>';

		return $markup;
	}

	function register_blocks() {
		if (!function_exists('register_block_type')) {
			return;
		}

		wp_register_script(
			'hura-apps-photos-block-editor',
			plugins_url('/block-editor.js', __FILE__),
			array('wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-server-side-render'),
			'1.5',
			true
		);

		register_block_type('hura/apps-photos', array(
			'editor_script' => 'hura-apps-photos-block-editor',
			'render_callback' => array($this, 'render_hura_apps_photos_block'),
			'attributes' => array(
				'fbType' => array(
					'type' => 'string',
					'default' => 'hmakfbalbum',
				),
				'fbID' => array(
					'type' => 'string',
					'default' => '',
				),
				'lightbox' => array(
					'type' => 'boolean',
					'default' => false,
				),
			),
		));
	}

	function render_hura_apps_photos_block($attributes) {
		$fb_type = isset($attributes['fbType']) ? (string) $attributes['fbType'] : 'hmakfbalbum';
		$fb_id = isset($attributes['fbID']) ? $this->sanitize_facebook_id($attributes['fbID']) : '';
		$lightbox = !empty($attributes['lightbox']) ? 1 : 0;

		if ($fb_id === '') {
			return '';
		}

		$shortcode_atts = array(
			'id' => $fb_id,
			'lightbox' => $lightbox,
		);

		if ($fb_type === 'hmakfbphoto') {
			return $this->HMAK_Facebook_Photo_Shortcode($shortcode_atts);
		}

		return $this->HMAK_Facebook_Album_Shortcode($shortcode_atts);
	}

	function settings_page()
	{	
		?>

			<style>
				.hmak-facebook-photos-admin-wrapper h3.hndle2{
					border-bottom: 1px solid #eeeeee;
				}

				.hmak-facebook-photos-admin-wrapper .left-sections {
					width: 49%;
					margin-right: 1%;
					float: left;
				}

				.hmak-facebook-photos-admin-wrapper .right-sections{
					width: 50%;
					float: left;
				}

				.hmak-facebook-photos-admin-wrapper picture,
				.hmak-facebook-photos-admin-wrapper img,
				.hmak-facebook-photos-admin-wrapper input,
				.hmak-facebook-photos-admin-wrapper table{
					width:100%;
				}

				.hmak-facebook-photos-admin-wrapper .faq{
					margin-bottom:10px;
				}

				.hmak-facebook-photos-admin-wrapper .ask{
					font-weight: 700;
					font-size: 15px;
					cursor: pointer;
				}

				.hmak-facebook-photos-admin-wrapper .ans{
					display:none;
					word-wrap: break-word;
				}

				.hmak-facebook-photos-admin-wrapper .clear{
					clear:both;
				}

				#paypal-donation{
					text-align:center;
				}

				#paypal-donation input{
					width:auto;
				}

				@media (max-width: 1024px){
					.hmak-facebook-photos-admin-wrapper .left-sections,
					.hmak-facebook-photos-admin-wrapper .right-sections{
						width: 100%;
						float: none;
					}
				}
			</style>

			<script>
				jQuery(document).ready(function(){
					jQuery(".hmak-facebook-photos-admin-wrapper .ask").click(function(){
						jQuery('.hmak-facebook-photos-admin-wrapper .ask').removeClass('open'); 
						if(false==jQuery(this).next().is(':visible')){
							jQuery('.hmak-facebook-photos-admin-wrapper .ans').slideUp(300);
							jQuery(this).toggleClass('open');
						}
						jQuery(this).next().slideToggle(300);    
					});
				});
			</script>

			<h1>Hura Apps Photos</h1>
				<?php
					$localhost_ips = array('127.0.0.1','::1');
					if(!in_array($_SERVER['REMOTE_ADDR'], $localhost_ips)){
						if(!$this->check_cURL()){
							echo '<div class="error notice"><p style="color:red;">This plugin cannot work because cURL extension is not available on your web server.</p></div>';
						}					
					}else{
						echo '<div class="error notice"><p style="color:red;">This plugin cannot work because the Facebook API doesn\'t work on localhost.</p></div>';						
					}					
				?>						

			<div id="poststuff" class="hmak-facebook-photos-admin-wrapper metabox-holder has-right-sidebar">
				<div class="inner-sidebar">
					<div id="side-sortables" class="meta-box-sortabless ui-sortable">
						<div class="postbox ">
							<h3 class="hndle2"><span>About Hura Apps Photos</span></h3>
							<div class="inside">
								<p>This plugin will help to show Facebook photos or Facebook album on your WordPress website.</p>
								<form id="paypal-donation" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
									<input type="hidden" name="cmd" value="_s-xclick">
									<input type="hidden" name="hosted_button_id" value="VVV645CQZTCRA">
									<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
									<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
								</form>								
							</div>
						</div>

						<div class="postbox ">
								<h3 class="hndle2"><span>About Us</span></h3>
								<div class="inside">
									<p></p>
									<p>Hura Apps is a web development team based in Vietnam. You can contact us at:</p>
									<ul>
										<li>Email: <a href="mailto:support@huraapps.com">support@huraapps.com</a></li>
										<li>LinkedIn: <a href="https://www.linkedin.com/company/huraapps" target="_blank">huraapps</a></li>
										<li>Website: <a href="https://www.huraapps.com" target="_blank">www.huraapps.Com</a></li>
									</ul>
									<p></p>
								</div>
						</div>
					</div>
				</div>

				<div class="has-sidebar sm-padded">
					<div id="post-body-content" class="has-sidebar-content">
						<div class="meta-box-sortabless">
							<div class="postbox">
								<h3 class="hndle2">HURA APPS PHOTOS</h3>
								<div class="inside">									
									<?php
										if(current_user_can('administrator')){
									?>									

									<div class="left-sections">
										<h3 class="hndle2">Settings</h3>
										<form action="options.php" method="post">
											<?php
												settings_fields( 'hmak-facebook-photos-plugin-settings' );
												do_settings_sections( 'hmak-facebook-photos-plugin-settings' );
											?>
											<table>
												<tbody>													
													<tr>
														<td style="vertical-align:top;"><span class="label">Facebook Token</span></td>
														<td>
															<input type="text" name="facebook_album_fb_app_token" value="<?php echo esc_attr( get_option('facebook_album_fb_app_token') ); ?>">
															<em style="display:block;">Click <a href="https://developers.facebook.com/tools/debug/accesstoken/?access_token=<?php echo esc_attr( get_option('facebook_album_fb_app_token') ); ?>" target="_blank">here</a> to check to make sure the validity of this Facebook token.</em>
														</td>
													</tr>														
													<tr>
														<td></td>
														<td><?php submit_button(); ?></td>
													</tr>																									
												</tbody>
											</table>										
										</form>
									</div>

									<div class="right-sections">
								
										<h3 class="hndle2">User Manual</h3>
										
										<div class="faq">
											<div class="ask">How to retrieve Facebook Token?</div>
											<div class="ans">							
												<p>Go to <a href="https://fb.anhkiet.info" target="_blank">https://fb.anhkiet.info</a>.</p>
												<p>Click onto <i>Login with Facebook</i> button.</p>
												<p>Login into your Facebook account.</p>
												<p>Click <i>Continue as...</i> to install Anh Kiet Solutions app into your Facebook.</p>
												<p>Choose the page you want to retrieve photos and click <i>Next</i> button.</p>
												<p>Turn on the <i>Manage your Pages</i> option and click <i>Done</i> button.</p>
												<p>Click <i>OK</i> to generate your token.</p>
												<p>Your Facebook token will appear in next page.</p>
											</div>
										</div>
										
										<div class="faq">
											<div class="ask">How to find a Facebook album ID?</div>
											<div class="ans">											
												<p>Navigate to your photo album in Facebook. Then you can find the album ID in your browser's address bar.</p>
												<p>For example: https://www.facebook.com/pg/mangbinhdinh.info/photos/?tab=album&album_id=<font style="font-weight:600;color:red;">1091121734251598</font></p>											
											</div>
										</div>
								
										<div class="faq">
											<div class="ask">How to find a Facebook photo ID?</div>
											<div class="ans">											
												<p>Navigate to your photo in Facebook. Then you can find the photo ID in your browser's address bar.</p>
												<p>For example: https://www.facebook.com/mangbinhdinh.info/photos/a.1091121734251598.1073741850.344612895569156/<font style="font-weight:600;color:red;">1091121894251582</font>/?type=3&theater</p>											
											</div>
										</div>
						
										<div class="faq">
											<div class="ask">How to insert Facebook Album/Photo into a post/page?</div>
											<div class="ans">											
												<p>You can insert the Facebook Album or  Photo in any page/post or even in PHP code using plugin shortcode.</p>
												<p>Album: <code>[hmakfbalbum id=12345]</code> - 12345 is a Facebook album ID</p>
												<p>Photo: <code>[hmakfbphoto id=54321]</code> - 54321 is a Facebook photo ID</p>
												<p>Also a command button on editor help you easier to insert shortcode.</p>
												<p><img src="<?php echo plugins_url('img/btn-editor.png', __FILE__); ?>"></p>
											</div>
										</div>
										<div class="faq">
											<div class="ask">How to set up lightbox for images?</div>
											<div class="ans">											
												<p>Install <a href="https://wordpress.org/extend/plugins/fancybox-for-wordpress/" target="_blank">FancyBox for Wordpress</a>.</p>
												<p>After installation and activation of FancyBox plugin go to it's settings panel.</p>
												<p>Select "<i>Extra Calls</i>" Tab.</p>
												<p>Check (activate) "<i>Additional FancyBox Calls</i>".</p>
												<p>A textbox will expand. Put the following code there.</p>
												<code>jQuery(".a.hmak-fancybox").fancybox({<br>
														'transitionIn': 'elastic',<br>
														'transitionOut': 'elastic',<br>
														'speedIn': 600,<br>
														'speedOut': 200,<br>
														'type': 'image'<br>
													});
												</code>
												<p>Save Changes and reload the album on frontend</p>
												<p>Now you should see the images of this plugin loading in fancybox.</p>
											</div>
										</div>

										<div class="faq">

											<div class="ask">I found an issue. How do I report it?</div>

											<div class="ans">											

												<p>If you found any issue, please let us know by send email to us at <a href="mailto:info@huraapps.com">info@huraapps.com</a>.</p>
											</div>
										</div>
									</div>
									<?php
										}else{
											echo "<p style='text-align:center;'>You don't have permission to access</p>";
										}
									?>
								</div>
								<div class="clear"></div>
							</div>							
							<div class="postbox">
								<div class="inside">
									<p style="text-align:center;">Copyright &copy; <?php echo date("Y"); ?> by <a href="https://www.huraapps.com" target="_blank">Hura Apps</a>. All rights reserved.<br>Developed and Designed by <a href="https://anhkiet.biz" target="_blank">Kiet Huynh</a>.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
	}

	function HMAK_fetchUrl($url)
	{
		if (function_exists('wp_remote_get')) {
			$response = wp_remote_get($url, array('timeout' => 20));
			if (is_wp_error($response)) {
				return false;
			}

			$code = wp_remote_retrieve_response_code($response);
			if ((int) $code < 200 || (int) $code >= 300) {
				return false;
			}

			return wp_remote_retrieve_body($response);
		}

		if (!function_exists('curl_init')) {
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		$retData = curl_exec($ch);
		curl_close($ch);

		return $retData;
	}

	function HMAK_Facebook_Photo_Shortcode($atts) {
		$default = array(
			'id' => '',
			'lightbox'=>0,
		);
		$fb = shortcode_atts($default, $atts);		
		$photo_id = $this->sanitize_facebook_id($fb['id']);
		$lightbox = !empty($fb['lightbox']) ? 1 : 0;
		$cachetime = 3600;

		if ($photo_id === '') {
			return '';
		}

		$prefer_webp = $this->should_prefer_webp();
		$cache_file = $this->get_cache_file('photo', $photo_id);
		
		if (file_exists($cache_file) && (time() - $cachetime < filemtime($cache_file))) {
			$image = $this->read_cache_file($cache_file);
		}

		if (!isset($image) || !is_array($image) || isset($image['error'])) {
			$facebook_access_token = trim((string) get_option('facebook_album_fb_app_token'));
			if ($facebook_access_token === '') {
				return '';
			}

			$url = 'https://graph.facebook.com/' . $photo_id . '?fields=webp_images,images&access_token=' . rawurlencode($facebook_access_token);
			$response = $this->HMAK_fetchUrl($url);
			if ($response === false) {
				return '';
			}

			$image = json_decode($response, true);
			if (!is_array($image) || isset($image['error'])) {
				return '';
			}

			$this->write_cache_file($cache_file, $image);
		}

		if (!isset($image['images'][0]['source'])) {
			return '';
		}

		$jpg_source = isset($image['images'][0]['source']) ? $image['images'][0]['source'] : '';
		$webp_source = isset($image['webp_images'][0]['source']) ? $image['webp_images'][0]['source'] : $jpg_source;
		$image_source = $prefer_webp ? $webp_source : $jpg_source;

		$code = '<div class="hmak-facebook-album-image-wrapper">';
		if ($lightbox === 1) {
			$code .= '<a class="hmak-fancybox" href="' . esc_url($image_source) . '">';
		}
		$code .= $this->render_picture_html($jpg_source, $webp_source, 'Facebook photo');
		if ($lightbox === 1) {
			$code .= '</a>';
		}
		$code .= '</div>';

		return $code;
	}

	function HMAK_Facebook_Album_Shortcode($atts) {
		$default = array(
			'id' => '',
			'lightbox'=>0,
		);
		$fb = shortcode_atts($default, $atts);
		$album_id = $this->sanitize_facebook_id($fb['id']);
		$lightbox = !empty($fb['lightbox']) ? 1 : 0;
		$cachetime = 3600;

		if ($album_id === '') {
			return '';
		}

		$prefer_webp = $this->should_prefer_webp();
		$cache_file = $this->get_cache_file('album', $album_id);
		$album = null;

		if (file_exists($cache_file) && (time() - $cachetime < filemtime($cache_file))) {
			$album = $this->read_cache_file($cache_file);
		}

		if (!is_object($album) || isset($album->error)) {
			$facebook_access_token = trim((string) get_option('facebook_album_fb_app_token'));
			if ($facebook_access_token === '') {
				return '';
			}

			$url = "https://graph.facebook.com/{$album_id}?fields=photos.limit(100){webp_images,name,images}&access_token=" . rawurlencode($facebook_access_token);
			$response = $this->HMAK_fetchUrl($url);
			if ($response !== false) {
				$fresh_album = json_decode($response);
				if (is_object($fresh_album) && !isset($fresh_album->error)) {
					$album = $fresh_album;
					$this->write_cache_file($cache_file, $album);
				}
			}

			if ((!is_object($album) || isset($album->error)) && file_exists($cache_file)) {
				$album = $this->read_cache_file($cache_file);
			}
		}

		if (!is_object($album) || isset($album->error) || !isset($album->photos->data) || !is_array($album->photos->data)) {
			return '';
		}

		$code = '';
		foreach($album->photos->data as $photo) {
			if (!isset($photo->images[0]->source)) {
				continue;
			}

			$caption_text = isset($photo->name) ? (string) $photo->name : '';
			$jpg_source = (string) $photo->images[0]->source;
			$webp_source = isset($photo->webp_images[0]->source) ? (string) $photo->webp_images[0]->source : $jpg_source;
			$image_source = $prefer_webp ? $webp_source : $jpg_source;

			$code .= '<div class="hmak-facebook-album-image-wrapper">';
			if ($lightbox === 1) {
				$code .= '<a class="hmak-fancybox" href="' . esc_url($image_source) . '" rel="fancybox">';
			}
			$code .= $this->render_picture_html($jpg_source, $webp_source, $caption_text);
			if ($lightbox === 1) {
				$code .= '</a>';
			}
			if ($caption_text !== '') {
				$code .= '<div class="hmak-facebook-album-image-caption">' . esc_html($caption_text) . '</div>';
			}
			$code .= '</div>';
		}

		return $code;
	}

	function adding_styles() {
		wp_enqueue_style('hura-apps-photos-style', plugins_url('style.css', __FILE__), array(), '1.5');
	}

	function add_menu_item()
	{
		add_menu_page("Hura Apps Photos Panel", "Hura Apps Photos", "manage_options", "hura-apps-photos-panel", array($this,"settings_page"), null, 99);
	}
}

$Hura_Apps_Photos = new Hura_Apps_Photos();

?>