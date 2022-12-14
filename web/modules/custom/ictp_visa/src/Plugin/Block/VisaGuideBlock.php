<?php
/**
 * @author Nicola Dimatteo <hello@nicoladimatteo.it>
 * @link https://github.com/webnicola
 */

namespace Drupal\ictp_visa\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'VisaGuideBlock' block.
 *
 * @Block(
 *  id = "visa_guide_block",
 *  admin_label = @Translation("Visa Guide"),
 * )
 */
class VisaGuideBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal\node\Entity\Node::create(['type' => 'visa_interactive_guide']);
    $form = \Drupal::service('entity.form_builder')->getForm($node);
    unset($form['advanced']);
    unset($form['langcode']);
    //unset($form['actions']);
    unset($form['#submit']);
    unset($form['#validate']);
    unset($form['revision_log']);
    unset($form['status']);
    unset($form['meta']);

    return [
      '#theme' => 'visa_guide',
      '#form' => $form,
    ];
  }
}
