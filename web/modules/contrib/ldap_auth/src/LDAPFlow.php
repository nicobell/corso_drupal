<?php

namespace Drupal\ldap_auth;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\Config;

/**
 *
 */
class LDAPFlow {

  private $ldapconn;

  private $bind;

  private $anon_bind;

  private $server_name;

  private $service_account_username;

  private $service_account_password;

  private $search_base;

  private $search_filter;

  private $custom_base;

  private Config $config_factory;

  private ImmutableConfig $config;

  /**
   *
   */
  public function __construct() {
    $this->server_name = \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_server') ? \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_server') : "";
    $this->service_account_username = \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_server_account_username') ? \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_server_account_username') : "";
    $this->service_account_password = \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_server_account_password') ? \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_server_account_password') : "";

    $this->config = \Drupal::config('ldap_auth.settings');
    $this->config_factory = \Drupal::configFactory()->getEditable('ldap_auth.settings');
  }

  /**
   *
   */
  public function getConnection() {
    $this->server_name = $this->getServerName();

    $this->ldapconn = ldap_connect($this->server_name);
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
      ldap_set_option($this->ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
    }
    ldap_set_option($this->ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->ldapconn, LDAP_OPT_REFERRALS, 0);
    $this->anon_bind = $this->getAnonBind();
    if ($this->anon_bind) {
      $this->setLdapconn($this->ldapconn);
    }
    else {
      $this->ldapconn = FALSE;
    }
    return $this->ldapconn;
  }

  /**
   * @param mixed $ldapconn
   */
  public function setLdapconn($ldapconn) {
    $this->ldapconn = $ldapconn;
  }

  /**
   * @return mixed
   */
  public function getLdapconn() {
    return $this->ldapconn;
  }

  /**
   * @return mixed
   */
  public function getServerName() {
    return $this->server_name;
  }

  /**
   * @param mixed $server_name
   */
  public function setServerName($server_name) {
    $this->config_factory
      ->set('miniorange_ldap_server', $server_name)
      ->save();
    $this->server_name = $server_name;
  }

  /**
   * @return mixed
   */
  public function getServiceAccountUsername() {
    return $this->service_account_username;
  }

  /**
   * @param mixed $service_account_username
   */
  public function setServiceAccountUsername($service_account_username) {
    $this->config_factory
      ->set('miniorange_ldap_server_account_username', $service_account_username)
      ->save();
    $this->service_account_username = $service_account_username;
  }

  /**
   * @return array|mixed|string|null
   */
  public function getServiceAccountPassword() {
    return $this->service_account_password;
  }

  /**
   * @param mixed $service_account_password
   */
  public function setServiceAccountPassword($service_account_password) {
    $this->config_factory
      ->set('miniorange_ldap_server_account_password', $service_account_password)
      ->save();
    $this->service_account_password = $service_account_password;
  }

  /**
   * @return mixed
   */
  public function getSearchBase() {
    $search_bases = $this->config->get('miniorange_ldap_search_base');
    if ($search_bases == 'custom_base') {
      $search_bases = $this->config->get('miniorange_ldap_custom_sb_attribute');
    }
    return $search_bases;
  }

  /**
   * @param mixed $search_base
   */
  public function setSearchBase($search_base, $custom_base = NULL) {
    if (!is_null($custom_base)) {
      $this->config_factory
        ->set('miniorange_ldap_custom_sb_attribute', $custom_base)
        ->save();
      $this->custom_base = $custom_base;
    }
    $this->config_factory
      ->set('miniorange_ldap_search_base', $search_base)
      ->save();
    $this->search_base = $search_base;

  }

  /**
   * @return mixed
   */
  public function getSearchFilter() {
    return $this->config
      ->get('miniorange_ldap_username_attribute');
  }

  /**
   * @param mixed $search_filter
   */
  public function setSearchFilter($search_filter) {
    $this->config_factory
      ->set('miniorange_ldap_username_attribute', $search_filter)
      ->save();
  }

  /**
   *
   */
  public function getAnonBind() {
    // Anonymous binding with LDAP server. Used to ensure that the LDAP Server is reachable.
    $this->anon_bind = @ldap_bind($this->getLdapconn());
    LDAPLOGGERS::addLogger('L17: Anonymous LDAP Bind: ', $this->anon_bind);
    return $this->anon_bind;
  }

  /**
   * Test Connection flow.
   */
  public function test_mo_ldap_config($username = NULL, $password = NULL) {
    if (empty($username)) {
      $username = $this->service_account_username;
    }
    if (empty($password)) {
      $password = $this->service_account_password;
    }
    if (empty($username) || empty($password)) {
      LDAPLOGGERS::addLogger('L18: Empty Username or Password');
      $return_status = [
        "Username or Password can not be empty until and unless you do not have any authentication setup and wish to perform <b>anonymous bind</b> to your server. If that is the case, please ignore this message and continue with the setup.",
        "error",
      ];
      return $return_status;
    }

    $auth_response = $this->ldap_login($username, $password);

    if (!empty($auth_response)) {
      LDAPLOGGERS::addLogger('L34: auth response not empty ');
      LDAPLOGGERS::addLogger('L35: auth response statusMessage:  ', $auth_response->statusMessage);
      if ($auth_response->statusMessage == "SUCCESS") {
        $return_status = [
          "Enable <b>LDAP Login</b> at the top and then Logout from your Drupal site and login again with your LDAP credentials.",
          "Success",
        ];
        return $return_status;
      }
      else {
        if ($auth_response->statusMessage == "USER_NOT_EXIST") {
          $return_status = [
            "The user you entered does not exist in the Active Directory. Please check your configurations or contact the administrator",
            "error",
          ];
          return $return_status;
        }
        else {
          if ($auth_response->statusMessage == "Test_Connection_was_successful") {
            $return_status = ["Your test connection was successful", "Success"];
            return $return_status;
          }
          else {
            $return_status = [
              "Invalid Password. Please check your password and try again.",
              "error",
            ];
            return $return_status;
          }
        }
      }
    }
  }

  /**
   * Login function.
   */
  public function ldap_login($username, $password, $istesting = TRUE) {
    // 3rd parameter while test authentication done by admin
    if (empty($username)) {
      Utilities::add_message(t('Username can not be empty'), 'error');
      return;
    }
    if (empty($password)) {
      Utilities::add_message(t('The Password can not be empty'), 'error');
      return;
    }
    $authStatus = NULL;
    $auth_response = new Mo_Ldap_Auth_Response();
    $auth_response->userDn = '';
    $ldapconn = $this->getConnection();
    LDAPLOGGERS::addLogger('L19: Anonymous LDAP Bind: ', $ldapconn);

    if ($ldapconn) {
      LDAPLOGGERS::addLogger('L20: Entered LDAPFlow:ldapconn ');
      $search_filter = $this->config
        ->get('miniorange_ldap_username_attribute');

      $value_filter = '(&(objectClass=*)(' . $search_filter . '=?))';
      $search_bases = $this->config
        ->get('miniorange_ldap_search_base');
      if ($search_bases == 'custom_base') {
        $search_bases = $this->config
          ->get('miniorange_ldap_custom_sb_attribute');
      }
      $ldap_bind_dn = $this->service_account_username;
      $ldap_bind_password = $this->service_account_password;
      $filter = str_replace('?', $username, $value_filter);
      $user_search_result = NULL;
      $entry = NULL;
      $info = NULL;
      $fname_attribute = 'mail';
      $lname_attribute = 'dn';
      $email_attribute = 'cn';
      $phone_attribute = 'samaccountname';
      $email_attribute = $this->config
        ->get('miniorange_ldap_email_attribute');
      $attr = [
        $fname_attribute,
        $lname_attribute,
        $email_attribute,
        $phone_attribute,
      ];
      $bind = @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_password);
      $err = ldap_error($ldapconn);
      LDAPLOGGERS::addLogger('L21: LDAPFlow ldap_error: ', $err);
      if (strtolower($err) != 'success') {
        LDAPLOGGERS::addLogger('L22: LDAPFlow strtolower(err) not success: ');
        $auth_response->status = FALSE;
        $auth_response->statusMessage = 'LDAP_NOT_RESPONDING';
        return $auth_response;
      }
      else {
        if (isset($_COOKIE['Drupal_visitor_mo_ldap_test']) && ($_COOKIE['Drupal_visitor_mo_ldap_test'] == TRUE) && $istesting) {
          LDAPLOGGERS::addLogger('L23: Drupal_visitor_mo_ldap_test found and set to true ');
          $auth_response->status = TRUE;
          $auth_response->statusMessage = 'Test_Connection_was_successful';
          return $auth_response;
        }
      }

      LDAPLOGGERS::addLogger('L24: LDAPFlow login flow: ');
      $s1 = @ldap_search($ldapconn, $search_bases, $filter);

      if ($s1) {
        $user_search_result = ldap_search($ldapconn, $search_bases, $filter, $attr);
        LDAPLOGGERS::addLogger('L25: LDAPFlow ldap search: ', $user_search_result);
        $info = ldap_first_entry($ldapconn, $user_search_result);
        $entry = ldap_get_entries($ldapconn, $user_search_result);
        LDAPLOGGERS::addLogger('L27: LDAPFlow ldap_first_entry: ', $info);

        if ($info) {
          $userDn = ldap_get_dn($ldapconn, $info);
          LDAPLOGGERS::addLogger('L28: LDAPFlow userDn: ', $userDn);
        }
        else {
          LDAPLOGGERS::addLogger('L29: LDAPFlow User Not Found ');
          $auth_response->status = FALSE;
          $auth_response->statusMessage = 'USER_NOT_EXIST';
          return $auth_response;
        }
      }
      else {
        LDAPLOGGERS::addLogger('L26: LDAPFlow User NOT Exist: ');
        $auth_response->status = FALSE;
        $auth_response->statusMessage = 'Error while search';
        return $auth_response;
      }

      $authentication_response = self::authenticate($userDn, $password);
      LDAPLOGGERS::addLogger('L31: LDAPFlow authenticate_response status message Bind: ', $auth_response->statusMessage);

      if ($authentication_response->statusMessage == 'SUCCESS') {
        $attributes_array = [];
        $profile_attributes = [];

        $email_attribute = \Drupal::config('ldap_auth.settings')
          ->get('miniorange_ldap_email_attribute');
        if (!empty($email_attribute)) {
          if (isset($entry[0][$email_attribute]) && is_array($entry[0][$email_attribute])) {
            $profile_attributes['mail'] = $entry[0][$email_attribute][0];
          }
          else {
            $profile_attributes['mail'] = isset($entry[0][$email_attribute]) ? $entry[0][$email_attribute] : '';
          }
        }
        $authentication_response->profileAttributesList = $profile_attributes;
        $authentication_response->attributeList = $attributes_array;
      }

      LDAPLOGGERS::addLogger('L32: LDAPFlow authenticate_response status message not SUCCESS ');
      return $authentication_response;
    }
    else {
      LDAPLOGGERS::addLogger('L33: LDAPFlow ldapconn if failed');
      $auth_response->status = FALSE;
      $auth_response->statusMessage = 'LDAP_CONNECTION_FAILED';
      return $auth_response;
    }

  }

  /**
   * Authenticate LDAP Credentials.
   */
  public function authenticate($userDn, $password) {
    $this->ldapconn = ldap_connect($this->server_name);
    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
      ldap_set_option(NULL, LDAP_OPT_NETWORK_TIMEOUT, 5);
    }
    ldap_set_option($this->ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($this->ldapconn, LDAP_OPT_REFERRALS, 0);
    // Binding to ldap server.
    $this->bind = @ldap_bind($this->ldapconn, $userDn, $password);
    // Verify binding.
    $search_filter = \Drupal::config('ldap_auth.settings')
      ->get('miniorange_ldap_username_attribute');
    $value_filter = '(&(objectClass=*)(' . $search_filter . '=?))';
    $filter = str_replace('?', $userDn, $value_filter);
    LDAPLOGGERS::addLogger('L30: LDAPFlow authenticate() Bind: ', $this->bind);
    if ($this->bind) {
      $search_result = ldap_search($this->ldapconn, $userDn, $filter);
      $auth_response = new Mo_Ldap_Auth_Response();
      $auth_response->status = TRUE;
      $auth_response->statusMessage = 'SUCCESS';
      $auth_response->userDn = $userDn;
      return $auth_response;
    }
    $auth_response = new Mo_Ldap_Auth_Response();
    $auth_response->status = FALSE;
    $auth_response->statusMessage = 'WRONG PASSWORD';
    $auth_response->userDn = $userDn;
    return $auth_response;
  }

  /**
   * Returns all search Bases from AD.
   */
  public function getSearchBases() {

    $ldapconn = $this->getConnection();
    $ldap_bind_dn = $this->getServiceAccountUsername();
    $ldap_bind_password = $this->getServiceAccountPassword();
    $bind = $ldapconn && @ldap_bind($ldapconn, $ldap_bind_dn, $ldap_bind_password);
    $search_base_list = [];
    if ($bind) {
      $result = ldap_read($ldapconn, '', '(objectclass=*)', ['namingContexts']);
      $data = ldap_get_entries($ldapconn, $result);
      $count = $data[0]['namingcontexts']['count'];
      for ($i = 0; $i < $count; $i++) {
        if ($i == 0) {
          $base_dn = $data[0]['namingcontexts'][$i];
        }

        $search_base_list[] = $data[0]['namingcontexts'][$i];
      }

      $filter = "(|(objectclass=organizationalUnit)(&(objectClass=top)(cn=users)))";
      $search_attr = ["dn", "ou"];

      @$ldapsearch = ldap_search($ldapconn, $base_dn, $filter, $search_attr);
      if ($ldapsearch) {
        @$info = ldap_get_entries($ldapconn, $ldapsearch);

        if ($info) {
          for ($i = 0; $i < $info["count"]; $i++) {
            $textvalue = $info[$i]["dn"];
            $search_base_list[] = $info[$i]["dn"];
          }
        }

      }

    }
    return $search_base_list;
  }

}
