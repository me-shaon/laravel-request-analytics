<?php

declare(strict_types=1);

namespace MeShaon\RequestAnalytics\Tests\Unit;

use MeShaon\RequestAnalytics\DTO\RequestDataDTO;
use MeShaon\RequestAnalytics\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RequestDataDTOTest extends TestCase
{
    #[Test]
    public function it_creates_dto_with_all_properties(): void
    {
        $path = '/test';
        $content = '<html>Test</html>';
        $browserInfo = ['browser' => 'Chrome', 'os' => 'Windows', 'device' => 'Desktop'];
        $ipAddress = '192.168.1.1';
        $referrer = 'https://example.com';
        $country = 'US';
        $city = 'New York';
        $language = 'en-US';
        $queryParams = '{"param":"value"}';
        $httpMethod = 'GET';
        $responseTime = 123.45;
        $requestCategory = 'web';
        $sessionId = 'session_123';
        $visitorId = 'visitor_456';

        $dto = new RequestDataDTO(
            $path,
            $content,
            $browserInfo,
            $ipAddress,
            $referrer,
            $country,
            $city,
            $language,
            $queryParams,
            $httpMethod,
            $responseTime,
            $requestCategory,
            $sessionId,
            $visitorId
        );

        $this->assertEquals($path, $dto->path);
        $this->assertEquals($content, $dto->content);
        $this->assertEquals($browserInfo, $dto->browserInfo);
        $this->assertEquals($ipAddress, $dto->ipAddress);
        $this->assertEquals($referrer, $dto->referrer);
        $this->assertEquals($country, $dto->country);
        $this->assertEquals($city, $dto->city);
        $this->assertEquals($language, $dto->language);
        $this->assertEquals($queryParams, $dto->queryParams);
        $this->assertEquals($httpMethod, $dto->httpMethod);
        $this->assertEquals($responseTime, $dto->responseTime);
        $this->assertEquals($requestCategory, $dto->requestCategory);
        $this->assertEquals($sessionId, $dto->sessionId);
        $this->assertEquals($visitorId, $dto->visitorId);
    }

    #[Test]
    public function it_creates_dto_with_default_session_and_visitor_ids(): void
    {
        $dto = new RequestDataDTO(
            '/test',
            'content',
            [],
            '127.0.0.1',
            '',
            '',
            '',
            '',
            '{}',
            'GET',
            0.0,
            'api'
        );

        $this->assertEquals('', $dto->sessionId);
        $this->assertEquals('', $dto->visitorId);
    }

    #[Test]
    public function it_allows_access_to_all_public_properties(): void
    {
        $dto = new RequestDataDTO(
            '/api/test',
            '{"message":"hello"}',
            ['browser' => 'Firefox', 'os' => 'Linux', 'device' => 'Mobile'],
            '10.0.0.1',
            'https://referrer.com',
            'CA',
            'Toronto',
            'en-CA',
            '{"search":"test"}',
            'POST',
            250.75,
            'api',
            'sess_789',
            'vis_101112'
        );

        // Test that all properties are accessible
        $this->assertIsString($dto->path);
        $this->assertIsString($dto->content);
        $this->assertIsArray($dto->browserInfo);
        $this->assertIsString($dto->ipAddress);
        $this->assertIsString($dto->referrer);
        $this->assertIsString($dto->country);
        $this->assertIsString($dto->city);
        $this->assertIsString($dto->language);
        $this->assertIsString($dto->queryParams);
        $this->assertIsString($dto->httpMethod);
        $this->assertIsFloat($dto->responseTime);
        $this->assertIsString($dto->requestCategory);
        $this->assertIsString($dto->sessionId);
        $this->assertIsString($dto->visitorId);
    }

    #[Test]
    public function it_handles_empty_and_null_values_gracefully(): void
    {
        $dto = new RequestDataDTO(
            '',
            '',
            [],
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            0.0,
            ''
        );

        $this->assertEquals('', $dto->path);
        $this->assertEquals('', $dto->content);
        $this->assertEquals([], $dto->browserInfo);
        $this->assertEquals('', $dto->ipAddress);
        $this->assertEquals('', $dto->referrer);
        $this->assertEquals('', $dto->country);
        $this->assertEquals('', $dto->city);
        $this->assertEquals('', $dto->language);
        $this->assertEquals('', $dto->queryParams);
        $this->assertEquals('', $dto->httpMethod);
        $this->assertEquals(0.0, $dto->responseTime);
        $this->assertEquals('', $dto->requestCategory);
        $this->assertEquals('', $dto->sessionId);
        $this->assertEquals('', $dto->visitorId);
    }
}
