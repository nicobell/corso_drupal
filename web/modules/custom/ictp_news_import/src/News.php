<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/interfasesrl/ictp
 */

namespace Drupal\ictp_news_import;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\File\FileSystemInterface;
use \Drupal\Component\Utility\Html;

class News {

   /**
   * @param string $body
   *
   * @return the body string
   *
   */
  public static function saveImagebyBody($string) {
    $dom_body = Html::load($string);
    $xpath = new \DOMXPath($dom_body);
    $imgs = $xpath->query("//img");
    for ($i=0; $i < $imgs->length; $i++) {
        $img = $imgs->item($i);
        $src = $img->getAttribute("src");
        $image_array = explode('/', $src);
        $data_file = file_get_contents('https://www.ictp.it'.$src);
        $file_inline = \Drupal::service('file.repository')->writeData($data_file, 'public://inline-images/'.$image_array[3], FileSystemInterface::EXISTS_REPLACE);
        $img->setAttribute("src", "/sites/default/files/inline-images/". $image_array[3]);
    }
    $result = Html::serialize($dom_body);
    return $result;
  }

  /**
   * @param array $tags Tags that we get tags
   *
   * @return the array ids of the Drupal tags
   *
   */
  public static function getTags($tags) {
      $tids = [];
      $names = explode(',', $tags);
      foreach($names as $name){
        $term_id = \Drupal\ictp_news_import\News::getTidByName($name, 'tags');
        if ($term_id) {
          array_push($tids, $term_id);
        }
        else {
          $term = Term::create([
            'name' => $name,
            'vid' => 'tags',
          ])->save();
          array_push($tids, $term->tid);
        }
      }
      return $tids;
  }

  /**
   * @param array $keywords Keywords that we get section
   *
   * @return the id of the Drupal section
   *
   */
  public static function getSections($sectors_umbraco) {
    $terms = [];
    if(is_string($sectors_umbraco)){
      $sectors_umbraco_array = explode(',', $sectors_umbraco);
      // 'id_umbraco' => 'id_term_drupal'
      $sectors_umbraco_map = [
        '1149' => '2', // hecap
        '1258' => '3', // cms
        '1255' => '5', // math
        '1226' => '7', // esp
        '18178' => '6', // qls
        '25500' => '4', // sti
        '1283' => '4' , // sti, in umbraco AP
        '25625' => '4' , // sti, in umbraco Mlab
      ];

      foreach($sectors_umbraco_array as $sector_umbraco_array){
        array_push($terms, $sectors_umbraco_map[$sector_umbraco_array]);
      }
    }
    // add home section
    array_push($terms, '1');
    return $terms;
  }

  /**
   * Utility: find term by name and vid.
   * @param null $name
   *  Term name
   * @param null $vid
   *  Term vid
   * @return int
   *  Term id or 0 if none.
   */
  public static function getTidByName($name = NULL, $vid = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);

    return !empty($term) ? $term->id() : 0;
  }

}

