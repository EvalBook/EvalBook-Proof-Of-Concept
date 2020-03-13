<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PeriodeRepository")
 */
class Periode
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=45)
     */
    private $name;

    /**
     * @ORM\Column(type="date")
     */
    private $dateStart;

    /**
     * @ORM\Column(type="date")
     */
    private $dateEnd;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Activite", mappedBy="idPeriode")
     */
    private $activites;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Implantation", inversedBy="periodes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $implantation;


    /**
     * Periode constructor.
     */
    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }


    /**
     * Return the Period ID.
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * Return the Period name.
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * Set the Period name.
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }


    /**
     * Return the Period start date.
     * @return \DateTimeInterface|null
     */
    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }


    /**
     * Set the Period start date.
     * @param \DateTimeInterface $dateStart
     * @return $this
     */
    public function setDateStart(\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;
        return $this;
    }

    /**
     * Return the Period end date.
     * @return \DateTimeInterface|null
     */
    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }


    /**
     * Set the Period end date.
     * @param \DateTimeInterface $dateEnd
     * @return $this
     */
    public function setDateEnd(\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }


    /**
     * Return true if the Period is still active ( current Period ).
     * @return bool|null
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }


    /**
     * Set the Period active state.
     * @param bool $active
     * @return $this
     */
    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }


    /**
     * Return a collection of Activite for the Period.
     * @return Collection|Activite[]
     */
    public function getActivites(): Collection
    {
        return $this->activites;
    }


    /**
     * Add an activite to the Period.
     * @param Activite $activite
     * @return $this
     */
    public function addActivite(Activite $activite): self
    {
        if (!$this->activites->contains($activite)) {
            $this->activites[] = $activite;
            $activite->setPeriode($this);
        }

        return $this;
    }


    /**
     * Remove an Activite from the Period.
     * @param Activite $activite
     * @return $this
     */
    public function removeActivite(Activite $activite): self
    {
        if ($this->activites->contains($activite)) {
            $this->activites->removeElement($activite);
            // set the owning side to null (unless already changed)
            if ($activite->getPeriode() === $this) {
                $activite->setPeriode(null);
            }
        }

        return $this;
    }


    /**
     * Return the Period implantation.
     * @return Implantation|null
     */
    public function getImplantation(): ?Implantation
    {
        return $this->implantation;
    }


    /**
     * Set the Period implantation.
     * @param Implantation|null $implantation
     * @return $this
     */
    public function setImplantation(?Implantation $implantation): self
    {
        $this->implantation = $implantation;
        return $this;
    }
}