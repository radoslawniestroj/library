<?php

namespace App\Tests\Unit\UsersController;

use App\Config\UserType;
use App\Controller\LoansController;
use App\Controller\UsersController;
use App\Dto\LoanRequestDto;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Service\LoanService;
use DateTime;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UsersControllerGetUserLoansTest extends WebTestCase
{
    public function testGetUserLoans(): void
    {
        $bookData = [
            'title' => 'Title',
            'author' => 'John Doe',
            'isbn' => 'isbn',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];
        $book = new Book($bookData);

        $userData = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'email@email.com',
            'password' => 'sample_password',
            'type' => UserType::LIBRARIAN
        ];
        $user = new User($userData);

        $loanData = [
            'book' => $book,
            'user' => $user,
            'borrow_date' => new DateTime('2024-11-01'),
            'return_date' => null,
            'status' => 'borrowed'
        ];

        $loan = new Loan($loanData);
        $loanData['borrow_date'] = '2024-11-01';
        $userData['type'] = UserType::LIBRARIAN->value;
        $loanData['book'] = $bookData;
        $loanData['user'] = $userData;
        $loans = [$loan];
        $userId = 1;

        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $userId])
            ->willReturn($loans);

        $controller = $this->getMockBuilder(UsersController::class)
            ->setConstructorArgs([
                $loanRepository
            ])
            ->onlyMethods(['json', 'getUser', 'isGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->any())
            ->method('isGranted')
            ->with('ROLE_LIBRARIAN')
            ->willReturn(true);

        $controller->expects($this->once())
            ->method('json')
            ->with($loans, Response::HTTP_OK, [], ['groups' => ['loan:read']])
            ->willReturn(new JsonResponse([$loanData], Response::HTTP_OK));

        $response = $controller->getUserLoans($userId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals([$loanData], $data);
    }

    public function testCreateLoanValidationErrors(): void
    {
        $bookData = [
            'title' => 'Title',
            'author' => 'John Doe',
            'isbn' => 'isbn',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];
        $book = new Book($bookData);

        $userData = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'email@email.com',
            'password' => 'sample_password',
            'type' => UserType::MEMBER
        ];
        $user = new User($userData);

        $loanData = [
            'book' => $book,
            'user' => $user,
            'borrow_date' => new DateTime('2024-11-01'),
            'return_date' => null,
            'status' => 'borrowed'
        ];

        $loan = new Loan($loanData);
        $userId = 1;
        $errors = ['errors' => ['Access denied']];

        $controller = $this->getMockBuilder(UsersController::class)
            ->setConstructorArgs([
                $this->createMock(LoanRepository::class)
            ])
            ->onlyMethods(['json', 'getUser', 'isGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->any())
            ->method('isGranted')
            ->with('ROLE_LIBRARIAN')
            ->willReturn(false);

        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Access denied'], Response::HTTP_FORBIDDEN)
            ->willReturn(new JsonResponse($errors, Response::HTTP_FORBIDDEN));

        $response = $controller->getUserLoans($userId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }
}
