<?php

namespace Drupal\ldap_auth\Controller;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ldap_auth\LDAPFlow;
use Drupal\ldap_auth\Mo_Ldap_Auth_Response;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class miniorange_ldapController extends ControllerBase {

  private $base_url;
  private ImmutableConfig $config;
  private Config $config_factory;

  /**
   *
   */
  public function __construct() {
    global $base_url;
    $this->base_url = $base_url;
    $this->config = \Drupal::config('ldap_auth.settings');
    $this->config_factory = \Drupal::configFactory()->getEditable('ldap_auth.settings');
  }

  /**
   * @return false|string|RedirectResponse
   */
  public function ldap_auth_feedback_func() {
    $res = '';
    $reason = '';
    $_SESSION['mo_other'] = "False";
    if (isset($_GET['deactivate_plugin']) && trim($_GET['deactivate_plugin']) != "") {
      $reason = $_GET['deactivate_plugin'];
    }
    else {
      $reason = "Not Specified";
    }

    $q_feedback = $_GET['query_feedback'];
    $message = 'Reason: ' . $reason . '<br>' . 'Feedback: ' . $q_feedback;
    $url = 'https://login.xecurify.com/moas/api/notify/send';
    $chi = curl_init($url);
    $inst_time = $this->config->get('miniorange_ldap_inst_time_ref');
    $inst_time = date('m/d/Y H:i:s', $inst_time);
    $page_visited = $this->config->get('miniorange_ldap_license_page_visited');
    $cont_ldap = $this->config->get('miniorange_ldap_contacted_server');
    $test_con = $this->config->get('miniorange_ldap_test_connection');
    $drupal_login = $this->config->get('miniorange_ldap_drupal_login');
    $email = $this->config->get('miniorange_ldap_customer_admin_email');
    if (empty($email)) {
      $email = $_GET['miniorange_feedback_email'];
    }
    $phone = $this->config->get('miniorange_ldap_customer_admin_phone');
    $skipped = isset($_GET['miniorange_feedback_skip']) ? TRUE : FALSE;
    $add_skip = $skipped ? "<b>Skipped: True</b><br><br>" : "";
    $customerKey = $this->config->get('miniorange_ldap_customer_id');
    $apikey = $this->config->get('miniorange_ldap_customer_api_key');
    if ($customerKey == '') {
      $customerKey = "16555";
      $apikey = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
    }
    $currentTimeInMillis = self::get_ldap_timestamp();
    $stringToHash = $customerKey . $currentTimeInMillis . $apikey;
    $hashValue = hash("sha512", $stringToHash);
    $customerKeyHeader = "Customer-Key: " . $customerKey;
    $timestampHeader = "Timestamp: " . $currentTimeInMillis;
    $authorizationHeader = "Authorization: " . $hashValue;
    $fromEmail = $email;
    $subject = "Drupal " . \Drupal::VERSION . " Active Directory / LDAP Integration - NTLM & Kerberos Login Feedback";
    $query = '[Drupal ' . \Drupal::VERSION . ' Active Directory / LDAP Integration - NTLM & Kerberos Login]: ' . $message;
    $content = '<div >Hello, <br><br>Company :<a href="' . $_SERVER['SERVER_NAME'] . '" target="_blank" >' . $_SERVER['SERVER_NAME'] . '</a><br><br>Phone Number :' . $phone . '<br><br>Email :<a href="mailto:' . $fromEmail . '" target="_blank">' . $fromEmail . '</a><br><br>Installed on: ' . $inst_time . '<br><br>Payment Page Visited: ' . $page_visited . '<br><br>Contact LDAP Server: ' . $cont_ldap . '<br><br>Performed Test Connection: ' . $test_con . '<br><br>Tried Login to Drupal using LDAP: ' . $drupal_login . '<br><br>' . $add_skip . 'Query: ' . $query . '</div>';
    $fields = [
      'customerKey' => $customerKey,
      'sendEmail' => TRUE,
      'email' => [
        'customerKey' => $customerKey,
        'fromEmail' => $fromEmail,
        'fromName' => 'miniOrange',
        'toEmail' => 'drupalsupport@xecurify.com',
        'toName' => 'drupalsupport@xecurify.com',
        'subject' => $subject,
        'content' => $content,
      ],
    ];
    $field_string = json_encode($fields);
    curl_setopt($chi, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($chi, CURLOPT_ENCODING, "");
    curl_setopt($chi, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($chi, CURLOPT_AUTOREFERER, TRUE);
    // Required for https urls.
    curl_setopt($chi, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($chi, CURLOPT_MAXREDIRS, 10);
    curl_setopt($chi, CURLOPT_HTTPHEADER, ["Content-Type: application/json", $customerKeyHeader,
      $timestampHeader, $authorizationHeader,
    ]);
    curl_setopt($chi, CURLOPT_POST, TRUE);
    curl_setopt($chi, CURLOPT_POSTFIELDS, $field_string);
    $content = curl_exec($chi);
    if (curl_errno($chi)) {
      return json_encode(["status" => 'ERROR', 'statusMessage' => curl_error($chi)]);
    }
    curl_close($chi);
    $this->config_factory->set('miniorange_ldap_auth_uninstall_status', 1)->save();
    \Drupal::service('module_installer')->uninstall(['ldap_auth']);
    $uninstall_redirect = $this->base_url . '/admin/modules';
    Utilities::add_message(t('Module uninstalled successfully.'), 'status');
    return new RedirectResponse($uninstall_redirect);
  }

  /**
   * This function is used to get the timestamp value.
   */
  public function get_ldap_timestamp() {
    $url = 'https://login.xecurify.com/moas/rest/mobile/get-timestamp';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_ENCODING, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    // Required for https urls.
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    $content = curl_exec($ch);
    if (curl_errno($ch)) {
      echo 'Error in sending curl Request';
      exit();
    }
    curl_close($ch);
    if (empty($content)) {
      $currentTimeInMillis = round(microtime(TRUE) * 1000);
      $currentTimeInMillis = number_format($currentTimeInMillis, 0, '', '');
    }
    return empty($content) ? $currentTimeInMillis : $content;
  }

  /**
   * @param $username
   * @return \Drupal\ldap_auth\Mo_Ldap_Auth_Response
   */
  public function search_user_attributes($username) {

    $authStatus = NULL;
    $ldap_connect = new LDAPFlow();
    $ldapconn = $ldap_connect->getConnection();

    if ($ldapconn) {
      $ldap_bind_dn = $ldap_connect->getServiceAccountUsername();
      $ldap_bind_password = $ldap_connect->getServiceAccountPassword();
      $search_base = $ldap_connect->getSearchBase();
      $search_filter_1 = $ldap_connect->getSearchFilter();
      $search_filter = '(&(objectClass=*)(' . $search_filter_1 . '=?))';
      $filter = str_replace('?', $username, $search_filter);

      $user_search_result = NULL;
      $entry              = NULL;
      $info               = NULL;
      $bind               = @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_password);
      $err                = ldap_error($ldapconn);

      if (strtolower($err) != 'success') {
        $auth_response = new Mo_Ldap_Auth_Response();
        $auth_response->status = FALSE;
        $auth_response->statusMessage = 'LDAP_NOT_RESPONDING';
        $auth_response->userDn = '';
        return $auth_response;
      }

      if (ldap_search($ldapconn, $search_base, $filter)) {

        $user_search_result = ldap_search($ldapconn, $search_base, $filter);

        $info = ldap_first_entry($ldapconn, $user_search_result);
        $entry = ldap_get_entries($ldapconn, $user_search_result);
        $user_attributes = [];
        $i = 0;

        if (!$info) {
          $err = ldap_error($ldapconn);
          $auth_response = new Mo_Ldap_Auth_Response();
          $auth_response->status = FALSE;
          $auth_response->statusMessage = 'User with <b>' . $search_filter_1 . ' = ' . $username . '</b> does not exist in selected search base and search filter<br>
                <ul>
                 <li>Selected search base : ' . $search_base . '</li>
                 <li>Selected search fitler : ' . $search_filter_1 . '</li>
                </ul>';
          $auth_response->userDn = NULL;
          return $auth_response;
        }

        $user_attributes['User DN'] = ldap_get_dn($ldapconn, $info);

        foreach ($entry[0] as $key => $value) {
          if (!is_int($key) && $key != 'count') {
            $user_attributes[$key] = $value[0];
          }
        }

        if (isset($entry[0]['memberof']) && is_array($entry[0]['memberof'])) {
          $user_attributes['memberof'] = [];
          foreach ($entry[0]['memberof'] as $member) {
            if ($i > 0) {
              array_push($user_attributes['memberof'], $member);
            }
            $i++;
          }
        }

        $auth_response = new Mo_Ldap_Auth_Response();
        $auth_response->status = TRUE;
        $auth_response->statusMessage = "Attributes fetched Successfully.";
        $auth_response->userDn = ldap_get_dn($ldapconn, $info);
        $auth_response->attributeList = $user_attributes;
        return $auth_response;
      }
      else {
        $auth_response = new Mo_Ldap_Auth_Response();
        $auth_response->status = FALSE;
        $auth_response->statusMessage = ldap_error($ldapconn);
        $auth_response->userDn = NULL;
        return $auth_response;
      }

    }
    else {
      // Error message.
      $auth_response = new Mo_Ldap_Auth_Response();
      $auth_response->status = FALSE;
      $auth_response->statusMessage = 'ERROR : Cannot connect to your LDAP Server';
      $auth_response->userDn = NULL;
      return $auth_response;
    }

  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function uninst_mod() {
    $this->config_factory->clear('miniorange_ldap_feedback_status')->save();
    \Drupal::service('module_installer')->uninstall(['ldap_auth']);
    $uninstall_redirect = $this->base_url . '/admin/modules';
    $response = new RedirectResponse($uninstall_redirect);
    $response->send();
    return new Response();
  }

  // Ajax Response for Trial Request form.

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function openTrialRequestForm() {
    $response = new AjaxResponse();
    $modal_form = \Drupal::formBuilder()->getForm('\Drupal\ldap_auth\Form\MiniornageLDAPRequestTrial');
    $response->addCommand(new OpenModalDialogCommand('Request 7-Days Full Feature Trial License', $modal_form, ['width' => '40%']));
    return $response;
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function openSupportRequestForm() {
    $response = new AjaxResponse();
    $modal_form = \Drupal::formBuilder()->getForm('\Drupal\ldap_auth\Form\MiniornageLDAPRequestSupport');
    $response->addCommand(new OpenModalDialogCommand('Support Request/Contact Us', $modal_form, ['width' => '40%']));
    return $response;
  }

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function openTestAuthenticationForm() {
    $response = new AjaxResponse();
    $modal_form = \Drupal::formBuilder()->getForm('\Drupal\ldap_auth\Form\TestAuthentication');
    $response->addCommand(new OpenModalDialogCommand('Test Authentication', $modal_form, ['width' => '60%']));
    return $response;
  }

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
   */
  public function test_configuration() {
    global $base_url;

    if ((isset($_POST['pass']) && $_POST['pass'] == '') || (isset($_POST['user']) && $_POST['user'] == '')) {
      echo '<div style="color: #9f1e2b;background-color: #f0d8d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #e8bcbc; font-size:16pt;"><b>Username or Password cannot be empty.</b></div>';
      exit;
    }

    $username = $_POST['user'] ?? '';
    $this->config_factory->set('mo_last_authenticated_user', $username)->save();

    $attributes = self::search_user_attributes($username);
    $module_path = \Drupal::service('extension.path.resolver')->getPath('module', 'ldap_auth');

    if (!$attributes->status) {
      echo '<div style="color: #961f1f; padding:2%;margin-bottom:20px;text-align:center;font-weight: bold; font-size:18pt;">TEST FAILED</div><div style="display:block;text-align:center;margin-bottom:0%;"></div><div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:12%;"src="' . $base_url . '/' . $module_path . '/assets/img/wrong.png"></div>';
      echo '<div style="color: #101010;background-color: #f0d8d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #e8bcbc; font-size:16pt;">' . $attributes->statusMessage . '</div>';
      echo '<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;" type="button" value="Close" onClick="self.close();"/></div>';
      return new Response();
    }

    unset($attributes->attributeList['objectsid']);
    unset($attributes->attributeList['objectguid']);
    $name = $attributes->attributeList['cn'];

    $attributes_list = [];
    Utilities::show_attr($attributes->attributeList, $attributes_list);

    foreach ($attributes_list as $value) {
      $attr_list[$value['att_name']] = $value['att_value'];
    }

    $this->config_factory->set('miniorange_ldap_user_attributes', json_encode($attributes))->save();

    if (isset($_POST['pass']) && $_POST['pass'] != 'undefined') {
      $user_pass = $_POST['pass'];
      $ldap_connect = new LDAPFlow();
      $ldapconn = $ldap_connect->getConnection();

      $auth_response = $ldap_connect->ldap_login($username, $user_pass, FALSE);

      // Error message.
      if (!$auth_response->status) {
        echo '<div style="color: #961f1f; padding:2%;margin-bottom:20px;text-align:center;font-weight: bold; font-size:18pt;">TEST FAILED</div><div style="display:block;text-align:center;margin-bottom:0%;"></div><div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:12%;"src="' . $base_url . '/' . $module_path . '/assets/img/wrong.png"></div>';
        echo '<div style="color: #9f1e2b;background-color: #f0d8d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #e8bcbc; font-size:16pt;"><b>' . $auth_response->statusMessage . '</b>: User found in the LDAP server but the password does not match. Please try again with another password.<br></div>';
        echo '<div style="margin:3%;display:block;text-align:center;"><input
                            style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"
                            type="button" value="Close" onClick="self.close();"/></div>';
        return new Response();
      }
    }


    echo '<style>select {
  background-color: transparent;
  border: none;
  padding: 0 1em 0 0;
  margin: 0;
  width: 100%;
  font-family: inherit;
  font-size: inherit;
  cursor: inherit;
  line-height: inherit;
  z-index: 1;
}
                .select {
  display: grid;
  grid-template-areas: "select";
  align-items: center;
  position: relative;
  min-width: 15ch;
  max-width: 30ch;
  border: 1px solid;
  border-radius: 0.25em;
  padding: 0.25em 0.5em;
  font-size: 1.25rem;
  cursor: pointer;
  line-height: 1.1;
  background-color: #fff;
  background-image: linear-gradient(to top, #f9f9f9, #fff 33%);
}
                .flex-container {
  display: flex;
  align-items: stretch;
  background-color: #ffffff;
  color: #000;
  width: 100%;
  margin: 1px;
  text-align: center;
  line-height: 35px;
  font-size: 20px;
  justify-content: space-between;
}
                .message_div{color: #3c763d;background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:left; border:1px solid #AEDB9A; font-size:16pt;}
                .message_div1{color: #000000;background-color: #d2d1d1; padding:2%;margin-bottom:10px;text-align:left; border:1px solid #ffffff; font-size:12pt;}
                .title_div {color: #3c763d; padding:2%;margin-bottom:20px;text-align:center;font-weight: bold; font-size:18pt;}
                .save_and_close_button {padding:10%;width:130px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;}
              </style>';

    echo '<div style="font-family:Calibri;padding:0 3%;">';
    echo '<div class="title_div">TEST SUCCESSFUL</div><div style="display:block;text-align:center;margin-bottom:0;"></div><div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:12%;" src="' . $base_url . '/' . $module_path . '/assets/img/green_check.png"></div>';
    echo '<div class="message_div">Congratulations, your test authentication is successfull. Now, please select the email attribute to complete the final step of the configuration. </div>';
    echo '<div class="message_div1">Please select the <b>Attribute name</b> in which you are getting your users <b>Email</b>.</div>';

    $email_attribute = $this->config->get('miniorange_ldap_email_attribute');

    echo '<div class="flex-container"><div style="max-width:70vh;font-weight: bold;">Email attribute: </div>
             <div class="select">
             <select id="mo_ldap_email_attr">';
    foreach ($attr_list as $key => $value) {
      $selected = $key == $email_attribute ? "selected" : "";
      echo '<option value=' . $key . ' ' . $selected . '>' . $key . '</option> ';
    }
    echo '</select></div> <div style="display:block;text-align:left;"><input class ="save_and_close_button"
                            type="button" value="Save & Next" onClick="save_and_done();"/></div></div>';
    echo '<script>
                        function save_and_done(){
                          var email_attr = document.getElementById("mo_ldap_email_attr").value;
                          var baseurl = window.location.href;
                          var pos = baseurl.indexOf("testConfig");
                          window.opener.location.href = baseurl.replace(baseurl.slice(pos), "mo_post_testconfig/?field_selected="+email_attr);
                          self.close();
                        }
                        </script>';

    $this->display_attributes($name, $attr_list);

    \Drupal::configFactory()->getEditable('ldap_auth.settings')->set('miniorange_ldap_user_attributes', json_encode($attributes))->save();
    return new Response();
  }

  /**
   * @return void
   */
  public function mo_post_testconfig() {
    $email_attribute = $_GET['field_selected'];
    $this->config_factory->set('miniorange_ldap_email_attribute', $email_attribute)->save();
    global $base_url;

    if ($this->config->get('miniorange_ldap_config_status') == 'review_config') {
      Utilities::add_message(t('Test Authentication successfull.'), 'status');
    }
    else {
      $this->config_factory->set('miniorange_ldap_config_status', 'review_config')->save();
      $this->config_factory->set('miniorange_ldap_steps', "5")->save();
      Utilities::add_message(t('Configuration updated successfully. <br><br>Now please open a private/incognito window and try to login to your Drupal site using your LDAP credentials. In case you face any issues or if you need any sort of assistance, please feel free to reach out to us at <u><a href="mailto:drupalsupport@xecurify.com"><i>drupalsupport@xecurify.com</i></a></u>'), 'status');
    }

    $response = new RedirectResponse($base_url . "/admin/config/people/ldap_auth/ldap_config");
    $response->send();
    return new Response();
  }

  /**
   *
   */
  public function display_attributes($name, $attr_list) {
    echo '<p style="font-size:13pt;margin-left:1%;"> Hello <b>' . $name . ',</b></p>
            <table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:13pt;background-color:#ffffff;">';
    foreach ($attr_list as $key => $value) {
      echo ' <tr style="text-align:left;">
                       <td style="font-weight:bold;border:2px solid #949090;padding:2%;"><b>' . $key . '</b></td>
                       <td style="padding:2%;border:2px solid #949090; word-wrap:break-word;">' . $value . '</td>
                </tr>';
    }
    echo '</table><br><br>';
  }

}
