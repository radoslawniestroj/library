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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BooksControllerIndexTest extends WebTestCase
{
    public function testIndex(): void
    {
        $book1Data = [
            'title' => 'Title 1',
            'author' => 'John Doe',
            'isbn' => 'isbn1',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];

        $book2Data = [
            'title' => 'Title 2',
            'author' => 'John Doe',
            'isbn' => 'isbn2',
            'publication_year' => 2000,
            'copies_number' => 1,
        ];

        $request = Request::create('/api/books?title=Title&author=John Doe', 'GET');

        $book1 = new Book($book1Data);
        $book2 = new Book($book2Data);
        $books = [$book1, $book2];

        $bookRepository = $this->createMock(BookRepository::class);
        $bookRepository->expects($this->once())
            ->method('findByFilters')
            ->with('/api/books?title=Title&author=John Doe', 'Title', 'John Doe')
            ->willReturn($books);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($books, 'json', ['groups' => ['book:read']])
            ->willReturn(json_encode([$book1Data, $book2Data]));

        $controller = new BooksController(
            $bookRepository,
            $serializer,
            $this->createMock(ValidatorInterface::class),
            $this->createMock(EntityManagerInterface::class)
        );

        $response = $controller->index($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertEquals([$book1Data, $book2Data], $data);
    }
}
