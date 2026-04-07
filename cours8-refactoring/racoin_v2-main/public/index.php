<?php

error_reporting(E_ALL ^ E_DEPRECATED);

extract(require __DIR__ . '/../src/bootstrap.php');

require __DIR__ . '/../routes/web.php';

$app->run();
