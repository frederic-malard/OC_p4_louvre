<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PersonRepository")
 */
class Person
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\Length(
     *      min=2,
     *      max=255,
     *      minMessage="le nom doit faire au moins 2 caractères",
     *      maxMessage="le nom doit faire au maximum 255 caractères"
     * )
     * 
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Assert\Length(
     *      min=2,
     *      max=255,
     *      minMessage="le prénom doit faire au moins 2 caractères",
     *      maxMessage="le prénom doit faire au maximum 255 caractères"
     * )
     * 
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $country;

    /**
     * @Assert\LessThanOrEqual(
     *      "+9 month",
     *      message = "Vous avez entré une date de naissance future, merci de vérifier votre date de visite."
     * )
     * @Assert\GreaterThanOrEqual(
     *      "-125 years",
     *      message = "Vous avez entré une date passée de plus de 125 ans, merci de vérifier votre date de naissance."
     * )
     * 
     * @ORM\Column(type="date")
     */
    private $birthDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $discount;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Reservation", mappedBy="persons")
     */
    private $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function stringBirthDate(): ?string
    {
        return $this->birthDate->format('d/m/Y');
    }

    public function setBirthDate(\DateTimeInterface $birthDate): self
    {
        if ($birthDate < new \DateTime())
            $this->birthDate = $birthDate;
        else
            throw new \Exception('date de naissance dans le futur');

        return $this;
    }

    public function getDiscount(): ?bool
    {
        return $this->discount;
    }

    public function setDiscount(bool $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->addPerson($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->contains($reservation)) {
            $this->reservations->removeElement($reservation);
            $reservation->removePerson($this);
        }

        return $this;
    }

    private function age()
    {
        return $this->birthDate->diff(new \DateTime())->format('%Y');
    }

    public function priceFullDay()
    {
        $age = $this->age();
        $parameters = Yaml::parseFile('../config/parameters.yaml');
        if ($age < $parameters['ageChild'])
            return $parameters['priceBaby'];
        elseif ($age < $parameters['ageTeenager'])
            return $parameters['priceChild'];
        elseif ($this->discount)
            return $parameters['priceDiscount'];
        elseif ($age >= $parameters['ageOld'])
            return $parameters['priceOld'];
        else
            return $parameters['priceDefault'];
    }

    public function priceHalfDay()
    {
        return $this->priceFullDay() / 2;
    }
}
