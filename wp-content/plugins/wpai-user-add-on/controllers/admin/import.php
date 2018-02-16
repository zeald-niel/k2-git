<?php 
/**
 * Import configuration wizard
 * 
 * @author Max Tsiplyakov <makstsiplyakov@gmail.com>
 */

class PMUI_Admin_Import extends PMUI_Controller_Admin {				

	public function index( $post = array() ) {			

		$this->data['post'] =& $post;	
		
		$this->render();

	}	
	
	public function options( $isWizard = false, $post = array() ){		

		$this->data['isWizard'] = $isWizard;	

		$this->data['post'] =& $post;	

		// Get All meta keys in the system
		$this->data['existing_meta_keys'] = array();
		$meta_keys = new PMXI_Model_List();
		$meta_keys->setTable(PMXI_Plugin::getInstance()->getWPPrefix() . 'usermeta');
		$meta_keys->setColumns('umeta_id', 'meta_key')->getBy(NULL, "umeta_id", NULL, NULL, "meta_key");	
		$hide_fields = array('first_name', 'last_name', 'nickname', 'description', PMXI_Plugin::getInstance()->getWPPrefix() . 'capabilities');
		if ( ! empty($meta_keys) and $meta_keys->count() ){
			foreach ($meta_keys as $meta_key) { if (in_array($meta_key['meta_key'], $hide_fields) or strpos($meta_key['meta_key'], '_wp') === 0) continue;
				$this->data['existing_meta_keys'][] = $meta_key['meta_key'];
			}
		}			

		$this->render();
	}

    public function confirm( $isWizard = false, $post = array() ){

        $this->data['isWizard'] = $isWizard;

        $this->data['post'] =& $post;

        $this->render();
    }
}
