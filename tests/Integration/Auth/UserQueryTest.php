<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Integration\Auth;

use Kreait\Firebase\Auth\UserQuery;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Tests\IntegrationTestCase;

use function array_values;
use function assert;
use function current;
use function end;
use function random_int;
use function usleep;

/**
 * @internal
 *
 * @phpstan-import-type UserQueryShape from UserQuery
 */
final class UserQueryTest extends IntegrationTestCase
{
    private Auth $auth;

    protected function setUp(): void
    {
        $this->auth = self::$factory->createAuth();
    }

    public function testSortByField(): void
    {
        $user = $this->createUserWithEmailAndPassword();

        // Another test could have created a user in-between, so we fetch more than we actually need
        $result = $this->auth->queryUsers([
            'sortBy' => UserQuery::FIELD_CREATED_AT,
            'order' => UserQuery::ORDER_DESC,
            'limit' => 10,
        ]);

        try {
            $this->assertUserExists($user, $result);
        } finally {
            $this->auth->deleteUser($user->uid);
        }
    }

    public function testAscendingSortOrder(): void
    {
        // Create two users just in case there are no others in the database
        $firstUser = $this->createUserWithEmailAndPassword();
        usleep(1000);
        $secondUser = $this->createUserWithEmailAndPassword();

        $query = [
            'sortBy' => UserQuery::FIELD_CREATED_AT,
            'order' => UserQuery::ORDER_ASC,
            'limit' => 10,
        ];

        /** @var list<UserRecord> $result */
        $result = array_values($this->auth->queryUsers($query));

        $firstCreatedAt = $result[0]->metadata->createdAt;
        $lastUserRecord = end($result);
        assert($lastUserRecord instanceof UserRecord);
        $secondCreatedAt = $lastUserRecord->metadata->createdAt;

        try {
            self::assertNotNull($firstCreatedAt);
            self::assertNotNull($secondCreatedAt);
            self::assertTrue($firstCreatedAt->getTimestamp() < $secondCreatedAt->getTimestamp());
        } finally {
            $this->auth->deleteUser($firstUser->uid);
            $this->auth->deleteUser($secondUser->uid);
        }
    }

    public function testDescendingSortOrder(): void
    {
        // Create two users just in case there are no others in the database
        $firstUser = $this->createUserWithEmailAndPassword();
        usleep(1000);
        $secondUser = $this->createUserWithEmailAndPassword();

        $query = [
            'sortBy' => UserQuery::FIELD_CREATED_AT,
            'order' => UserQuery::ORDER_DESC,
            'limit' => 10,
        ];

        $result = array_values($this->auth->queryUsers($query));

        $firstCreatedAt = $result[0]->metadata->createdAt;
        $lastUserRecord = end($result);
        assert($lastUserRecord instanceof UserRecord);
        $secondCreatedAt = $lastUserRecord->metadata->createdAt;

        try {
            self::assertNotNull($firstCreatedAt);
            self::assertNotNull($secondCreatedAt);
            self::assertTrue($firstCreatedAt->getTimestamp() > $secondCreatedAt->getTimestamp());
        } finally {
            $this->auth->deleteUser($firstUser->uid);
            $this->auth->deleteUser($secondUser->uid);
        }
    }

    public function testLimit(): void
    {
        // Create two users just in case there are no others in the database
        $firstUser = $this->createUserWithEmailAndPassword();
        $secondUser = $this->createUserWithEmailAndPassword();

        $query = [
            'limit' => 1,
        ];

        $result = $this->auth->queryUsers($query);

        try {
            self::assertCount(1, $result);
        } finally {
            $this->auth->deleteUser($firstUser->uid);
            $this->auth->deleteUser($secondUser->uid);
        }
    }

    public function testFilterByUid(): void
    {
        $user = $this->createUserWithEmailAndPassword();

        $query = [
            'filter' => [
                'userId' => $user->uid,
            ],
            'limit' => 1,
        ];

        $result = $this->auth->queryUsers($query);
        $found = current($result);

        try {
            self::assertCount(1, $result);
            self::assertInstanceOf(UserRecord::class, $found);
            self::assertSame($user->uid, $found->uid);
        } finally {
            $this->auth->deleteUser($user->uid);
        }
    }

    public function testFilterByEmail(): void
    {
        $user = $this->createUserWithEmailAndPassword();

        $query = [
            'filter' => [
                'email' => $user->email,
            ],
            'limit' => 1,
        ];

        $result = $this->auth->queryUsers($query);
        $found = current($result);

        try {
            self::assertCount(1, $result);
            self::assertInstanceOf(UserRecord::class, $found);
            self::assertSame($user->email, $found->email);
        } finally {
            $this->auth->deleteUser($user->uid);
        }
    }

    public function testFilterByPhoneNumber(): void
    {
        $user = $this->auth->createUser([
            'phoneNumber' => '+49' . random_int(90_000_000_000, 99_999_999_999),
        ]);

        $query = [
            'filter' => [
                'phoneNumber' => $user->phoneNumber,
            ],
            'limit' => 1,
        ];

        $result = $this->auth->queryUsers($query);
        $found = current($result);

        try {
            self::assertCount(1, $result);
            self::assertInstanceOf(UserRecord::class, $found);
            self::assertSame($user->phoneNumber, $found->phoneNumber);
        } finally {
            $this->auth->deleteUser($user->uid);
        }
    }

    protected function createUserWithEmailAndPassword(?string $email = null, ?string $password = null): UserRecord
    {
        $email ??= self::randomEmail();
        $password ??= self::randomString();

        return $this->auth->createUser([
            'email' => $email,
            'clear_text_password' => $password,
        ]);
    }

    /**
     * @param array<UserRecord> $queryResult
     */
    private function assertUserExists(UserRecord $userRecord, array $queryResult): void
    {
        foreach ($queryResult as $record) {
            if ($record->uid === $userRecord->uid) {
                $this->addToAssertionCount(1);

                return;
            }
        }

        self::fail('Expected query result to contain a user with UID ' . $userRecord->uid);
    }
}
