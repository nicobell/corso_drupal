<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/webnicola
 */

namespace Drupal\ictp_visa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class IctpVisaoController extends ControllerBase {
/**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ictp_visa';
  }

   /**
   * {@inheritdoc}
   */
  function guide() {

    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('ictp_visa');
    $guide = $store->get('guida');
    // dump($guide);
    // [WIP]



    $answers = array(
      'v1' => true,
      'v2' => $this->set_1001100($guide),
      'v3' => $this->set_1001100($guide),
      'v4' => $this->set_1000000($guide),
      'v5' => $this->set_0001100($guide),
      'v6' => $this->set_1001100($guide),
      'v7' => $this->set_1000000($guide),
      'v8' => $this->set_1001100($guide),
      'v9' => $this->set_1001100($guide),
      'v10' => $this->set_0000001($guide),
      'v11' => $this->set_0010010($guide), 
      'v12' => $this->set_0000010($guide), 
      'v13' => $this->set_0100100($guide), 
      'v14' => $this->set_0100000($guide), 
      'v15' => true, 
      'p1' => $this->set_1001100($guide),
      'p2' => $this->set_1000000($guide),
      'p3' => $this->set_1000000($guide),
      'p4' => $this->set_0001100($guide),
      'p5' => $this->set_0000001($guide),
      'p6' => $this->set_0001100($guide),
      'p7' => $this->set_0010010($guide),
      'p8' => $this->set_0100000($guide),
      'p9' => $this->set_0100000($guide),
      'p10' => $this->set_0001100($guide),
      'h1' => true, 
      'h2' => $this->set_1001000($guide), 
      'h3' => $this->set_0110110($guide), 
      'h4' => $this->set_1001000($guide),
      'h5' => $this->set_1001000($guide),
      'h6' => $this->set_0000001($guide),
      'h7' => true,
      'h8' => $this->set_1001001($guide), 
      'h9' => true,
      'h10' => true,
    );

    return [
      '#theme' => 'visa_guide_answers',
      '#answers' => $answers,
    ];
  }



  private static function set_1001100($guide)
  {
    $view = false;

    if ($guide['field_visa_length_stay'] == '1'){
      if ($guide['field_visa_who_are_you'] == '1'){
        $view = true;
      }
    }else if ($guide['field_visa_length_stay'] == '2'){
      if ($guide['field_visa_who_are_you'] == '1' || $guide['field_visa_who_are_you'] == '2'){
        $view = true;
      }
    }

    return $view;
  }

  private static function set_1000000($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '1'){
      $view = true;
    }
   
    return $view;
  }

  private static function set_0001100($guide)
  {
    $view = false;

    if ($guide['field_visa_length_stay'] == '2'){
      if ($guide['field_visa_who_are_you'] == '1' || $guide['field_visa_who_are_you'] == '2'){
        $view = true;
      }
    }

    return $view;
  }

  private static function set_0000001($guide)
  {
    $view = false;

    if ($guide['field_visa_family_members'] == '1'){
      $view = true;
    }
   
    return $view;
  }
  
  private static function set_0010010($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '3'){
      $view = true;
    }

    return $view;
  }
  
  private static function set_0000010($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '3' && $guide['field_visa_length_stay'] == '2'){
      $view = true;
    }

    return $view;
  }

  private static function set_0100100($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '2'){
      $view = true;
    }

    return $view;
  }
  
  private static function set_0100000($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '2' && $guide['field_visa_length_stay'] == '1'){
      $view = true;
    }

    return $view;
  }

  private static function set_1001000($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '1'){
      $view = true;
    }

    return $view;
  }

  private static function set_0110110($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '2' || $guide['field_visa_who_are_you'] == '3'){
      $view = true;
    }

    return $view;
  }

  private static function set_1001001($guide)
  {
    $view = false;

    if ($guide['field_visa_who_are_you'] == '1' || $guide['field_visa_family_members'] == '1'){
      $view = true;
    }

    return $view;
  }
  

}


// 1) Who we are
// 1|NON-EU passaport holders
// 2|NON -EU passaport holders residing in a Schengen member state
// 3|EU passaport holders

// 2) I want information for accompaning family members
// TRUE | FALSE

// 1) Length of your stay
// 1|Less than 90 day
// 2|90 day or more




// Type of visit (NON USATA PIU')
// 1|Scientific Activity
// 2|Short-term/long-term visits
// 3|Associate
// 4|Programme




//   "field_visa_who_are_you" => "2"
//   "field_visa_family_members" => 0
//   "field_visa_length_stay" => "90 day or more"


//   "field_visa_type_visit" => "Short-term/long-term visits"
