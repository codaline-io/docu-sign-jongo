<?php
  require_once('vendor/autoload.php');
  require_once('vendor/docusign/esign-client/autoload.php');

  include_once 'ds_config.php';
  include_once 'lib/base.php';
  include_once 'lib/send_envelope.php';
  include_once 'lib/create_recipient_uri.php';

  $config = new DocuSign\eSign\Configuration();
  $apiClient = new DocuSign\eSign\ApiClient($config);

  if (isset($_SERVER['HTTP_ORIGIN'])) {
    // header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Origin: *");
    header('Access-Control-Allow-Credentials: true');
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  }
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
      header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
      header("Access-Control-Allow-Headers:{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
  }

  // get the HTTP method, path and body of the request
  $method = $_SERVER['REQUEST_METHOD'];
  $input = json_decode(file_get_contents('php://input'),true);

  // Only Post is allowed
  if ($method != 'POST' && $method != 'OPTIONS') {
    http_response_code(404);
    // 404 error that request method is not
    echo json_encode((object) ['message' => 'request_method_not_allowed']);

    return;
  }

  try {
    $email_subject = $_POST["email_subject"];
    $doc_b64 = $_POST["pdf_base64_content"];
    $pdf_title = $_POST["pdf_title"];
    $email = $_POST["email"];
    $name = $_POST["name"];
    $return_url = $_POST["return_url"];
    $sign_pos_x = $_POST["pos_x"] ?: '20';
    $sign_pos_y = $_POST["pos_y"] ?: '20';
    $sign_page = $_POST["page"] ?: '1';
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
      header('Location: '.$uri);
      die();
    } else {
      throw new Exception('signing_uri_not_created');
    }
  } catch (Exception $e) {
    if ($e instanceof DocuSign\eSign\ApiException) {
      echo json_encode((object) ['error' => $e->getResponseObject()]);
    } else {
      echo json_encode((object) ['error' => $e->getMessage()]);
    }
  }
?>
