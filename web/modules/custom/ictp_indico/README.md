# ICTP Indico

INTRODUCTION
------------

The ICTP Indico module imports the events from Indico (indico.ictp.it) to Drupal.

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration. When enabled, the module will add the routes:
- /admin/ictp_indico
- /admin/ictp_indico/add
- /admin/ictp_indico/delete


UPDATE
--------
The process of updating an event is sensitive to:
- indico content
- field_indico_keywords
- field_indico_topic


PROCESS
--------
```mermaid
flowchart TD
  A[Indico API] ---> B[ICTP_Indico] ---> C{Evento nuovo?};
  C -- Yes --> D[Crea nuovo evento];
  C -- No --> E[Aggiorna evento];
  D ----> F[Evento in Drupal]
  E ----> F[Evento in Drupal]
```

```mermaid
classDiagram
    direction RL
    Event <|--  Indico
    Event : +String guid
    Event : +String title
    Event : +String description
    Event : +Ref section
    Event : +Date start_date
    Event : +Date end_date
    Event : +Time start_time
    Event : +Time end_time
    Event : +Date deadline
    Event : +String hash
    Event: +addNode()
    Event: +updateNode()

    class Indico{
      +String id
      +String title
      +String description
      +Array keywords
      +Date startDate.date
      +Date endDate.date
      +Date startDate.time
      +Date endDate.time
      +String contactInfo
      getSource()
    }
```
