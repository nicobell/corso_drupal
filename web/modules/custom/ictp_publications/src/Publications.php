<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/interfasesrl/ictp
 * 
 * Update by Alesssandro Fontana
 */

namespace Drupal\ictp_publications;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

class Publications {

  /**
   * @param string ORCID code
   *
   * @return array of works from arxiv
   *
   */
  public static function getArxivWorks($orcidCode) {
    $worksUrl = ARXIV_END_POINT.$orcidCode.".atom2";
    $response = file_get_contents($worksUrl);
    $response = trim(str_replace('"', "'", $response));
    $simpleXml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
    $lastModifiedDate = strtotime($simpleXml->updated);
    
    $result = array(
        'last-modified-date' => $lastModifiedDate,
        'works' => array()
    );

    foreach ($simpleXml->entry as $item) {
        array_push($result['works'], json_decode(json_encode((array)$item), TRUE));  
    }

    return $result;
  }

  /**
   * @param string ORCID code
   *
   * @return array of works code
   *
   */
  public static function getAllWorks($orcidCode) {
    $worksUrl = ORCID_END_POINT.$orcidCode."/works";

    // ORCID necessita header application/json perché di default ritorna xml
    // uso curl al posto di file_get_contents() che ritorna XML
    // TODO: randomizzare user-agent

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $worksUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',

    ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    
    $wCodes = array();
    $works =  json_decode($response, TRUE);
    $result = array('last-modified-date' => $works['last-modified-date']['value'], 'worksids' => array());
    for ($i=0; $i<count($works['group']); $i++)
    {
        $result['worksids'][] = $works['group'][$i]['work-summary'][0]['put-code'];
    }
    return $result;
  }

  /**
   * @param string ORCID code, work code
   *
   * @return array of work details (is of type WORKS_CATEGORY)
   *
   */
  public static function getWorkDetails($orcidCode, $workCode) {
    $workDetailsUrl = ORCID_END_POINT.$orcidCode."/work/".$workCode;

    // ORCID necessita header application/json perché di default ritorna xml
    // uso curl al posto di file_get_contents() che ritorna XML
    // TODO: randomizzare user-agent

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $workDetailsUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0',

    ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $details = array();
    $work =  json_decode($response, TRUE);

    if (in_array($work['type'],WORKS_CATEGORY)){
        $details['title'] = $work['title']['title']['value'];                                                                       // Title
        $details['field_publication_year'] = $work['publication-date'] != null ? $work['publication-date']['year']['value'] : '';   // Year
        $details['field_single_url'] = $work['url'] != null ? $work['url']['value'] : Publications::lookingForDOI($work);           // DOI 
        $details['field_publication_type'] = $work['type'];                                                                         // Type
        $details['field_publication_authors'] = Publications::getWorkContributors($work);                                           // Authors
        // $details['field_last_modified'] = $work['last-modified-date']['value'];             // Last modified date
      
        // DOI potrebbe essere un url 'non-doi' ma solo un link esterno

    }else{
        // PER DEBUG, da togliere
        // $details['field_publication_type'] = $work['type'];
    }

    return $details ;

  }

  /**
   * @param array work data
   *
   * @return string url or null
   * 
   * NOTE
   * - public api di ORCID sul dettaglio del singolo WORK può avere o non avere nella root del
   * json di risposta una chiave "url" es.: https://pub.orcid.org/v3.0/0000-0002-0578-0830/work/46464676 
   * - negli "external-ids" (può esistere o non esistere anche la chiave stessa) ci sono gli external id
   * associati al dettaglio del lavoro, da cui api orcid può generare il valore "url" presente in root
   * - se esiste un external id di tipo DOI tra la lista degi external ids, 
   * all'interno esiste il codice DOI, ma non sempre compare anche l'external-id-normalized, ne l'external-id-url
   * - in update/inserimento della publicazione, se il campo url è nullo o non esistente, questa funzione prova a cercare
   * un doi e generare un url
   * 
   * 
   * 
   * ESEMPIO
   * external-ids": {  
   *    "external-id": [
   *         {
   *           "external-id-type": "doi",
   *           "external-id-value": "10.1109/MCOM.2017.1600663",
   *           "external-id-normalized": { 
   *                "value": "10.1109/mcom.2017.1600663"
   *                 "transient": true
   *            },
   *            "external-id-normalized-error": null,
   *            "external-id-url": null,
   *            "external-id-relationship": "self"
   *          },
   * 
   * 
   * 
   */
  public static function lookingForDOI($work){
     // Vedi struttura e note qui sopra
     //dump($work['title']['title']['value']);
     //dump($work['external-ids']);
     

     // External-ids, root key? contents?
     $external_ids = array();
     $has_external_ids = array_key_exists('external-ids', $work);
     if ($has_external_ids) $external_ids = $work['external-ids'];

     // Array degli ids
     $ids = array();
     $has_ids = array_key_exists('external-id',$external_ids);
     if ($has_ids) $ids = $external_ids['external-id'];


     // Controllato ci sia la chiave root e che non sia valorizzata a null
     // Controllato ci sia la chiave degli ids dentro la root e che non sia valorizzata a null
     // Ciclo dentro l'array degli ids e cerco un id DOI

     if($ids){
      for($i=0;$i<count($ids);$i++){
        $type = $ids[$i]['external-id-type'];
        if (strtolower($type) == 'doi') return ('https://doi.org/'.strtolower($ids[$i]['external-id-value']));
      }
     }

     return null;

  }

  /**
   * @param array work data
   *
   * @return string authors
   *
   */
  public static function getWorkContributors($workDetail) {
        $authors = array();
        if(array_key_exists("contributors",$workDetail) && $workDetail['contributors'] != null){
            for ($i=0; $i<count($workDetail['contributors']['contributor']); $i++)
            {
                $authors[] = $workDetail['contributors']['contributor'][$i]['credit-name']['value'];
            }
        }
        
        // Tronco lista autori (per front e anche per lunghezza campo in drupal)
        $outputString = implode(" | ",$authors);
        if (strlen($outputString) > 240)
        {
            $outputString = substr($outputString,0,240);
            $limittop = strpos($outputString," | ",215);
            $outputString = substr($outputString,0,$limittop);
            $outputString .= " ...";
        }

        return $outputString;
  }

  /**
   *
   * @return orcid's nodes
   *
   */
  public static function getOrcidUser($orcidID = null){
    if ($orcidID){
      // Sto aggiungendo/aggiornando un orcidID singolo?
      $nids = \Drupal::entityQuery('node')
            ->condition('status', 1)
            ->condition('type', 'member')
            ->condition('field_orcid', $orcidID)
            ->execute();
    }else{
      $nids = \Drupal::entityQuery('node')
            ->condition('status', 1)
            ->condition('type', 'member')
            ->condition('field_orcid', '', '<>')
            ->execute();
    }

    $members = Node::loadMultiple($nids);

    return $members;
  }

  /**
   * Check node by arxiv id
   * @param string arxiv_id
   * 
   * @return false or nid
   */
  public static function publicationNid_byArxiv_id($arxiv_id) {
    $publication = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', 'publication')
          ->condition('field_arxiv_id', $arxiv_id)
          ->execute();
    $nid = reset($publication);
    return $nid;
  }
  


}

