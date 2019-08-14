<?php
include_once 'base.php';

class CreateRecipientUri extends Base {
    protected static $signer_mail;
    protected static $signer_name;
    protected static $client_user_id;
    protected static $return_url;
    protected static $recipient_id;
    protected static $envelope_id;

    public function __construct($client, $signer_mail, $signer_name, $client_user_id, $recipient_id, $envelope_id, $return_url) {
        parent::__construct($client, $return_url);
        self::$signer_mail = $signer_mail;
        self::$signer_name = $signer_name;
        self::$client_user_id = $client_user_id;
        self::$recipient_id = $recipient_id;
        self::$envelope_id = $envelope_id;
        self::$return_url = $return_url;
    }

    public function create() {
        $this->checkToken();
        $recipient_view_request = new DocuSign\eSign\Model\RecipientViewRequest(array(
          'email' => self::$signer_mail,
          'user_name' => self::$signer_name,
          'recipient_id' => self::$recipient_id,
          'client_user_id' => self::$client_user_id,
          'authentication_method' => 'email',
          'return_url' => self::$return_url
        ));

        $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi(self::$apiClient);

        $results = $envelopeApi->createRecipientView(self::$accountID, self::$envelope_id, $recipient_view_request);

        return $results;
    }
}
