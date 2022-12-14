<?php

namespace Drupal\ictp_news_import\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Provides a form for add a event entity.
 *
 * @ingroup ictp_news_import
 */
class IctpNewsFormImport extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'ictp_news_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<p>Carica il file XML per importare le News</p>';


    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => 'XML file',
      '#upload_location' => 'private://news',
      '#required' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['xml'],
      ],
    ];

    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => 'Procedi',
      ),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('file')[0];
    $file = File::load($fid);
    $file_name = $file->getFilename();
    $uri = $file->getFileUri();
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
    $file_path = $stream_wrapper_manager->realpath();
    // Converts the XML content to a SimpleXML object.
    $xmlObject = simplexml_load_file($file_path, 'SimpleXMLElement', LIBXML_NOCDATA);
    $archivio = $xmlObject->FirstLevelPage->MediaPage->NewsPage->NewsArchivePage;
    $jsonArchivio = json_encode($archivio);
    $arrayArchivio = json_decode($jsonArchivio,TRUE);
    $latest = $xmlObject->FirstLevelPage->MediaPage->NewsPage;
    $jsonLatest = json_encode($latest);
    $arrayLatest = json_decode($jsonLatest,TRUE);
    $archivioLatest = array_merge($arrayLatest['YearFolder'], $arrayArchivio['YearFolder']);

    $news = array_map(function($item) {
      return $item['MonthFolder'];
    }, $archivioLatest);

    $import_tmp = [];
    $import = [];
    foreach($news as $values) {
      foreach($values as $value) {
        if(isset($value['News']['pPageTitle'])){
          array_push($import_tmp, $value);
        } else {
          foreach($value['News'] as $newsItem) {
            array_push($import_tmp, $newsItem);
          }
        }
      }
    }

    // sanitize
    foreach($import_tmp as $tmp) {

      if(!isset($tmp['pPageTitle'])){
        array_push($import, $tmp['News']);
      } else {
        array_push($import, $tmp);
      }
    }

    $batch = [
      'title' => t('Import news from Umbraco'),
      'operations' => [],
      'init_message' => t('Import news from Umbraco is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The process has encountered an error.'),
    ];


    foreach ($import as $xml_item) {
      $batch['operations'][] = [
        ['\Drupal\ictp_news_import\Controller\IctpNewsController', 'add'],
        [$xml_item]
      ];
    }

    batch_set($batch);
    $form_state->setRebuild(TRUE);
  }
}
