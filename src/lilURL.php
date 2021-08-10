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
	const ERR_ACCESS_DENIED = -10;

	const RANDOM_ID_ATTEMPTS_THERSHOLD = 1000000;
	const MAX_RANDOM_ID_BUMP_LENGTH = 5;
	const MAX_RANDOM_ID_ATTEMPTS = 15000000;

	// Tables
	const TABLE_GROUPS = 'tblGroups';
	const TABLE_GROUP_USERS = 'tblGroupUsers';
	const TABLE_URLS = 'tblURLs';

	// Table Column Placeholders
	const PDO_PLACEHOLDER_URL_ID = ':urlID';
	const PDO_PLACEHOLDER_LONG_URL = ':longURL';
	const PDO_PLACEHOLDER_CREATED_BY = ':createdBy';
	const PDO_PLACEHOLDER_REDIRECTS = ':redirects';
	const PDO_PLACEHOLDER_GROUP_ID = ':groupID';
	const PDO_PLACEHOLDER_GROUP_NAME = ':groupName';
	const PDO_PLACEHOLDER_UID = ':uid';

	// Common where string segements
	const WHERE_GROUP_ID = 'groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID;
	const WHERE_URL_ID = 'urlID = ' . self::PDO_PLACEHOLDER_URL_ID;
	const WHERE_UID = 'uid = ' . self::PDO_PLACEHOLDER_UID;

	protected $db;
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
		$this->db = new PdoDB(sprintf('mysql:host=%s;dbname=%s', $host, $schema), $user, $pass);
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
	public function getID(string $url)
	{
		$result = $this->db->select(
			self::TABLE_URLS,
			'longURL = ' . self::PDO_PLACEHOLDER_LONG_URL . ' AND (createdBy = ' . self::PDO_PLACEHOLDER_CREATED_BY . ' OR createdBy IS NULL)',
			array(self::PDO_PLACEHOLDER_LONG_URL => $url,self::PDO_PLACEHOLDER_CREATED_BY => ''),
			'urlID'
		);
		return !empty($result) && !empty($result->urlID) ? $result->urlID : FALSE;
	}

	public function getLinkRow($id, $fields = [], $pdoFormat = PDO::FETCH_OBJ)
	{
		if (!$fields) {
			$fields = ['*'];
		}

		$row = $this->db->select(
			self::TABLE_URLS,
			self::WHERE_URL_ID,
			array(self::PDO_PLACEHOLDER_URL_ID => $id),
			implode(',', $fields),
			TRUE,
			$pdoFormat
		);

		return (!empty($row)) ? $row : FALSE;
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
		$result = $this->db->select(
			self::TABLE_URLS,
			self::WHERE_URL_ID . ' AND longURL = ' . self::PDO_PLACEHOLDER_LONG_URL,
			array(self::PDO_PLACEHOLDER_URL_ID => $id, self::PDO_PLACEHOLDER_LONG_URL => $url),
			'urlID'
		);
		return !empty($result) && !empty($result->urlID) ? $result->urlID : FALSE;
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

		$data = array(
			ltrim(self::PDO_PLACEHOLDER_URL_ID, ':') => $id,
			ltrim(self::PDO_PLACEHOLDER_LONG_URL, ':') => $url
		);

		if (!empty($user)) {
			$data[ ltrim(self::PDO_PLACEHOLDER_CREATED_BY, ':')] = $user;
		}

		if (!empty($groupID)) {
			$data[ltrim(self::PDO_PLACEHOLDER_GROUP_ID, ':')] = $groupID;
		}

		$result = $this->db->insert(self::TABLE_URLS, $data);
		return !empty($result) ? $id : FALSE;
	}

	/**
	* update a url in the database
	*
	* @param string $url URL to add
	* @return bool
	*/
	public function updateURL($url, $id = null, $uid = null, $groupID = null)
	{
		if (!$this->userHasURLAccess($id, $uid)) {
			return FALSE;
		}

		$id = strtolower($id);
		$result = $this->db->update(
			self::TABLE_URLS,
			array(
				ltrim(self::PDO_PLACEHOLDER_LONG_URL, ':') => $url,
				ltrim(self::PDO_PLACEHOLDER_GROUP_ID, ':') => $groupID
			),
			self::WHERE_URL_ID,
			array(self::PDO_PLACEHOLDER_URL_ID => $id)
		);

		return !empty($result) ? $id : FALSE;
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
		return $this->db->run(
			'(SELECT u.*, g.groupName FROM ' . self::TABLE_URLS . ' u LEFT JOIN ' . self::TABLE_GROUPS . ' g on u.groupID = g.groupID WHERE u.createdBy = ' . self::PDO_PLACEHOLDER_CREATED_BY . ' ORDER BY u.urlID)
			UNION
			(SELECT u.*, g.groupName FROM ' . self::TABLE_URLS . ' u  LEFT JOIN ' . self::TABLE_GROUPS . ' g on u.groupID = g.groupID INNER JOIN ' .   self::TABLE_GROUP_USERS . ' ug on u.groupID = ug.groupID WHERE ug.uid = ' . self::PDO_PLACEHOLDER_UID . ' ORDER BY u.urlID)',
			array(self::PDO_PLACEHOLDER_CREATED_BY => $uid, self::PDO_PLACEHOLDER_UID => $uid)
		);
	}

	public function userOwnsURL($urlID, $uid) {
		$result = $this->db->run(
			'SELECT count(*) AS ownsCount FROM ' . self::TABLE_URLS . ' WHERE ' . self::WHERE_URL_ID. ' AND createdBy = ' . self::PDO_PLACEHOLDER_CREATED_BY,
			array(self::PDO_PLACEHOLDER_URL_ID => $urlID, self::PDO_PLACEHOLDER_CREATED_BY => $uid),
			TRUE
		);
		return $result->ownsCount > 0;
	}

	public function userHasGroupURLAccess($urlID, $uid) {
		$result = $this->db->run(
			'SELECT count(*) AS accessCount FROM ' . self::TABLE_URLS . ' u INNER JOIN tblGroupUsers ug on u.groupID = ug.groupID WHERE ' . self::WHERE_URL_ID . ' AND ug.uid = ' . self::PDO_PLACEHOLDER_UID,
			array(self::PDO_PLACEHOLDER_URL_ID => $urlID, self::PDO_PLACEHOLDER_UID => $uid),
			TRUE
		);
		return $result->accessCount > 0;
	}

	public function userHasURLAccess($urlID, $uid) {
		return $this->userOwnsURL($urlID, $uid) || $this->userHasGroupURLAccess($urlID, $uid);
	}

	public function deleteURL($urlID)
	{
		return $this->db->delete(
			self::TABLE_URLS,
			self::WHERE_URL_ID . ' LIMIT 1',
			array(self::PDO_PLACEHOLDER_URL_ID => $urlID)
		);
	}

	public function resetRedirectCount($id) {
		return $this->db->update(
			self::TABLE_URLS,
			array(ltrim(self::PDO_PLACEHOLDER_REDIRECTS, ':') => 0),
			self::WHERE_URL_ID,
			array(self::PDO_PLACEHOLDER_URL_ID => $id)
		);
	}

	public function getGroup($id)
	{
		return $this->db->select(
		self::TABLE_GROUPS,
		self::WHERE_GROUP_ID,
		array(self::PDO_PLACEHOLDER_GROUP_ID => $id),
		'*',
		TRUE);
	}

	public function getUserGroups($uid)
	{
		return $this->db->run(
			'SELECT g.groupID, g.groupName FROM tblGroups g INNER JOIN tblGroupUsers gu ON g.groupID = gu.groupID WHERE gu.uid = ' . self::PDO_PLACEHOLDER_UID . ' ORDER BY g.groupName',
			array(self::PDO_PLACEHOLDER_UID => $uid)
		);
	}

	public function getGroupUsers($groupID)
	{
		return $this->db->select(
			self::TABLE_GROUP_USERS,
			self::WHERE_GROUP_ID . ' ORDER BY uid',
			array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID)
		);
	}

	public function isValidGroupUser($uid, &$error = '') {
		if (empty(trim($uid))) {
			$error = 'A user must have a username.';
		} elseif (!preg_match('/^[\w\.]+$/', $uid)) {
			$error = 'Invalid username format. Allows alphanumeric, underscore and period.';
		}
		return empty($error);
	}

	public function insertGroupUser($groupID, $uid, $adminUID)
	{
		if (!empty($uid) && $this->isGroupMember($groupID, $adminUID)) {
			return $this->db->insert(
				self::TABLE_GROUP_USERS,
				array(
					ltrim(self::PDO_PLACEHOLDER_GROUP_ID, ':') => $groupID,
					ltrim(self::PDO_PLACEHOLDER_UID, ':') => $uid
				)
			);
		}
		return false;
	}

	public function deleteGroupUser($groupID, $uid, $adminUID)
	{
		if ($this->mayDeleteGroupUser($groupID, $adminUID)) {
			return $this->db->delete(
				self::TABLE_GROUP_USERS,
				self::WHERE_GROUP_ID. ' AND ' . self::WHERE_UID,
				array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID, self::PDO_PLACEHOLDER_UID => $uid)
			);
		}
		return false;
	}

	public function isGroup($groupID) {
		$result = $this->db->run(
			'SELECT count(*) as isGroupCount from tblGroups WHERE ' . self::WHERE_GROUP_ID,
			array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID),
			TRUE
		);
		return $result->isGroupCount > 0;
	}

	public function mayDeleteGroupUser($groupID, $uid) {
		if ($this->isGroupMember($groupID, $uid)) {
			$result = $this->db->run(
				'SELECT count(*) AS isMemberCount FROM tblGroupUsers WHERE ' . self::WHERE_GROUP_ID . ' AND uid <> ' . self::PDO_PLACEHOLDER_UID,
				array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID, self::PDO_PLACEHOLDER_UID => $uid),
				TRUE
			);
			return $result->isMemberCount > 0;
		}
		return false;
	}

	public function isGroupMember($groupID, $uid) {
		$result = $this->db->run(
			'SELECT count(*) AS isMemberCount FROM tblGroupUsers WHERE ' . self::WHERE_GROUP_ID . ' AND ' . self::WHERE_UID,
			array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID, self::PDO_PLACEHOLDER_UID => $uid),
			TRUE
		);
		return $result->isMemberCount > 0;
	}

	public function isValidGroupName($groupName, $groupID = 0, &$error = '') {
		if (empty(trim($groupName))) {
			$error = 'A group must have a name.';
		}
		if (empty(trim($groupID))) {
			$groupID = 0;
		}

		$result = $this->db->run(
			'SELECT count(*) AS isGroupCount FROM tblGroups WHERE groupID <> ' . self::PDO_PLACEHOLDER_GROUP_ID . ' AND groupName = ' . self::PDO_PLACEHOLDER_GROUP_NAME,
			array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID, self::PDO_PLACEHOLDER_GROUP_NAME => trim($groupName)),
			TRUE
		);

		if ($result->isGroupCount > 0) {
			$error = 'A group must have an unique name.';
		}

		return empty($error);
	}

	public function insertGroup($group, $uid)
	{
		if (!empty($uid) && !empty(trim($group['groupName']))) {
			$result1 = $this->db->insert(
				self::TABLE_GROUPS,
				array(ltrim(self::PDO_PLACEHOLDER_GROUP_NAME, ':') => trim($group['groupName']))
			);

			if ($result1) {
				$groupID = $this->db->lastInsertId();
				if ($groupID > 0) {
					return $this->db->insert(
						self::TABLE_GROUP_USERS,
						array(ltrim(self::PDO_PLACEHOLDER_GROUP_ID, ':') => $groupID, ltrim(self::PDO_PLACEHOLDER_UID, ':') => $uid)
					);
				}
			}
		}
		return false;
	}

	public function updateGroup($group, $uid)
	{
		if ($this->isGroupMember($group['groupID'], $uid)) {
			return $this->db->update(
				self::TABLE_GROUPS,
				array(ltrim(self::PDO_PLACEHOLDER_GROUP_NAME, ':') => trim($group['groupName'])),
				self::WHERE_GROUP_ID,
				array(self::PDO_PLACEHOLDER_GROUP_ID => $group['groupID'])
			);
		}
		return false;
	}

	public function deleteGroup($groupID, $uid)
	{
		if ($this->isGroupMember($groupID, $uid)) {
			// Remove group from any urls
			$this->db->run(
				'UPDATE ' . self::TABLE_URLS . ' SET groupID = NULL WHERE groupID = ' . self::PDO_PLACEHOLDER_GROUP_ID,
				array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID)
			);

			// Remove all group users
			$result1 = $this->db->delete(
				self::TABLE_GROUP_USERS,
				self::WHERE_GROUP_ID,
				array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID)
			);

			if ($result1) {
				// remove group
				return $this->db->delete(
					self::TABLE_GROUPS,
					self::WHERE_GROUP_ID,
					array(self::PDO_PLACEHOLDER_GROUP_ID => $groupID)
				);
			}
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

	public function createDateTimeFromTimestamp($timestamp) {
		if (!empty($timestamp) && $timestamp !== '0000-00-00 00:00:00') {
			try {
				return new DateTime($timestamp);
			} catch (Exception $e) {
				// Do Nothing
			}
		}
		return NULL;
	}

	private function incrementRedirectCount($id) {
		return $this->db->run('UPDATE ' . self::TABLE_URLS . ' SET redirects = redirects + 1, lastRedirect = now() WHERE ' . self::WHERE_URL_ID,
			array(self::PDO_PLACEHOLDER_URL_ID => $id)
		);
	}
}
