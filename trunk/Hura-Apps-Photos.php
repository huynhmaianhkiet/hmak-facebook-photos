<?php
/*
  Plugin Name: Hura Apps Photos
  Version: 1.4
  Description: Showing your Facebook Photos, Facebook Albums on your WordPress website.
  Author: Hura Apps
  Author URI: https://www.huraapps.com
 */

class Hura_Apps_Photos {

	function __construct() {
		add_action("admin_menu", array(&$this, 'add_menu_item'));
		add_shortcode('hmakfbalbum', array(&$this, 'HMAK_Facebook_Album_Shortcode'));
		add_shortcode('hmakfbphoto', array(&$this, 'HMAK_Facebook_Photo_Shortcode'));
		add_action( 'wp_enqueue_scripts', array(&$this, 'adding_styles'));		
		add_action('admin_enqueue_scripts', array(&$this,'custom_css_mce_button'));
		add_action( 'admin_head', array(&$this, 'custom_mce_button'));		
		add_action( 'admin_init', function() {
			register_setting( 'hmak-facebook-photos-plugin-settings', 'facebook_album_fb_app_token' );
		});			
	}
	
	function create_upload_folder() {	 
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/huraapps-photos';
		if (! is_dir($upload_dir)) {
		   mkdir( $upload_dir, 0700 );
		}
	}
	
	function custom_mce_button() {
		if ( !current_user_can( 'edit_posts' ) || !current_user_can( 'edit_pages' ) ) {
			return false;
		}
		if ( 'true' == get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array(&$this, 'custom_tinymce_plugin' ));
			add_filter( 'mce_buttons', array(&$this, 'register_mce_button' ));
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
		return function_exists('curl_version');
	}
	
	function isSafari($ua) {
		return preg_match("/^((?!chrome).)*safari/i",$ua) && stripos($ua,' version/')!==false && stripos($ua,'mqqbrowser')===false;
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
									<p>Hura Apps is a Vietnam-based Web & Mobile App development team. You can contact us via:</p>
									<ul>
										<li>Email: <a href="mailto:info@huraapps.com">Info@huraapps.Com</a></li>
										<li>Facebook: <a href="https://www.facebook.com/huraapps" target="_blank">Huraapps</a></li>
										<li>Website: <a href="https://www.huraapps.com" target="_blank">wWw.HuraApps.Com</a></li>
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
		$photo_id = $fb['id'];		
		$lightbox = $fb['lightbox'];
		
		$this->create_upload_folder();
		
		$is_image = false;
		$upload = wp_upload_dir();
		$imageData = $upload['basedir'].'/huraapps-photos/photo_'.$photo_id;		
				
		if (file_exists($imageData)){
			$image = unserialize(base64_decode(file_get_contents($imageData)));
			$image_source = ( isset($image['webp_images']) && $this->isSafari($_SERVER['HTTP_USER_AGENT'])==false )?$image['webp_images'][0]['source']:$image['images'][0]['source'];
			$jpg_source = ( isset($image['images']) ) ? $image['images'][0]['source'] : '';
			$webp_source = ( isset($image['webp_images']) ) ? $image['webp_images'][0]['source'] : $jpg_source;
			if( getimagesize($image_source) ){
				$is_image = true;
			}
		}
		
		if(!$is_image){
			$facebook_access_token = get_option('facebook_album_fb_app_token');
			$image = json_decode($this->HMAK_fetchUrl("https://graph.facebook.com/".$photo_id."?fields=webp_images,images&access_token=".$facebook_access_token),true);
			if( !isset($image['error']) ){
				file_put_contents($imageData,base64_encode(serialize($image)));
				$is_image = true;
			}
		}
		
		$code = '';
		if($is_image){
			$image_source = ( isset($image['webp_images']) && $this->isSafari($_SERVER['HTTP_USER_AGENT'])==false )?$image['webp_images'][0]['source']:$image['images'][0]['source'];	
			$jpg_source = ( isset($image['images']) ) ? $image['images'][0]['source'] : '';
			$webp_source = ( isset($image['webp_images']) ) ? $image['webp_images'][0]['source'] : $jpg_source;
			$code .= '<div class="hmak-facebook-album-image-wrapper">';
			if($lightbox==1){$code .= '<a class="hmak-fancybox" href="'.$image_source.'">';}
			//$code .= '<img src="'.$image_source.'">';
			$code .= '<picture>';
			$code .= '<source srcset="'.$webp_source.'" type="image/webp">';
			$code .= '<source srcset="'.$jpg_source.'" type="image/jpeg"> ';
			$code .= '<img src="'.$jpg_source.'">';
			$code .= '</picture>';
			if($lightbox==1){$code .= '</a>';}
			$code .= '</div>';
		}		
		return $code;
	}

	function HMAK_Facebook_Album_Shortcode($atts) {
		$default = array(
			'id' => '',
			'lightbox'=>0,
		);
		$fb = shortcode_atts($default, $atts);		
		$album_id = $fb['id'];		
		$lightbox = $fb['lightbox'];
		
		$this->create_upload_folder();
		
		$is_album = false;
		$upload = wp_upload_dir();
		$albumData = $upload['basedir'].'/huraapps-photos/album_'.$album_id;
		$cachetime = 3600;
		
		if (file_exists($albumData) && (time() - $cachetime < filemtime($albumData))){
			$album = unserialize(base64_decode(file_get_contents($albumData)));
			$is_album = true;
		}else{
			$facebook_access_token = get_option('facebook_album_fb_app_token'); 
			$album = json_decode($this->HMAK_fetchUrl("https://graph.facebook.com/{$album_id}?fields=photos.limit(100){webp_images,name,images}&access_token={$facebook_access_token}"));
			if( !isset($album->error) ){
				file_put_contents($albumData,base64_encode(serialize($album)));
				$is_album = true;	
			}else{
				if(file_exists($albumData)){
					$album = unserialize(base64_decode(file_get_contents($albumData)));
					$is_album = true;	
				}
			}					
		}		
		
		$code = '';		
		if($is_album){
			if( !isset($album->error) ){
				$photos = $album->photos->data;					
				$images = array();
				foreach($photos as $photo){
					$caption = "";
					if(isset($photo->name)){
						$caption = $photo->name;
					}
					$images[] = array(
						'src'	=> ( isset($photo->webp_images) && $this->isSafari($_SERVER['HTTP_USER_AGENT'])==false )?$photo->webp_images[0]->source:$photo->images[0]->source,
						'jpg'	=> ( isset($photo->images) ) ? $photo->images[0]->source : '',
						'webp'	=> ( isset($photo->webp_images) ) ? $photo->webp_images[0]->source : $photo->images[0]->source,
						'alt'	=> $caption
					);
				}
				$code = "";
				foreach($images as $image){
					if($image['alt']!=""){
						$caption = '<div class="hmak-facebook-album-image-caption">'.$image['alt'].'</div>';
					}else{
						$caption = '';
					}			
					$code .= '<div class="hmak-facebook-album-image-wrapper">';
					if($lightbox==1){$code .= '<a class="hmak-fancybox" href="'.$image['src'].'" rel="fancybox">';}
					//$code .= '<img src="'.$image['src'].'">';
					$code .= '<picture>';
					$code .= '<source srcset="'.$image['webp'].'" type="image/webp">';
					$code .= '<source srcset="'.$image['jpg'].'" type="image/jpeg"> ';
					$code .= '<img src="'.$image['jpg'].'">';
					$code .= '</picture>';
					if($lightbox==1){$code .= '</a>';}
					$code .= $caption;
					$code .= '</div>';
				}
			}
		}
		
		return $code;
	}

	function adding_styles() {
		wp_register_style('my_stylesheet', plugins_url('style.css', __FILE__));
		wp_enqueue_style('my_stylesheet');
	}

	function add_menu_item()
	{
		add_menu_page("Hura Apps Photos Panel", "Hura Apps Photos", "manage_options", "hura-apps-photos-panel", array(&$this,"settings_page"), null, 99);
	}
}

$Hura_Apps_Photos = new Hura_Apps_Photos();

?>