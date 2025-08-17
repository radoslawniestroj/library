<?php

namespace App\Tests\Unit\BooksController;

use App\Controller\BooksController;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BooksControllerCreateBookTest extends WebTestCase
{
    public function testCreateBook(): void
    {
        $bookData = [
            'title' => 'Title',
            'author' => 'John Doe',
            'isbn' => 'isbn',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];

        $book = new Book($bookData);
        $encodedBookData = json_encode($bookData);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with($encodedBookData, Book::class, 'json', [
                AbstractNormalizer::GROUPS => ['book:write']
            ])
            ->willReturn($book);


        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($book)
            ->willReturn(new ConstraintViolationList([]));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($book);
        $entityManager->expects($this->once())->method('flush');

        $controller = $this->getMockBuilder(BooksController::class)
            ->setConstructorArgs([
                $this->createMock(BookRepository::class),
                $serializer,
                $validator,
                $entityManager
            ])
            ->onlyMethods(['json'])
            ->getMock();

        $controller->expects($this->once())
            ->method('json')
            ->with($book, Response::HTTP_CREATED, [], ['groups' => ['book:read']])
            ->willReturn(new JsonResponse($bookData, Response::HTTP_CREATED));

        $request = new Request(content: $encodedBookData);
        $response = $controller->createBook($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($bookData, $data);
    }

    public function testCreateBookValidationErrors(): void
    {
        $bookData = [
            'title' => 'Title',
            'author' => 'John Doe',
            'isbn' => 'isbn',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];

        $book = new Book($bookData);
        $encodedBookData = json_encode($bookData);
        $errors = ['errors' => ['Error 1', 'Error 2']];

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->willReturn($book);

        $violation1 = $this->createMock(ConstraintViolationInterface::class);
        $violation1->method('getMessage')->willReturn('Error 1');

        $violation2 = $this->createMock(ConstraintViolationInterface::class);
        $violation2->method('getMessage')->willReturn('Error 2');

        $violations = [$violation1, $violation2];
        $violationList = new ConstraintViolationList($violations);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($book)
            ->willReturn($violationList);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist')->with($book);
        $entityManager->expects($this->never())->method('flush');

        $controller = $this->getMockBuilder(BooksController::class)
            ->setConstructorArgs([
                $this->createMock(BookRepository::class),
                $serializer,
                $validator,
                $entityManager
            ])
            ->onlyMethods(['json'])
            ->getMock();

        $controller->expects($this->once())
            ->method('json')
            ->with($violationList, Response::HTTP_BAD_REQUEST)
            ->willReturn(new JsonResponse($errors, Response::HTTP_BAD_REQUEST));

        $request = new Request(content: $encodedBookData);
        $response = $controller->createBook($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($errors, $data);
    }
}
