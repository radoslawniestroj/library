<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\LoanRepository;

class BookService
{
    public function __construct(
        private readonly LoanRepository $loanRepository
    ) {}

    public function isBookBorrowedByUser(Book $book, User $user): bool
    {
        return $this->loanRepository->findOneBy([
                'book' => $book,
                'user' => $user,
                'returnDate' => null,
            ]) !== null;
    }

    public function areAllCopiesLoaned(Book $book): bool
    {
        return $this->getActiveLoanCount($book) >= $book->getCopiesNumber();
    }

    public function isBookAvailableForLoan(Book $book): bool
    {
        return $this->getActiveLoanCount($book) < $book->getCopiesNumber();
    }

    private function getActiveLoanCount(Book $book): int
    {
        return $this->loanRepository->count([
            'book' => $book,
            'returnDate' => null,
        ]);
    }
}
