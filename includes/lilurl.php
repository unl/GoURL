<?php
/**
 * This class handles the lilURL database interactions.
 * 
 */
class lilURL
{
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
     * Return the id for a given url (or -1 if the url doesn't exist)
     * 
     * @param string $url URL to check for an id
     * 
     * @return int
     */
    function get_id($url)
    {
        $q = 'SELECT urlID FROM '.URL_TABLE.' WHERE (longURL="'.$url.'")';
        $result = mysql_query($q);

        if (mysql_num_rows($result)) {
            $row = mysql_fetch_array($result);
            return $row['urlID'];
        } else {
            return -1;
        }
    }

    /**
     * return the url for a given id (or -1 if the id doesn't exist)
     * 
     * @param string|int $id The id of the URL to find.
     * 
     * @return string
     */
    function get_url($id)
    {
        $q = 'SELECT longURL FROM '.URL_TABLE.' WHERE (urlID="'.$id.'")';
        $result = mysql_query($q);

        if (mysql_num_rows($result)) {
            $row = mysql_fetch_array($result);
            return $row['longURL'];
        } else {
            return -1;
        }
    }
    
    /**
     * add a url to the database
     * 
     * @param string $url URL to add
     * 
     * @return bool
     */
    function add_url($url)
    {
        // check to see if the url's already in there
        $id = $this->get_id($url);
        
        // if it is, return true
        if ($id != -1) {
            return true;
        } else {
            // otherwise, put it in
            $id = $this->get_next_id($this->get_last_id());
            //echo($id);
            $q = 'INSERT INTO '.URL_TABLE.' (urlID, longURL, submitDate) VALUES ("'.$id.'", "'.$url.'", NOW())';

            return mysql_query($q);
        }
    }

    /**
     * Return the most recent id (or -1 if no ids exist)
     * 
     * @return int
     */
    function get_last_id()
    {    
        $q = 'SELECT urlID FROM '.URL_TABLE.' ORDER BY submitDate DESC LIMIT 1';
        $result = mysql_query($q)  or die('Query failed: ' . mysql_error());
        
        if (mysql_num_rows($result)) {
            $row = mysql_fetch_array($result);
            return $row['urlID'];
        } else {
            return -1;
        }
    }    

    /**
     * return the next id
     * 
     * @param int $last_id
     * 
     * @return int
     */ 
    function get_next_id($last_id)
    { 
    
        // if the last id is -1 (non-existant), start at the begining with 0
        if ($last_id == -1) {
            $next_id = 0;
        } else {
            // loop through the id string until we find a character to increment
            for ($x = 1; $x <= strlen($last_id); $x++) {
                $pos = strlen($last_id) - $x;

                if ($last_id[$pos] != 'z') {
                    $next_id = $this->increment_id($last_id, $pos);
                    break; // <- kill the for loop once we've found our char
                }
            }

            // if every character was already at its max value (z),
            // append another character to the string
            if (!isSet($next_id)) {
                $next_id = $this->append_id($last_id);
            }
        }

        // check to see if the $next_id we made already exists, and if it does, 
        // loop the function until we find one that doesn't
        //
        // (this is basically a failsafe to get around the potential dangers of
        //  my kludgey use of a timestamp to pick the most recent id)
        $q = 'SELECT urlID FROM '.URL_TABLE.' WHERE (urlID="'.$next_id.'")';
        $result = mysql_query($q);
        
        if (mysql_num_rows($result)) {
            $next_id = $this->get_next_id($next_id);
        }

        return $next_id;
    }

    /**
     * make every character in the string 0, and then add an additional 0 to that
     * 
     * @param int $id
     * 
     * @return string
     */
    function append_id($id)
    {
        for ($x = 0; $x < strlen($id); $x++) {
            $id[$x] = 0;
        }

        $id .= 0;

        return $id;
    }

    /**
     * increment a character to the next alphanumeric value and return the modified id
     * 
     * @param string $id
     * @param $pos
     * 
     * @return string
     */
    function increment_id($id, $pos)
    {        
        $char = $id[$pos];
        
        // add 1 to numeric values
        if (is_numeric($char)) {
            if ($char < 9 ) {
                $new_char = $char + 1;
            } else {
                // if we're at 9, it's time to move to the alphabet
                $new_char = 'a';
            }
        } else {
            // move it up the alphabet
            $new_char = chr(ord($char) + 1);
        }

        $id[$pos] = $new_char;
        
        // set all characters after the one we're modifying to 0
        if ($pos != (strlen($id) - 1)) {
            for ( $x = ($pos + 1); $x < strlen($id); $x++ ) {
                $id[$x] = 0;
            }
        }

        return $id;
    }

}

?>