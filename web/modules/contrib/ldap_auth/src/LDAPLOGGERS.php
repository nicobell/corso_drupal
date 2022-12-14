<?php

namespace Drupal\ldap_auth;

class LDAPLOGGERS{
    public static function addLogger($log_info, $log_val=''){
        $enable_logs = \Drupal::config('ldap_auth.settings')->get('miniorange_ldap_enable_logs');
        if($enable_logs)
            \Drupal::logger('ldap_auth')->notice($log_info.'<pre>code>' . print_r($log_val, TRUE) . '</code></pre>' );
        return;
    }
}
?>
