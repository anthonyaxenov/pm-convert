<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PmConverter\Converters\Http\HttpRequest;

class HttpRequestTest extends TestCase
{
    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Http\HttpRequest
     * @covers PmConverter\Converters\Http\HttpRequest::prepareDescription()
     * @covers PmConverter\Converters\Http\HttpRequest::__toString()
     * @return void
     */
    public function testPrepareDescription()
    {
        $description = [
            'lorem ipsum',
            'dolor sit',
            'amet',
        ];
        $needle = implode("\n", [
            '# lorem ipsum',
            '# dolor sit',
            '# amet',
        ]);

        $request = (new HttpRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setDescription(implode("\n", $description));

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Http\HttpRequest
     * @covers PmConverter\Converters\Http\HttpRequest::prepareHeaders()
     * @covers PmConverter\Converters\Http\HttpRequest::__toString()
     * @return void
     */
    public function testPrepareHeaders()
    {
        $headers = [
            (object)[
                'key' => 'Header1',
                'value' => 'Value 1',
                'disabled' => true,
            ],
            (object)[
                'key' => 'Header2',
                'value' => 'Value 2',
                'disabled' => false,
            ],
            (object)[
                'key' => 'Header3',
                'value' => 'Value 3',
            ],
        ];
        $needle = implode("\n", [
            '# Header1: Value 1',
            'Header2: Value 2',
            'Header3: Value 3',
        ]);

        $request = (new HttpRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setHeaders($headers);

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Http\HttpRequest
     * @covers PmConverter\Converters\Http\HttpRequest::prepareBody()
     * @covers PmConverter\Converters\Http\HttpRequest::__toString()
     * @return void
     */
    public function testPrepareFormdataBody()
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
        $needle = implode("\n", [
            'param1=value1',
            'param2=@/tmp/somefile.txt',
        ]);

        $request = (new HttpRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setBody($body);

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Http\HttpRequest
     * @covers PmConverter\Converters\Http\HttpRequest::prepareBody()
     * @covers PmConverter\Converters\Http\HttpRequest::__toString()
     * @return void
     */
    public function testPrepareJsonBody()
    {
        $body = (object)[
            'mode' => 'raw',
            'raw' => $needle = '["lorem ipsum dolor sit amet"]',
            'options' => (object)[
                'raw' => (object)[
                    'language' => 'json',
                ]
            ]
        ];

        $request = (new HttpRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setBody($body);

        $this->assertStringContainsString($needle, (string)$request);
    }
}
