<?php

namespace AssignmentFourTests\ModelTests;

use AssignmentFour\Exceptions\PostException;
use AssignmentFour\Models\Post;
use AssignmentFourTests\AssignmentFourTest;

final class PostTest extends AssignmentFourTest
{
	public function testPostWasCreatedSuccessfully(): void
	{
		$this->assertInstanceOf(Post::class, $this->generatePost());
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testExceptionWasThrownWhenCreatingPost(array $parameters, string $exception, string $message): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$this->generatePost();

		Post::create(
			$parameters['userId'],
			$parameters['categoryId'],
			$parameters['title'],
			$parameters['type'],
			$parameters['content']
		);
	}

	public function createPostProvider()
	{
		yield 'invalid user ID 999' => [
			[
				'userId' => 999,
				'categoryId' => 1,
				'title' => 'Top 10 Pokemon',
				'type' => 'Text',
				'content' => 'The best Pokemon community ever!'
			],
			PostException::class,
			'Cannot create Post: User does not exist with ID 999.'
		];

		yield 'invalid category ID 999' => [
			[
				'userId' => 1,
				'categoryId' => 999,
				'title' => 'Top 10 Pokemon',
				'type' => 'Text',
				'content' => 'The best Pokemon community ever!'
			],
			PostException::class,
			'Cannot create Post: Category does not exist with ID 999.'
		];

		yield 'blank title' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => '',
				'type' => 'Text',
				'content' => 'The best Pokemon community ever!'
			],
			PostException::class,
			'Cannot create Post: Missing title.'
		];

		yield 'blank type' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => 'Top 10 Pokemon',
				'type' => '',
				'content' => 'The best Pokemon community ever!'
			],
			PostException::class,
			'Cannot create Post: Missing type.'
		];

		yield 'blank content' => [
			[
				'userId' => 1,
				'categoryId' => 1,
				'title' => 'Top 10 Pokemon',
				'type' => 'Text',
				'content' => ''
			],
			PostException::class,
			'Cannot create Post: Missing content.'
		];
	}

	public function testPostWasFoundById(): void
	{
		$newPost = $this->generatePost();
		$retrievedPost = Post::findById($newPost->getId());

		$this->assertEquals(
			$retrievedPost->getTitle(),
			$newPost->getTitle()
		);
	}

	public function testPostWasNotFoundByWrongId(): void
	{
		$newPost = $this->generatePost();
		$retrievedPost = Post::findById($newPost->getId() + 1);

		$this->assertNull($retrievedPost);
	}

	public function testTextPostContentsWasUpdatedSuccessfully(): void
	{
		$oldPost = $this->generatePost();
		$oldPost->setType('Text');
		$newPostContent = self::$faker->paragraph();

		$oldPost->setContent($newPostContent);
		$this->assertNull($oldPost->getEditedAt());
		$this->assertTrue($oldPost->save());

		$retrievedPost = Post::findById($oldPost->getId());
		$this->assertEquals($newPostContent, $retrievedPost->getContent());
		$this->assertNotNull($retrievedPost->getEditedAt());
	}

	public function testExceptionWasThrownWhenUpdatingPostWithBlankContent(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot update Post: Missing content.");

		$post = $this->generatePost('Text');
		$post->setContent('');
		$post->save();
	}

	public function testExceptionWasThrownWhenUpdatingUrlPost(): void
	{
		$this->expectException(PostException::class);
		$this->expectExceptionMessage("Cannot update Post: Only text posts are updateable.");

		$post = $this->generatePost();
		$post->setType('URL');
		$post->save();
	}

	public function testPostWasDeletedSuccessfully(): void
	{
		$post = $this->generatePost();

		$this->assertNull($post->getDeletedAt());
		$this->assertTrue($post->remove());

		$retrievedPost = Post::findById($post->getId());
		$this->assertNotNull($retrievedPost->getDeletedAt());
	}
}
