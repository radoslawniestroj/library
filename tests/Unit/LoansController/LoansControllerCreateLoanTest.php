<?php

namespace App\Tests\Unit\LoansController;

use App\Config\UserType;
use App\Controller\LoansController;
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

class LoansControllerCreateLoanTest extends WebTestCase
{
    public function testCreateLoan(): void
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
        $dto = new LoanRequestDto();
        $dto->bookId = 1;

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $bookRepository = $this->createMock(BookRepository::class);
        $bookRepository->expects($this->once())
            ->method('find')
            ->with($dto->bookId)
            ->willReturn($book);

        $loanService = $this->createMock(LoanService::class);
        $loanService->expects($this->once())
            ->method('borrowBook')
            ->with($user, $book)
            ->willReturn($loan);

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $this->createMock(LoanRepository::class),
                $bookRepository,
                $loanService,
                $serializer,
                $validator
            ])
            ->onlyMethods(['json', 'getUser'])
            ->getMock();
        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->once())
            ->method('json')
            ->with($loan, Response::HTTP_CREATED, [], ['groups' => ['loan:read']])
            ->willReturn(new JsonResponse($loanData, Response::HTTP_CREATED));

        $request = new Request(content: json_encode(['bookId' => 1]));
        $response = $controller->createLoan($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($loanData, $data);
    }

    public function testCreateLoanValidationErrors(): void
    {
        $dto = new LoanRequestDto();
        $dto->bookId = 1;
        $errors = ['errors' => ['Invalid data']];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getMessage')->willReturn('Invalid data');
        $violations = new ConstraintViolationList([$violation]);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $this->createMock(LoanRepository::class),
                $this->createMock(BookRepository::class),
                $this->createMock(LoanService::class),
                $serializer,
                $validator
            ])
            ->onlyMethods(['json', 'getUser'])
            ->getMock();

        $controller->expects($this->once())
            ->method('json')
            ->with($violations, Response::HTTP_BAD_REQUEST)
            ->willReturn(new JsonResponse($errors, Response::HTTP_BAD_REQUEST));

        $request = new Request(content: '{}');
        $response = $controller->createLoan($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }

    public function testCreateLoanBookNotFound(): void
    {
        $userData = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'email@email.com',
            'password' => 'sample_password',
            'type' => UserType::LIBRARIAN
        ];
        $user = new User($userData);

        $dto = new LoanRequestDto();
        $dto->bookId = 1;
        $errors = ['errors' => ['Book not found']];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $bookRepository = $this->createMock(BookRepository::class);
        $bookRepository->expects($this->once())
            ->method('find')
            ->with($dto->bookId)
            ->willReturn(null);

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $this->createMock(LoanRepository::class),
                $bookRepository,
                $this->createMock(LoanService::class),
                $serializer,
                $validator
            ])
            ->onlyMethods(['json', 'getUser'])
            ->getMock();
        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Book not found'], Response::HTTP_NOT_FOUND)
            ->willReturn(new JsonResponse($errors, Response::HTTP_NOT_FOUND));

        $request = new Request(content: '{}');
        $response = $controller->createLoan($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }

    public function testCreateLoanRuntimeException(): void
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

        $dto = new LoanRequestDto();
        $dto->bookId = 1;
        $errors = ['errors' => ['Already borrowed']];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($dto);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $bookRepository = $this->createMock(BookRepository::class);
        $bookRepository->expects($this->once())
            ->method('find')
            ->with($dto->bookId)
            ->willReturn($book);

        $loanService = $this->createMock(LoanService::class);
        $loanService->expects($this->once())
            ->method('borrowBook')
            ->with($user, $book)
            ->willThrowException(new RuntimeException('Already borrowed'));

        $controller = $this->getMockBuilder(LoansController::class)
            ->setConstructorArgs([
                $this->createMock(LoanRepository::class),
                $bookRepository,
                $loanService,
                $serializer,
                $validator
            ])
            ->onlyMethods(['json', 'getUser'])
            ->getMock();

        $controller->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Already borrowed'], Response::HTTP_BAD_REQUEST)
            ->willReturn(new JsonResponse($errors, Response::HTTP_BAD_REQUEST));

        $request = new Request(content: json_encode(['bookId' => 1]));
        $response = $controller->createLoan($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }
}
