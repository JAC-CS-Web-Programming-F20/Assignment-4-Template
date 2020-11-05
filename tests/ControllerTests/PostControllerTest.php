<?php

namespace AssignmentFourTests\ControllerTests;

use AssignmentFour\Controllers\PostController;
use AssignmentFour\Exceptions\PostException;
use AssignmentFour\Router\JsonResponse;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;
use AssignmentFourTests\AssignmentFourTest;

final class PostControllerTest extends AssignmentFourTest
{
	public function testPostControllerCalledShow(): void
	{
		$post = $this->generatePost();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('GET');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$post->getId()]
		]);

		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was retrieved successfully!', $response->getMessage());
		$this->assertEquals($post->getId(), $response->getPayload()->getId());
		$this->assertEquals($post->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($post->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider findPostProvider
	 */
	public function testExceptionWasThrownShowingPost(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new PostController($request, new JsonResponse()))->doAction();
	}

	public function findPostProvider()
	{
		yield 'invalid ID' => [
			PostException::class,
			'Cannot find Post: Post does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testPostControllerCalledNew(): void
	{
		$user = $this->generateUser();
		$category = $this->generateCategory();
		$post = $this->generatePostData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('POST');
		$request->method('getParameters')->willReturn([
			'body' => [
				'userId' => $user->getId(),
				'categoryId' => $category->getId(),
				'title' => $post['title'],
				'type' => $post['type'],
				'content' => $post['content']
			],
			'header' => []
		]);

		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was created successfully!', $response->getMessage());
		$this->assertEquals($post['title'], $response->getPayload()->getTitle());
		$this->assertEquals($post['content'], $response->getPayload()->getContent());
		$this->assertNotEmpty($response->getPayload()->getId());
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testExceptionWasThrownNewingPost(string $exception, string $message, string $requestMethod, array $parameters, bool $generatePost = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generatePost) {
			$this->generatePost();
		}

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new PostController($request, new JsonResponse()))->doAction();
	}

	public function createPostProvider()
	{
		yield 'string user ID' => [
			PostException::class,
			'Cannot create Post: User ID must be an integer.',
			'POST',
			[
				'body' => [
					'userId' => 'abc',
					'categoryId' => 1,
					'title' => 'Top 10 Pokemon',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'invalid user ID' => [
			PostException::class,
			'Cannot create Post: User does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'userId' => 999,
					'categoryId' => 1,
					'title' => 'Top 10 Pokemon',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'string category ID' => [
			PostException::class,
			'Cannot create Post: Category ID must be an integer.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 'abc',
					'title' => 'Top 3 Pokemon!',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'invalid category ID' => [
			PostException::class,
			'Cannot create Post: Category does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 999,
					'title' => 'Top 3 Pokemon!',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			],
			true
		];

		yield 'blank title' => [
			PostException::class,
			'Cannot create Post: Missing title.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 1,
					'title' => '',
					'type' => 'Text',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			]
		];

		yield 'blank type' => [
			PostException::class,
			"Cannot create Post: Type must be 'Text' or 'URL'.",
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 1,
					'title' => 'Top 3 Pokemon!',
					'type' => '',
					'content' => '1. Magikarp 2. Rattata 3. Pidgey'
				],
				'header' => []
			]
		];

		yield 'blank content' => [
			PostException::class,
			'Cannot create Post: Missing content.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'categoryId' => 1,
					'title' => 'Top 3 Pokemon!',
					'type' => 'Text',
					'content' => ''
				],
				'header' => []
			]
		];
	}

	public function testPostControllerCalledEdit(): void
	{
		$post = $this->generatePost('Text');
		$newPostData = $this->generatePostData('Text');

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('PUT');
		$request->method('getParameters')->willReturn([
			'body' => [
				'content' => $newPostData['content'],
			],
			'header' => [$post->getId()]
		]);

		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was updated successfully!', $response->getMessage());
		$this->assertEquals($newPostData['content'], $response->getPayload()->getContent());
		$this->assertNotEquals($post->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider editPostProvider
	 */
	public function testExceptionWasThrownEditingPost(string $exception, string $message, string $requestMethod, array $parameters, bool $generatePost = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generatePost) {
			$this->generatePost(true);
		}

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new PostController($request, new JsonResponse()))->doAction();
	}

	public function editPostProvider()
	{
		yield 'invalid ID' => [
			PostException::class,
			'Cannot update Post: Post does not exist with ID 1.',
			'PUT',
			[
				'body' => [
					'content' => 'Pokemon are awesome!'
				],
				'header' => [1]
			]
		];

		yield 'blank content' => [
			PostException::class,
			'Cannot update Post: Missing content.',
			'PUT',
			[
				'body' => [
					'content' => ''
				],
				'header' => [1]
			],
			true
		];
	}

	public function testPostControllerCalledDestroy(): void
	{
		$post = $this->generatePost();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('DELETE');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$post->getId()]
		]);

		$controller = new PostController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Post was deleted successfully!', $response->getMessage());
		$this->assertEquals($post->getId(), $response->getPayload()->getId());
		$this->assertEquals($post->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($post->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider deletePostProvider
	 */
	public function testExceptionWasThrownDestroyingPost(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new PostController($request, new JsonResponse()))->doAction();
	}

	public function deletePostProvider()
	{
		yield 'invalid ID' => [
			PostException::class,
			'Cannot delete Post: Post does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}
}
