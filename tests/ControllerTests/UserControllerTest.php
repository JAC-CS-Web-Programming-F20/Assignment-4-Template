<?php

namespace AssignmentFourTests\ControllerTests;

use AssignmentFour\Controllers\UserController;
use AssignmentFour\Exceptions\UserException;
use AssignmentFour\Router\JsonResponse;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;
use AssignmentFourTests\AssignmentFourTest;

final class UserControllerTest extends AssignmentFourTest
{
	public function testUserControllerCalledShow(): void
	{
		$user = $this->generateUser();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('GET');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$user->getId()]
		]);

		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was retrieved successfully!', $response->getMessage());
		$this->assertEquals($user->getId(), $response->getPayload()->getId());
		$this->assertEquals($user->getUsername(), $response->getPayload()->getUsername());
		$this->assertEquals($user->getEmail(), $response->getPayload()->getEmail());
	}

	/**
	 * @dataProvider findUserProvider
	 */
	public function testExceptionWasThrownShowingUser(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new UserController($request, new JsonResponse()))->doAction();
	}

	public function findUserProvider()
	{
		yield 'invalid ID' => [
			UserException::class,
			'Cannot find User: User does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testUserControllerCalledNew(): void
	{
		$user = $this->generateUserData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('POST');
		$request->method('getParameters')->willReturn([
			'body' => [
				'username' => $user['username'],
				'email' => $user['email'],
				'password' => $user['password']
			],
			'header' => []
		]);

		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was created successfully!', $response->getMessage());
		$this->assertEquals($user['username'], $response->getPayload()->getUsername());
		$this->assertEquals($user['email'], $response->getPayload()->getEmail());
		$this->assertNotEmpty($response->getPayload()->getId());
	}

	/**
	 * @dataProvider createUserProvider
	 */
	public function testExceptionWasThrownNewingUser(string $exception, string $message, string $requestMethod, array $parameters, bool $generateUser = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generateUser) {
			self::generateUser('Charmeleon', 'charmeleon@pokemon.com', 'Fire123');
		}

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new UserController($request, new JsonResponse()))->doAction();
	}

	public function createUserProvider()
	{
		yield 'blank username' => [
			UserException::class,
			'Cannot create User: Missing username.',
			'POST',
			[
				'body' => [
					'username' => '',
					'email' => 'bulbasaur@pokemon.com',
					'password' => 'Grass123'
				],
				'header' => []
			],
		];

		yield 'blank email' => [
			UserException::class,
			'Cannot create User: Missing email.',
			'POST',
			[
				'body' => [
					'username' => 'Blastoise',
					'email' => '',
					'password' => 'Water123'
				],
				'header' => []
			],
		];

		yield 'blank password' => [
			UserException::class,
			'Cannot create User: Missing password.',
			'POST',
			[
				'body' => [
					'username' => 'Charmeleon',
					'email' => 'charmeleon@pokemon.com',
					'password' => ''
				],
				'header' => []
			],
		];

		yield 'duplicate username' => [
			UserException::class,
			'Cannot create User: Username already exists.',
			'POST',
			[
				'body' => [
					'username' => 'Charmeleon',
					'email' => 'charmeleon1@pokemon.com',
					'password' => 'Fire123'
				],
				'header' => []
			],
			true
		];

		yield 'duplicate email' => [
			UserException::class,
			'Cannot create User: Email already exists.',
			'POST',
			[
				'body' => [
					'username' => 'Charmeleon1',
					'email' => 'charmeleon@pokemon.com',
					'password' => 'Fire123'
				],
				'header' => []
			],
			true
		];
	}

	public function testUserControllerCalledEdit(): void
	{
		$user = $this->generateUser();
		$newUserData = $this->generateUserData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('PUT');
		$request->method('getParameters')->willReturn([
			'body' => [
				'username' => $newUserData['username'],
				'email' => $newUserData['email']
			],
			'header' => [$user->getId()]
		]);

		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was updated successfully!', $response->getMessage());
		$this->assertEquals($newUserData['username'], $response->getPayload()->getUsername());
		$this->assertEquals($newUserData['email'], $response->getPayload()->getEmail());
		$this->assertNotEquals($user->getUsername(), $response->getPayload()->getUsername());
		$this->assertNotEquals($user->getEmail(), $response->getPayload()->getEmail());
	}

	/**
	 * @dataProvider editUserProvider
	 */
	public function testExceptionWasThrownEditingUser(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$this->generateUser();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new UserController($request, new JsonResponse()))->doAction();
	}

	public function editUserProvider()
	{
		yield 'invalid ID' => [
			UserException::class,
			'Cannot edit User: User does not exist with ID 999.',
			'PUT',
			[
				'body' => [
					'username' => 'Bulbasaur',
					'email' => 'bulbasaur@pokemon.com',
					'password' => 'Grass123'
				],
				'header' => [999]
			],
		];

		yield 'blank username' => [
			UserException::class,
			'Cannot update User: Missing username.',
			'PUT',
			[
				'body' => [
					'username' => '',
					'email' => 'bulbasaur@pokemon.com',
					'password' => 'Grass123'
				],
				'header' => [1]
			],
		];

		yield 'blank email' => [
			UserException::class,
			'Cannot update User: Missing email.',
			'PUT',
			[
				'body' => [
					'username' => 'Blastoise',
					'email' => '',
					'password' => 'Water123'
				],
				'header' => [1]
			],
		];
	}

	public function testUserControllerCalledDestroy(): void
	{
		$user = $this->generateUser();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('DELETE');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$user->getId()]
		]);

		$controller = new UserController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('User was deleted successfully!', $response->getMessage());
		$this->assertEquals($user->getId(), $response->getPayload()->getId());
		$this->assertEquals($user->getUsername(), $response->getPayload()->getUsername());
		$this->assertEquals($user->getEmail(), $response->getPayload()->getEmail());
	}

	/**
	 * @dataProvider deleteUserProvider
	 */
	public function testExceptionWasThrownDestroyingUser(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$this->generateUser();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new UserController($request, new JsonResponse()))->doAction();
	}

	public function deleteUserProvider()
	{
		yield 'invalid ID' => [
			UserException::class,
			'Cannot delete User: User does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}

	// public function testUserCommentsWereRetrieved(): void
	// {
	// 	$user = $this->generateUser();

	// 	for ($i = 0; $i < rand(1, 20); $i++) {
	// 		$posts[] = $this->generatePost();

	// 		// User will randomly comment on posts.
	// 		if (rand(0, 1) === 0) {
	// 			$comments[] = $this->generateComment($posts[$i]->getId(), $user->getId());
	// 		}
	// 	}

	// 	// Force a comment in case none were created above.
	// 	$comments[] = $this->generateComment($posts[0]->getId(), $user->getId());

	// 	$request = $this->createMock(Request::class);
	// 	$request->method('getRequestMethod')->willReturn('GET');
	// 	$request->method('getParameters')->willReturn([
	// 		'header' => [$user->getId(), 'comment'],
	// 		'body' => []
	// 	]);

	// 	$controller = new UserController($request, new JsonResponse());

	// 	$this->assertEquals('comment', $controller->getAction());

	// 	$response = $controller->doAction();

	// 	$this->assertTrue($response instanceof Response);
	// 	$this->assertEquals('User comments were retrieved successfully!', $response->getMessage());
	// 	$this->assertEquals($user->getId(), $response->getPayload()['user']->getId());
	// 	$this->assertEquals(sizeOf($comments), sizeOf($response->getPayload()['comments']));

	// 	for ($i = 0; $i < sizeOf($comments); $i++) {
	// 		$this->assertEquals($comments[$i]->getContent(), $response->getPayload()['comments'][$i]->getContent());
	// 	}
	// }

	// public function testUserPostsWereRetrieved(): void
	// {
	// 	$user = $this->generateUser();

	// 	// User will randomly create on posts.
	// 	for ($i = 0; $i < rand(1, 20); $i++) {
	// 		if (rand(0, 1) === 0) {
	// 			$posts[] = $this->generatePost($user->getId());
	// 		} else {
	// 			$this->generatePost();
	// 		}
	// 	}

	// 	// Force a post in case none were created above.
	// 	$posts[] = $this->generatePost($user->getId());

	// 	$request = $this->createMock(Request::class);
	// 	$request->method('getRequestMethod')->willReturn('GET');
	// 	$request->method('getParameters')->willReturn([
	// 		'header' => [$user->getId(), 'post'],
	// 		'body' => []
	// 	]);

	// 	$controller = new UserController($request, new JsonResponse());

	// 	$this->assertEquals('post', $controller->getAction());

	// 	$response = $controller->doAction();

	// 	$this->assertTrue($response instanceof Response);
	// 	$this->assertEquals('User posts were retrieved successfully!', $response->getMessage());
	// 	$this->assertEquals($user->getId(), $response->getPayload()['user']->getId());
	// 	$this->assertEquals(sizeOf($posts), sizeOf($response->getPayload()['posts']));

	// 	for ($i = 0; $i < sizeOf($posts); $i++) {
	// 		$this->assertEquals($posts[$i]->getContent(), $response->getPayload()['posts'][$i]->getContent());
	// 	}
	// }
}
