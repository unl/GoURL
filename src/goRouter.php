<?php
class GoRouter {
    // Edit Modes
    const MODE_CREATE = 'create';
    const MODE_EDIT = 'edit';

    // route names
    const ROUTE_NAME_API  = 'api';
    const ROUTE_NAME_EDIT  = 'edit';
    const ROUTE_NAME_GROUP = 'group';
    const ROUTE_NAME_GROUP_USER_ADD = 'group-user-add';
    const ROUTE_NAME_GROUP_USER_REMOVE = 'group-user-remove';
    const ROUTE_NAME_GROUPS = 'groups';
    const ROUTE_NAME_HOME = 'home';
    const ROUTE_NAME_LINKS = 'links';
    const ROUTE_NAME_LOGIN = 'login';
    const ROUTE_NAME_LOGOUT = 'logout';
    const ROUTE_NAME_LOOKUP = 'lookup';
    const ROUTE_NAME_MANAGE = 'manage';
    const ROUTE_NAME_QR_PNG = 'qr-png';
    const ROUTE_NAME_QR_SVG = 'qr-svg';
    const ROUTE_NAME_REDIRECT = 'redirect';
    const ROUTE_NAME_RESET = 'reset';

    // route paths
    const ROUTE_PATH_A = 'a/';
    const ROUTE_PATH_API = 'api/';
    const ROUTE_PATH_GROUP = 'a/group';
    const ROUTE_PATH_GROUP_USER_ADD = 'a/group-user-add';
    const ROUTE_PATH_GROUP_USER_REMOVE = 'a/group-user-remove';
    const ROUTE_PATH_GROUPS = 'a/groups';
    const ROUTE_PATH_HOME = '';
    const ROUTE_PATH_LINKS = 'a/links';
    const ROUTE_PATH_LOGIN = 'a/login';
    const ROUTE_PATH_LOGOUT = 'a/logout';
    const ROUTE_PATH_LOOKUP = 'a/lookup';

    public static $corsAllowedDomains = array();

    protected $viewTemplate;
    protected $viewParams;
    protected $route;
    protected $groupId;
    protected $groupMode;
    protected $uid;
    protected $goId;
    protected $pathInfo;

    protected function routeRequiresLogin(): bool
    {
        return in_array($this->route, array(
            self::ROUTE_NAME_EDIT,
            self::ROUTE_NAME_GROUP,
            self::ROUTE_NAME_GROUP_USER_ADD,
            self::ROUTE_NAME_GROUP_USER_REMOVE,
            self::ROUTE_NAME_GROUPS,
            self::ROUTE_NAME_LINKS,
            self::ROUTE_NAME_LOOKUP,
            self::ROUTE_NAME_MANAGE,
            self::ROUTE_NAME_RESET
        ));
    }

    public function getViewTemplate() {
        return $this->viewTemplate;
    }

    public function getViewParams() {
        return $this->viewParams;
    }

    public function route() {
        $this->route = NULL;

        if (empty($this->pathInfo)) {
            $this->route = self::ROUTE_NAME_HOME;
        } elseif ($this->pathInfo === self::ROUTE_PATH_API) {
            $this->route = self::ROUTE_NAME_API;
        } elseif (preg_match('#^([^/]+)\.png$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_QR_PNG;
            $this->goId = $matches[1];
        }elseif (preg_match('#^([^/]+)\.svg$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_QR_SVG;
            $this->goId = $matches[1];
        }

        $this->routeAdmin();

        if (!$this->route && $this->pathInfo !== '') {
            $this->route = self::ROUTE_NAME_REDIRECT;
        }
    }

    private function routeAdmin() {
        if (isset($_GET['manage']) || in_array($this->pathInfo, array(self::ROUTE_PATH_A, self::ROUTE_PATH_LINKS))) {
            $this->route = self::ROUTE_NAME_MANAGE;
        } elseif (isset($_GET['lookup']) || $this->pathInfo === self::ROUTE_PATH_LOOKUP) {
            $this->route = self::ROUTE_NAME_LOOKUP;
        } elseif (preg_match('/^a\/group\/(\d+)$/', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_GROUP;
            $this->groupId = $matches[1];
            $this->groupMode = self::MODE_EDIT;
        } elseif ($this->pathInfo === self::ROUTE_PATH_GROUP) {
            $this->route = self::ROUTE_NAME_GROUP;
            $this->groupId = NULL;
            $this->groupMode = self::MODE_CREATE;
        } elseif ($this->pathInfo === self::ROUTE_PATH_GROUPS) {
            $this->route = self::ROUTE_NAME_GROUPS;
        } elseif (preg_match('/^a\/group-user-add\/(\d+)$/', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_GROUP_USER_ADD;
            $this->groupId = $matches[1];
            $this->uid = filter_input(INPUT_POST, 'uid', FILTER_SANITIZE_STRING);
        } elseif (preg_match('/^a\/group-user-remove\/(\d+)-([\w\-\.]+)$/', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_GROUP_USER_REMOVE;
            $this->groupId = $matches[1];
            $this->uid = urldecode($matches[2]);
        } elseif (preg_match('#^([^/]+)\/edit$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_EDIT;
            $this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/reset$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_RESET;
            $this->goId = $matches[1];
        }
    }

    protected function redirect($location, $code = 303, $sendCORSHeaders = FALSE) {
        header("LOCATION: ". htmlspecialchars($location), TRUE, $code);
        if ($sendCORSHeaders) {
            $this->sendCORSHeaders();
        }
        exit();
    }

    public function allowedCORSDomain() {
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            foreach (static::$corsAllowedDomains as $allowedDomain) {
                $domainCheck = "/https?:\/\/.*" . $allowedDomain . "$/";
                if (preg_match($domainCheck, $_SERVER['HTTP_ORIGIN'])) {
                    return $_SERVER['HTTP_ORIGIN'];
                }
            }
        }
        return false;
    }

    protected function sendCORSHeaders() {
        if ($allowedCORSDomain = $this->allowedCORSDomain()) {
            header('Access-Control-Allow-Origin: ' . $allowedCORSDomain);
            header('Access-Control-Allow-Methods: GET, POST');
            header('Access-Control-Allow-Headers: X-Requested-With');
        }
    }

    protected function handle404($showCustom = TRUE) {
        header('HTTP/1.1 404 Not Found');
        if ($showCustom === TRUE) {
            include __DIR__ . '/../www/templates/404.php';
        }
        exit;
    }
}
