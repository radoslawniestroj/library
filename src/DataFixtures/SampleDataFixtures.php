<?php

namespace App\DataFixtures;

use App\Config\UserType;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SampleDataFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $book1 = $this->createBook('The Witcher: The Last Wish', 'Andrzej Sapkowski',
            '978-0-575-08244-1', 1993, 1);
        $manager->persist($book1);

        $book2 = $this->createBook('The Witcher: Blood of Elves', 'Andrzej Sapkowski',
            '978-0-575-08484-1', 1994, 1);
        $manager->persist($book2);

        $book3 = $this->createBook('The Lord of the Rings: The Fellowship of the Ring', 'John Ronald Reuel Tolkien',
            '978-0547928210', 1954, 2);
        $manager->persist($book3);

        $book4 = $this->createBook('The Lord of the Rings: The Two Towers', 'John Ronald Reuel Tolkien',
            '978-0547928203', 1962, 2);
        $manager->persist($book4);

        $book5 = $this->createBook('The Lord of the Rings: The Return of the King', 'John Ronald Reuel Tolkien',
            '978-0547928197', 1963, 2);
        $manager->persist($book5);


        $librarian = $this->createUser('John', 'Doe', 'johndoe@email.com', UserType::LIBRARIAN);
        $manager->persist($librarian);

        $member1 = $this->createUser('James', 'Smith', 'jamessmith@email.com', UserType::MEMBER);
        $manager->persist($member1);

        $member2 = $this->createUser('Lara', 'Croft', 'laracroft@email.com', UserType::MEMBER);
        $manager->persist($member2);


        $loan1 = $this->createLoan($book1, $librarian, '2024-11-01', '2024-12-11', 'RETURNED');
        $manager->persist($loan1);

        $loan2 = $this->createLoan($book1, $member1, '2025-03-07', '2025-03-30', 'RETURNED');
        $manager->persist($loan2);

        $loan3 = $this->createLoan($book2, $member1, '2024-12-22', '2025-01-06', 'RETURNED');
        $manager->persist($loan3);

        $loan4 = $this->createLoan($book2, $member2, '2025-02-11', '2025-02-25', 'RETURNED');
        $manager->persist($loan4);

        $loan5 = $this->createLoan($book3, $librarian, '2024-07-19', '2024-09-22', 'RETURNED');
        $manager->persist($loan5);

        $loan6 = $this->createLoan($book3, $member2, '2024-10-04', '2024-11-16', 'RETURNED');
        $manager->persist($loan6);

        $loan7 = $this->createLoan($book3, $member1, '2025-06-08', null, 'BORROWED');
        $manager->persist($loan7);

        $loan8 = $this->createLoan($book4, $librarian, '2024-05-12', '2024-07-11', 'RETURNED');
        $manager->persist($loan8);

        $loan9 = $this->createLoan($book4, $member2, '2025-06-12', null, 'BORROWED');
        $manager->persist($loan9);

        $loan10 = $this->createLoan($book5, $member2, '2025-06-12', null, 'BORROWED');
        $manager->persist($loan10);


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
