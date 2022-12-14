<?php

namespace Drupal\ldap_user\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the plain text password is provided for editing a protected field.
 *
 * @Constraint(
 *   id = "LdapProtectedUserField",
 *   label = @Translation("LDAP password required for protected field change", context = "Validation")
 * )
 */
class LdapProtectedUserFieldConstraint extends Constraint {

  /**
   * Violation message.
   *
   * @var string
   */
  public $message = "Your current password is missing or incorrect; it's required to change the %name.";

}
