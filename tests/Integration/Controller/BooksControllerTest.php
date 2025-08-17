<?php

namespace App\Tests\Integration\Controller;

use App\Controller\BooksController;
use App\Entity\Book;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BooksControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $expected = [[
            "title" => "test book title",
            "author" => "admin librarian",
            "isbn" => "123123123123",
            "publicationYear" => 2000,
            "copiesNumber" => 1
        ]];

        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findBy(['email' => 'librarian@admin.com']);
        $client->loginUser(reset($testUser));

        $client->request('GET', '/api/books');
        static::assertResponseIsSuccessful();

        static::assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($expected, $response);
    }

    public function testGetBook(): void
    {
        $expected = [
            "title" => "test book title",
            "author" => "admin librarian",
            "isbn" => "123123123123",
            "publicationYear" => 2000,
            "copiesNumber" => 1
        ];

        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findBy(['email' => 'librarian@admin.com']);
        $client->loginUser(reset($testUser));

        $client->request('GET', '/api/books/1');
        static::assertResponseIsSuccessful();

        static::assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($expected, $response);
    }

//    public function testCreateBook(): void
//    {
//        $expected = [
//            "title" => "test book title",
//            "author" => "admin librarian",
//            "isbn" => "123123123123",
//            "publicationYear" => 200,
//            "copiesNumber" => 1
//        ];
//
//        $client = static::createClient();
//        $userRepository = static::getContainer()->get(UserRepository::class);
//        $testUser = $userRepository->findBy(['email' => 'librarian@admin.com']);
//        $client->loginUser(reset($testUser));
//
//        $client->request('POST', '/api/books', [], [], [
//            'CONTENT_TYPE' => 'application/json',
//        ], json_encode($expected));
//
//        static::assertResponseHeaderSame('Content-Type', 'application/json');
//        $this->assertResponseFormatSame('json');
//
//        $response = json_decode($client->getResponse()->getContent(), true);
//        $this->assertEquals($expected, $response);
//    }
}
