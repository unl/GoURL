<?php
/**
 * This class handles the lilURL database interactions.
 * 
 */
class lilURL
{
    const ERR_UNKNOWN          = -1;
    const ERR_INVALID_PROTOCOL = -2;
    
    protected static $random_id_length = 3;
    
    protected $allowed_protocols = array();
    
    /**
     * Construct a lilURL object
     */
    function __construct()
    {
        // open mysql connection
        mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die('Could not connect to database');
        mysql_select_db(MYSQL_DB) or die('Could not select database');
    }
    
    /**
     * Redirect the clien to the appropriate URL
     * 
     * @param string $id tinyurl id
     * 
     * @return false on error
     */
    function handleRedirect($id)
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
    function handlePOST($id = null, $user = null)
    {
        // First, build the $longurl by combining theURL and the GA stuff
        $gaTags = '';    
        
        //Start by gathering all the GA items
        if (!empty($_POST['gaSource'])) {
            $gaTags = 'utm_source='.($_POST['gaSource']);
            $gaTags = $gaTags.'&utm_medium='.($_POST['gaMedium']);
            $gaTags = $gaTags.'&utm_term='.($_POST['gaTerm']);
            $gaTags = $gaTags.'&utm_content='.($_POST['gaContent']);
            $gaTags = $gaTags.'&utm_campaign='.($_POST['gaName']);
        }
        //http://www.unl.edu/?utm_source=source&utm_medium=medium&utm_term=term&utm_content=content&utm_campaign=name
        if (strpbrk($_POST['theURL'], '?')) {
            //if the URL already contains a '?' then add GA stuff with '&' 
            $longurl = $_POST['theURL'].'&'.$gaTags;
        } else {
            // we don't have a '?', so use one in the URL
            $longurl = $_POST['theURL'].'?'.$gaTags;
        }
        
        //escape bad characters from the user's url, and trim extraneous stuff
        $longurl = trim($longurl, ' ?&');

        if (!$this->urlIsAllowed($longurl)) {
            throw new Exception('Invalid Protocol', self::ERR_INVALID_PROTOCOL);
        }
        
        if (!$this->isSafeURL($longurl)) {
            throw new Exception('Indvalid URL.');
        }
        
        // add the url to the database
        if ($id = $this->addURL($longurl, $id, $user)) {
            $url = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
            $url = trim($url, '/').'/'.$id;
            return $url;
        }
        
        throw new Exception('Unknown error', self::ERR_UNKNOWN);
    }
    
    function isSafeURL($url)
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
    function getID($url)
    {
        $url = mysql_escape_string($url);
        $q = 'SELECT urlID FROM '.URL_TABLE.' WHERE (longURL="'.$url.'")';
        $result = mysql_query($q);

        if (mysql_num_rows($result)) {
            $row = mysql_fetch_array($result);
            return $row['urlID'];
        }
        
        return false;
    }

    /**
     * return the url for a given id (or -1 if the id doesn't exist)
     * 
     * @param string $id The id of the URL to find.
     * 
     * @return string
     */
    function getURL($id)
    {
        $id = mysql_escape_string($id);
        $q = 'SELECT longURL FROM '.URL_TABLE.' WHERE (urlID="'.$id.'")';
        $result = mysql_query($q);

        if (mysql_num_rows($result)) {
            $row = mysql_fetch_array($result);
            return $row['longURL'];
        }
        
        return false;
    }
    
    /**
     * add a url to the database
     * 
     * @param string $url URL to add
     * 
     * @return bool
     */
    function addURL($url, $id = null, $user = null)
    {
        // if the url is already in here, return true
        if ($existing_id = $this->getID($url)) {
            // Already in the DB
            return $existing_id;
        }
        
        if ($id == null) {
            $id = $this->getRandomID();
        } else {
            $id = strtolower($id);
        }

        $q = 'INSERT INTO '.URL_TABLE.' (urlID, longURL, submitDate, createdBy)
              VALUES ("'.mysql_escape_string($id).'", "'.mysql_escape_string($url).'", NOW(), "'.mysql_escape_string($user).'")';

        $result = mysql_query($q);
        
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
    function setAllowedProtocols($protocols)
    {
        if (count($protocols)) {
            $this->allowed_protocols = $protocols;
        }
    }
    
    /**
     * Returns a random ID
     * 
     * @return string
     */
    function getRandomID()
    {
        mt_srand();
        $possible_characters = 'abcdefghijkmnopqrstuvwxyz234567890';
        $string = '';
        while (strlen($string) < self::$random_id_length) {
            $string .= substr($possible_characters, rand()%(strlen($possible_characters)),1);
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

}

?>