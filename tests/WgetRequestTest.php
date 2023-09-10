<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PmConverter\Converters\Wget\WgetRequest;

class WgetRequestTest extends TestCase
{
    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareDescription()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
     * @return void
     */
    public function testPrepareNotEmptyDescription()
    {
        $request = (new WgetRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setDescription(null);

        $result = explode("\n", (string)$request);

        $this->assertFalse(str_starts_with($result[0], '# ') && str_starts_with($result[1], '# '));
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareDescription()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
     * @return void
     */
    public function testPrepareEmptyDescription()
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

        $request = (new WgetRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setDescription(implode("\n", $description));

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareHeaders()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
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
            "\t--header 'Header2: Value 2' \ ",
            "\t--header 'Header3: Value 3' \ ",
        ]);

        $request = (new WgetRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setHeaders($headers);

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareBody()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
     * @return void
     */
    public function testPrepareFormdataBodyGet()
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
                (object)[
                    'key' => 'param3',
                    'value' => 'value3',
                    'type' => 'text',
                ],
            ],
        ];
        $needle = 'http://localhost?' . http_build_query([
            'param1' => 'value1',
            'param3' => 'value3',
        ]);

        $request = (new WgetRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setBody($body);

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareBody()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
     * @return void
     */
    public function testPrepareFormdataBodyPost()
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
                (object)[
                    'key' => 'param3',
                    'value' => 'value3',
                    'type' => 'text',
                ],
            ],
        ];
        $needle = http_build_query([
            'param1' => 'value1',
            'param3' => 'value3',
        ]);

        $request = (new WgetRequest())
            ->setVerb('POST')
            ->setUrl('http://localhost')
            ->setBody($body);

        $this->assertStringContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareBody()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
     * @return void
     */
    public function testPrepareJsonBodyGet()
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

        $request = (new WgetRequest())
            ->setVerb('GET')
            ->setUrl('http://localhost')
            ->setBody($body);

        $this->assertStringNotContainsString($needle, (string)$request);
    }

    /**
     * @covers PmConverter\Converters\Abstract\AbstractRequest
     * @covers PmConverter\Converters\Wget\WgetRequest
     * @covers PmConverter\Converters\Wget\WgetRequest::prepareBody()
     * @covers PmConverter\Converters\Wget\WgetRequest::__toString()
     * @return void
     */
    public function testPrepareJsonBodyPost()
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

        $request = (new WgetRequest())
            ->setVerb('POST')
            ->setUrl('http://localhost')
            ->setBody($body);

        $this->assertStringContainsString($needle, (string)$request);
    }
}
