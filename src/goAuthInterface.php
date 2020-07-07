<?php

interface GoAuthInterface
{
    const AUTH_TYPE_CAS = 'AUTH_TYPE_CAS';
    const AUTH_TYPE_SHIB = 'AUTH_TYPE_SHIB';

    public function getAuthType();
    public function login();
    public function logout();
    public function isAuthenticated();
    public function getUser();
    public function getUserId();
    public function getUserDisplayName();
}