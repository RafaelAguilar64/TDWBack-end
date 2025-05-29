<?php

/**
 * src/Entity/Association.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;

use DateTime;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionObject;

#[ORM\Entity, ORM\Table(name: "associations")]
#[ORM\UniqueConstraint(name: "Association_name_uindex", columns: [ "name" ])]
class Association extends Element
{
    /* Collection of entities that are part of the association */
    #[ORM\ManyToMany(targetEntity: Entity::class, inversedBy: "associations")]
    #[ORM\JoinTable(name: "association_entity")]
    #[ORM\JoinColumn(name: "association_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "entity_id", referencedColumnName: "id")]
    protected Collection $entities;

    /**
     * Association constructor.
     *
     * @param non-empty-string $name
     * @param DateTime|null $birthDate
     * @param DateTime|null $deathDate
     * @param string|null $imageUrl
     * @param string|null $wikiUrl
     */
    public function __construct(
        string $name,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ) {
        parent::__construct($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
        $this->entities = new ArrayCollection();
    }

    // Entities

    /**
     * Gets the entities that are part of the association
     *
     * @return Collection<Entity>
     */
    public function getEntities(): Collection
    {
        return $this->entities;
    }

    /**
     * Indicates whether an entity is part of this association
     *
     * @param Entity $entity
     *
     * @return bool
     */
    public function containsEntity(Entity $entity): bool
    {
        return $this->entities->contains($entity);
    }

    /**
     * Add an entity to this association
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function addEntity(Entity $entity): void
    {
        if (!$this->containsEntity($entity)) {
            $this->entities->add($entity);
        }
    }

    /**
     * Removes an entity from this association
     *
     * @param Entity $entity
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEntity(Entity $entity): bool
    {
        return $this->entities->removeElement($entity);
    }

    /**
     * @see \Stringable
     */
    public function __toString(): string
    {
        return sprintf(
            '%s entities=%s)]',
            parent::__toString(),
            $this->getCodesStr($this->getEntities())
        );
    }

    /**
     * @see \JsonSerializable
     */
    #[ArrayShape(['association' => "array|mixed"])]
    public function jsonSerialize(): mixed
    {
        /* Reflection to examine the instance */
        $reflection = new ReflectionObject($this);
        $data = parent::jsonSerialize();
        $numEntities = count($this->getEntities());
        $data['entities'] = $numEntities !== 0 ? $this->getCodes($this->getEntities()) : null;

        return [strtolower($reflection->getShortName()) => $data];
    }
}