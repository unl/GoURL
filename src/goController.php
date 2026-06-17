<?php
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

use Ramsey\Uuid\Uuid;

class GoController extends GoRouter {
    const DEFAULT_QR_ICON_NAME = 'icons/blank_qr_235.png';
    const URL_AUTO_PURGE_NOTICE = '<abbr title="Uniform Resource Locators">URLs</abbr> not redirected for two years will be removed without notice.';

    const FLASHBAG_HEADING_DELETE_SUCCESSFUL = 'Delete Successful';
    const FLASHBAG_HEADING_DELETE_FAILED = 'Delete Failed';
    const FLASHBAG_HEADING_ADD_FAILED = 'Add Failed';

    private $auth;
    private $lilurl;
    private $qrIconPNG;
    private $qrIconSVG;
    private $qrIconSize;
    private $qrCachePrefix = '';
    private $flashBag;

    // Public State
    public static $appName;
    public static $institution;
    public static $themePath;
    public static $customThemeTemplate;
    public static $template;
    public static $templateVersion;

    public function __construct($lilurl, $auth, $flashBag, $qrIconPNG, $qrIconSVG, $qrIconSize, $qrCachePrefix='')
    {
        $this->lilurl = $lilurl;
        $this->auth = $auth;
        $this->qrIconPNG = $qrIconPNG;
        $this->qrIconSVG = $qrIconSVG;
        $this->qrIconSize = $qrIconSize;
        $this->qrCachePrefix = $qrCachePrefix;
        $this->flashBag = $flashBag;
    }

    private function checkAuth() {
        // See if already logged in via PHP CAS
        if ($this->auth->getAuthType() === $this->auth::AUTH_TYPE_CAS && array_key_exists('unl_sso', $_COOKIE) && !$this->auth->isAuthenticated()) {
            // Run PHPCAS checkAuthentication
            $this->auth->checkAuthentication();
        }
    }

    private function loginCheck() {
        if ($this->routeRequiresLogin()) {
            $this->checkAuth();
            if (!$this->auth->isAuthenticated()) {
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LOGIN));
            }
        }
    }

    private function verifyGroup() {
        if (isset($this->groupId)) {
            if (!$this->lilurl->isGroup($this->groupId)) {
                $this->flashBag->setParams('Not Found', '<p>The group is not found.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS), 404);
            } elseif (!$this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
                $this->flashBag->setParams('Access Denied', '<p>You are not a member of this group.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS), 403);
            }
        }
    }

    public function preDispatch() {
        $this->goId = NULL;
        $this->route = '';
        $this->pathInfo = $this->lilurl->getRequestPath();

        if (isset($_GET['login']) || $this->pathInfo === self::ROUTE_PATH_LOGIN) {
            $this->auth->login();
            $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
        }

        if (isset($_GET['logout']) || $this->pathInfo === self::ROUTE_PATH_LOGOUT) {
            session_destroy();
            $this->auth->logout();
            $this->redirect($this->lilurl->getBaseUrl());
        }

        if ($this->pathInfo === 'api_create.php') {
            $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_API), 307, TRUE);
        }

        if (!isset($_SESSION['clientId'])) {
            $_SESSION['clientId'] = (string) Uuid::uuid4();
        }
        $this->lilurl->setGaClientId($_SESSION['clientId']);
    }

    public function dispatch() {
        // check login for protected routes
        $this->loginCheck();

        // verify group and group access if set
        $this->verifyGroup();

        $this->viewTemplate = 'index.php';
        $this->viewParams = [];

        if (!empty(static::$appName)) {
            $this->viewParams['appName'] = static::$appName;
        }

        if (!empty(static::$institution)) {
            $this->viewParams['institution'] = static::$institution;
        }

        switch($this->route) {
            case self::ROUTE_NAME_API:

            case self::ROUTE_NAME_API_V1_READ:
                $this->handleRouteRead();
                die();

            case self::ROUTE_NAME_API_V1_CREATE:
                $this->handleRouteCreate();
                die();

            case self::ROUTE_NAME_API_V1_UPDATE:
                $this->handleRouteUpdate();
                die();

            case self::ROUTE_NAME_API_V1_DELETE:
                $this->handleRouteDelete();
                die();

            case self::ROUTE_NAME_HOME:
                $this->handleRouteHomePage();
                break;

            case self::ROUTE_NAME_EDIT:
                $this->handleRouteURLEdit();
                break;

            case self::ROUTE_NAME_RESET:
                $this->handleRouteURLReset();
                break;

            case self::ROUTE_NAME_QR_PNG:
                $this->handleRouteURLQRCodePNG();
                break;

            case self::ROUTE_NAME_QR_SVG:
                $this->handleRouteURLQRCodeSVG();
                break;

            case self::ROUTE_NAME_LOOKUP:
                // If get an error trying to update/create something
                // Then try to do something else it will recall the last error
                // This causes funkiness
                $this->lilurl->clearErrorPOST();

                $this->handleRouteLookup();
                break;

            case self::ROUTE_NAME_MANAGE:
                // If get an error trying to update/create something
                // Then try to do something else it will recall the last error
                // This causes funkiness
                $this->lilurl->clearErrorPOST();

                $this->handleRouteManage();
                break;

            case self::ROUTE_NAME_GROUPS:
                // If get an error trying to update/create something
                // Then try to do something else it will recall the last error
                // This causes funkiness
                $this->lilurl->clearErrorPOST();

                $this->handleRouteGroups();
                break;

            case self::ROUTE_NAME_GROUP:
                $this->handleRouteGroup();
                break;

            case self::ROUTE_NAME_GROUP_USER_ADD:
                $this->handleRouteGroupAdd();
                break;

            case self::ROUTE_NAME_GROUP_USER_REMOVE:
                $this->handleRouteGroupRemove();
                break;

            case self::ROUTE_NAME_REDIRECT:
                if (!$this->lilurl->handleRedirect($this->pathInfo)) {
                    $this->handle404();
                }
                break;

            case self::ROUTE_NAME_TOKEN_GENERATOR:
                $this->handleRouteTokenGenerator();
                break;

            case self::ROUTE_NAME_NEW_UUID:
                $this->handleRouteNewUUID();
                break;

            default:
                $this->handle404();
                break;
        }
    }

    public function handleRouteRead() {
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            http_response_code(421);
            var_dump("Wrong request method. GET required.");
            die();
        }

        if (!isset(getallheaders()['X-Api-Key'])) {
            http_response_code(401);
            var_dump("No API Key was provided in the headers.");
            die();
        }

        $urlID = isset($_GET['urlID']) ? $_GET['urlID'] : null;
        if(!$this->lilurl->getURL($urlID)) {
            http_response_code(404);
            var_dump("URL not found.");
            die();
        }

        $info = $this->lilurl->getLinkRow($urlID, NULL, PDO::FETCH_OBJ);
        $group = $this->lilurl->getGroup($info->groupID);

        header('Content-Type: application/json');

        try {
            echo json_encode([
                'success' => true,
                'message' => 'URL read successfully.',
                'data' => [
                    'Go_URL' => $info->urlID,
                    'Long_URL' => $info->longURL,
                    'Redirect_Count' => $info->redirects,
                    'Last_Redirect' => $info->lastRedirect,
                    'Created_On' => $info->submitDate,
                    'Created_By' => $info->createdBy,
                    'Group' => $group ? $group->groupName : null,
                    'Group_Users' => $group ? array_column($this->lilurl->getGroupUsers($group->groupID), 'uid') : null
                ]
            ]); 

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleRouteCreate() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            http_response_code(421);
            var_dump("Wrong request method. POST required.");
            die();
        }

        $apiKey = isset(getallheaders()['X-Api-Key']) ? getallheaders()['X-Api-Key'] : null;
        $uid = $this->lilurl->validateAPIKey($apiKey);
        if ($apiKey === null) {
            http_response_code(401);
            var_dump("No API Key was provided in the headers.");
            die();
        }
        if(!$uid) {
            http_response_code(418);
            var_dump("No user associated with API Key provided.");
            die();
        }

        $longURL = isset($_POST['longURL']) ? $_POST['longURL'] : null;
        if($longURL === null) {
            http_response_code(418);
            var_dump("No URL provided.");
            die();
        }
        if(!$this->lilurl->getID($longURL)) {
            http_response_code(400);
            var_dump("URL provided already exist.");
            die();
        }

        $urlID = isset($_POST['urlID']) ? $_POST['urlID'] : $this->lilurl->getRandomID();
        
        $this->aliasCheck($urlID, $longURL);

        $groupID = isset($_POST['groupID']) ? $_POST['groupID'] : 0;
        $malicious_check_value='unchecked';

        $this->lilurl->addURL($longURL, $urlID, $uid,$groupID, $malicious_check_value);

        header('Content-Type: application/json');

        try {
            echo json_encode([
                'success' => true,
                'message' => 'URL created successfully.',
                'data' => [
                    'Go_URL' => $urlID,
                    'Long_URL' => $longURL,
                    'Group' => $groupID,
                    'Group_Users' => $groupID ? array_column($this->lilurl->getGroupUsers($groupID), 'uid') : null
                ]
            ]);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleRouteUpdate() {
        if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
            http_response_code(421);
            var_dump("Wrong request method. PUT required.");
            die();
        }
        else{
            parse_str(file_get_contents('php://input'), $_PUT);
        }

        $apiKey = isset(getallheaders()['X-Api-Key']) ? getallheaders()['X-Api-Key'] : null;
        if ($apiKey === null) {
            http_response_code(401);
            var_dump("No API Key was provided in the headers.");
            die();
        }
        if(!$this->lilurl->validateAPIKey($apiKey)) {
            http_response_code(418);
            var_dump("No user associated with API Key.");
            die();
        }

        $urlID = isset($_PUT['urlID']) ? $_PUT['urlID'] : null;
        if(!$this->lilurl->getURL($urlID)) {
            http_response_code(404);
            var_dump("URL not found.");
            die();
        }

        $longURL = isset($_PUT['longURL']) ? $_PUT['longURL'] : http_response_code(418);

        $groupID = isset($_PUT['groupID']) ? $_PUT['groupID'] : 0;
        $malicious_check_value='unchecked';

        $uid = $this->lilurl->validateAPIKey($apiKey);
        $this->lilurl->updateURL($longURL, $urlID, $uid, $groupID, $malicious_check_value);

        header('Content-Type: application/json');

        try {
            echo json_encode([
                'success' => true,
                'message' => 'URL updated successfully.',
                'data' => [
                    'Go_URL' => $urlID,
                    'Long_URL' => $longURL,
                    'Group' => $groupID,
                    'Group_Users' => $groupID ? array_column($this->lilurl->getGroupUsers($groupID), 'uid') : null
                ]
            ]);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function handleRouteDelete() {
        if ($_SERVER['REQUEST_METHOD'] != 'DELETE') {
            http_response_code(421);
            var_dump("Wrong request method. DELETE required.");
            die();
        }

        $apiKey = isset(getallheaders()['X-Api-Key']) ? getallheaders()['X-Api-Key'] : null;
        if ($apiKey === null) {
            http_response_code(401);
            var_dump("No API Key was provided in the headers.");
            die();
        }
        if(!$this->lilurl->validateAPIKey($apiKey)) {
            http_response_code(418);
            var_dump("No user associated with API Key.");
            die();
        }

        $urlID = isset($_GET['urlID']) ? $_GET['urlID'] : null;
        if(!$this->lilurl->getURL($urlID)) {
            http_response_code(404);
            var_dump("URL not found.");
            die();
        }

        $uid = $this->lilurl->validateAPIKey($apiKey);
        $this->lilurl->deleteURL($urlID, $uid);

        header('Content-Type: application/json');

        try {
            echo json_encode([
                'success' => true,
                'message' => 'URL deleted successfully.'
            ]); 

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function aliasCheck($urlID = null, $longURL = null){
        //validate the alias if specified (data integrity)
        if (!empty($urlID) && !preg_match('/^[\w\-]+$/', $urlID)) {
            $this->lilurl->setErrorPOST();
            throw new Exception('Invalid custom alias.', lilURL::ERR_INVALID_ALIAS);
        }

        //make sure alias isn't already in use
        if (empty($this->lilurl->getURL($urlID)) === false) {
            $this->lilurl->setErrorPOST();
            throw new Exception('Alias is already in use. Please use a different alias.', lilURL::ERR_ALIAS_EXISTS);
        }

        // Check to see if the pair already exists in db
        if ($this->lilurl->getIDandURL($urlID, $longURL) !== false) {
            $this->lilurl->setErrorPOST();
            throw new Exception('This alias/URL pair already exists.', lilURL::ERR_USED);
        }
    }

    public function renderTemplate($file, $params = [])
    {
        global $lilurl, $auth, $page;
        extract($params);
        unset($params);
        ob_start();
        include __DIR__ .'/../www/templates/' . $file;
        return ob_get_clean();
    }

    private function handleRouteLookup() {
        $this->viewTemplate = 'linkinfo.php';

        if (isset($_POST, $_POST['lookupTerm'])) {
            $lookupTerm = htmlspecialchars($_POST['lookupTerm'] ?? '');
            $link = $this->lilurl->getLinkRow($lookupTerm, NULL, PDO::FETCH_OBJ);
            if (!$link) {
                $this->flashBag->setParams('Not Found', '<p>&apos;' . $lookupTerm . '&apos; is not in use and available.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
            } else {
                $this->viewParams['link'] = $link;
                $group = $this->lilurl->getGroup($link->groupID);
                if (!empty($group)) {
                    $this->viewParams['group'] = $group;
                    $this->viewParams['group']->users = $this->lilurl->getGroupUsers($link->groupID);
                }
            }
        }
    }

    private function handleRouteManage() {
        $this->viewTemplate = 'manage.php';

        if (isset($_POST, $_POST['urlID'])) {
            $urlID = filter_input(INPUT_POST, 'urlID', FILTER_SANITIZE_URL);
            $deleted = $this->lilurl->deleteURL($urlID, $this->auth->getUserId());

            if ($deleted) {
                $this->flashBag->setParams(self::FLASHBAG_HEADING_DELETE_SUCCESSFUL, '<p>The URL &apos;' . htmlspecialchars($_POST['urlID'] ?? '') . '&apos; has been deleted.</p>', $this->flashBag::FLASH_BAG_TYPE_SUCCESS);
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
            } else {
                $this->flashBag->setParams(self::FLASHBAG_HEADING_DELETE_FAILED, '<p>The URL &apos;' . htmlspecialchars($_POST['urlID'] ?? '') . '&apos; has NOT been deleted.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
            }

        }
    }

    private function handleRouteGroups() {
        $this->viewTemplate = 'groups.php';

        if (isset($_POST, $_POST['groupID'])) {
            $groupID = filter_input(INPUT_POST, 'groupID', FILTER_SANITIZE_NUMBER_INT);
            $deleted = $this->lilurl->deleteGroup($groupID, $this->auth->getUserId());

            if ($deleted) {
                $this->flashBag->setParams(self::FLASHBAG_HEADING_DELETE_SUCCESSFUL, '<p>Your group has been deleted.</p>', $this->flashBag::FLASH_BAG_TYPE_SUCCESS);
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
            } else {
                $this->flashBag->setParams(self::FLASHBAG_HEADING_DELETE_FAILED, '<p>Your group has NOT been deleted.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
                $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
            }
        }
    }

    private function handleRouteGroup() {
        $this->viewTemplate = 'group.php';
        $this->viewParams['groupMode'] = $this->groupMode;

        if ($this->groupMode === self::MODE_EDIT) {
            $this->viewParams['group'] = $this->lilurl->getGroup($this->groupId);
            $this->viewParams['group']->users = $this->lilurl->getGroupUsers($this->groupId);
        }

        if (!empty($_POST)) {
            $this->handleRouteGroupPost();
        }
    }

    private function handleRouteGroupPost() {
        $error = '';
        $msg = '';
        $type = '';
        $groupName = htmlspecialchars($_POST['groupName'] ?? '');

        if (!$this->lilurl->isValidGroupName($groupName, $this->groupId, $error)) {
            $heading = 'Invalid Group';
            $msg = '<p>' . $error . '</p>';
            $type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
            $this->flashBag->setParams($heading, $msg, $type);
        } else {
            if ($this->groupMode === self::MODE_CREATE) {
                if ($this->lilurl->insertGroup($_POST, $this->auth->getUserId())) {
                    $heading = 'Add Successful';
                    $msg = '<p>Your group has been added.</p>';
                    $type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
                } else {
                    $heading = self::FLASHBAG_HEADING_ADD_FAILED;
                    $msg = '<p>Your group has not been added.</p>';
                    $type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
                }
            } elseif ($this->groupMode === self::MODE_EDIT && $this->groupId === $_POST['groupID']) {
                if ($this->lilurl->isSameGroupName($groupName, $this->groupId, $error) || $this->lilurl->updateGroup($_POST, $this->auth->getUserId())) {
                    $heading = 'Update Successful';
                    $msg = '<p>Your group has been updated.</p>';
                    $type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
                } else {
                    $heading = 'Update Failed';
                    $msg = '<p>Your group has not been updated.</p>';
                    $type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
                }
            }

            $this->flashBag->setParams($heading, $msg, $type);
            $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
        }
    }

    private function handleRouteGroupAdd() {
        $this->viewTemplate = 'group.php';
        $this->viewParams['groupMode'] = $this->groupMode;
        $heading = '';
        $msg = '';
        $type = '';
        $error = '';

        if ($this->lilurl->isValidGroupUser($this->uid, $error)) {
            if ($this->lilurl->insertGroupUser($this->groupId, $this->uid, $this->auth->getUserId())) {
                $heading = 'Add Successful';
                $msg = '<p>User, ' . htmlspecialchars($this->uid ?? '') . ' added to group.</p>';
                $type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
                $_POST['uid'] = NULL;
            } else {
                $heading = self::FLASHBAG_HEADING_ADD_FAILED;
                $msg = '<p>User, ' . htmlspecialchars($this->uid ?? '') . ' not added to group.</p>';
                $type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
            }
        } else {
            $heading = self::FLASHBAG_HEADING_ADD_FAILED;
            $msg = '<p>' . $error . '</p>';
            $type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
        }

        if ($this->groupMode === self::MODE_EDIT) {
            $this->viewParams['group'] = $this->lilurl->getGroup($this->groupId);
            $this->viewParams['group']->users = $this->lilurl->getGroupUsers($this->groupId);
        }

        if (!empty($msg) && !empty($type)) {
            $this->flashBag->setParams($heading, $msg, $type);
        }

        $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUP . '/' . $this->groupId));
    }

    private function handleRouteGroupRemove() {
        if (!empty($this->groupId) && $this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
            if ($this->lilurl->deleteGroupUser($this->groupId, $this->uid, $this->auth->getUserId())) {
                $heading = self::FLASHBAG_HEADING_DELETE_SUCCESSFUL;
                $msg = '<p>' . htmlspecialchars($this->uid ?? '') . ' has been removed from group.</p>';
                $type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
            } else {
                $heading = self::FLASHBAG_HEADING_DELETE_FAILED;
                $msg = '<p>Unable to remove ' . htmlspecialchars($this->uid ?? '') . ' from group.</p>';
                $type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
            }

            $this->flashBag->setParams($heading, $msg, $type);
            $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUP . '/' . $this->groupId));
        }

        // Not authorized to delete user from group
        $this->flashBag->setParams('Access Denied', '<p>Unable to remove ' . $this->uid . ' from group.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
        $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
    }

    private function handleRouteURLEdit() {
        if (!$this->auth->isAuthenticated() || !$this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
            $this->handle404();
        }

        if ($this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
            $this->viewTemplate = 'index.php';
            $this->viewParams['goURL'] =  $this->lilurl->getLinkRow($this->goId, NULL, PDO::FETCH_ASSOC);
        } else {
            $this->flashBag->setParams('Not Authorized', '<p>You are not the owner of the Go URL.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
            $this->redirect($this->lilurl->getBaseUrl() . self::ROUTE_PATH_LINKS);
        }
    }

    private function handleRouteURLReset() {
        if (!$this->auth->isAuthenticated() || !$this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
            $this->handle404();
        }

        if ($this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
            $this->lilurl->resetRedirectCount($this->goId, $this->auth->getUserId());
            $this->flashBag->setParams('Reset Successful', '<p>Your Go URL redirect count has been reset.</p>', $this->flashBag::FLASH_BAG_TYPE_SUCCESS);
        } else {
            $this->flashBag->setParams('Not Authorized', '<p>You are not the owner of the Go URL.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
        }

        $this->redirect($this->lilurl->getBaseUrl() . self::ROUTE_PATH_LINKS);
    }

    private function handleRouteURLQRCodePNG()
    {
        if (!$this->lilurl->getURL($this->goId)) {
            $this->handle404(FALSE);
        }

        $shortURL = $this->lilurl->getShortURL($this->goId);
        $pngPrefix = __DIR__ . '/../data/qr/';
        $qrCodeHash = hash("sha512", $shortURL);
        $qrCache = $pngPrefix . 'cache/' . $this->qrCachePrefix . hash("sha512", $shortURL) . '.png';

        // Remove any old cached files
        $files = glob($pngPrefix . 'cache/*' . $qrCodeHash . '.png', GLOB_BRACE);
        foreach ($files as $file) {
            // Check the current cachePrefix is not in there before deleting
            if (strpos($file, $this->qrCachePrefix) === false) {
                unlink($file);
            }
        }

        if (!file_exists($qrCache)) {
            $writer = new PngWriter();

            // Create QR code
            $qrCode = QrCode::create($shortURL)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setSize(1080)
                ->setMargin(36)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->setForegroundColor(new Color(35, 31, 32))
                ->setBackgroundColor(new Color(255, 255, 255));

            if (!empty($this->qrIconPNG) && file_exists($this->qrIconPNG)) {
                // Create generic logo
                $qrLogo = Logo::create($this->qrIconPNG)
                ->setResizeToWidth($this->qrIconSize)
                ->setResizeToHeight($this->qrIconSize);

                $writer->write($qrCode, $qrLogo)->saveToFile($qrCache);
            } else {
                $writer->write($qrCode)->saveToFile($qrCache);
            }
        }

        $out = imagecreatefrompng($qrCache);
        header('Content-Type: image/png');
        imagepng($out);
        imagedestroy($out);
        exit;
    }

    private function handleRouteURLQRCodeSVG()
    {
        if (!$this->lilurl->getURL($this->goId)) {
            $this->handle404(false);
        }

        $shortURL = $this->lilurl->getShortURL($this->goId);
        $svgPrefix = __DIR__ . '/../data/qr/';
        $qrCodeHash = hash("sha512", $shortURL);
        $qrCache = $svgPrefix . 'cache/' . $this->qrCachePrefix . hash("sha512", $shortURL) . '.svg';

        // Remove any old cached files
        $files = glob($svgPrefix . 'cache/*' . $qrCodeHash . '.svg', GLOB_BRACE);
        foreach ($files as $file) {
            // Check the current cachePrefix is not in there before deleting
            if (strpos($file, $this->qrCachePrefix) === false) {
                unlink($file);
            }
        }

        if (!file_exists($qrCache)) {
            $writer = new SvgWriter();

            // Create QR code
            $qrCode = QrCode::create($shortURL)
                ->setEncoding(new Encoding('ISO-8859-1'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setSize(1080)
                ->setMargin(36)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->setForegroundColor(new Color(35, 31, 32))
                ->setBackgroundColor(new Color(255, 255, 255));

            if (!empty($this->qrIconSVG) && file_exists($this->qrIconSVG)) {
                // Create generic logo
                $qrLogo = Logo::create($this->qrIconSVG)
                    ->setResizeToWidth($this->qrIconSize)
                    ->setResizeToHeight($this->qrIconSize);

                // Get the string version of the SVG QR code
                $result = $writer->write($qrCode, $qrLogo)->getString();

                // Get the SVG icon and prep it for preg_replace with back reference
                $svg_file = file_get_contents($this->qrIconSVG);
                $svg_file = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $svg_file);
                $svg_file = str_replace('<svg', '<svg x="$1" y="$2" width="$3" height="$4"', $svg_file);

                // Replace image with SVG icon, use back reference to get the variables we need
                $result = preg_replace(
                    '/<image x="([\d]+)" y="([\d]+)" width="([\d]+)" height="([\d]+)".*\/>/',
                    $svg_file,
                    $result
                );

                // Write the files to the cache
                file_put_contents($qrCache, $result);
            } else {
                $writer->write($qrCode)->saveToFile($qrCache);
            }
        }

        header('Content-Type: image/svg+xml');
        include_once($qrCache);
        exit;
    }

    private function handleRouteHomePage() {
        if (isset($_GET['url']) && $_GET['url'] === 'referer' && isset($_SERVER['HTTP_REFERER'])) {
            $_POST['theURL'] = urldecode($_SERVER['HTTP_REFERER']);
        }

        if (isset($_POST['theURL'])) {
            $mode = static::MODE_CREATE;
            $userId = NULL;
            $alias = NULL;
            $this->sanitizeURLPost($mode, $userId, $alias);

            try {
                $url = $this->lilurl->handlePOST($mode, $alias, $userId);
                $heading = $mode === static::MODE_EDIT ? 'Your Go URL is updated!' : 'You have a Go URL!';

                $this->flashBag->setParams($heading, '<input type="text" onclick="this.select(); return false;" value="'.$url.'" />', $this->flashBag::FLASH_BAG_TYPE_SUCCESS, $url);
                if ($mode === static::MODE_EDIT) {
                    $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
                }
            } catch (Exception $e) {
                $this->handleException($e);
            }

            if ($this->route === self::ROUTE_NAME_API) {
                $this->sendCORSHeaders();
                $this->flashBag->clearParams();

                if (!empty($url)) {
                    echo htmlspecialchars($url ?? '');
                    exit;
                }

                header('HTTP/1.1 404 Not Found');
                echo 'There was an error. ';
                exit;
            }

            $this->redirect($this->lilurl->getBaseUrl());

        } elseif ($this->route === self::ROUTE_NAME_API) {
            $this->sendCORSHeaders();
            header('HTTP/1.1 404 Not Found');
            echo 'You need a URL!';
            exit;
        }
    }

    private function sanitizeURLPost(&$mode, &$userId, &$alias) {
        $modeFiltered = htmlspecialchars($_POST['mode'] ?? '');
        $mode = $modeFiltered === static::MODE_EDIT ? static::MODE_EDIT : static::MODE_CREATE;
        $userId = $alias = null;

        if ($this->auth->isAuthenticated()) {
            $userId = $this->auth->getUserId();

            if ($mode === static::MODE_EDIT) {
                if (!empty($_POST['id'])) {
                    $alias = htmlspecialchars($_POST['id'] ?? '');
                }
            } else {
                if (!empty($_POST['theAlias'])) {
                    $alias = htmlspecialchars($_POST['theAlias'] ?? '');
                }
            }
        }
    }

    private function handleRouteTokenGenerator() {
        $this->viewTemplate = 'token-generator.php';

        return [
            'autoGenerateUUID' => true
        ];
    }

    private function handleRouteNewUUID()
    {
        global $lilurl, $auth;

        header('Content-Type: application/json');

        try {

            $uid = $auth->getUserId();

            $newKey = Uuid::uuid4()->toString();

            $existing = $lilurl->getUserAPIKey($uid);

            if ($existing) {

                $lilurl->updateUserAPIKey($uid, $newKey);

            } else {

                $lilurl->createUserAPIKey($uid);

                $lilurl->updateUserAPIKey($uid, $newKey);
            }

            echo json_encode([
                'success' => true,
                'apiKey' => $newKey
            ]);

        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }

    private function handleException(Exception $e) {
        switch ($e->getCode()) {
            case lilURL::ERR_INVALID_PROTOCOL:
                $heading = 'Invalid URL';
                $msg = '<p>Your URL must begin with <code>http://</code>, <code>https://</code>.</p>';
                break;
            case lilURL::ERR_INVALID_DOMAIN:
                $heading = 'Action Denied';
                $msg = '<p>You must sign in to create a URL for this domain: '.parse_url($_POST['theURL'], PHP_URL_HOST).'</p>';
                break;
            case lilURL::ERR_INVALID_ALIAS:
                $heading = 'Invalid URL Alias';
                $msg = '<p>The custom Alias you provided should only contain letters, numbers, underscores (_), and dashes (-).</p>';
                break;
            case lilURL::ERR_USED:
                $heading = 'This alias/URL pair already exists.';
                $msg = '<p>The existing Go URL for this pair is: </p>';
                break;
            case lilURL::ERR_ALIAS_EXISTS:
                $heading = 'This alias is already in use.';
                $msg = '<p>Please use a different alias.</p>';
                break;
            case lilURL::ERR_INVALID_GA_CAMPAIGN:
                $heading = 'Invalid Google Campaign.';
                $msg = '<p>Please provide all required campaign information.</p>';
                break;
            case lilURL::ERR_INVALID_URL:
                $heading = 'Invalid URL.';
                $msg = '<p>Please verify the URL is correct.</p>';
                break;
            case lilURL::ERR_MAX_RANDOM_ID_ATTEMPTS:
                $heading = 'Random Alias Error.';
                $msg = '<p>'. $e->getMessage() . '</p>';
                break;
            case lilURL::ERR_MALICIOUS_URL:
                    $heading = 'The URL you submitted has been deemed malicious.';
                    $msg = '
                        <p>
                            If you think this is an error reach out to <a href="mailto:dxg@lists.nebraska.edu">dxg@lists.nebraska.edu</a>
                        </p>
                    ';
                    break;
            default:
                $heading = 'Submission Error';
                $msg = '<p>There was an error submitting your url. Check your steps.</p>';
        }

        $this->flashBag->setParams($heading, $msg, $this->flashBag::FLASH_BAG_TYPE_ERROR);
    }
}