<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/webnicola
 */

namespace Drupal\ictp_news_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\ictp_news_import\News;

class IctpNewsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ictp_news_import';
  }

  /**
   * {@inheritdoc}
   */
  function home() {
    $markup = '<div>';
    $markup .= '<h2>API per importare le news da umbraco</h2>';
    $markup .= '<p><a href="/admin/news/import" class="button button-action button--primary button--small">Add news from Umbraco</a></p>';
    $markup .= '<p><a href="/admin/news/delete" class="button button-action button--danger button--small">Delete news from Drupal</a></p>';
    $markup .= '</div>';
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * Add node type 'event'
   *
   */
  public static function add($item) {
    // to be reviewed
    $nid = \Drupal::entityQuery('node')
      ->condition('field_id_import', $item['@attributes']['id'], '=')
      ->execute();

    if (count($nid)) {
      \Drupal\ictp_news_import\Controller\IctpNewsController::updateNode(reset($nid), $item);
    } else {
      \Drupal\ictp_news_import\Controller\IctpNewsController::addNode($item);
    }


    return [
      '#markup' => 'news nodes',
    ];
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
    return $all_value;
  }

  /**
   * @param string $nid id node
   *
   * @param object $item Item that we get value from Indico
   *
   * @return all used values from indico
   */
  public static function updateNode($nid, $item) {
    $hash = hash('sha256', \Drupal\ictp_news_import\Controller\IctpNewsController::getSource($item));
    $node = Node::load($nid);
    $node_hash = $node->field_event_hash->value;

    if ($node_hash != $hash) {
      \Drupal\ictp_news_import\Controller\IctpNewsController::setNode($node, $item, 'updated');
    } else {
      $node->save();
    }
  }

  /**
   * Create node content
   * @param object $item Item that we get value from Indico
   */
  public static function addNode(&$item) {
    $node = Node::create([
      'type' => 'article',
      'uid' => "1",
    ]);
    \Drupal\ictp_news_import\Controller\IctpNewsController::setNode($node, $item, 'added');
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
    // ex url umbraco per redirect
    $date = trim($item['pDate'], 'T00:00:00');
    $date_array = explode('-', $date);
    $anno = $date_array[0];
    $mese = ltrim($date_array[1], '0');
    $news_exurl = 'https://www.ictp.it/about-ictp/media-centre/news/' . $anno . '/' . $mese . '/' . $item['@attributes']['urlName'] . '.aspx';

    $node->title = $item['pPageTitle'];
    $node->field_page_subtitle = $item['pEyelet'];
    $node->body->value = News::saveImagebyBody($item['pContent']);
    $node->body->format = 'full_html';
    $node->field_id_import = $item['@attributes']['id'];
    $node->created = strtotime($date);
    $node->changed = strtotime(trim($item['@attributes']['updateDate'], 'T00:00:00'));
    $node->field_news_date = $date;
    $node->field_news_highlight = $item['pHighlight'];
    $node->field_url_news_umbraco = $news_exurl;
    $node->field_ref_sections = News::getSections($item['pResearchSectors']);
    if(is_string($item['pPageTags'])){
      $node->field_ref_tags = News::getTags($item['pPageTags']);
    }

    if(isset($item['pIsColloquia'])) {
      $node->field_news_colloquia = $item['pIsColloquia'];
    }
    \Drupal::logger('ictp_news_import')->notice('news: %id %title.',
      [
        '%id' => $item['@attributes']['id'],
        '%title' => $item['pPageTitle']
      ]
    );
    // Add image
    if(is_string($item['pContentImage'])) {
      $image_array = explode('/', $item['pContentImage']);
      $data_file = file_get_contents('https://www.ictp.it'.$item['pContentImage']);
      $file = \Drupal::service('file.repository')->writeData($data_file, 'public://'.$image_array[3], FileSystemInterface::EXISTS_REPLACE);
      $legend = $item['pContentImageLegend'] ? $item['pContentImageLegend'] : $item['pPageTitle'];

      // Main image
      $node->field_image = [
        'target_id' => $file->id(),
        'alt' => $legend,
        'title' => $legend ,
      ];
      // Thumbnail
      $node->field_thumbnail_image = [
        'target_id' => $file->id(),
        'alt' => $legend,
        'title' => $legend ,
      ];
    }
    $node->save();

    \Drupal::logger('ictp_news_import')->notice('news: %operation %title.',
      [
        '%operation' => $operation,
        '%title' => $item['pPageTitle']
      ]
    );

    return $node;
  }

  /**
   *
   * Remove all node type news
   *
   */
  function delete() {
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $storage_handler->loadByProperties(['type' => 'article']);
    $storage_handler->delete($entities);

    \Drupal::logger('ictp_news_import')->notice('all news deleted');

    return [
      '#markup' => $this->t('News deleted from Drupal.'),
    ];
  }
}
