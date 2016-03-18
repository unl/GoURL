<?php
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

    protected $db;

    protected $urlTable = 'tblURLs';

    protected static $random_id_length = 4;

    protected $allowed_protocols = [];

    protected $allowed_domains = [];

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
     * Redirect the clien to the appropriate URL
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
                header('Location: '.$location);
                exit();
            }
        }
        return false;
    }

    /**
     * handles adding a new url
     *
     * @return string The URL
     */
    public function handlePOST($id = null, $user = null)
    {
        $longurl = trim($_POST['theURL']);

        //Start by gathering all the GA items
        if (!empty($_POST['gaSource'])) {
            $gaTags = http_build_query([
                'utm_source' => $_POST['gaSource'],
                'utm_medium' => $_POST['gaMedium'],
                'utm_term' => $_POST['gaTerm'],
                'utm_content' => $_POST['gaContent'],
                'utm_campaign' => $_POST['gaName'],
            ]);

            $longurl .= (strpos($_POST['theURL'], '?') !== false) ? '&' : '?';
            $longurl .= $gaTags;
        }


        // Check to see if the URL is allowed
        if (!$this->urlIsAllowed($longurl)) {
            throw new Exception('Invalid Protocol', self::ERR_INVALID_PROTOCOL);
        }

        // Check to see if the URL is valid
        if (!$this->isSafeURL($longurl)) {
            throw new Exception('Invalid URL.');
        }

        // Check to see if user domain is valid
        if (!$user) {
            if (!$this->urlIsAllowedDomain($longurl)) {
                throw new Exception('Invalid domain.', self::ERR_INVALID_DOMAIN);
            }
        }

        //validate the alias if specified (data integrity)
        if (!empty($id) && !preg_match('/^[\w\-]+$/', $id)) {
            throw new Exception('Invalid custom alias.', self::ERR_INVALID_ALIAS);
        }

        //make sure alias isn't already in use
        if (empty($this->getURL($id)) == false) {
            throw new Exception('Alias is already in use. Please use a different alias.', self::ERR_ALIAS_EXISTS);
        }

        // Check to see if the pair already exists in db
        if ($this->getIDandURL($id, $longurl) !== false) {
            throw new Exception('This alias/URL pair already exists.', self::ERR_USED);
        }

        // add the url to the database
        if ($id = $this->addURL($longurl, $id, $user)) {
            return $this->getShortURL($id);
        }
        // Generic Exception
        throw new Exception('Unknown error', self::ERR_UNKNOWN);
    }

    public function getShortURL($id)
    {
        $url = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
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

        if (strpos($url,'http://go.unl.edu/') !== false) {
            return false;
        }

        return true;
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
        $sql = 'SELECT urlID FROM '.$this->getUrlTable().' WHERE longURL = :longURL AND (createdBy = :createdBy OR createdBy IS NULL) ';
        $statement = $this->executeQuery($sql, [
            ':longURL' => $url,
            ':createdBy' => '',
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['urlID'];
        }

        return false;
    }

    public function getLinkRow($id, $fields = [])
    {
        if (!$fields) {
            $fields = ['*'];
        }

        $sql = 'SELECT ' . implode(',', $fields) . ' FROM '.$this->getUrlTable().' WHERE urlID = :urlID';
        $statement = $this->executeQuery($sql, [
            ':urlID' => $id,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row) {
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
        $row = $this->getLinkRow($id, ['longURL']);

        if ($row) {
            return $row['longURL'];
        }

        return false;
    }

    public function getIDandURL($id, $url)
    {
    	$sql = 'SELECT urlID FROM '.$this->getUrlTable().' WHERE urlID = :urlID AND longURL = :longURL';
        $statement = $this->executeQuery($sql, [
            ':urlID' => $id,
            ':longURL' => $url,
        ]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['urlID'];
        }

        return false;
    }

    /**
     * add a url to the database
     *
     * @param string $url URL to add
     * @return bool
     */
    public function addURL($url, $id = null, $user = null)
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

        $sql = 'INSERT INTO '.$this->getUrlTable().' (urlID, longURL, submitDate, createdBy) VALUES (:urlID, :longURL, NOW(), :createdBy)';
        $statement = $this->executeQuery($sql, [
            ':urlID' => $id,
            ':longURL' => $url,
            ':createdBy' => $user,
        ]);
        $result = $statement->rowCount();

        if ($result) {
            return $id;
        }

        return false;
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
     * Returns a random ID
     *
     * @return string
     */
    public function getRandomID()
    {
        mt_srand();
        $possible_characters = 'abcdefghijkmnopqrstuvwxyz234567890';
        $string = '';

        while (strlen($string) < self::$random_id_length) {
            $string .= substr($possible_characters, rand() % (strlen($possible_characters)),1);
        }

        if (false === $this->getURL($string)) {
            return $string;
        }

        return $this->getRandomID();
    }

    public function setRandomIDLength($length)
    {
        self::$random_id_length = (int)$length;
    }

    public function getUserURLs($user)
    {
        $sql = 'SELECT * FROM '.$this->getUrlTable().' WHERE createdBy = :createdBy';
        return $this->executeQuery($sql, [
            ':createdBy' => $user,
        ]);
    }

    public function deleteURL($urlID, $user)
    {
        $sql = 'DELETE FROM '.$this->getUrlTable().' WHERE urlID = :urlID AND createdBy = :createdBy LIMIT 1';
        $statement = $this->executeQuery($sql, [
            ':urlID' => $urlID,
            ':createdBy' => $user,
        ]);
        return $statement->rowCount();
    }
}
