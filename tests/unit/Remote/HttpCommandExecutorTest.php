<?php

namespace Facebook\WebDriver\Remote;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class HttpCommandExecutorTest extends TestCase
{
    use PHPMock;

    /** @var HttpCommandExecutor */
    private $executor;

    protected function setUp()
    {
        $this->executor = new HttpCommandExecutor('http://localhost:4444');
    }

    /**
     * @dataProvider provideCommand
     * @param int $command
     * @param array $params
     * @param bool $shouldResetExpectHeader
     * @param string $expectedUrl
     * @param string $expectedPostData
     */
    public function testShouldSendRequestToAssembledUrl(
        $command,
        array $params,
        $shouldResetExpectHeader,
        $expectedUrl,
        $expectedPostData
    ) {
        $command = new WebDriverCommand('foo-123', $command, $params);

        $curlSetoptMock = $this->getFunctionMock(__NAMESPACE__, 'curl_setopt');
        $curlSetoptMock->expects($this->at(0))
            ->with($this->anything(), CURLOPT_URL, $expectedUrl);

        if ($shouldResetExpectHeader) {
            $curlSetoptMock->expects($this->at(2))
                ->with(
                    $this->anything(),
                    CURLOPT_HTTPHEADER,
                    ['Content-Type: application/json;charset=UTF-8', 'Accept: application/json', 'Expect:']
                );
            $curlSetoptMock->expects($this->at(3))
                ->with($this->anything(), CURLOPT_POSTFIELDS, $expectedPostData);
        } else {
            $curlSetoptMock->expects($this->at(2))
                ->with(
                    $this->anything(),
                    CURLOPT_HTTPHEADER,
                    ['Content-Type: application/json;charset=UTF-8', 'Accept: application/json']
                );
            $curlSetoptMock->expects($this->at(3))
                ->with($this->anything(), CURLOPT_POSTFIELDS, $expectedPostData);
        }

        $curlExecMock = $this->getFunctionMock(__NAMESPACE__, 'curl_exec');
        $curlExecMock->expects($this->once())
            ->willReturn('{}');

        $this->executor->execute($command);
    }

    /**
     * @return array[]
     */
    public function provideCommand()
    {
        return [
            'POST command having :id placeholder in url' => [
                DriverCommand::SEND_KEYS_TO_ELEMENT,
                ['value' => 'submitted-value', ':id' => '1337'],
                true,
                'http://localhost:4444/session/foo-123/element/1337/value',
                '{"value":"submitted-value"}',
            ],
            'POST command without :id placeholder in url' => [
                DriverCommand::TOUCH_UP,
                ['x' => 3, 'y' => 6],
                true,
                'http://localhost:4444/session/foo-123/touch/up',
                '{"x":3,"y":6}',
            ],
            'Extra useless placeholder parameter should be removed' => [
                DriverCommand::TOUCH_UP,
                ['x' => 3, 'y' => 6, ':useless' => 'foo'],
                true,
                'http://localhost:4444/session/foo-123/touch/up',
                '{"x":3,"y":6}',
            ],
            'DELETE command' => [
                DriverCommand::DELETE_COOKIE,
                [':name' => 'cookie-name'],
                false,
                'http://localhost:4444/session/foo-123/cookie/cookie-name',
                null,
            ],
            'GET command without session in URL' => [
                DriverCommand::GET_ALL_SESSIONS,
                [],
                false,
                'http://localhost:4444/sessions',
                null,
            ],
        ];
    }
}
