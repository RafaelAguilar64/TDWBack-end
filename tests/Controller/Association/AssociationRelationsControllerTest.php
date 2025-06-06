<?php

/**
 * tests/Controller/Association/AssociationRelationsControllerTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Controller\Association;

use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use PHPUnit\Framework\Attributes as TestsAttr;
use TDW\ACiencia\Controller\Element\ElementRelationsBaseController;
use TDW\ACiencia\Controller\Association\{ AssociationQueryController, AssociationRelationsController };
use TDW\ACiencia\Entity\{ Association, Entity };
use TDW\ACiencia\Factory\{ AssociationFactory, EntityFactory };
use TDW\ACiencia\Utility\{ DoctrineConnector, Utils };
use TDW\Test\ACiencia\Controller\BaseTestCase;

/**
 * Class AssociationRelationsControllerTest
 */
#[TestsAttr\CoversClass(AssociationRelationsController::class)]
#[TestsAttr\CoversClass(ElementRelationsBaseController::class)]
final class AssociationRelationsControllerTest extends BaseTestCase
{
    /** @var string Path para la gestión de asociaciones */
    protected const RUTA_API = '/api/v1/associations';

    /** @var array<string,mixed> Admin data */
    protected static array $writer;

    /** @var array<string,mixed> reader user data */
    protected static array $reader;

    protected static ?EntityManagerInterface $entityManager;

    private static Association $association;
    private static Entity $entity;

    /**
     * Se ejecuta una vez al inicio de las pruebas de la clase
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // load user admin fixtures
        self::$writer = [
            'username' => (string) getenv('ADMIN_USER_NAME'),
            'email'    => (string) getenv('ADMIN_USER_EMAIL'),
            'password' => (string) getenv('ADMIN_USER_PASSWD'),
        ];

        self::$writer['id'] = Utils::loadUserData(
            username: (string) self::$writer['username'],
            email: (string) self::$writer['email'],
            password: (string) self::$writer['password'],
            isWriter: true
        );

        // load user reader fixtures
        self::$reader = [
            'username' => self::$faker->userName(),
            'email'    => self::$faker->email(),
            'password' => self::$faker->password(),
        ];
        self::$reader['id'] = Utils::loadUserData(
            username: self::$reader['username'],
            email: self::$reader['email'],
            password: self::$reader['password'],
            isWriter: false
        );

        // create and insert fixtures
        $associationName = substr(self::$faker->name(), 0, 80);
        self::assertNotEmpty($associationName);
        self::$association = AssociationFactory::createElement($associationName);

        $entityName = substr(self::$faker->name(), 0, 80);
        self::assertNotEmpty($entityName);
        self::$entity = EntityFactory::createElement($entityName);

        self::$entityManager = DoctrineConnector::getEntityManager();
        self::$entityManager->persist(self::$association);
        self::$entityManager->persist(self::$entity);
        self::$entityManager->flush();
    }

    public function testGetEntitiesTag(): void
    {
        self::assertSame(
            AssociationQueryController::getEntitiesTag(),
            AssociationRelationsController::getEntitiesTag()
        );
    }

    // *******************
    // Association -> Entities
    // *******************

    /**
     * OPTIONS /associations/{associationId}/entities
     * OPTIONS /associations/{associationId}/entities/add/{entityId}
     */
    public function testOptionsRelationship204(): void
    {
        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$association->getId() . '/entities'
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());

        $response = $this->runApp(
            'OPTIONS',
            self::RUTA_API . '/' . self::$association->getId()
            . '/entities/add/' . self::$entity->getId()
        );
        self::assertSame(204, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Allow'));
        self::assertEmpty($response->getBody()->getContents());
    }

    /**
     * PUT /associations/{associationId}/entities/add/{entityId}
     */
    public function testAddEntity209(): void
    {
        self::$writer['authHeader'] = $this->getTokenHeaders(self::$writer['username'], self::$writer['password']);
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$association->getId()
                . '/entities/add/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        self::assertJson($response->getBody()->getContents());
    }

    /**
     * GET /associations/{associationId}/entities 200 Ok
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testAddEntity209')]
    public function testGetEntities200OkWithElements(): void
    {
        self::$reader['authHeader'] = $this->getTokenHeaders(self::$reader['username'], self::$reader['password']);
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$association->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseEntities);
        self::assertSame(
            self::$entity->getName(),
            $responseEntities['entities'][0]['entity']['name']
        );
    }

    /**
     * PUT /associations/{associationId}/entities/rem/{entityId}
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testGetEntities200OkWithElements')]
    public function testRemoveEntity209(): void
    {
        $response = $this->runApp(
            'PUT',
            self::RUTA_API . '/' . self::$association->getId()
            . '/entities/rem/' . self::$entity->getId(),
            null,
            self::$writer['authHeader']
        );
        self::assertSame(209, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseAssociation = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseAssociation['association']);
        self::assertEmpty($responseAssociation['association']['entities']);
    }

    /**
     * GET /associations/{associationId}/entities 200 Ok - Empty
     *
     * @throws JsonException
     */
    #[TestsAttr\Depends('testRemoveEntity209')]
    public function testGetEntities200OkEmpty(): void
    {
        $response = $this->runApp(
            'GET',
            self::RUTA_API . '/' . self::$association->getId() . '/entities',
            null,
            self::$reader['authHeader']
        );
        self::assertSame(200, $response->getStatusCode());
        $r_body = $response->getBody()->getContents();
        self::assertJson($r_body);
        $responseEntities = json_decode($r_body, true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('entities', $responseEntities);
        self::assertEmpty($responseEntities['entities']);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param int $status
     * @param string $user
     *
     * @return void
     */
    #[TestsAttr\DataProvider('routeExceptionProvider')]
    public function testAssociationRelationshipErrors(string $method, string $uri, int $status, string $user = ''): void
    {
        $requestingUser = match ($user) {
            'admin'  => self::$writer,
            'reader' => self::$reader,
            default  => ['username' => '', 'password' => '']
        };

        $response = $this->runApp(
            $method,
            $uri,
            null,
            $this->getTokenHeaders($requestingUser['username'], $requestingUser['password'])
        );
        $this->internalTestError($response, $status);
    }

    // --------------
    // DATA PROVIDERS
    // --------------

    /**
     * Route provider (expected status: 404 NOT FOUND)
     *
     * @return array<string,mixed> [ method, url, path, status, user ]
     */
    public static function routeExceptionProvider(): array
    {
        return [
            // 401
            'putAddEntity401'     => [ 'PUT', self::RUTA_API . '/1/entities/add/1', 401],
            'putRemoveEntity401'  => [ 'PUT', self::RUTA_API . '/1/entities/rem/1', 401],

            // 403
            'putAddEntity403'     => [ 'PUT', self::RUTA_API . '/1/entities/add/1', 403, 'reader'],
            'putRemoveEntity403'  => [ 'PUT', self::RUTA_API . '/1/entities/rem/1', 403, 'reader'],

            // 404
            'getEntities404'       => [ 'GET', self::RUTA_API . '/0/entities',       404, 'admin'],
            'putAddEntity404'     => [ 'PUT', self::RUTA_API . '/0/entities/add/1', 404, 'admin'],
            'putRemoveEntity404'  => [ 'PUT', self::RUTA_API . '/0/entities/rem/1', 404, 'admin'],

            // 406
            'putAddEntity406'     => [ 'PUT', self::RUTA_API . '/1/entities/add/100', 406, 'admin'],
            'putRemoveEntity406'  => [ 'PUT', self::RUTA_API . '/1/entities/rem/100', 406, 'admin'],
        ];
    }
}