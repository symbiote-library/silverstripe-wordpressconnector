<?php
/**
 * @package silverstripe-wordpressconnector
 */

use Zend\XmlRpc\Client;

/**
 * The base wordpress content source.
 *
 * @package silverstripe-wordpressconnector
 */
class WordpressContentSource extends ExternalContentSource {

	const DEFAULT_CACHE_LIFETIME = 3600;

	private static $db = array(
		'BaseUrl'       => 'Varchar(255)',
		'BlogId'        => 'Int',
		'Username'      => 'Varchar(255)',
		'Password'      => 'Varchar(255)',
		'CacheLifetime' => 'Int'
	);

	private static $defaults = array(
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
		Requirements::css('wordpressconnector/css/WordpressContentSource.css');

		$fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
			'Name', 'BaseUrl', 'BlogId', 'Username', 'Password', 'ShowContentInMenu'
		));

		$fields->addFieldToTab('Root.Advanced',
			new NumericField('CacheLifetime', 'Cache Lifetime (in seconds)'));

		if ($this->BaseUrl && !$this->isValid()) {
			$error = new LiteralField('ConnError', sprintf(
				'<p id="wordpress-conn-error">%s <span>%s</span></p>',
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
		return array_merge(parent::fieldLabels(), array(
			'ConnError' => _t('WordpresConnector.CONNERROR', 'Could not connect to the wordpress site:'),
			'BaseUrl'   => _t('WordpressConnector.WPBASEURL', 'Wordpress Base URL'),
			'BlogId'    => _t('WordpressConnector.BLOGID', 'Wordpress Blog ID'),
			'Username'  => _t('WordpressConnector.WPUSER', 'Wordpress Username'),
			'Password'  => _t('WordpressConnector.WPPASS', 'Wordpress Password')
		));
	}

	/**
	 * @return Zend_XmlRpc_Client
	 */
	public function getClient() {
		if (!$this->client) {
			$client = new Client($this->getApiUrl());
			$client->setSkipSystemLookup(true);

			$this->client = SS_Cache::factory('wordpress_posts', 'Class', array(
				'cached_entity' => $client,
				'lifetime'      => $this->getCacheLifetime()
			));
		}

		return $this->client;
	}

	public function getContentImporter($target = null) {
		return new WordpressImporter();
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
			$client->call('demo.sayHello');
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
	public function canImport() {
		return $this->isValid();
	}

	/**
	 * @return int
	 */
	public function getCacheLifetime() {
		return ($t = $this->getField('CacheLifetime')) ? $t : self::DEFAULT_CACHE_LIFETIME;
	}

}
