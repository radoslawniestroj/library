<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BundleWebTestCase;

class WebTestCase extends BundleWebTestCase
{
    public function getAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'johndoe@email.com',
            'password' => 'Password123',
        ]));

        $data = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'] ?? null;

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
        return $client;
    }
}
