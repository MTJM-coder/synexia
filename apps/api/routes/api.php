<?php

/**
 * Point d'entrée unique de toutes les routes API. Chaque module déclare ses
 * routes dans Modules/{Nom}/routes.php (préfixées en interne par 'v1/...'),
 * et elles sont incluses ICI plutôt que via loadRoutesFrom() dans chaque
 * ServiceProvider — pour hériter automatiquement du préfixe 'api' et du
 * middleware group 'api' que Laravel n'applique qu'à ce fichier précis.
 *
 * IMPORTANT : si un module a encore un appel à $this->loadRoutesFrom(...)
 * dans son ServiceProvider, ENLÈVE-LE — sinon ses routes sont enregistrées
 * deux fois (une fois ici avec le bon préfixe, une fois via loadRoutesFrom
 * sans le préfixe 'api').
 */

foreach (glob(base_path('Modules/*/routes.php')) as $moduleRoutesFile) {
    require $moduleRoutesFile;
}