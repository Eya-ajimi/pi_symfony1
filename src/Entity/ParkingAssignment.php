<?php
// src/Entity/ParkingAssignment.php

namespace App\Entity;

use App\Repository\ParkingAssignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents an assignment of a phone number to a parking spot at a specific time.
 */
#[ORM\Entity(repositoryClass: ParkingAssignmentRepository::class)]
// IMPORTANT: Verify 'parking_assignments' matches your EXACT database table name.
// The previous error message indicated 'parking_assignments' (plural).
// If your table is actually 'parking_assignment' (singular), change it below.
#[ORM\Table(name: 'parking_assignments')]
#[ORM\Index(columns: ["phone_number", "scanned_at"], name: "IDX_PHONE_SCANNED")] // Index for faster lookups by phone number
class ParkingAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue] // Use default strategy (auto-increment for MySQL)
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * The parking spot associated with this assignment.
     */
    #[ORM\ManyToOne(targetEntity: PlaceParking::class)] // Establishes the relationship
    #[ORM\JoinColumn(
        name: "placeparking_id",         // *** THIS IS THE KEY FIX: Explicitly matches your DB column name ***
        referencedColumnName: "id",     // Column in PlaceParking table to link to (usually 'id')
        nullable: false                 // An assignment must always have a parking spot
    )]
    private ?PlaceParking $placeParking = null;

    /**
     * The phone number provided by the user when scanning.
     */
    #[ORM\Column(type: 'string', length: 20, nullable: false)] // Adjust length if needed, ensure it's not nullable
    private ?string $phoneNumber = null;

    /**
     * The exact date and time when the QR code was scanned and the assignment was created.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)] // Use DATETIME type, ensure it's not nullable
    private ?\DateTimeInterface $scannedAt = null;

    // --- Getters and Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaceParking(): ?PlaceParking
    {
        return $this->placeParking;
    }

    public function setPlaceParking(?PlaceParking $placeParking): self
    {
        $this->placeParking = $placeParking;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * Sets the phone number. Optionally normalizes it by removing non-digit characters.
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        // Optional: Basic normalization to remove spaces, dashes, parentheses
        $this->phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        return $this;
    }

    public function getScannedAt(): ?\DateTimeInterface
    {
        return $this->scannedAt;
    }

    public function setScannedAt(\DateTimeInterface $scannedAt): self
    {
        $this->scannedAt = $scannedAt;
        return $this;
    }

    // --- Lifecycle Callbacks ---

    /**
     * Automatically set the scannedAt timestamp when the entity is first persisted,
     * if it hasn't been set already.
     */
    #[ORM\PrePersist]
    public function setScannedAtValue(): void
    {
        // Only set if not already explicitly set (e.g., during testing or specific scenarios)
        if ($this->scannedAt === null) {
            $this->scannedAt = new \DateTime(); // Sets to current date and time
        }
    }
}