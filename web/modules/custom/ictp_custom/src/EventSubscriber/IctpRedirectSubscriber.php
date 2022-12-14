<?php

/**
 * @file
 * Contains \Drupal\ictp_custom\EventSubscriber\IctpRedirectSubscriber
 */

namespace Drupal\ictp_custom\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\redirect\Entity\Redirect;

class IctpRedirectSubscriber implements EventSubscriberInterface {

  public function __construct() {
    $this->account = \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This announces which events you want to subscribe to.
    // We only need the request event for this example.  Pass
    // this an array of method names
    return([
      KernelEvents::REQUEST => [
        ['redirectEventTypeNode'],
        ['checkAuthStatus', 100]
      ]
    ]);
  }

  /**
   * Redirect requests for my_content_type node detail pages to node/123.
   *
   * @param GetResponseEvent $event
   * @return void
   */
  public function redirectEventTypeNode(GetResponseEvent $event) {
    $request = $event->getRequest();
    $aliasManager = \Drupal::service('path_alias.manager');

    if ($request->attributes->get('_route') === 'entity.node.canonical') {
      if ($request->attributes->get('node')->getType() == 'topic') {
        $node = $request->attributes->get('node');

        $section_tid = $node->get('field_ref_section')->getValue()[0]['target_id'];
        $ra_alias = $aliasManager->getAliasByPath('/node/'.$node->id());
        $ancora = ltrim($ra_alias, $ra_alias[0]);
        $ra_page_alias = $aliasManager->getAliasByPath('/taxonomy/term/'.$section_tid).'/research-areas#'.$ancora;


        $response = new TrustedRedirectResponse($ra_page_alias);
        $event->setResponse($response);

      } else if ($request->attributes->get('node')->getType() == 'project') {
        $node = $request->attributes->get('node');
        $section_tid = $node->get('field_ref_section')->getValue()[0]['target_id'];
        $resarch_area = $node->get('field_ref_topic')->getValue()[0]['target_id'];

        $ra_alias = $aliasManager->getAliasByPath('/node/'.$resarch_area);
        $ancora = ltrim($ra_alias, $ra_alias[0]);

        $ra_page_alias = $aliasManager->getAliasByPath('/taxonomy/term/'.$section_tid).'/research-areas#'.$ancora;

        $response = new TrustedRedirectResponse($ra_page_alias);
        $event->setResponse($response);
      } else if ($request->attributes->get('node')->getType() == 'publication') {
        $node = $request->attributes->get('node');
        $link = $node->get('field_single_url')->getValue()[0]['uri'];

        $response = new TrustedRedirectResponse($link);
        $event->setResponse($response);
      }
    } else if ($request->attributes->get('_route') === 'system.404') {
      $uri_404 = $request->getRequestUri();
      if(strpos($uri_404, 'prizes-awards')) {
        $uri_noaspx = str_replace('.aspx', '', $uri_404);
        $uri_from = '';
        if(strpos($uri_404, 'the-dirac-medal')) {
          $uri_from = str_replace('/about-ictp/prizes-awards/the-dirac-medal/the-medallists', '/home', $uri_noaspx);
        } else if(strpos($uri_404, 'the-ictp-prize')) {
          $uri_from = str_replace('/about-ictp/prizes-awards/the-ictp-prize/the-prize-winners', '/home', $uri_noaspx);
        } else if(strpos($uri_404, 'icoictp-gallieno-denardo-award')) {
          $uri_from = str_replace('/about-ictp/prizes-awards/icoictp-gallieno-denardo-award/winners', '/home', $uri_noaspx);
        } else if(strpos($uri_404, 'the-dst-ictp-imu-ramanujan-prize')) {
          $uri_from = str_replace('/about-ictp/prizes-awards/the-dst-ictp-imu-ramanujan-prize/the-dst-ictp-imu-ramanujan-prize-winners', '/home', $uri_noaspx);
        }

        $redirects = \Drupal::service('redirect.repository')->findBySourcePath($uri_404);
        if(!count($redirects)){
          $uri_to = str_replace('/about-ictp/', 'about-ictp/', $uri_404);
          Redirect::create([
            'redirect_source' => $uri_to,
            'redirect_redirect' => 'internal:' . $uri_from,
            'language' => 'en',
            'status_code' => '301',
          ])->save();
        }
      }
    }

  }

 /**
   * Redirect requests after login.
   *
   * @param GetResponseEvent $event
   * @return void
   */
  public function checkAuthStatus(GetResponseEvent $event) {
    $current_path = \Drupal::service('path.current')->getPath();
    $route_name = Url::fromUserInput($current_path)->getRouteName();
    $user_roles = $this->account->getRoles();
    if (!$this->account->isAnonymous() && $route_name == 'entity.user.canonical') {
      if (in_array('content_editor', $user_roles) || in_array('administrator', $user_roles)) {
        $response = new RedirectResponse('/admin/dashboard');
        $event->setResponse($response);
      } else if (in_array('member', $user_roles)) {
        $response = new RedirectResponse('/admin/personal-page/profile');
        $event->setResponse($response);
      } else if (count($this->account->getRoles()) == 1 && in_array('authenticated', $user_roles)) {
        $session_manager = \Drupal::service('session_manager');
        $session_manager->delete(\Drupal::currentUser()->id());
        $response = new RedirectResponse('/access-denied');
        $event->setResponse($response);
      } else {
        return;
      }
    }
    return;
  }
}
