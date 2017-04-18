<?php 
/**
 * Admin Main Class
 *
 * @param void
 *
 * @return void
 */
if (!class_exists('ThorCPTempAdmin')) {
	
	class ThorCPTempAdmin {

		public function __construct() {
			// Activation and deactivation hook.
    		register_activation_hook(WP_PLUGIN_DIR . '/wp-thor-custom-post-template/wp-thor-custom-post-template.php',  array($this, 'thor_custom_post_template_activate'));
			register_deactivation_hook( WP_PLUGIN_DIR . '/wp-thor-custom-post-template/wp-thor-custom-post-template.php',  array($this, 'thor_custom_post_template_deactivate' ));

			if ( is_admin() ) {
				// Admin Menu
				add_action('admin_menu', array($this, 'thor_custom_post_template_admin_menu'));
			}

			add_action('admin_init', array($this, 'thor_custom_post_template_settings_init'));

			add_action('wpmu_new_blog',  array($this, 'thor_on_new_blog', 10, 6)); 		
			add_action('activate_blog',  array($this, 'thor_on_new_blog', 10, 6));
			
			add_action('admin_enqueue_scripts', array($this, 'thor_custom_post_template_head') );
			
			add_action('plugins_loaded', array($this, 'thor_custom_post_template_load_textdomain'));

			add_action('add_meta_boxes',array($this, 'thor_cbt_post_custom_template'));
			add_action('save_post',array($this, 'thor_cbt_save_custom_post_template',10,2));
			add_filter('single_template',array($this, 'thor_cbt_get_custom_post_template_for_template_loader'));

			add_filter('admin_footer_text', array($this, 'ctp_admin_footer'));
		}

		/* ***************************** PLUGIN (DE-)ACTIVATION *************************** */

		/**
		 * Run single site / network-wide activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being activated network-wide.
		 */
		function thor_custom_post_template_activate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorCPTempAdmin::_thor_custom_post_template_activate();
			}
			else {
				/* Multi-site network activation - activate the plugin for all blogs */
				ThorCPTempAdmin::thor_custom_post_template_network_activate_deactivate( true );
			}
		}

		/**
		 * Run single site / network-wide de-activation of the plugin.
		 *
		 * @param bool $networkwide Whether the plugin is being de-activated network-wide.
		 */
		function thor_custom_post_template_deactivate() {

		    $networkwide = ($_SERVER['SCRIPT_NAME'] == '/wp-admin/network/plugins.php')?true:false;

			if ( ! is_multisite() || ! $networkwide ) {
				ThorCPTempAdmin::_thor_custom_post_template_deactivate();
			}
			else {
				/* Multi-site network activation - de-activate the plugin for all blogs */
				ThorCPTempAdmin::ctp_network_activate_deactivate( false );
			}
		}

		/**
		 * Run network-wide (de-)activation of the plugin
		 *
		 * @param bool $activate True for plugin activation, false for de-activation.
		 */
		function thor_custom_post_template_network_activate_deactivate( $activate = true ) {
			global $wpdb;

			$network_blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid ) );

			if ( is_array( $network_blogs ) && $network_blogs !== array() ) {
				foreach ( $network_blogs as $blog_id ) {
					switch_to_blog( $blog_id );

					if ( $activate === true ) {
						ThorCPTempAdmin::_thor_custom_post_template_activate();
					}
					else {
						ThorCPTempAdmin::_thor_custom_post_template_deactivate();
					}

					restore_current_blog();
				}
			}
		}

		/**
		 * On deactivation
		 */
		function _thor_custom_post_template_deactivate() {

			global $wpdb;

		    if (function_exists('is_multisite') && is_multisite()) {
		        // check if it is a network activation - if so, run the activation function 
		        // for each blog id
		        if ($networkwide) {
		            $old_blog = $wpdb->blogid;
		            // Get all blog ids
		            $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
		            foreach ($blogids as $blog_id) {
		                switch_to_blog($blog_id);
		            }
		            switch_to_blog($old_blog);
		            return;
		        }   
		    }

			delete_option ( 'thor_cbt');
			do_action( 'thor_custom_post_template_deactivate' );
		}

		/**
		 * Run activation routine on creation / activation of a multisite blog if WP THOR FCM is activated network-wide.
		 *
		 * Will only be called by multisite actions.
		 *
		 * @internal Unfortunately will fail if the plugin is in the must-use directory
		 * @see      https://core.trac.wordpress.org/ticket/24205
		 *
		 * @param int $blog_id Blog ID.
		 */
		function thor_on_new_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

			global $wpdb;

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
		 
			if (is_plugin_active_for_network('wp-thor-custom-post-template/wp-thor-custom-post-template.php')) {
				$old_blog = $wpdb->blogid;
				switch_to_blog($blog_id);
				ThorCPTempAdmin::ctp_activate();
				switch_to_blog($old_blog);
			}
		}

		/**
		 * Runs on activation of the plugin.
		 *
		 * @param void
		 *
		 * @return void
		 */
		function _thor_custom_post_template_activate() {
		    // Create new table if necessary
			add_option ( 'thor_cbt','post');
			// Activate
			do_action( 'thor_custom_post_template_activate' );
		}

		/**
		 * Set The Header
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_custom_post_template_head(){

				wp_enqueue_style( 'thor-admin-style', THORCPTEMP_PLUGIN_URL . '/app/views/css/style.css' );
				wp_enqueue_style( 'thor-font-awesome', THORCPTEMP_PLUGIN_URL . '/app/views/css/font-awesome.css' );
				wp_enqueue_style( 'thor-bootstrap-style', THORCPTEMP_PLUGIN_URL . '/app/views/css/bootstrap.css' );
				wp_enqueue_style( 'thor-bootstrap-theme-style', THORCPTEMP_PLUGIN_URL . '/app/views/css/bootstrap-theme.css' );

				wp_enqueue_script( 'thor-bootstrap-js', THORCPTEMP_PLUGIN_URL . '/app/views/js/bootstrap.js' );;

				wp_localize_script( 'thor-admin-js', 'thor_base_url', get_site_url() );
				wp_localize_script( 'thor-admin-js', 'thor_admin_url', get_admin_url() . 'admin.php?page=thor_custom_post_template_admin' );
		}

		/**
		 * Add Admin Menues
		 *
		 * @param void
		 *
		 * @return void
		 */	
		public function thor_custom_post_template_admin_menu(){
			add_menu_page ( 'WP Thor CPT', 'WP Thor CPT', 'manage_options', 'thor_custom_post_template_admin', array($this, 'thor_custom_post_template_admin'),'fa-uploads-thor', 10 );
		}
		
		/**
		 * Set Admin Menues
		 *
		 * @param void
		 *
		 * @return void
		 */			
		public function thor_custom_post_template_admin(){
			//current tab
			if (isset($_GET['tab'])){
				$tab = $_GET['tab'];
			} else {
				$tab = 'general_settings';
			}
			
			//url admin
			$url = get_admin_url() . 'admin.php?page=thor_custom_post_template_admin';

			//all tabs available
			$tabs_arr = array('general_settings' => 'Settings',
								'support' => 'Support',
								'hireus' => 'Services',
								'pluginsthemes'	=> 'Plugins/Themes'				
							  );
			
			//include dashboard header
			require_once THORCPTEMP_PLUGIN_PATH . '/app/views/dashboard-head.php';
			
			switch ($tab){
				case 'general_settings':
					require_once THORCPTEMP_PLUGIN_PATH . '/app/views/settings.php';
				break;
				case 'support':
					require_once THORCPTEMP_PLUGIN_PATH . '/app/views/support.php';
				break;
				case 'hireus':
					require_once THORCPTEMP_PLUGIN_PATH . '/app/views/hireus.php';
				break;
				case 'pluginsthemes':
					require_once THORCPTEMP_PLUGIN_PATH . '/app/views/pluginsthemes.php';
				break;
			}
		}


		/**
		 * Set The Settings Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_custom_post_template_settings_init(  ) {


//			register_setting('thor-cbt-settings', 'thor_custom_post_template_settings' );

//			add_settings_section( 'thor_custom_post_template_settings_section', '', array( $this, 'thor_custom_post_template_settings_section_callback' ), 'thor-cbt-settings', 'section_general' );
			
//			add_settings_field( 'thor_custom_post_template_checkbox_spi_render',  __('Google API Key', 'thor_custom_post_template'), array( $this, 'thor_custom_post_template_api_key_render' ), 'thor-cbt-settings', 'thor_custom_post_template_settings_section' );

		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_custom_post_template_checkbox_spi_render() { 
			$options = get_option('thor_custom_post_template_settings');
			if(!isset($options['thor_custom_post_template_checkbox_spi'])) { 
				$setting = 0; 
			}else {
				$setting = $options['thor_custom_post_template_checkbox_spi'];
			}
			?>
			<input type='checkbox' name='thor_custom_post_template_settings[thor_custom_post_template_checkbox_spi]' value="1" <?php checked($setting, 1); ?> />
			<?php
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_custom_post_template_checkbox_abl_render() { 
			$options = get_option('thor_custom_post_template_settings');
			if(!isset($options['thor_custom_post_template_checkbox_abl'])) { 
				$setting = 0; 
			}else {
				$setting = $options['thor_custom_post_template_checkbox_abl'];
			}
			?>
			<input type='checkbox' name='thor_custom_post_template_settings[thor_custom_post_template_checkbox_abl]' value="1" <?php checked($setting, 1); ?> />
			<?php
		}

		/**
		 * Set The Parameters
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_custom_post_template_checkbox_debug_render() { 
			$options = get_option('thor_custom_post_template_settings');
			if(!isset($options['thor_custom_post_template_checkbox_debug'])) { 
				$setting = 0; 
			}else {
				$setting = $options['thor_custom_post_template_checkbox_debug'];
			}
			?>
			<input type='checkbox' name='thor_custom_post_template_settings[thor_custom_post_template_checkbox_debug]' value="1" <?php checked($setting, 1); ?> />
			<?php
		}


		/**
		 * Settings Section Callbck
		 *
		 * @param void
		 *
		 * @return void
		 */	
		function thor_custom_post_template_settings_section_callback() { 
			echo __('Required settings for the plugin and the App.', 'thor_custom_post_template');
		}

		/**
		 * Register ToolBar
		 *
		 * @param void
		 *
		 * @return void
		 */
		function thor_custom_post_template_toolbar() {
			$options = get_option('thor_custom_post_template_settings');
			if(isset($options['thor_custom_post_template_checkbox_abl']) && $options['thor_custom_post_template_checkbox_abl'] != false && current_user_can('edit_posts')) {
				global $wp_admin_bar;
				$page = get_site_url().'/wp-admin/admin.php?page=thor_custom_post_template_admin';
				$args = array(
					'id'     => 'thor_custom_post_template',
					'title'  => '<img class="dashicons dashicons-cloud">FCM</img>', 'thor_custom_post_template',
					'href'   =>  "$page" );
				$wp_admin_bar->add_menu($args);
			}
		}

		/**
		 * load the translations
		 *
		 * @param void
		 *
		 * @return void
		 */
		function thor_custom_post_template_load_textdomain() {
			load_plugin_textdomain('thor_custom_post_template', false, basename(dirname( __FILE__ )).'/lang'); 
		}

		/**
		 * Admin Footer.
		 *
		 * @param void
		 *
		 * @return void
		 */
		function ctp_admin_footer() {
			global $pagenow;
			
			if ($pagenow == 'admin.php') {
				$page = $_GET['page'];
				switch($page) {
					case 'thor_custom_post_template_admin':
						echo "<div class=\"social-links alignleft\"><i>Created by <a href='http://thunderbeardesign.com'>ThunderBear Design</a></i>				
						<a href=\"http://twitter.com/tbearmarketing\" class=\"twitter\" target=\"_blank\"><span
						class=\"dashicons dashicons-twitter\"></span></a>
						<a href=\"fb.me/thunderbeardesign\" class=\"facebook\"
				   target=\"_blank\"><span class=\"dashicons dashicons-facebook\"></span></a>
						<a href=\"https://thunderbeardesign.com/feed/\" class=\"rss\" target=\"_blank\"><span
						class=\"dashicons dashicons-rss\"></span></a>
						</div>";
						break;
					default:
						return;
				}
			}
		}

		/**
		 * Write debug info as a text file and download it.
		 *
		 * @param void
		 *
		 * @return void
		 */
		public function download_debuginfo_as_text() {

			global $wpdb, $wp_version;
			$debug_info = array();
			$debug_info['Home URL'] = esc_url( home_url() );
			$debug_info['Site URL'] = esc_url( site_url() );
			$debug_info['PHP'] = esc_html( PHP_VERSION );
			$debug_info['MYSQL'] = esc_html( $wpdb->db_version() );
			$debug_info['WordPress'] = esc_html( $wp_version );
			$debug_info['OS'] = esc_html( PHP_OS );
			if ( extension_loaded( 'imagick' ) ) {
				$imagickobj = new Imagick();
				$imagick    = $message = preg_replace( " #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i", "'<a href=\"$1\" target=\"_blank\">$3</a>$4'", $imagickobj->getversion() );
			} else {
				$imagick['versionString'] = 'Not Installed';
			}
			$debug_info['Imagick'] = $imagick['versionString'];
			if ( extension_loaded( 'gd' ) ) {
				$gd = gd_info();
			} else {
				$gd['GD Version'] = 'Not Installed';
			}
			$debug_info['GD'] = esc_html( $gd['GD Version'] );
			$debug_info['[php.ini] post_max_size'] = esc_html( ini_get( 'post_max_size' ) );
			$debug_info['[php.ini] upload_max_filesize'] = esc_html( ini_get( 'upload_max_filesize' ) );
			$debug_info['[php.ini] memory_limit'] = esc_html( ini_get( 'memory_limit' ) );
			$active_theme = wp_get_theme();
			$debug_info['Theme Name'] = esc_html( $active_theme->Name );
			$debug_info['Theme Version'] = esc_html( $active_theme->Version );
			$debug_info['Author URL'] = esc_url( $active_theme->{'Author URI'} );

			$ctp_options = get_option( 'thor_custom_post_template_settings' );
			$ctp_options = array_merge( $debug_info, $ctp_options );
			if( ! empty( $ctp_options ) ) {

				$url = wp_nonce_url('admin.php?page=thor_custom_post_template_admin&tab=support&subtab=debuginfo','thor-debuginfo');
				if (false === ($creds = request_filesystem_credentials($url, '', false, false, null)) ) {
					return true;
				}
				
				if (!WP_Filesystem($creds)) {
					request_filesystem_credentials($url, '', true, false, null);
					return true;
				}
				
				global $wp_filesystem;
				$contentdir = trailingslashit($wp_filesystem->wp_content_dir());
				
				$in = '==============================================================================' . PHP_EOL;
				$in .= '================================== Debug Info ================================' . PHP_EOL;
				$in .=  '==============================================================================' . PHP_EOL . PHP_EOL . PHP_EOL;

				foreach ( $ctp_options as $option => $value ) {
					$in .= ucwords( str_replace( '_', ' ', $option ) ) . str_repeat( ' ', 50 - strlen($option) ) . wp_strip_all_tags( $value ) . PHP_EOL;
				}

				mb_convert_encoding($in, "ISO-8859-1", "UTF-8");
				
				if(!$wp_filesystem->put_contents($contentdir.'ctp_debuginfo.txt', $in, FS_CHMOD_FILE)) {
					echo 'Failed saving file';
				}
				return content_url()."/ctp_debuginfo.txt"; 
			}
		}

		/**
		 * Show debug_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return void
		 */
		public function debug_info() {
			global $wpdb, $wp_version;
			$debug_info               = array();
			$debug_info['Home URL']   = esc_url( home_url() );
			$debug_info['Site URL']   = esc_url( site_url() );
			$debug_info['PHP']        = esc_html( PHP_VERSION );
			$debug_info['MYSQL']      = esc_html( $wpdb->db_version() );
			$debug_info['WordPress']  = esc_html( $wp_version );
			$debug_info['OS']         = esc_html( PHP_OS );
			if ( extension_loaded( 'imagick' ) ) {
				$imagickobj = new Imagick();
				$imagick    = $message = preg_replace( " #((http|https|ftp)://(\S*?\.\S*?))(\s|\;|\)|\]|\[|\{|\}|,|\"|'|:|\<|$|\.\s)#i", "'<a href=\"$1\" target=\"_blank\">$3</a>$4'", $imagickobj->getversion() );
			} else {
				$imagick['versionString'] = 'Not Installed';
			}
			$debug_info['Imagick'] = $imagick['versionString'];
			if ( extension_loaded( 'gd' ) ) {
				$gd = gd_info();
			} else {
				$gd['GD Version'] = 'Not Installed';
			}
			$debug_info['GD']                            = esc_html( $gd['GD Version'] );
			$debug_info['[php.ini] post_max_size']       = esc_html( ini_get( 'post_max_size' ) );
			$debug_info['[php.ini] upload_max_filesize'] = esc_html( ini_get( 'upload_max_filesize' ) );
			$debug_info['[php.ini] memory_limit']        = esc_html( ini_get( 'memory_limit' ) );
			$debug_info['Installed Plugins']             = $this->get_plugin_info();
			$active_theme                                = wp_get_theme();
			$debug_info['Theme Name']                    = esc_html( $active_theme->Name );
			$debug_info['Theme Version']                 = esc_html( $active_theme->Version );
			$debug_info['Author URL']                    = esc_url( $active_theme->{'Author URI'} );

			/* get all Settings */
			$ctp_options = get_option( 'thor_custom_post_template_settings' );
			if ( is_array( $ctp_options ) ) {
				foreach ( $ctp_options as $option => $value ) {
					$debug_info[ ucwords( str_replace( '_', ' ', $option ) ) ] = $value;
				}
			}

			$this->debug_info = $debug_info;
		}


		/**
		 * Get plugin_info.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return array $rtmedia_plugins
		 */
		public function get_plugin_info() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			$ctp_plugins = array();
			foreach ( $active_plugins as $plugin ) {
				$plugin_data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
				$version_string = '';
				if ( ! empty( $plugin_data['Name'] ) ) {
					$ctp_plugins[] = esc_html( $plugin_data['Name'] ) . ' ' . esc_html__( 'by', 'fcm' ) . ' ' . $plugin_data['Author'] . ' ' . esc_html__( 'version', 'fcm' ) . ' ' . $plugin_data['Version'] . $version_string;
				}
			}
			if ( 0 === count( $ctp_plugins ) ) {
				return false;
			} else {
				return implode( ', <br/>', $ctp_plugins );
			}
		}

		/**
		 * Custom Post Type
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return array $rtmedia_plugins
		 */
		function thor_cbt_post_custom_template($postType) {
			
			if(get_option('thor_cbt') == ''){ //get option value
				$postType_title = 'post';
				$postType_arr[] = $postType_title;
			}else{
				$postType_title = get_option('thor_cbt');
				$postType_arr = explode(',',$postType_title);
			}
			if(in_array($postType, $postType_arr)){
				add_meta_box(
						'postparentdiv',
						__('Thor Post Template'),
						array($this, 'thor_cbt_meta_box'),
						$postType,
						'side', 
						'core'
				);
			}
		}

		/**
		 * Meta Box.
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return 
		 */
		function thor_cbt_meta_box($post) {
			if ( $post->post_type != 'page' && 0 != count( ThorCPTempAdmin::thor_cbt_get_post_custom_templates() ) ) {
				$template = get_post_meta($post->ID,'_post_template',true);
			?>
				<label class="screen-reader-text" for="post_template"><?php _e('Post Template') ?></label>
				<select name="post_template" id="post_template">
					<option value='default'><?php _e('Default Template'); ?></option>
					<?php ThorCPTempAdmin::thor_cbt_dropdown($template); ?>
				</select>
				<p><i><?php _e( 'Some themes have custom templates you can use for single posts template selecting from dropdown.'); ?></i></p>
			<?php
			}
		}

		/**
		 * Get Post Custom Templates
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return 
		 */
		function thor_cbt_get_post_custom_templates() {
		  if(function_exists('wp_get_themes')){
				$themes = wp_get_themes();
			} else {
				$themes = get_themes();
			}

			$theme = get_option( 'template' );
		  	$templates = $themes[$theme]['Template Files'];
		  	$post_templates = array();

		  	if ( is_array( $templates ) ) {
		    	$base = array( trailingslashit(get_template_directory()), trailingslashit(get_stylesheet_directory()) );

		    foreach ( $templates as $template ) {
		      $basename = str_replace($base, '', $template);
		      if ($basename != 'functions.php') {
		        // don't allow template files in subdirectories
		        if ( false !== strpos($basename, '/') )
		          continue;

		        $template_data = implode( '', file( $template ));

		        $name = '';
		        if ( preg_match( '|Post Template:(.*)$|mi', $template_data, $name ) )
		          $name = _cleanup_header_comment($name[1]);

		        if ( !empty( $name ) ) {
		          $post_templates[trim( $name )] = $basename;
		        }
		      }
		    }
		  }
		  return $post_templates;
		}

		/**
		 * Dropdwon
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return 
		 */
		function thor_cbt_dropdown( $default = '' ) {
		  $templates = ThorCPTempAdmin::thor_cbt_get_post_custom_templates();
		  ksort( $templates );
		  foreach (array_keys( $templates ) as $template )
		    : if ( $default == $templates[$template] )
		      $selected = " selected='selected'";
		    else
		      $selected = '';
		  echo "\n\t<option value='".$templates[$template]."' $selected>$template</option>";
		  endforeach;
		}

		/**
		 * Save Custom Post Template
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return 
		 */
		function thor_cbt_save_custom_post_template($post_id,$post) {
		  if ($post->post_type !='page' && !empty($_POST['post_template']))
		    update_post_meta($post->ID,'_post_template',$_POST['post_template']);
		}

		/**
		 * Get Custom Post Template for Template Loader
		 *
		 * @access public
		 *
		 * @param  void
		 *
		 * @return array $rtmedia_plugins
		 */
		function thor_cbt_get_custom_post_template_for_template_loader($template) {
		  global $wp_query;
		  $post = $wp_query->get_queried_object();
		  if ($post) {
		    $post_template = get_post_meta($post->ID,'_post_template',true);

		    if (!empty($post_template) && $post_template!='default')
		      $template = get_stylesheet_directory() . "/{$post_template}";
		  }
		  
		  return $template;
		}
  	} //end of class
} //end of if class exists