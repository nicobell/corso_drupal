<?php

namespace Drupal\ldap_auth;

use Drupal\ldap_auth\Controller\miniorange_ldapController;

/**
 * @file
 * This class represents support information for customer.
 */
/**
 * @file
 * Contains miniOrange Support class.
 */
/**
 *
 */
class MiniorangeLdapSupport {
  public $email;
  public $phone;
  public $query;
  public $plan;
  public $mo_timezone;
  public $mo_date;
  public $mo_time;

  /**
   *
   */
  public function __construct($email, $phone, $query, $plan = '', $mo_timezone = '', $mo_date = '', $mo_time = '') {
    $this->email = $email;
    $this->phone = $phone;
    $this->query = $query;
    $this->plan = $plan;
    $this->mo_timezone = $mo_timezone;
    $this->mo_date = $mo_date;
    $this->mo_time = $mo_time;
  }

  /**
   *
   */
  public function sendSupportQuery() {
    $modules_info = \Drupal::service('extension.list.module')->getExtensionInfo('ldap_auth');
    $modules_version = $modules_info['version'];
    $customerKey = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_customer_id');
    $apikey = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_customer_api_key');
    if ($customerKey == '') {
      $customerKey = "16555";
      $apikey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
    }
    $controller = new miniorange_ldapController();
    $currentTimeInMillis = $controller->get_ldap_timestamp();
    $stringToHash = $customerKey . $currentTimeInMillis . $apikey;
    $hashValue = hash("sha512", $stringToHash);
    if ($this->plan == 'demo' || $this->plan == 'trial' || $this->plan == 'schedule_call' || $this->plan == 'request_quote') {
      $url = MiniorangeLdapConstants::BASE_URL . '/moas/api/notify/send';

      $request_for = $this->plan == 'demo' ? 'Demo' : (($this->plan == 'trial') ? 'Trial' : (($this->plan == 'schedule_call') ? 'Setup Meeting/Call' : 'Quote'));

      $subject = $request_for . ' request for Drupal-' . \DRUPAL::VERSION . ' LDAP Login Module | ' . $modules_version;
      $this->query = $request_for . ' required for - ' . $this->query;

      if ($this->plan == 'schedule_call') {
        $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>Phone Number:' . $this->phone . '<br><br>Email:<a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br><br> Timezone: <b>' . $this->mo_timezone . '</b><br><br> Date: <b>' . $this->mo_date . '</b>&nbsp;&nbsp; Time: <b>' . $this->mo_time . '</b><br><br>Query:[DRUPAL ' . Utilities::mo_get_drupal_core_version() . ' LDAP Login Free | ' . $modules_version . ' ] ' . $this->query . '</div>';
      }
      elseif ($this->plan == 'request_quote') {
        $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br>Email: <a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br>' . '</b><br>Query:[DRUPAL ' . Utilities::mo_get_drupal_core_version() . ' LDAP Login Free | ' . $modules_version . ' ] ' . $this->query . '</div>';
      }
      else {
        $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>Phone Number:' . $this->phone . '<br><br>Email:<a href="mailto:' . $this->email . '" target="_blank">' . $this->email . '</a><br><br>Query:[DRUPAL ' . Utilities::mo_get_drupal_core_version() . ' LDAP Login Free | ' . $modules_version . ' ] ' . $this->query . '</div>';
      }

      $fields = [
        'customerKey' => $customerKey,
        'sendEmail' => TRUE,
        'email' => [
          'customerKey' => $customerKey,
          'fromEmail' => $this->email,
          'fromName' => 'miniOrange',
          'toEmail' => 'drupalsupport@xecurify.com',
          'toName'  => 'drupalsupport@xecurify.com',
          'subject' => $subject,
          'content' => $content,
        ],
      ];

//      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', $customerKeyHeader,
//        $timestampHeader, $authorizationHeader,
//      ]);

      $header = [
        'Content-Type' => 'application/json',
        'Customer-Key' => $customerKey,
        'Timestamp' => $currentTimeInMillis,
        'Authorization' => $hashValue,
      ];

    }
    else {

      $this->query = '[Drupal ' . \DRUPAL::VERSION . ' LDAP Login Free Module | ' . $modules_version . '] ' . $this->query;
      $fields = [
        'company' => $_SERVER['SERVER_NAME'],
        'email' => $this->email,
        'phone' => $this->phone,
        'ccEmail' => 'drupalsupport@xecurify.com',
        'query' => $this->query,
      ];

      $url = MiniorangeLdapConstants::BASE_URL . '/moas/rest/customer/contact-us';

      $header = [
        'Content-Type' => 'application/json',
        'charset' => 'UTF-8',
        'Authorization' => 'Basic',
      ];

    }

    $field_string = json_encode($fields);

    if (!Utilities::isCurlInstalled()) {
      return json_encode(array(
        "statusCode" => 'ERROR',
        "statusMessage" => 'cURL is not enabled on your site. Please enable the cURL module.',
      ));
    }

    try {
      $response = \Drupal::httpClient()
        ->request('POST', $url, [
          'body' => $field_string,
          'allow_redirects' => TRUE,
          'http_errors' => FALSE,
          'decode_content'  => TRUE,
          'verify' => FALSE,
          'headers' => [
            'Content-Type' => 'application/json',
            'Customer-Key' => $customerKey,
            'Timestamp' => $currentTimeInMillis,
            'Authorization' => $hashValue,
          ],
        ]);

      return $response->getBody()->getContents();
    }
    catch (RequestException $exception) {
      \Drupal::logger('ldap_auth')->notice('Error at %method of %file: %error',
        [
          '%method' => 'checkCustomer',
          '%file' => 'customer_setup.php',
          '%error' => $exception->getMessage(),
        ]);
    }

  }

}
