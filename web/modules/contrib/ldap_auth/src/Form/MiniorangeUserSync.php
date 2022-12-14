<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class MiniorangeUserSync extends FormBase {

  /**
   *
   */
  public function getFormId() {
    return 'user_sync';
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
        ],
      ],
    ];

    /*
     *  All Inclusive feature showcase
     */
    $form['markup_top'] = [
      '#markup' => $this->t('<div class="mo_ldap_table_layout_1"><div class="mo_ldap_table_layout container" >
                           <h2 ><span>User & Password Sync  <a href= "' . $base_url . '/admin/config/people/ldap_auth/Licensing"><span style="font-size: small">[All Inclusive]</span></a> <a class="button button--primary" style="float:right;margin: 0px;" href ="https://www.drupal.org/docs/contributed-modules/ldap-integration/ldap-password-sync" target="_blank">&#128366; Configure Provisioning</a></span></h2><hr><br>'),
    ];
    $form['sync_markup_note'] = [
      '#markup' => $this->t('<div class="mo_ldap_highlight_background_note_1" >
            These feature allows you to perform of directory & password synchronization for your Drupal users with any LDAP Directory and vice versa.</div><br>'),
    ];

    $form['create_user_in_ldap'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Create users in Active Directory/LDAP Server when a user is created in Drupal.'),
    ];

    $form['delete_user_in_ldap'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Delete users in Active Directory/LDAP Server when a user is deleted in Drupal.'),
    ];

    $form['miniorange_ldap_update_user_info'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Update user information in Active Directory/LDAP Server when user information is updated in Drupal.'),
    ];

    $form['miniorange_ldap_enable_password_sync'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Update user password in your LDAP/AD server when a user resets the password in Drupal .'),
      '#description' => $this->t('<b>Note:- </b>You need LDAPS for password related operations.'),
    ];

    $form['miniorange_ldap_enable_ldap_markup2'] = [
      '#markup' => $this->t("<br><div><h2>Import Users From LDAP:<a class='button button--primary' style='float:right;margin: 0px;' href ='https://www.drupal.org/docs/contributed-modules/ldap-integration/import-users-from-ldap' target='_blank'>&#128366; How to Import users</a></h2></div><hr>"),
    ];
    $form['miniorange_ldap_import_at_cron'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Import Users from your LDAP/AD server on cron'),
    ];

    $form['miniorange_ldap_load_account_with_email'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Search User By Email, if not found by Username'),
    ];

    $form['miniorange_ldap_import_mapping'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Enable Attribute and Role mapping during User sync'),
    ];

    $form['miniorange_ldap_import_auto_create_users'] = [
      '#type' => 'checkbox',
      '#disabled' => TRUE,
      '#title' => $this->t('Auto Create users after Sync'),
    ];

    $form['miniorange_ldap_set_of_radiobuttons1']['miniorange_ldap_block_new_users'] = [
      '#type' => 'radios',
      '#disabled' => TRUE,
      '#options' => [
        'block_ad' => $this->t('Block the new users which are not present in Drupal and present in AD'),
        'block_drupal' => $this->t('Block the users which are not present in AD and present in Drupal'),
        'block_none' => $this->t('Do not block any user'),
      ],
    ];

    $form['miniorange_ldap_import_username_attribute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username Attribute:'),
      '#disabled' => TRUE,
      '#description' => $this->t('Enter the attribute with which you wish to search against their Drupal Usernames.Example: sAMAccountName, mail, userPrincipalName'),
      '#attributes' => ['placeholder' => 'Enter Username Attribute'],
      '#suffix' => '<br>',
    ];

    $form['miniorange_ldap_save_import_users_settings'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
      '#disabled' => TRUE,
    ];

    $form['miniorange_ldap_import_users'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import All Users From LDAP'),
      '#disabled' => TRUE,
    ];

    $form['miniorange_ldap_save_export_users_settings'] = [
      '#prefix' => "<br><br>",
      '#type' => 'submit',
      '#disabled' => TRUE,
      '#value' => $this->t('Export Log'),
    ];

    $form['mo_markup_div_imp_2'] = ['#markup' => '</div>'];

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
