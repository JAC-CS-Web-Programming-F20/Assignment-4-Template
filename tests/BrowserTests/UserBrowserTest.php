<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFour\Models\User;
use AssignmentFourTests\AssignmentFourTest;

final class UserBrowserTest extends AssignmentFourTest
{
	public function testHome(): void
	{
		self::$driver->get(self::$baseUri);

		$h1 = $this->findElement('h1');
		$this->assertStringContainsString('Welcome to Reddit!', $h1->getText());
	}

	public function testInvalidEndpoint(): void
	{
		self::$driver->get(self::$baseUri . '/digimon');

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString('404', $body->getText());
	}

	public function testUserWasCreatedSuccessfully(): void
	{
		$userData = $this->generateUserData();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"user/new\"]")->click();

		$h1 = $this->findElement('h1');
		$usernameInput = $this->findElement("form#new-user-form input[name=\"username\"]");
		$emailInput = $this->findElement("form#new-user-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#new-user-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#new-user-form button");

		$this->assertStringContainsString('Register', $h1->getText());

		$usernameInput->sendKeys($userData['username']);
		$emailInput->sendKeys($userData['email']);
		$passwordInput->sendKeys($userData['password']);
		$submitButton->click();

		$user = User::findByUsername($userData['username']);
		$usernameElement = $this->findElement("#username");
		$emailElement = $this->findElement("#email");

		$this->assertStringContainsString($user->getUsername(), $usernameElement->getText());
		$this->assertStringContainsString($user->getEmail(), $emailElement->getText());
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testUserWasNotCreated(array $userData, string $message, bool $generateUser = false): void
	{
		if ($generateUser) {
			self::generateUser('Bulbasaur', 'bulbasaur@user.com', 'Grass123');
		}

		self::$driver->get(self::$baseUri . '/user/new');

		$h1 = $this->findElement('h1');
		$usernameInput = $this->findElement("form#new-user-form input[name=\"username\"]");
		$emailInput = $this->findElement("form#new-user-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#new-user-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#new-user-form button");

		$this->assertStringContainsString('Register', $h1->getText());

		$usernameInput->sendKeys($userData['username']);
		$emailInput->sendKeys($userData['email']);
		$passwordInput->sendKeys($userData['password']);

		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString($message, $body->getText());
	}

	public function createUserProvider()
	{
		yield 'blank username' => [
			[
				'username' => '',
				'email' => 'bulbasaur@user.com',
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
				'email' => 'bulbasaur@user.com',
				'password' => ''
			],
			'Cannot create User: Missing password.'
		];

		yield 'duplicate username' => [
			[
				'username' => 'Bulbasaur',
				'email' => 'bulbasaur1@user.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Username already exists.',
			true
		];

		yield 'duplicate email' => [
			[
				'username' => 'Bulbasaur1',
				'email' => 'bulbasaur@user.com',
				'password' => 'Grass123'
			],
			'Cannot create User: Email already exists.',
			true
		];
	}

	public function testUserWasFoundById(): void
	{
		$user = $this->generateUser();
		$userId = $user->getId();

		self::$driver->get(self::$baseUri . "/user/$userId");

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($user->getUsername(), $username->getText());
		$this->assertStringContainsString($user->getEmail(), $email->getText());
	}

	public function testUserWasNotFoundByWrongId(): void
	{
		$randomUserId = rand(1, 100);
		self::$driver->get(self::$baseUri . "/user/$randomUserId");

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find User: User does not exist with ID $randomUserId.", $body->getText());
	}

	public function testUserWasUpdatedSuccessfully(): void
	{
		$user = $this->generateUser();
		$userId = $user->getId();
		$newUserData = $this->generateUserData();

		self::$driver->get(self::$baseUri . "/user/$userId");

		$usernameInput = $this->findElement("form#edit-user-form input[name=\"username\"]");
		$emailInput = $this->findElement("form#edit-user-form input[name=\"email\"]");
		$passwordInput = $this->findElement("form#edit-user-form input[name=\"password\"]");
		$submitButton = $this->findElement("form#edit-user-form button");

		$usernameInput->sendKeys($newUserData['username']);
		$emailInput->sendKeys($newUserData['email']);
		$passwordInput->sendKeys($newUserData['password']);
		$submitButton->click();

		$username = $this->findElement('#username');
		$email = $this->findElement('#email');

		$this->assertStringContainsString($newUserData['username'], $username->getText());
		$this->assertStringContainsString($newUserData['email'], $email->getText());
	}

	public function testUserWasNotUpdatedWithBlankUsername(): void
	{
		$user = $this->generateUser();
		$userId = $user->getId();

		self::$driver->get(self::$baseUri . "/user/$userId");

		$usernameInput = $this->findElement("form#edit-user-form input[name=\"username\"]");
		$submitButton = $this->findElement("form#edit-user-form button");

		$usernameInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot update User: Missing username.", $body->getText());
	}

	public function testUserWasNotUpdatedWithBlankEmail(): void
	{
		$user = $this->generateUser();
		$userId = $user->getId();

		self::$driver->get(self::$baseUri . "/user/$userId");

		$emailInput = $this->findElement("form#edit-user-form input[name=\"email\"]");
		$submitButton = $this->findElement("form#edit-user-form button");

		$emailInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot update User: Missing email.", $body->getText());
	}

	public function testUserWasDeletedSuccessfully(): void
	{
		$user = $this->generateUser();
		$userId = $user->getId();

		self::$driver->get(self::$baseUri . "/user/$userId");

		$deleteButton = $this->findElement("form#delete-user-form button");
		$deleteButton->click();

		$deletedAt = User::findById($userId)->getDeletedAt();
		$body = $this->findElement('body');

		$this->assertStringContainsString("User was deleted on $deletedAt", $body->getText());
	}
}
