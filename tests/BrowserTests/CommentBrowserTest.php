<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFour\Models\Comment;
use AssignmentFourTests\AssignmentFourTest;
use Facebook\WebDriver\Exception\NoSuchElementException;

final class CommentBrowserTest extends AssignmentFourTest
{
	public function testCommentWasCreatedSuccessfully(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$commentData = $this->generateCommentData($postId);

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();

		$userIdInput = $this->findElement("form#new-comment-form input[name=\"userId\"]");
		$postIdInput = $this->findElement("form#new-comment-form input[name=\"postId\"]");
		$contentInput = $this->findElement("form#new-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-comment-form button");

		$this->assertStringContainsString($postId, $postIdInput->getAttribute('value'));

		$userIdInput->sendKeys($commentData['userId']);
		$contentInput->sendKeys($commentData['content']);
		$submitButton->click();

		$comment = Comment::findByPost($postId)[0];
		$commentId = $comment->getId();
		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($commentData['content'], $commentElement->getText());
		$this->assertStringContainsString($comment->getUser()->getUsername(), $commentElement->getText());
		$this->assertStringContainsString($comment->getCreatedAt(), $commentElement->getText());
	}

	public function testManyCommentsWereCreatedSuccessfully(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		for ($i = 0; $i < rand(2, 5); $i++) {
			$commentData = $this->generateCommentData($postId);

			self::$driver->get(self::$baseUri);
			$this->findElement("a[href*=\"category/$categoryId\"]")->click();
			$this->findElement("a[href*=\"post/$postId\"]")->click();

			$userIdInput = $this->findElement("form#new-comment-form input[name=\"userId\"]");
			$postIdInput = $this->findElement("form#new-comment-form input[name=\"postId\"]");
			$contentInput = $this->findElement("form#new-comment-form textarea[name=\"content\"]");
			$submitButton = $this->findElement("form#new-comment-form button");

			$this->assertStringContainsString($postId, $postIdInput->getAttribute('value'));

			$userIdInput->sendKeys($commentData['userId']);
			$contentInput->sendKeys($commentData['content']);
			$submitButton->click();

			$comment = Comment::findByPost($postId)[$i];
			$commentId = $comment->getId();
			$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

			$this->assertStringContainsString($commentData['content'], $commentElement->getText());
			$this->assertStringContainsString($comment->getUser()->getUsername(), $commentElement->getText());
			$this->assertStringContainsString($comment->getCreatedAt(), $commentElement->getText());
		}
	}

	/**
	 * @dataProvider createCommentProvider
	 */
	public function testCommentWasNotCreated(array $commentData, string $message): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();

		$userIdInput = $this->findElement("form#new-comment-form input[name=\"userId\"]");
		$contentInput = $this->findElement("form#new-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#new-comment-form button");

		$userIdInput->sendKeys($commentData['userId']);
		$contentInput->sendKeys($commentData['content']);
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString($message, $body->getText());
	}

	public function createCommentProvider()
	{
		yield 'invalid user ID' => [
			[
				'userId' => 999,
				'content' => 'You call that a top 3 pick?!'
			],
			'Cannot create Comment: User does not exist with ID 999.'
		];

		yield 'blank content' => [
			[
				'userId' => 1,
				'content' => ''
			],
			'Cannot create Comment: Missing content.'
		];
	}

	public function testCommentWasFoundById(): void
	{
		$comment = $this->generateComment();
		$commentId = $comment->getId();

		self::$driver->get(self::$baseUri . "comment/$commentId");

		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($comment->getUser()->getUsername(), $commentElement->getText());
		$this->assertStringContainsString($comment->getContent(), $commentElement->getText());
		$this->assertStringContainsString($comment->getCreatedAt(), $commentElement->getText());
	}

	/**
	 * @dataProvider commentAndRepliesProvider
	 */
	public function testCommentAndRepliesWereFound(array $commentOrder, array $answers): void
	{
		$post = $this->generatePost();
		$comments = [];

		foreach ($commentOrder as $comment) {
			if ($comment === null) {
				$comments[] = $this->generateComment($post->getId(), null, null);
			} else {
				$comments[] = $this->generateComment($post->getId(), null, $comments[$comment]->getId());
			}
		}

		for ($i = 0; $i < sizeOf($comments); $i++) {
			$commentId = $comments[$i]->getId();

			self::$driver->get(self::$baseUri . "comment/$commentId");

			$commentElements = $this->findElements(".comment");

			$this->assertEquals($answers[$i], sizeOf($commentElements));

			$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

			$this->assertStringContainsString($comments[$i]->getUser()->getUsername(), $commentElement->getText());
			$this->assertStringContainsString($comments[$i]->getContent(), $commentElement->getText());
			$this->assertStringContainsString($comments[$i]->getCreatedAt(), $commentElement->getText());
		}
	}

	/**
	 * The first array represents the comments that will be created in the test.
	 * [null] means one comment will be created with null as the parent.
	 * [null, 0] means one comment will be created with null as the parent and
	 * the next comment will be created with its parent as the comment at index 0. Index
	 * 0 being the first comment with null as the parent, in this case.
	 *
	 * The second array contains the number of comments that should be displayed on
	 * the page for when the corresponding comment in the first array is requested.
	 * In the first scenario, only one comment is created in the first array so the
	 * second array says there should only be one comment displayed for when that comment
	 * is requested. In the second scenario, if we request the comment whose parent is null,
	 * we should get back 2 comments: the requested comment and its one reply. If we request
	 * the second comment, we should only get back one comment: the requested comment and no
	 * replies.
	 */
	public function commentAndRepliesProvider()
	{
		yield 'scenario 1' => [
			[null],
			[1]
		];

		yield 'scenario 2' => [
			[null, 0],
			[2, 1]
		];

		yield 'scenario 3' => [
			[null, 0, 1, 0],
			[4, 2, 1, 1]
		];

		yield 'scenario 4' => [
			[null, 0, 0, null, 3, 3],
			[3, 1, 1, 3, 1, 1]
		];

		yield 'scenario 5' => [
			[null, 0, 0, 0, 1, 1, 2, 6, 6, 8],
			[10, 3, 5, 1, 1, 1, 4, 1, 2, 1]
		];
	}

	public function testCommentWasNotFoundByWrongId(): void
	{
		$randomCommentId = rand(1, 100);

		self::$driver->get(self::$baseUri . "/comment/$randomCommentId");

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot find Comment: Comment does not exist with ID $randomCommentId.", $body->getText());
	}

	public function testCommentWasUpdatedSuccessfully(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$comment = $this->generateComment($postId, null, null);
		$commentId = $comment->getId();
		$newCommentData = $this->generateCommentData();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("a[href*=\"comment/$commentId/edit\"]")->click();

		$contentInput = $this->findElement("form#edit-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-comment-form button");

		$this->assertStringContainsString($comment->getContent(), $contentInput->getText());

		$contentInput->sendKeys($newCommentData['content']);
		$submitButton->click();

		$commentElement = $this->findElement(".comment[comment-id=\"$commentId\"]");

		$this->assertStringContainsString($newCommentData['content'], $commentElement->getText());
	}

	public function testReplyWasUpdatedSuccessfully(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$comment = $this->generateComment($postId, null, null);
		$reply = $this->generateComment($postId, null, $comment->getId());
		$replyId = $reply->getId();
		$newCommentData = $this->generateCommentData();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("a[href*=\"comment/$replyId/edit\"]")->click();

		$contentInput = $this->findElement("form#edit-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-comment-form button");

		$this->assertStringContainsString($reply->getContent(), $contentInput->getText());

		$contentInput->sendKeys($newCommentData['content']);
		$submitButton->click();

		$commentElement = $this->findElement(".comment[comment-id=\"$replyId\"]");

		$this->assertStringContainsString($newCommentData['content'], $commentElement->getText());
	}

	public function testCommentWasNotUpdatedWithBlankContent(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$comment = $this->generateComment($postId, null, null);
		$commentId = $comment->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement("a[href*=\"comment/$commentId/edit\"]")->click();

		$contentInput = $this->findElement("form#edit-comment-form textarea[name=\"content\"]");
		$submitButton = $this->findElement("form#edit-comment-form button");

		$this->assertStringContainsString($comment->getContent(), $contentInput->getText());

		$contentInput->clear();
		$submitButton->click();

		$h1 = $this->findElement('h1');
		$body = $this->findElement('body');

		$this->assertStringContainsString('Error', $h1->getText());
		$this->assertStringContainsString("Cannot update Comment: Missing content.", $body->getText());
	}

	public function testCommentWasDeletedSuccessfully(): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$comment = $this->generateComment($postId, null, null);
		$commentId = $comment->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement(".comment[comment-id=\"$commentId\"] form.delete-comment-form button")->click();

		$deletedComment  = $this->findElement(".comment[comment-id=\"$commentId\"]");
		$deletedAt = Comment::findById($commentId)->getDeletedAt();

		$this->assertStringContainsString("Comment was deleted on $deletedAt", $deletedComment->getText());
	}

	/**
	 * @dataProvider deleteCommentProvider
	 */
	public function testDeletedCommentShouldHaveAnInterface(string $selector): void
	{
		$post = $this->generatePost();
		$postId = $post->getId();
		$categoryId = $post->getCategory()->getId();
		$comment = $this->generateComment($postId, null, null);
		$commentId = $comment->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"post/$postId\"]")->click();
		$this->findElement(".comment[comment-id=\"$commentId\"] form.delete-comment-form button")->click();

		$this->expectException(NoSuchElementException::class);
		$this->findElement(".comment[comment-id=\"$commentId\"] $selector");
	}

	public function deleteCommentProvider()
	{
		yield "no edit link" => ["a[href*=\"/edit\"]"];
		yield "no delete button" => ["form.delete-comment-form"];
		yield "no reply form" => ["form#new-reply-form"];
	}
}
