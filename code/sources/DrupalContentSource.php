<?php
/**
 * @package silverstripe-drupal-connector
 */

// Done this way as the set_include_path in _config.php isn't run on a dev/build, and we can't use Zend_Loader as
// there's a duplicate in the external-content module. So the functionality of that class is duplicated below:
$includePath = get_include_path();
set_include_path($includePath . PATH_SEPARATOR . dirname(__FILE__) . '/../../thirdparty' . PATH_SEPARATOR . dirname(__FILE__) . '/../../../external-content/thirdparty');
require_once('Zend/XmlRpc/Client.php');
set_include_path($includePath);

/**
 * The base Drupal content source.
 *
 * @package silverstripe-drupal-connector
 */
class DrupalContentSource extends ExternalContentSource
{

    public static $icon = 'drupal-connector/images/drupal.png';

    const DEFAULT_CACHE_LIFETIME = 3600;

    public static $db = array(
        'BaseUrl' => 'Varchar(255)',
        'Username' => 'Varchar(255)',
        'Password' => 'Varchar(255)',
        'DrupalVersion' => "Enum('5.x, 6.x, 7.x', '5.x')",
        'APIKey' => 'Varchar(255)',
        'APIKeyDomain' => 'Varchar(255)',
        'CacheLifetime' => 'Int'
    );

    public static $defaults = array(
        'CacheLifetime' => self::DEFAULT_CACHE_LIFETIME
    );

    protected $client;
    protected $valid;
    protected $error;
    protected $session_id = null;

    /**
     * @return FieldSet
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $fields->addFieldToTab('Root.Main', new DropdownField('DrupalVersion', 'Drupal Version', singleton('DrupalContentSource')->dbObject('DrupalVersion')->enumValues()));

        $fields->fieldByName('Root.Main')->getChildren()->changeFieldOrder(array(
            'Name', 'BaseUrl', 'DrupalVersion', 'Username', 'Password', 'APIKey', 'APIKeyDomain', 'ShowContentInMenu'
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

        $fields->addFieldsToTab('Root.Import', array(
            new CheckboxField('ImportMedia', 'Import and rewrite references to Drupal media?', true),
            new TextField('FileRelation', 'Relation on Page to import attached files into'),
            new TextField('AssetsPath', 'Upload Drupal files to', 'Uploads/Drupal'),
        ));
        if (class_exists('TaxonomyTerm')) {
            $fields->addFieldToTab('Root.Import', new TextField('TaxonomyRelation', 'Relation on Page to import taxonomy tags into'));
        }
        $fields->addFieldsToTab('Root.Import', array(
            new TextField('PageType', 'Page type to import into (leave blank for default)'),
            new CheckboxField('PublishOnImport', 'Publish pages after import?'),
            new CheckboxField('ImportPublishDates', 'Import created and last edited dates?')
        ));

        return $fields;
    }

    /**
     * @return array
     */
    public function fieldLabels($includerelations = true)
    {
        return array_merge(parent::fieldLabels($includerelations), array(
            'ConnError' => _t('DrupalConnector.CONNERROR', 'Could not connect to the Drupal site:'),
            'BaseUrl' => _t('DrupalConnector.WPBASEURL', 'Drupal Base URL'),
            'Username' => _t('DrupalConnector.WPUSER', 'Drupal Username'),
            'Password' => _t('DrupalConnector.WPPASS', 'Drupal Password'),
            'Version' => 'Drupal Version',
            'APIKey' => 'API Key',
            'APIKeyDomain' => 'API Key Domain',
        ));
    }

    /**
     * Gets an object from the Drupal site. As there are different types of objects the type is
     * encoded along with the numeric ID.
     */
    public function getObject($id)
    {
        $id = $this->decodeId($id);

        $parts = explode(':', $id);
        if (count($parts) != 2) {
            return null;
        }
        $type = $parts[0];
        $id = $parts[1];

        return $this->createContentItem($type, $id);
    }

    protected function createContentItem($type, $id)
    {
        if ($type == 'node') {
            return $this->fetchNode($id);
        }
    }

    protected function fetchNode($id)
    {
        $function = 'node.retrieve';
        if ($this->DrupalVersion == '5.x') {
            $function = 'node.load';
        }
        $nodeData = $this->RPC($function, array($id, array()));

        if ($nodeData) {
            return DrupalNodeContentItem::factory($this, $nodeData);
        }
    }

    /**
     * @return Zend_XmlRpc_Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $client = new Zend_XmlRpc_Client($this->getApiUrl());
            $client->setSkipSystemLookup(true);

            /*$this->client = SS_Cache::factory('drupal_content_source', 'Class', array(
                'cached_entity' => $client,
                'lifetime' => $this->getCacheLifetime()
            ));*/
            $this->client = $client;
        }

        return $this->client;
    }

    public function getContentImporter($target=null)
    {
        return new DrupalImporter();
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return Controller::join_links($this->BaseUrl, 'xmlrpc.php');
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (!$this->BaseUrl || !$this->Username || !$this->Password) {
            return false;
        }

        if ($this->valid !== null) {
            return $this->valid;
        }

        try {
            $this->RPC('system.connect');
        } catch (Zend_Exception $ex) {
            $this->error = $ex->getMessage();
            return $this->valid = false;
        }

        return $this->valid = true;
    }

    /**
     * Passes a call through to the RPC client.
     * This handles all the login and session id shenanigans.
     */
    public function RPC($method, $arguments = array())
    {
        if ($method != 'system.connect' && $method != 'system.listMethods') {
            // If this is anything but a system.connect or system.listMethods, then we need to have a
            // valid session id and login.
            if (is_null($this->session_id)) {
                $this->login();
            }

            // If this a v5 site then we need to fetch the sessid and pass in the API key, nonce, etc.
            if ($this->DrupalVersion == '5.x') {
                // Push them all to the front of the arguments array.
                if ($method == 'node.load') {
                    $arguments = array_merge(array($this->session_id), $arguments);
                } else {
                    // Timestamp and nonce
                    $timestamp = (string) time();
                    $generator = new RandomGenerator();
                    $nonce = $generator->randomToken();

                    // Create new secure hash using your api key.
                    $hash = hash_hmac('sha256', $timestamp . ';' . $this->APIKeyDomain . ';' . $nonce . ';' . $method, $this->APIKey);

                    $arguments = array_merge(array($hash, $this->APIKeyDomain, $timestamp, $nonce, $this->session_id), $arguments);
                }
            }
        }

        $client = $this->getClient();
        return $client->call($method, $arguments);
    }

    /**
     * Logs in to the Drupal site with the provided username and password, and saves the session id.
     */
    protected function login()
    {
        $client = $this->getClient();

        // Get the sessid from the connect call.
        $result = $client->call('system.connect');
        $this->session_id = $result['sessid'];

        // Log in to the site with the username and password.
        $result = $this->RPC('user.login', array($this->Username, $this->Password));
        $this->session_id = $result['sessid'];
    }

    /**
     * Prevent creating this abstract content source type.
     */
    public function canCreate($member = null)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canImport($member = null)
    {
        return $this->isValid();
    }

    /**
     * @return int
     */
    public function getCacheLifetime()
    {
        return ($t = $this->getField('CacheLifetime')) ? $t : self::DEFAULT_CACHE_LIFETIME;
    }

    protected function parseType($id)
    {
        $parts = explode(':', $id);
        if (count($parts) != 2) {
            return null;
        }
        return $parts[0];
    }

    protected function parseID($id)
    {
        $parts = explode(':', $id);
        if (count($parts) != 2) {
            return null;
        }
        return $parts[1];
    }
}
