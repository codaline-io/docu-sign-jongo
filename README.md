# DocuSign Handling for a single pdf with multiple signers

First signer will be redirected (`api.php`) to the DocuSign signing process or it returns the uri to start the process (`lib.php`).

An example usage of the `lib.php` can be found in the `example.lib.php`.

## Prequestion

1. Create and Login to you DocuSign admin Account.
2. Open AdminPanel
3. Create an API under "Integrations" **Store the RSA private and public key in a secret and safe place!**
4. The APIs `Integration key` is the `DS_CLIENT_ID`
5. Grab your DS_IMPERSONATED_USER_GUID from "Users" and edit a user. The GUID is shown as `User name`
6. To add `Redirect URIs` | `Return URLs` open the API and check the `Additional settings` area ;)

## Installation

Requirements: PHP v5.4 or later + composer

1. Download files
2. install composer `curl -sS https://getcomposer.org/installer | php`
3. simply install vendors with `php composer.phar install`
4. Check `example.env` and create a new `.env` file and paste the content of the example and fill in the valid values
5. run the `example.lib.php` or start a php server and call the `api.php`

## Config (`example.env`)

- `DS_CLIENT_ID` - INTEGRATION_KEY # (Go To DocuSign Admin Panel - Integrations - Api And Keys - Add Api and copy integration key)
- `DS_AUTH_SERVER` - default: "https://account-d.docusign.com"
- `DS_IMPERSONATED_USER_GUID` - USER_ACCOUNT_GUID # (Admin Panel - Users - select user - User name), If the user is not already allowed to use the API, open: `https://account-d.docusign.com/oauth/auth?response_type=token&scope=signature%20impersonation&client_id={the client id}&redirect_uri={a valid return url}`
- `DS_TARGET_ACCOUNT_ID` - default `FALSE` (not needed?)
- `DS_PRIVATE_KEY_FILE` - default `FALSE` - we have inline pricate key here
- `DS_PRIVATE_KEY` - RSA private key string you get from docsign during API setup

## API - `api.php`

- POST to `api.php`
- form data:
  - `pdf_base64_content` - pdf file as base64 string
  - `pdf_title` - The title of the pdf file
  - `return_url` - return url defined in docsign admin panel - redirected to after successful signing
  - `name[] | name` - name array of signer full names (index should be match email)
  - `email[] | email` - email array of signer emails (index should be match name)
  - `page` - page where the sign field should be added in the document
  - `pos_y` & `pos_x` - x and y values where the sign field should be presented
  - `email_subject` - the email subject used when sending sign email
- redirects to DocuSign and starts signing process for the **first** `name` and `email`!
- sends signing mails to all other names and mails

- for production:
  - cors is disabled per default
  - enable it by setting the allowed hosts in `api.php`:

```
// header("Access-Control-Allow-Origin: HOSTS");
header("Access-Control-Allow-Origin: *");
```

## Lib - `lib.php`

- provides the `createEnvelopsAndGetViewUri` function
- accepts a params object:
  - `pdf_base64_content` - pdf file as base64 string
  - `pdf_title` - The title of the pdf file
  - `return_url` - return url defined in docsign admin panel - redirected to after successful signing
  - `name[] | name` - name array of signer full names (index should be match email)
  - `email[] | email` - email array of signer emails (index should be match name)
  - `page` - page where the sign field should be added in the document
  - `pos_y` & `pos_x` - x and y values where the sign field should be presented
  - `email_subject` - the email subject used when sending sign email
- returns the URI to DocuSign to start the signing process for the **first** `name` and `email`!
- sends signing mails to all other names and mails
