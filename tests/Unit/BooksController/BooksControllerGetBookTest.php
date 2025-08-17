<?php

namespace App\Tests\Unit\BooksController;

use App\Controller\BooksController;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BooksControllerGetBookTest extends WebTestCase
{
    public function testGetBook(): void
    {
        $bookData = [
            'title' => 'Title',
            'author' => 'John Doe',
            'isbn' => 'isbn',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];

        $book = new Book($bookData);

        $bookRepository = $this->createMock(BookRepository::class);
        $bookRepository->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn($book);

        $controller = $this->getMockBuilder(BooksController::class)
            ->setConstructorArgs([
                $bookRepository,
                $this->createMock(SerializerInterface::class),
                $this->createMock(ValidatorInterface::class),
                $this->createMock(EntityManagerInterface::class)
            ])
            ->onlyMethods(['json'])
            ->getMock();
        $controller->expects($this->once())
            ->method('json')
            ->with($book, Response::HTTP_OK, [], ['groups' => ['book:read']])
            ->willReturn(new JsonResponse($bookData));

        $response = $controller->getBook(0);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($bookData, $data);
    }

    public function testGetBookNotFound(): void
    {
        $bookRepository = $this->createMock(BookRepository::class);
        $bookRepository->expects($this->once())
            ->method('find')
            ->with(99999999)
            ->willReturn(null);

        $controller = $this->getMockBuilder(BooksController::class)
            ->setConstructorArgs([
                $bookRepository,
                $this->createMock(SerializerInterface::class),
                $this->createMock(ValidatorInterface::class),
                $this->createMock(EntityManagerInterface::class)
            ])
            ->onlyMethods(['json'])
            ->getMock();
        $controller->expects($this->once())
            ->method('json')
            ->with(['error' => 'Book not found'], Response::HTTP_NOT_FOUND)
            ->willReturn(new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND));

        $response = $controller->getBook(99999999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(['error' => 'Book not found'], $data);
    }
}
