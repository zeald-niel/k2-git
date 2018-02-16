<?php

class PMUI_Import_Record extends PMUI_Model_Record {		

	/**
	 * Associative array of data which will be automatically available as variables when template is rendered
	 * @var array
	 */
	public $data = array();

	public $parsing_data = array();

	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) { 
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}	
	
	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return PMUI_Import_Record
	 * @chainable
	 */
	public function parse($parsing_data = array()) { //$import, $count, $xml, $logger = NULL, $chunk = false, $xpath_prefix = ""		

		if ( empty($parsing_data['import']->options['pmui']['import_users']) ) return;

		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content			

		$cxpath = $parsing_data['xpath_prefix'] . $parsing_data['import']->xpath;

		$this->data = array();
		$records    = array();
		$tmp_files  = array();

		$parsing_data['chunk'] == 1 and $parsing_data['logger'] and call_user_func($parsing_data['logger'], __('Composing users...', 'wp_all_import_user_add_on'));
		
		$xml = $parsing_data['xml'];

		if ( ! empty($parsing_data['import']->options['pmui']['login']) ) {
			$this->data['pmui_logins'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['login'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_logins'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['pass']) ) {
			$this->data['pmui_pass'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['pass'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_pass'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['nicename']) ) {
			$this->data['pmui_nicename'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['nicename'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_nicename'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['email']) ) {
			$this->data['pmui_email'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['email'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_email'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['registered']) ) {
			$this->data['pmui_registered'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['registered'], $file)->parse($records); $tmp_files[] = $file;				
			$warned = array(); // used to prevent the same notice displaying several times
			foreach ($this->data['pmui_registered'] as $i => $d) {
				if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
				$time = strtotime($d);
				if (FALSE === $time) {
					in_array($d, $warned) or $logger and call_user_func($logger, sprintf(__('<b>WARNING</b>: unrecognized date format `%s`, assigning current date', 'wp_all_import_user_add_on'), $warned[] = $d));
					$time = time();
				}
				$this->data['pmui_registered'][$i] = date('Y-m-d H:i:s', $time);
			}
		}
		else {
			$parsing_data['count'] and $this->data['pmui_registered'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['display_name']) ) {
			$this->data['pmui_display_name'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['display_name'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_display_name'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['url']) ) {
			$this->data['pmui_url'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['url'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_url'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['first_name']) ) {
			$this->data['pmui_first_name'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['first_name'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_first_name'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['last_name']) ) {
			$this->data['pmui_last_name'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['last_name'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_last_name'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['description']) ) {
			$this->data['pmui_description'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['description'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_description'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['nickname']) ) {
			$this->data['pmui_nickname'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['nickname'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_nickname'] = array_fill(0, $parsing_data['count'], '');
		}

		if ( ! empty($parsing_data['import']->options['pmui']['role']) ) {
			$this->data['pmui_role'] = XmlImportParser::factory($xml, $cxpath, $parsing_data['import']->options['pmui']['role'], $file)->parse($records); $tmp_files[] = $file;				
		}
		else {
			$parsing_data['count'] and $this->data['pmui_role'] = array_fill(0, $parsing_data['count'], '');
		}
			
		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure					

		foreach ($tmp_files as $file) { // remove all temporary files created
			@unlink($file);
		}
		
		return $this->data;
	}

	public function import($importData = array()){ //$pid, $i, $import, $articleData, $xml, $is_cron = false, $xpath_prefix = ""

		if ($importData['import']->options['custom_type'] != 'import_users') return;

		if (empty($importData['articleData']['ID']) and empty($importData['import']->options['do_not_send_password_notification']))
		{
			// Welcome Email

			global $wp_version;

			if (version_compare($wp_version, '4.3.1') < 0)
			{
				wp_new_user_notification( $importData['pid'], 'both' );
			}
			else
			{
				wp_new_user_notification( $importData['pid'], null, 'both' );
			}
		}		

	}

	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	public function filtering($var){
		return ("" == $var) ? false : true;
	}		
}
