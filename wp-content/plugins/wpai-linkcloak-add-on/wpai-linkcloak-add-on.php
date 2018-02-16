<?php
/*
Plugin Name: WP All Import - Link Cloaking Add-on
Plugin URI: http://www.wpallimport.com/
Description: Cloak all links present during import.
Version: 1.1.0
Author: Soflyy
*/

/**
 * Plugin root dir with forward slashes as directory separator regardless of actuall DIRECTORY_SEPARATOR value
 * @var string
 */
define('PMLCA_ROOT_DIR', str_replace('\\', '/', dirname(__FILE__)));
/**
 * Plugin root url for referencing static content
 * @var string
 */
define('PMLCA_ROOT_URL', rtrim(plugin_dir_url(__FILE__), '/'));
/**
 * Plugin prefix for making names unique (be aware that this variable is used in conjuction with naming convention,
 * i.e. in order to change it one must not only modify this constant but also rename all constants, classes and functions which
 * names composed using this prefix)
 * @var string
 */
define('PMLCA_PREFIX', 'pmlca_');

define('PMLCA_VERSION', '1.1.0');

final class WPAI_Link_Cloak {

	/**
	 * Singletone instance
	 * @var WPAI_Link_Cloak
	 */
	protected static $instance;
	/**
	 * Plugin root dir
	 * @var string
	 */
	const ROOT_DIR = PMLCA_ROOT_DIR;
	/**
	 * Plugin root URL
	 * @var string
	 */
	const ROOT_URL = PMLCA_ROOT_URL;
	/**
	 * Prefix used for names of shortcodes, action handlers, filter functions etc.
	 * @var string
	 */
	const PREFIX = PMLCA_PREFIX;
	/**
	 * Plugin file path
	 * @var string
	 */
	const FILE = __FILE__;	

	public $input;

	/**
	 * Return singletone instance
	 * @return PMLCA_Plugin
	 */
	static public function getInstance() {
		if (self::$instance == NULL) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function __construct(){				

		if (self::$instance == NULL) {

			register_activation_hook(self::FILE, array($this, 'activation'));

			// Register own wpallimport addon
			add_filter('pmxi_addons', array( &$this, 'register_addon' ), 10, 1);				
			/*
			* Function to render addon's view
			*/
			add_action('pmxi_extend_options_featured', array( &$this, 'view'), 10, 1);

			add_action('admin_notices', array( &$this, 'admin_notices'));
			add_action('wp_loaded', array( &$this, 'wp_loaded'));

			add_filter('pmxi_the_content', array( &$this, 'pmxi_the_content'), 10, 2);
			add_filter('pmxi_the_excerpt', array( &$this, 'pmxi_the_content'), 10, 2);
			add_filter('pmxi_custom_field', array( &$this, 'pmxi_custom_field'), 10, 5);
			add_filter('pmwi_cloak_affiliate_url', array( &$this, 'pmwi_cloak_affiliate_url'), 10, 2);
            add_filter('pmxi_save_options', array( &$this, 'pmwi_pmxi_save_options'), 10, 1);
		}			

	}

	public function wp_loaded(){

		// detect if cloaked link is requested and execute redirect.php if so
		if ( ! is_admin() and (preg_match('%^' . preg_quote($this->site_url_no_domain(""), '%') . '/([\w-]+)(/([^/?]+))?/?($|\?)%', $_SERVER['REQUEST_URI'], $mtch) or preg_match('%^' . preg_quote($this->site_url_no_domain(), '%') . '/?\?(.*?&)?cloaked=([\w-]+)(&|$)%', $_SERVER['REQUEST_URI'], $mtch_alt) or preg_match('%^' . preg_quote($this->site_url_no_domain(), '%') . '/?\?(.*?&)?link=([\w-]+)(&|$)%', $_SERVER['REQUEST_URI'], $mtch_alt))) {
				
			if ($mtch) {
				$slug = $mtch[1];
				$_GET['subid'] = $mtch[3];
			} else {
				$slug = $mtch_alt[2];
			}

			$table_prefix = self::getInstance()->getTablePrefix();

			global $wpdb;			

			$link = $wpdb->get_row("SELECT * FROM {$table_prefix}links WHERE slug = '$slug'", ARRAY_A);

			if ( empty($link) && !empty($mtch[0])){
				$slug = ltrim($mtch[0], "/");
				$link = $wpdb->get_row("SELECT * FROM {$table_prefix}links WHERE slug = '$slug'", ARRAY_A);
			}

			if ( $link != null ){				
				
				$http_response_code = apply_filters('wpai_link_cloak_http_response_code', 301, $link);

				if ( ! headers_sent()) {
					header('Cache-Control: no-cache');
			  		header('Pragma: no-cache');
				}
				
				header("Location: " . $link['afflink'], true, $http_response_code);
				die;
			}			

		}

	}

	/**
	 * Same as site_url() but return path without domain name
	 * @return string
	 */
	function site_url_no_domain($path = '') {		
		return preg_replace('%^http://[^/]*%', '', site_url($path, 'http'));
	}

	/**
	 * Check whether plugin is activated as network one
	 * @return bool
	 */
	public function isNetwork() {
		if ( !is_multisite() )
		return false;

		$plugins = get_site_option('active_sitewide_plugins');
		if (isset($plugins[plugin_basename(self::FILE)]))
			return true;

		return false;
	}

	/**
	 * Return prefix for plugin database tables
	 * @return string
	 */
	public function getTablePrefix() {
		global $wpdb;
		return ($this->isNetwork() ? $wpdb->base_prefix : $wpdb->prefix) . self::PREFIX;
	}

	/**
	 * Plugin activation logic
	 */
	public function activation(){

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require self::ROOT_DIR . '/schema.php';
		global $wpdb;

		if (function_exists('is_multisite') && is_multisite()) {
	        // check if it is a network activation - if so, run the activation function for each blog id	        
	        if (isset($_GET['networkwide']) && ($_GET['networkwide'] == 1)) {
	            $old_blog = $wpdb->blogid;
	            // Get all blog ids
	            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	            foreach ($blogids as $blog_id) {
	                switch_to_blog($blog_id);
	                require self::ROOT_DIR . '/schema.php';
	                dbDelta($plugin_queries);		                
	            }
	            switch_to_blog($old_blog);
	            return;	         
	        }	         
	    }

		dbDelta($plugin_queries);

	}

	public function admin_notices(){

		if ( ! class_exists( 'PMXI_Plugin' ) ) {
			?>
			<div class="error"><p>
				<?php printf(
						__('<b>%s Plugin</b>: WP All Import must be installed. Free edition of WP All Import at <a href="http://wordpress.org/plugins/wp-all-import/" target="_blank">http://wordpress.org/plugins/wp-all-import/</a> and the paid edition at <a href="http://www.wpallimport.com/">http://www.wpallimport.com/</a>', 'PMLI_Plugin'),
						self::getInstance()->getName()
				) ?>
			</p></div>
			<?php
					
			deactivate_plugins( self::ROOT_DIR . '/wpai-linkcloak-add-on.php');
			
		}

		if ( class_exists( 'PMXI_Plugin' ) and ( version_compare(PMXI_VERSION, '4.1.7') < 0 and PMXI_EDITION == 'paid' or version_compare(PMXI_VERSION, '3.2.9') <= 0 and PMXI_EDITION == 'free') ) {
			?>
			<div class="error"><p>
				<?php printf(
						__('<b>%s Plugin</b>: Please update your WP All Import to the latest version', 'pmli_plugin'),
						self::getInstance()->getName()
				) ?>
			</p></div>
			<?php
			
			deactivate_plugins( self::ROOT_DIR . '/wpai-linkcloak-add-on.php');
		}	

	}

	public static function getEddName(){
		return 'Link Cloaking Add-On';
	}

	public function getName(){
		return __('WP All Import - Link Cloaking Add-on', 'pmxi_plugin');
	}

	public function register_addon( $addons ){

		// link_cloaking_addon - own addon prefix (should be unique)
		if ( empty($addons['WPAI_Link_Cloak']) ) $addons['WPAI_Link_Cloak'] = 1;
		
		return $addons;
	}

	// define addon's options
	public static function get_default_import_options(){
		return array(			
			'pmlca_mode'   => 'all',
			'pmlca_prefix' => '',
            'pmlca_old_prefix' => ''
		);
	}

	public function pmwi_pmxi_save_options( $options ){

	    $import_id = $_GET['id'];

        $import = new PMXI_Import_Record();
        $import->getById($import_id);
        if (!$import->isEmpty() && !empty($import->options['pmlca_prefix']) && $import->options['pmlca_prefix'] != $import->options['pmlca_old_prefix']){
            $options['pmlca_old_prefix'] = $import->options['pmlca_prefix'];
        }

        return $options;
    }

	/**
	 * Function for parsing data. Function name should start from your addon's prefix {Addon Prefix}_parse, for example 'my_pmai_addon_parse'
	 * @param object $parsingData['import'] Import object
	 * @param int $parsingData['count'] Count of records to import
	 * @param string $parsingData['xml'] XML data
	 * @param function $parsingData['logger'] Add message to the log
	 * @param int $parsingData['chunk'] Number of Ajax iteration
	 * @param string $parsingData['xpath_prefix'] constant
	 */
	public function parse($parsingData){
		
		
	}

	/**
	 * Function to import data. Function name should start from your addon's prefix {Addon Prefix}_import, for example 'my_pmai_addon_import'
	 * @param int $importData['pid'] post ID 
	 * @param int $importData['i'] Number of record in current XML chunk
	 * @param object $importData['import'] Import object
	 * @param array $importData['articleData'] post data
	 * @param string $importData['xml'] XML data
	 * @param bool $importData['is_cron']
	 * @param function $importData['logger'] Add message to the log
	 * @param string $importData['xpath_prefix'] constant
	 * 
	 * @param array $parsedData Previous function result
	 */
	public function import($importData, $parsedData = array()){	

		
	}

	public function view( $post_type ){
		
		$default = self::get_default_import_options();

		$this->input = new PMXI_Input();

		$id = $this->input->get('id');

		$import = new PMXI_Import_Record();			
		if ( ! $id or $import->getById($id)->isEmpty()) { // specified import is not found
			$post = $this->input->post(			
				$default			
			);
		}
		else 
			$post = $this->input->post(
				$import->options
				+ $default			
			);		

		if ( (PMXI_EDITION == 'free' and version_compare(PMXI_VERSION, '3.3.6-beta1') >= 0) or (PMXI_EDITION == 'paid' and version_compare(PMXI_VERSION, '4.0.0-beta1') >= 0 )){
			$is_loaded_template = (!empty(PMXI_Plugin::$session->is_loaded_template)) ? PMXI_Plugin::$session->is_loaded_template : false;
		}
		else{
			$is_loaded_template = (!empty(PMXI_Plugin::$session->data['pmxi_import']) and !empty(PMXI_Plugin::$session->data['pmxi_import']['is_loaded_template'])) ? PMXI_Plugin::$session->data['pmxi_import']['is_loaded_template'] : false;
		}

		$load_options = $this->input->post('load_template');

		if ($load_options) { // init form with template selected
			
			$template = new PMXI_Template_Record();
			if ( ! $template->getById($is_loaded_template)->isEmpty()) {	
				$post = (!empty($template->options) ? $template->options : array()) + $default;				
			}
			
		} elseif ($load_options == -1){
			
			$post = $default;
							
		}

		if ( (PMXI_EDITION == 'free' and version_compare(PMXI_VERSION, '3.3.6-beta1') >= 0) or (PMXI_EDITION == 'paid' and version_compare(PMXI_VERSION, '4.0.0-beta1') >= 0 )){
		?>
		<div class="wpallimport-section">
			<div class="wpallimport-collapsed closed">
				<div class="wpallimport-content-section">
					<div class="wpallimport-collapsed-header">
						<h3><?php _e('Link Cloaking Add-On','pmxi_plugin');?></h3>	
					</div>
					<div class="wpallimport-collapsed-content" style="padding: 0;">
						<div class="wpallimport-collapsed-content-inner">
							<table class="form-table" style="max-width:none;">
								<tr>
									<td colspan="3" style="padding-top:20px;">					
										<div class="input">						
											<input type="radio" id="pmxilc_mode_no_<?php echo $post_type; ?>" class="switcher" name="pmlca_mode" value="no" <?php echo 'no' == $post['pmlca_mode'] ? 'checked="checked"': '' ?>/>
											<label for="pmxilc_mode_no_<?php echo $post_type; ?>"><?php _e('Do not cloak links', 'pmxi_plugin' )?></label><br>
											<input type="radio" id="pmxilc_mode_all_<?php echo $post_type; ?>" class="switcher" name="pmlca_mode" value="all" <?php echo 'all' == $post['pmlca_mode'] ? 'checked="checked"': '' ?>/>
											<label for="pmxilc_mode_all_<?php echo $post_type; ?>"><?php _e('Cloak all links present during import', 'pmxi_plugin' )?></label><br>
											<input type="radio" id="pmxilc_mode_affiliate_<?php echo $post_type; ?>" class="switcher" name="pmlca_mode" value="affiliate" <?php echo 'affiliate' == $post['pmlca_mode'] ? 'checked="checked"': '' ?>/>
											<label for="pmxilc_mode_affiliate_<?php echo $post_type; ?>"><?php _e('Only cloak WooCommerce External/Affiliate Product Buy URL ', 'pmxi_plugin' )?></label><br>				
										</div>				
										<?php if (PMXI_Plugin::getInstance()->isPermalinks()): ?>	
										<div class="input">		
											<h4><?php _e('Link prefix', 'wp_all_import_plugin'); ?></h4>
											<input type="text" name="pmlca_prefix" value="<?php echo $post['pmlca_prefix']; ?>"/>
											<a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="<?php printf('Cloaked link will look like: %s', site_url() . '/{prefix}{link_ID}'); ?>">?</a>
										</div>					
										<?php endif; ?>
									</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		} else {
		?>
		<tr>
			<td colspan="3" style="padding-top:20px;">
				<fieldset class="optionsset pmli_options">
					<legend><?php _e('Link Cloaking Add-On','pmxi_plugin');?></legend>
					<div class="input">						
						<input type="radio" id="pmxilc_mode_no_<?php echo $post_type; ?>" class="switcher" name="pmlca_mode" value="no" <?php echo 'no' == $post['pmlca_mode'] ? 'checked="checked"': '' ?>/>
						<label for="pmxilc_mode_no_<?php echo $post_type; ?>"><?php _e('Do not cloak links', 'pmxi_plugin' )?></label><br>
						<input type="radio" id="pmxilc_mode_all_<?php echo $post_type; ?>" class="switcher" name="pmlca_mode" value="all" <?php echo 'all' == $post['pmlca_mode'] ? 'checked="checked"': '' ?>/>
						<label for="pmxilc_mode_all_<?php echo $post_type; ?>"><?php _e('Cloak all links present during import', 'pmxi_plugin' )?></label><br>
						<input type="radio" id="pmxilc_mode_affiliate_<?php echo $post_type; ?>" class="switcher" name="pmlca_mode" value="affiliate" <?php echo 'affiliate' == $post['pmlca_mode'] ? 'checked="checked"': '' ?>/>
						<label for="pmxilc_mode_affiliate_<?php echo $post_type; ?>"><?php _e('Only cloak WooCommerce External/Affiliate Product Buy URL ', 'pmxi_plugin' )?></label><br>				
					</div>
				</fieldset>		
			</td>
		</tr>
		<?php
		}
		
	}

	/* FILTERS */
	public function pmxi_the_content( $content, $import_id ){

		if (empty($content)) return '';

		$import = new PMXI_Import_Record();		

		if ( ! $import->getById($import_id)->isEmpty() ){

			if ( empty($import->options['is_cloak']) and ! empty($import->options['pmlca_mode']) and $import->options['pmlca_mode'] == "all" )

				return $this->cloak_aff_links($content, false, $import->options['pmlca_prefix'], $import->options['pmlca_old_prefix']);
		}

		return $content; 

	}

	public function pmxi_custom_field( $value, $pid, $m_key, $existing_meta_keys, $import_id ){

		if (empty($value)) return $value;

		$import = new PMXI_Import_Record();		

		if ( ! $import->getById($import_id)->isEmpty() ){

			if ( empty($import->options['is_cloak']) and ! empty($import->options['pmlca_mode']) and $import->options['pmlca_mode'] == "all" )

				return $this->cloak_aff_links($value, true, $import->options['pmlca_prefix'], $import->options['pmlca_old_prefix']);
		}

		return $value; 

	}

	public function pmwi_cloak_affiliate_url( $aff_url, $import_id ){

		if (empty($aff_url)) return '';

		$import = new PMXI_Import_Record();		

		if ( ! $import->getById($import_id)->isEmpty() ){		

			if ( (empty($import->options['is_cloak']) or ! class_exists('PMLC_Plugin')) and ! empty($import->options['pmlca_mode']) and ( in_array($import->options['pmlca_mode'], array("affiliate", "all")) ) )			
			{								
				return $this->cloak_aff_links($aff_url, true, $import->options['pmlca_prefix'], $import->options['pmlca_old_prefix']);
			}
		}

		return $aff_url; 

	}

	protected function cloak_aff_links( $content, $single_url = true, $link_prefix = '',  $old_prefix = ''){

		return pmailc_cloak_aff_links( $content, $single_url, $link_prefix, $old_prefix );

	}

	/**
	 * Return full url which corresponds to this link
	 */
	public function getUrl($link, $sub_id = NULL) {
		if ( ! empty($link->slug)) {
			$url = '';
			
			$url = site_url( '/' . ((PMXI_Plugin::getInstance()->isPermalinks()) ? apply_filters('wp_all_import_linkcloak', $link->slug) : '?link=' . apply_filters('wp_all_import_linkcloak', $link->slug)));								
			if ( ! (is_null($sub_id) or '' === $sub_id)) {					
				$url .= '/' . (PMXI_Plugin::getInstance()->isPermalinks()) ? apply_filters('wp_all_import_linkcloak', $sub_id) : '?link=' . apply_filters('wp_all_import_linkcloak', $sub_id);
			}
			
			return $url;
		} else {
			return NULL;
		}
	}
}

WPAI_Link_Cloak::getInstance();

// retrieve our license key from the DB
$wpai_linkcloak_addon_options = get_option('PMXI_Plugin_Options');	

if ( ! empty($wpai_linkcloak_addon_options['info_api_url']) and class_exists('PMXI_Updater')){
	// setup the updater
	$updater = new PMXI_Updater( $wpai_linkcloak_addon_options['info_api_url'], __FILE__, array( 
			'version' 	=> PMLCA_VERSION, // current version number
			'license' 	=> false, // license key (used get_option above to retrieve from DB)
			'item_name' => WPAI_Link_Cloak::getEddName(), 	// name of this plugin
			'author' 	=> 'Soflyy'  // author of this plugin
		)
	);
}

if ( ! function_exists('pmailc_cloak_aff_links')):

	function pmailc_cloak_aff_links($content, $single_url = true, $link_prefix = '', $old_prefix = ''){

		if ($single_url){
					
			if (preg_match('%^\w+://%i', $content)) { // mask only links having protocol
				// try to find matching cloaked link among already registered ones

				global $wpdb;
				$table_prefix = WPAI_Link_Cloak::getInstance()->getTablePrefix();
				$results = $wpdb->get_results( "SELECT * FROM {$table_prefix}links WHERE afflink LIKE '%{$content}%'", OBJECT );
				
				if ($results) { // matching link found
					$link = $results[0];
                    if (!empty($old_prefix) && $old_prefix != $link_prefix){
                        $link->slug = str_replace($old_prefix, '', $link->slug);
                    }
					$slug = apply_filters('wpai_link_cloak_update_slug', $link_prefix . str_replace($link_prefix, '', $link->slug), $link->afflink);									
					if ($slug != $link->slug)
					{
						$link->slug = $slug;
						$wpdb->update( 
							$table_prefix . 'links', 
							array( 
								'slug' => strval($slug)								
							), 
							array( 'id' => $link->id ),
							array( 
								'%s' 
							) 
						);
					}
				} else { // register new cloaked link					
					$slug = max(							
						intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}links")),
						0
					);
					$final_slug = '';
					$i = 0; do {
						is_int(++$slug) and $slug > 0 or $slug = 1;
						$final_slug = $link_prefix . $slug;
						$final_slug = apply_filters('wpai_link_cloak_slug', $final_slug, $content);						
						$is_slug_found = ! intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}links WHERE slug = '$final_slug'"));
					} while( ! $is_slug_found and $i++ < 100000);

					if ($is_slug_found) {		

						$wpdb->insert( 
							$table_prefix . 'links', 
							array( 
								'slug' => strval($final_slug), 
								'afflink' => $content 
							), 
							array( 
								'%s', 
								'%s' 
							) 
						);		

						if ($wpdb->insert_id){
							$link = $wpdb->get_row("SELECT * FROM {$table_prefix}links WHERE id = {$wpdb->insert_id}");
						}							
					}
				}
									
				if ($link) { // cloaked link is found or created for url
					$content = preg_replace('%' . preg_quote($content, '%') . '(?=([\s\'"]|$))%i', WPAI_Link_Cloak::getInstance()->getUrl($link), $content);
				}
			}
		}
		elseif (preg_match_all('%<a\s[^>]*href=(?(?=")"([^"]*)"|(?(?=\')\'([^\']*)\'|([^\s>]*)))%is', $content, $matches, PREG_PATTERN_ORDER)) {
			$hrefs = array_unique(array_merge(array_filter($matches[1]), array_filter($matches[2]), array_filter($matches[3])));
			foreach ($hrefs as $url) {
				if (preg_match('%^\w+://%i', $url)) { // mask only links having protocol
					// try to find matching cloaked link among already registered ones
					global $wpdb;
					$table_prefix = WPAI_Link_Cloak::getInstance()->getTablePrefix();
					$results = $wpdb->get_results( "SELECT * FROM {$table_prefix}links WHERE afflink LIKE '%{$url}%'", OBJECT );
					
					if ($results) { // matching link found
						$link = $results[0];
                        if (!empty($old_prefix) && $old_prefix != $link_prefix){
                            $link->slug = str_replace($old_prefix, '', $link->slug);
                        }
						$slug = apply_filters('wpai_link_cloak_update_slug', $link_prefix . str_replace($link_prefix, '', $link->slug), $link->afflink);									
						if ($slug != $link->slug)
						{
							$link->slug = $slug;
							$wpdb->update( 
								$table_prefix . 'links', 
								array( 
									'slug' => strval($slug)								
								), 
								array( 'id' => $link->id ),
								array( 
									'%s'									
								) 
							);
						}
					} else { // register new cloaked link					
						$slug = max(							
							intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}links")),
							0
						);
						$final_slug = '';
						$i = 0; do {
							is_int(++$slug) and $slug > 0 or $slug = 1;
							$final_slug = $link_prefix . $slug;
							$final_slug = apply_filters('wpai_link_cloak_slug', $final_slug, $url);
							$is_slug_found = ! intval($wpdb->get_var("SELECT COUNT(*) FROM {$table_prefix}links WHERE slug = '$final_slug'"));
						} while( ! $is_slug_found and $i++ < 100000);

						if ($is_slug_found) {
							
							$wpdb->insert( 
								$table_prefix . 'links', 
								array( 
									'slug' => strval($final_slug), 
									'afflink' => $url 
								), 
								array( 
									'%s', 
									'%s' 
								) 
							);		

							if ($wpdb->insert_id){
								$link = $wpdb->get_row("SELECT * FROM {$table_prefix}links WHERE id = {$wpdb->insert_id}");
							}								
						}
					}
										
					if ($link) { // cloaked link is found or created for url
						$content = preg_replace('%' . preg_quote($url, '%') . '(?=([\s\'"]|$))%i', WPAI_Link_Cloak::getInstance()->getUrl($link), $content);
					}
				}
			}
		}
		return $content;
	}

endif;

