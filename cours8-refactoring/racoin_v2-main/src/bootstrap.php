<?php

use App\Db\Connection;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../vendor/autoload.php';

Connection::createConn();

$app = new App([
    'settings' => [
        'displayErrorDetails' => true,
    ],
]);

$loader = new FilesystemLoader(__DIR__ . '/../template');
$twig   = new Environment($loader);

$app->add(function (Request $request, Response $response, $next) {
    $uri  = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(substr($path, 0, -1));
        if ($request->getMethod() == 'GET') {
            return $response->withRedirect((string)$uri, 301);
        } else {
            return $next($request->withUri($uri), $response);
        }
    }
    return $next($request, $response);
});

if (!isset($_SESSION)) {
    session_start();
    $_SESSION['formStarted'] = true;
}

if (!isset($_SESSION['token'])) {
    $token                  = md5(uniqid(rand(), TRUE));
    $_SESSION['token']      = $token;
    $_SESSION['token_time'] = time();
} else {
    $token = $_SESSION['token'];
}

$menu   = [['href' => './index.php', 'text' => 'Accueil']];
$chemin = dirname($_SERVER['SCRIPT_NAME']);

return compact('app', 'twig', 'menu', 'chemin');
