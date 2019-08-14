<?php
  class DSConfig {
    private $config;
    private static $instance;

    private static function getInstance() {
      if(is_null(self::$instance)){
        self::$instance = new DSConfig();
      }

      return self::$instance;
    }

    public function __construct() {
      date_default_timezone_set("UTC");

      $clientId = getenv("DS_CLIENT_ID");
      if (!is_null($clientId) and !empty($clientId)) {
        $this->config["DS_CLIENT_ID"] = $clientId;
        $this->config["DS_AUTH_SERVER"] = getenv("DS_AUTH_SERVER");
        $this->config["DS_IMPERSONATED_USER_GUID"] = getenv("DS_IMPERSONATED_USER_GUID");
        $this->config["DS_TARGET_ACCOUNT_ID"] = getenv("DS_TARGET_ACCOUNT_ID");
        $this->config["DS_PRIVATE_KEY"] = getenv("DS_PRIVATE_KEY");
      } else {
        $this->config = parse_ini_file('ds_config.ini', true);
      }
    }

    private function _auth_server() {
      return $this->config["DS_AUTH_SERVER"];
    }
    public static  function auth_server() {
      return self::getInstance()->_auth_server();
    }
    private function _client_id() {
      return $this->config["DS_CLIENT_ID"];
    }
    public static  function client_id() {
      return self::getInstance()->_client_id();
    }
    private function _impersonated_user_guid() {
      return $this->config["DS_IMPERSONATED_USER_GUID"];
    }
    public static  function impersonated_user_guid() {
      return self::getInstance()->_impersonated_user_guid();
    }
    private function _target_account_id() {
      return $this->config["DS_TARGET_ACCOUNT_ID"];
    }
    public static  function target_account_id(){
      return self::getInstance()->_target_account_id();
    }
    private function _private_key() {
      return $this->config["DS_PRIVATE_KEY"];
    }
    public static function private_key(){
      return self::getInstance()->_private_key();
    }
    public static function aud() {
      $auth_server = self::getInstance()->_auth_server();

      if (strpos($auth_server, 'https://') !== false) {
        $aud = substr($auth_server, 8);
      } else { # assuming http://blah
        $aud = substr($auth_server, 7);
      }
      return $aud;
    }
    public static function api() {
      return "restapi/v2";
    }
    public static function jwt_scope() {
      return "signature";
    }
  }
