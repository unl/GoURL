# UNL GoURL

## Setup

Install DB with data/goURL.sql

Run `composer install`

Copy config.sample.php to config.inc.php and modify connection details.

Copy www/sample.htaccess to www/.htaccess and modify site path.

### Install with Docker

1. Copy `config.sample.php` to `config.inc.php` and use recommended docker configs
2. Copy `www/sample.htaccess` to `www/.htaccess` and use recommended docker configs
3. Add `127.0.0.1 localhost.unl.edu` to `/etc/hosts` on your host machine
4. Run `docker-compose build` in the root directory to build the image for php
6. Run `docker-compose up` in the root directory to run the docker containers
7. Open GoURL in the browser using the URL [https://localhost.unl.edu:5507/](https://localhost.unl.edu:5507/)

## About

Originally based on lilURL: http://lilurl.sourceforge.net
http://www.gnu.org/licenses/gpl.html

## User Auth
GoURL currently supports UNL PHP CAS or Apache mod_shib by setting `$auth` in config.inc.php

### UNL PHP CAS example
```
define('CAS_CA_FILE', '/etc/pki/tls/cert.pem');
$auth = new \UNL\Templates\Auth\AuthCAS('2.0', 'shib.unl.edu', 443, '/idp/profile/cas', CAS_CA_FILE);
```
### Apache mod_shib example
```
$shibSettings = array (
  'shibLoginURL' => 'https://localhost/Shibboleth.sso/Login',
  'shibLogoutURL' => 'https://localhost/Shibboleth.sso/Logout',
  'appBaseURL' => 'https://your.site',
  'userAttributes' => array(
    'eduPersonAssurance',
    'eduPersonScopedAffiliation',
    'eduPersonAffiliation',
    'sn',
    'givenName',
    'surname',
    'email',
    'displayName',
    'eduPersonPrincipalName'
  )
);
$auth = new \UNL\Templates\Auth\AuthModShib($shibSettings);
```

