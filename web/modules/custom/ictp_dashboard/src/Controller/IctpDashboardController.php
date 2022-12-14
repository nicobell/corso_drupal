<?php
/**
 * @author Alesssandro Fontana
 * 
 */

namespace Drupal\ictp_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\ictp_dashboard\Dashboard;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Routing\TrustedRedirectResponse;


class IctpDashboardController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'ictp_dashboard';
  }

  /**
   * Dashboard home
   */
  function home($page) {

    $outHtml = '';
    $isValidPAge = array_key_exists($page, PAGES);
    $isDashboardHome = $page == 'home';

    if ($isDashboardHome)
    {
      $outHtml = '<div>';
      $outHtml .= '<a href="/" class="dashboard_btn">Go to website</a>';
      $outHtml .= '<a href="/admin/dashboard/news" class="dashboard_btn">News</a>';
      $outHtml .= '<a href="/admin/dashboard/pages" class="dashboard_btn">Pages</a>';
      $outHtml .= '<a href="/admin/dashboard/carousel" class="dashboard_btn">Carousel images</a>';
      $outHtml .= '<a href="/admin/dashboard/chart" class="dashboard_btn">Chart</a>';
      $outHtml .= '<a href="/admin/dashboard/collaborations" class="dashboard_btn">Collaborations</a>';
      $outHtml .= '<a href="/admin/dashboard/collaboration-groups" class="dashboard_btn">Collaboration groups</a>';
      $outHtml .= '<a href="/admin/dashboard/groups" class="dashboard_btn">Groups of people</a>';
      $outHtml .= '<a href="/admin/dashboard/external-people" class="dashboard_btn">External People</a>';
      $outHtml .= '<a href="/admin/dashboard/prizes" class="dashboard_btn">Prizes</a>';
      $outHtml .= '<a href="/admin/dashboard/winners" class="dashboard_btn">Winners</a>';
      $outHtml .= '<a href="/admin/dashboard/opportunities" class="dashboard_btn">Opportunities</a>';
      $outHtml .= '<a href="/admin/dashboard/research-area/2" class="dashboard_btn">Research Area</a>';
      $outHtml .= '<a href="/admin/dashboard/topics" class="dashboard_btn">Topics</a>';
      $outHtml .= '<a href="/admin/dashboard/projects" class="dashboard_btn">Topics & projects</a>';
      $outHtml .= '<a href="/admin/dashboard/publications" class="dashboard_btn">Publications</a>';
      $outHtml .= '<a href="/admin/dashboard/infographics" class="dashboard_btn">Infographics</a>';
      $outHtml .= '<a href="/admin/dashboard/rooms" class="dashboard_btn">Buildings & rooms</a>';
      $outHtml .= '<a href="/admin/dashboard/events" class="dashboard_btn">Events</a>';
      $outHtml .= '<a href="/admin/dashboard/openbids" class="dashboard_btn">Open bids</a>';
      $outHtml .= '<a href="/admin/dashboard/jobopportunities" class="dashboard_btn">Job opporotunities</a>';
      $outHtml .= '<a href="/user/logout" class="dashboard_btn">Log out</a>';
      $outHtml .= '</div>';
    }else if (!$isDashboardHome && $isValidPAge)
    {
      $this->changeTitlePage($page);
      $outHtml = \Drupal::service('renderer')->render(views_embed_view('dashboard',PAGES[$page]['blocks'][0]));
      /*
      MULTIPLE VIEWS per singola route, non viene renderizzato correttamente se ha filtri
      for ($i=0; $i<count(PAGES[$page]['blocks']);$i++){
        $outHtml .= \Drupal::service('renderer')->render(views_embed_view('dashboard',PAGES[$page]['blocks'][$i]));
      }
      */

    }else{
      $url = Url::fromRoute('ictp_dashboard.home');
      return new RedirectResponse($url->toString());
    }


    return [
      '#markup' => $outHtml,
      '#attached' => [
        'library' => [
          'ictp_dashboard/dashboard',
        ], 
      ],
    ];

  }

  function changeTitlePage($page)
  {
    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', PAGES[$page]['title']);
    }
  }

  function getRouteViews($page)
  {
    $resultHtml = '';
    for ($i=0; $i<count(PAGES[$page]['blocks']);$i++){
      $resultHtml .= \Drupal::service('renderer')->render(views_embed_view('dashboard',PAGES[$page]['blocks'][$i]));
    }
    return $resultHtml;
  }

  function personalprofile()
  {
    $request = \Drupal::request();
    $host = $request->getSchemeAndHttpHost(); // Home

    $userId = \Drupal::currentUser()->id();
    if (!$userId) return new TrustedRedirectResponse($host);

    // TODO: da procedura bisogna far editare il profilo anche ai
    // member che non hanno la personal page? Si, no? 
    // Chiedere chiarimenti, in caso aggiungere la condizione anche
    // relativa al campo booleano personal_page
    $member = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('type', 'member')
    ->condition('uid', $userId)
    ->execute();
    
    // Esiste un Member il cui autore sia utente loggato?
    if (!$member) return new TrustedRedirectResponse($host);

    // Pagina del profilo
    // $url = Url::fromRoute('entity.node.canonical', ['node' => reset($member)]);
    // return new RedirectResponse($url->toString());

    // Edit del nodo member
    // - resetto al primo nodo di tipo member con autore uguale ad user loggato (dovrebbe essere uno solo!!)
    $urlobject = Url::fromUri($host.'/node/'.reset($member).'/edit');
    $url = $urlobject->getUri();


    return new TrustedRedirectResponse($url);

  }

  function publicprofile()
  {
    $request = \Drupal::request();
    $host = $request->getSchemeAndHttpHost(); // Home

    $userId = \Drupal::currentUser()->id();
    if (!$userId) return new TrustedRedirectResponse($host);

    // TODO: da procedura bisogna far editare il profilo anche ai
    // member che non hanno la personal page? Si, no? 
    // Chiedere chiarimenti, in caso aggiungere la condizione anche
    // relativa al campo booleano personal_page
    $member = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('type', 'member')
    ->condition('uid', $userId)
    ->execute();
    
    // Esiste un Member il cui autore sia utente loggato?
    if (!$member) return new TrustedRedirectResponse($host);

    // Pagina del profilo
    $url = Url::fromRoute('entity.node.canonical', ['node' => reset($member)]);
    return new RedirectResponse($url->toString());

  }

  /**
   * Usato dalla pagina profile del member (edit) per forzare aggiornamento
   * delle proprie pubblicazioni su ORCID
   * (lancia update e aggiorna pubblicazioni solo se data dell'ultimo aggiornamento non coincide)
   */
  function updatemyorcid()
  {
    $request = \Drupal::request();
    $host = $request->getSchemeAndHttpHost(); // Home

    $userId = \Drupal::currentUser()->id();
    if (!$userId) return new TrustedRedirectResponse($host);

    // TODO: da procedura bisogna far editare il profilo anche ai
    // member che non hanno la personal page? Si, no? 
    // Chiedere chiarimenti, in caso aggiungere la condizione anche
    // relativa al campo booleano personal_page
    $member = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('type', 'member')
    ->condition('uid', $userId)
    ->execute();
    
    // Esiste un Member il cui autore sia utente loggato?
    if (!$member) return new TrustedRedirectResponse($host);

    $memberNode = Node::load(reset($member));
    $orcid = $memberNode->field_orcid->value;

    $c = new \Drupal\ictp_publications\Controller\IctpPublicationsController;
    $result = $c->add($orcid,null); // 'force-update' per forzare update sempre, altrimenti fa il check della data di ultima modifica orcid
    //dump($result);die();

    \Drupal::logger('ICTP member')->notice('Manual update ORCID: '.$memberNode->title->value);

    // Pagina del profilo
    $url = Url::fromRoute('entity.node.canonical', ['node' => reset($member)]);
    return new RedirectResponse($url->toString());


  }

   /**
   * Usato dalla pagina profile del member (edit) per forzare aggiornamento
   * delle proprie pubblicazioni su ORCID
   * (lancia update e aggiorna pubblicazioni solo se data dell'ultimo aggiornamento non coincide)
   */
  function updatemyarxiv()
  {
    $request = \Drupal::request();
    $host = $request->getSchemeAndHttpHost(); // Home

    $userId = \Drupal::currentUser()->id();
    if (!$userId) return new TrustedRedirectResponse($host);

    // TODO: da procedura bisogna far editare il profilo anche ai
    // member che non hanno la personal page? Si, no? 
    // Chiedere chiarimenti, in caso aggiungere la condizione anche
    // relativa al campo booleano personal_page
    $member = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('type', 'member')
    ->condition('uid', $userId)
    ->execute();
    
    // Esiste un Member il cui autore sia utente loggato?
    if (!$member) return new TrustedRedirectResponse($host);

    $memberNode = Node::load(reset($member));
    $orcid = $memberNode->field_orcid->value;

    $c = new \Drupal\ictp_publications\Controller\IctpPublicationsController;
    $result = $c->arxivAdd($orcid,null); // 'force-update' per forzare update sempre, altrimenti fa il check della data di ultima modifica orcid
    //dump($result);die();

    \Drupal::logger('ICTP member')->notice('Manual update ARXIV: '.$memberNode->title->value);

    // Pagina del profilo
    $url = Url::fromRoute('entity.node.canonical', ['node' => reset($member)]);
    return new RedirectResponse($url->toString());


  }

  
}

