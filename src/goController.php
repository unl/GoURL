<?php
use Endroid\QrCode\QrCode;
use Ramsey\Uuid\Uuid;

class GoFlashBag {
	const FLASH_BAG_SESSION_NAME = 'gourlFlashBag';
	const FLASH_BAG_ATTR_MESSAGE = 'message';
	const FLASH_BAG_ATTR_TYPE = 'type';
	const FLASH_BAG_ATTR_URL = 'url';
	const FLASH_BAG_TYPE_ERROR = 'error';
	const FLASH_BAG_TYPE_SUCCESS = 'success';

	public function setParams($message, $type = self::FLASH_BAG_TYPE_SUCCESS, $url = NULL) {
		unset($_SESSION[self::FLASH_BAG_SESSION_NAME]);
		$_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_TYPE] = $type;
		$_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_MESSAGE] = $message;
		if (!empty($url)) {
			$_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_URL] = $url;
		}
	}

	public function clearParams() {
		unset($_SESSION[self::FLASH_BAG_SESSION_NAME]);
	}

	public function getParams() {
		$error = false;
		$msg = '';
		$url = '';

		if (isset($_SESSION[self::FLASH_BAG_SESSION_NAME])) {
			$msg = $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_MESSAGE];

			if (self::FLASH_BAG_TYPE_ERROR === $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_TYPE]) {
				$error = true;
			}

			if (isset($_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_URL])) {
				$url = $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_URL];
			}

			unset($_SESSION[self::FLASH_BAG_SESSION_NAME]);
		}

		return array(
			'msg' => $msg,
			'url' => $url,
			'error' => $error
		);
	}
}

class GoController {
    const MODE_CREATE = 'create';
    const MODE_EDIT = 'edit';
    const DEFAULT_QR_ICON_NAME = 'icons/blank_qr_235.png';

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
		const ROUTE_NAME_QR = 'qr';
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
    private $flashBag;

    // Public State
    public static $appName;
    public static $institution;
    public static $themePath;
    public static $customThemeTemplate;
    public static $template;
    public static $templateVersion;

    public function __construct($lilurl, $auth, $flashBag, $qrIconPNG) {
        $this->lilurl = $lilurl;
        $this->auth = $auth;
        $this->qrIconPNG = $qrIconPNG;
        $this->flashBag = $flashBag;

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
				self::ROUTE_NAME_EDIT,
				self::ROUTE_NAME_GROUP,
				self::ROUTE_NAME_GROUP_USER_ADD,
				self::ROUTE_NAME_GROUP_USER_REMOVE,
				self::ROUTE_NAME_GROUPS,
				self::ROUTE_NAME_LINKS,
				self::ROUTE_NAME_LOOKUP,
				self::ROUTE_NAME_MANAGE,
				self::ROUTE_NAME_REDIRECT,
				self::ROUTE_NAME_RESET
			));
    }

    private function loginCheck() {
	    if ($this->routeRequiresLogin() && !$this->auth->isAuthenticated()) {
		    $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LOGIN));
	    }
    }

    private function verifyGroup() {
	    if (isset($this->groupId)) {
		    if (!$this->lilurl->isGroup($this->groupId)) {
			    $this->flashBag->setParams('<p class="title">Not Found</p><p>The group is not found.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
			    $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS), 404);

		    } elseif (!$this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
			    $this->flashBag->setParams('<p class="title">Access Denied</p><p>You are not a member of this group.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
			    $this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS), 403);
		    }
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

    public function route() {
				$this->route = NULL;

		    if (isset($_GET['manage']) || in_array($this->pathInfo, array(self::ROUTE_PATH_A, self::ROUTE_PATH_LINKS))) {
			    $this->route = self::ROUTE_NAME_MANAGE;
		    } elseif (isset($_GET['lookup']) || $this->pathInfo === self::ROUTE_PATH_LOOKUP) {
			    $this->route = self::ROUTE_NAME_LOOKUP;
		    } elseif (preg_match('/^a\/group\/(\d+)$/', $this->pathInfo, $matches)) {
			    $this->route = self::REOUTE_NAME_GROUP;
			    $this->groupId = $matches[1];
			    $this->groupMode = self::MODE_EDIT;
		    } elseif ($this->pathInfo === self::ROUTE_PATH_GROUP) {
			    $this->route = self::REOUTE_NAME_GROUP;
			    $this->groupId = NULL;
			    $this->groupMode = self::MODE_CREATE;
		    } elseif ($this->pathInfo === self::ROUTE_PATH_GROUPS) {
			    $this->route = self::ROUTE_NAME_GROUPS;
		    } elseif (preg_match('/^a\/group-user-add\/(\d+)$/', $this->pathInfo, $matches)) {
			    $this->route = self::ROUTE_NAME_GROUP_USER_ADD;
			    $this->groupId = $matches[1];
			    $this->uid = filter_input(INPUT_POST, 'uid', FILTER_SANITIZE_STRING);
        } elseif (preg_match('/^a\/group-user-remove\/(\d+)-(\w+)$/', $this->pathInfo, $matches)) {
			    $this->route = self::ROUTE_NAME_GROUP_USER_REMOVE;
			    $this->groupId = $matches[1];
			    $this->uid = urldecode($matches[2]);
		    } elseif (preg_match('#^([^/]+)\.qr$#', $this->pathInfo, $matches)) {
					$this->route = self::ROUTE_NAME_QR;
					$this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/edit$#', $this->pathInfo, $matches)) {
					$this->route = self::ROUTE_NAME_EDIT;
					$this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/reset$#', $this->pathInfo, $matches)) {
					$this->route = self::ROUTE_NAME_RESET;
					$this->goId = $matches[1];
        } elseif (empty($this->pathInfo)) {
					$this->route = self::ROUTE_NAME_HOME;
				} elseif ($this->pathInfo === self::ROUTE_PATH_API) {
					$this->route = self::ROUTE_NAME_API;
				}

	      if (!$this->route && $this->pathInfo !== '') {
            $this->route = self::ROUTE_NAME_REDIRECT;
        }

        // check login for protected routes
	      $this->loginCheck();

				// verify group and group access if set
				$this->verifyGroup();
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

        switch($this->route) {
	        case self::ROUTE_NAME_API:
	        case self::ROUTE_NAME_HOME:
						$this->handleRouteHomePage();
						break;

	        case self::ROUTE_NAME_EDIT:
						$this->handleRouteURLEdit();
						break;

	        case self::ROUTE_NAME_RESET:
		        $this->handleRouteURLReset();
		        break;

	        case self::ROUTE_NAME_QR:
						$this->handleRouteURLQRCode();
						break;

	        case self::ROUTE_NAME_LOOKUP:
	        	$this->handleRouteLookup();
	        	break;

	        case self::ROUTE_NAME_MANAGE:
	        	$this->handleRouteManage();
	        	break;

	        case self::ROUTE_NAME_GROUPS:
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

	        default:
		        $this->handle404();
	        	break;
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

    private function sendCORSHeaders() {
        if (!empty($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST');
            header('Access-Control-Allow-Headers: X-Requested-With');
        }
    }

		private function handle404($showCustom = TRUE) {
			header('HTTP/1.1 404 Not Found');
			if ($showCustom === TRUE) {
				include __DIR__ . '/../www/templates/404.php';
			}
			exit;
		}

		private function handleRouteLookup() {
			$this->viewTemplate = 'linkinfo.php';

			if (isset($_POST, $_POST['lookupTerm'])) {
				$lookupTerm = filter_input(INPUT_POST, 'lookupTerm', FILTER_SANITIZE_STRING);
				$link = $this->lilurl->getLinkRow($lookupTerm, NULL, PDO::FETCH_OBJ);
				if (!$link) {
					$this->flashBag->setParams('<p class="title">Not Found</p><p>&apos;' . $lookupTerm . '&apos; is not in use and available.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
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
				$this->lilurl->deleteURL($urlID, $this->auth->getUserId());
				$this->flashBag->setParams('<p class="title">Delete Successful</p><p>Your URL has been deleted.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
				$this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
			}
		}

		private function handleRouteGroups() {
			$this->viewTemplate = 'groups.php';

			if (isset($_POST, $_POST['groupID'])) {
				$groupID = filter_input(INPUT_POST, 'groupID', FILTER_SANITIZE_NUMBER_INT);
				$this->lilurl->deleteGroup($groupID, $this->auth->getUserId());
				$this->flashBag->setParams('<p class="title">Delete Successful</p><p>Your group has been deleted.</p>');
				$this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
			}
		}

		private function handleRouteGroup() {
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
				$groupName = filter_input(INPUT_POST, 'groupName', FILTER_SANITIZE_STRING);
				if (!$this->lilurl->isValidGroupName($groupName, $this->groupId, $error)) {
					$msg = '<p class="title">Invalid Group</p><p>' . $error . '</p>';
					$type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
					$redirect = false;
				} else {
					if ($this->groupMode === self::MODE_CREATE) {
						if ($this->lilurl->insertGroup($_POST, $this->auth->getUserId())) {
							$msg = '<p class="title">Add Successful</p><p>Your group has been added.</p>';
							$type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
						} else {
							$msg = '<p class="title">Add Failed</p><p>Your group has not been added.</p>';
							$type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
						}
					} elseif ($this->groupMode === self::MODE_EDIT && $this->groupId === $_POST['groupID']) {
						if ($this->lilurl->updateGroup($_POST, $this->auth->getUserId())) {
							$msg = '<p class="title">Update Successful</p><p>Your group has been updated.</p>';
							$type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
						}
					} else {
						$msg = '<p class="title">Update Failed</p><p>Your group has not been updated.</p>';
						$type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
					}
				}

				if (!empty($msg) && !empty($type)) {
					$this->flashBag->setParams($msg, $type);
				}

				if ($redirect) {
					$this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUPS));
				}
			}
		}

		private function handleRouteGroupAdd() {
			$this->viewTemplate = 'group.php';
			$this->viewParams['groupMode'] = $this->groupMode;
			$msg = '';
			$type = '';
			$error = '';

			if ($this->lilurl->isValidGroupUser($this->uid, $error)) {
				if ($this->lilurl->insertGroupUser($this->groupId, $this->uid, $this->auth->getUserId())) {
					$msg = '<p class="title">Add Successful</p><p>User, ' . $this->uid . ' added to group.</p>';
					$type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
					$_POST['uid'] = NULL;
				} else {
					$msg = '<p class="title">Add Failed</p><p>User, ' . $this->uid . ' not added to group.</p>';
					$type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
				}
			} else {
				$msg = '<p class="title">Add Failed</p><p>' . $error . '</p>';
				$type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
			}

			if ($this->groupMode === self::MODE_EDIT) {
				$this->viewParams['group'] = $this->lilurl->getGroup($this->groupId);
				$this->viewParams['group']->users = $this->lilurl->getGroupUsers($this->groupId);
			}

			if (!empty($msg) && !empty($type)) {
				$this->flashBag->setParams($msg, $type);
			}

			$this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUP . '/' . $this->groupId));
		}

		private function handleRouteGroupRemove() {
			if (!empty($this->groupId) && $this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
				if ($this->lilurl->deleteGroupUser($this->groupId, $this->uid, $this->auth->getUserId())) {
					$msg = '<p class="title">Delete Successful</p><p>' . $this->uid . ' has been removed from group.</p>';
					$type = $this->flashBag::FLASH_BAG_TYPE_SUCCESS;
				} else {
					$msg = '<p class="title">Delete Failed</p><p>Unable to remove ' . $this->uid . ' from group.</p>';
					$type = $this->flashBag::FLASH_BAG_TYPE_ERROR;
				}

				$this->flashBag->setParams($msg, $type);
				$this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_GROUP . '/' . $this->groupId));
			}

			// Not authorized to delete user from group
			$this->flashBag->setParams('<p class="title">Access Denied</p><p>Unable to remove ' . $this->uid . ' from group.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
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
				$this->flashBag->setParams('<p class="title">Not Authorized</p><p>You are not the owner of the Go URL.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
				$this->redirect($this->lilurl->getBaseUrl() . self::ROUTE_PATH_LINKS);
			}
		}

		private function handleRouteURLReset() {
			if (!$this->auth->isAuthenticated() || !$this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
				$this->handle404();
			}

			if ($this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
				$this->lilurl->resetRedirectCount($this->goId, $this->auth->getUserId());
				$this->flashBag->setParams('<p class="title">Reset Successful</p><p>Your Go URL redirect count has been reset.</p>');
			} else {
				$this->flashBag->setParams('<p class="title">Not Authorized</p><p>You are not the owner of the Go URL.</p>', $this->flashBag::FLASH_BAG_TYPE_ERROR);
			}

			$this->redirect($this->lilurl->getBaseUrl() . self::ROUTE_PATH_LINKS);
		}

		private function handleRouteURLQRCode() {
			if (!$this->lilurl->getURL($this->goId)) {
				$this->handle404(FALSE);
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

		private function handleRouteHomePage() {
			if (isset($_GET['url']) && $_GET['url'] === 'referer' && isset($_SERVER['HTTP_REFERER'])) {
				$_POST['theURL'] = urldecode($_SERVER['HTTP_REFERER']);
			}

			if (isset($_POST['theURL'])) {
				$mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING) === static::MODE_EDIT ? static::MODE_EDIT : static::MODE_CREATE;
				$userId = $alias = null;

				if ($this->auth->isAuthenticated()) {
					$userId = $this->auth->getUserId();

					if ($mode === static::MODE_EDIT) {
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

					$this->flashBag->setParams('<p class="title">' . $msg . '</p><input type="text" onclick="this.select(); return false;" value="'.$url.'" />', $this->flashBag::FLASH_BAG_TYPE_SUCCESS, $url);
					if ($mode === static::MODE_EDIT) {
						$this->redirect($this->lilurl->getBaseUrl(self::ROUTE_PATH_LINKS));
					}
				} catch (Exception $e) {
					switch ($e->getCode()) {
						case lilURL::ERR_INVALID_PROTOCOL:
							$msg = '<p class="title">Whoops, Something Broke</p><p>Your URL must begin with <code>http://</code>, <code>https://</code>.</p>';
							break;
						case lilURL::ERR_INVALID_DOMAIN:
							$msg = '<p class="title">Whoops, Something Broke</p><p>You must sign in to create a URL for this domain: '.parse_url($_POST['theURL'], PHP_URL_HOST).'</p>';
							break;
						case lilURL::ERR_INVALID_ALIAS:
							$msg = '<p class="title">Whoops, Something Broke</p><p>The custom Alias you provided should only contain letters, numbers, underscores (_), and dashes (-).</p>';
							break;
						case lilURL::ERR_USED:
							$msg = '<p class="title">Whoops, this alias/URL pair already exists.</p><p>The existing Go URL for this pair is: </p>';
							break;
						case lilURL::ERR_ALIAS_EXISTS:
							$msg = '<p class="title">Whoops, This alias is already in use.</p><p>Please use a different alias.</p>';
							break;
						case lilURL::ERR_INVALID_GA_CAMPAIGN:
							$msg = '<p class="title">Whoops, Invalid Google Campaign.</p><p>Please provide all required campaign information.</p>';
							break;
						case lilURL::ERR_INVALID_URL:
							$msg = '<p class="title">Whoops, Invalid URL.</p><p>Please verify the URL is correct.</p>';
							break;
						case lilURL::ERR_MAX_RANDOM_ID_ATTEMPTS:
							$msg = '<p class="title">Whoops, Random Alias Error.</p><p>'. $e->getMessage() . '</p>';
							break;
						default:
							$msg = '<p class="title">Whoops, Something Broke</p><p>There was an error submitting your url. Check your steps.</p>';
					}

					$this->flashBag->setParams($msg, $this->flashBag::FLASH_BAG_TYPE_ERROR);
				}

				if ('api' === $this->route) {
					$this->sendCORSHeaders();
					$this->flashBag->clearParams();

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
		}
}