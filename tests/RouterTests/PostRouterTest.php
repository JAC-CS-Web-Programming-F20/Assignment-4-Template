<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFourTests\AssignmentFourTest;

final class PostRouterTest extends AssignmentFourTest
{
	public function testPostWasCreatedSuccessfully(): void
	{
		$postData = $this->generatePostData();

		$response = $this->getResponse(
			'POST',
			'post',
			$postData
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('user', $response['payload']);
		$this->assertArrayHasKey('category', $response['payload']);
		$this->assertArrayHasKey('title', $response['payload']);
		$this->assertArrayHasKey('type', $response['payload']);
		$this->assertArrayHasKey('content', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($postData['userId'], $response['payload']['user']['id']);
		$this->assertEquals($postData['categoryId'], $response['payload']['category']['id']);
		$this->assertEquals($postData['title'], $response['payload']['title']);
		$this->assertEquals($postData['type'], $response['payload']['type']);
		$this->assertEquals($postData['content'], $response['payload']['content']);
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testPostWasNotCreated(array $postData, string $message): void
	{
		self::generateCategory();

		$response = $this->getResponse(
			'POST',
			'post',
			$postData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
	}

	public function createPostProvider()
	{
		yield 'string user ID' => [
			[
				'userId' => 'abc',
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: User ID must be an integer.'
		];

		yield 'invalid user ID' => [
			[
				'userId' => 999,
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: User does not exist with ID 999.'
		];

		yield 'string category ID' => [
			[
				'userId' => 1,
				'categoryId' => 'abc',
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Category ID must be an integer.'
		];

		yield 'invalid category ID' => [
			[
				'userId' => 1,
				'categoryId' => 999,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Category does not exist with ID 999.'
		];

		yield 'blank title' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => '',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Missing title.'
		];

		yield 'blank type' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => '',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			"Cannot create Post: Type must be 'Text' or 'URL'."
		];

		yield 'blank content' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => ''
			],
			'Cannot create Post: Missing content.'
		];
	}

	public function testPostWasFoundById(): void
	{
		$post = $this->generatePost();

		$retrievedPost = $this->getResponse(
			'GET',
			'post/' . $post->getId()
		)['payload'];

		$this->assertArrayHasKey('id', $retrievedPost);
		$this->assertArrayHasKey('user', $retrievedPost);
		$this->assertArrayHasKey('category', $retrievedPost);
		$this->assertArrayHasKey('title', $retrievedPost);
		$this->assertArrayHasKey('type', $retrievedPost);
		$this->assertArrayHasKey('content', $retrievedPost);
		$this->assertEquals($post->getId(), $retrievedPost['id']);
		$this->assertEquals($post->getUser()->getId(), $retrievedPost['user']['id']);
		$this->assertEquals($post->getCategory()->getId(), $retrievedPost['category']['id']);
		$this->assertEquals($post->getTitle(), $retrievedPost['title']);
		$this->assertEquals($post->getType(), $retrievedPost['type']);
		$this->assertEquals($post->getContent(), $retrievedPost['content']);
	}

	public function testPostWasNotFoundByWrongId(): void
	{
		$retrievedPost = $this->getResponse(
			'GET',
			'post/1',
		);

		$this->assertEquals('Cannot find Post: Post does not exist with ID 1.', $retrievedPost['message']);
		$this->assertEmpty($retrievedPost['payload']);
	}

	/**
	 * @dataProvider updatedPostProvider
	 */
	public function testPostWasUpdated(array $oldPostData, array $newPostData, array $editedFields): void
	{
		$this->generatePost();

		$oldPost = $this->getResponse(
			'POST',
			'post',
			$oldPostData
		)['payload'];

		$editedPost = $this->getResponse(
			'PUT',
			'post/' . $oldPost['id'],
			$newPostData
		)['payload'];

		/**
		 * Check every Post field against all the fields that were supposed to be edited.
		 * If the Post field is a field that's supposed to be edited, check if they're not equal.
		 * If the Post field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldPost as $oldPostKey => $oldPostValue) {
			foreach ($editedFields as $editedField) {
				if ($oldPostKey === $editedField) {
					$this->assertNotEquals($oldPostValue, $editedPost[$editedField]);
					$this->assertEquals($editedPost[$editedField], $newPostData[$editedField]);
				}
			}
		}
	}

	public function updatedPostProvider()
	{
		yield 'valid content' => [
			[
				'title' => 'Top 3 Pokemon',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey',
				'userId' => 1,
				'categoryId' => 1
			],
			['content' => 'Bulbasaur'],
			['content'],
		];
	}

	/**
	 * @dataProvider updatePostProvider
	 */
	public function testPostWasNotUpdated(int $postId, string $type, array $newPostData, string $message): void
	{
		self::generatePost($type);

		$editedPost = $this->getResponse(
			'PUT',
			'post/' . $postId,
			$newPostData
		);

		$this->assertEquals($message, $editedPost['message']);
		$this->assertEmpty($editedPost['payload']);
	}

	public function updatePostProvider()
	{
		yield 'blank text content' => [
			1,
			'text',
			['content' => ''],
			'Cannot update Post: Missing content.'
		];

		yield 'blank url content' => [
			1,
			'url',
			['content' => ''],
			'Cannot update Post: Missing content.'
		];

		yield 'new url' => [
			1,
			'url',
			['content' => 'www.nintendo.com'],
			'Cannot update Post: Only text posts are updateable.'
		];

		yield 'invalid ID' => [
			999,
			'text',
			['content' => 'Pokemon'],
			'Cannot update Post: Post does not exist with ID 999.'
		];
	}

	public function testPostWasDeletedSuccessfully(): void
	{
		$randomPost = $this->generatePostData();

		$oldPost = $this->getResponse(
			'POST',
			'post',
			$randomPost
		)['payload'];

		$this->assertEmpty($oldPost['deletedAt']);

		$deletedPost = $this->getResponse(
			'DELETE',
			'post/' . $oldPost['id']
		)['payload'];

		$this->assertEquals($oldPost['id'], $deletedPost['id']);
		$this->assertEquals($oldPost['title'], $deletedPost['title']);
		$this->assertEquals($oldPost['content'], $deletedPost['content']);

		$retrievedPost = $this->getResponse(
			'GET',
			'post/' . $oldPost['id'],
		)['payload'];

		$this->assertNotEmpty($retrievedPost['deletedAt']);
	}

	public function testPostWasNotDeleted(): void
	{
		$deletedPost = $this->getResponse(
			'DELETE',
			'post/999'
		);

		$this->assertEquals('Cannot delete Post: Post does not exist with ID 999.', $deletedPost['message']);
		$this->assertEmpty($deletedPost['payload']);
	}
}
