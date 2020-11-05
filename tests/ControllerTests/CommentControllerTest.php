<?php

namespace AssignmentFourTests\ControllerTests;

use AssignmentFour\Controllers\CommentController;
use AssignmentFour\Exceptions\CommentException;
use AssignmentFour\Router\JsonResponse;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;
use AssignmentFourTests\AssignmentFourTest;

final class CommentControllerTest extends AssignmentFourTest
{
	public function testCommentControllerCalledShow(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('GET');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$comment->getId()]
		]);

		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was retrieved successfully!', $response->getMessage());
		$this->assertEquals($comment->getUser()->getId(), $response->getPayload()->getUser()->getId());
		$this->assertEquals($comment->getPost()->getId(), $response->getPayload()->getPost()->getId());
		$this->assertEquals($comment->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider findCommentProvider
	 */
	public function testExceptionWasThrownShowingComment(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CommentController($request, new JsonResponse()))->doAction();
	}

	public function findCommentProvider()
	{
		yield 'invalid ID' => [
			CommentException::class,
			'Cannot find Comment: Comment does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testCommentControllerCalledNew(): void
	{
		$comment = $this->generateCommentData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('POST');
		$request->method('getParameters')->willReturn([
			'body' => [
				'postId' => $comment['postId'],
				'userId' => $comment['userId'],
				'content' => $comment['content'],
				'replyId' => $comment['replyId']
			],
			'header' => []
		]);

		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was created successfully!', $response->getMessage());
		$this->assertEquals($comment['userId'], $response->getPayload()->getUser()->getId());
		$this->assertEquals($comment['content'], $response->getPayload()->getContent());
		$this->assertNotEmpty($response->getPayload()->getId());
	}

	/**
	 * @dataProvider createCommentProvider
	 */
	public function testExceptionWasThrownNewingComment(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$this->generatePost();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CommentController($request, new JsonResponse()))->doAction();
	}

	public function createCommentProvider()
	{
		yield 'blank content' => [
			CommentException::class,
			'Cannot create Comment: Missing content.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'postId' => 1,
					'content' => '',
					'replyId' => null
				],
				'header' => []
			]
		];

		yield 'non-existant user' => [
			CommentException::class,
			'Cannot create Comment: User does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'userId' => 999,
					'postId' => 1,
					'content' => 'The best Pokemon community!',
					'replyId' => null
				],
				'header' => []
			]
		];

		yield 'non-existant category' => [
			CommentException::class,
			'Cannot create Comment: Post does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'userId' => 1,
					'postId' => 999,
					'content' => 'The best Pokemon community!',
					'replyId' => null
				],
				'header' => []
			]
		];
	}

	public function testCommentControllerCalledEdit(): void
	{
		$comment = $this->generateComment();
		$newCommentData = $this->generateCommentData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('PUT');
		$request->method('getParameters')->willReturn([
			'body' => [
				'content' => $newCommentData['content'],
			],
			'header' => [$comment->getId()]
		]);

		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was updated successfully!', $response->getMessage());
		$this->assertEquals($newCommentData['content'], $response->getPayload()->getContent());
		$this->assertNotEquals($comment->getContent(), $response->getPayload()->getUser()->getId());
	}

	/**
	 * @dataProvider editCommentProvider
	 */
	public function testExceptionWasThrownEditingComment(string $exception, string $message, string $requestMethod, array $parameters, bool $generateComment = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generateComment) {
			$this->generateComment();
		}

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CommentController($request, new JsonResponse()))->doAction();
	}

	public function editCommentProvider()
	{
		yield 'invalid ID' => [
			CommentException::class,
			'Cannot update Comment: Comment does not exist with ID 1.',
			'PUT',
			[
				'body' => [
					'content' => 'Pokemon are awesome!'
				],
				'header' => [1]
			]
		];

		yield 'blank content' => [
			CommentException::class,
			'Cannot update Comment: Missing content.',
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

	public function testCommentControllerCalledDestroy(): void
	{
		$comment = $this->generateComment();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('DELETE');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$comment->getId()]
		]);

		$controller = new CommentController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Comment was deleted successfully!', $response->getMessage());
		$this->assertEquals($comment->getUser()->getId(), $response->getPayload()->getUser()->getId());
		$this->assertEquals($comment->getPost()->getId(), $response->getPayload()->getPost()->getId());
		$this->assertEquals($comment->getContent(), $response->getPayload()->getContent());
	}

	/**
	 * @dataProvider deleteCommentProvider
	 */
	public function testExceptionWasThrownDestroyingComment(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CommentController($request, new JsonResponse()))->doAction();
	}

	public function deleteCommentProvider()
	{
		yield 'invalid ID' => [
			CommentException::class,
			'Cannot delete Comment: Comment does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}
}
