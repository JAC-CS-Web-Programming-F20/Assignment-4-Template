<?php

namespace AssignmentFour\Models;

use AssignmentFour\Exceptions\UserException;

class User extends Model
{
	protected string $username;
	protected string $email;
	protected string $password;
	protected int $postScore;
	protected int $commentScore;
	protected ?string $avatar;

	private function __construct()
	{
		$this->setPassword('')
			->setPostScore(0)
			->setCommentScore(0)
			->setAvatar(null)
			->setCreatedAt('')
			->setEditedAt(null)
			->setDeletedAt(null);
	}

	static public function create(string $username, string $email, string $password): User
	{
		if (empty($username)) {
			throw new UserException("Cannot create User: Missing username.");
		}

		if (empty($email)) {
			throw new UserException("Cannot create User: Missing email.");
		}

		if (empty($password)) {
			throw new UserException("Cannot create User: Missing password.");
		}

		if (self::findByUsername($username)) {
			throw new UserException("Cannot create User: Username already exists.");
		}

		if (self::findByEmail($email)) {
			throw new UserException("Cannot create User: Email already exists.");
		}

		$userData = [
			'username' => $username,
			'email' => $email,
			'password' => password_hash($password, PASSWORD_DEFAULT)
		];

		$userData['id'] = parent::_create(__CLASS__, $userData);

		return self::build($userData);
	}

	static public function findById(int $id): ?User
	{
		$user = parent::_findBy(__CLASS__, 'id', $id);

		if (empty($user)) {
			return null;
		}

		return self::build($user);
	}

	static public function findByEmail(string $email): ?User
	{
		$user = parent::_findBy(__CLASS__, 'email', $email);

		if (empty($user)) {
			return null;
		}

		return self::build($user);
	}

	static public function findByUsername(string $username): ?User
	{
		$user = parent::_findBy(__CLASS__, 'username', $username);

		if (empty($user)) {
			return null;
		}

		return self::build($user);
	}

	private static function build(array $fields): ?User
	{
		if (empty($fields)) {
			return null;
		}

		$user = new self();
		$user->originalValues = $fields;

		empty($fields['username']) ? $user->originalValues['username'] = '' : $user->setUsername($fields['username']);
		empty($fields['email']) ? $user->originalValues['email'] = '' : $user->setEmail($fields['email']);
		empty($fields['post_score']) ? $user->originalValues['post_score'] = 0 : $user->setPostScore($fields['post_score']);
		empty($fields['comment_score']) ? $user->originalValues['comment_score'] = 0 : $user->setCommentScore($fields['comment_score']);
		empty($fields['avatar']) ? $user->originalValues['avatar'] = null : $user->setAvatar($fields['avatar']);
		empty($fields['created_at']) ? $user->originalValues['created_at'] = '' : $user->setCreatedAt($fields['created_at']);
		empty($fields['edited_at']) ? $user->originalValues['edited_at'] = null : $user->setEditedAt($fields['edited_at']);
		empty($fields['deleted_at']) ? $user->originalValues['deleted_at'] = null : $user->setDeletedAt($fields['deleted_at']);
		empty($fields['id']) ? $user->originalValues['id'] = 0 : $user->setId($fields['id']);

		return $user;
	}

	public function save(): bool
	{
		if (empty($this->username)) {
			throw new UserException("Cannot update User: Missing username.");
		}

		if (empty($this->email)) {
			throw new UserException("Cannot update User: Missing email.");
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
			'username' => $this->username,
			'email' => $this->email,
			'avatar' => $this->avatar ?? '',
			'createdAt' => $this->createdAt ?? '',
			'editedAt' => $this->editedAt ?? '',
			'deletedAt' => $this->deletedAt ?? ''
		];
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getUsername(): string
	{
		return $this->username;
	}

	public function getPostScore(): int
	{
		return $this->postScore;
	}

	public function getCommentScore(): int
	{
		return $this->commentScore;
	}

	public function getAvatar(): ?string
	{
		return $this->avatar;
	}

	public function setEmail(string $email): self
	{
		$this->email = $email;
		return $this;
	}

	public function setUsername(string $username): self
	{
		$this->username = $username;
		return $this;
	}

	public function setPostScore(int $postScore): self
	{
		$this->postScore = $postScore;
		return $this;
	}

	public function setCommentScore(int $commentScore): self
	{
		$this->commentScore = $commentScore;
		return $this;
	}

	public function setAvatar(?string $avatar): self
	{
		$this->avatar = $avatar;
		return $this;
	}

	public function setPassword(string $password): self
	{
		$this->password = $password;
		return $this;
	}
}
