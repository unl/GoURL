<?php
use Endroid\QrCode\QrCode;
use Ramsey\Uuid\Uuid;

class GoController
{
    const MODE_CREATE = 'create';
    const MODE_EDIT = 'edit';
    const DEFAULT_QR_ICON_NAME = 'icons/blank_qr_235.png';

    // route names
		const ROUTE_NAME_API  = 'api';
		const ROUTE_NAME_EDIT  = 'edit';
		const ROUTE_NAME_GROUP = 'group';
		const ROUTE_NAME_GROUPS = 'groups';
		const ROUTE_NAME_LINKS = 'links';
		const ROUTE_NAME_LOGIN = 'login';
		const ROUTE_NAME_LOGOUT = 'logout';
		const ROUTE_NAME_LOOKUP = 'lookup';
		const ROUTE_NAME_MANAGE = 'manage';
		const ROUTE_NAME_QR = 'qr';
		const ROUTE_NAME_REMOVE_USER = 'remove-user';
		const ROUTE_NAME_RESET = 'reset';

    // route paths
	  const ROUTE_PATH_API = 'api/';
		const ROUTE_PATH_GROUP = 'a/group';
		const ROUTE_PATH_GROUPS = 'a/groups';
		const ROUTE_PATH_LINKS = 'a/links';
		const ROUTE_PATH_LOGIN = 'a/login';
		const ROUTE_PATH_LOGOUT = 'a/logout';
		const ROUTE_PATH_LOOKUP = 'a/lookup';

    private $auth;
    private $lilurl;
    private $route;
    private $groupId;
    private $groupMode;
    private $uid;
    private $goId;
    private $pathInfo;
    private $viewTemplate;
    private $viewParams;
    private $qrIconPNG;

    // Public State
    public static $appName;
    public static $institution;
    public static $themePath;
    public static $customThemeTemplate;
    public static $template;
    public static $templateVersion;

    public function __construct($lilurl, $auth, $qrIconPNG) {
        $this->lilurl = $lilurl;
        $this->auth = $auth;
        $this->qrIconPNG = $qrIconPNG;

		    // See if already logged in via PHP CAS
		    if ($this->auth->getAuthType() === $this->auth::AUTH_TYPE_CAS && array_key_exists('unl_sso', $_COOKIE) && !$this->auth->isAuthenticated()) {
			    // Run PHPCAS checkAuthentication
			    $this->auth->checkAuthentication();
		    }
    }

    private function redirect($location, $code = 303, $sendCORSHeaders = FALSE) {
	    header("LOCATION: ". htmlspecialchars($location), TRUE, $code);
	    if ($sendCORSHeaders) {
		    $this->sendCORSHeaders();
	    }
	    exit();
    }

    private function routeRequiresLogin() {
			return in_array($this->route, array(
				self::ROUTE_NAME_GROUP,
				self::ROUTE_NAME_GROUPS,
				self::ROUTE_NAME_LINKS,
				self::ROUTE_NAME_LOOKUP,
				self::ROUTE_NAME_REMOVE_USER
			));
    }

    private function loginCheck() {
	    if ($this->routeRequiresLogin() && !$this->auth->isAuthenticated()) {
		    $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LOGIN));
	    }
    }

    public function getViewTemplate() {
        return $this->viewTemplate;
    }

    public function getViewParams() {
        return $this->viewParams;
    }

    public function preDispatch() {
        $this->goId = NULL;
        $this->route = '';
        $this->pathInfo = $this->lilurl->getRequestPath();

        if (isset($_GET['login']) || 'a/login' === $this->pathInfo) {
            $this->auth->login();
	          $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
        }

        if (isset($_GET['logout']) || 'a/logout' === $this->pathInfo) {
            session_destroy();
            $this->auth->logout();
	          $this->redirect($this->lilurl->getBaseUrl());
        }

		    if ('api_create.php' === $this->pathInfo) {
			    $this->redirect($this->lilurl->getBaseUrl('api/'), 307, TRUE);
		    }

		    // TODO Move to routes if makes sense
		    if (preg_match('/^a\/group\/(\d+)$/', $this->pathInfo, $matches) && isset($matches[1]) && $this->lilurl->isGroup($matches[1])) {
			    $this->route = 'group';
			    $this->groupId = $matches[1];
			    $this->groupMode = self::MODE_EDIT;

			    if (!$this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
				    $_SESSION['gourlFlashBag'] = array(
					    'msg' => '<p class="title">Access Denied</p><p>You are not a member of this group.</p>',
					    'type' => 'error'
				    );
				    $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
			    }

			    if (!empty($_POST)) {
				    switch($_POST['formName']) {
					    case 'group-form':
						    $this->route = 'group';
						    break;

					    case 'user-form':
						    $this->route = 'add-group-user';
						    $this->uid = filter_input(INPUT_POST, 'uid', FILTER_SANITIZE_STRING);
						    break;

					    default:
						    // missing or unexpected form so bail
						    $this->redirect($this->lilurl->getBaseUrl($this->pathInfo));
				    }
			    }
		    }

		    if (!isset($_SESSION['clientId'])) {
			    $_SESSION['clientId'] = (string) Uuid::uuid4();
		    }
	      $this->lilurl->setGaClientId($_SESSION['clientId']);
    }

    public function route() {
		    if (isset($_GET['manage']) || in_array($this->pathInfo, array('a/', self::ROUTE_PATH_LINKS))) {
			    $this->route = self::ROUTE_NAME_MANAGE;
		    } elseif (isset($_GET['lookup']) || $this->pathInfo === self::ROUTE_PATH_LOOKUP) {
			    $this->route = self::ROUTE_NAME_LOOKUP;
		    } elseif ($this->pathInfo === self::ROUTE_PATH_GROUP) {
			    $this->route = 'group';
			    $this->groupId = NULL;
			    $this->groupMode = self::MODE_CREATE;
		    } elseif ($this->pathInfo === self::ROUTE_PATH_GROUPS) {
			    $this->route = self::ROUTE_NAME_GROUPS;
		    } elseif ($this->pathInfo === self::ROUTE_PATH_API) {
            $this->route = self::ROUTE_NAME_API;
        } elseif (preg_match('/^a\/removeuser\/(\d+)-(\w+)$/', $this->pathInfo, $matches)) {
			    $this->route = self::ROUTE_NAME_REMOVE_USER;
			    $this->groupId = $matches[1];
			    $this->uid = urldecode($matches[2]);
		    } elseif (preg_match('#^([^/]+)\.qr$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_QR;
            $this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/edit$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_EDIT;
            $this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/reset$#', $this->pathInfo, $matches)) {
            $this->route = self::ROUTE_NAME_EDIT;
            $this->goId = $matches[1];
        }

        if (!$this->route && $this->pathInfo !== '') {
            $this->route = 'redirect';
        }

        // check login for protected routes
	      $this->loginCheck();
    }

    public function dispatch() {
        $this->viewTemplate = 'index.php';
        $this->viewParams = [];

        if (!empty(static::$appName)) {
            $this->viewParams['appName'] = static::$appName;
        }

        if (!empty(static::$institution)) {
            $this->viewParams['institution'] = static::$institution;
        }

        if (!$this->route || 'api' === $this->route) {
            if (isset($_GET['url']) && $_GET['url'] === 'referer' && isset($_SERVER['HTTP_REFERER'])) {
                $_POST['theURL'] = urldecode($_SERVER['HTTP_REFERER']);
            }

            if (isset($_POST['theURL'])) {
                $mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING) === static::MODE_EDIT ? static::MODE_EDIT : static::MODE_CREATE;
                $userId = $alias = null;

                if ($this->auth->isAuthenticated()) {
                    $userId = $this->auth->getUserId();

                    if ($mode == static::MODE_EDIT) {
                        if (!empty($_POST['id'])) {
                            $alias = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
                        }
                    } else {
                        if (!empty($_POST['theAlias'])) {
                            $alias = filter_input(INPUT_POST, 'theAlias', FILTER_SANITIZE_STRING);
                        }
                    }
                }

                try {
                    $url = $this->lilurl->handlePOST($mode, $alias, $userId);
                    $msg = $mode === static::MODE_EDIT ? 'Your Go URL is updated!' : 'You have a Go URL!';

                    $_SESSION['gourlFlashBag'] = array(
                        'msg' => '<p class="title">' . $msg . '</p><input type="text" onclick="this.select(); return false;" value="'.$url.'" />',
                        'type' => 'success',
                        'url' => $url,
                    );
                    if ($mode === static::MODE_EDIT) {
	                      $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
                    }
                } catch (Exception $e) {
                    switch ($e->getCode()) {
                        case lilURL::ERR_INVALID_PROTOCOL:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Something Broke</p><p>Your URL must begin with <code>http://</code>, <code>https://</code>.</p>',
                            );
                            break;
                        case lilURL::ERR_INVALID_DOMAIN:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Something Broke</p><p>You must sign in to create a URL for this domain: '.parse_url($_POST['theURL'], PHP_URL_HOST).'</p>',
                            );
                            break;
                        case lilURL::ERR_INVALID_ALIAS:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Something Broke</p><p>The custom Alias you provided should only contain letters, numbers, underscores (_), and dashes (-).</p>',
                            );
                            break;
                        case lilURL::ERR_USED:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, this alias/URL pair already exists.</p><p>The existing Go URL for this pair is: </p>',
                            );
                            break;
                        case lilURL::ERR_ALIAS_EXISTS:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, This alias is already in use.</p><p>Please use a different alias.</p>',
                            );
                            break;
                        case lilURL::ERR_INVALID_GA_CAMPAIGN:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Invalid Google Campaign.</p><p>Please provide all required campaign information.</p>',
                            );
                            break;
                        case lilURL::ERR_INVALID_URL:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Invalid URL.</p><p>Please verify the URL is correct.</p>',
                            );
                            break;
                        case lilURL::ERR_MAX_RANDOM_ID_ATTEMPTS:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Random Alias Error.</p><p>'. $e->getMessage() . '</p>',
                            );
                            break;
                        default:
                            $_SESSION['gourlFlashBag'] = array(
                                'msg' => '<p class="title">Whoops, Something Broke</p><p>There was an error submitting your url. Check your steps.</p>',
                            );
                    }

                    $_SESSION['gourlFlashBag']['type'] = 'error';
                }

                if ('api' === $this->route) {
                    $this->sendCORSHeaders();
                    unset($_SESSION['gourlFlashBag']);

                    if (!empty($url)) {
                        echo htmlspecialchars($url);
                        exit;
                    }

                    header('HTTP/1.1 404 Not Found');
                    echo 'There was an error. ';
                    exit;
                }

	              $this->redirect($this->lilurl->getBaseUrl());

            } elseif ('api' === $this->route) {
                $this->sendCORSHeaders();
                header('HTTP/1.1 404 Not Found');
                echo 'You need a URL!';
                exit;
            }
        } elseif ('redirect' === $this->route) {
            $id = $this->pathInfo;

            if (!$this->lilurl->handleRedirect($id)) {
                header('HTTP/1.1 404 Not Found');
                include __DIR__ . '/../www/templates/404.php';
                exit;
            }
        } elseif ('lookup' === $this->route) {
	        $this->viewTemplate = 'linkinfo.php';

	        if (isset($_POST, $_POST['lookupTerm'])) {
						$lookupTerm = filter_input(INPUT_POST, 'lookupTerm', FILTER_SANITIZE_STRING);
		        $link = $this->lilurl->getLinkRow($lookupTerm, NULL, PDO::FETCH_OBJ);
		        if (!$link) {
			        $_SESSION['gourlFlashBag'] = array(
				        'msg' => '<p class="title">Not Found</p><p>&apos;' . $lookupTerm . '&apos; is not in use and available.</p>',
				        'type' => 'error',
			        );
		        } else {
			        $this->viewParams['link'] = $link;
			        $group = $this->lilurl->getGroup($link->groupID);
			        if (!empty($group)) {
				        $this->viewParams['group'] = $group;
				        $this->viewParams['group']->users = $this->lilurl->getGroupUsers($link->groupID);
			        }
		        }
	        }
        } elseif ('manage' === $this->route) {
            $this->viewTemplate = 'manage.php';

            if (isset($_POST, $_POST['urlID'])) {
                $this->lilurl->deleteURL($_POST['urlID'], $this->auth->getUserId());
                $_SESSION['gourlFlashBag'] = array(
                    'msg' => '<p class="title">Delete Successful</p><p>Your Go URL has been deleted</p>',
                    'type' => 'success',
                );
	              $this->redirect($this->lilurl->getBaseUrl('a/links'));
            }
        } elseif ('groups' === $this->route) {
            $this->viewTemplate = 'groups.php';

            if (isset($_POST, $_POST['groupID'])) {
              $this->lilurl->deleteGroup($_POST['groupID'], $this->auth->getUserId());
              $_SESSION['gourlFlashBag'] = array(
                'msg' => '<p class="title">Delete Successful</p><p>Your group has been deleted</p>',
                'type' => 'success',
              );

	            $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
            }
        } elseif ('group' === $this->route) {
	        $redirect = true;
	        $this->viewTemplate = 'group.php';
	        $this->viewParams['groupMode'] = $this->groupMode;

	        if ($this->groupMode === self::MODE_EDIT) {
		        $this->viewParams['group'] = $this->lilurl->getGroup($this->groupId);
		        $this->viewParams['group']->users = $this->lilurl->getGroupUsers($this->groupId);
	        }

	        if (!empty($_POST)) {
		        $error = '';
		        $msg = '';
		        $type = '';
		        if (!$this->lilurl->isValidGroupName($_POST['groupName'], $this->groupId, $error)) {
			        $msg = '<p class="title">Invalid Group</p><p>' . $error . '</p>';
			        $type = 'error';
			        $redirect = false;
		        } else {
			        if ($this->groupMode === self::MODE_CREATE) {
				        if ($this->lilurl->insertGroup($_POST, $this->auth->getUserId())) {
					        $msg = '<p class="title">Add Successful</p><p>Your group has been added.</p>';
					        $type = 'success';
				        } else {
					        $msg = '<p class="title">Add Failed</p><p>Your group has not been added.</p>';
					        $type = 'error';
				        }
			        } elseif ($this->groupMode === self::MODE_EDIT && $this->groupId === $_POST['groupID']) {
				        if ($this->lilurl->updateGroup($_POST, $this->auth->getUserId())) {
					        $msg = '<p class="title">Update Successful</p><p>Your group has been updated.</p>';
					        $type = 'success';
				        }
			        } else {
				        $msg = '<p class="title">Update Failed</p><p>Your group has not been updated.</p>';
				        $type = 'error';
			        }
		        }

		        if (!empty($msg) && !empty($type)) {
			        $_SESSION['gourlFlashBag'] = array('msg' => $msg, 'type' => $type);
		        }

		        if ($redirect) {
			        $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
		        }
	        }
        } elseif ('add-group-user' === $this->route) {
	        $this->viewTemplate = 'group.php';
	        $this->viewParams['groupMode'] = $this->groupMode;
	        $msg = '';
	        $type = '';
	        $error = '';

	        if ($this->lilurl->isValidGroupUser($this->uid, $error)) {
		        if ($this->lilurl->insertGroupUser($this->groupId, $this->uid, $this->auth->getUserId())) {
			        $msg = '<p class="title">Add Successful</p><p>User, ' . $this->uid . ' added to group.</p>';
			        $type = 'success';
			        $_POST['uid'] = NULL;
		        } else {
			        $msg = '<p class="title">Add Failed</p><p>User, ' . $this->uid . ' not added to group.</p>';
			        $type = 'error';
		        }
	        } else {
		        $msg = '<p class="title">Add Failed</p><p>' . $error . '</p>';
		        $type = 'error';
	        }

	        if ($this->groupMode === self::MODE_EDIT) {
		        $this->viewParams['group'] = $this->lilurl->getGroup($this->groupId);
		        $this->viewParams['group']->users = $this->lilurl->getGroupUsers($this->groupId);
	        }

	        if (!empty($msg) && !empty($type)) {
		        $_SESSION['gourlFlashBag'] = array('msg' => $msg, 'type' => $type);
	        }
        } elseif ($this->route === self::ROUTE_NAME_REMOVE_USER) {
	        if (!empty($this->groupId) && $this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
		        if ($this->lilurl->deleteGroupUser($this->groupId, $this->uid, $this->auth->getUserId())) {
			        $msg = '<p class="title">Delete Successful</p><p>' . $this->uid . ' has been removed from group.</p>';
			        $type = 'success';
		        } else {
			        $msg = '<p class="title">Delete Failed</p><p>Unable to remove ' . $this->uid . ' from group.</p>';
			        $type = 'error';
		        }

		        $_SESSION['gourlFlashBag'] = array('msg' => $msg, 'type' => $type);
		        $this->redirect($this->lilurl->getBaseUrl('a/group/' . $this->groupId));
	        }

	        // Not authorized to delete user from group
	        $_SESSION['gourlFlashBag'] = array(
		        'msg' => '<p class="title">Access Denied</p><p>Unable to remove ' . $this->uid . ' from group.</p>',
		        'type' => 'error'
	        );
	        $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
        }  elseif ('edit' === $this->route) {
            if (!$this->auth->isAuthenticated() || !$this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
                header('HTTP/1.1 404 Not Found');
                include __DIR__ . '/../www/templates/404.php';
                exit;
            }
            if ($this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
                $this->viewTemplate = 'index.php';
                $this->viewParams['goURL'] =  $this->lilurl->getLinkRow($this->goId, NULL, PDO::FETCH_ASSOC);
            } else {
                $_SESSION['gourlFlashBag'] = array(
                    'msg' => '<p class="title">Not Authorized</p><p>You are not the owner of the Go URL.</p>',
                    'type' => 'error',
                );

	              $this->redirect($this->lilurl->getBaseUrl() . self::ROUTE_PATH_LINKS);
            }
        } elseif ('reset' === $this->route) {
            if (!$this->auth->isAuthenticated() || !$this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
                header('HTTP/1.1 404 Not Found');
                include __DIR__ . '/../www/templates/404.php';
                exit;
            }
            if ($this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
                $this->lilurl->resetRedirectCount($this->goId, $this->auth->getUserId());
                $_SESSION['gourlFlashBag'] = array(
                    'msg' => '<p class="title">Reset Successful</p><p>Your Go URL redirect count has been reset.</p>',
                    'type' => 'success',
                );
            } else {
                $_SESSION['gourlFlashBag'] = array(
                    'msg' => '<p class="title">Not Authorized</p><p>You are not the owner of the Go URL.</p>',
                    'type' => 'error',
                );
            }

	          $this->redirect($this->lilurl->getBaseUrl() . self::ROUTE_PATH_LINKS);
        } elseif ('qr' === $this->route) {
            if (!$this->lilurl->getURL($this->goId)) {
                header('HTTP/1.1 404 Not Found');
                exit;
            }

            $shortURL = $this->lilurl->getShortURL($this->goId);
            $pngPrefix = __DIR__ . '/../data/qr/';
            $qrCache = $pngPrefix . 'cache/' . sha1($shortURL) . '.png';

            if (!file_exists($qrCache)) {
                $qrCode = new QrCode();
                $qrCode->setText($shortURL)
                    ->setSize(1080)
                    ->setPadding(36)
                    ->save($qrCache);
            }

            $out = imagecreatefrompng($qrCache);
            $qrIcon = !empty($this->qrIconPNG) && file_exists($this->qrIconPNG) ? $this->qrIconPNG : $pngPrefix . static::DEFAULT_QR_ICON_NAME;
            $n = imagecreatefrompng($qrIcon);

            imagecopy($out, $n, 422, 428, 0, 0, 235, 235);
            imagedestroy($n);
            header('Content-Type: image/png');
            imagepng($out);
            imagedestroy($out);
            exit;
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

    public function getFlashBagParams() {
        $error = false;
        $msg = '';
        $url = '';

        if (isset($_SESSION['gourlFlashBag'])) {
            $msg = $_SESSION['gourlFlashBag']['msg'];

            if ('error' === $_SESSION['gourlFlashBag']['type']) {
                $error = true;
            }

            if (isset($_SESSION['gourlFlashBag']['url'])) {
                $url = $_SESSION['gourlFlashBag']['url'];
            }

            unset($_SESSION['gourlFlashBag']);
        }

        return array(
            'msg' => $msg,
            'url' => $url,
            'error' => $error
        );
    }

    private function sendCORSHeaders() {
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST');
            header('Access-Control-Allow-Headers: X-Requested-With');
        }
    }
}