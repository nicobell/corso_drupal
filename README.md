# ictp

## Project setup

### Edit example.env
Rinomina file example.env in .env ed eventualmente modifica per sviluppo in locale / produzione


### Settings.php
Se necessario copia settings.php in settings.local.php

La versione di default considera proxy server quindi nella versione in locale:
```
// Interfase config proxy
$settings['reverse_proxy'] = FALSE;
$settings['reverse_proxy_addresses'] = array();
$settings['reverse_proxy_trusted_headers'] = '';
```

### Init.sql
Dentro la cartella database esiste un Init.sql utile per lanciare docker con db già settato

## Note

- config/sync in root, fuori da cartella web
- private in root, fuori da cartella web
