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

// Ces variables sont injectées depuis public/index.php via extract(bootstrap)
// $app, $twig, $menu, $chemin

$cat = new CategorieController();
$dpt = new DepartementController();

$app->get('/', function () use ($twig, $menu, $chemin, $cat) {
    $index = new HomeController();
    $index->displayAllAnnonce($twig, $menu, $chemin, $cat->getCategories());
});

$app->get('/item/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n    = $arg['n'];
    $item = new ItemController();
    $item->afficherItem($twig, $menu, $chemin, $n, $cat->getCategories());
});

$app->get('/add', function () use ($twig, $app, $menu, $chemin, $cat, $dpt) {
    $ajout = new AddItemController();
    $ajout->addItemView($twig, $menu, $chemin, $cat->getCategories(), $dpt->getAllDepartments());
});

$app->post('/add', function ($request) use ($twig, $app, $menu, $chemin) {
    $allPostVars = $request->getParsedBody();
    $ajout       = new AddItemController();
    $ajout->addNewItem($twig, $menu, $chemin, $allPostVars);
});

$app->get('/item/{id}/edit', function ($request, $response, $arg) use ($twig, $menu, $chemin) {
    $id   = $arg['id'];
    $item = new ItemController();
    $item->modifyGet($twig, $menu, $chemin, $id);
});

$app->post('/item/{id}/edit', function ($request, $response, $arg) use ($twig, $app, $menu, $chemin, $cat, $dpt) {
    $id          = $arg['id'];
    $allPostVars = $request->getParsedBody();
    $item        = new ItemController();
    $item->modifyPost($twig, $menu, $chemin, $id, $allPostVars, $cat->getCategories(), $dpt->getAllDepartments());
});

$app->map(['GET, POST'], '/item/{id}/confirm', function ($request, $response, $arg) use ($twig, $app, $menu, $chemin) {
    $id          = $arg['id'];
    $allPostVars = $request->getParsedBody();
    $item        = new ItemController();
    $item->edit($twig, $menu, $chemin, $id, $allPostVars);
});

$app->get('/search', function () use ($twig, $menu, $chemin, $cat) {
    $s = new SearchController();
    $s->show($twig, $menu, $chemin, $cat->getCategories());
});

$app->post('/search', function ($request, $response) use ($app, $twig, $menu, $chemin, $cat) {
    $array = $request->getParsedBody();
    $s     = new SearchController();
    $s->research($array, $twig, $menu, $chemin, $cat->getCategories());
});

$app->get('/annonceur/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n         = $arg['n'];
    $annonceur = new AnnonceurController();
    $annonceur->afficherAnnonceur($twig, $menu, $chemin, $n, $cat->getCategories());
});

$app->get('/del/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin) {
    $n    = $arg['n'];
    $item = new ItemController();
    $item->supprimerItemGet($twig, $menu, $chemin, $n);
});

$app->post('/del/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n    = $arg['n'];
    $item = new ItemController();
    $item->supprimerItemPost($twig, $menu, $chemin, $n, $cat->getCategories());
});

$app->get('/cat/{n}', function ($request, $response, $arg) use ($twig, $menu, $chemin, $cat) {
    $n         = $arg['n'];
    $categorie = new CategorieController();
    $categorie->displayCategorie($twig, $menu, $chemin, $cat->getCategories(), $n);
});

$app->get('/api(/)', function () use ($twig, $menu, $chemin) {
    $template = $twig->load('api.html.twig');
    $menu     = array(
        array('href' => $chemin, 'text' => 'Acceuil'),
        array('href' => $chemin . '/api', 'text' => 'Api')
    );
    echo $template->render(array('breadcrumb' => $menu, 'chemin' => $chemin));
});

$app->group('/api', function () use ($app, $twig, $menu, $chemin) {

    $app->group('/annonce', function () use ($app) {

        $app->get('/{id}', function ($request, $response, $arg) use ($app) {
            $id          = $arg['id'];
            $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
            $return      = Annonce::select($annonceList)->find($id);

            if (isset($return)) {
                $response->headers->set('Content-Type', 'application/json');
                $return->categorie     = Categorie::find($return->categorie);
                $return->annonceur     = Annonceur::select('email', 'nom_annonceur', 'telephone')->find($return->annonceur);
                $return->departement   = Departement::select('id_departement', 'nom_departement')->find($return->departement);
                $links                 = [];
                $links['self']['href'] = '/api/annonce/' . $return->id_annonce;
                $return->links         = $links;
                echo $return->toJson();
            } else {
                $app->notFound();
            }
        });
    });

    $app->group('/annonces(/)', function () use ($app) {

        $app->get('/', function ($request, $response) use ($app) {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $response->headers->set('Content-Type', 'application/json');
            $a     = Annonce::all($annonceList);
            $links = [];
            foreach ($a as $ann) {
                $links['self']['href'] = '/api/annonce/' . $ann->id_annonce;
                $ann->links            = $links;
            }
            $links['self']['href'] = '/api/annonces/';
            $a->links              = $links;
            echo $a->toJson();
        });
    });

    $app->group('/categorie', function () use ($app) {

        $app->get('/{id}', function ($request, $response, $arg) use ($app) {
            $id = $arg['id'];
            $response->headers->set('Content-Type', 'application/json');
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
            echo $c->toJson();
        });
    });

    $app->group('/categories(/)', function () use ($app) {
        $app->get('/', function ($request, $response, $arg) use ($app) {
            $response->headers->set('Content-Type', 'application/json');
            $c     = Categorie::get();
            $links = [];
            foreach ($c as $cat) {
                $links['self']['href'] = '/api/categorie/' . $cat->id_categorie;
                $cat->links            = $links;
            }
            $links['self']['href'] = '/api/categories/';
            $c->links              = $links;
            echo $c->toJson();
        });
    });

    $app->get('/key', function () use ($app, $twig, $menu, $chemin) {
        $kg = new KeyGeneratorController();
        $kg->show($twig, $menu, $chemin, (new CategorieController())->getCategories());
    });

    $app->post('/key', function () use ($app, $twig, $menu, $chemin) {
        $nom = $_POST['nom'];
        $kg  = new KeyGeneratorController();
        $kg->generateKey($twig, $menu, $chemin, (new CategorieController())->getCategories(), $nom);
    });
});
