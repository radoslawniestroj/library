<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoanRequestDto
{
    #[Assert\NotBlank(message: "Book ID is required")]
    #[Assert\Positive(message: "Book ID must be a positive number")]
    public ?int $bookId = null;
}
