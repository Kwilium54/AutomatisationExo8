<?php

namespace Tests\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration HTTP : vérifient que les routes de l'application
 * répondent correctement en appelant le serveur Docker via Guzzle.
 *
 *
 * URL de base configurable via la variable d'environnement APP_BASE_URL.
 * Depuis l'hôte Windows : http://localhost:8080
 * Depuis un container Docker : http://php
 */
class RouteTest extends TestCase
{
    private static Client $client;
    private static bool $serverAvailable = false;

    public static function setUpBeforeClass(): void
    {
        $baseUrl = $_ENV['APP_BASE_URL'] ?? getenv('APP_BASE_URL') ?: 'http://localhost:8080';

        self::$client = new Client([
            'base_uri'        => $baseUrl,
            'http_errors'     => false,
            'timeout'         => 5,
            'connect_timeout' => 3,
        ]);

        // Vérifie que le serveur est accessible avant de lancer les tests HTTP
        try {
            self::$client->get('/');
            self::$serverAvailable = true;
        } catch (ConnectException) {
            self::$serverAvailable = false;
        }
    }

    protected function setUp(): void
    {
        if (!self::$serverAvailable) {
            $this->markTestSkipped(
                "Serveur non disponible. Lancez `docker compose up` puis relancez les tests."
            );
        }
    }

    // ────────────────────────────────────────────────────────────────────
    // Pages principales
    // ────────────────────────────────────────────────────────────────────

    public function testAccueilRetourne200(): void
    {
        $response = self::$client->get('/');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString(
            'text/html',
            $response->getHeaderLine('Content-Type')
        );
    }

    public function testPageAjoutRetourne200(): void
    {
        $response = self::$client->get('/add');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPageRechercheRetourne200(): void
    {
        $response = self::$client->get('/search');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testPageCategorieRetourne200(): void
    {
        $response = self::$client->get('/cat/1');

        $this->assertSame(200, $response->getStatusCode());
    }

    // ────────────────────────────────────────────────────────────────────
    // Annonce individuelle
    // ────────────────────────────────────────────────────────────────────

    public function testAnnonceExistanteRetourne200(): void
    {
        $response = self::$client->get('/item/1');

        // L'annonce 1 doit exister après import des données SQL
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testAnnonceInexistanteAffiche404(): void
    {
        $response = self::$client->get('/item/999999');
        $body     = (string) $response->getBody();

        // L'app affiche "404" directement (echo "404" dans le controller)
        $this->assertStringContainsString('404', $body);
    }

   

    // ────────────────────────────────────────────────────────────────────
    // Formulaire POST /add — vérification de la validation côté serveur
    // ────────────────────────────────────────────────────────────────────

    public function testPostAddAvecChampsVidesAfficheErreurs(): void
    {
        $response = self::$client->post('/add', [
            'form_params' => [
                'nom'         => '',
                'email'       => 'pas-un-email',
                'phone'       => '',
                'ville'       => '',
                'departement' => 'invalid',
                'categorie'   => 'invalid',
                'title'       => '',
                'description' => '',
                'price'       => '',
                'psw'         => 'abc',
                'confirm-psw' => 'xyz',
            ],
        ]);

        $body = (string) $response->getBody();

        // La page d'erreur doit contenir au moins un message de validation
        $this->assertStringContainsString('Veuillez', $body);
    }

    public function testTrailingSlashRedirigeEnGet(): void
    {
        // Slim 3 middleware supprime les trailing slashes et redirige en 301
        $clientNoRedirect = new Client([
            'base_uri'        => self::$client->getConfig('base_uri'),
            'http_errors'     => false,
            'allow_redirects' => false,
            'timeout'         => 5,
        ]);

        $response = $clientNoRedirect->get('/add/');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringEndsWith('/add', $response->getHeaderLine('Location'));
    }
}
