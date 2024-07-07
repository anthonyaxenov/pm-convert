<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PmConverter\Exceptions\EmptyHttpVerbException;
use PmConverter\Exceptions\InvalidHttpVersionException;

class AbstractRequestTest extends TestCase
{
    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\HttpVersion
     * @return void
     * @throws InvalidHttpVersionException
     */
    public function testHttpVersion(): void
    {
        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setHttpVersion(2.0);

        $this->assertSame(2.0, $request->getHttpVersion());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getVerb()
     * @covers PmConverter\HttpVersion
     * @return void
     * @throws InvalidHttpVersionException
     */
    public function testInvalidHttpVersionException(): void
    {
        $this->expectException(InvalidHttpVersionException::class);

        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setHttpVersion(5);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setVerb()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getVerb()
     * @return void
     * @throws EmptyHttpVerbException
     */
    public function testVerb(): void
    {
        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setVerb('GET');

        $this->assertSame('GET', $request->getVerb());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getVerb()
     * @return void
     * @throws EmptyHttpVerbException
     */
    public function testEmptyHttpVerbException(): void
    {
        $this->expectException(EmptyHttpVerbException::class);

        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->getVerb();
    }


    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setUrl()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getRawUrl()
     * @return void
     */
    public function testUrl(): void
    {
        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setUrl('http://localhost');

        $this->assertSame('http://localhost', $request->getRawUrl());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setName()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getName()
     * @return void
     */
    public function testName(): void
    {
        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setName('lorem ipsum');

        $this->assertSame('lorem ipsum', $request->getName());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setDescription()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getDescription()
     * @return void
     */
    public function testDescription(): void
    {
        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setDescription("lorem ipsum\ndolor sit\namet");

        $this->assertSame("lorem ipsum\ndolor sit\namet", $request->getDescription());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setBodymode()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getBodymode()
     * @return void
     */
    public function testBodyMode(): void
    {
        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setBodymode('raw');

        $this->assertSame('raw', $request->getBodymode());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setHeaders()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setHeader()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getHeaders()
     * @return void
     */
    public function testHeaders(): void
    {
        $headers = [
            (object)[
                'key' => 'Header 1',
                'value' => 'Value 1',
                'disabled' => true,
            ],
            (object)[
                'key' => 'Header 2',
                'value' => 'Value 2',
                'disabled' => false,
            ],
            (object)[
                'key' => 'Header 3',
                'value' => 'Value 3',
            ],
        ];
        $expected = [
            'Header 1' => [
                'value' => 'Value 1',
                'disabled' => true,
            ],
            'Header 2' => [
                'value' => 'Value 2',
                'disabled' => false,
            ],
            'Header 3' => [
                'value' => 'Value 3',
                'disabled' => false,
            ],
            'Header 4' => [
                'value' => 'Value 4',
                'disabled' => false,
            ],
            'Header 5' => [
                'value' => 'Value 5',
                'disabled' => true,
            ],
        ];

        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setHeaders($headers)
            ->setHeader('Header 4', 'Value 4')
            ->setHeader('Header 5', 'Value 5', true);

        $this->assertSame($expected, $request->getHeaders());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setAuth()
     * @return void
     */
    public function testAuthBearer(): void
    {
        $auth = (object)[
            'type' => 'bearer',
            'bearer' => [
                (object)[
                    'key' => 'token',
                    'value' => 'qwerty',
                    'type' => 'string',
                ]
            ]
        ];
        $expected = [
            'Authorization' => [
                'value' => 'Bearer qwerty',
                'disabled' => false,
            ],
        ];

        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setAuth($auth);

        $this->assertSame($expected, $request->getHeaders());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setBodymode()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setHeader()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setBody()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setBodyAsIs()
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getBody()
     * @return void
     */
    public function testJson(): void
    {
        $body = (object)[
            'mode' => 'raw',
            'raw' => $expectedBody = '["lorem ipsum dolor sit amet"]',
            'options' => (object)[
                'raw' => (object)[
                    'language' => 'json',
                ]
            ]
        ];

        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setBody($body);

        $expectedHeaders = [
            'Content-Type' => [
                'value' => 'application/json',
                'disabled' => false,
            ],
        ];

        $this->assertSame($expectedHeaders, $request->getHeaders());
        $this->assertSame($expectedBody, $request->getBody());
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setBodymode
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setHeader
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setBody
     * @covers PmConverter\Converters\Abstract\AbstractRequest::setFormdataBody
     * @covers PmConverter\Converters\Abstract\AbstractRequest::getBody
     * @return void
     */
    public function testFormdata(): void
    {
        $body = (object)[
            'mode' => 'formdata',
            'formdata' => [
                (object)[
                    'key' => 'param1',
                    'value' => 'value1',
                    'type' => 'text',
                ],
                (object)[
                    'key' => 'param2',
                    'src' => '/tmp/somefile.txt',
                    'type' => 'file',
                ],
            ],
        ];
        $expectedBody = [
            'param1' => [
                'value' => 'value1',
                'type' => 'text',
            ],
            'param2' => [
                'value' => '/tmp/somefile.txt',
                'type' => 'file',
            ],
        ];
        $expectedHeaders = [
            'Content-Type' => [
                'value' => 'multipart/form-data',
                'disabled' => false,
            ],
        ];

        $request = new \PmConverter\Converters\Http\HttpRequest();
        $request->setBody($body);

        $this->assertSame($expectedHeaders, $request->getHeaders());
        $this->assertSame($expectedBody, $request->getBody());
    }
}
