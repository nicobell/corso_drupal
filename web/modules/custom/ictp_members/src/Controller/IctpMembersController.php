<?php
/**
 *  by Alesssandro Fontana
 */

namespace Drupal\ictp_members\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\ictp_members\Members;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class IctpMembersController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ictp_members';
  }

  /**
   * {@inheritdoc}
   */
  function home() {
    $markup = '<div>';
    $markup .= '<h2>API per importare i members da ictp.it/phonebook</h2>';
    $markup .= '<p><a href="/admin/ictp_members/add" class="button button-action button--primary button--small">Add members from ICTP</a></p>';
    $markup .= '</div>';
    return [
      '#markup' => $markup,
    ];
  }
  /**
   * Add node type 'publication'
   *
   */
  function add() {

      
      $url = WITH_PERSONS; 
  
      $curl = curl_init();
  
      curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
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

      $allPersons =  json_decode($response, TRUE);

      
      
      /**
       * Filter only person to be imported in drupal (by id / main_sector_id)
       * 
       * (Struttura json WITH_PERSONS)
       * Facilities
       * -- id
       * -- name
       * -- staffs
       *     -- id
       *     -- main_sector_id (= facilities.id)
       * 
       */
      $persons = array();
      $addUserResult = 0;
      $addMemberResult = 0;
      $updateMemberResult = 0;
      $totFound = 0;
   
      // $allpersons grouped by facilities
      for ($i=count($allPersons);$i>=0;$i--)
      {
        if (array_key_exists($allPersons[$i]['id'], SECTIONS_TO_IMPORT)) {
          $persons[$allPersons[$i]['id']] = array_values($allPersons[$i]['staffs']);
        }
      }

      foreach($persons as $section => $staff){
        \Drupal::logger('ictp_members')->notice('Phonebook Sector: '.$section);
        $totFound += count($staff);
        for ($i=0; $i<count($staff);$i++){
          $addUserResult = \Drupal\ictp_members\Controller\IctpMembersController::addUser($staff[$i]) ? $addUserResult + 1 : $addUserResult;
          if (!$staff[$i]["is_deleted"] && !$staff[$i]["is_short_term_visitor"])
          {
            // Aggiungo solo se member phonebook non è stato cancellato e se non è un short visitor
            \Drupal\ictp_members\Controller\IctpMembersController::addMember($staff[$i],$addMemberResult,$updateMemberResult);
          }
          
        
        }
      }

      return [
        '#markup' =>  t('Tot persons: '.$totFound.' | New users: '.$addUserResult. ' | New members: '. $addMemberResult. ' | Updated members: '. $updateMemberResult)
      ];

  }


  /**
   * Get phonebook member detail
   * @param id value of member 
   * @return array values
   */
  public static function getPhonebookMemberDetail($id) {

    $url = PERSON_DETAIL.$id; 
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
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

    $detail =  json_decode($response, TRUE);
    return $detail;
  }



  /**
   * Create user
   * @param array values of member from phonebook
   */
  public static function addUser($memberData) {
    // Get user data, from [ad_username]
    $username = $memberData['ad_username'] != null ? trim(str_replace(STRIP_AD,"",$memberData['ad_username'])) : '';
    $email = $memberData['ad_username'] != null ? trim($memberData['email']) : '';
    $personalPage = array_key_exists($memberData['main_group_id'], ROLES_FOR_PERSONAL_PAGE) ? true : false;
    $validAD = $username != '' ? true : false;

    $isNew = false;
    if($validAD)
    {
      $member = \Drupal::entityQuery('user')
      ->condition('name', $username)
      ->execute();
     $isNew = $member != null ? false : true;
    }
  
    if($validAD && $isNew && $personalPage)
    {
      $user = \Drupal\user\Entity\User::create();
      $user->setPassword($username);
      $user->enforceIsNew();
      $user->setEmail($email);
      $user->setUsername($username);
      $user->addRole('member');
      $user->activate();
      $user->save();
      \Drupal::logger('ictp_members')->notice('Add user: '.$username);



    }else{
      // VERBOSE DEBUG
      /*
      \Drupal::logger('ictp_members')->notice('User not added: [AD: %username] [NEW: %isNew] [P_PAGE: %personalPage].',
        [
          '%username' => $username,
          '%isNew' => $isNew == 1 ? 'true' : 'false',
          '%personalPage' => $personalPage == 1 ? 'true' : 'false'
        ]
      );
      */
    }

    return ($validAD && $isNew && $personalPage);

  }
  
   /**
   * Add/Update member CT
   * @param array values of member from phonebook
   */
  public static function addMember($memberData,&$addMemberResult,&$updateMemberResult) {

    $nid = \Drupal::entityQuery('node')
    ->condition('type','member')
    ->condition('field_sync_id',$memberData['id'], '=')
    ->execute();

    
    if (count($nid)) {
      \Drupal\ictp_members\Controller\IctpMembersController::updateNode(reset($nid), $memberData,$addMemberResult,$updateMemberResult);
    } else {
      \Drupal\ictp_members\Controller\IctpMembersController::addNode($memberData,$addMemberResult,$updateMemberResult);
    }
    
  }


  /**
   * @param string $nid id node
   *
   * @param array member detail
   *
   */
  public static function updateNode($nid, $memberData,&$addMemberResult, &$updateMemberResult) {
    $hash = hash('sha256', \Drupal\ictp_members\Controller\IctpMembersController::getSource($memberData));
    $node = Node::load($nid);
    $node_hash = $node->field_hash->value;

    if ($node_hash != $hash) {
      \Drupal\ictp_members\Controller\IctpMembersController::setNode($node, $memberData, 'updated',$addMemberResult,$updateMemberResult);
    }
  }

  /**
   * @param object $node drupal node
   *
   * @param array member data
   *
   * @param string $operation he operation that is to be performed on $entity. Usually one of: added, updated.
   *
   */
  public static function setNode($node, $memberData, $operation, &$addMemberResult, &$updateMemberResult) {
    $hash = hash('sha256', \Drupal\ictp_members\Controller\IctpMembersController::getSource($memberData));
    $phonbookMemberDetail = \Drupal\ictp_members\Controller\IctpMembersController::getPhonebookMemberDetail($memberData['id']);

    $main_group_id = isset($memberData['main_group_id']) ? $memberData['main_group_id'] : null; // id del gruppo dalla lista degli utenti, può essere null
    $picture_id = $memberData['picture_id']; // Null se non c'è imagine del profilo
    /**
     * NOTA: 
     * - se nella lista degli utenti divisi per settore, il picture_id è valorizzato, group name e id sono dentro al picture
     * - se non è presente l'immagine del profilo dalla lista utenti abbiamo solo id del gruppo ma non il nome (per associare alla tassonmia)
     * - nome del gruppo (es.: Head of Section) trovato solo dentro picture
     * - nel caso non ci sia immagine del profilo, provo ad associare con lista statica id<->nome estratta da phonebook
     * 
     */

    if ($picture_id){
      $member_role = isset($phonbookMemberDetail['picture']['staffs'][0]['main_group']['name']) ? $phonbookMemberDetail['picture']['staffs'][0]['main_group']['name'] : null;
    }else{
      $member_role = $main_group_id ? CURRENT_ROLES_PHONEBOOK[$main_group_id] : null;
    }
    
    $member_picture_url = $picture_id ? BASE_PICTURE_URL.$phonbookMemberDetail['picture']['path'] : null;
    $title = $memberData['name'] . " " . $memberData['surname'];

    // Importo foto solo alla creazione del nodo member (procedura concordata)
    if($member_picture_url && $operation == 'added'){
      $file = file_save_data(file_get_contents($member_picture_url), 'public://images/members/'.basename($member_picture_url), \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
      $file->setPermanent();
      $file->status = 1;
      if (!empty($file)) { 
        $file_arr = array(
          "target_id" => $file->id(),
          "alt" => $title,
          "title" => $title
        );       
          
        $node->field_profile_picture = $file_arr;
      }
    }
   

    $member_role_tid = 0;
    if ($member_role)
    {
      // Ruolo su phonebook da tendina valore singolo, controllo se esiste altrimenti creo
      $member_role_tid = \Drupal\ictp_members\Controller\IctpMembersController:: getTidByName($member_role, 'member_role');
      if($member_role_tid == 0)
      {
        $term = [
          'name'     => $member_role,
          'vid'      => 'member_role',
        ];
        \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($term)->save();
        $member_role_tid = \Drupal\ictp_members\Controller\IctpMembersController:: getTidByName($member_role, 'member_role');
      }
    }
    
    
    // Main sector su phonebook da tendina valore singolo, mappo con le nostre sezioni se esiste
    if (array_key_exists($memberData['main_sector_id'], SECTIONS_TO_IMPORT)) {
      $node->field_ref_sections = [SECTIONS_TO_IMPORT[$memberData['main_sector_id']]];
    }

    
    $node->title = $title;
    $node->field_hash = $hash;
    foreach(FIELDS_MAP as $key => $value){
      $node->set($key,$memberData[$value]);
      
    }
    $node->field_personal_page = isset($memberData['main_group_id']) && array_key_exists($memberData['main_group_id'], ROLES_FOR_PERSONAL_PAGE) ? true : false;
    $node->field_role = $member_role_tid;

    // Ultimo check: salvo solo se ha un ruolo definito
    // TODO: risistemare con check all'inizio (cambiate cose in corsa su procedura di import)

    if ($member_role_tid != 0)
    {
      
      if ($operation == 'updated'){
        // Check if author node change - Patch da sistemare _____________________
        // - se da phonebook sono cambiati i parametri per permettere un nuovo user, ma il member era già esistente,
        //  viene reimpostato l'autore del ct member precedentemente creato
        // - allo stesso modo, se uno user non ha più diritto alla personal page, resetto author del CT member ad admin
        $username = $memberData['ad_username'] != null ? trim(str_replace(STRIP_AD,"",$memberData['ad_username'])) : '';
        $hasPersonalPage = $node->field_personal_page->value;
        $validAD = $username != '' ? true : false;
        $uid = 1;
        if ($hasPersonalPage && $validAD){
            $author = \Drupal::entityTypeManager()
            ->getStorage('user')
            ->loadByProperties([
            'name' => $username
          ]);
          $uid = $author ? reset($author)->id() : 1; //
          // Se qui $uid == 1 significa che non ha trovato lo user che si aspettava per associarlo al member
          if($uid == 1){
            \Drupal::logger('ictp_members')->notice('member: %operation %title.',
            [
              '%operation' => $operation,
              '%title' => $title. " Fallita associazione con user!"
            ]
          );
          }
          
        }else{
          // Reimposto admin author
          $uid = 1;
        }
        // Check if author node change - Patch da sistemare _____________________
        $node->uid = $uid;
      }
      
      $node->save();
      

      if ($operation == 'added') $addMemberResult += 1;
      if ($operation == 'updated') $updateMemberResult +=1;
      \Drupal::logger('ictp_members')->notice('member: %operation %title.',
        [
          '%operation' => $operation,
          '%title' => $title
        ]
      );
    }
    
    
    return $node;
    
  }
  
  /**
   * Create node content
   * @param array memberdata
   */
  public static function addNode($memberData,&$addMemberResult,&$updateMemberResult) {
    $username = $memberData['ad_username'] != null ? trim(str_replace(STRIP_AD,"",$memberData['ad_username'])) : '';
    $personalPage = array_key_exists($memberData['main_group_id'], ROLES_FOR_PERSONAL_PAGE) ? true : false;
    $validAD = $username != '' ? true : false;

    // Associo l'autore del member allo user (se ha un AD valido e se ha la personal page)
    $uid = 1;
    if($personalPage && $validAD){
      $author = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadByProperties([
        'name' => $username
      ]);
      $uid = $author ? reset($author)->id() : $uid;
    }

    // In creazione, aggiungo immagine se esiste in phonebook
    // Dopo la creazione del memeber la foto viene aggiornata solo dal back drupal
    // (procedura concordata)

    $node = Node::create([
      'type' => 'member',
      'field_sync_id' => $memberData['id'],
      'uid' => $uid
    ]);
    \Drupal\ictp_members\Controller\IctpMembersController::setNode($node, $memberData, 'added',$addMemberResult,$updateMemberResult);
  }

  /**
   * @param object Detail of member from phonebook ictp
   *
   * @return string used values from phonebook
   */
  public static function getSource($memberData) {
    $output = '';
    foreach(FIELDS_MAP as $key => $value){
      $output .= $memberData[$value];
    }
    // Aggiungo nell'hash anche il main_group_id (il nostro member role)
    $output .= $memberData['main_group_id'];
    // Aggiungo nell'hash anche il main_sector_id (mappato con le nostre sezioni)
    $output .= $memberData['main_sector_id'];
    

    return $output;
  }



  /**
   *
   * Remove all node type member
   *
   */
  function delete() {
    
    $result = \Drupal::entityQuery("node")
    ->condition('type','member')
    ->condition('field_sync_id','', '!=')
    ->execute();
  
    $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
    $entities = $storage_handler->loadMultiple($result);
    $storage_handler->delete($entities);

    \Drupal::logger('ictp_members')->notice('all imported members deleted');
    
    return [
      '#markup' => $this->t('Deleted imported members.'),
    ];

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

  public static function findKey($array, $keySearch)
  {
      foreach ($array as $key => $item) {
          if ($key == $keySearch) {
              return true;
          } elseif (is_array($item) && findKey($item, $keySearch)) {
              return true;
          }
      }
      return false;
  }

   /**
   * @param string ORCID code
   *
   * @return boolen (valid code)
   *
   */
  public static function checkOrcidCode($orcid) {
    // https://support.orcid.org/hc/en-us/articles/360006897674-Structure-of-the-ORCID-Identifier
    
    // Check se formato corretto
    $pattern = "/([0-9]{4}[-]){3}[0-9]{3}[0-9X]/";
    $check = preg_match($pattern, $orcid);

    if (!$check) return false;

    // Ultimo carattere è un checksum da 0-9 oppure X maiuscolo se dal checksum esce 10
    $baseDigits = str_replace("-","",$orcid);
    $total = 0; 
    for ($i = 0; $i < strlen($baseDigits)-1; $i++) { 
        $digit = $baseDigits[$i]; 
        $total = ($total + $digit) * 2; 
    } 
    $remainder = $total % 11; 
    $result = (12 - $remainder) % 11; 
    $result = $result == 10 ? "X" : $result;
    $lastchar = $baseDigits[strlen($baseDigits)-1];

    if($lastchar != $result) return false;

    return true;
  }

  /**
   * @param orcid id
   * @return array of profile person associated to orcid ID
   * 
   */
  public static function getProfileOrcid($orcidCode)
  {
    $profileUrl = ORCID_END_POINT.$orcidCode."/person";

    // ORCID necessita header application/json perché di default ritorna xml
    // uso curl al posto di file_get_contents() che ritorna XML
    // TODO: randomizzare user-agent

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $profileUrl,
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

    $profile =  json_decode($response, TRUE);
    return $profile;

  }


  function debugdata() {

    /*
    $url = WITH_PERSONS; 

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
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
    $allPersons =  json_decode($response, TRUE);

    $detail = \Drupal\ictp_members\Controller\IctpMembersController::getPhonebookMemberDetail(2701);
    dump($detail);
    */

    /*
    $tid = \Drupal\ictp_members\Controller\IctpMembersController:: getTidByName('STI Associates', 'member_role');
    if($tid == 0)
    {
      $term = [
        'name'     => 'STI Associates',
        'vid'      => 'member_role',
      ];
      \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($term)->save();
      $tid = \Drupal\ictp_members\Controller\IctpMembersController:: getTidByName('STI Associates', 'member_role');
    }

    return [
      '#markup' =>  $tid // '<pre>'.print_r($detail,1).'</pre><hr>'.'<pre>'.print_r($allPersons,1).'</pre>'
    ];
    */


    // TEST ORCID
    // Ultimo carattere è un checksum da 0-9 oppure X maiuscolo se dal checksum esce 10
    $baseDigits = '0000000294853091';
    $total = 0; 
    for ($i = 0; $i < strlen($baseDigits)-1; $i++) { 
        $digit = $baseDigits[$i]; 
        $total = ($total + $digit) * 2; 
    } 
    $remainder = $total % 11; 
    $result = (12 - $remainder) % 11; 
    $result = $result == 10 ? "X" : $result;
    $lastchar = $baseDigits[strlen($baseDigits)-1];
    $check = $lastchar == $result ? "check passed" : "wrong code";
    return [
      '#markup' =>  $lastchar. " --> ".$result. " = " . $check
    ];

  }

 



}




