<?php

class PMUI_Import_List extends PMUI_Model_List {
	public function __construct() {
		parent::__construct();
		$this->setTable(PMUI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}
}