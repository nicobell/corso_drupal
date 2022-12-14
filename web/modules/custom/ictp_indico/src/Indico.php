<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/interfasesrl/ictp
 */

namespace Drupal\ictp_indico;

use Drupal\taxonomy\Entity\Term;

class Indico {
  /**
   * @param string $string that we can convert to a \Date iso
   *
   * @return iso \Date
   */
  public static function getDeadline($string = '') {
    //preg_match('/"([^"]+)"/', $string);
    $filter = '/(\d{1,4}([.\-\/])\d{1,2}([.\-\/])\d{1,4})/';
    $matchCount = preg_match($filter, $string, $match);
    if ($matchCount > 0) {
      $day = explode('/', $match[1]);
    } else {
      $day = [];
    }
    return count($day) == 3
      ? $day[2] . '-' . $day[1] . '-' . $day[0]
      : '';
  }

  /**
   * @param string $time Time
   *
   * @return the time in second
   */
  public static function timeConvert($time) {
    sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
    $second = $hours * 3600 + $minutes * 60 + $seconds;
    return $second;
  }

  /**
   * @param array $keywords Keywords that we get section
   *
   * @return the id of the Drupal section
   *
   */
  public static function getSection($keywords) {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);

    $drupal_section = array_map(function ($term) {
      $indico_keywords = $term->get('field_indico_keywords')->value;
      $new_term['id'] = $term->get('tid')->value;
      $new_term['name'] = str_replace(',', '', $term->get('name')->value);
      $new_term['keywords'] = $indico_keywords ? explode(', ', $indico_keywords) : [];
      return $new_term;
    }, $terms);

    $tid = null;
    // Remove first array item (home)
    array_shift($drupal_section);
    foreach($drupal_section as $section) {
      
      $array_diff = array_diff($section['keywords'], $keywords);
      if (count($section['keywords']) > count($array_diff)) {
        $tid = $section['id'];
      } else if(!count($section['keywords'])) {
        $tid = null;
      }
    }
    return $tid;
  }

  /**
   * @param array $keywords Keywords that we get sections
   *
   * @return the array ids of the Drupal sections
   *
   */
  public static function getSections($keywords) {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);

    $drupal_section = array_map(function ($term) {
      $indico_keywords = $term->get('field_indico_keywords')->value;
      $new_term['id'] = $term->get('tid')->value;
      $new_term['name'] = str_replace(',', '', $term->get('name')->value);
      $new_term['keywords'] = $indico_keywords ? explode(', ', $indico_keywords) : [];
      return $new_term;
    }, $terms);

    $tid = array(1); // di default aggiunta HOME
    // Remove first array item (home)
    array_shift($drupal_section);
    foreach($drupal_section as $section) {
      $array_diff = array_diff($section['keywords'], $keywords);
      if (count($section['keywords']) > count($array_diff)) {
        $tid[] = $section['id'];
      }
    }

    return $tid;
  }

  /**
   * @param array $keywords Keywords that we get section
   *
   * @return the topic string
   *
   */
  public static function getTopic($keywords) {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);

    $event_topic = array_map(function ($term) {
      $indico_topic = $term->get('field_indico_topic')->value;
      return $indico_topic ? explode(', ', $indico_keywords) : [];
    }, $terms);

    $value = null;
    // Remove first array item (home)
    array_shift($event_topic);
    foreach($event_topic as $topic) {
      $array_diff = array_diff($topic, $keywords);
      if (count($topic) > count($array_diff)) {
        $value = $topic;
      } else {
        $value = '';
      }
    }

    return $value;
  }

  /**
   * @param string $location that we get location
   *
   * @return the id of the Drupal location term, default physical
   *
   * One of: online, hybrid, physical
   */
  public static function getLocation($location, $keywords) {
    // term physical
    $tid = '17';
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('event_location', 0, 1, TRUE);

    $events_location = array_map(function ($term) {
      $indico_keywords = $term->get('field_indico_keywords')->value;
      $new_term['id'] = $term->get('tid')->value;
      $new_term['keywords'] = $indico_keywords ? explode(', ', $indico_keywords) : [];
      return $new_term;
    }, $terms);


    foreach($events_location as $event_location) {
      if (in_array($location, $event_location['keywords'])) {
        $tid = $event_location['id'];
      } elseif (in_array($keywords, $event_location['keywords'])) {
        $array_diff = array_diff($event_location, $keywords);
        if (count($event_location) > count($array_diff)) {
          $tid = $event_location['id'];
        }
      }
    }

    return $tid;
  }

  /**
   * @param string $categoryId that we get category id
   *
   * @return the boolean
   *
   */
  public static function getHostedActivities($categoryId) {
    return $categoryId == '2l130';
  }

  /**
   * @param string $categoryId that we get category id
   *
   * @return the boolean
   *
   */
  public static function getActivitiesOutside($categoryId) {
    return $categoryId == '2l132';
  }

  /**
   * @param string $item that we get title and categoryId
   *
   * @return the tid (term id taxonomy event_type)
   * indico category:
   * 2l130 Hosted activities
   * 2l131 ICTP activities in Trieste
   * 2l132 ICTP activities outside Trieste
   * 2l133 Throughout the year -> CATEGORY SENZA EVENTI
   */
  public static function getEventType($keywords, $title, $categoryId) {
    $tid = '52'; // Seminar
    if ($categoryId == '6') {
      // abdus salam distinguished lectures
      $tid = '51';
    } elseif ($categoryId == '3l162') {
      // colloquia
      $tid = '53';
    } elseif ($categoryId == '10') {
      // prizes
      $tid = '58';
    } elseif ($categoryId == '11') {
      // ceremonies
      $tid = '59';
    } elseif ($categoryId == '2l131' || $categoryId == '2l132') {
      // In Trieste || Outside Trieste
      if (in_array('school', $keywords) || strpos(strtolower($title), 'school')) {
        $tid = '54';
      } elseif (in_array('workshop', $keywords) || strpos(strtolower($title), 'workshop')) {
        $tid = '55';
      } elseif (in_array('conference', $keywords) || strpos(strtolower($title), 'conference')) {
        $tid = '56';
      } else {
        $tid = '60'; // Scientific activity
      }
    } elseif ($categoryId == '2l130') {
      // Hosted
      $tid = '60'; // Scientific activity
    } 
    return $tid;
  }
}

