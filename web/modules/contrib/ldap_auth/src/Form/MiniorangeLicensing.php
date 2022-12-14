<?php

namespace Drupal\ldap_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ldap_auth\MiniorangeLdapSupport;
use Drupal\ldap_auth\Utilities;

/**
 *
 */
class MiniorangeLicensing extends LDAPFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'miniorange_ldap_licensing';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $form['markup_library'] = [
      '#attached' => [
        'library' => [
          "ldap_auth/ldap_auth.admin",
          "ldap_auth/ldap_auth.style_settings",
          "ldap_auth/ldap_auth.main",

        ],
      ],
    ];
    $this->config_factory->set('miniorange_ldap_license_page_visited', "True")->save();

    $form['markup_1'] = [
      '#markup' => $this->t('<div class="mo_ldap_licensing_table_layout">
            <div class="mo_ldap_license_layout"><br>'),
    ];

    $refer = $base_url . '/admin/config/people/ldap_auth/ldap_config';

    $module_path = \Drupal::service('extension.path.resolver')->getPath('module', 'ldap_auth');

    $form['miniorange_ldap_license1'] = [
      '#markup' => $this->t('<html lang="en">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <!-- Main Style -->
          </head>
        <body>

<!--Heading-->


    <h2 id="pricing-header"><a href=' . $refer . ' class="button button--danger" style="float:left;">&#11164;&nbsp;BACK</a>Active Directory / LDAP Integration <br> Licensing Plans</h2><br>
<!--Heading End -->
<!--Navbar-->
<div class="topnav">
  <a href="#plans">Plans</a>
  <a href="#what_is_instance">Instance</a>
  <a href="#feature_comparison">Feature Comparison</a>
  <a href="#steps_upgrade_premium">Upgrade Steps</a>
  <a href="#faq">FAQs</a>
  <a href="#payment_method">Payment Methods</a>
  <a href="#request_Quote">Request Quote</a>
</div>

<!--End Navbar-->

<!-- Contact Us -->
<div id="contact">
  <br>
  <h2>Choose Your Licensing Plan</h2>
  <h4>Need assistance in choosing a plan? <a href="mailto:drupalsupport@xecurify.com">Contact Us</a></h4>
  <br>
</div>

<!-- End of Contact US-->

<!-- Plans Cards -->

<div class="plan_cards_main" id="plans">

    <div class="row">
        <div class="col-md-3 plan_cards_inner_divs">
          <div class="card">
            <h5 class="pricing-card-head">Standard</h5>
              <div class="card-body mo-card-body  mo_card_body">
              <br><br><br>
                <p class="card-text sup_price" id="standard_price"><sup>$</sup>249</p>
                <p id="standard_discount"></p>
                    <span class="instance_dropdown">
                    <label for="instances1">Instances:</label>
                    <select id="instances1" name="instances" onchange="Instance_Pricing(this.value,instances2,instances3)">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                        <option value="40">40</option>
                        <option value="50">50</option>
                    </select>
                    </span>
              </div>
                <p class="card-footer mo-card-footer"><a href="https://login.xecurify.com/moas/login?redirectUrl=https://login.xecurify.com/moas/initializepayment&requestOrigin=drupal8_ldap_standard_plan">UPGRADE NOW</a></p>
          </div>
        </div>

        <div class="col-md-3 plan_cards_inner_divs">
          <div class="card">
            <h5 class=" pricing-card-head mt-0 mb-0">Premium</h5>
            <div class="card-body mo_card_body">
            <br><br><br>
                <p class="card-text sup_price" id="premium_price"><sup>$</sup>399</p>
                <p id="premium_discount"></p>
                <span class="instance_dropdown">
                <label for="instances2">Instances:</label>&nbsp;&nbsp;
                <select id="instances2" name="instances" onchange="Instance_Pricing(this.value,instances1,instances3)">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6</option>
                    <option value="7">7</option>
                    <option value="8">8</option>
                    <option value="9">9</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                    <option value="40">40</option>
                    <option value="50">50</option>
                </select>
                </span>
            </div>
            <p class="card-footer mo-card-footer"><a href="https://login.xecurify.com/moas/login?redirectUrl=https://login.xecurify.com/moas/initializepayment&requestOrigin=drupal8_ldap_premium_plan">UPGRADE NOW</a></p>
          </div>
        </div>

        <div class="col-md-3  plan_cards_inner_divs">
            <div class="card">
                <h5 class=" pricing-card-head mt-0 mb-0">All-Inclusive Plan</h5>
                <div class="card-body mo_card_body">
                <br><br><br>
                    <p  class="card-text sup_price" id="AllInclusive_price" > <sup>$</sup>649</p>
                    <p id="AllInclusive_discount"></p>
                    <span class="instance_dropdown">
                      <label for="instances3">Instances:</label>&nbsp;&nbsp;
                      <select id="instances3" onchange="Instance_Pricing(this.value,instances1,instances2)">
                            <option value="1" >1</option>
                            <option value="2" >2</option>
                            <option value="3" >3</option>
                            <option value="4" >4</option>
                            <option value="5" >5</option>
                            <option value="6" >6</option>
                            <option value="7" >7</option>
                            <option value="8" >8</option>
                            <option value="9" >9</option>
                            <option value="10">10</option>
                            <option value="15" >15</option>
                            <option value="20" >20</option>
                            <option value="30" >30</option>
                            <option value="40" >40</option>
                            <option value="50" >50</option>
                      </select>
                    </span>
                </div>
                <p class="card-footer mo-card-footer"><a href="https://www.miniorange.com/contact">CONTACT US</a></p>
            </div>
        </div>
    </div>

</div>

<!-- Plan Cards End-->

<br>
<!-- What is Instance -->
<div class="container instance" id="what_is_instance">
    <h3>What is an Instance?</h3>
    <p>A Drupal instance refers to a single installation of a Drupal site. It refers to each individual website where the module is activated. In the case of multisite/subsite Drupal setup, each site with a separate database will be counted as a single instance. For eg. If you have the dev-staging-prod type of environment then you will require 3 licenses of the module (with additional discounts applicable on pre-production environments). Contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> for bulk discounts.</p>
</div>

<!--End of Instance -->

<!--Features Comparison-->
<div class="container" id="feature_comparison">
    <h3 class="feature_comparison">FEATURE COMPARISON</h3><hr style="background-color:#E97D68;">

    <table>
          <tr style="background-color: #af3655; color: white;">
            <th>Features List</th>
            <th>Free</th>
            <th>Standard</th>
            <th>Premium</th>
            <th>All-Inclusive</th>
          </tr>
          <tr>
            <td>Unlimited Authentications</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Single LDAP Directory Configuration</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Search User by Single Attribute</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>Custom Search Filter</td>
            <td>Custom Search Filter</td>
          </tr>
          <tr>
            <td>Single Search Base</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>Multiple Search Base</td>
            <td>Multiple Search Base</td>
          </tr>
          <tr>
            <td>Attribute Mapping</td>
            <td>Email Mapping</td>
            <td>Custom Mapping</td>
            <td>Custom Mapping</td>
            <td>Custom Mapping</td>
          </tr>
          <tr>
            <td>Auto Create User</td>
            <td></td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Role Mapping</td>
            <td></td>
            <td>Basic</td>
            <td>Advanced</td>
            <td>Advanced</td>
          </tr>
          <tr>
            <td>TLS Connection</td>
            <td></td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Support for Custom Integration</td>
            <td></td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>NTLM & Kerberos Authentication</td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Redirect user after login </td>
            <td></td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Page Restriction </td>
            <td></td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Import users from LDAP server </td>
            <td></td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Password and Directory Sync</td>
            <td></td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Auto Create users after Sync</td>
            <td></td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
          </tr>
          <tr>
            <td>Attribute and Role mapping during User sync</td>
            <td></td>
            <td></td>
            <td></td>
            <td>&#x2714;</td>
          </tr>
    </table>
</div>
<!-- Feature Comparison End -->


<!--How to upgrade to premium-->

<div class="container steps_upgrade_premium" id="steps_upgrade_premium">
  <h3 style="text-align: center; margin-top:3%;">HOW TO UPGRADE TO THE LICENSED VERSION OF THE MODULE</h3><hr>
  <div class="row">
   <div class="col-md-6">
     <div class="upgrade_step"><div class="upgrade_steps_wise">1</div> Click on Upgrade Now button for required licensed version plan and you will be redirected to miniOrange login console.</div>
     <div class="upgrade_step"><div class="upgrade_steps_wise">2</div> Enter your username and password with which you have created an account with us. After that you will be redirected to payment page.</div>
     <div class="upgrade_step"><div class="upgrade_steps_wise">3</div> Enter your card details and proceed for payment. On successful payment completion, the licensed version will be available for download.</div>
   </div>
   <div class="col-md-6">
        <div class="upgrade_step"><div class="upgrade_steps_wise">4</div> Download the licensed version module from under the Releases and Downloads section.</div>
    <div class="upgrade_step"><div class="upgrade_steps_wise">5</div> Uninstall and then delete the free version of the module from your Drupal site. Now install the downloaded latest version of the module</div>
   </div>
  </div>
</div> <br><br>

<!--End of Upgrade-->

<!--FAQ-->
<div class="container" id="faq">
  <h3 id="faq-heading" >Frequently Asked Questions</h3><hr>
  <div class= "row">
    <div class="col-md-6">

    <button type="button" class="collapsible">Are the Licenses Perpetual?</button>
    <div class="content">
      <p>The modules licenses are perpetual and includes 12 months of free maintenance (version updates). You can renew maintenance after 12 months at 50% of the current license cost.</p>
    </div>

    <button type="button" class="collapsible">Does miniOrange Offer Technical Support?</button>
    <div class="content">
      <p>Yes, we provide 24*7 support for all and any issues you might face while using the module including technical support from our developers. You can get prioritized support based on the Support Plan you have opted.</p>
    </div>

    </div>
    <div class="col-md-6">

    <button type="button" class="collapsible">What is the Refund Policy?</button>
    <div class="content">
      <p>At miniOrange, we want to ensure you are 100% happy with your purchase. If the module that you purchased is not working as advertised and you\'ve attempted to resolve any issues with our support team, which couldn\'t get resolved, we will refund the whole amount given that you raised a refund request within the first 10 days of the purchase. Please email us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> for any queries regarding the return policy or contact us <a href="https://www.miniorange.com/contact">here</a>.</p>
    </div>

    <button type="button" class="collapsible">Does miniOrange store any User data ?</button>
    <div class="content">
      <p>miniOrange does not store or transfer any data which is coming from your LDAP server. All the data remains within your premises.</p>
    </div>

    </div>

  </div>

</div>
<!-- FAQ End-->

        </body>
        </html>'),
    ];

    $form['miniorange_ldap_email'] = [
      '#type' => 'textfield',
      '#default_value' => Utilities::getCustomerEmail(),
      '#title' => t('Email:'),
      '#attributes' => ['placeholder' => 'name@example.com'],
      '#required' => TRUE,
      '#prefix' => '<div class="floatee shift-right"><div class="container" id="request_Quote"><h3 id="request-quote-heading">REQUEST QUOTE<h3><hr>',
    ];

    $form["miniorange_ldap_number_of_instances"] = [
      "#type" => "select",
      "#title" => t("Select the number of instances: "),
      '#attributes' => [
        'style' => 'width:42%;',
      ],
      "#options" => ['1' => t('1'), '2' => t('2'), '3' => t('3'), '4' => t('4'), '5' => t('5'), '6' => t('6'), '7' => t('7'), '8' => t('8'), '9' => t('9'), '10' => t('10'), '15' => t('15'), '20' => t('20'), '30' => t('30'), '40' => t('40'), '50' => t('50')],
    ];

    $form["foobar_options"]["miniorange_ldap_plan_select"] = [
      "#type" => "select",
      "#title" => t("Select your plan:"),
      '#attributes' => [
        'style' => 'width:42%;',
      ],
      "#options" => [
        "Standard" => t("Standard"),
        "Premium" => t("Premium"),
        "All-Inclusive" => t("All-Inclusive"),
        "Not-Sure" => t("Not-Sure"),
      ],

    ];

    $form['miniorange_ldap_support_comment'] = [
      '#type' => 'textarea',
      '#title' => t('Comments'),
      '#cols' => '10',
      '#rows' => '5',
      '#attributes' => ['style' => 'width:42%;height:80px;', 'placeholder' => 'Write your query here'],
      '#required' => TRUE,
    ];
    $form['miniorange_ldap_support_submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit Query'),
      '#submit' => ['::saved_request_quote'],
      '#suffix' => '</div>',

    ];
    $form['miniorange_ldap_license2'] = [
      '#markup' => t('<html lang="en">
          <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <!-- Main Style -->
          </head>
        <body>
<!--Request Quote Form-->



<!--Supported Payment methods-->

<div class="container payment_method_main_divs" id="payment_method">
    <h3 style="text-align: center; margin:3%;">PAYMENT METHODS</h3><hr><br><br>
    <div class="row">
        <div class="col-md-3 payment_method_inner_divs">
            <br><div><img src="' . $base_url . '/' . $module_path . '/resources/card_payment.png" width="120" ><h4>Card Payment</h4></div><hr>
            <p>If the payment is made through Credit Card/International Debit Card, the license will be created automatically once the payment is completed.</p>
        </div>
        <div class="col-md-3 payment_method_inner_divs">
            <br><div><img src="' . $base_url . '/' . $module_path . '/resources/paypal.png" class="payment_img" width="120"><h4>PayPal</h4></div><hr>
            <p>Use the following PayPal ID <a href="mailto:info@xecurify.com">info@xecurify.com</a> for making the payment via PayPal.</p>
        </div>
        <div class="col-md-3 payment_method_inner_divs">
            <br><div><img src="' . $base_url . '/' . $module_path . '/resources/bank_transfer.png" width="150" ><h4>Bank Transfer</h4></div><hr>
            <p>If you want to use bank transfer for the payment then contact us at <a href="mailto:drupalsupport@xecurify.com">drupalsupport@xecurify.com</a> so that we can provide you the bank details.</p>
        </div>
    </div>
</div>
        </body>
        </html>'),
    ];
    $form['markup_6'] = [
      '#markup' => '</div></div><br>
        ',
    ];

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
  public function saved_support(array &$form, FormStateInterface $form_state) {
    $email = $form['miniorange_ldap_email']['#value'];
    $instances = $form['miniorange_ldap_number_of_instances']['#value'];
    $planname = $form["foobar_options"]['miniorange_ldap_plan_select']['#value'];
    $query = $form['miniorange_ldap_support_comment']['#value'];
    $query = $query . '<br> No of Instances <br>' . $instances . '&nsbp Plan <br>' . $planname;
    Utilities::send_support_query($email, '', $query);
  }

  /**
   *
   */
  public static function saved_request_quote(array &$form, FormStateInterface $form_state) {
    $email = trim($form['miniorange_ldap_email']['#value']);

    if ($email == '' || !\Drupal::service('email.validator')->isValid($email)) {
      Utilities::add_message(t('The email address <b><i>' . $email . '</i></b> is not valid.'), 'error');
      return;
    }

    $instances = $form['miniorange_ldap_number_of_instances']['#value'];
    $planname = trim($form["foobar_options"]['miniorange_ldap_plan_select']['#value']);
    $query1 = trim($form['miniorange_ldap_support_comment']['#value']);
    $query = $query1 . '<br> No of Instances: ' . $instances . '<br> Plan: ' . $planname;

    if (empty($email)||empty($query)) {
      Utilities::add_message(t('The <b><u>Email</u></b> and <b><u>Query</u></b> fields are mandatory.'), 'error');
      return;
    }
    elseif (!\Drupal::service('email.validator')->isValid($email)) {
      Utilities::add_message(t('The email address <b><i>' . $email . '</i></b> is not valid.'), 'error');
      return;
    }

    $support = new MiniorangeLdapSupport($email, '', $query, 'request_quote');
    $support_response = $support->sendSupportQuery();

    if ($support_response) {
      Utilities::add_message(t('Request successfully sent. We will get back to you shortly.'), 'status');
    }
    else {
      Utilities::add_message(t('Error sending request.'), 'error');
    }
  }

}
