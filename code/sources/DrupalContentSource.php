<?php
/**
 * @package silverstripe-drupal-connector
 */

require_once 'Zend/XmlRpc/Client.php';

/**
 * The base Drupal content source.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalContentSource extends ExternalContentSource {

	public static $icon = 'drupal-connector/images/drupal.png';

	const DEFAULT_CACHE_LIFETIME = 3600;

	public static $db = array(
		'BaseUrl' => 'Varchar(255)',
		'Username' => 'Varchar(255)',
		'Password' => 'Varchar(255)',
		'CacheLifetime' => 'Int'
	);

	public static $defaults = array(
		'CacheLifetime' => self::DEFAULT_CACHE_LIFETIME
	);

	protected $client;
	protected $valid;
	protected $error;

	/**
	 * @return FieldSet
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		//Requirements::css('wordpressconnector/css/WordpressContentSource.css');

		$fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
			'Name', 'BaseUrl', 'Username', 'Password', 'ShowContentInMenu'
		));

		$fields->addFieldToTab('Root.Advanced',
			new NumericField('CacheLifetime', 'Cache Lifetime (in seconds)'));

		if ($this->BaseUrl && !$this->isValid()) {
			$error = new LiteralField('ConnError', sprintf(
				'<p id="drupal-conn-error">%s <span>%s</span></p>',
				$this->fieldLabel('ConnError'), $this->error
			));
			$fields->addFieldToTab('Root.Main', $error, 'Name');
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	public function fieldLabels($includerelations = true) {
		return array_merge(parent::fieldLabels($includerelations), array(
			'ConnError' => _t('DrupalConnector.CONNERROR', 'Could not connect to the Drupal site:'),
			'BaseUrl' => _t('DrupalConnector.WPBASEURL', 'Drupal Base URL'),
			'Username' => _t('DrupalConnector.WPUSER', 'Drupal Username'),
			'Password' => _t('DrupalConnector.WPPASS', 'Drupal Password')
		));
	}

	/**
	 * @return array
	 */
	public function getNode($id) {
		$client = $this->getClient($id);
		$id = $this->decodeId($id);

		$node = $client->call("node.retrieve", array($id));

		return $node;
	}

	/**
	 * @return Zend_XmlRpc_Client
	 */
	public function getClient() {
		if (!$this->client) {
			$client = new Zend_XmlRpc_Client($this->getApiUrl());
			$client->setSkipSystemLookup(true);

			$this->client = SS_Cache::factory('drupal_menu', 'Class', array(
				'cached_entity' => $client,
				'lifetime' => $this->getCacheLifetime()
			));
			/*
			TODO: authenticate, to avoid hacking perms in Drupal.
			$result = $this->client->call('user.login', array($this->Username, $this->Password));
			$sessionName = $result['session_name'];
			$sessid = $result['sessid'];
			*/
		}

		return $this->client;
	}

	public function getContentImporter($target=null) {
		return new DrupalImporter();
	}

	/**
	 * @return string
	 */
	public function getApiUrl() {
		return Controller::join_links($this->BaseUrl, 'xmlrpc.php');
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		if (!$this->BaseUrl || !$this->Username || !$this->Password) return;

		if ($this->valid !== null) {
			return $this->valid;
		}

		try {
			$client = $this->getClient();
			$client->call('system.connect');
		} catch (Zend_Exception $ex) {
			$this->error = $ex->getMessage();
			return $this->valid = false;
		}

		return $this->valid = true;
	}

	/**
	 * Prevent creating this abstract content source type.
	 */
	public function canCreate($member = null) {
		return false;
	}

	/**
	 * @return bool
	 */
	public function canImport($member = null) {
		return $this->isValid();
	}

	/**
	 * @return int
	 */
	public function getCacheLifetime() {
		return ($t = $this->getField('CacheLifetime')) ? $t : self::DEFAULT_CACHE_LIFETIME;
	}

}