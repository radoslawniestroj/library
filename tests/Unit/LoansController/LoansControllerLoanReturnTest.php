<?php

namespace App\Tests\Unit\LoansController;

use App\Config\UserType;
use App\Controller\LoansController;
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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LoansControllerLoanReturnTest extends WebTestCase
{
    public function testReturnLoan(): void
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
        $loanId = 1;

        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->expects($this->once())
            ->method('find')
            ->with($loanId)
            ->willReturn($loan);

        $loanService = $this->createMock(LoanService::class);
        $loanService->expects($this->once())
            ->method('returnBook')
            ->with($loan);

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $loanRepository,
                $this->createMock(BookRepository::class),
                $loanService,
                $this->createMock(SerializerInterface::class),
                $this->createMock(ValidatorInterface::class)
            ])
            ->onlyMethods(['json', 'getUser', 'isGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->once())
            ->method('json')
            ->with($loan, Response::HTTP_OK, [], ['groups' => ['loan:read']])
            ->willReturn(new JsonResponse($loanData, Response::HTTP_OK));

        $response = $controller->returnLoan($loanId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($loanData, $data);
    }

    public function testReturnLoanDoesNotExists(): void
    {
        $loanId = 1;
        $errors = ['errors' => ['Loan not found']];

        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->expects($this->once())
            ->method('find')
            ->with($loanId)
            ->willReturn(null);

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $loanRepository,
                $this->createMock(BookRepository::class),
                $this->createMock(LoanService::class),
                $this->createMock(SerializerInterface::class),
                $this->createMock(ValidatorInterface::class)
            ])
            ->onlyMethods(['json', 'getUser'])
            ->getMock();

        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Loan not found'], Response::HTTP_NOT_FOUND)
            ->willReturn(new JsonResponse($errors, Response::HTTP_NOT_FOUND));

        $response = $controller->returnLoan($loanId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }

    public function testReturnLoanAccessDenied(): void
    {
        $bookData = [
            'title' => 'Title',
            'author' => 'John Doe',
            'isbn' => 'isbn',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];
        $book = new Book($bookData);

        $userData1 = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'email@email.com',
            'password' => 'sample_password',
            'type' => UserType::LIBRARIAN
        ];
        $user1 = new User($userData1);

        $userData2 = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'email@email.com',
            'password' => 'sample_password',
            'type' => UserType::MEMBER
        ];
        $user2 = new User($userData2);

        $loanData = [
            'book' => $book,
            'user' => $user1,
            'borrow_date' => new DateTime('2024-11-01'),
            'return_date' => null,
            'status' => 'borrowed'
        ];

        $loan = new Loan($loanData);
        $loanId = 1;
        $errors = ['errors' => ['Access denied']];

        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->expects($this->once())
            ->method('find')
            ->with($loanId)
            ->willReturn($loan);

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $loanRepository,
                $this->createMock(BookRepository::class),
                $this->createMock(LoanService::class),
                $this->createMock(SerializerInterface::class),
                $this->createMock(ValidatorInterface::class)
            ])
            ->onlyMethods(['json', 'getUser', 'isGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user2);

        $controller->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_LIBRARIAN')
            ->willReturn(false);

        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Access denied'], Response::HTTP_FORBIDDEN)
            ->willReturn(new JsonResponse($errors, Response::HTTP_FORBIDDEN));

        $response = $controller->returnLoan($loanId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }

    public function testReturnLoanRuntimeException(): void
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
        $loanId = 1;
        $errors = ['errors' => ['Access denied']];

        $loanRepository = $this->createMock(LoanRepository::class);
        $loanRepository->expects($this->once())
            ->method('find')
            ->with($loanId)
            ->willReturn($loan);

        $loanService = $this->createMock(LoanService::class);
        $loanService->expects($this->once())
            ->method('returnBook')
            ->willThrowException(new RuntimeException('Book has already been returned.'));

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $loanRepository,
                $this->createMock(BookRepository::class),
                $loanService,
                $this->createMock(SerializerInterface::class),
                $this->createMock(ValidatorInterface::class)
            ])
            ->onlyMethods(['json', 'getUser', 'isGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Book has already been returned.'], Response::HTTP_BAD_REQUEST)
            ->willReturn(new JsonResponse($errors, Response::HTTP_BAD_REQUEST));

        $response = $controller->returnLoan($loanId);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }
}
