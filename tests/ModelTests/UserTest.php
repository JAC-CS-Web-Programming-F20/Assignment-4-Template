<?php

namespace AssignmentFourTests\ModelTests;

use AssignmentFour\Exceptions\UserException;
use AssignmentFour\Models\User;
use AssignmentFourTests\AssignmentFourTest;

final class UserTest extends AssignmentFourTest
{
	public function testUserWasCreatedSuccessfully(): void
	{
		$this->assertInstanceOf(User::class, self::generateUser());
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testExceptionWasThrownWhenCreatingUser(array $parameters, string $exception, string $message, bool $generateUser = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generateUser) {
			self::generateUser('Blastoise', 'blastoise@pokemon.com', 'Water123');
		}

		User::create($parameters['username'], $parameters['email'], $parameters['password']);
	}

	public function createUserProvider()
	{
		yield 'blank username' => [
			['username' => '', 'email' => 'blastoise@pokemon.com', 'password' => 'Water123'],
			UserException::class,
			'Cannot create User: Missing username.'
		];

		yield 'blank email' => [
			['username' => 'Blastoise', 'email' => '', 'password' => 'Water123'],
			UserException::class,
			'Cannot create User: Missing email.'
		];

		yield 'blank password' => [
			['username' => 'Blastoise', 'email' => 'blastoise@pokemon.com', 'password' => ''],
			UserException::class,
			'Cannot create User: Missing password.'
		];

		yield 'duplicate username' => [
			['username' => 'Blastoise', 'email' => 'blastoise1@pokemon.com', 'password' => 'Water123'],
			UserException::class,
			'Cannot create User: Username already exists.',
			true
		];

		yield 'duplicate email' => [
			['username' => 'Blastoise1', 'email' => 'blastoise@pokemon.com', 'password' => 'Water123'],
			UserException::class,
			'Cannot create User: Email already exists.',
			true
		];
	}

	public function testUserWasFoundById(): void
	{
		$user = self::generateUser();
		$retrievedUser = User::findById($user->getId());

		$this->assertEquals(
			$retrievedUser->getUsername(),
			$user->getUsername()
		);
	}

	public function testUserWasNotFoundByWrongId(): void
	{
		$user = self::generateUser();
		$retrievedUser = User::findById($user->getId() + 1);

		$this->assertNull($retrievedUser);
	}

	public function testUserWasFoundByUsername(): void
	{
		$user = self::generateUser();
		$retrievedUser = User::findByUsername($user->getUsername());

		$this->assertEquals(
			$retrievedUser->getUsername(),
			$user->getUsername()
		);
	}

	public function testUserWasNotFoundByWrongUsername(): void
	{
		$user = self::generateUser();
		$retrievedUser = User::findByUsername($user->getUsername() . '!');

		$this->assertNull($retrievedUser);
	}

	public function testUserWasFoundByEmail(): void
	{
		$user = self::generateUser();
		$retrievedUser = User::findByEmail($user->getEmail());

		$this->assertEquals(
			$retrievedUser->getEmail(),
			$user->getEmail()
		);
	}

	public function testUserWasNotFoundByWrongEmail(): void
	{
		$user = self::generateUser();
		$retrievedUser = User::findByEmail($user->getEmail() . '.wrong');

		$this->assertNull($retrievedUser);
	}

	public function testUserWasUpdatedSuccessfully(): void
	{
		$oldUser = $this->generateUser();
		$newUsername = self::$faker->name;

		$oldUser->setUsername($newUsername);
		$this->assertNull($oldUser->getEditedAt());
		$this->assertTrue($oldUser->save());

		$retrievedUser = User::findById($oldUser->getId());
		$this->assertEquals($newUsername, $retrievedUser->getUsername());
		$this->assertNotNull($retrievedUser->getEditedAt());
	}

	/**
	 * @dataProvider updateUserProvider
	 */
	public function testExceptionWasThrownWhenUpdatingUser(string $functionName, $updatedValue, string $exception, string $message): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$user = self::generateUser();

		call_user_func([$user, $functionName], $updatedValue);
		$user->save();
	}

	public function updateUserProvider()
	{
		yield 'blank username' => [
			'setUsername',
			'',
			UserException::class,
			'Cannot update User: Missing username.'
		];

		yield 'blank email' => [
			'setEmail',
			'',
			UserException::class,
			'Cannot update User: Missing email.'
		];
	}

	public function testUserWasDeletedSuccessfully(): void
	{
		$user = $this->generateUser();
		$this->assertNull($user->getDeletedAt());
		$this->assertTrue($user->remove());

		$retrievedUser = User::findById($user->getId());
		$this->assertNotNull($retrievedUser->getDeletedAt());
	}
}
