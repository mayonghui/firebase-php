<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Http;

use InvalidArgumentException;
use Kreait\Firebase\Http\HttpClientOptions;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class HttpClientOptionsTest extends TestCase
{
    public function testOptionsCanBeSet(): void
    {
        $options = HttpClientOptions::default()
            ->withConnectTimeout(1.1)
            ->withReadTimeout(2.2)
            ->withTimeout(3.3)
            ->withProxy('https://proxy.tld');

        self::assertSame(1.1, $options->connectTimeout());
        self::assertSame(2.2, $options->readTimeout());
        self::assertSame(3.3, $options->timeout());
        self::assertSame('https://proxy.tld', $options->proxy());
    }

    public function testConnectTimeoutMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HttpClientOptions::default()->withConnectTimeout(-0.1);
    }

    public function testReadTimeoutMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HttpClientOptions::default()->withReadTimeout(-0.1);
    }

    public function testTimeoutMustBePositive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HttpClientOptions::default()->withTimeout(-0.1);
    }
}
