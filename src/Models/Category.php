<?php

namespace AssignmentFour\Models;

use AssignmentFour\Exceptions\CategoryException;

class Category extends Model
{
	protected User $createdBy;
	protected string $title;
	protected string $description;
	private array $posts;

	private function __construct()
	{
		$this->setEditedAt(null);
		$this->setDeletedAt(null);
		$this->setCreatedAt('');
	}

	static public function create(int $createdBy, string $title, string $description): Category
	{
		if (empty($createdBy)) {
			throw new CategoryException("Cannot create Category: Invalid user ID.");
		}

		if (empty($title)) {
			throw new CategoryException("Cannot create Category: Missing title.");
		}

		if (self::findByTitle($title)) {
			throw new CategoryException("Cannot create Category: Title already exists.");
		}

		if (empty(User::findById($createdBy))) {
			throw new CategoryException("Cannot create Category: User does not exist with ID $createdBy.");
		}

		$categoryData = [
			'created_by' => $createdBy,
			'title' => $title,
			'description' => $description
		];

		$categoryData['id'] = parent::_create(__CLASS__, $categoryData);

		return self::build($categoryData);
	}

	static public function findById(int $id): ?Category
	{
		$category = parent::_findBy(__CLASS__, 'id', $id);

		if (empty($category)) {
			return null;
		}

		return self::build($category);
	}

	static public function findByTitle(string $title): ?Category
	{
		$category = parent::_findBy(__CLASS__, 'title', $title);

		if (empty($category)) {
			return null;
		}

		return self::build($category);
	}

	public function findAllPosts(): array
	{
		$this->posts = Post::findByCategory($this->id);
		return $this->posts;
	}

	private static function build(array $fields): ?Category
	{
		if (empty($fields)) {
			return null;
		}

		$category = new self();
		$category->originalValues = $fields;

		empty($fields['title']) ? $category->originalValues['title'] = '' : $category->setTitle($fields['title']);
		empty($fields['description']) ? $category->originalValues['description'] = '' : $category->setDescription($fields['description']);
		empty($fields['created_by']) ? $category->originalValues['created_by'] = 0 : $category->setCreatedBy($fields['created_by']);
		empty($fields['created_at']) ? $category->originalValues['created_at'] = '' : $category->setCreatedAt($fields['created_at']);
		empty($fields['edited_at']) ? $category->originalValues['edited_at'] = null : $category->setEditedAt($fields['edited_at']);
		empty($fields['deleted_at']) ? $category->originalValues['deleted_at'] = null : $category->setDeletedAt($fields['deleted_at']);
		empty($fields['id']) ? $category->originalValues['id'] = 0 : $category->setId($fields['id']);

		return $category;
	}

	public function save(): bool
	{
		if (empty($this->title)) {
			throw new CategoryException("Cannot update Category: Missing title.");
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
			'createdBy' => $this->createdBy ?? [],
			'title' => $this->title,
			'description' => $this->description ?? '',
			'createdAt' => $this->createdAt ?? '',
			'editedAt' => $this->editedAt ?? '',
			'deletedAt' => $this->deletedAt ?? ''
		];
	}

	public function getCreatedBy(): User
	{
		return $this->createdBy;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setTitle(string $title): self
	{
		$this->title = $title;
		return $this;
	}

	public function setCreatedBy(int $userId): self
	{
		$this->createdBy = User::findById($userId);
		return $this;
	}

	public function setDescription(string $description): self
	{
		$this->description = $description;
		return $this;
	}
}
