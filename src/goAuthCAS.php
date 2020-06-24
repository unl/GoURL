<?php

class GoAuthCAS implements GoAuthInterface
{
    public function __construct($version, $hostname, $port, $uri, $cert = NULL) {
        if (!phpCAS::isInitialized()) {
            phpCAS::client($version, $hostname, $port, $uri);
            if (!empty($cert)) {
                phpCAS::setCasServerCACert($cert);
            } else {
                phpCAS::setNoCasServerValidation();
            }
            phpCAS::handleLogoutRequests();
        }
    }

    public function getAuthType() {
        return static::AUTH_TYPE_CAS;
    }

    public function login() {
        phpCAS::forceAuthentication();
    }

    public function logout() {
        phpCAS::logout();
    }

    public function isAuthenticated() {
        return phpCAS::isAuthenticated();
    }

    public function getUser() {
        return $this->isAuthenticated() ? phpCAS::getAttributes() : NULL;
    }

    public function getUserId() {
        return $this->isAuthenticated() ? phpCAS::getUser() : NULL;
    }

    public function getUserDisplayName() {
        $user = $this->getUser();
        return $this->isAuthenticated() ? $user['displayName'] : NULL;
    }

}