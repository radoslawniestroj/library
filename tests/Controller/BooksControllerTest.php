<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class BooksControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = $this->getAuthenticatedClient();
        $client->request('GET', '/api/books');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testGetBook(): void
    {
        $client = $this->getAuthenticatedClient();
        $client->request('GET', '/api/books/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
    }

    public function testCreateBook(): void
    {
        $client = $this->getAuthenticatedClient();
        $client->request(
            'POST',
            '/api/books',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Book',
                'author' => 'John Doe',
                'isbn' => 'some test isbn',
                'publicationYear' => 2000,
                'copiesNumber' => 5,
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseFormatSame('json');
    }
}
