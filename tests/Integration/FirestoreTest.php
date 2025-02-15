<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Integration;

use Google\Cloud\Firestore\Transaction;
use Kreait\Firebase\Contract\Firestore;
use Kreait\Firebase\Tests\IntegrationTestCase;

use function str_replace;

/**
 * @group Firestore
 *
 * @requires PHP < 8.1
 *
 * @internal
 */
final class FirestoreTest extends IntegrationTestCase
{
    private Firestore $firestore;
    private string $collectionName;

    protected function setUp(): void
    {
        $this->firestore = self::$factory->createFirestore();
        $this->collectionName = str_replace('\\', '_', self::class);
    }

    public function testItReturnsAWorkingFirestoreClient(): void
    {
        $client = $this->firestore->database();

        $doc = $client->collection($this->collectionName)->document(__METHOD__);

        $doc->set(['counter' => 1]);

        $newCounter = $client->runTransaction(static function (Transaction $transaction) use ($doc) {
            $snapshot = $transaction->snapshot($doc);
            $newCounter = $snapshot['counter'] + 1;

            $transaction->update($doc, [
                ['path' => 'counter', 'value' => $newCounter],
            ]);

            return $newCounter;
        });

        self::assertSame(2, $newCounter);
    }
}
