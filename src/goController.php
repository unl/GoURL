<?php
use Endroid\QrCode\QrCode;
use Ramsey\Uuid\Uuid;

class GoController
{
    const MODE_CREATE = 'create';
    const MODE_EDIT = 'edit';
    const DEFAULT_QR_ICON_NAME = 'icons/blank_qr_235.png';
    private $auth;
    private $lilurl;
    private $route;
    private $groupId;
    private $groupMode;
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

        // See if already logged in via PHP CAS
        if ($this->auth->getAuthType() === $this->auth::AUTH_TYPE_CAS && array_key_exists('unl_sso', $_COOKIE) && !$this->auth->isAuthenticated()) {
          // Run PHPCAS checkAuthentication
          $this->auth->checkAuthentication();
        }

        if (isset($_GET['login']) || 'a/login' === $this->pathInfo) {
            $this->auth->login();
            header('Location: ' . $this->lilurl->getBaseUrl('a/links'));
            exit;
        }

        if (isset($_GET['logout']) || 'a/logout' === $this->pathInfo) {
            session_destroy();
            $this->auth->logout();
            header('Location: ' . $this->lilurl->getBaseUrl());
            exit;
        }

        if (isset($_GET['manage']) || in_array($this->pathInfo, array('a/', 'a/links'))) {
            $this->route = 'manage';

            if (!$this->auth->isAuthenticated()) {
                header('Location: ' . $this->lilurl->getBaseUrl('a/login'));
                exit;
            }
        }

        if (isset($_GET['groups']) || in_array($this->pathInfo, array('a/', 'a/groups'))) {
          $this->route = 'groups';

          if (!$this->auth->isAuthenticated()) {
            header('Location: ' . $this->lilurl->getBaseUrl('a/login'));
            exit;
          }
        }

        if (preg_match('/^a\/group\/(\d+)$/', $this->pathInfo, $matches)) {
          if (isset($matches[1]) && $this->lilurl->isGroup($matches[1])) {
            $this->route = 'group';
            $this->groupId = $matches[1];
            $this->groupMode = self::MODE_EDIT;

            if (!$this->auth->isAuthenticated()) {
              header('Location: ' . $this->lilurl->getBaseUrl('a/login'));
              exit;
            } elseif (!$this->lilurl->isGroupMember($this->groupId, $this->auth->getUserId())) {
              $_SESSION['gourlFlashBag'] = array(
                'msg' => '<p class="title">Access Denied</p><p>You are not a member of this group.</p>',
                'type' => 'error'
              );
              header('Location: ' . $this->lilurl->getBaseUrl('a/groups'));
              exit;
            }

            if (!empty($_POST)) {
							switch($_POST['formName']) {
		            case 'group-form':
			            $this->route = 'group';
			            break;

		            case 'user-form':
			            $this->route = 'add-group-user';
			            break;

		            default:
									// missing or unexpected form so bail
			            header('Location: ' . $this->lilurl->getBaseUrl($this->pathInfo));
			            exit;
	            }
            }
          }
        }

        if ($this->pathInfo === 'a/group') {
            $this->route = 'group';
            $this->groupId = NULL;
            $this->groupMode = self::MODE_CREATE;

            if (!$this->auth->isAuthenticated()) {
              header('Location: ' . $this->lilurl->getBaseUrl('a/login'));
              exit;
            }
        }

	      if (preg_match('/^a\/removeuser\/(\d+)-(\w+)$/', $this->pathInfo, $matches)) {
		      if (!$this->auth->isAuthenticated()) {
			      header('Location: ' . $this->lilurl->getBaseUrl('a/login'));
			      exit;
		      }

		      $groupID = $matches[1];
		      $uid = urldecode($matches[2]);

		      if (isset($matches[1]) && $this->lilurl->isGroupMember($groupID, $this->auth->getUserId())) {

			      if ($this->lilurl->deleteGroupUser($groupID, $uid, $this->auth->getUserId())) {
				      $msg = '<p class="title">Delete Successful</p><p>' . $uid . ' has been removed from group.</p>';
				      $type = 'success';
			      } else {
				      $msg = '<p class="title">Delete Failed</p><p>Unable to remove ' . $uid . ' from group.</p>';
					    $type = 'error';
			      }

			      $_SESSION['gourlFlashBag'] = array('msg' => $msg, 'type' => $type);
			      header('Location: ' . $this->lilurl->getBaseUrl('a/group/' . $groupID));
			      exit;
		      }

		      // Not authorized to delete user from group
		      $_SESSION['gourlFlashBag'] = array(
			      'msg' => '<p class="title">Access Denied</p><p>Unable to remove ' . $uid . ' from group.</p>',
			      'type' => 'error'
		      );
		      header('Location: ' . $this->lilurl->getBaseUrl('a/groups'));
		      exit;

	      }

        if ('api_create.php' === $this->pathInfo) {
            header('Location: ' . $this->lilurl->getBaseUrl('api/'), true, 307);
            $this->sendCORSHeaders();
            exit;
        }

        if (!isset($_SESSION['clientId'])) {
            $_SESSION['clientId'] = (string) Uuid::uuid4();
        }
        $this->lilurl->setGaClientId($_SESSION['clientId']);
    }

    public function route() {
        if ('api/' === $this->pathInfo) {
            $this->route = 'api';
        } elseif (preg_match('#^([^/]+)\.qr$#', $this->pathInfo, $matches)) {
            $this->route = 'qr';
            $this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/edit$#', $this->pathInfo, $matches)) {
            $this->route = 'edit';
            $this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/reset$#', $this->pathInfo, $matches)) {
            $this->route = 'reset';
            $this->goId = $matches[1];
        } elseif (preg_match('#^([^/]+)\/info$#', $this->pathInfo, $matches)) {
            $this->route = 'linkinfo';
            $this->goId = $matches[1];
        }

        if (!$this->route && $this->pathInfo !== '') {
            $this->route = 'redirect';
        }
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
                        header('Location: ' . $this->lilurl->getBaseUrl('a/links'));
                        exit;
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

                header('Location: ' . $this->lilurl->getBaseUrl(), true, 303);
                exit;
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
        } elseif ('manage' === $this->route) {
            $this->viewTemplate = 'manage.php';

            if (isset($_POST, $_POST['urlID'])) {
                $this->lilurl->deleteURL($_POST['urlID'], $this->auth->getUserId());
                $_SESSION['gourlFlashBag'] = array(
                    'msg' => '<p class="title">Delete Successful</p><p>Your Go URL has been deleted</p>',
                    'type' => 'success',
                );
                header('Location: ' . $this->lilurl->getBaseUrl('a/links'));
                exit;
            }
        } elseif ('groups' === $this->route) {
            $this->viewTemplate = 'groups.php';

            if (isset($_POST, $_POST['groupID'])) {
              $this->lilurl->deleteGroup($_POST['groupID'], $this->auth->getUserId());
              $_SESSION['gourlFlashBag'] = array(
                'msg' => '<p class="title">Delete Successful</p><p>Your group has been deleted</p>',
                'type' => 'success',
              );

              header('Location: ' . $this->lilurl->getBaseUrl('a/groups'));
              exit;
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
			        header('Location: ' . $this->lilurl->getBaseUrl('a/groups'));
			        exit;
		        }
	        }
        } elseif ('add-group-user' === $this->route) {
	        $this->viewTemplate = 'group.php';
	        $this->viewParams['groupMode'] = $this->groupMode;
	        $msg = '';
	        $type = '';
	        $uid = trim($_POST['uid']);
					$error = '';

	        if ($this->lilurl->isValidGroupUser($uid, $error)) {
		        if ($this->lilurl->insertGroupUser($this->groupId, $uid, $this->auth->getUserId())) {
			        $msg = '<p class="title">Add Successful</p><p>User, ' . $uid . ' added to group.</p>';
			        $type = 'success';
			        $_POST['uid'] = NULL;
		        } else {
			        $msg = '<p class="title">Add Failed</p><p>User, ' . $uid . ' not added to group.</p>';
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

        }  elseif ('edit' === $this->route) {
            if (!$this->auth->isAuthenticated() || !$this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
                header('HTTP/1.1 404 Not Found');
                include __DIR__ . '/../www/templates/404.php';
                exit;
            }
            if ($this->lilurl->userHasURLAccess($this->goId, $this->auth->getUserId())) {
                $this->viewTemplate = 'index.php';
                $this->viewParams['goURL'] =  $this->lilurl->getLinkRow($this->goId);
            } else {
                $_SESSION['gourlFlashBag'] = array(
                    'msg' => '<p class="title">Not Authorized</p><p>You are not the owner of the Go URL.</p>',
                    'type' => 'error',
                );

                header('Location: ' . $this->lilurl->getBaseUrl() . 'a/links', true, 303);
                exit;
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

            header('Location: ' . $this->lilurl->getBaseUrl() . 'a/links', true, 303);
            exit;
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
        } elseif ('linkinfo' === $this->route) {
            $this->viewTemplate = 'linkinfo.php';

            if (!$link = $this->lilurl->getLinkRow($this->goId)) {
                header('HTTP/1.1 404 Not Found');
                include __DIR__ . '/../www/templates/404.php';
                exit;
            }

            $this->viewParams['link'] = $link;
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