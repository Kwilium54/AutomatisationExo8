<?php

error_reporting(E_ALL ^ E_DEPRECATED);

// Démarre le buffering de sortie pour éviter que des warnings PHP
// ne déclenchent l'envoi prématuré des headers HTTP (ce qui bloquerait
// les redirections 3xx et autres réponses avec status code personnalisé).
ob_start();

extract(require __DIR__ . '/../src/bootstrap.php');

require __DIR__ . '/../routes/web.php';

ob_end_clean();

$app->run();
