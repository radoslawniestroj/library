<?php

namespace App\DataFixtures\Test;

use App\Config\UserType;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SampleDataFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly KernelInterface $kernel
    ) {
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    public function load(ObjectManager $manager): void
    {
//        if ('test' !== $this->kernel->getEnvironment()) {
//            return;
//        }

        $book1 = $this->createBook('test book title', 'admin librarian',
            '123123123123', 200, 1);
        $manager->persist($book1);

        $librarian = $this->createUser('admin', 'librarian', 'librarian@admin.com', UserType::LIBRARIAN);
        $manager->persist($librarian);

        $loan1 = $this->createLoan($book1, $librarian, '2024-01-01', '2025-01-01', 'RETURNED');
        $manager->persist($loan1);

        $manager->flush();
    }

    private function createBook(
        string $title,
        string $author,
        string $isbn,
        string $releaseYear,
        string $numberOfCopies
    ): Book {
        $book = new Book();
        $book->setTitle($title);
        $book->setAuthor($author);
        $book->setISBN($isbn);
        $book->setPublicationYear($releaseYear);
        $book->setCopiesNumber($numberOfCopies);

        return $book;
    }

    private function createUser(
        string $name,
        string $lastName,
        string $email,
        UserType $type
    ): User {
        $user = new User();
        $user->setName($name);
        $user->setSurname($lastName);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Password123'));
        $user->setType($type);

        return $user;
    }

    /**
     * @throws Exception
     */
    private function createLoan(
        Book $book,
        User $user,
        string $borrowDate,
        ?string $returnDate,
        string $status
    ): Loan {
        $borrowDate = new DateTime($borrowDate);
        $returnDate = $returnDate ? new DateTime($returnDate) : $returnDate;

        $loan = new Loan();
        $loan->setBook($book);
        $loan->setUser($user);
        $loan->setBorrowDate($borrowDate);
        $loan->setReturnDate($returnDate);
        $loan->setStatus($status);

        return $loan;
    }
}
