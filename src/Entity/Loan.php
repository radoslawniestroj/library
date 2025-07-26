<?php

namespace App\Entity;

use App\Repository\LoanRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LoanRepository::class)]
class Loan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read'])]
    private ?Book $book = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['loan:read'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['loan:read'])]
    private ?DateTime $borrowDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['loan:read'])]
    private ?DateTime $returnDate = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBorrowDate(): ?DateTime
    {
        return $this->borrowDate;
    }

    public function setBorrowDate(DateTime $borrowDate): static
    {
        $this->borrowDate = $borrowDate;

        return $this;
    }

    public function getReturnDate(): ?DateTime
    {
        return $this->returnDate;
    }

    public function setReturnDate(?DateTime $returnDate): static
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
