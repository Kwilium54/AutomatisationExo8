<?php

use App\Controller\AddItemController;
use App\Controller\AnnonceurController;
use App\Controller\CategorieController;
use App\Controller\DepartementController;
use App\Controller\HomeController;
use App\Controller\ItemController;
use App\Controller\KeyGeneratorController;
use App\Controller\SearchController;
use App\Model\Annonce;
use App\Model\Annonceur;
use App\Model\Categorie;
use App\Model\Departement;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteCollectorProxy;

// Ces variables sont injectÃ©es depuis public/index.php via extract(bootstrap)
// $app, $twig, $menu, $chemin

$cat = new CategorieController();
$dpt = new DepartementController();

$app->get('/', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
    $index = new HomeController();
    ob_start();
    $index->displayAllAnnonce($twig, $menu, $chemin, $cat->getCategories());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/item/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $item = new ItemController();
    ob_start();
    $item->afficherItem($twig, $menu, $chemin, $args['n'], $cat->getCategories());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat, $dpt): Response {
    $ajout = new AddItemController();
    ob_start();
    $ajout->addItemView($twig, $menu, $chemin, $cat->getCategories(), $dpt->getAllDepartments());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin): Response {
    $allPostVars = $request->getParsedBody();
    $ajout       = new AddItemController();
    ob_start();
    $ajout->addNewItem($twig, $menu, $chemin, $allPostVars);
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin): Response {
    $item = new ItemController();
    ob_start();
    $item->modifyGet($twig, $menu, $chemin, $args['id']);
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat, $dpt): Response {
    $allPostVars = $request->getParsedBody();
    $item        = new ItemController();
    ob_start();
    $item->modifyPost($twig, $menu, $chemin, $args['id'], $allPostVars, $cat->getCategories(), $dpt->getAllDepartments());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->map(['GET', 'POST'], '/item/{id}/confirm', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin): Response {
    $allPostVars = $request->getParsedBody();
    $item        = new ItemController();
    ob_start();
    $item->edit($twig, $menu, $chemin, $allPostVars, $args['id']);
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
    $s = new SearchController();
    ob_start();
    $s->show($twig, $menu, $chemin, $cat->getCategories());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
    $array = $request->getParsedBody();
    $s     = new SearchController();
    ob_start();
    $s->research($array, $twig, $menu, $chemin, $cat->getCategories());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/annonceur/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $annonceur = new AnnonceurController();
    ob_start();
    $annonceur->afficherAnnonceur($twig, $menu, $chemin, $args['n'], $cat->getCategories());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin): Response {
    $item = new ItemController();
    ob_start();
    $item->supprimerItemGet($twig, $menu, $chemin, $args['n']);
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->post('/del/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $item = new ItemController();
    ob_start();
    $item->supprimerItemPost($twig, $menu, $chemin, $args['n'], $cat->getCategories());
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/cat/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $cat): Response {
    $categorie = new CategorieController();
    ob_start();
    $categorie->displayCategorie($twig, $menu, $chemin, $cat->getCategories(), $args['n']);
    $response->getBody()->write(ob_get_clean());
    return $response;
});

$app->get('/api', function (Request $request, Response $response) use ($twig, $menu, $chemin): Response {
    $template = $twig->load('api.html.twig');
    $menu     = [
        ['href' => $chemin, 'text' => 'Acceuil'],
        ['href' => $chemin . '/api', 'text' => 'Api'],
    ];
    $response->getBody()->write($template->render(['breadcrumb' => $menu, 'chemin' => $chemin]));
    return $response;
});

$app->group('/api', function (RouteCollectorProxy $group) use ($twig, $menu, $chemin, $cat) {

    $group->get('/annonce/{id}', function (Request $request, Response $response, array $args): Response {
        $id          = $args['id'];
        $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
        $return      = Annonce::select($annonceList)->find($id);

        if ($return === null) {
            throw new HttpNotFoundException($request);
        }

        $return->categorie     = Categorie::find($return->categorie);
        $return->annonceur     = Annonceur::select('email', 'nom_annonceur', 'telephone')->find($return->annonceur);
        $return->departement   = Departement::select('id_departement', 'nom_departement')->find($return->departement);
        $links                 = [];
        $links['self']['href'] = '/api/annonce/' . $return->id_annonce;
        $return->links         = $links;

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($return->toJson());
        return $response;
    });

    $group->get('/annonces', function (Request $request, Response $response): Response {
        $annonceList           = ['id_annonce', 'prix', 'titre', 'ville'];
        $a                     = Annonce::all($annonceList);
        $links                 = [];
        foreach ($a as $ann) {
            $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
            $ann->links            = $links;
        }
        $links['self']['href'] = '/api/annonces';
        $a->links              = $links;

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($a->toJson());
        return $response;
    });

    $group->get('/categorie/{id}', function (Request $request, Response $response, array $args): Response {
        $id    = $args['id'];
        $a     = Annonce::select('id_annonce', 'prix', 'titre', 'ville')
            ->where('id_categorie', '=', $id)
            ->get();
        $links = [];
        foreach ($a as $ann) {
            $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
            $ann->links            = $links;
        }
        $c                     = Categorie::find($id);
        $links['self']['href'] = '/api/categorie/' . $id;
        $c->links              = $links;
        $c->annonces           = $a;

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($c->toJson());
        return $response;
    });

    $group->get('/categories', function (Request $request, Response $response): Response {
        $c     = Categorie::get();
        $links = [];
        foreach ($c as $item) {
            $links['self']['href'] = '/api/categorie/' . $item->id_categorie;
            $item->links           = $links;
        }
        $links['self']['href'] = '/api/categories';
        $c->links              = $links;

        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write($c->toJson());
        return $response;
    });

    $group->get('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
        $kg = new KeyGeneratorController();
        ob_start();
        $kg->show($twig, $menu, $chemin, $cat->getCategories());
        $response->getBody()->write(ob_get_clean());
        return $response;
    });

    $group->post('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $cat): Response {
        $body = $request->getParsedBody();
        $nom  = $body['nom'] ?? '';
        $kg   = new KeyGeneratorController();
        ob_start();
        $kg->generateKey($twig, $menu, $chemin, $cat->getCategories(), $nom);
        $response->getBody()->write(ob_get_clean());
        return $response;
    });
});


