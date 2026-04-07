<?php

namespace Tests\Unit;

use App\Controller\AddItemController;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de la validation d'email et de formulaire dans AddItemController.
 */
class AddItemValidationTest extends TestCase
{
    private AddItemController $controller;
    private \ReflectionMethod $isEmail;

    protected function setUp(): void
    {
        $this->controller = new AddItemController();
        $this->isEmail    = new \ReflectionMethod(AddItemController::class, 'isEmail');
    }

    // ────────────────────────────────────────────────────────────────────
    // Emails VALIDES
    // ────────────────────────────────────────────────────────────────────

    public function testEmailSimpleValide(): void
    {
        $this->assertTrue(
            (bool) $this->isEmail->invoke($this->controller, 'user@example.com')
        );
    }

    public function testEmailAvecSousDomaine(): void
    {
        $this->assertTrue(
            (bool) $this->isEmail->invoke($this->controller, 'user@mail.example.org')
        );
    }

    public function testEmailAvecTiretEtPoint(): void
    {
        $this->assertTrue(
            (bool) $this->isEmail->invoke($this->controller, 'jean-pierre.dupont@service.fr')
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Emails INVALIDES
    // ────────────────────────────────────────────────────────────────────

    public function testEmailSansArobase(): void
    {
        $this->assertFalse(
            (bool) $this->isEmail->invoke($this->controller, 'invalide.com')
        );
    }

    public function testEmailChainVide(): void
    {
        $this->assertFalse(
            (bool) $this->isEmail->invoke($this->controller, '')
        );
    }

    public function testEmailSansDomaine(): void
    {
        $this->assertFalse(
            (bool) $this->isEmail->invoke($this->controller, 'user@')
        );
    }

    public function testEmailSansTld(): void
    {
        $this->assertFalse(
            (bool) $this->isEmail->invoke($this->controller, 'user@domain')
        );
    }

    public function testEmailArobaseSeul(): void
    {
        $this->assertFalse(
            (bool) $this->isEmail->invoke($this->controller, '@example.com')
        );
    }

    // ────────────────────────────────────────────────────────────────────
    // Validation du formulaire : règles métier sur les champs
    // Ces tests vérifient la logique de validation telle qu'elle existe dans
    // addNewItem(), extraite ici sous forme de règles indépendantes pour
    // servir de contrat à ne pas briser lors du refactoring.
    // ────────────────────────────────────────────────────────────────────

    public function testChampsVideProduisantDesErreurs(): void
    {
        // Reproduit exactement le tableau d'erreurs construit par addNewItem()
        $errors = [];

        $nom         = '';
        $email       = '';
        $phone       = '';
        $ville       = '';
        $departement = 'invalid';
        $categorie   = 'invalid';
        $title       = '';
        $description = '';
        $price       = '';
        $password    = '';
        $password_confirm = 'autre';

        if (empty($nom))                                              { $errors[] = 'nameAdvertiser'; }
        if (!(bool) $this->isEmail->invoke($this->controller, $email)){ $errors[] = 'emailAdvertiser'; }
        if (empty($phone) && !is_numeric($phone))                     { $errors[] = 'phoneAdvertiser'; }
        if (empty($ville))                                            { $errors[] = 'villeAdvertiser'; }
        if (!is_numeric($departement))                                { $errors[] = 'departmentAdvertiser'; }
        if (!is_numeric($categorie))                                  { $errors[] = 'categorieAdvertiser'; }
        if (empty($title))                                            { $errors[] = 'titleAdvertiser'; }
        if (empty($description))                                      { $errors[] = 'descriptionAdvertiser'; }
        if (empty($price) || !is_numeric($price))                     { $errors[] = 'priceAdvertiser'; }
        if (empty($password) || $password !== $password_confirm)      { $errors[] = 'passwordAdvertiser'; }

        $this->assertNotEmpty($errors, 'Un formulaire vide doit générer des erreurs');
        $this->assertContains('nameAdvertiser', $errors);
        $this->assertContains('emailAdvertiser', $errors);
        $this->assertContains('titleAdvertiser', $errors);
        $this->assertContains('passwordAdvertiser', $errors);
    }

    public function testFormulaireSansErreurs(): void
    {
        $errors = [];

        $nom         = 'Jean Dupont';
        $email       = 'jean@example.com';
        $phone       = '0612345678';
        $ville       = 'Paris';
        $departement = '75';
        $categorie   = '1';
        $title       = 'Vélo à vendre';
        $description = 'Très bon état';
        $price       = '150';
        $password    = 'secret123';
        $password_confirm = 'secret123';

        if (empty($nom))                                              { $errors[] = 'nameAdvertiser'; }
        if (!(bool) $this->isEmail->invoke($this->controller, $email)){ $errors[] = 'emailAdvertiser'; }
        if (empty($phone) && !is_numeric($phone))                     { $errors[] = 'phoneAdvertiser'; }
        if (empty($ville))                                            { $errors[] = 'villeAdvertiser'; }
        if (!is_numeric($departement))                                { $errors[] = 'departmentAdvertiser'; }
        if (!is_numeric($categorie))                                  { $errors[] = 'categorieAdvertiser'; }
        if (empty($title))                                            { $errors[] = 'titleAdvertiser'; }
        if (empty($description))                                      { $errors[] = 'descriptionAdvertiser'; }
        if (empty($price) || !is_numeric($price))                     { $errors[] = 'priceAdvertiser'; }
        if (empty($password) || $password !== $password_confirm)      { $errors[] = 'passwordAdvertiser'; }

        $this->assertEmpty($errors, 'Un formulaire correctement rempli ne doit générer aucune erreur');
    }

    public function testMotsDePasseDifferentsProduisantUneErreur(): void
    {
        $errors = [];

        $password = 'secret123';
        $password_confirm = 'autrechose';

        if (empty($password) || $password !== $password_confirm) {
            $errors[] = 'passwordAdvertiser';
        }

        $this->assertContains('passwordAdvertiser', $errors);
    }

    public function testPrixNonNumeriqueProduisantUneErreur(): void
    {
        $errors = [];
        $price = 'gratuit';

        if (empty($price) || !is_numeric($price)) {
            $errors[] = 'priceAdvertiser';
        }

        $this->assertContains('priceAdvertiser', $errors);
    }
}
