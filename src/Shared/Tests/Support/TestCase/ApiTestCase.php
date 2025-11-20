<?php
// tests/Support/TestCase/ApiTestCase.php

namespace App\Shared\Tests\Support\TestCase;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class ApiTestCase extends WebTestCase
{
    use ResetDatabase, Factories, MailerAssertionsTrait;

    protected ?KernelBrowser $client = null;
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->entityManager = null;
    }

    protected function authenticateRequest(string $token): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', "Bearer {$token}");
    }

    protected function postJson(string $uri, array $payload = []): Response
    {
        $this->client->request('POST', $uri, [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], json_encode($payload));

        return $this->client->getResponse();
    }

    protected function getJson(string $uri): Response
    {
        $this->client->request('GET', $uri, [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        return $this->client->getResponse();
    }

    protected function putJson(string $uri, array $payload = []): Response
    {
        $this->client->request('PUT', $uri, [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], json_encode($payload));

        return $this->client->getResponse();
    }

    protected function patchJson(string $uri, array $payload = []): Response
    {
        $this->client->request('PATCH', $uri, [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], json_encode($payload));

        return $this->client->getResponse();
    }

    protected function deleteJson(string $uri): Response
    {
        $this->client->request('DELETE', $uri, [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        return $this->client->getResponse();
    }

    protected function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    protected function getResponseStatus(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    protected function refresh(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $realEntity = method_exists($entity, '_real') ? $entity->_real() : $entity;
            $this->entityManager->refresh($realEntity);
        }
    }

    protected function assertJsonContains(array $expected): void
    {
        $responseData = $this->getResponseData();

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $responseData, "Key '$key' not found in response");
            
            if (is_callable($value)) {
                $this->assertTrue(
                    $value($responseData[$key]),
                    "Callable assertion failed for key '$key'"
                );
            } else {
                $this->assertEquals(
                    $value,
                    $responseData[$key],
                    "Value mismatch for key '$key'"
                );
            }
        }
    }
}
