<?php

namespace AssignmentFourTests\ModelTests;

use AssignmentFour\Exceptions\CommentException;
use AssignmentFour\Models\Comment;
use AssignmentFourTests\AssignmentFourTest;

final class CommentTest extends AssignmentFourTest
{
	public function testCommentWasCreatedSuccessfully(): void
	{
		$this->assertInstanceOf(Comment::class, $this->generateComment());
	}

	/**
	 * @dataProvider createCommentProvider
	 */
	public function testExceptionWasThrownWhenCreatingComment(array $parameters, string $exception, string $message): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$this->generateComment();

		Comment::create(
			$parameters['postId'],
			$parameters['userId'],
			$parameters['content'],
			$parameters['replyId']
		);
	}

	public function createCommentProvider()
	{
		yield 'invalid user ID 999' => [
			[
				'postId' => 1,
				'userId' => 999,
				'content' => 'The best Pokemon community ever!',
				'replyId' => null,
			],
			CommentException::class,
			'Cannot create Comment: User does not exist with ID 999.'
		];

		yield 'invalid post ID 999' => [
			[
				'postId' => 999,
				'userId' => 1,
				'content' => 'The best Pokemon community ever!',
				'replyId' => null
			],
			CommentException::class,
			'Cannot create Comment: Post does not exist with ID 999.'
		];

		yield 'blank content' => [
			[
				'postId' => 1,
				'userId' => 1,
				'content' => '',
				'replyId' => null
			],
			CommentException::class,
			'Cannot create Comment: Missing content.'
		];

		// yield 'invalid reply ID 999' => [
		// 	[
		// 		'userId' => 1,
		// 		'postId' => 1,
		// 		'content' => '',
		// 		'replyId' => 999
		// 	],
		// 	CommentException::class,
		// 	'Cannot create Comment: Comment does not exist with ID 999.'
		// ];
	}

	public function testCommentWasFoundById(): void
	{
		$newComment = $this->generateComment();
		$retrievedComment = Comment::findById($newComment->getId());

		$this->assertEquals(
			$retrievedComment->getContent(),
			$newComment->getContent()
		);
	}

	public function testCommentWasNotFoundByWrongId(): void
	{
		$newComment = $this->generateComment();
		$retrievedComment = Comment::findById($newComment->getId() + 1);

		$this->assertNull($retrievedComment);
	}

	public function testCommentContentsWasUpdatedSuccessfully(): void
	{
		$oldComment = $this->generateComment();
		$newCommentContent = self::$faker->paragraph();

		$oldComment->setContent($newCommentContent);
		$this->assertNull($oldComment->getEditedAt());
		$this->assertTrue($oldComment->save());

		$retrievedComment = Comment::findById($oldComment->getId());
		$this->assertEquals($newCommentContent, $retrievedComment->getContent());
		$this->assertNotNull($retrievedComment->getEditedAt());
	}

	public function testCommentWasDeletedSuccessfully(): void
	{
		$comment = $this->generateComment();

		$this->assertNull($comment->getDeletedAt());
		$this->assertTrue($comment->remove());

		$retrievedComment = Comment::findById($comment->getId());
		$this->assertNotNull($retrievedComment->getDeletedAt());
	}
}
