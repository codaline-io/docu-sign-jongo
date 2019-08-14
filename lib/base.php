<?php
use \Firebase\JWT\JWT;

class Base {

   const TOKEN_EXPIRATION_IN_SECONDS = 3600; # 1 hour
   const TOKEN_REPLACEMENT_IN_SECONDS = 600; # 10 minutes

   private $permission_scopes="signature%20impersonation";

   protected static $expires_in;
   protected static $access_token;
   protected static $expiresInTimestamp;
   protected static $accountID;
   protected static $base_uri;
   protected static $account;
   protected static $apiClient;
   protected static $redirect_uri;

   public function __construct($client, $redirect_uri) {
       self::$apiClient = $client;
       self::$redirect_uri = $redirect_uri;
    }

    protected function checkToken() {
        if (is_null(self::$access_token)
                || (time() + Base::TOKEN_REPLACEMENT_IN_SECONDS) > self::$expiresInTimestamp) {
            $this->updateToken();
        }
    }

    private function updateToken(){
        $this->authToken = $this->configureJwtAuthorizationFlowByKey();
        self::$expiresInTimestamp = time() + self::$expires_in;

        if(is_null(self::$account)) {
            self::$account = $this->getUserInfo();
        }

        self::$accountID = self::$account->{"account_id"};
        self::$base_uri = self::$account->{"base_uri"}."/restapi";
        $config = self::$apiClient->getConfig();
        $config->setHost(self::$base_uri);
    }
    /**
     *
     */
    private function configureJwtAuthorizationFlowByKey() {
        $current_time = time ();
        $aud = DSConfig::aud();
        $iss = DSConfig::client_id();

        $_token = array(
            "iss" => $iss,
            "sub" => DSConfig::impersonated_user_guid(),
            "aud" => $aud,
            "scope" => DSConfig::jwt_scope(),
            "nbf" => $current_time,
            "exp" => $current_time + 60*1000
        );

        $private_key = DSConfig::private_key();
        $jwt = JWT::encode($_token, $private_key, "RS256");

        $headers = array("Accept" => "application/json");
        $data = array("grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer", "assertion" => $jwt);
        $body = Unirest\Request\Body::form($data);
        $response = Unirest\Request::post("https://{$aud}/oauth/token", $headers, $body);
        // Handle the response if it is an html page
        if (strpos($response->raw_body, "<html>") !== false) {
            throw new Exception("An error response was received!\n\n");
        }

        $json = $response->body;
        $redirect_uri = self::$redirect_uri;

        if (property_exists ($json, "error") and $json->{"error"} == "consent_required" ){
            $consent_url = "https://{$aud}/oauth/auth?response_type=token&scope={$this->permission_scopes}&client_id={$iss}&redirect_uri={$redirect_uri}";
            throw new Exception("\n\nC O N S E N T   R E Q U I R E D\n"
            ."Ask the user who will be impersonated to run the following url:\n"
            ."    {$consent_url}\n"
            ."It will ask the user to login and to approve access by your application.\n\n"
            ."Alternatively, an Administrator can use Organization Administration to\n"
            ."pre-approve one or more users.\n\n", 401);
        }

        if (property_exists ($json, "error") or !property_exists ($json, "access_token")){
            throw new Exception("\n\nUnexpected error: {$json->{"error"}}\n\n");
        }

        self::$access_token = $json->{"access_token"};
        self::$expires_in = $json->{"expires_in"};

        $config = self::$apiClient->getConfig();
        $config->setAccessToken(self::$access_token);
        $config->addDefaultHeader("Authorization" , "Bearer ".self::$access_token);
    }

    private function getUserInfo(){
        $aud = DSConfig::aud();
        $access_token = self::$access_token;
        $user_info_url="https://{$aud}/oauth/userinfo";
        $headers = array("Accept" => "application/json", "Authorization" => "Bearer {$access_token}");
        $response = Unirest\Request::get($user_info_url, $headers);

        $json = $response->body;
        $target = DSConfig::target_account_id();
        $accounts = $json->{"accounts"};

        if(is_null($target) or empty($target) or $target === "FALSE"){
            foreach($accounts as $acct){
                if($acct->{"is_default"} === true) {
                    return $acct;
                }
            }
        }

        # Looking for a specific user account
        foreach($accounts as $acct){
            if($acct->{"account_id"} === $target){
                return $acct;
            }
        }
        throw new Exception("\n\nUser does not have access to account {$target}\n\n");
    }
}
