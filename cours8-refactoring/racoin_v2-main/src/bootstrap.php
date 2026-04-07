<?php

use App\Db\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response as SlimResponse;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../vendor/autoload.php';

Connection::createConn();

$app = AppFactory::create();

$loader = new FilesystemLoader(__DIR__ . '/../template');
$twig   = new Environment($loader);

// Middleware : suppression des trailing slashes (redirect 301 en GET, rewrite URI sinon)
// Ajouté APRÈS addRoutingMiddleware pour s'exécuter AVANT le routage (LIFO Slim 4)
$app->addRoutingMiddleware();

$app->add(function (Request $request, RequestHandlerInterface $handler): Response {
    $uri  = $request->getUri();
    $path = $uri->getPath();
    if ($path !== '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(rtrim($path, '/'));
        if ($request->getMethod() === 'GET') {
            $response = new SlimResponse();
            return $response->withStatus(301)->withHeader('Location', (string)$uri);
        }
        $request = $request->withUri($uri);
    }
    return $handler->handle($request);
});

$app->addErrorMiddleware(true, true, true);

if (session_status() === PHP_SESSION_NONE) {
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
