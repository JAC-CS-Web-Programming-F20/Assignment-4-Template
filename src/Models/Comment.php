<?php

namespace AssignmentFour\Models;

use AssignmentFour\Exceptions\CommentException;

class Comment extends Model
{
	protected User $user;
	protected Post $post;
	protected ?Comment $reply;
	protected string $content;
	private array $replies;

	private function __construct()
	{
		$this->setEditedAt(null);
		$this->setDeletedAt(null);
		$this->setCreatedAt('');
	}

	static public function create(int $postId, int $userId, string $content, int $replyId = null): Comment
	{
		if (empty($content)) {
			throw new CommentException("Cannot create Comment: Missing content.");
		}

		if (empty(User::findById($userId))) {
			throw new CommentException("Cannot create Comment: User does not exist with ID $userId.");
		}

		if (empty(Post::findById($postId))) {
			throw new CommentException("Cannot create Comment: Post does not exist with ID $postId.");
		}

		$commentData = [
			'user_id' => $userId,
			'post_id' => $postId,
			'content' => $content,
			'reply_id' => $replyId
		];

		$commentData['id'] = parent::_create(__CLASS__, $commentData);

		return self::build($commentData);
	}

	static public function findById(int $id): ?Comment
	{
		$comment = parent::_findBy(__CLASS__, 'id', $id);

		if (empty($comment)) {
			return null;
		}

		return self::build($comment);
	}

	static public function findAllReplies(int $replyId): array
	{
		$sql = "SELECT `id`, `post_id`, `user_id`, `content`, `created_at`, `edited_at`, `deleted_at` FROM `comment` WHERE `reply_id` = ?;";

		self::connect();

		$statement = self::$connection->prepare($sql);
		$statement->bind_param('i', $replyId);
		$statement->execute();
		$statement->bind_result($id, $postId, $userId, $content, $createdAt, $editedAt, $deletedAt, $commentId);

		$replies = [];

		while ($statement->fetch() !== null) {
			$comment = (new self())
				->setUser($userId)
				->setPost($postId)
				->setReplyTo($replyId)
				->setContent($content)
				->setCreatedAt($createdAt)
				->setEditedAt($editedAt)
				->setDeletedAt($deletedAt)
				->setId($id);

			array_push($replies, $comment);
		}

		$statement->close();

		return $replies;
	}

	static public function findByUser(int $userId): array
	{
		$sql = "SELECT `id`, `post_id`, `reply_id`, `content`, `created_at`, `edited_at`, `deleted_at` FROM `comment` WHERE `user_id` = ?;";

		self::connect();

		$statement = self::$connection->prepare($sql);
		$statement->bind_param('i', $userId);
		$statement->execute();
		$statement->bind_result($id, $postId, $replyId, $content, $createdAt, $editedAt, $deletedAt);
		$statement->store_result();

		while ($statement->fetch()) {
			$comments[] = (new self())
				->setUser($userId)
				->setPost($postId)
				->setReplyTo($replyId)
				->setContent($content)
				->setCreatedAt($createdAt)
				->setEditedAt($editedAt)
				->setDeletedAt($deletedAt)
				->setId($id);
		}

		$statement->close();

		return $comments;
	}

	private static function build(array $fields): ?Comment
	{
		if (empty($fields)) {
			return null;
		}

		$comment = new self();
		$comment->originalValues = $fields;

		empty($fields['user_id']) ? $comment->originalValues['user_id'] = 0 : $comment->setUser($fields['user_id']);
		empty($fields['post_id']) ? $comment->originalValues['post_id'] = 0 : $comment->setPost($fields['post_id']);
		empty($fields['content']) ? $comment->originalValues['content'] = '' : $comment->setContent($fields['content']);
		empty($fields['reply_id']) ? $comment->originalValues['reply_id'] = 0 : $comment->setReplyTo($fields['reply_id']);
		empty($fields['created_at']) ? $comment->originalValues['created_at'] = '' : $comment->setCreatedAt($fields['created_at']);
		empty($fields['edited_at']) ? $comment->originalValues['edited_at'] = null : $comment->setEditedAt($fields['edited_at']);
		empty($fields['deleted_at']) ? $comment->originalValues['deleted_at'] = null : $comment->setDeletedAt($fields['deleted_at']);
		empty($fields['id']) ? $comment->originalValues['id'] = 0 : $comment->setId($fields['id']);

		return $comment;
	}

	public function save(): bool
	{
		if (empty($this->content)) {
			throw new CommentException("Cannot update Comment: Missing content.");
		}

		return parent::_save($this);
	}

	public function remove(): bool
	{
		return parent::_remove(__CLASS__, 'id', $this->id);
	}

	public function jsonSerialize()
	{
		return [
			'id' => $this->id,
			'user' => $this->user ?? [],
			'post' => $this->post ?? [],
			'reply' => $this->reply ?? [],
			'content' => $this->content ?? '',
			'replies' => $this->replies ?? [],
			'createdAt' => $this->createdAt ?? '',
			'editedAt' => $this->editedAt ?? '',
			'deletedAt' => $this->deletedAt ?? ''
		];
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function getPost(): Post
	{
		return $this->post;
	}

	public function getReplyTo(): Comment
	{
		return $this->reply;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function getReplies(): array
	{
		return $this->replies;
	}

	public function setPost(string $postId): self
	{
		$this->post = Post::findById($postId);
		return $this;
	}

	public function setUser(int $userId): self
	{
		$this->user = User::findById($userId);
		return $this;
	}

	public function setReplyTo(?int $commentId): self
	{
		$this->reply = empty($commentId) ? null : Comment::findById($commentId);
		return $this;
	}

	public function setContent(string $content): self
	{
		$this->content = $content;
		return $this;
	}

	public function setReplies(array $replies): self
	{
		$this->replies = $replies;
		return $this;
	}
}
