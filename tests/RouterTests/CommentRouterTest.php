<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFourTests\AssignmentFourTest;

final class CommentRouterTest extends AssignmentFourTest
{
	public function testCommentWasCreatedSuccessfully(): void
	{
		$commentData = $this->generateCommentData();

		$response = $this->getResponse(
			'POST',
			'comment',
			$commentData
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('user', $response['payload']);
		$this->assertArrayHasKey('post', $response['payload']);
		$this->assertArrayHasKey('reply', $response['payload']);
		$this->assertArrayHasKey('content', $response['payload']);
		$this->assertArrayHasKey('replies', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($commentData['userId'], $response['payload']['user']['id']);
		$this->assertEquals($commentData['postId'], $response['payload']['post']['id']);
		$this->assertEmpty($response['payload']['reply']);
		$this->assertEquals($commentData['content'], $response['payload']['content']);
	}

	/**
	 * @dataProvider createCommentProvider
	 */
	public function testCommentWasNotCreated(array $commentData, string $message): void
	{
		self::generatePost();

		$response = $this->getResponse(
			'POST',
			'comment',
			$commentData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
	}

	public function createCommentProvider()
	{
		yield 'string user ID' => [
			[
				'userId' => 'abc',
				'postId' => 1,
				'content' => 'You call that a top 3 pick?!'
			],
			'Cannot create Comment: User ID must be an integer.'
		];

		yield 'invalid user ID' => [
			[
				'userId' => 999,
				'postId' => 1,
				'content' => 'You call that a top 3 pick?!'
			],
			'Cannot create Comment: User does not exist with ID 999.'
		];

		yield 'string post ID' => [
			[
				'userId' => 1,
				'postId' => 'abc',
				'content' => 'You call that a top 3 pick?!'
			],
			'Cannot create Comment: Post ID must be an integer.'
		];

		yield 'invalid post ID' => [
			[
				'userId' => 1,
				'postId' => 999,
				'content' => 'You call that a top 3 pick?!'
			],
			'Cannot create Comment: Post does not exist with ID 999.'
		];

		yield 'blank content' => [
			[
				'userId' => 1,
				'postId' => 1,
				'content' => ''
			],
			'Cannot create Comment: Missing content.'
		];
	}

	public function testCommentWasFoundById(): void
	{
		$comment = $this->generateComment();

		$retrievedComment = $this->getResponse(
			'GET',
			'comment/' . $comment->getId()
		)['payload'];

		$this->assertArrayHasKey('id', $retrievedComment);
		$this->assertArrayHasKey('user', $retrievedComment);
		$this->assertArrayHasKey('post', $retrievedComment);
		$this->assertArrayHasKey('content', $retrievedComment);
		$this->assertEquals($comment->getId(), $retrievedComment['id']);
		$this->assertEquals($comment->getUser()->getId(), $retrievedComment['user']['id']);
		$this->assertEquals($comment->getPost()->getId(), $retrievedComment['post']['id']);
		$this->assertEquals($comment->getContent(), $retrievedComment['content']);
	}

	public function testCommentWasNotFoundByWrongId(): void
	{
		$retrievedComment = $this->getResponse(
			'GET',
			'comment/1',
		);

		$this->assertEquals('Cannot find Comment: Comment does not exist with ID 1.', $retrievedComment['message']);
		$this->assertEmpty($retrievedComment['payload']);
	}

	/**
	 * @dataProvider updatedCommentProvider
	 */
	public function testCommentWasUpdated(array $oldCommentData, array $newCommentData, array $editedFields): void
	{
		$this->generateComment();

		$oldComment = $this->getResponse(
			'POST',
			'comment',
			$oldCommentData
		)['payload'];

		$editedComment = $this->getResponse(
			'PUT',
			'comment/' . $oldComment['id'],
			$newCommentData
		)['payload'];

		/**
		 * Check every Comment field against all the fields that were supposed to be edited.
		 * If the Comment field is a field that's supposed to be edited, check if they're not equal.
		 * If the Comment field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldComment as $oldCommentKey => $oldCommentValue) {
			foreach ($editedFields as $editedField) {
				if ($oldCommentKey === $editedField) {
					$this->assertNotEquals($oldCommentValue, $editedComment[$editedField]);
					$this->assertEquals($editedComment[$editedField], $newCommentData[$editedField]);
				}
			}
		}
	}

	public function updatedCommentProvider()
	{
		yield 'valid content' => [
			['postId' => 1, 'userId' => 1, 'content' => 'pikachu@pokemon.com', 'replyId' => null],
			['content' => 'Bulbasaur'],
			['content'],
		];
	}

	/**
	 * @dataProvider updateCommentProvider
	 */
	public function testCommentWasNotUpdated(int $commentId, array $newCommentData, string $message): void
	{
		self::generateComment();

		$editedComment = $this->getResponse(
			'PUT',
			'comment/' . $commentId,
			$newCommentData
		);

		$this->assertEquals($message, $editedComment['message']);
		$this->assertEmpty($editedComment['payload']);
	}

	public function updateCommentProvider()
	{
		yield 'blank content' => [
			1,
			['content' => ''],
			'Cannot update Comment: Missing content.'
		];
	}

	public function testCommentWasDeletedSuccessfully(): void
	{
		$randomComment = $this->generateCommentData();

		$oldComment = $this->getResponse(
			'POST',
			'comment',
			$randomComment
		)['payload'];

		$this->assertEmpty($oldComment['deletedAt']);

		$deletedComment = $this->getResponse(
			'DELETE',
			'comment/' . $oldComment['id']
		)['payload'];

		$this->assertEquals($oldComment['id'], $deletedComment['id']);
		$this->assertEquals($oldComment['content'], $deletedComment['content']);

		$retrievedComment = $this->getResponse(
			'GET',
			'comment/' . $oldComment['id'],
		)['payload'];

		$this->assertNotEmpty($retrievedComment['deletedAt']);
	}

	public function testCommentWasNotDeleted(): void
	{
		$deletedComment = $this->getResponse(
			'DELETE',
			'comment/999'
		);

		$this->assertEquals('Cannot delete Comment: Comment does not exist with ID 999.', $deletedComment['message']);
		$this->assertEmpty($deletedComment['payload']);
	}
}
