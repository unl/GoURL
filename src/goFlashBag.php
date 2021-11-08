<?php
class GoFlashBag {
    const FLASH_BAG_SESSION_NAME = 'gourlFlashBag';
    const FLASH_BAG_ATTR_HEADING = 'heading';
    const FLASH_BAG_ATTR_MESSAGE = 'message';
    const FLASH_BAG_ATTR_TYPE = 'type';
    const FLASH_BAG_ATTR_URL = 'url';
    const FLASH_BAG_TYPE_ERROR = 'dcf-notice-danger';
    const FLASH_BAG_TYPE_SUCCESS = 'dcf-notice-success';
    const FLASH_BAG_TYPE_INFO = 'dcf-notice-info';

    public function setParams($heading, $message, $type = self::FLASH_BAG_TYPE_INFO, $url = NULL) {
        unset($_SESSION[self::FLASH_BAG_SESSION_NAME]);
        $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_TYPE] = $type;
        $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_HEADING] = $heading;
        $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_MESSAGE] = $message;
        if (!empty($url)) {
            $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_URL] = $url;
        }
    }

    public function clearParams() {
        unset($_SESSION[self::FLASH_BAG_SESSION_NAME]);
    }

    public function getParams() {
        $type = self::FLASH_BAG_TYPE_INFO;
        $heading = '';
        $msg = '';
        $url = '';

        if (isset($_SESSION[self::FLASH_BAG_SESSION_NAME])) {
            $type = $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_TYPE];
            $heading = $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_HEADING];
            $msg = $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_MESSAGE];

            if (isset($_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_URL])) {
                $url = $_SESSION[self::FLASH_BAG_SESSION_NAME][self::FLASH_BAG_ATTR_URL];
            }

            unset($_SESSION[self::FLASH_BAG_SESSION_NAME]);
        }

        return array(
            'type' => $type,
            'heading' => $heading,
            'msg' => $msg,
            'url' => $url
        );
    }
}
