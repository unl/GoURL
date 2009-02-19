lilURL 0.1.1 http://lilurl.sourceforge.net

lilURL is a simple PHP/MySQL app that works basically like tinyurl.com,
allowing you to create shortcuts on your own server.

-----------------------------------------------------------------------

To install:

1. Create a MySQL database and user for lilURL. 

2. Import the lilurl.sql file:

      (( like so:
      
         mysql -u <lilurl_user> -p <lilurl_db> < lilurl.sql
      
      ))

3. Edit the configuration file includes/conf.php to suit your needs.

4. Set up mod_rewrite, if necessary

      (( a .htaccess file with the lines:
   
         RewriteEngine On
         RewriteRule (.*) index.php
   
        should suffice ))

5. Buy 15 donuts and eat them all in one sitting.
