<?php

/**
 * Wrapper for GetResponse's API.
 *
 * @since   1.1.0
 *
 * @package ET\Core\API\Email
 */
class ET_Core_API_Email_GetResponse extends ET_Core_API_Email_Provider {

	/**
	 * @inheritDoc
	 */
	public $BASE_URL = 'https://api.getresponse.com/v3';

	/**
	 * @inheritDoc
	 */
	public $LISTS_URL = 'https://api.getresponse.com/v3/campaigns';

	public $SUBSCRIBE_URL = 'https://api.getresponse.com/v3/contacts';

	/**
	 * @inheritDoc
	 */
	public $name = 'GetResponse';

	/**
	 * @inheritDoc
	 */
	public $name_field_only = true;

	/**
	 * @inheritDoc
	 */
	public $slug = 'getresponse';

	/**
	 * @inheritDoc
	 * @internal If true, oauth endpoints properties must also be defined.
	 */
	public $uses_oauth = false;

	public function __construct( $owner = '', $account_name = '', $api_key = '' ) {
		parent::__construct( $owner, $account_name, $api_key );

		$this->_maybe_set_custom_headers();
	}

	protected function _maybe_set_custom_headers() {
		if ( empty( $this->custom_headers ) && isset( $this->data['api_key'] ) ) {
			$this->custom_headers = array( 'X-Auth-Token' => "api-key {$this->data['api_key']}" );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_account_fields() {
		return array(
			'api_key' => array(
				'label' => esc_html__( 'API Key', 'et_core' ),
			),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_data_keymap( $keymap = array(), $custom_fields_key = '' ) {
		$custom_fields_key = 'customFieldValues';

		$keymap = array(
			'list'       => array(
				'name'              => 'name',
				'list_id'           => 'campaignId',
				'subscribers_count' => 'totalSubscribers',
			),
			'subscriber' => array(
				'name'       => 'name',
				'email'      => 'email',
				'list_id'    => 'campaign.campaignId',
				'ip_address' => 'ipAddress',
			),
			'error'      => array(
				'error_message' => 'message',
			),
		);

		return parent::get_data_keymap( $keymap, $custom_fields_key );
	}

	/**
	 * @inheritDoc
	 */
	public function fetch_subscriber_lists() {
		if ( empty( $this->data['api_key'] ) ) {
			return $this->API_KEY_REQUIRED;
		}

		$this->_maybe_set_custom_headers();

		$this->response_data_key = false;

		return parent::fetch_subscriber_lists();
	}

	/**
	 * @inheritDoc
	 */
	public function subscribe( $args, $url = '' ) {
		$args               = $this->transform_data_to_provider_format( $args, 'subscriber' );
		$args['note']       = $this->SUBSCRIBED_VIA;
		$args['dayOfCycle'] = 1;

		if ( empty( $args['name'] ) ) {
			unset( $args['name'] );
		}

		$this->prepare_request( $this->SUBSCRIBE_URL, 'POST', false, $args );

		return parent::subscribe( $args, $this->SUBSCRIBE_URL );
	}
}
