<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class MiniorangeGeneralSettings extends LDAPFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_general_settings';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $attachments['#attached']['library'][] = 'ldap_auth/ldap_auth.admin';
    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "ldap_auth/ldap_auth.admin",
          "ldap_auth/ldap_auth.testconfig",
        ],
      ],
    ];

    $form['markup_14'] = [
      '#markup' => '<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container">',
    ];

    $form['markup_reg'] = [
      '#markup' => $this->t('<div><h2> Sign-In Settings  <a class="button button--primary shift-right" style="float: right" href ="https://plugins.miniorange.com/guide-to-configure-ldap-ad-integration-module-for-drupal" target="_blank">&#128366; Setup Guide</a></h2><br>'),
    ];
    $form['ntlm_gateway'] = [
      '#type' => 'horizontal_tabs',
      '#default_tab' => 'edit-debug',
    ];
    $form['Ntlm'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('NTLM/ Kerberos'),
      '#group' => 'ntlm_gateway',
    ];
    $form['Ntlm']['miniorange_ldap_enable_ntlm'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#description' => t('<b style="color: red">Note:</b> Enabling NTLM/Kerberos login will protect your website through login with NTLM/Kerberos. Upgrade to the <a href="' . $base_url . '/admin/config/people/ldap_auth/Licensing"><b>Premium, All-inclusive</b></a> version of the module to use this feature.'),
      '#title' => t('Enable NTLM/ Kerberos Login'),
    ];

    $form['Ntlm']['miniorange_ldap_nltm_desc'] = [
      '#markup' => t('<div><br>
              <h1>What is Microsoft NTLM?</h1><hr>
              <p>NTLM is the authentication protocol used on networks that include systems running the Windows operating system and on stand-alone systems.</p>
              <p>NTLM credentials are based on data obtained during the interactive logon process and consist of a domain name, a user name, and a one-way hash of the users password. NTLM uses an encrypted challenge/response protocol to authenticate a user without sending the user password over the wire. Instead, the system requesting authentication must perform a calculation that proves it has access to the secured NTLM credentials.<br></p></div>'),
    ];

    $form['Ntlm']['miniorange_ldap_kerbeors_desc'] = [
      '#markup' => t('<br>
            <h1>What is Kerberos?</h1><hr>
            <p>Kerberos is a client-server authentication protocol that enables mutual authentication –  both the user and the server verify each other’s identity – over non-secure network connections.  The protocol is resistant to eavesdropping and replay attacks, and requires a trusted third party.</p>
            <p>The Kerberos protocol uses a symmetric key derived from the user password to securely exchange a session key for the client and server to use. A server component is known as a Ticket Granting Service (TGS) then issues a security token (AKA Ticket-Granting-Ticket TGT) that can be later used by the client to gain access to different services provided by a Service Server.<br></p><br>'),
    ];

    $form['gateway'] = [
      '#type' => 'details',
      '#title' => $this
        ->t("Gateway Login"),
      '#group' => 'ntlm_gateway',
      '#open' => TRUE,
    ];

    $form['gateway']['miniorange_ldap_enable_gateway'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#description' => $this->t('<b style="color: red">Note:</b> Enabling allows login to publicly/privately hosted sites using credentials stored in Active Directory. Upgrade to the <a href="' . $base_url . '/admin/config/people/ldap_auth/Licensing"><b>All Inclusive</b></a> version of the module to use this feature. '),
      '#title' => t('Enable Gateway Login   <a class="btn btn-primary btn-sm" style="position:absolute;top: 5px;right: 5px;" href ="https://plugins.miniorange.com/guide-to-configure-ldap-ad-integration-module-for-drupal" target="_blank">Setup Guide</a> '),
    ];

    $form['gateway']['miniorange_ldap_gateway_desc'] = [
      '#markup' => $this->t('<br><br><br><h1>What is Gateway Login? </h1> <hr><p>
      LDAP Gateway allows login to publicly/privately hosted sites using credentials stored in Active Directory, OpenLDAP and other LDAP servers. If the LDAP Server is not publicly accessible from your site,
      this module can be used in conjunction with the miniOrange LDAP Gateway, which is deployed at the DMZ server in the intranet.'),
    ];

    $module_path = \Drupal::service('extension.path.resolver')->getPath('module', 'ldap_auth');

    $form['gateway']['gateway_login_img'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="box" class="image_class">',
      '#suffix' => '</div>',
      '#markup' => '<img src="' . $base_url . '/' . $module_path . '/resources/gateway_login.png" alt= "Gateway_login_image" >',
    ];
    $form['register_close'] = [
      '#markup' => '</div></div>',
    ];
    Utilities::AddSupportButton($form, $form_state);
    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   *
   */
  public function setup_call(array &$form, FormStateInterface $form_state) {
    Utilities::setup_call($form, $form_state);
  }

}
