<?php
/*
Plugin Name: Captcha Spam Blocker
Description: Enhance your site’s security with dynamic CAPTCHA, blocking spam and bot access on forms. GDPR-compliant.
Version: 2.0.0
Requires at least: 4.0
Tested up to: 6.5.3
Requires PHP: 5.4
Author: botezatu
Author URI: https://botezatu.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: captcha, security, spam protection, antispam
Text Domain: captcha-spam-blocker
*/



if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CSB_BOTEZATU_CaptchaSpamBlocker' ) ) {

	class CSB_BOTEZATU_CaptchaSpamBlocker {
		
		public $menuSlug = 'captcha-spam-blocker';
 		public $showOnLoginForm;
		public $showOnCommentsForm;
		public $showOnRegisterForm;
		public $showOnLostPassForm;
		public $showOnWooLogin;
		public $showOnWooRegister;
		public $showOnWooLostPass;
		public $showOnWooRatingsAndReviews;
		public $showOnWooCheckout;		
		public $showEnableHoneyPot;
		public $showAddAJavaScriptLayer;
		public $showDisableXMLRPC;	
 		public $honeypotNames = [ 'csb_botezatu_user_confirm_email_address', 'csb_botezatu_wp_verify_user_name', 'csb_botezatu_site_check_password', 'csb_botezatu_wp_validate_username', 'csb_botezatu_site_zip_code_validation', 'csb_botezatu_wp_user_age_verification', 'csb_botezatu_wp_user_response','csb_botezatu_wp_time_stamp', 'csb_botezatu_site_session_verify', 'csb_botezatu_site_wp_confirm_user' ]; 
 		public $captcha_occurrences;
 		public $is_product_page = false;
		public $nameWpOption = 'csb_botezatu_captcha_spam_blocker_option'; 
		public $is_cf7_version_ok = false;
		public $is_woo_version_ok = false;
		public $is_gd_active;
 		public $settings;
 		private static $_instance = null;
		private $version = '2.0.0';
  	  
		public function __construct()  {
		  
			if( !session_id() ) {
				session_start();
  			}  
			
			$this->settings = $this->getMainSettings();
			
			$this->is_gd_active = extension_loaded('gd')&&function_exists('gd_info') ? true : false;
  
  			$this->showOnLoginForm = (int)$this->settings['showOnLoginForm']==1 ? true : false;
			$this->showOnCommentsForm = (int)$this->settings['showOnCommentsForm']==1 ? true : false;
			$this->showOnRegisterForm = (int)$this->settings['showOnRegisterForm']==1 ? true : false;
			$this->showOnLostPassForm = (int)$this->settings['showOnLostPassForm']==1 ? true : false;
			$this->showOnWooLogin = (int)$this->settings['showOnWooLogin']==1 ? true : false;
			$this->showOnWooRegister = (int)$this->settings['showOnWooRegister']==1 ? true : false;
 			$this->showOnWooLostPass = (int)$this->settings['showOnWooLostPass']==1 ? true : false;
			$this->showOnWooRatingsAndReviews = (int)$this->settings['showOnWooRatingsAndReviews']==1 ? true : false;
			$this->showOnWooCheckout = (int)$this->settings['showOnWooCheckout']==1 ? true : false;
 			$this->showEnableHoneyPot = (int)$this->settings['showEnableHoneyPot']==1 ? true : false;
			$this->showAddAJavaScriptLayer = (int)$this->settings['showAddAJavaScriptLayer']==1 ? true : false;
			$this->showDisableXMLRPC = (int)$this->settings['showDisableXMLRPC']==1 ? true : false;		
 
  			$this->captcha_occurrences = [
				'showOnLoginForm'=>0,
				'showOnCommentsForm'=>0,
				'showOnRegisterForm'=>0,
				'showOnLostPassForm'=>0,
				'showOnWooLogin'=>0,
				'showOnWooRegister'=>0,
				'showOnWooLostPass'=>0,
				'showOnWooRatingsAndReviews'=>0,
				'showOnWooCheckout'=>0,
			];
  
 			add_action('plugins_loaded', array($this, 'site_load_textdomain'));
			add_action('admin_menu', array($this, 'action_admin_menu'));
 			add_action('wp_loaded', array($this, 'load_all_captchas'));
			
			add_action('wp_ajax_csb_botezatu_generate_captcha', array($this,'csb_botezatu_generate_captcha'));
			add_action('wp_ajax_nopriv_csb_botezatu_generate_captcha', array($this,'csb_botezatu_generate_captcha'));	
   		}


		public function load_all_captchas() {
			
			$this->is_cf7_version_ok = defined('WPCF7_VERSION')&&version_compare(WPCF7_VERSION, '4.6', '>=') ? true : false;
			$this->is_woo_version_ok = class_exists('WooCommerce')&&defined('WC_VERSION')&&version_compare(WC_VERSION, '3.0', '>=') ? true : false;
			
 			add_action('template_redirect',array($this, 'detect_woo_product_page'),1 );
    			
			if( $this->showOnLoginForm ) {
				if( $this->_is_login() ) {
					add_action('login_form', array($this, 'captcha_login_form'), 99);
					add_filter('authenticate', array($this, 'validate_authenticate'), 15); 
				}
			}
 			if( $this->showOnCommentsForm || $this->showOnWooRatingsAndReviews ) {		
 				if( is_user_logged_in() ) {
					
					add_filter ('comment_form_field_comment', array($this, 'captcha_comments_form_logged_in'), 11);
 				} else {
					add_action('comment_form_after_fields', array($this, 'captcha_comments_form'), 99);
 				}	
  				add_action('comment_form', array($this, 'captcha_comments_form'), 99); 	
 				add_filter('preprocess_comment', array($this, 'process_comment'), 1);	
			}			
			if( $this->showOnRegisterForm ) {
  				add_action('register_form', array($this, 'captcha_register_form'), 70);
				add_action('register_post', array($this, 'validate_registration_captcha'), 10, 3);
  			}
			if( $this->showOnLostPassForm ) {
				add_action('lostpassword_form', array($this, 'captcha_lostpass_form'));	
 				add_action('lostpassword_post',array($this, 'lostpassword_verify'));	
 			}			
			if( $this->showOnWooLogin && $this->is_woo_version_ok ) {
				if( !$this->_is_login() ) {
					add_action('woocommerce_login_form' ,array($this, 'captcha_login_form_woo' ), 10, 0 );
					add_filter('wp_authenticate_user', array( $this, 'woo_authenticate' ), 10, 2 );	 
				}
			}
			if( $this->showOnWooRegister && $this->is_woo_version_ok ) {
	 			add_action('woocommerce_register_form', array( $this, 'captcha_woo_register_form' ) );
 				add_filter('woocommerce_registration_errors', array($this, 'validate_woo_registration_captcha'), 10, 3 );  				
   			}
			if( $this->showOnWooLostPass && $this->is_woo_version_ok ) {
 				add_action('woocommerce_lostpassword_form', array($this, 'captcha_woo_lostpass_form'), 99);
				add_action('lostpassword_post',array($this, 'woo_lostpassword_verify'));	
			}
			if( $this->showOnWooCheckout && $this->is_woo_version_ok ) {
				add_action('woocommerce_review_order_before_submit', array($this, 'captcha_woo_checkout_form'), 10, 2 );
 				add_action('woocommerce_after_checkout_validation', array( $this, 'captcha_woo_checkout_check' ), 10, 2 );
  			}	

			if( $this->showDisableXMLRPC ) {
				add_filter('xmlrpc_enabled', '__return_false');
			}
 	 
			if( $this->is_cf7_version_ok ) {
				add_filter('wpcf7_form_elements', [ $this, 'captcha_wpcf7_form_elements' ] );
				add_shortcode('csb_botezatu_captcha_spam_blocker', [ $this, 'captcha_shortcode' ] );
				add_filter('wpcf7_feedback_response', [ $this,'custom_captcha_error_message' ], 10, 2);
				add_action('wpcf7_init', [ $this, 'captcha_add_form_tag_button' ], 10, 0 );
				add_filter('wpcf7_validate', [ $this, 'captcha_wpcf7_verify' ], 20, 2 );
			}
  
  			add_filter("plugin_action_links_" . plugin_basename(__FILE__), [ $this, 'site_admin_add_settings_link'] );
  			
			add_action('wp_enqueue_scripts', [$this, 'load_captcha_spam_blocker_js_and_styles']);
			add_action('admin_enqueue_scripts', [$this, 'load_captcha_spam_blocker_js_and_styles']);
			add_action('login_enqueue_scripts', [$this, 'load_captcha_spam_blocker_js_and_styles']);			
 		}
  
		
		public function load_captcha_spam_blocker_js_and_styles() {
			$js_url = plugins_url('assets/js/captcha-spam-blocker.js', __FILE__);
			$css_url = plugins_url('assets/css/captcha-spam-blocker.css', __FILE__);
 			wp_register_style('csb-botezatu-captcha-spam-blocker-css', esc_url($css_url), array(), $this->version );
			wp_enqueue_style('csb-botezatu-captcha-spam-blocker-css');
 			wp_register_script('csb-botezatu-captcha-spam-blocker-js', esc_url($js_url), array(), $this->version, true );
 			wp_localize_script('csb-botezatu-captcha-spam-blocker-js', 'csb_botezatu_ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			));			
 			wp_enqueue_script('csb-botezatu-captcha-spam-blocker-js');
		}
		
		public function csb_botezatu_generate_captcha() {
 			
			if (!session_id()) {
				session_start();
			}
			
			$txt_rnd = (string)wp_rand(1111, 9999);
			$_SESSION['csb_botezatu_captcha_code_input'] = $txt_rnd;
			
			$nr1 = $txt_rnd[0];
			$nr2 = $txt_rnd[1];
			$nr3 = $txt_rnd[2];
			$nr4 = $txt_rnd[3];

			$txt_fin = $nr1;
			$txt_fin .= (wp_rand(1, 2) == 1) ? " " : "";
			$txt_fin .= $nr2;
			$txt_fin .= (wp_rand(1, 2) == 1) ? " " : "";
			$txt_fin .= $nr3;
			$txt_fin .= (wp_rand(1, 2) == 1) ? " " : "";
			$txt_fin .= $nr4;

			$my_img = imagecreate(90, 40);
			$background = imagecolorallocate($my_img, 247, 247, 247);
			$text_color = imagecolorallocate($my_img, 10, 10, 10);
			imagestring($my_img, 5, 20, 10, $txt_fin, $text_color);

			for ($i = 0; $i < 2; $i++) {
				$line_color = imagecolorallocate($my_img, wp_rand(200, 230), wp_rand(200, 230), wp_rand(200, 230));
				imageline($my_img, wp_rand(0, 90), wp_rand(0, 40), wp_rand(0, 90), wp_rand(0, 40), $line_color);
			}

			for ($i = 0; $i < 25; $i++) {
				$pixel_color = imagecolorallocate($my_img, 0, 0, 255);
				imagesetpixel($my_img, wp_rand(0, 90), wp_rand(0, 40), $pixel_color);
			}

			ob_start();
			imagepng($my_img);
			$imagedata = ob_get_contents();
			ob_end_clean();
			imagedestroy($my_img);
			$base64 = base64_encode($imagedata);
			$captcha_code_str = strlen($this->settings['translate_captcha_code']) > 1 ? $this->settings['translate_captcha_code'] : 'Captcha Code';
			
 			$html = "<table class='csb_botezatu_captcha_table_show'><tr><td style='vertical-align:top;padding-right:10px;'><img class='csb_botezatu_captcha_img' src='data:image/png;base64," . esc_html($base64) . "'></td><td style='vertical-align:top;'><input type='text' class='csb_botezatu_captcha_code_input' name='csb_botezatu_captcha_code_input' placeholder='" . esc_attr($captcha_code_str) . "' required autocomplete='off'></td></tr></table>";
			
			$html = "<div class='csb_botezatu_captcha_container'>
						<div class='csb_botezatu_captcha_img_container'>
							<img class='csb_botezatu_captcha_img' src='data:image/png;base64," . esc_html($base64) . "'>
						</div>
						<div class='csb_botezatu_captcha_input_container'>
							<input type='text' class='csb_botezatu_captcha_code_input' name='csb_botezatu_captcha_code_input' placeholder='" . esc_attr($captcha_code_str) . "' required autocomplete='off'>
							
						</div>
					 </div>";
			
			
			die($html);
		}
	
 
		public function captcha_add_form_tag_button() {
			if ( class_exists( 'WPCF7_Submission' ) && function_exists('wpcf7_add_form_tag') ) {
				wpcf7_add_form_tag( 'csb_captcha_spam_blocker', [ $this, 'captcha_shortcode' ] );
			}
		}
 		
		public function captcha_shortcode( $atts ) { 
  			return $this->captchaShow('captcha_wpcf7');
		}		
 		
		public function captcha_wpcf7_form_elements( $form ) {
			$form = do_shortcode( $form );
			return $form;
		}		
 		
		public function custom_captcha_error_message($response, $result) {
 			if( $result['status'] == 'validation_failed' ) {
 				$count_invalid = 0;
				foreach( $result['invalid_fields'] as $k=>$v ) {
					if( $k!='csb_botezatu_captcha_code_input' && $k!='stopword_field_cf7' ) {
						$count_invalid++;
					}
				}
				if( $count_invalid==0 ) {
 					if( isset($result['invalid_fields']['csb_botezatu_captcha_code_input']) ) {
						$response['message'] = $result['invalid_fields']['csb_botezatu_captcha_code_input']['reason'];
					}
					if( isset($result['invalid_fields']['stopword_field_cf7']) ) {
						$response['message'] = $result['invalid_fields']['stopword_field_cf7']['reason'];
					}
				}
			}
			return $response;
		}
				
		
		public function captcha_wpcf7_verify( $request, $tag ) {  
			if ( !class_exists( 'WPCF7_Submission' ) ) {
				return $request;
			}

 			$_wpcf7 = isset($_POST['_wpcf7']) ? (int)sanitize_text_field(wp_unslash($_POST['_wpcf7'])) : 0;  
 			if( $_wpcf7==0 ) {
				return $request;
			}

 			$submission = WPCF7_Submission::get_instance();
			$data = $submission->get_posted_data();
			
 			$result = $this->validateCaptcha();
			
 			if( $result == 'valid' ) {
				$arr_stopword_fields_cf7 = $this->comma2Arr($this->settings['stopword_fields_cf7']);
				$arr_stopword_words = $this->comma2Arr($this->settings['stopword_list']);
 				if( count($arr_stopword_fields_cf7)>0 && count($arr_stopword_words)>0 ) {
 					$isBadField = '';
					foreach( $arr_stopword_fields_cf7 as $fcf7 ) {
						$content = isset($data[$fcf7]) ? strtolower($data[$fcf7]) : '';
						if( strlen($content)>1 ) {
							foreach( $arr_stopword_words as $word ) {
								$word = strtolower($word);
								if( strpos($content,$word) !== false ) {
									$isBadField = $fcf7;
								}
							}
						}
					}
 					if( strlen($isBadField)>1 ) {
						$txt_invalid = strlen($this->settings['translate_invalid_content'])>1 ? $this->settings['translate_invalid_content'] : 'Invalid Form Content';
						$result = esc_html($txt_invalid);
						$request->invalidate([ 'name' => 'stopword_field_cf7' ], $result);
					}
 				}
			}			
			
 			if( $result !== 'valid' ) {
 				$request->invalidate([ 'name' => 'csb_botezatu_captcha_code_input' ], $result);
 			}

			return $request;
		}		
 
 		public function detect_woo_product_page() {
			if( function_exists('is_singular') && $this->is_woo_version_ok ) {
				if( is_singular('product') ) { 
					$this->is_product_page = true; 
				}				
			}
 		}		
 
		public function captcha_login_form() {
			if( !$this->showOnLoginForm ) { 
				return true; 
			}
			echo $this->captchaShow('showOnLoginForm');  
		}		
		
		public function captcha_login_form_woo() {
			if( !$this->showOnWooLogin ) { 
				return true; 
			}
			echo $this->captchaShow('showOnWooLogin');  
		}	

		public function captcha_woo_checkout_form() {
			if( !$this->showOnWooCheckout ) { 
				return true; 
			}
			echo $this->captchaShow('showOnWooCheckout');  
		}		
		
		public function captcha_comments_form() {
 			if( !$this->comments_can_continue() ) {
				return true; 
			}
			echo $this->captchaShow('showOnCommentsForm');  
		}	
 
		public function captcha_register_form() {
			if( !$this->showOnRegisterForm ) { 
				return; 
			}
			echo $this->captchaShow('showOnRegisterForm');  
		}
		
		public function captcha_woo_register_form() {
			if( !$this->showOnWooRegister ) { 
				return; 
			}
			echo $this->captchaShow('showOnWooRegister');  
		}		

		public function captcha_lostpass_form() {
			if( !$this->showOnLostPassForm ) { 
				return; 
			}
			echo $this->captchaShow('showOnLostPassForm');  
		}	

		public function captcha_woo_lostpass_form() {
			if( !$this->showOnWooLostPass ) { 
				return; 
			}
			echo $this->captchaShow('showOnWooLostPass');  
		}		
		
		public function captcha_woo_checkout() {
			if( !$this->showOnWooCheckout ) { 
				return; 
			}
			echo $this->captchaShow('showOnWooCheckout');  
		}		
 
		public function validate_authenticate($user) {
 			if( !$this->showOnLoginForm ) {
				return $user;
			}
			if( !$this->_is_login()  ) {
				return $user;
			}
 			if( empty($_POST['log']) && empty($_POST['pwd']) ) {  
				return $user;
			}			
			if( $this->is_xmlrpc_or_restapi_request_type() ) {
				return $user;
			}
  			$result = $this->validateCaptcha();
			if( $result !== 'valid' ) {
				remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
				remove_filter('authenticate', 'wp_authenticate_cookie', 30, 3);
   				$login_url = wp_login_url(); 
 				wp_safe_redirect(add_query_arg('captcha_error', 'yes', $login_url));
				exit;
			} 			
			return $user;
		} 
 		
		public function validate_registration_captcha($login, $email, $errors) {
  			if( !$this->showOnRegisterForm ) { 
				return; 
			}
   			$result = $this->validateCaptcha();
			if( $result != 'valid' ) {
				if( !is_wp_error( $errors ) ) {	
					$errors = new WP_Error();
				}				
				return $errors->add( 'captcha_error', $result );
 			}
 			return;
		} 
 		
		public function validate_woo_registration_captcha($errors, $sanitized_user_login, $user_email) {
  			if( !$this->showOnWooRegister ) { 
				return $errors;
			}			
   			$result = $this->validateCaptcha();
			if( $result != 'valid' ) {
                $errors = new WP_Error( 'captcha_error', $result );
			} 
			return $errors;
		}		
		
		public function captcha_woo_checkout_check( $fields, $errors ) {
			if( !$this->showOnWooCheckout ) { 
				return;
			}
			$result = $this->validateCaptcha();
			if( $result != 'valid' ) {
				$errors->add( 'validation', $result );
			}
		}		
   
		public function woo_authenticate( $user, $password ) {
 			if( !$this->showOnWooLogin ) { 
				return $user;
			}			
   			$result = $this->validateCaptcha();
			if( $result != 'valid' ) {
				return new WP_Error( 'captcha_error', $result );
 			}			
			return $user;
		}
 
 		public function lostpassword_verify( $errors='' ) {
 			if( !$this->showOnLostPassForm ) { 
				return; 
			}
 			$result = $this->validateCaptcha();
			if( $result != 'valid') {
				if( !is_wp_error( $errors ) ) {	
					$errors = new WP_Error();
				}				
				if( isset($_POST['wc_reset_password']) && isset($_POST['_wp_http_referer']) ) {  
					$errors->add('captcha_error', $result);
					return $errors;
				} else {
 					wp_die( $result, __('Error','captcha-spam-blocker'), array( 'back_link' => true ) );   
				}
			}
  			return;
		}		
		
 		public function woo_lostpassword_verify( $errors='' ) {
 			if( !$this->showOnWooLostPass ) { 
				return; 
			}
 			$result = $this->validateCaptcha();
			if( $result != 'valid') {
				if( !is_wp_error( $errors ) ) {	
					$errors = new WP_Error();
				}	
 				if( isset($_POST['wc_reset_password']) && isset($_POST['_wp_http_referer']) ) {  
					$errors->add('captcha_error', $result);
					return $errors;
				} else {
 					wp_die( $result, __('Error','captcha-spam-blocker'), array( 'back_link' => true ) );  
				}
  			}
  			return;
		}		
  		
		public function process_comment($commentdata) {
  			if( !$this->comments_can_continue() ) {
				return $commentdata; 
			}
 			if( $commentdata['comment_type'] != '' && $commentdata['comment_type'] != 'comment' && $commentdata['comment_type'] != 'review' ) { 
				return $commentdata;
			}	
			/* Skip the CAPTCHA for comment replies from the admin menu */
			if( isset( $_REQUEST['action'] ) && 'replyto-comment' == $_REQUEST['action'] &&	(
					check_ajax_referer( 'replyto-comment', '_ajax_nonce', false ) ||
					check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment', false )
				)
			) {
				return $commentdata;
			}			
			$result = $this->validateCaptcha();
 			if( $result == 'valid' ) {
				$arr_stopword_fields = $this->comma2Arr($this->settings['stopword_fields_wp']);
				$arr_stopword_words = $this->comma2Arr($this->settings['stopword_list']);
 				if( count($arr_stopword_fields)>0 && count($arr_stopword_words)>0 ) {
 					$isBadComment = false;
					if( in_array('comment',$arr_stopword_fields) && !$isBadComment ) {
						$comment_content = strtolower($commentdata['comment_content']);
						foreach( $arr_stopword_words as $word ) {
							$word = strtolower($word);
							if( strpos($comment_content,$word) !== false ) {
								$isBadComment = true;
							}
						}
					}
					if( in_array('author',$arr_stopword_fields) && !$isBadComment ) {
						$comment_author = strtolower($commentdata['comment_author']);
						foreach( $arr_stopword_words as $word ) {
							$word = strtolower($word);
							if( strpos($comment_author,$word) !== false ) {
								$isBadComment = true;
							}
						}
					}
					if( in_array('email',$arr_stopword_fields) && !$isBadComment ) {
						$comment_author_email = strtolower($commentdata['comment_author_email']);
						foreach( $arr_stopword_words as $word ) {
							$word = strtolower($word);
							if( strpos($comment_author_email,$word) !== false ) {
								$isBadComment = true;
							}
						}
					}					
					if( $isBadComment ) {
						$txt_invalid = strlen($this->settings['translate_invalid_content'])>1 ? $this->settings['translate_invalid_content'] : 'Invalid Form Content';
						$result = esc_html($txt_invalid);
					}
 				}
			}			
			if( $result != 'valid' ) {
 				wp_die( $result, __('Error','captcha-spam-blocker'), array( 'back_link' => true ) );  
			}
 			return $commentdata;
		}	
		
		function captcha_comments_form_logged_in($comment_field) {
			if( $this->comments_can_continue() ) {
				$html = $this->captchaShow('showOnCommentsForm'); 
				return $comment_field."\n".$html;
			} else {
				return $comment_field;
			}
		}  	
	 
		public function action_admin_menu() {
			add_menu_page(
				'Captcha Spam Blocker', 					// Page title
				'Captcha Spam Blocker',				     	// Menu title
				'manage_options',        					// Capability
				$this->menuSlug, 							// Menu slug
				array($this, 'display_admin_settings_page'),// Function
				'dashicons-shield'       					// Dashicon
			);
		}
		
		public function site_admin_add_settings_link($links) {
			$settings_link = "<a href='admin.php?page=".$this->menuSlug."'>".__('Settings','captcha-spam-blocker')."</a>";
			array_unshift($links, $settings_link);  
			return $links;
		}
		
		public function site_load_textdomain() {
			load_plugin_textdomain('captcha-spam-blocker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		}
	  
		public function display_admin_settings_page() {
	  
			if( !current_user_can('manage_options')	) {
				wp_die(__('You do not have the necessary permissions to view this page.', 'captcha-spam-blocker')); 
			}
			
			$users_can_register = get_option('users_can_register') ? true : false;
			
			$cf7_form_names = $this->extract_all_cf7_form_names();
			
			$submit_save_wordpress_forms = isset($_POST['submit_save_wordpress_forms']) ? sanitize_text_field(wp_unslash($_POST['submit_save_wordpress_forms'])) : 'no'; 
			if( $submit_save_wordpress_forms=='yes' ) {
				
  				if( isset($_POST['wordpress_forms_field'] ) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['wordpress_forms_field'])) ,'wordpress_forms_nonce') ) {
  
					$stop_words_wp_fields = [];
					if( isset($_POST['field_wp_comment']) ) $stop_words_wp_fields[] = 'comment';
					if( isset($_POST['field_wp_author']) ) $stop_words_wp_fields[] = 'author';
					if( isset($_POST['field_wp_email']) ) $stop_words_wp_fields[] = 'email';
 					
					$stop_words_cf7_fields = [];
					if( count($cf7_form_names)>0 ) {
						foreach( $cf7_form_names as $cfn ) {
							if( isset($_POST['field_cf7'][$cfn]) ) {
								$stop_words_cf7_fields[] = sanitize_text_field($cfn);
							}
						}
					}
 					$stop_words_cf7_str = $this->array2CommaString($stop_words_cf7_fields);
					
					$post_captcha_stop_words = isset($_POST['captcha_stop_words']) ? sanitize_text_field(wp_unslash($_POST['captcha_stop_words'])) : '';
					$stop_words_list = $this->array2CommaString($this->comma2Arr($post_captcha_stop_words));
					
					$post_translate_captcha_no_robot = isset($_POST['translate_captcha_no_robot']) ? sanitize_text_field(wp_unslash($_POST['translate_captcha_no_robot'])) : '';
					$post_translate_captcha_code = isset($_POST['translate_captcha_code']) ? sanitize_text_field(wp_unslash($_POST['translate_captcha_code'])) : '';
					$post_translate_captcha_invalid = isset($_POST['translate_captcha_invalid']) ? sanitize_text_field(wp_unslash($_POST['translate_captcha_invalid'])) : '';
 					$post_translate_invalid_content = isset($_POST['translate_invalid_content']) ? sanitize_text_field(wp_unslash($_POST['translate_invalid_content'])) : '';
    
 					$post_arr = [];
					$post_arr['showOnLoginForm'] = isset($_POST['captcha_on_login_form']) ? 1 : 0;
					$post_arr['showOnCommentsForm'] = isset($_POST['captcha_on_comments_form']) ? 1 : 0;
					$post_arr['showOnRegisterForm'] = isset($_POST['captcha_on_register_form']) ? 1 : 0;
					$post_arr['showOnLostPassForm'] = isset($_POST['captcha_on_lost_pass_form']) ? 1 : 0;
					$post_arr['showOnWooLogin'] = isset($_POST['captcha_on_woo_login']) ? 1 : 0;
					$post_arr['showOnWooRegister'] = isset($_POST['captcha_on_woo_register']) ? 1 : 0;
					$post_arr['showOnWooLostPass'] = isset($_POST['captcha_on_woo_lost_pass']) ? 1 : 0;
					$post_arr['showOnWooRatingsAndReviews'] = isset($_POST['captcha_on_woo_reviews']) ? 1 : 0;
					$post_arr['showOnWooCheckout'] = isset($_POST['captcha_on_woo_checkout']) ? 1 : 0;
					$post_arr['showEnableHoneyPot'] = isset($_POST['captcha_honey_pot']) ? 1 : 0;
					$post_arr['showAddAJavaScriptLayer'] = isset($_POST['captcha_js_extra_layer']) ? 1 : 0;
					$post_arr['showDisableXMLRPC'] = isset($_POST['captcha_disable_xmlrpc']) ? 1 : 0;	
					$post_arr['stopword_fields_wp'] = count($stop_words_wp_fields)>0 ? implode(',',$stop_words_wp_fields) : '';
					$post_arr['stopword_fields_cf7'] = $stop_words_cf7_str;
					$post_arr['stopword_list'] = $stop_words_list;
					$post_arr['translate_captcha_no_robot'] = $post_translate_captcha_no_robot;
					$post_arr['translate_captcha_code'] = $post_translate_captcha_code;
					$post_arr['translate_captcha_invalid'] = $post_translate_captcha_invalid;
					$post_arr['translate_invalid_content'] = $post_translate_invalid_content;
					
 					update_option($this->nameWpOption, $post_arr);
					
					usleep(wp_rand(1000,2000)); 
 		
					$this->settings = $this->getMainSettings();
					$cf7_form_names = $this->extract_all_cf7_form_names();
		  
					$this->showOnLoginForm = (int)$this->settings['showOnLoginForm']==1 ? true : false;
					$this->showOnCommentsForm = (int)$this->settings['showOnCommentsForm']==1 ? true : false;
					$this->showOnRegisterForm = (int)$this->settings['showOnRegisterForm']==1 ? true : false;
					$this->showOnLostPassForm = (int)$this->settings['showOnLostPassForm']==1 ? true : false;
					$this->showOnWooLogin = (int)$this->settings['showOnWooLogin']==1 ? true : false;
					$this->showOnWooRegister = (int)$this->settings['showOnWooRegister']==1 ? true : false;
					$this->showOnWooLostPass = (int)$this->settings['showOnWooLostPass']==1 ? true : false;
					$this->showOnWooRatingsAndReviews = (int)$this->settings['showOnWooRatingsAndReviews']==1 ? true : false;
					$this->showOnWooCheckout = (int)$this->settings['showOnWooCheckout']==1 ? true : false;
					$this->showEnableHoneyPot = (int)$this->settings['showEnableHoneyPot']==1 ? true : false;
					$this->showAddAJavaScriptLayer = (int)$this->settings['showAddAJavaScriptLayer']==1 ? true : false;
					$this->showDisableXMLRPC = (int)$this->settings['showDisableXMLRPC']==1 ? true : false;		
 				}
			}
   
 			$is_checked_show_on_login = $this->showOnLoginForm ? 'checked' : '';
			$is_checked_show_on_comments = $this->showOnCommentsForm ? 'checked' : '';
			$is_checked_show_on_register = $this->showOnRegisterForm ? 'checked' : '';
			$is_checked_show_on_lost_pass = $this->showOnLostPassForm ? 'checked' : '';
			$is_checked_show_on_woo_login = $this->showOnWooLogin ? 'checked' : '';
			$is_checked_show_on_woo_register = $this->showOnWooRegister ? 'checked' : '';
			$is_checked_show_on_woo_lost_pass = $this->showOnWooLostPass ? 'checked' : '';
			$is_checked_show_on_woo_reviews = $this->showOnWooRatingsAndReviews ? 'checked' : '';
			$is_checked_show_on_woo_checkout = $this->showOnWooCheckout ? 'checked' : '';
 			$is_checked_show_enable_honey_pot = $this->showEnableHoneyPot ? 'checked' : '';
			$is_checked_show_add_javascript_layer = $this->showAddAJavaScriptLayer ? 'checked' : '';
			$is_checked_show_disable_xmlrpc = $this->showDisableXMLRPC ? 'checked' : '';			
 			
			echo "<div class='wrap'>";
			
			if( !$this->is_gd_active ) {
				echo "<div class='csb_botezatu_admin_no_gd'>PHP module GD not installed. The captcha module will not work</div>";
			}
			
			echo "<h2>Captcha Spam Blocker</h2>";
			echo "<hr>";
  			
			echo "<form action='' method='POST'>";
			
			wp_nonce_field('wordpress_forms_nonce', 'wordpress_forms_field');
			
			echo "<p class='submit'>" .
				 "<input type='hidden' name='submit_save_wordpress_forms' value='yes'>" .
				 "<input type='submit' value='" . esc_attr__('Save Settings', 'captcha-spam-blocker') . "' class='button button-primary' id='submit1' name='submit1'>" .
				 "</p>";
 			
			echo "<h3>" . esc_html__('Wordpress Forms', 'captcha-spam-blocker') . "</h3>" .
				 "<table class='form-table'>" .
				 "<tbody>" .
				 "<tr>" .
				 "<td>" .
				 "<div>" .
				 "<label for='captcha_on_login_form'>" .
				 "<input type='checkbox' id='captcha_on_login_form' name='captcha_on_login_form' " . esc_attr($is_checked_show_on_login) . ">" .  
				 esc_html__('Add CAPTCHA on wp-admin Login', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .
				 "<div class='csb_botezatu_admin_pt5'>" .
				 "<label for='captcha_on_register_form'>" .
				 "<input type='checkbox' id='captcha_on_register_form' name='captcha_on_register_form' " . esc_attr($is_checked_show_on_register) . ">" .  
				 esc_html__('Add CAPTCHA on wp-admin Register', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .        
				 "<div class='csb_botezatu_admin_pt5'>" .
				 "<label for='captcha_on_lost_pass_form'>" .
				 "<input type='checkbox' id='captcha_on_lost_pass_form' name='captcha_on_lost_pass_form' " . esc_attr($is_checked_show_on_lost_pass) . ">" .  
				 esc_html__('Add CAPTCHA on wp-admin Lost password', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .        
				 "<div class='csb_botezatu_admin_pt5'>" .
				 "<label for='captcha_on_comments_form'>" .
				 "<input type='checkbox' id='captcha_on_comments_form' name='captcha_on_comments_form' " . esc_attr($is_checked_show_on_comments) . ">" .  
				 esc_html__('Add CAPTCHA on Comments', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .
				 "</td>" .
				 "</tr>" .    
				 "</tbody>" .
				 "</table>";
 
 			
			echo "<hr>";
			
 			echo "<h3>".esc_html__('Woocommerce Forms', 'captcha-spam-blocker')."</h3>";
 			
			$woo_fail = !class_exists('WooCommerce')||!$this->is_woo_version_ok ? 'yes' : 'no';
			if( $woo_fail == 'no' ) {
				echo "<table class='form-table'>" .
					 "<tbody>" .
					 "<tr>" .
					 "<td>" .
					 "<div>" .
					 "<label for='captcha_on_woo_login'>" .
					 "<input type='checkbox' id='captcha_on_woo_login' name='captcha_on_woo_login' " . esc_attr($is_checked_show_on_woo_login) . ">" .
					 esc_html__('Add CAPTCHA on Woocommerce Login', 'captcha-spam-blocker') .
					 "</label>" .
					 "</div>" .

					 "<div class='csb_botezatu_admin_pt5'>" .
					 "<label for='captcha_on_woo_register'>" .
					 "<input type='checkbox' id='captcha_on_woo_register' name='captcha_on_woo_register' " . esc_attr($is_checked_show_on_woo_register) . ">" .
					 esc_html__('Add CAPTCHA on Woocommerce Register', 'captcha-spam-blocker') .
					 "</label>" .
					 "</div>" .

					 "<div class='csb_botezatu_admin_pt5'>" .
					 "<label for='captcha_on_woo_lost_pass'>" .
					 "<input type='checkbox' id='captcha_on_woo_lost_pass' name='captcha_on_woo_lost_pass' " . esc_attr($is_checked_show_on_woo_lost_pass) . ">" .
					 esc_html__('Add CAPTCHA on Woocommerce Lost password', 'captcha-spam-blocker') .
					 "</label>" .
					 "</div>" .

					 "<div class='csb_botezatu_admin_pt5'>" .
					 "<label for='captcha_on_woo_reviews'>" .
					 "<input type='checkbox' id='captcha_on_woo_reviews' name='captcha_on_woo_reviews' " . esc_attr($is_checked_show_on_woo_reviews) . ">" .
					 esc_html__('Add CAPTCHA on Woocommerce Ratings and Reviews', 'captcha-spam-blocker') .
					 "</label>" .
					 "</div>" .

					 "<div class='csb_botezatu_admin_pt5'>" .
					 "<label for='captcha_on_woo_checkout'>" .
					 "<input type='checkbox' id='captcha_on_woo_checkout' name='captcha_on_woo_checkout' " . esc_attr($is_checked_show_on_woo_checkout) . ">" .
					 esc_html__('Add CAPTCHA on Woocommerce Checkout', 'captcha-spam-blocker') .
					 "</label>" .
					 "</div>" .

					 "</td>" .
					 "</tr>" .
					 "</tbody>" .
					 "</table>";
			} else {
				if (!class_exists('WooCommerce')) {
					echo "<div class='csb_botezatu_admin_not_present'>WooCommerce is not installed.</div>";
				} elseif (!$this->is_woo_version_ok) {
					echo "<div class='csb_botezatu_admin_not_present'>The installed version of WooCommerce is older than 3.0, which was released in April 2017.</div>";
				} else {
					echo "<div>WooCommerce is installed but failed to load properly.</div>";
				}
			}
 			
			echo "<hr>";
	 
			echo "<h3>" . esc_html__('Contact Form 7 (CF7) Forms', 'captcha-spam-blocker') . "</h3>";
			$cf7_fail = !defined('WPCF7_VERSION') || !$this->is_cf7_version_ok ? 'yes' : 'no';
			if( $cf7_fail == 'no' ) {
				echo "<div><code>[csb_botezatu_captcha_spam_blocker]</code></div>" .
					 "<div class='csb_botezatu_admin_pt10 csb_botezatu_admin_pb10'>" .
					 "* Copy this code and insert it into your Contact Form 7 form to enhance security against spam and automated submissions. " .
					 "<a href='javascript:void(0);' onclick=\"csb_botezatu_captchaJsModule.toggleMoreImg('more_show_img');\">Implementation Example</a></div>";

				$img_how_to = plugins_url('assets/contact_form.jpg', __FILE__); 
				echo "<div style='display:none' id='more_show_img'><img src='" . esc_url($img_how_to) . "' alt='Contact Form 7 CAPTCHA Implementation'></div>";
			} else {
				if( !defined('WPCF7_VERSION') ) {
					echo "<div class='csb_botezatu_admin_not_present'>Contact Form 7 is not installed.</div>";
				} elseif (!$this->is_cf7_version_ok) {
					echo "<div class='csb_botezatu_admin_not_present'>The installed version of Contact Form 7 is older than 4.6, which was released in December 2016.</div>";
				} else {
					echo "<div>Contact Form 7 is installed but failed to load properly.</div>";
				}
			}
 			
			echo "<hr>";
			
			$arr_stop_words_wp = $this->comma2Arr($this->settings['stopword_fields_wp']);
			$checked_field_wp_comment = in_array('comment',$arr_stop_words_wp) ? ' checked' : '';
			$checked_field_wp_author = in_array('author',$arr_stop_words_wp) ? ' checked' : '';
			$checked_field_wp_email = in_array('email',$arr_stop_words_wp) ? ' checked' : '';		
			$img_wp_fields = plugins_url('assets/comment_author_email.jpg', __FILE__);
			echo "<h3>" . esc_html__('Miscellaneous ', 'captcha-spam-blocker') . "</h3>" .
				 "<div>" .
				 "<label for='captcha_honey_pot'>" .
				 "<input type='checkbox' id='captcha_honey_pot' name='captcha_honey_pot' " . esc_attr($is_checked_show_enable_honey_pot) . ">" .
				 esc_html__('Enable Honey Pot CAPTCHA (Recommended)', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .
				 "<div class='csb_botezatu_admin_pt5'>" .
				 "<label for='captcha_js_extra_layer'>" .
				 "<input type='checkbox' id='captcha_js_extra_layer' name='captcha_js_extra_layer' " . esc_attr($is_checked_show_add_javascript_layer) . ">" .
				 esc_html__('Add a JavaScript Layer for CAPTCHA Security (Recommended)', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .
				 "<div class='csb_botezatu_admin_pt10 csb_botezatu_admin_pb2'>- Enable Honey Pot option adds a trap field to catch bots</div>" .
				 "<div class='csb_botezatu_admin_pt2 csb_botezatu_admin_pb10'>- Add a JavaScript Layer for CAPTCHA Security option enhances security by implementing an input field updated via JavaScript.</div>" .
				 "<hr>" .

				 "<h3>" . esc_html__('Disable XMLRPC', 'captcha-spam-blocker') . "</h3>" .
				 "<div>" .
				 "<label for='captcha_disable_xmlrpc'>" .
				 "<input type='checkbox' id='captcha_disable_xmlrpc' name='captcha_disable_xmlrpc' " . esc_attr($is_checked_show_disable_xmlrpc) . ">" .
				 esc_html__('Disable XMLRPC (Recommended)', 'captcha-spam-blocker') .
				 "</label>" .
				 "</div>" .
				 "<div class='csb_botezatu_admin_pt10'>XMLRPC is a common vector for unauthorized login attempts on WordPress sites. Over half of all WordPress login attempts are made through the xmlrpc.php endpoint. For safety and security, it is recommended to disable XMLRPC.</div>" .
				 "<hr>" .

				 "<h3>" . esc_html__('Stop Words', 'captcha-spam-blocker') . "</h3>" .
				 "<div>" .
				 "<div style='padding-bottom:7px'><strong>Stop Words List (Separated by Commas)</strong> features a list of words, entered manually and separated by commas, used to control unwanted content. These words are essential for filtering purposes. If a comment or message contains a word from the list, it will not be sent. A warning message will be displayed: Invalid Form Content.</div>" .
				 "<div class='csb_botezatu_admin_inline_pr10'>Apply <b>StopWords</b> to the following fields in WordPress and WooCommerce:</div>" .
				 "<div class='csb_botezatu_admin_inline_pr15'><input type='checkbox' id='field_wp_comment' name='field_wp_comment' " . esc_attr($checked_field_wp_comment) . "><label for='field_wp_comment' class='csb_botezatu_admin_cur_pointer'>comment</label></div>" .
				 "<div class='csb_botezatu_admin_inline_pr15'><input type='checkbox' id='field_wp_author' name='field_wp_author' " . esc_attr($checked_field_wp_author) . "><label for='field_wp_author' class='csb_botezatu_admin_cur_pointer'>author</label></div>" .
				 "<div class='csb_botezatu_admin_inline_pr15'><input type='checkbox' id='field_wp_email' name='field_wp_email' " . esc_attr($checked_field_wp_email) . "><label for='field_wp_email' class='csb_botezatu_admin_cur_pointer'>email</label></div>" .
  				"<div style='display:none' id='info_wp_fields'><img src='".esc_url($img_wp_fields)."'></div>" .
				"</div>";
				
				if( count($cf7_form_names)>0 ) {
					$arr_stop_words_cf = $this->comma2Arr($this->settings['stopword_fields_cf7']);
					echo "<div class='csb_botezatu_admin_pt5'>";
					echo "<div class='csb_botezatu_admin_inline_pr10'>Apply <b>StopWords</b> to these CF7 fields:</div>";
					foreach( $cf7_form_names as $cname ) {
						$checked = in_array($cname,$arr_stop_words_cf) ? ' checked' : '';
						echo "<div class='csb_botezatu_admin_inline_pr15'><input type='checkbox' id='field_cf7_".esc_attr($cname)."' name='field_cf7[".esc_attr($cname)."]' ".$checked."><label for='field_cf7_".esc_attr($cname)."' class='csb_botezatu_admin_cur_pointer'>".esc_html($cname)."</label></div>"; 
					}
					echo "</div>";
				}				 
				 
				echo "<div class='csb_botezatu_admin_pt5'>" .
					 "<div><strong>Stop Words List (Separated by Commas) - case-insensitive:</strong></div>" . 
					 "<textarea rows='4' id='captcha_stop_words' name='captcha_stop_words' class='csb_botezatu_admin_w100perc'>".esc_textarea($this->comma2commaAndSpace($this->settings['stopword_list']))."</textarea>" .
					 "</div>" . 
					"<div style='padding-top:10px;padding-bottom:2px;'>Ex: https://, http://, www., bitcoin, crypto, viagra, pharmacy, millionaire, rolex, casino</div>";				 
 				echo "<hr>" .

				 "<h3>" . esc_html__('Translate ', 'captcha-spam-blocker') . "</h3>" .
				 "<table>" .
				 "<tr>" .
				 "<td>I'm Not a Robot:</td>" .
				 "<td><input type='text' name='translate_captcha_no_robot' autocomplete='off' value='" . esc_attr($this->settings['translate_captcha_no_robot']) . "'> Ex: Je ne suis pas un robot, Non sono un robot, No soy un robot, Ich bin kein Roboter ... </td>" .
				 "</tr>" .
				 "<tr>" .
				 "<td>Captcha Code:</td>" .
				 "<td><input type='text' name='translate_captcha_code' autocomplete='off' value='" . esc_attr($this->settings['translate_captcha_code']) . "'> Ex: Code de Sécurité, Código de Seguridad, Captcha-Code, Codice Captcha ... </td>" .
				 "</tr>" .
				 "<tr>" .
				 "<td>Invalid Captcha Code:</td>" .
				 "<td><input type='text' name='translate_captcha_invalid' autocomplete='off' value='" . esc_attr($this->settings['translate_captcha_invalid']) . "'> Ex: Captcha invalide, Captcha no válido, Ungültiges Captcha ... </td>" .
				 "</tr>" .
				 "<tr>" .
				 "<td>Invalid Form Content:</td>" .
				 "<td><input type='text' name='translate_invalid_content' autocomplete='off' value='" . esc_attr($this->settings['translate_invalid_content']) . "'> Ex: Contenu invalide, Contenido no válido, Ungültiger Inhalt ... </td>" .
				 "</tr>" .
				 "</table>" .
				 "<div class='csb_botezatu_admin_pt10 csb_botezatu_admin_pb2'>*If you do not enter any values, default texts will be used</div>";

 			echo "<hr>";
 			
			echo "<p class='submit'>" .
				 "<input type='hidden' name='submit_save_wordpress_forms' value='yes'>" .
				 "<input type='submit' value='" . esc_attr__('Save Settings', 'captcha-spam-blocker') . "' class='button button-primary' id='submit1' name='submit1'>" .
				 "</p>";

 			
			echo "</form>";
			
			echo "<hr>";
			
			echo "<h3>" . esc_html__('Captcha Preview', 'captcha-spam-blocker') . "</h3>" .
				 "<form action='' method='POST'>" .
				 $this->captchaShow('captcha_text') . 
				 "</form>";
 
			echo "</div>";			
  			
		}
 
		public function validateCaptcha() {
			if( !$this->is_gd_active ) {
				return 'valid';			
			}
			$captcha_no_rotbot = strlen($this->settings['translate_captcha_no_robot']) > 1 ? $this->settings['translate_captcha_no_robot'] : "I'm Not a Robot";
			$captcha_invalid_code = strlen($this->settings['translate_captcha_invalid']) > 1 ? $this->settings['translate_captcha_invalid'] : 'Invalid Captcha Code';
			$captcha_invalid_html = $captcha_no_rotbot.' - '.$captcha_invalid_code;
			$captcha_bad = [];
			$post_captcha_code = isset($_POST['csb_botezatu_captcha_code_input']) ? sanitize_text_field(trim(wp_unslash($_POST['csb_botezatu_captcha_code_input']))) : '';

			if (strlen($post_captcha_code) != 4) {
				$captcha_bad[] = 'len_not_4';
			}

			$session_captcha_code = isset($_SESSION['csb_botezatu_captcha_code_input']) ? $_SESSION['csb_botezatu_captcha_code_input'] : '';
			if ($post_captcha_code !== $session_captcha_code) {
				$captcha_bad[] = 'post_and_session_not_equal';
			}

			if ($this->showEnableHoneyPot) {
				foreach ($this->honeypotNames as $honey) {
					$post_val = isset($_POST[$honey]) ? sanitize_text_field(wp_unslash($_POST[$honey])) : '';
					if (!empty($post_val)) {
						$captcha_bad[] = 'honeypot_not_empty';
					}
				}
			}

			if ($this->showAddAJavaScriptLayer) {
				$post_wp_token_site_id_value = isset($_POST['csb_botezatu_wp_token_site_id_value']) ? (int)sanitize_text_field(wp_unslash($_POST['csb_botezatu_wp_token_site_id_value'])) : 0;
				$token_js_layer_is_ok = $post_wp_token_site_id_value>=11111 && $post_wp_token_site_id_value<=99999 ? true : false;
				if( !$token_js_layer_is_ok ) {
					$captcha_bad[] = 'js_layer';
				}
			}

			if( count($captcha_bad)>0 ) {
  				return esc_html($captcha_invalid_html);   
			}
			return 'valid';
		}

 
		public function captchaShow($type = '') {
 			$captcha_robot_str = strlen($this->settings['translate_captcha_no_robot']) > 1 ? esc_html($this->settings['translate_captcha_no_robot']) : "I'm Not a Robot";
 			$captcha_invalid_code = strlen($this->settings['translate_captcha_invalid']) > 1 ? esc_html($this->settings['translate_captcha_invalid']) : 'Invalid Captcha Code';
			$captcha_invalid_html = $captcha_robot_str.' - '.$captcha_invalid_code;
			$occurrences = isset($this->captcha_occurrences[$type]) ? $this->captcha_occurrences[$type]++ : 0;

			if ($occurrences > 0) {
				return '';
			}

			$captcha_error = isset($_REQUEST['captcha_error']) ? sanitize_text_field(wp_unslash($_REQUEST['captcha_error'])) : '';
			$is_invalid = $captcha_error == 'yes';

			$html = '';
			if( $is_invalid ) {
				$html .= "<div class='csb_botezatu_captcha_invalid_elem'>";
				$html .= esc_html($captcha_invalid_html);
				$html .= "</div>";
			}
 
			$html .= "<div class='csb_botezatu_robot_container' onclick='csb_botezatu_captchaJsModule.toggleCheckbox()'>";
			$html .= "<div class='csb_botezatu_robot_row'>";
			$html .= "<input type='checkbox' id='csb_botezatu_robot_checkbox' class='csb_botezatu_robot_checkbox csb_botezatu_hand' required>";
			$html .= "<label for='csb_botezatu_robot_checkbox' class='csb_botezatu_robot_label csb_botezatu_hand'>".$captcha_robot_str." ✓</label>";
			$html .= "</div>";
			$html .= "</div>";
 
			if ($this->showEnableHoneyPot || $this->showAddAJavaScriptLayer) {
				$html .= "<div style='display:none'>";
				if ($this->showEnableHoneyPot) {
					$randomKey = array_rand($this->honeypotNames);
					$randomElement = esc_attr($this->honeypotNames[$randomKey]);
					$html .= "<input type='text' name='" . $randomElement . "' id='" . $randomElement . "' value='' autocomplete='off'/>";
				}
				if ($this->showAddAJavaScriptLayer) {
					$html .= "<input type='text' class='csb_botezatu_wp_token_site_id_value' name='csb_botezatu_wp_token_site_id_value' value='" . esc_attr(wp_rand(111111, 999999)) . "' autocomplete='off'/>";
				}
				$html .= "</div>";
			}
			return $html;
		}
 
 
 
		public function getMainSettings() {
			$arr = [
				'showOnLoginForm' => 0,
				'showOnCommentsForm' => 0,
				'showOnRegisterForm' => 0,
				'showOnLostPassForm' => 0,
				'showOnWooLogin' => 0,
				'showOnWooRegister' => 0,
				'showOnWooLostPass' => 0,
				'showOnWooRatingsAndReviews' => 0,
				'showOnWooCheckout' => 0,
				'showEnableHoneyPot' => 0,
				'showAddAJavaScriptLayer' => 0,
				'showDisableXMLRPC' => 0,
				'stopword_fields_wp' => '',
				'stopword_fields_cf7' => '',
				'stopword_list' => '',
				'translate_captcha_no_robot' => '',
				'translate_captcha_code' => '',
				'translate_captcha_invalid' => '',
 				'translate_invalid_content'=>''
			];
  			$db_arr = get_option($this->nameWpOption);
  			if( empty($db_arr) ) {
				return $arr;
			} else {
  				foreach( $arr as $k=>$v ) {
					if( isset($db_arr[$k]) ) {
						$arr[$k] = $db_arr[$k];
					}
 				}
 			}
 			return $arr;
		}

 
 		public function pre($a) {
			echo "<pre>";
			print_r($a);
			echo "</pre>";
		}
 
		public function _is_login() { 
			return false !== stripos( wp_login_url(), $_SERVER['SCRIPT_NAME'] ); 
		}

		public function comments_can_continue() {
			if( $this->is_product_page ) {
 				return $this->showOnWooRatingsAndReviews ? true : false;
			} else {
 				return $this->showOnCommentsForm ? true : false;
			}
			return false;
		}
		
		public function extract_all_cf7_form_names() {
			$arr = [];
			if (!function_exists('wpcf7')) { return []; }
			$args = array( 'post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1 );
			$cf7_forms = get_posts($args);
			foreach ($cf7_forms as $form) {
				$form_id = $form->ID;
				$ContactForm = WPCF7_ContactForm::get_instance($form_id);
				if( method_exists($ContactForm, 'scan_form_tags') ) {
					$form_fields = $ContactForm->scan_form_tags();
					if( count($form_fields)>0 ) {
						foreach ($form_fields as $ff) {
							if( in_array($ff->basetype, ['text', 'email', 'textarea']) ) {
 								$arr[$ff->name] = $ff->name;
							}
						}
					}
				}
			}
			if( count($arr)>0 ) {
				$arr = array_values(array_unique($arr));
			}
			return $arr;
		}


		public function array2CommaString($array) {
 			if (!is_array($array) || empty($array)) { return '';  }
 			$validItems = [];
 			foreach ($array as $item) {
 				$trimmedItem = trim($item);
 				if (strlen($trimmedItem) >= 4) {
					$validItems[] = $trimmedItem;
				}
			}
  			return implode(',', $validItems);
		}
		
		public function comma2Arr($comma) {
  			$validItems = [];
			$parts = explode(',',$comma);
 			foreach( $parts as $pp ) {
				$pp = trim($pp);
				if( strlen($pp)>=4 ) {
					$validItems[] = sanitize_text_field($pp);
				}
			}
    		return $validItems;
		}		
		
		public function comma2commaAndSpace($comma) {
  			$validItems = [];
			$parts = explode(',',$comma);
 			foreach( $parts as $pp ) {
				$pp = trim($pp);
				if( strlen($pp)>=4 ) {
					$validItems[] = sanitize_text_field($pp);
				}
			}
    		return implode(', ',$validItems);
		}	

		public function is_xmlrpc_or_restapi_request_type() {
  			return defined('XMLRPC_REQUEST') && XMLRPC_REQUEST || defined('REST_REQUEST') && REST_REQUEST ? true : false;
		}		
  		
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'captcha-spam-blocker' ), '1.0.0' );  
		}
		
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'captcha-spam-blocker' ), '1.0.0' );  
		}
		
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}			
 
	}
	
}


function CSB_BOTEZATU_CaptchaSpamBlocker_start() {
	return CSB_BOTEZATU_CaptchaSpamBlocker::instance();
}

CSB_BOTEZATU_CaptchaSpamBlocker_start();

