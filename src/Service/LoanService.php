<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class LoanService
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function borrowBook(User $user, Book $book): Loan
    {
        if (!$this->bookService->isBookAvailableForLoan($book)) {
            throw new RuntimeException('Book is not available.');
        }

        $loan = new Loan();
        $loan->setUser($user);
        $loan->setBook($book);
        $loan->setBorrowDate(new DateTime());
        $loan->setStatus('BORROWED');

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        return $loan;
    }

    public function returnBook(Loan $loan): void
    {
        if ($loan->getReturnDate() !== null) {
            throw new RuntimeException('Book has already been returned.');
        }

        $loan->setReturnDate(new DateTime());
        $loan->setStatus('RETURNED');

        $this->entityManager->flush();
    }
}
