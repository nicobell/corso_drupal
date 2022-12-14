<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/webnicola
 * 
 * Update by Alesssandro Fontana
 */

namespace Drupal\ictp_publications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\ictp_publications\Publications;
use Drupal\Core\Render\Markup;
use Drupal\Component\Render\FormattableMarkup;

class IctpPublicationsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ictp_publications';
  }

  /**
   * {@inheritdoc}
   */
  function home() {
    $markup = '<div>';
    $markup .= '<h2>API per importare le pubblicazioni da ORCID</h2>';
    $markup .= '<p><a href="/admin/ictp_publications/add" class="button button-action button--primary button--small">Add publications from ORCID</a></p>';
    $markup .= '<p><a href="/admin/ictp_publications/arxiv/add" class="button button-action button--primary button--small">Add publications from arxiv</a></p>';
    $markup .= '</div>';
    return [
      '#markup' => $markup,
    ];
  }

  /**
   * Member list with ORCID for forcing manual update
   *
   */
  function memberList() {

    $members = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', 'member')
          ->condition('field_orcid', '', '<>')
          ->execute();
    
    $header = array('Member','OrcidID','');
    $rows = array();
    
    foreach ($members as $key => $value)
    {
        $memberNode = Node::load($value);
        $orcid = $memberNode->field_orcid->value;
        $link = new FormattableMarkup('<a href=":link">Force publications update</a>', [':link' => "/admin/ictp_publications/add/".$orcid."/force-update"]);
        $rows[] = array($memberNode->title->value,$orcid, $link);
        
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    
  }

 
  /**
   * Add node type 'publication'
   *
   */
  function add($orcidID,$forcemode) {

    // NUOVA PROCEDURA: pubblicazioni di tutti i member che hanno ORCID ID
    // (precedentemente: user con role member --> member CT cn autore == user e orcid != '' --> importa pubblicazioni)

    $htmlOUT = '';

    if ($orcidID){
      // Sto aggiungendo/aggiornando un orcidID singolo?
      $members = \Drupal::entityQuery('node')
            ->condition('status', 1)
            ->condition('type', 'member')
            ->condition('field_orcid', $orcidID)
            ->execute();
    }else{
      $members = \Drupal::entityQuery('node')
            ->condition('status', 1)
            ->condition('type', 'member')
            ->condition('field_orcid', '', '<>')
            ->execute();
    }
    $htmlOUT .= "Members with ORCID: ".count($members)."<br>";
    $totMemberPubUpdated = 0; // non le pub ma i member che necessitavano di aggiornare le proprie pubblicazioni
    $newPublications = 0;

    foreach ($members as $key => $value)
    {
        $memberNode = Node::load($value);
        $memberNid = $memberNode->id();
        $orcid = $memberNode->field_orcid->value;
        $lastModified = $memberNode->field_orcid_last_modified_date->value;

        // Force update
        if($forcemode == "force-update") $lastModified = '000';

        $works = Publications::getAllWorks($orcid);
        // Ottimizzazione se dovesse andare in timeout: indagare se si può passare id orcid e data modifica registrata nel member
        
        // Check last modified
        if ($lastModified != $works['last-modified-date']){ // 
          $totMemberPubUpdated += 1;
          $worksIds = $works['worksids'];
          
          for ($i=0; $i<count($worksIds);$i++)
          {
            $workDetails =  Publications::getWorkDetails($orcid,$worksIds[$i]);
            if($workDetails){
              // se non ci sono autori metto il member
              $workDetails['field_publication_authors'] = !$workDetails['field_publication_authors']
              ? $memberNode->getTitle()
              : $workDetails['field_publication_authors'];
              // Esiste una publicazione con titolo identico?
              $publicationNid = \Drupal\ictp_publications\Controller\IctpPublicationsController::publicationNid_byTitle($workDetails['title']);

              if ($publicationNid){
                // UPDATE
                \Drupal\ictp_publications\Controller\IctpPublicationsController::updateNode($publicationNid,$workDetails,$memberNode);
               
              }else{
                // ADD
                \Drupal\ictp_publications\Controller\IctpPublicationsController::addNode($workDetails,$memberNid);
                $newPublications += 1;
              }
              

            } 
          }

          // Aggiorno la data di modifica nel CT member
          $memberNode->field_orcid_last_modified_date = $works['last-modified-date'];
          $memberNode->save();
         
        }
        
    }

    $htmlOUT .= "Members need publication update: ".$totMemberPubUpdated."<br>";
    $htmlOUT .= "Total new publications added: ".$newPublications."<br>";


    return [
      '#markup' => $htmlOUT
    ];
  }

  /**
   * Add node type 'publication' from arxiv
   *
   */
  function arxivAdd($orcidID,$forcemode) {

    $members = Publications::getOrcidUser($orcidID);
    $markup = "Members with ORCID: ".count($members)."<br>";
    $totMemberPubUpdated = 0; // non le pub ma i member che necessitavano di aggiornare le proprie pubblicazioni
    $newPublications = 0;

    foreach ($members as $key => $member){
      $memberNid = $member->id();
      $orcid = $member->field_orcid->value;
      $lastModified = $member->field_arxiv_last_modified_date->value;
      // Force update
      if($forcemode == "force-update") $lastModified = '000';
      
      // get preprint from arxiv
      $works = Publications::getArxivWorks($orcid);

      if ($lastModified != $works['last-modified-date']){
        $totMemberPubUpdated += 1;
        if(count($works['works'])){
          
          foreach($works['works'] as $work){
            $published = explode('-', $work['published']);
            $authors = !$work['author']['name'] 
              ? $member->getTitle()
              : str_replace(',', ' |', $work['author']['name']);
            // preparo nodo
            $workDetails = array();
            $workDetails['title'] = $work['title'];
            $workDetails['field_publication_year'] = $published[0];
            $workDetails['field_single_url'] = $work['link'][0]['@attributes']['href'];
            $workDetails['field_publication_type'] = 'preprint';
            $workDetails['field_publication_authors'] = $authors;
            $workDetails['field_arxiv_id'] = $work['id'];
            // Esiste una publicazione con titolo identico?
            $publicationNid = Publications::publicationNid_byArxiv_id($work['id']);
            if ($publicationNid){
              // UPDATE
              \Drupal\ictp_publications\Controller\IctpPublicationsController::updateNode($publicationNid,$workDetails,$member);
            
            }else{
              // ADD
              \Drupal\ictp_publications\Controller\IctpPublicationsController::addNode($workDetails,$memberNid);
              $newPublications += 1;
            }
          }
        }
        // Aggiorno la data di modifica nel CT member
        $member->field_arxiv_last_modified_date = $works['last-modified-date'];
        $member->save();
      }
    
    }

    $markup .= "Members need publication update: ".$totMemberPubUpdated."<br>";
    $markup .= "Total new publications added: ".$newPublications."<br>";
    return [
      '#markup' => $markup,
    ];
  }


  /**
   * Create node content
   * @param array publication data
   */
  public static function addNode($workDetails,$memberNid) {
    $member =  Node::load($memberNid);
    $publication = Node::create([
      'type' => 'publication',
      'uid' => "1",
    ]);

    // Associo alla pubblicazione le stesse sezioni che ha il member
    $sections = $member->get('field_ref_sections')->referencedEntities();
    foreach ($sections as $key => $section) {
      $publication->field_ref_sections[] = $section->id();
    }

    foreach($workDetails as $key => $value){
      $publication->set($key,$value);
    }
    
    $publication->set('field_ref_members',[$memberNid]);

    $publication->save();
  }

  /**
   * Update node content
   * @param array publication data
   */
  public static function updateNode($nid,$workDetails,&$memberNode) {
    $publication = Node::load($nid);
    foreach($workDetails as $key => $value){
      $publication->set($key,$value);
    }
    
    // In update potrebbe esserci una pubblicazione condivisa, aggiungo ref member solo se non già settato
    $isMemberSet = \Drupal\ictp_publications\Controller\IctpPublicationsController::isSetPublicationRefMember($publication,$memberNode->id()); 
    if(!$isMemberSet) $publication->field_ref_members[] = $memberNode->id();

    // Associo alla pubblicazione le stesse sezioni che ha il member 
    // (in update due member che condividono una publication potrebbero avere sezioni differenti) 
    $sections = $memberNode->get('field_ref_sections')->referencedEntities();
    foreach ($sections as $key => $section) {
      $isSectionSet = \Drupal\ictp_publications\Controller\IctpPublicationsController::isSectionSet($publication,$section->id()); 
      if(!$isSectionSet) $publication->field_ref_sections[] = $section->id();
    }

    $publication->save();
    
  }

   /**
   * Check node by title
   * @param string publication title  (DOI non sempre presente, check sul titolo, per ora unico modo)
   * 
   * @return false or nid
   */
  public static function publicationNid_byTitle($title) {
    $publication = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', 'publication')
          ->condition('title', $title)
          ->execute();
    $nid = reset($publication);
    return $nid;
  }

  /**
   * Check reference is set on publication
   * @param objec publication 
   * @param nid member 
   * 
   * @return true or false
   */
  public static function isSetPublicationRefMember(&$publication, $nidMember) {
    $entities = $publication->get('field_ref_members')->referencedEntities();
    foreach ($entities as $key => $entity) {
      if ($entity->id() == $nidMember){
          return true;
      }
    }
    return false;
  }


  /**
   * Check if section is just set on publication
   * @param objec publication 
   * @param tid section 
   * 
   * @return true or false
   */
  public static function isSectionSet(&$publication, $tidSection) {
    $entities = $publication->get('field_ref_sections')->referencedEntities();
    foreach ($entities as $key => $entity) {
      if ($entity->id() == $tidSection){
          return true;
      }
    }
    return false;
  }




  /**
   *
   * Remove all node type event
   *
   */
  function delete() {
    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $storage_handler->loadByProperties(['type' => 'publication']);
    $storage_handler->delete($entities);

    \Drupal::logger('ictp_publications')->notice('all publications deleted');

    return [
      '#markup' => $this->t('Publications deleted from Drupal.'),
    ];
  }
}
