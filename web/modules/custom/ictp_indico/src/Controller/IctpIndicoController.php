<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/webnicola
 */

namespace Drupal\ictp_indico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\ictp_indico\Indico;
use Drupal\Core\Render\Markup;

class IctpIndicoController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ictp_indico';
  }

  /**
   * {@inheritdoc}
   */
  function home() {
    $markup = '<div>';
    $markup .= '<h2>API per importare gli eventi da Indico</h2>';
    $markup .= '<p><a href="/admin/ictp_indico/add" class="button button-action button--primary button--small">Add events from indico</a></p>';
    $markup .= '<p><a href="/admin/ictp_indico/add/archive/" class="button button-action button--primary button--small">Add events from indico archive</a></p>';
    $markup .= '<p><a href="/admin/ictp_indico/delete" class="button button-action button--primary button--small">Delete events from Drupal</a> <em>for testing, use with caution </em></p>';
    $markup .= '</div>';
    return [
      '#markup' => $markup,
    ];
  }
  /**
   * Add node type 'event'
   *
   */
  function add() {
    // to be reviewed
    $current_day = date('Y/m/d');
    $category_indico = '2l132,2l131,2l130,2l133,10,11&limit=100&started=' . $current_day;
    $from = INDICO_URL . END_POINT . $category_indico;
    $indico_events = file_get_contents($from);
    $indico_array =  json_decode($indico_events, TRUE);
    $indico_items = $indico_array['results'];

    foreach ($indico_items as $item) {
      $nid = \Drupal::entityQuery('node')
        ->condition('field_indico_guid', $item['id'], '=')
        ->execute();
      if (count($nid)) {
        \Drupal\ictp_indico\Controller\IctpIndicoController::updateNode(reset($nid), $item);
      } else {
        \Drupal\ictp_indico\Controller\IctpIndicoController::addNode($item);
      }
    }

    \Drupal\ictp_indico\Controller\IctpIndicoController::setSectionKeywords();
    \Drupal\ictp_indico\Controller\IctpIndicoController::setSectionTopics();

    return [
      '#markup' => 'event nodes from ' . $from,
    ];
  }

   /**
   * Add node type 'event'
   *
   */
  public static function archive($item) {

    $nid = \Drupal::entityQuery('node')
    ->condition('field_indico_guid', $item['id'], '=')
    ->execute();

    if (count($nid)) {
      \Drupal\ictp_indico\Controller\IctpIndicoController::updateNode(reset($nid), $item);
    } else {
      \Drupal\ictp_indico\Controller\IctpIndicoController::addNode($item);
    }
  }

  /**
   * @param object $item Item that we get value from Indico
   *
   * @return all used values from indico
   */
  public static function getSource($item) {
    $keywords = !empty($item['keywords']) ? $item['keywords'] : [];
    $all_value = $item['id'];
    $all_value .= $item['title'];
    $all_value .= $item['description'];
    $all_value .= implode(' ', $keywords);
    $all_value .= $item['startDate']['date'];
    $all_value .= $item['startDate']['time'];
    $all_value .= $item['endDate']['date'];
    $all_value .= $item['endDate']['time'];
    $all_value .= $item['contactInfo'];
    $all_value .= $item['location'];
    $all_value .= $item['room'];
    return $all_value;
  }

  /**
   *
   * Set keywords imported
   */
  public static function setSectionKeywords() {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);
    $config = \Drupal::service('config.factory')
      ->getEditable('ictp_indico.settings');
    foreach($terms as $term) {
      if($term->get('field_indico_keywords')->value){
        $section = $term->get('field_section_acronym')->value;
        $item = $term->get('field_indico_keywords')->value;
        
        $config->set($section.'.keywords_imported', hash('sha256', $item));
        $config->save();
      }
    }
  }

  /**
   *
   * Set keywords imported
   */
  public static function setSectionTopics() {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);
    $config = \Drupal::service('config.factory')
      ->getEditable('ictp_indico.settings');
    foreach($terms as $term) {
      if($term->get('field_indico_topic')->value){
        $section = $term->get('field_section_acronym')->value;
        $item = $term->get('field_indico_topic')->value;
        
        $config->set($section.'.topics_imported', hash('sha256', $item));
        $config->save();
      }
    }
  }

  /**
   *
   * @return true/flase
   */
  public static function getKeywordsDiff() {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);
    $section_keyword = [];
    $section_keyword_imported = [];
    foreach($terms as $term) {
      if($term->get('field_indico_keywords')->value){
        $section = $term->get('field_section_acronym')->value;
        array_push($section_keyword, \Drupal::config('ictp_indico.settings')->get($section.'.keywords'));
        array_push($section_keyword_imported, \Drupal::config('ictp_indico.settings')->get($section.'.keywords_imported'));
      }
    }

    $result = array_diff($section_keyword, $section_keyword_imported);
    $value = count($result);

    return $value;
  }

  /**
   *
   * @return true/flase
   */
  public static function getTopicsDiff() {
    $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadTree('section', 0, 1, TRUE);
    $section_keyword = [];
    $section_keyword_imported = [];
    foreach($terms as $term) {
      if($term->get('field_indico_topic')->value){
        $section = $term->get('field_section_acronym')->value;
        array_push($section_keyword, \Drupal::config('ictp_indico.settings')->get($section.'.topics'));
        array_push($section_keyword_imported, \Drupal::config('ictp_indico.settings')->get($section.'.topics_imported'));
      }
    }

    $result = array_diff($section_keyword, $section_keyword_imported);
    $value = count($result);

    return $value;
  }

  /**
   * @param string $nid id node
   *
   * @param object $item Item that we get value from Indico
   *
   * @return all used values from indico
   */
  public static function updateNode($nid, $item) {
    $hash = hash('sha256', \Drupal\ictp_indico\Controller\IctpIndicoController::getSource($item));
    $node = Node::load($nid);
    $node_hash = $node->field_event_hash->value;
    $keywords_diff = \Drupal\ictp_indico\Controller\IctpIndicoController::getKeywordsDiff();
    $topics_diff = \Drupal\ictp_indico\Controller\IctpIndicoController::getTopicsDiff();
    if ($node_hash != $hash || $keywords_diff || $topics_diff) {
      \Drupal\ictp_indico\Controller\IctpIndicoController::setNode($node, $item, 'updated');
    }
  }

  /**
   * Create node content
   * @param object $item Item that we get value from Indico
   */
  public static function addNode(&$item) {
    $node = Node::create([
      'type' => 'event',
      'uid' => "1",
    ]);
    \Drupal\ictp_indico\Controller\IctpIndicoController::setNode($node, $item, 'added');
  }

  /**
   * @param object $node drupal node
   *
   * @param object $item Item that we get value from Indico
   *
   * @param string $operation he operation that is to be performed on $entity. Usually one of: added, updated.
   *
   * @return all used values from indico
   */
  public static function setNode($node, $item, $operation) {
    $keywords = !empty($item['keywords']) ? $item['keywords'] : [];
    // truncate long title
    $node->title = substr($item['title'], 0, 255);
    $node->body->value = $item['description'];
    $node->body->format = 'full_html';
    $node->field_ref_section = [Indico::getSection($keywords)];
    $node->field_ref_sections = Indico::getSections($keywords);
    $node->field_topic = [Indico::getTopic($keywords)];
    $node->field_ref_event_location = [Indico::getLocation($item['location'], $keywords)];
    $node->field_event_location_description = $item['location'];
    $node->field_event_room = $item['room'];
    $node->field_event_hash = hash('sha256', \Drupal\ictp_indico\Controller\IctpIndicoController::getSource($item));
    $node->field_indico_guid = $item['id'];
    $node->field_start_date = $item['startDate']['date'];
    $node->field_end_date = $item['endDate']['date'];
    $node->field_start_time = Indico::timeConvert($item['startDate']['time']);
    $node->field_end_time = Indico::timeConvert($item['endDate']['time']);
    $node->field_event_deadline = Indico::getDeadline($item['contactInfo']);
    $node->field_hosted_activities = Indico::getHostedActivities($item['categoryId']);
    $node->field_ictp_activities_outside = Indico::getActivitiesOutside($item['categoryId']);
    $node->field_event_type = Indico::getEventType($keywords, $item['title'], $item['categoryId']);
    $node->save();

    \Drupal::logger('ictp_indico')->notice('event: %operation %title.',
      [
        '%operation' => $operation,
        '%title' => $item['title']
      ]
    );

    return $node;
  }

  /**
   *
   * Remove all node type event
   *
   */
  function delete() {
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $storage_handler->loadByProperties(['type' => 'event']);
    $storage_handler->delete($entities);

    \Drupal::logger('ictp_indico')->notice('all events deleted');

    return [
      '#markup' => $this->t('Events deleted from Drupal.'),
    ];
  }


  /**
   *
   * Get table code of collaboration's logos from INDICO
   *
   */
  function collaborationlogos(){
      $baseurl = "https://indico.ictp.it/css/ICTP/images/sponsor-logo/";
      $data = file_get_contents('https://indico.ictp.it/admin/plugins/type/ictp_addons/sponsor_management/export_dictionary');
      $json = json_decode($data,true);

      $header = [
        'col1' => 'CODE',
        'col2' => 'Name',
        'col3' => 'Country',
        'col4' => ' ',
      ];
      $rows = array();

      foreach ( $json['info'] as $row ) {  
        $logo = Markup::create('<img src="'.$baseurl.$row['logo'].'" style="height:50px">');
        $code = Markup::create('<b>'.$row['name'].'</b>');
        $rows[] = [$code,$row['title'], $row['country'],$logo ];

      }

      

      return [
        '#type' => 'table',
        '#sticky' => true,
        '#header' => $header,
        '#rows' => $rows,
      ];
  }
  
}
