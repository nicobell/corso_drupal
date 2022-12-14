<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class AttributeMapping extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_attrmapping';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $attachments['#attached']['library'][] = 'ldap_auth/ldap_auth.admin';
    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "ldap_auth/ldap_auth.admin",
          "ldap_auth/ldap_auth.testconfig",
        ],
      ],
    ];
    global $base_url;

    $form['markup_top'] = [
      '#markup' => t('<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container" >
          <span><h2>Attribute Mapping <a class="button button--primary" style="float:right;margin: 0px;" href ="https://developers.miniorange.com/docs/drupal/ldap/attribute-and-role-mapping" target="_blank">&#128366;  How to Perform Mapping</a></h2></span><hr>'),
    ];

    $form['miniorange_ldap_email_attribute'] = [
      '#type' => 'textfield',
      '#title' => t('Email Attribute'),
      '#required' => TRUE,
      '#attributes' => [
        'style' => 'width:700px; background-color: hsla(0,0%,0%,0.02) !important;',
        'placeholder' => t('Enter Email attribute'),
      ],
      '#default_value' => $this->config->get('miniorange_ldap_email_attribute'),
      '#description' => t("Enter the LDAP attribute in which you get the email address of your users"),
    ];

    $form['markup_idp_user_attr_header'] = [
          '#markup' => '</br><h5>Add Mapping for User Attributes &nbsp;&nbsp;<a aria-disabled="true" class="mo_ldap_btn1 button button_class_attr" >+</a></h5> ',
      ];
    $form['markup_cam'] = [
      '#markup' => '<br>
            <div class="mo_ldap_highlight_background_note_1"> 1. LDAP Server Attribute Name :  It is the attribute name received from your LDAP server.   <br>
            2. Drupal Machine filed Name :  It is the user attribute (machine name) in which you want the corresponding LDAP attribute value. 
            <br><b>For example: </b>If the attribute name in the drupal is firstname then its machine name will be field_firstname.<br>
            <b>This feature is available in the <a href="' . $base_url . '/admin/config/people/ldap_auth/Licensing">[Premium, All-inclusive]</a> version of the module.</b>
            </div>',
    ];


    $form['miniorange_ldap_attr_name'] = [
          '#type' => 'textfield',
          '#prefix' => '<div><table><tr><td>',
          '#suffix' => '</td>',
          '#title' => t('LDAP Server Attribute Name'),
          '#attributes' => ['placeholder' => 'LDAP server attribute name'],
          '#required' => FALSE,
          '#disabled' => TRUE,
      ];
    $form['miniorange_ldap_server_name'] = [
          '#type' => 'textfield',
          '#prefix' => '<td>',
          '#suffix' => '</td>',
          '#title' => t('Drupal Field Machine Name'),
          '#attributes' => array('placeholder' => 'Drupal machine field name'),
          '#required' => FALSE,
          '#disabled' => TRUE,
      ];

    $form['miniorange_ldap_sub_name'] = [
          '#prefix' => '<td>',
          '#suffix' => '</td></tr></table></div>',
          '#type' => 'button',
          '#disabled' => 'true',
          '#value' => '-',
      ];

    $form['miniorange_ldap_gateway_config1_submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Save Configuration'),
      '#prefix' => '<br>',
      '#suffix' => '<br>',
    ];

    /**
     * User Role Mapping
     */

    $form['markup_cam_attr'] = [
      '#markup' => t('<br><br><div><h2>User Role Mapping  <a href="' . $base_url . '/admin/config/people/ldap_auth/Licensing"><small style="font-size: small">[Premium, All-inclusive]</small></a></h2><hr>'),
    ];

    $form['miniorange_ldap_enable_rolemapping'] = [
      '#type' => 'checkbox',
      '#title' => t('Check this option if you want to <b>Enable Role Mapping</b>'),
      '#description' => t('<b style="color: red">Note:</b> Enabling Role Mapping will automatically map Users from LDAP Groups to below selected Drupal Role.<br> Role mapping will not be applicable for primary admin of Drupal.'),
      '#suffix' => '</div>',
      '#disabled' => TRUE,
    ];

    $form['miniorange_ldap_disable_role_update'] = [
      '#type' => 'checkbox',
      '#title' => t("Check this option if you don't want to remove existing roles of users (New Roles will be added)"),
      '#disabled' => TRUE,
    ];

    $form['miniorange_ldap_enable_ntlm_role_mapping'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => t('Enable Role Mapping for NTLM Users'),
      '#description' => t('<b style="color: red">Note: </b>Likewise Role Mapping, enabling this option automatically map NTLM user roles from LDAP Groups to below selected Drupal Role.'),
    ];

    $mrole = user_role_names($membersonly = TRUE);
    $drole = array_values($mrole);

    $form['miniorange_ldap_default_mapping'] = [
      '#type' => 'select',
      '#title' => t('Select default group for the new users'),
      '#options' => $mrole,
      '#default_value' => $drole,
      '#attributes' => ['style' => 'width:73%; border-radius: 4px; padding: 5px;'],
      '#disabled' => FALSE,
    ];

    $form['miniorange_ldap_memberOf'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => t('LDAP Group Name'),
      '#attributes' => ['style' => 'width:73%; background-color: hsla(0,0%,0%,0.08) !important;', 'placeholder' => 'memberOf'],
    ];

    foreach ($mrole as $roles) {
      $rolelabel = str_replace(' ', '', $roles);
      $form['miniorange_ldap_role_' . $rolelabel] = [
        '#type' => 'textfield',
        '#title' => t($roles),
        '#attributes' => ['style' => 'width:73%;background-color: hsla(0,0%,0%,0.08) !important;', 'placeholder' => 'Semi-colon(;) separated Group/Role value for ' . $roles],
        '#disabled' => TRUE,
      ];
    }

    $form['miniorange_ldap_gateway_config4_submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Configuration'),
      '#disabled' => TRUE,
      '#prefix' => '<br>',
      '#suffix' => '<br><br></div>',
    ];

    Utilities::AddSupportButton($form, $form_state);

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config_factory->set('miniorange_ldap_email_attribute', trim($form_state->getValue('miniorange_ldap_email_attribute')))->save();
  }

  /**
   *
   */
  public function setup_call(array &$form, FormStateInterface $form_state) {
    Utilities::setup_call($form, $form_state);
  }

}
