<?php
// src/Entity/Reservation.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')] 
#[Vich\Uploadable] 
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idReservation')]  // Match exactly your database column name
    private ?int $idReservation = null;

    #[ORM\Column(name: 'idUtilisateur')]  // Match exactly your database column name
    private ?int $idUtilisateur = null;

    #[Vich\UploadableField(mapping: 'reservation_image', fileNameProperty: 'imageName', size: 'imageSize')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;


    #[ORM\Column(name: 'dateReservation', type: 'datetime')]
    private ?\DateTimeInterface $dateReservation = null;

    #[ORM\Column(name: 'dateExpiration', type: 'datetime')]
    private ?\DateTimeInterface $dateExpiration = null;

    #[ORM\Column(name: 'statut')]  // Match exactly your database column name
    private ?string $statut = null;

    #[ORM\Column(name: 'vehicleType')]  // Match exactly your database column name
    private ?string $vehicleType = null;

    #[ORM\Column(name: 'carWashType', nullable: true)]  // Match exactly your database column name
    private ?string $carWashType = null;

    #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'price', type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    // Adding the ManyToOne relationship with PlaceParking
    #[ORM\ManyToOne(targetEntity: PlaceParking::class)]
    #[ORM\JoinColumn(name: 'idParking', referencedColumnName: 'id', nullable: false)]
    private ?PlaceParking $placeParking = null;
    // Getters and Setters

    public function getIdReservation(): ?int
    {
        return $this->idReservation;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->idUtilisateur;
    }

    public function setIdUtilisateur(int $idUtilisateur): self
    {
        $this->idUtilisateur = $idUtilisateur;
        return $this;
    }





    public function getDateReservation(): ?\DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeInterface $dateExpiration): self
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getVehicleType(): ?string
    {
        return $this->vehicleType;
    }

    public function setVehicleType(string $vehicleType): self
    {
        $this->vehicleType = $vehicleType;
        return $this;
    }

    public function getCarWashType(): ?string
    {
        return $this->carWashType;
    }

    public function setCarWashType(?string $carWashType): self
    {
        $this->carWashType = $carWashType;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    // Getter for the related PlaceParking
    public function getPlaceParking(): ?PlaceParking
    {
        return $this->placeParking;
    }

    public function setPlaceParking(?PlaceParking $placeParking): self
    {
        $this->placeParking = $placeParking;
        return $this;
    }
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It's required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }
}
