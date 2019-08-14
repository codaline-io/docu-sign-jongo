<?php
  require_once('vendor/autoload.php');
  require_once('vendor/docusign/esign-client/autoload.php');

  include_once 'ds_config.php';
  include_once 'lib/base.php';
  include_once 'lib/send_envelope.php';
  include_once 'lib/create_recipient_uri.php';

  # params:
  ## email_subject: Title of the Email send to sign the document
  ## pdf_base64_content: pdf document as base64
  ## pdf_title: Title of the pdf document
  ## email: one or array of email addresses used as signer mails
  ## name: one or array of names used as signer names
  ## return_url: A valid return url. The user gets redirected to if the document was successfully signed
  ## pos_x: x coords of the sign field
  ## pos_y: y coords of the sign field
  ## page: page number for the sign field

  function createEnvelopsAndGetViewUri($params) {
    $config = new DocuSign\eSign\Configuration();
    $apiClient = new DocuSign\eSign\ApiClient($config);

    $email_subject = $params["email_subject"] ?: 'Ein Dokument wartet auf Ihre Unterschrift';
    $doc_b64 = $params["pdf_base64_content"];
    $pdf_title = $params["pdf_title"];
    $email = $params["email"];
    $name = $params["name"];
    $return_url = $params["return_url"];
    $sign_pos_x = $params["pos_x"] ?: '20';
    $sign_pos_y = $params["pos_y"] ?: '20';
    $sign_page = $params["page"] ?: '1';
    $first_client_user_id = '1';
    $first_recipient_id = '1';

    if (is_array($name) == false) {
      $name = array($name);
    }
    if (is_array($email) == false) {
      $email = array($email);
    }

    $send_envelope_handler = new SendEnvelope($apiClient, $email, $name, $doc_b64, $pdf_title, $sign_pos_x, $sign_pos_y, $sign_page, $return_url, $email_subject);
    $envelope_result = $send_envelope_handler->send();

    $create_recipient_uri_handler = new CreateRecipientUri($apiClient, array_values($email)[0], array_values($name)[0], $first_client_user_id, $first_recipient_id, $envelope_result->getEnvelopeId(), $return_url);
    $result = $create_recipient_uri_handler->create();

    $uri = $result->getUrl();

    if ($uri) {
      return $uri;
    } else {
      throw new Exception('signing_uri_not_created');
    }
  }
?>
