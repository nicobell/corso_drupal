<?php

/**
 * @file
 * Contains \Drupal\ictp_indico\EventSubscriber\MyModuleRedirectSubscriber
 */
 
namespace Drupal\ictp_indico\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
 
class IctpIndicoRedirectSubscriber implements EventSubscriberInterface {
 
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
 
    if ($request->attributes->get('_route') !== 'entity.node.canonical') {
      return;
    }
 
    if ($request->attributes->get('node')->getType() !== 'event') {
      return;
    }

    $node = $request->attributes->get('node');
    $id_indico = $node->get('field_indico_guid')->getValue()[0]['value'];
    $response = new TrustedRedirectResponse("https://indico.ictp.it/event/$id_indico");
    $event->setResponse($response);
  }
 
}

