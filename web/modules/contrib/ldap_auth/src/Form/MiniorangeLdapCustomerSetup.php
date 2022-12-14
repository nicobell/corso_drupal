<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\MiniorangeLdapCustomer;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class MiniorangeLdapCustomerSetup extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_customer_setup';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $current_status = $this->config->get('miniorange_ldap_status');
    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "ldap_auth/ldap_auth.admin",
        ],
      ],
    ];
    if ($current_status == 'VALIDATE_OTP') {

      $form['miniorange_ldap_customer_otp_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('OTP'),
        '#prefix' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">',
        '#suffix' => '<br>',
      ];

      $form['miniorange_ldap_customer_validate_otp_button'] = [
        '#type' => 'submit',
        '#value' => $this->t('Validate OTP'),
        '#submit' => ['::miniorange_ldap_validate_otp_submit'],
      ];

      $form['miniorange_ldap_customer_setup_resendotp'] = [
        '#type' => 'submit',
        '#value' => $this->t('Resend OTP'),
        '#submit' => ['::miniorange_ldap_resend_otp'],
      ];

      $form['miniorange_ldap_customer_setup_back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::miniorange_ldap_back'],
        '#suffix' => '<br>',
      ];

      $form['mo_markup_div_imp_2'] = ['#markup' => '</div>'];
      Utilities::AddSupportButton($form, $form_state);
      return $form;
    }
    elseif ($current_status == 'PLUGIN_CONFIGURATION') {
      $form['header_top_style_1'] = [
        '#markup' => $this->t('<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">
          <div class="mo_ldap_welcome_message">Thank you for registering with miniOrange</div><br><h4>Your Profile: </h4>'),
      ];

      $header = [
        'email' => ['data' => t('Customer Email')],
        'customerid' => ['data' => t('Customer ID')],
        'token' => ['data' => t('Token Key')],
        'apikey' => ['data' => t('API Key')],
      ];

      $options = [];

      $options[0] = [
        'email' => $this->config->get('miniorange_ldap_customer_admin_email'),
        'customerid' => $this->config->get('miniorange_ldap_customer_id'),
        'token' => $this->config->get('miniorange_ldap_customer_admin_token'),
        'apikey' => $this->config->get('miniorange_ldap_customer_api_key'),
      ];

      $form['fieldset']['customerinfo'] = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $options,
        '#suffix' => '<br><br><br><br>',
      ];

      $form['markup_idp_attr_header_top_support'] = ['#markup' => '</div>',
      ];

      Utilities::AddSupportButton($form, $form_state);
      return $form;
    }

    $form['markup_14'] = [
      '#markup' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">',
    ];

    $form['markup_reg'] = [
      '#markup' => '<div><h2>Register with mini<span class="mo_orange"><b>O</b></span>range</h2><hr>',
    ];

    $form['fmarkup_15'] = [
      '#markup' => $this->t('<br><div class="mo_ldap_highlight_background_note_1">Please enter a valid email id that you have access to. You will be able to move forward after verifying an OTP that we will send to this email.
      <p>In case you are facing any issues trying to register with us, you can directly create an account from the link <a target="_blank" href="https://www.miniorange.com/businessfreetrial"><b>HERE</b></a> or you can reach out to us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a>
      </div>'),
    ];
    $form['miniorange_ldap_customer_setup_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
    ];

    $form['miniorange_ldap_customer_setup_phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#description' => $this->t('<b>NOTE:</b> We will only call if you need support.'),
    ];

    $form['miniorange_ldap_customer_setup_password'] = [
      '#type' => 'password_confirm',
    ];

    $form['miniorange_ldap_customer_setup_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    $form['register_close'] = [
      '#markup' => '</div>',
    ];

    $form['mo_markup_div_imp_2'] = ['#markup' => '</div>'];
    Utilities::AddSupportButton($form, $form_state);

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $username = $form['miniorange_ldap_customer_setup_username']['#value'];
    $phone = $form['miniorange_ldap_customer_setup_phone']['#value'];
    $password = $form['miniorange_ldap_customer_setup_password']['#value']['pass1'];
    if (empty($username)||empty($password)) {
      Utilities::add_message(t('The <b><u>Email </u></b> and <b><u>Password</u></b> fields are mandatory.'), 'error');
      return;
    }
    if (!$this->emailValidator->isValid($username)) {
      Utilities::add_message(t('The email address <i>' . $username . '</i> is not valid.'), 'error');
      return;
    }
    $customer_config = new MiniorangeLdapCustomer($username, $phone, $password, NULL);
    $check_customer_response = json_decode($customer_config->checkCustomer());

    if ($check_customer_response->status == 'CUSTOMER_NOT_FOUND') {
      $this->config_factory->set('miniorange_ldap_customer_admin_email', $username)
        ->set('miniorange_ldap_customer_admin_phone', $phone)
        ->set('miniorange_ldap_customer_admin_password', $password)->save();
      $send_otp_response = json_decode($customer_config->sendOtp());

      if ($send_otp_response->status == 'SUCCESS') {
        $this->config_factory->set('miniorange_ldap_tx_id', $send_otp_response->txId)->save();
        $current_status = 'VALIDATE_OTP';
        $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
        Utilities::add_message(t('Verify email address by entering the passcode sent to @username', ['@username' => $username]), 'status');
      }
      else {
        Utilities::add_message(t('Error while processing the request. If you want you can also Register from <a href="https://www.miniorange.com/businessfreetrial" target="_blank"><i>here</i></a>'), 'error');
        return;
      }

    }
    elseif ($check_customer_response->status == 'CURL_ERROR') {
      Utilities::add_message(t('cURL is not enabled. Please enable cURL'), 'error');
    }
    else {
      $customer_keys_response = json_decode($customer_config->getCustomerKeys());

      if (json_last_error() == JSON_ERROR_NONE) {
        $this->config_factory->set('miniorange_ldap_customer_id', $customer_keys_response->id)
          ->set('miniorange_ldap_customer_admin_token', $customer_keys_response->token)
          ->set('miniorange_ldap_customer_admin_email', $username)
          ->set('miniorange_ldap_customer_admin_phone', $phone)
          ->set('miniorange_ldap_customer_api_key', $customer_keys_response->apiKey)->save();
        $current_status = 'PLUGIN_CONFIGURATION';
        $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
        Utilities::add_message(t('Successfully retrieved your account.'), 'status');
      }
      else {
        Utilities::add_message(t('Invalid credentials.'), 'error');
        return;
      }
    }
  }

  /**
   *
   */
  public function miniorange_ldap_back(&$form, $form_state) {
    $current_status = 'CUSTOMER_SETUP';
    $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
    $this->config_factory->clear('miniorange_miniorange_ldap_customer_admin_email')
      ->clear('miniorange_ldap_customer_admin_phone')
      ->clear('miniorange_ldap_tx_id')->save();

    Utilities::add_message(t('Register/Login with your miniOrange Account'), 'status');
  }

  /**
   *
   */
  public function miniorange_ldap_resend_otp(&$form, $form_state) {
    $this->config_factory->clear('miniorange_ldap_tx_id')->save();
    $username = $this->config->get('miniorange_ldap_customer_admin_email');
    $phone = $this->config->get('miniorange_ldap_customer_admin_phone');
    $customer_config = new MiniorangeLdapCustomer($username, $phone, NULL, NULL);
    $send_otp_response = json_decode($customer_config->sendOtp());
    if ($send_otp_response->status == 'SUCCESS') {
      // Store txID.
      $this->config_factory->set('miniorange_ldap_tx_id', $send_otp_response->txId)->save();
      $current_status = 'VALIDATE_OTP';
      $this->config_factory->set('miniorange_ldap_status', $current_status)->save();
      Utilities::add_message(t('Verify email address by entering the passcode sent to @username', ['@username' => $username]), 'status');
    }
  }

  /**
   *
   */
  public function miniorange_ldap_validate_otp_submit(&$form, $form_state) {
    $otp_token = $form['miniorange_ldap_customer_otp_token']['#value'];
    $username = $this->config->get('miniorange_ldap_customer_admin_email');
    $phone = $this->config->get('miniorange_ldap_customer_admin_phone');
    $tx_id = $this->config->get('miniorange_ldap_tx_id');
    $customer_config = new MiniorangeLdapCustomer($username, $phone, NULL, $otp_token);
    $validate_otp_response = json_decode($customer_config->validateOtp($tx_id));

    if ($validate_otp_response->status == 'SUCCESS') {
      $this->config_factory->clear('miniorange_ldap_tx_id')->save();
      $password = $this->config->get('miniorange_ldap_customer_admin_password');
      $customer_config = new MiniorangeLdapCustomer($username, $phone, $password, NULL);
      $create_customer_response = json_decode($customer_config->createCustomer());
      if ($create_customer_response->status == 'SUCCESS') {
        $current_status = 'PLUGIN_CONFIGURATION';
        $this->config_factory->set('miniorange_ldap_status', $current_status)
          ->set('miniorange_ldap_customer_admin_email', $username)
          ->set('miniorange_ldap_customer_admin_phone', $phone)
          ->set('miniorange_ldap_customer_admin_token', $create_customer_response->token)
          ->set('miniorange_ldap_customer_id', $create_customer_response->id)
          ->set('miniorange_ldap_customer_api_key', $create_customer_response->apiKey)->save();
        Utilities::add_message(t('Customer account created.'), 'status');

      }
      elseif (trim($create_customer_response->message) == 'Email is not enterprise email.') {
        Utilities::add_message(t('There was an error creating an account for you.<br> You may have entered an invalid Email-Id
            <strong>(We discourage the use of disposable emails) </strong>
            <br>Please try again with a valid email.'), 'error');
        return;
      }
      else {
        Utilities::add_message(t('Error creating customer :- '.$create_customer_response->message), 'error');
        return;
      }
    }
    else {
      Utilities::add_message(t('Error validating OTP'), 'error');
      return;
    }
  }

  /**
   *
   */
  public function setup_call(array &$form, FormStateInterface $form_state) {
    Utilities::setup_call($form, $form_state);
  }

}
