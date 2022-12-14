<?php
/**
 * by Alesssandro Fontana
 */

namespace Drupal\ictp_members;

use Drupal\taxonomy\Entity\Term;

class Members {



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
    for ($i=0; $i<count($works['group']); $i++)
    {
        $wCodes[] = $works['group'][$i]['work-summary'][0]['put-code'];
    }
    return $wCodes;
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
        $details['title'] = $work['title']['title']['value'];                               // Title
        $details['field_publication_year'] = $work['publication-date']['year']['value'];    // Year
        $details['field_single_url'] = $work['url']['value'];                               // DOI 
        $details['field_publication_type'] = $work['type'];                                 // Type
        $details['field_publication_authors'] = Publications::getWorkContributors($work);   // Authors

        // DOI potrebbe essere un url 'non-doi' ma solo un link esterno
        // TODO: (fare funzione per ciclare negli external ids)
    }else{
        // PER DEBUG, da togliere
        // $details['field_publication_type'] = $work['type'];
    }

    return $details ;

  }

  /**
   * @param array work data
   *
   * @return string authors
   *
   */
  public static function getWorkContributors($workDetail) {
        $authors = array();
        if(array_key_exists("contributors",$workDetail)){
            for ($i=0; $i<count($workDetail['contributors']['contributor']); $i++)
            {
                $authors[] = $workDetail['contributors']['contributor'][$i]['credit-name']['value'];
            }
        }
        return implode(" | ",$authors);
  }
  


}

