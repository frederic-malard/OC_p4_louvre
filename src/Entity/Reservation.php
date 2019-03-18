<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReservationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Reservation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mail;

    /**
     * visit day (the day people will go to louvre)
     * 
     * @ORM\Column(type="date")
     */
    private $visitDay;

    /**
     * the date and time booking were made (do not mistake this for visit day)
     * 
     * @ORM\Column(type="datetime")
     */
    private $dateBookingWereMade;

    /**
     * @ORM\Column(type="boolean")
     */
    private $halfDay;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $random;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Person", inversedBy="reservations", cascade={"persist"})
     */
    private $persons;

    private $temporaryPersonsList;

    /**
     * prepare slug before persist or update. Not 100% sure it's unique (but almost) I would use id instead of mail, but can't access id before it's flushed. Would like to flush, then in PostPersist and PostUpdate, get id, modify slug, then persist and flush again, but symfony is made so we can't use a manager in an entity method. I choosed to replace id with mail, this way even if it's not 100% sure unique, it's less abstract for user.
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function prepare()
    {
        if (empty($this->random))
        {
            $random = 'azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN1234567890';
            $this->random = substr(str_shuffle($random), 0, 5);
        }
        if (empty($this->slug))
            $this->slug = (new Slugify())->slugify($this->mail . ' ' . $this->random);
        if (empty($this->dateBookingWereMade))
            $this->dateBookingWereMade = new \DateTime();
    }

    public function __construct()
    {
        $this->persons = new ArrayCollection();
        $this->temporaryPersonsList = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): self
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL))
            $this->mail = $mail;
        else
            $this->mail = "invalid email";

        return $this;
    }

    public function getVisitDay(): ?\DateTimeInterface
    {
        return $this->visitDay;
    }

    public function stringVisitDay(): ?string
    {
        return $this->visitDay->format('d/m/Y');
    }

    public function setVisitDay(\DateTimeInterface $visitDay): self
    {
        if ($visitDay >= new \DateTime())
            $this->visitDay = $visitDay;
        else
            throw new \Exception('date de visite passée');

        return $this;
    }

    public function getHalfDay(): ?bool
    {
        return $this->halfDay;
    }

    public function setHalfDay(bool $halfDay): self
    {
        $this->halfDay = $halfDay;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getRandom(): ?string
    {
        return $this->random;
    }

    public function setRandom(string $random): self
    {
        $this->random = $random;

        return $this;
    }

    /**
     * @return Collection|Person[]
     */
    public function getPersons(): Collection
    {
        return $this->persons;
    }

    public function addPerson(Person $person): self
    {
        if (!$this->persons->contains($person)) {
            $this->persons[] = $person;
        }

        return $this;
    }

    public function removePerson(Person $person): self
    {
        if ($this->persons->contains($person)) {
            $this->persons->removeElement($person);
        }

        return $this;
    }

    /**
     * return the booking code, made with :
     * - the id of the reservation this way we're sure the booking code will be unique
     * - the 3 first letters of the mail this way a human can easily recognozie the reservation with the booking code
     * - the day of the reservation with formate ddmmyy this way humans can get infos about reservation just when customer give booking code
     * - some random letters and numbers, this way it get harder for malicious visitors to print a fake ticket with a booking code that have chance to already exists in the database. Also usefull if for some reason the database is erased and reservation id get back to 1, this way we could have twice the same id on tickets.
     *
     * @return void
     */
    public function getBookingCode()
    {
        $codemail = substr($this->mail, 0, 1); // 3 first letters of mail
        $length = 1;
        $i = 1;
        $lettres = 'azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN';
        while ($length < 3 && $i < strlen($this->mail))
        {
            if (strstr ($lettres, substr($this->mail, $i, 1)))
            {
                $codemail .= substr($this->mail, $i, 1);
                $length++;
            }
            $i++;
        }

        return $this->id . $codemail . $this->visitDay->format('dmy') . $this->random;
    }

    public function price()
    {
        $price = 0;
        foreach($this->persons as $person)
        {
            if ($this->halfDay)
                $price += $person->priceHalfDay();
            else
                $price += $person->priceFullDay();
        }

        return $price;
    }

    /**
     * @return Collection|Person[]
     */
    public function getTemporaryPersonsList(): Collection
    {
        return $this->temporaryPersonsList;
    }

    public function addTemporaryPersonsList(Person $temporaryPersonsList): self
    {
        if (!$this->temporaryPersonsList->contains($temporaryPersonsList)) {
            $this->temporaryPersonsList[] = $temporaryPersonsList;
        }

        return $this;
    }

    public function removeTemporaryPersonsList(Person $temporaryPersonsList): self
    {
        if ($this->temporaryPersonsList->contains($temporaryPersonsList)) {
            $this->temporaryPersonsList->removeElement($temporaryPersonsList);
        }

        return $this;
    }

    public function getDateBookingWereMade(): ?\DateTimeInterface
    {
        return $this->dateBookingWereMade;
    }

    public function setDateBookingWereMade(\DateTimeInterface $dateBookingWereMade): self
    {
        $this->dateBookingWereMade = $dateBookingWereMade;

        return $this;
    }
}
