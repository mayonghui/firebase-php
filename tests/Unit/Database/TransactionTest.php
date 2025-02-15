<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Unit\Database;

use Kreait\Firebase\Database\ApiClient;
use Kreait\Firebase\Database\Reference;
use Kreait\Firebase\Database\Transaction;
use Kreait\Firebase\Exception\Database\DatabaseError;
use Kreait\Firebase\Exception\Database\ReferenceHasNotBeenSnapshotted;
use Kreait\Firebase\Exception\Database\TransactionFailed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @internal
 */
final class TransactionTest extends TestCase
{
    /**
     * @var ApiClient|MockObject
     */
    private $apiClient;
    private Transaction $transaction;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(ApiClient::class);

        $this->transaction = new Transaction($this->apiClient);
    }

    public function testAReferenceCanNotBeChangedIfItHasNotBeenSnapshotted(): void
    {
        $reference = $this->createMock(Reference::class);

        try {
            $this->transaction->set($reference, 'does not matter');
        } catch (ReferenceHasNotBeenSnapshotted $e) {
            self::assertSame($reference, $e->getReference());
        } catch (Throwable $e) {
            self::fail('A ' . ReferenceHasNotBeenSnapshotted::class . ' should have been thrown');
        }
    }

    public function testATransactionCanFail(): void
    {
        $reference = $this->createMock(Reference::class);
        $reference->method('getPath')->willReturn('/foo');

        $this->apiClient
            ->method('getWithETag')
            ->with('/foo')
            ->willReturn(['etag' => 'etag', 'value' => 'old value']);

        $this->apiClient
            ->method('setWithEtag')
            ->with('/foo')
            ->willThrowException(new DatabaseError());

        $this->transaction->snapshot($reference);

        $this->expectException(TransactionFailed::class);
        $this->transaction->set($reference, 'new value');
    }
}
