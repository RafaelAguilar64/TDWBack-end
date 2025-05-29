<?php

/**
 * tests/Entity/AssociationTest.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\Test\ACiencia\Entity;

use PHPUnit\Framework\Attributes as TestsAttr;
use PHPUnit\Framework\TestCase;
use TDW\ACiencia\Entity\{ Element, Association };
use TDW\ACiencia\Factory;
use function PHPUnit\Framework\assertNotEmpty;

/**
 * Class AssociationTest
 */
#[TestsAttr\Group('associations')]
#[TestsAttr\CoversClass(Association::class)]
#[TestsAttr\CoversClass(Element::class)]
#[TestsAttr\CoversClass(Factory\AssociationFactory::class)]
#[TestsAttr\UsesClass(Factory\EntityFactory::class)]
class AssociationTest extends TestCase
{
    protected static Association $association;

    private static \Faker\Generator $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass(): void
    {
        self::$faker = \Faker\Factory::create('es_ES');
        $name = self::$faker->company();
        self::assertNotEmpty($name);
        self::$association = Factory\AssociationFactory::createElement($name);
    }

    /**
     * @return void
     */
    public function testConstructor(): void
    {
        $name = self::$faker->company();
        self::assertNotEmpty($name);
        self::$association = Factory\AssociationFactory::createElement($name);
        self::assertSame(0, self::$association->getId());
        self::assertSame(
            $name,
            self::$association->getName()
        );
        self::assertEmpty(self::$association->getEntities());
    }

    public function testGetId(): void
    {
        self::assertSame(0, self::$association->getId());
    }

    public function testGetSetAssociationName(): void
    {
        /** @var non-empty-string $associationName */
        $associationName = self::$faker->name();
        self::$association->setName($associationName);
        static::assertSame(
            $associationName,
            self::$association->getName()
        );
    }

    public function testGetSetBirthDate(): void
    {
        $birthDate = self::$faker->dateTime();
        self::$association->setBirthDate($birthDate);
        static::assertSame(
            $birthDate,
            self::$association->getBirthDate()
        );
    }

    public function testGetSetDeathDate(): void
    {
        $deathDate = self::$faker->dateTime();
        self::$association->setDeathDate($deathDate);
        static::assertSame(
            $deathDate,
            self::$association->getDeathDate()
        );
    }

    public function testGetSetImageUrl(): void
    {
        $imageUrl = self::$faker->url();
        self::$association->setImageUrl($imageUrl);
        static::assertSame(
            $imageUrl,
            self::$association->getImageUrl()
        );
    }

    public function testGetSetWikiUrl(): void
    {
        $wikiUrl = self::$faker->url();
        self::$association->setWikiUrl($wikiUrl);
        static::assertSame(
            $wikiUrl,
            self::$association->getWikiUrl()
        );
    }

    public function testGetAddContainsRemoveEntities(): void
    {
        self::assertEmpty(self::$association->getEntities());
        $slug = self::$faker->slug();
        self::assertNotEmpty($slug);
        $entity = Factory\EntityFactory::createElement($slug);

        self::$association->addEntity($entity);
        self::$association->addEntity($entity);  // CC
        self::assertNotEmpty(self::$association->getEntities());
        self::assertTrue(self::$association->containsEntity($entity));

        self::$association->removeEntity($entity);
        self::assertFalse(self::$association->containsEntity($entity));
        self::assertCount(0, self::$association->getEntities());
        self::assertFalse(self::$association->removeEntity($entity));
    }

    public function testToString(): void
    {
        /** @var non-empty-string $associationName */
        $associationName = self::$faker->company();
        $birthDate = self::$faker->dateTime();
        $deathDate = self::$faker->dateTime();
        self::$association->setBirthDate($birthDate);
        self::$association->setDeathDate($deathDate);
        self::$association->setName($associationName);
        self::assertStringContainsString(
            $associationName,
            self::$association->__toString()
        );
        self::assertStringContainsString(
            $birthDate->format('Y-m-d'),
            self::$association->__toString()
        );
        self::assertStringContainsString(
            $deathDate->format('Y-m-d'),
            self::$association->__toString()
        );
    }

    public function testJsonSerialize(): void
    {
        $jsonStr = (string) json_encode(self::$association, JSON_PARTIAL_OUTPUT_ON_ERROR);
        self::assertJson($jsonStr);
    }
}