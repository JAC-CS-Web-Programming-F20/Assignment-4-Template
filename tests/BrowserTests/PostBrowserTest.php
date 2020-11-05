<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFour\Models\Post;
use AssignmentFourTests\AssignmentFourTest;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverSelect;

final class PostBrowserTest extends AssignmentFourTest
{
	public function testPostWasCreatedSuccessfully(): void
	{
		$postData = $this->generatePostData();
		$categoryId = $postData['categoryId'];

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();

		$userIdInput = $this->findElement("form#new-post-form input[name=\"userId\"]");
		$categoryIdInput = $this->findElement("form#new-post-form input[name=\"categoryId\"]");
		$titleInput = $this->findElement("form#new-post-form input[name=\"title\"]");
		$typeSelect = new WebDriverSelect($this->findElement("form#new-post-form select"));
		$contentInput = $this->findElement("form#new-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-post-form button");

		$this->assertStringContainsString($categoryId, $categoryIdInput->getAttribute("value"));

		$userIdInput->sendKeys($postData['userId']);
		$titleInput->sendKeys($postData['title']);
		$typeSelect->selectByValue($postData['type']);
		$contentInput->sendKeys($postData['content']);
		$submitButton->click();

		$post = Post::findByUser($postData['userId'])[0];
		$posts = $this->findElement('#posts');

		$this->assertStringContainsString($postData['title'], $posts->getText());
		$this->assertStringContainsString($post->getUser()->getUsername(), $posts->getText());
		$this->assertStringContainsString($post->getCreatedAt(), $posts->getText());
	}

	public function testManyPostsWereCreatedSuccessfully(): void
	{
		for ($i = 0; $i < rand(2, 5); $i++) {
			$postData = $this->generatePostData();
			$categoryId = $postData['categoryId'];

			self::$driver->get(self::$baseUri);
			$this->findElement("a[href*=\"category/$categoryId\"]")->click();

			$userIdInput = $this->findElement("form#new-post-form input[name=\"userId\"]");
			$categoryIdInput = $this->findElement("form#new-post-form input[name=\"categoryId\"]");
			$titleInput = $this->findElement("form#new-post-form input[name=\"title\"]");
			$typeSelect = new WebDriverSelect($this->findElement("form#new-post-form select"));
			$contentInput = $this->findElement("form#new-post-form textarea[name=\"content\"]");
			$submitButton = $this->findElement("form#new-post-form button");

			$this->assertStringContainsString($categoryId, $categoryIdInput->getAttribute('value'));

			$userIdInput->sendKeys($postData['userId']);
			$titleInput->sendKeys($postData['title']);
			$typeSelect->selectByValue($postData['type']);
			$contentInput->sendKeys($postData['content']);
			$submitButton->click();

			$post = Post::findByUser($postData['userId'])[0];
			$posts = $this->findElement('#posts');

			$this->assertStringContainsString($postData['title'], $posts->getText());
			$this->assertStringContainsString($post->getUser()->getUsername(), $posts->getText());
			$this->assertStringContainsString($post->getCreatedAt(), $posts->getText());
		}
	}

	/**
	 * @dataProvider createPostProvider
	 */
	public function testPostWasNotCreated(array $postData, string $message): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();

		$userIdInput = $this->findElement("form#new-post-form input[name=\"userId\"]");
		$titleInput = $this->findElement("form#new-post-form input[name=\"title\"]");
		$typeSelect = new WebDriverSelect($this->findElement("form#new-post-form select"));
		$contentInput = $this->findElement("form#new-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-post-form button");

		$userIdInput->sendKeys($postData['userId']);
		$titleInput->sendKeys($postData['title']);
		$typeSelect->selectByValue($postData['type']);
		$contentInput->sendKeys($postData['content']);
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString($message, $body->getText());
	}

	public function createPostProvider()
	{
		yield 'invalid user ID' => [
			[
				'userId' => 999,
				'title' => 'Top 3 Pokemon!',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: User does not exist with ID 999.'
		];

		yield 'blank title' => [
			[
				'userId' => 1,
				'title' => '',
				'type' => 'Text',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			'Cannot create Post: Missing title.'
		];

		yield 'blank type' => [
			[
				'userId' => 1,
				'title' => 'Top 3 Pokemon!',
				'type' => '',
				'content' => '1. Magikarp 2. Rattata 3. Pidgey'
			],
			"Cannot create Post: Type must be 'Text' or 'URL'."
		];

		yield 'blank content' => [
			[
				'userId' => 1,
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
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();

		$postTitle = $this->findElement("#post-title");
		$postContent = $this->findElement("#post-content");

		$this->assertStringContainsString($post->getTitle(), $postTitle->getText());
		$this->assertStringContainsString($post->getContent(), $postContent->getText());
	}

	public function testPostWasNotFoundByWrongId(): void
	{
		$randomPostId = rand(1, 100);
		self::$driver->get(self::$baseUri . "/post/$randomPostId");

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find Post: Post does not exist with ID $randomPostId.", $body->getText());
	}

	public function testTextPostWasUpdatedSuccessfully(): void
	{
		$post = $this->generatePost('Text');
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$newPostData = $this->generatePostData('Text');

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("a[href*=\"post/$postId/edit\"]")->click();

		$contentInput = $this->findElement("form#edit-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-post-form button");

		$this->assertStringContainsString($post->getContent(), $contentInput->getText());

		$contentInput->sendKeys($newPostData['content']);
		$submitButton->click();

		$postTitle = $this->findElement('#post-title');
		$postContent = $this->findElement('#post-content');

		$this->assertStringContainsString($post->getTitle(), $postTitle->getText());
		$this->assertStringContainsString($newPostData['content'], $postContent->getText());
	}

	public function testTextPostWasNotUpdatedWithBlankContent(): void
	{
		$post = $this->generatePost('Text');
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("a[href*=\"post/$postId/edit\"]")->click();

		$contentInput = $this->findElement("form#edit-post-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-post-form button");

		$this->assertStringContainsString($post->getContent(), $contentInput->getAttribute('value'));

		$contentInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString('Cannot update Post: Missing content.', $body->getText());
	}

	public function testNoUpdateInterfaceForUrlPost(): void
	{
		$post = $this->generatePost('URL');
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();

		$this->expectException(NoSuchElementException::class);
		$this->findElement('form#edit-post-form');
	}

	public function testPostWasDeletedSuccessfully(): void
	{
		$post = $this->generatePost('Text');
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("form#delete-post-form button")->click();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$postsRow = $this->findElement("tr[post-id=\"$postId\"]");

		$this->assertStringContainsString($post->getTitle(), $postsRow->getText());
		$this->assertStringContainsString($post->getUser()->getUsername(), $postsRow->getText());
		$this->assertStringContainsString('Yes', $postsRow->getText());
	}

	/**
	 * @dataProvider deletePostProvider
	 */
	public function testDeletedPostShouldHaveAnInterface(string $selector): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("form#delete-post-form button")->click();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();

		$this->expectException(NoSuchElementException::class);
		$this->findElement($selector);
	}

	public function deletePostProvider()
	{
		yield 'no edit post form' => ['form#edit-post-form'];
		yield 'no delete post form' => ['form#delete-post-form'];
		yield 'no new comment form' => ['form#new-comment-form'];
	}
}
