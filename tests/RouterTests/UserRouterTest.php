<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFourTests\AssignmentFourTest;

final class UserRouterTest extends AssignmentFourTest
{
	public function testHome(): void
	{
		$response = $this->getResponse(
			'GET',
			''
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertEquals('Please feel free to make an account and browse around :)', $response['message']);
	}

	public function testInvalidEndpoint(): void
	{
		$response = $this->getResponse(
			'GET',
			'digimon'
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertEquals('404', $response['message']);
	}

	public function testInvalidHttpMethod(): void
	{
		$response = $this->getResponse(
			'PATCH',
			'user'
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertEquals('404', $response['message']);
	}

	public function testUserWasCreatedSuccessfully(): void
	{
		$randomUser = $this->generateUserData();

		$response = $this->getResponse(
			'POST',
			'user',
			$randomUser
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('username', $response['payload']);
		$this->assertArrayHasKey('email', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($randomUser['username'], $response['payload']['username']);
		$this->assertEquals($randomUser['email'], $response['payload']['email']);
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testUserWasNotCreated(array $userData, string $message, bool $generateUser = false): void
	{
		if ($generateUser) {
			self::generateUser('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');
		}

		$response = $this->getResponse(
			'POST',
			'user',
			$userData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
	}

	public function createUserProvider()
	{
		yield 'blank username' => [
			[
				'username' => '',
				'email' => 'bulbasaur@pokemon.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Missing username.'
		];

		yield 'blank email' => [
			[
				'username' => 'Bulbasaur',
				'email' => '',
				'password' => 'Grass123'
			],
			'Cannot create User: Missing email.'
		];

		yield 'blank password' => [
			[
				'username' => 'Bulbasaur',
				'email' => 'bulbasaur@pokemon.com',
				'password' => ''
			],
			'Cannot create User: Missing password.'
		];

		yield 'duplicate username' => [
			[
				'username' => 'Bulbasaur',
				'email' => 'bulbasaur1@pokemon.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Username already exists.',
			true
		];

		yield 'duplicate email' => [
			[
				'username' => 'Bulbasaur1',
				'email' => 'bulbasaur@pokemon.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Email already exists.',
			true
		];
	}

	public function testUserWasFoundById(): void
	{
		$user = $this->generateUser();

		$retrievedUser = $this->getResponse(
			'GET',
			'user/' . $user->getId()
		)['payload'];

		$this->assertArrayHasKey('id', $retrievedUser);
		$this->assertArrayHasKey('username', $retrievedUser);
		$this->assertArrayHasKey('email', $retrievedUser);
		$this->assertEquals($user->getId(), $retrievedUser['id']);
		$this->assertEquals($user->getUsername(), $retrievedUser['username']);
		$this->assertEquals($user->getEmail(), $retrievedUser['email']);
	}

	public function testUserWasNotFoundByWrongId(): void
	{
		$retrievedUser = $this->getResponse(
			'GET',
			'user/1',
		);

		$this->assertEquals('Cannot find User: User does not exist with ID 1.', $retrievedUser['message']);
		$this->assertEmpty($retrievedUser['payload']);
	}

	// public function testUserWasFoundByName(): void
	// {
	// 	$randomUser = $this->generateRandomUserData();

	// 	$newUser = User::create(
	// 		self::$database,
	// 		$randomUser['username'],
	// 		$randomUser['email'],
	// 	);

	// 	$retrievedUser = User::findByName(
	// 		self::$database,
	// 		$newUser->getName()
	// 	);

	// 	$this->assertEquals(
	// 		$retrievedUser->getName(),
	// 		$newUser->getName()
	// 	);
	// }

	// public function testUserWasNotFoundByWrongName(): void
	// {
	// 	$randomUser = $this->generateRandomUserData();

	// 	User::create(
	// 		self::$database,
	// 		$randomUser['username'],
	// 		$randomUser['email'],
	// 	);

	// 	$retrievedUser = User::findByName(
	// 		self::$database,
	// 		$randomUser['username'] . '!'
	// 	);

	// 	$this->assertNull($retrievedUser);
	// }

	/**
	 * @dataProvider updatedUserProvider
	 */
	public function testUserWasUpdated(array $oldUserData, array $newUserData, array $editedFields): void
	{
		$oldUser = $this->getResponse(
			'POST',
			'user',
			$oldUserData
		)['payload'];

		$editedUser = $this->getResponse(
			'PUT',
			'user/' . $oldUser['id'],
			$newUserData
		)['payload'];

		/**
		 * Check every User field against all the fields that were supposed to be edited.
		 * If the User field is a field that's supposed to be edited, check if they're not equal.
		 * If the User field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldUser as $oldUserKey => $oldUserValue) {
			foreach ($editedFields as $editedField) {
				if ($oldUserKey === $editedField) {
					$this->assertNotEquals($oldUserValue, $editedUser[$editedField]);
					$this->assertEquals($editedUser[$editedField], $newUserData[$editedField]);
				}
			}
		}
	}

	public function updatedUserProvider()
	{
		yield 'valid username' => [
			['username' => 'Pikachu', 'email' => 'pikachu@pokemon.com', 'password' => 'pikachu123'],
			['username' => 'Bulbasaur'],
			['username'],
		];

		yield 'valid email' => [
			['username' => 'Pikachu', 'email' => 'pikachu@pokemon.com', 'password' => 'pikachu123'],
			['email' => 'bulbasaur@pokemon.com'],
			['email'],
		];

		yield 'valid username and email' => [
			['username' => 'Pikachu', 'email' => 'pikachu@pokemon.com', 'password' => 'pikachu123'],
			['username' => 'Magikarp', 'email' => 'magikarp@pokemon.com'],
			['username', 'email'],
		];
	}

	/**
	 * @dataProvider updateUserProvider
	 */
	public function testUserWasNotUpdated(int $userId, array $newUserData, string $message): void
	{
		self::generateUser('Bulbasaur', 'bulbasaur@pokemon.com', 'Grass123');

		$editedUser = $this->getResponse(
			'PUT',
			'user/' . $userId,
			$newUserData
		);

		$this->assertEquals($message, $editedUser['message']);
		$this->assertEmpty($editedUser['payload']);
	}

	public function updateUserProvider()
	{
		yield 'invalid ID' => [
			999,
			[
				'username' => 'Ivysaur',
			],
			'Cannot edit User: User does not exist with ID 999.'
		];

		yield 'blank username' => [
			1,
			['username' => ''],
			'Cannot update User: Missing username.'
		];

		yield 'blank email' => [
			1,
			['email' => ''],
			'Cannot update User: Missing email.'
		];

		yield 'integer username' => [
			1,
			['username' => 123],
			'User was not updated.'
		];

		yield 'integer email' => [
			1,
			['email' => 123],
			'User was not updated.'
		];
	}

	public function testUserWasDeletedSuccessfully(): void
	{
		$randomUser = $this->generateUserData();

		$oldUser = $this->getResponse(
			'POST',
			'user',
			$randomUser
		)['payload'];

		$this->assertEmpty($oldUser['deletedAt']);

		$deletedUser = $this->getResponse(
			'DELETE',
			'user/' . $oldUser['id']
		)['payload'];

		$this->assertEquals($oldUser['id'], $deletedUser['id']);
		$this->assertEquals($oldUser['username'], $deletedUser['username']);
		$this->assertEquals($oldUser['email'], $deletedUser['email']);

		$retrievedUser = $this->getResponse(
			'GET',
			'user/' . $oldUser['id'],
		)['payload'];

		$this->assertNotEmpty($retrievedUser['deletedAt']);
	}

	public function testUserWasNotDeleted(): void
	{
		$deletedUser = $this->getResponse(
			'DELETE',
			'user/999'
		);

		$this->assertEquals('Cannot delete User: User does not exist with ID 999.', $deletedUser['message']);
		$this->assertEmpty($deletedUser['payload']);
	}
}
