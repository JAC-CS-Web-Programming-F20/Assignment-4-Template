<?php

namespace AssignmentFour\Models;

use AssignmentFour\Exceptions\PostException;

class Post extends Model
{
	protected User $user;
	protected Category $category;
	protected string $title;
	protected string $type;
	protected string $content;

	private function __construct()
	{
		$this->setEditedAt(null);
		$this->setDeletedAt(null);
		$this->setCreatedAt('');
	}

	static public function create(int $userId, int $categoryId, string $title, string $type, string $content): Post
	{
		if (empty($title)) {
			throw new PostException("Cannot create Post: Missing title.");
		}

		if (empty($type)) {
			throw new PostException("Cannot create Post: Missing type.");
		}

		if (empty($content)) {
			throw new PostException("Cannot create Post: Missing content.");
		}

		if (empty(User::findById($userId))) {
			throw new PostException("Cannot create Post: User does not exist with ID $userId.");
		}

		if (empty(Category::findById($categoryId))) {
			throw new PostException("Cannot create Post: Category does not exist with ID $categoryId.");
		}

		$postData = [
			'user_id' => $userId,
			'category_id' => $categoryId,
			'title' => $title,
			'type' => $type,
			'content' => $content
		];

		$postData['id'] = parent::_create(__CLASS__, $postData);

		return self::build($postData);
	}

	static public function findById(int $id): ?Post
	{
		$post = parent::_findBy(__CLASS__, 'id', $id);

		if (empty($post)) {
			return null;
		}

		return self::build($post);
	}

	static public function findByCategory(int $categoryId): array
	{
		$sql = "SELECT `id`, `user_id`, `title`, `type`, `content`, `created_at`, `edited_at`, `deleted_at` FROM `post` WHERE `category_id` = ?;";

		self::connect();

		$statement = self::$connection->prepare($sql);
		$statement->bind_param('i', $id);
		$statement->execute();
		$statement->bind_result($id, $userId, $title, $type, $content, $createdAt, $editedAt, $deletedAt, $categoryId);
		$statement->store_result();

		while ($statement->fetch()) {
			$posts[] = (new self())
				->setUser($userId)
				->setTitle($title)
				->setType($type)
				->setContent($content)
				->setCreatedAt($createdAt)
				->setEditedAt($editedAt)
				->setDeletedAt($deletedAt)
				->setId($id);
		}

		$statement->close();

		return $posts;
	}

	static public function findByUser(int $userId): array
	{
		$sql = "SELECT `id`, `category_id`, `title`, `type`, `content`, `created_at`, `edited_at`, `deleted_at` FROM `post` WHERE `user_id` = ?;";

		self::connect();

		$statement = self::$connection->prepare($sql);
		$statement->bind_param('i', $userId);
		$statement->execute();
		$statement->bind_result($id, $categoryId, $title, $type, $content, $createdAt, $editedAt, $deletedAt);
		$statement->store_result();

		while ($statement->fetch()) {
			$posts[] = (new self())
				->setUser($userId)
				->setCategory($categoryId)
				->setTitle($title)
				->setType($type)
				->setContent($content)
				->setCreatedAt($createdAt)
				->setEditedAt($editedAt)
				->setDeletedAt($deletedAt)
				->setId($id);
		}

		$statement->close();

		return $posts;
	}

	private static function build(array $fields): ?Post
	{
		if (empty($fields)) {
			return null;
		}

		$post = new self();
		$post->originalValues = $fields;

		empty($fields['user_id']) ? $post->originalValues['user_id'] = 0 : $post->setUser($fields['user_id']);
		empty($fields['category_id']) ? $post->originalValues['category_id'] = 0 : $post->setCategory($fields['category_id']);
		empty($fields['title']) ? $post->originalValues['title'] = '' : $post->setTitle($fields['title']);
		empty($fields['type']) ? $post->originalValues['type'] = '' : $post->setType($fields['type']);
		empty($fields['content']) ? $post->originalValues['content'] = '' : $post->setContent($fields['content']);
		empty($fields['created_at']) ? $post->originalValues['created_at'] = '' : $post->setCreatedAt($fields['created_at']);
		empty($fields['edited_at']) ? $post->originalValues['edited_at'] = null : $post->setEditedAt($fields['edited_at']);
		empty($fields['deleted_at']) ? $post->originalValues['deleted_at'] = null : $post->setDeletedAt($fields['deleted_at']);
		empty($fields['id']) ? $post->originalValues['id'] = 0 : $post->setId($fields['id']);

		return $post;
	}

	public function save(): bool
	{
		if ($this->type === 'URL') {
			throw new PostException("Cannot update Post: Only text posts are updateable.");
		}

		if (empty($this->content)) {
			throw new PostException("Cannot update Post: Missing content.");
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
			'category' => $this->category ?? [],
			'title' => $this->title ?? '',
			'type' => $this->type ?? '',
			'content' => $this->content ?? '',
			'createdAt' => $this->createdAt ?? '',
			'editedAt' => $this->editedAt ?? '',
			'deletedAt' => $this->deletedAt ?? ''
		];
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function getCategory(): Category
	{
		return $this->category;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;
		return $this;
	}

	public function setUser(int $userId): self
	{
		$this->user = User::findById($userId);
		return $this;
	}

	public function setCategory(int $category): self
	{
		$this->category = Category::findById($category);
		return $this;
	}

	public function setType(string $type): self
	{
		$this->type = $type;
		return $this;
	}

	public function setContent(string $content): self
	{
		$this->content = $content;
		return $this;
	}
}
