<?php

use TheIconic\Tracking\GoogleAnalytics\Analytics;
use Ramsey\Uuid\Uuid;

/**
 * This class handles the lilURL database interactions.
 */
class lilURL
{
    const ERR_UNKNOWN          = -1;
    const ERR_INVALID_PROTOCOL = -2;
    const ERR_INVALID_DOMAIN   = -3;
    const ERR_USED             = -4;
    const ERR_INVALID_ALIAS    = -5;
    const ERR_ALIAS_EXISTS     = -6;
    const ERR_INVALID_GA_CAMPAIGN = -7;
    const ERR_INVALID_URL = -8;
    const ERR_MAX_RANDOM_ID_ATTEMPTS = -9;
    const RANDOM_ID_ATTEMPTS_THERSHOLD = 1000000;
    const MAX_RANDOM_ID_BUMP_LENGTH = 5;
    const MAX_RANDOM_ID_ATTEMPTS = 15000000;

    const SQL_INSERT = 'INSERT';
    const SQL_UPDATE = 'UPDATE';
    const SQL_DELETE = 'DELETE';

    // Table Column Placeholders
		const PDO_PLACEHOLDER_URL_ID = ':urlID';
		const PDO_PLACEHOLDER_LONG_URL = ':longURL';
		const PDO_PLACEHOLDER_SUBMIT_DATE = ':submitDate';
		const PDO_PLACEHOLDER_CREATED_BY = ':createdBy';
		const PDO_PLACEHOLDER_REDIRECTS = ':redirects';
		const PDO_PLACEHOLDER_GROUP_ID = ':groupID';
		const PDO_PLACEHOLDER_GROUP_NAME = ':groupName';
		const PDO_PLACEHOLDER_UID = ':uid';

    protected $db;

    protected $urlTable = 'tblURLs';

    protected static $random_id_length = 4;

    protected $allowed_protocols = [];

    protected $allowed_domains = [];

    protected $gaAccount;

    protected $gaClientId;

    /**
     * Construct a lilURL object
     */
    public function __construct($host, $user, $pass, $schema)
    {
        $this->db = new PDO(sprintf('mysql:host=%s;dbname=%s', $host, $schema), $user, $pass);
    }

    protected function executeQuery($sql, $params = [])
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    public function setUrlTable($table)
    {
        $this->urlTable = $table;
        return $this;
    }

    public function getUrlTable()
    {
        return $this->urlTable;
    }

    /**
     * Redirect the client to the appropriate URL
     *
     * @param string $id tinyurl id
     *
     * @return false on error
     */
    public function handleRedirect($id)
    {
        // if the id isn't empty and it's not this file, redirect to it's url
        if ($id != '' && $id != basename($_SERVER['PHP_SELF']) && $id != '?login') {
            $location = $this->getURL($id);
            if ($location != false) {
                $this->trackHit($id);
                header('Location: '.$location);
                exit();
            }
        }
        return false;
    }

    protected function trackHit($id)
    {
        // track system redirect
        $this->incrementRedirectCount($id);

        $accountId = $this->getGaAccount();
        if (!$accountId) {
            return false;
        }

        $clientId = $this->getGaClientId() ?: (string) Uuid::uuid4();

        try {
            $analytics = new Analytics(true);
            $analytics
                ->setProtocolVersion('1')
                ->setTrackingId($accountId)
                ->setClientId($clientId)
                ->setIpOverride($_SERVER['REMOTE_ADDR'])
                ->setUserAgentOverride($_SERVER['HTTP_USER_AGENT'])
                ->setDocumentHostName($_SERVER['HTTP_HOST'])
                ->setDocumentPath('/' . $id);

            if (!empty($_SERVER['HTTP_REFERER'])) {
                $analytics->setDocumentReferrer($_SERVER['HTTP_REFERER']);
            }

            $response = $analytics->sendPageview();
        } catch (Exception $e) {
            // just ignore it for now
        }

        return true;
    }

    /**
     * handles adding a new url
     *
     * @return string The URL
     */
    public function handlePOST($mode, $id = null, $user = null)
    {
        $this->clearErrorPOST();

        $longurl = trim(filter_input(INPUT_POST,'theURL', FILTER_SANITIZE_URL));

        // Hack to handle url passed via url=referer query string not handled by filter_input above
        if (empty($longurl) && !empty($_GET['url']) && strtolower($_GET['url']) === 'referer' && !empty($_POST['theURL'])) {
          $longurl = filter_var($_POST['theURL'], FILTER_SANITIZE_URL);
        }

        //Start by gathering all the GA items
        if (isset($_POST['with-ga-campaign'])) {
            $utmData =[
                'utm_source' => filter_input(INPUT_POST, 'gaSource', FILTER_SANITIZE_STRING),
                'utm_medium' => filter_input(INPUT_POST, 'gaMedium', FILTER_SANITIZE_STRING),
                'utm_term' => filter_input(INPUT_POST, 'gaTerm', FILTER_SANITIZE_STRING),
                'utm_content' => filter_input(INPUT_POST, 'gaContent', FILTER_SANITIZE_STRING),
                'utm_campaign' => filter_input(INPUT_POST, 'gaName', FILTER_SANITIZE_STRING),
            ];

            /*
             * Verify GA data
             */
            if (!$this->validateGAData($utmData)) {
                $this->setErrorPOST();
                throw new Exception('Invalid Google Campaign Data', self::ERR_INVALID_GA_CAMPAIGN);
            }

            $gaTags = http_build_query($utmData);

            $longurl .= (strpos(filter_input(INPUT_POST,'theURL', FILTER_SANITIZE_URL), '?') !== false) ? '&' : '?';
            $longurl .= $gaTags;
        }

	      $groupID = filter_input(INPUT_POST,'groupID', FILTER_SANITIZE_NUMBER_INT);

        // Check to see if the URL is allowed
        if (!$this->urlIsAllowed($longurl)) {
            $this->setErrorPOST();
            throw new Exception('Invalid Protocol', self::ERR_INVALID_PROTOCOL);
        }

        // Check to see if the URL is valid
        if (!$this->isSafeURL($longurl)) {
            $this->setErrorPOST();
            throw new Exception('Invalid URL.', self::ERR_INVALID_URL);
        }

        // Check to see if user domain is valid
        if (!$user) {
            if (!$this->urlIsAllowedDomain($longurl)) {
                $this->setErrorPOST();
                throw new Exception('Invalid domain.', self::ERR_INVALID_DOMAIN);
            }
        }

        //validate the alias if specified (data integrity)
        if (!empty($id) && !preg_match('/^[\w\-]+$/', $id)) {
            $this->setErrorPOST();
            throw new Exception('Invalid custom alias.', self::ERR_INVALID_ALIAS);
        }

        if ($mode !== 'edit') {
            //make sure alias isn't already in use
            if (empty($this->getURL($id)) === false) {
                $this->setErrorPOST();
                throw new Exception('Alias is already in use. Please use a different alias.', self::ERR_ALIAS_EXISTS);
            }

            // Check to see if the pair already exists in db
            if ($this->getIDandURL($id, $longurl) !== false) {
                $this->setErrorPOST();
                throw new Exception('This alias/URL pair already exists.', self::ERR_USED);
            }
        }

        // add the url to the database
        if ($mode === 'edit') {
            $this->updateURL($longurl, $id, $user, $groupID);
            return $this->getShortURL($id);
        } else {
            if ($id = $this->addURL($longurl, $id, $user, $groupID)) {
                return $this->getShortURL($id);
            }
        }
        // Generic Exception
        $this->setErrorPOST();
        throw new Exception('Unknown error', self::ERR_UNKNOWN);
    }

    public function clearErrorPOST() {
        $_SESSION['errorPost'] = NULL;
    }

    public function setErrorPOST() {
        $filterArgs = array(
            'mode' => FILTER_SANITIZE_STRING,
            'theURL' => FILTER_SANITIZE_URL,
            'theAlias' => FILTER_SANITIZE_STRING,
            'with-ga-campaign' => FILTER_SANITIZE_NUMBER_INT,
            'gaName' => FILTER_SANITIZE_STRING,
            'gaMedium' => FILTER_SANITIZE_STRING,
            'gaSource' => FILTER_SANITIZE_STRING,
            'gaTerm' => FILTER_SANITIZE_STRING,
            'gaContent'=> FILTER_SANITIZE_STRING
        );
        $_SESSION['errorPost'] = filter_input_array(INPUT_POST, $filterArgs);
    }

    public function getShortURL($id)
    {
        $protocol = 'http://';
        if (defined('HTTPS_SHORT_URLS') && HTTPS_SHORT_URLS) {
          $protocol = 'https://';
        }
        $url = $protocol.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
        $url = trim($url, '/').'/'.$id;
        return $url;
    }

    protected function getRootPath()
    {
        $root = dirname($_SERVER['PHP_SELF']);

        if ('/' === $root) {
            return '';
        }

        return $root;
    }

    public function getBaseUrl($path = '')
    {
        $path = ltrim($path, '/');
        return $this->getRootPath() . '/' . $path;
    }

    public function escapeURL($url) {
        return htmlentities($url, ENT_COMPAT|ENT_HTML5);
    }

    public function getRequestPath()
    {
        $requestURI = $_SERVER['REQUEST_URI'];

        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestURI = substr($requestURI, 0, -strlen($_SERVER['QUERY_STRING']) - 1);
        }

        return substr($requestURI, strlen($this->getRootPath()) + 1);
    }

    public function isSafeURL($url)
    {
        if (strip_tags($url) != $url) {
            return false;
        }

        if (strip_tags(urldecode($url)) != urldecode($url)) {
            return false;
        }

        if (strip_tags(html_entity_decode($url)) != html_entity_decode($url)) {
            return false;
        }

        if (strpos($url, $_SERVER['HTTP_HOST']) !== false) {
            return false;
        }

        return true;
    }

    protected function validateGAData($utmData) {
        return(!empty($utmData['utm_source']) && !empty($utmData['utm_campaign']) && !empty($utmData['utm_medium']));
    }

    /**
     * check to make sure the user's url is allowed for non-authenticated users.
     */
    protected function urlIsAllowedDomain($url)
    {
        $parseUrl = parse_url(trim($url));

        if (!$parseUrl || !$this->allowed_domains) {
            return false;
        }

        $attemptedHostName = strtolower(trim($parseUrl['host']));
        $escapedDomains = [];

        foreach ($this->allowed_domains as $domain) {
            $escapedDomains[] = preg_quote(strtolower($domain), '/');
        }

        return preg_match('/(?:^|\\.)(?:' . implode('|', $escapedDomains) . ')$/', $attemptedHostName);
    }
    /**
     * check to make sure that the user's url is allowed
     *
     * @param string $url
     *
     * @return bool
     */
    protected function urlIsAllowed($url)
    {
        if (!count($this->allowed_protocols)) {
            return true;
        }

        foreach ($this->allowed_protocols as $ap) {
            if (strtolower(substr($url, 0, strlen($ap))) == strtolower($ap)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the id for a given url (or -1 if the url doesn't exist)
     *
     * @param string $url URL to check for an id
     *
     * @return int
     */
    public function getID($url)
    {
        $sql = 'SELECT urlID FROM '.$this->getUrlTable().' WHERE longURL = ' . self::PDO_PLACEHOLDER_LONG_URL . ' AND (createdBy = ' . self::PDO_PLACEHOLDER_CREATED_BY . ' OR createdBy IS NULL) ';
        $statement = $this->executeQuery($sql, [
	          self::PDO_PLACEHOLDER_LONG_URL => $url,
	          self::PDO_PLACEHOLDER_CREATED_BY => '',
        ]);
        $row = $statement->fetch(PDO::FETCH_OBJ);

        if ($row) {
            return $row->urlID;
        }

        return false;
    }

    public function getLinkRow($id, $fields = [], $pdoFormat = PDO::FETCH_ASSOC)
    {
        if (!$fields) {
            $fields = ['*'];
        }

        $sql = 'SELECT ' . implode(',', $fields) . ' FROM '.$this->getUrlTable().' WHERE urlID = ' . self::PDO_PLACEHOLDER_URL_ID;
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_URL_ID => $id,
        ]);
        $row = $statement->fetch($pdoFormat);

        if ($row) {
					if ($pdoFormat === PDO::FETCH_ASSOC) {
						if (isset($row['submitDate']) && $row['submitDate'] !== '0000-00-00 00:00:00') {
							$row['submitDateTime'] = new DateTime($row['submitDate']);
						}
					} elseif ($pdoFormat === PDO::FETCH_OBJ) {
						if (isset($row->submitDate) && $row->submitDate !== '0000-00-00 00:00:00') {
							$row->submitDateTime = new DateTime($row->submitDate);
						}
					}

					return $row;
				}

        return false;
    }

    /**
     * return the url for a given id
     * @param string $id The id of the URL to find.
     * @return string|false
     */
    public function getURL($id)
    {
        $row = $this->getLinkRow($id, ['longURL'], PDO::FETCH_OBJ);

        if ($row) {
            return $row->longURL;
        }

        return false;
    }

    /**
     * return the URL owner/creator for a given id
     * @param string $id The id of the URL owner/creator to find.
     * @return string|false
     */
    public function getCreator($id)
    {
        $row = $this->getLinkRow($id, ['createdBy'], PDO::FETCH_OBJ);

        if ($row) {
            return $row->createdBy;
        }

        return false;
    }

    public function getIDandURL($id, $url)
    {
        $sql = 'SELECT urlID FROM '.$this->getUrlTable().' WHERE urlID = ' . self::PDO_PLACEHOLDER_URL_ID. ' AND longURL = ' . self::PDO_PLACEHOLDER_LONG_URL;
        $statement = $this->executeQuery($sql, [
	          self::PDO_PLACEHOLDER_URL_ID => $id,
	          self::PDO_PLACEHOLDER_LONG_URL => $url,
        ]);
        $row = $statement->fetch(PDO::FETCH_OBJ);

        if ($row) {
            return $row->urlID;
        }

        return false;
    }

    /**
     * add a url to the database
     *
     * @param string $url URL to add
     * @return bool
     */
    public function addURL($url, $id = null, $user = null, $groupID = null)
    {
        if (!$id) {
            // if the url is already in here, return true
            if ($existing_id = $this->getID($url)) {
                return $existing_id;
            }

            $id = $this->getRandomID();
        } elseif ($existing_id = $this->getIDandURL($id, $url)) {
            return $existing_id;
        }

        $id = strtolower($id);

        $sql = 'INSERT INTO '.$this->getUrlTable().' (urlID, longURL, submitDate, createdBy, groupID) VALUES (:urlID, :longURL, NOW(), :createdBy, :groupID)';
        $statement = $this->executeQuery($sql, [
            ':urlID' => $id,
            ':longURL' => $url,
            ':createdBy' => $user,
	          ':groupID' => $groupID
        ]);
        $result = $statement->rowCount();

        if ($result) {
            return $id;
        }

        return false;
    }

    /**
     * update a url in the database
     *
     * @param string $url URL to add
     * @return bool
     */
    public function updateURL($url, $id = null, $user = null, $groupID = null)
    {
        $id = strtolower($id);

        $sql = 'UPDATE '.$this->getUrlTable().' set longURL = ' . self::PDO_PLACEHOLDER_LONG_URL . ',
          groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID . ' WHERE urlID = ' . self::PDO_PLACEHOLDER_URL_ID;
        $statement = $this->executeQuery($sql, [
	          self::PDO_PLACEHOLDER_LONG_URL => $url,
	          self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
	          self::PDO_PLACEHOLDER_URL_ID => $id
        ]);
        return $id;
    }

    /**
     * Set the list of allowed protocols.
     *
     * @param array $protocols Protocols/prefixes to allow
     *
     * @return void
     */
    public function setAllowedProtocols($protocols)
    {
        if (count($protocols)) {
            $this->allowed_protocols = (array) $protocols;
        }
        return $this;
    }

    /**
     * Set the list of allowed domains.
     *
     * @param array $domains domains to allow
     *
     * @return void
     */
    public function setAllowedDomains($domains)
    {
        if (count($domains)) {
            $this->allowed_domains = (array) $domains;
        }
        return $this;
    }

    /**
     * Get the list of allowed domains.
     *
     * @return void
     */
    public function getAllowedDomains()
    {
       return $this->allowed_domains;
    }

    /**
     * Returns a random ID
     *
     * @return string
     */
    public function getRandomID($attempts = 0, $lengthOverride = 0)
    {
        $attempts++;
        if ($attempts > self::MAX_RANDOM_ID_ATTEMPTS) {
             throw new Exception('Failed to generate random unique alias after ' . self::MAX_RANDOM_ID_ATTEMPTS . ' attempts.  You can try again or provide custom alias.', self::ERR_MAX_RANDOM_ID_ATTEMPTS);
        }

        $possibleCharacters = 'abcdefghijkmnopqrstuvwxyz234567890';
        $length = !empty($lengthOverride) && is_int($lengthOverride) ? $lengthOverride : self::$random_id_length;
        if ($length < self::MAX_RANDOM_ID_BUMP_LENGTH && $attempts % self::RANDOM_ID_ATTEMPTS_THERSHOLD == 0) {
            $length++;
        }
        $string = substr(str_shuffle($possibleCharacters),0, $length);

        if (false === $this->getURL($string)) {
            return $string;
        }

        return $this->getRandomID($attempts, $length);
    }

    public function setRandomIDLength($length)
    {
        self::$random_id_length = (int)$length;
    }

    public function getUserURLs($uid)
    {
        $sql = '(SELECT * FROM '.$this->getUrlTable().' WHERE createdBy = ' . self::PDO_PLACEHOLDER_CREATED_BY . ' ORDER BY urlID)
          UNION
          (SELECT u.* FROM '.$this->getUrlTable(). ' u INNER JOIN tblGroupUsers ug on u.groupID = ug.groupID WHERE ug.uid = ' . self::PDO_PLACEHOLDER_UID . ' ORDER BY urlID)';
        return $this->executeQuery($sql, [
	          self::PDO_PLACEHOLDER_CREATED_BY => $uid,
	          self::PDO_PLACEHOLDER_UID => $uid
        ]);
    }

    public function userHasURLAccess($urlID, $uid) {
	    $sql = 'SELECT count(*) AS accessCount FROM '.$this->getUrlTable(). ' u INNER JOIN tblGroupUsers ug on u.groupID = ug.groupID
	      WHERE u.urlID = ' . self::PDO_PLACEHOLDER_URL_ID . ' AND ug.uid = ' . self::PDO_PLACEHOLDER_UID;
	    $statement = $this->executeQuery($sql, [
		    self::PDO_PLACEHOLDER_URL_ID => $urlID,
		    self::PDO_PLACEHOLDER_UID => $uid,
	    ]);
	    $result = $statement->fetch(PDO::FETCH_OBJ);
	    return $result->accessCount > 0;
    }

    public function deleteURL($urlID)
    {
        $sql = 'DELETE FROM '.$this->getUrlTable().' WHERE urlID = ' . self::PDO_PLACEHOLDER_URL_ID . ' LIMIT 1';
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_URL_ID => $urlID
        ]);
        return $statement->rowCount();
    }

    public function resetRedirectCount($id) {
        $sql = 'UPDATE '.$this->getUrlTable().' set redirects = 0 where urlID = ' . self::PDO_PLACEHOLDER_URL_ID;
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_URL_ID => $id
        ]);
        return $statement->rowCount();
    }

    public function getGroup($id)
    {
        $sql = 'SELECT * FROM tblGroups WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID;
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_GROUP_ID => $id,
        ]);
        $result = $statement->fetch(PDO::FETCH_OBJ);
        return $result === FALSE ? NULL: $result;
    }

    public function getUserGroups($uid)
    {
        $sql = 'SELECT g.groupID, g.groupName FROM tblGroups g
            INNER JOIN tblGroupUsers gu ON g.groupID = gu.groupID
            WHERE gu.uid = ' . self::PDO_PLACEHOLDER_UID . '
            ORDER BY g.groupName';
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_UID => $uid,
        ]);
	    $result = $statement->fetchAll(PDO::FETCH_OBJ);
	    return $result === FALSE ? NULL: $result;
    }

		public function getGroupUsers($groupID)
		{
			$sql = 'SELECT uid from tblGroupUsers WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID . ' ORDER BY uid';
			$statement = $this->executeQuery($sql, [
				self::PDO_PLACEHOLDER_GROUP_ID => $groupID
			]);
			$result = $statement->fetchAll(PDO::FETCH_OBJ);
			return $result === FALSE ? NULL: $result;
		}

		public function isValidGroupUser($uid, &$error = '') {
			if (empty(trim($uid))) {
				$error = 'A user must have a username.';
			} elseif (!preg_match('/^\w+$/', $uid)) {
				$error = 'Invalid username format. Allows alphanumeric and underscore.';
			}
			return empty($error);
		}

		public function insertGroupUser($groupID, $uid, $adminUID)
		{
			if (!empty($uid) && $this->isGroupMember($groupID, $adminUID)) {
				$sql = 'INSERT INTO tblGroupUsers (groupID, uid) VALUES (' . self::PDO_PLACEHOLDER_GROUP_ID . ', ' . self::PDO_PLACEHOLDER_UID . ')';
				$statement = $this->executeQuery($sql, [
					self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
					self::PDO_PLACEHOLDER_UID => $uid
				]);
				return $statement->rowCount();
			}
			return false;
		}

		public function deleteGroupUser($groupID, $uid, $adminUID)
		{
			if ($this->isGroupMember($groupID, $adminUID)) {
				$sql = 'DELETE FROM tblGroupUsers WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID . ' AND uid = ' . self::PDO_PLACEHOLDER_UID;
				$statement = $this->executeQuery($sql, [
					self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
					self::PDO_PLACEHOLDER_UID => $uid
				]);
				return $statement->rowCount();
			}
			return false;
		}

    public function isGroup($groupID) {
      $sql = 'SELECT count(*) as isGroupCount from tblGroups WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID;
      $statement = $this->executeQuery($sql, [
	      self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
      ]);
      $result = $statement->fetch(PDO::FETCH_OBJ);
      return $result->isGroupCount > 0;
    }

    public function isGroupMember($groupID, $uid) {
      $sql = 'SELECT count(*) AS isMemberCount FROM tblGroupUsers WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID . ' AND uid = ' . self::PDO_PLACEHOLDER_UID;
      $statement = $this->executeQuery($sql, [
	      self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
	      self::PDO_PLACEHOLDER_UID => $uid,
      ]);
      $result = $statement->fetch(PDO::FETCH_OBJ);
      return $result->isMemberCount > 0;
    }

    public function isValidGroupName($groupName, $groupID = 0, &$error = '') {
      if (empty(trim($groupName))) {
        $error = 'A group must have a name.';
      }
      if (empty(trim($groupID))) {
        $groupID = 0;
      }

      $sql = 'SELECT count(*) AS isGroupCount FROM tblGroups WHERE groupID != ' . self::PDO_PLACEHOLDER_GROUP_ID . ' AND groupName = ' . self::PDO_PLACEHOLDER_GROUP_NAME;
      $statement = $this->executeQuery($sql, [
	      self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
	      self::PDO_PLACEHOLDER_GROUP_NAME => trim($groupName)
      ]);
      $result = $statement->fetch(PDO::FETCH_OBJ);

      if ($result->isGroupCount > 0) {
        $error = 'A group must have an unique name.';
      }

      return empty($error);
    }

    public function insertGroup($group, $uid)
    {
      if (!empty($uid) && !empty(trim($group['groupName']))) {
        $sql = 'INSERT INTO tblGroups (groupName) VALUES (' . self::PDO_PLACEHOLDER_GROUP_NAME . ')';
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_GROUP_NAME => trim($group['groupName'])
        ]);
        if ($statement->rowCount()) {
          $groupID = $this->db->lastInsertId();
          $sql = 'INSERT INTO tblGroupUsers (groupID, uid) VALUES (' . self::PDO_PLACEHOLDER_GROUP_ID . ', ' . self::PDO_PLACEHOLDER_UID . ')';
          $statement2 = $this->executeQuery($sql, [
	          self::PDO_PLACEHOLDER_GROUP_ID => $groupID,
            self::PDO_PLACEHOLDER_UID => $uid
          ]);
        }
        return $statement2->rowCount();
      }
      return false;
    }

    public function updateGroup($group, $uid)
    {
      if ($this->isGroupMember($group['groupID'], $uid)) {
        $sql = 'UPDATE tblGroups SET groupName = ' . self::PDO_PLACEHOLDER_GROUP_NAME . ' WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID;
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_GROUP_NAME => trim($group['groupName']),
	        self::PDO_PLACEHOLDER_GROUP_ID => $group['groupID']
        ]);
        return $statement->rowCount();
      }
      return false;
    }

    public function deleteGroup($groupID, $uid)
    {
      if ($this->isGroupMember($groupID, $uid)) {
        $sql = 'DELETE FROM tblGroupUsers WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID;
        $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_GROUP_ID => $groupID
        ]);
        $sql = 'DELETE FROM tblGroups WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID;
        $statement2 = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_GROUP_ID => $groupID
        ]);
        return $statement2->rowCount();
      }
      return false;
    }

    public function getGaAccount()
    {
        return $this->gaAccount;
    }

    public function setGaAccount($account)
    {
        $this->gaAccount = $account;
        return $this;
    }

    public function getGaClientId()
    {
        return $this->gaClientId;
    }

    public function setGaClientId($clientId)
    {
        $this->gaClientId = $clientId;
        return $this;
    }

    private function incrementRedirectCount($id) {
        $sql = 'UPDATE '.$this->getUrlTable().' set redirects = redirects + 1 where urlID = ' . self::PDO_PLACEHOLDER_URL_ID;
        $statement = $this->executeQuery($sql, [
	        self::PDO_PLACEHOLDER_URL_ID => $id,
        ]);
        return $statement->rowCount();
    }
}
