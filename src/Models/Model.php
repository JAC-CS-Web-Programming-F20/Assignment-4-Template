<?php

namespace AssignmentFour\Models;

use AssignmentFour\Database\Connection;
use JsonSerializable;
use mysqli;

abstract class Model implements JsonSerializable
{
	protected int $id;
	protected static mysqli $connection;
	protected string $createdAt;
	protected ?string $editedAt;
	protected ?string $deletedAt;

	/**
	 * @var array $originalValues
	 * Keeps the state of the model when it was originally built so that we may use it as a reference to only update modified properties.
	 */
	protected array $originalValues;

	protected static function connect()
	{
		$database = new Connection();
		self::$connection = $database->connect();
	}

	protected static function _findBy(string $class, string $field, $value): array
	{
		$sql = self::buildSelectStatement($class, $field);
		$modelFields = self::getModelFields($class);
		$bindFields = array_fill(0, sizeOf($modelFields), null);

		self::connect();

		$statement = self::$connection->prepare($sql);
		$statement->bind_param(self::getTypesAsChars([$value]), $value);
		$statement->execute();
		$statement->bind_result(...$bindFields);

		if ($statement->fetch() === null) {
			$statement->close();
			return [];
		}

		for ($i = 0; $i < sizeOf($modelFields); $i++) {
			$result[$modelFields[$i]] = $bindFields[$i];
		}

		$statement->close();

		return $result;
	}

	protected static function _create(string $class, array $fields): int
	{
		$sql = self::buildInsertStatement($class, $fields);

		self::connect();
		$statement = self::$connection->prepare($sql);
		$statement->bind_param(self::getTypesAsChars($fields), ...array_values($fields));

		if (!$statement->execute()) {
			$statement->close();
			return 0;
		}

		$id = $statement->insert_id;

		$statement->close();

		return $id;
	}

	protected static function _save(Model $model): bool
	{
		$updateStatement = self::buildUpdateStatement($model);
		$sql = $updateStatement['statement'];
		$bindFormat = self::getTypesAsChars($updateStatement['fields']);
		$bindParameters = $updateStatement['fields'];

		self::connect();
		$statement = self::$connection->prepare($sql);
		$statement->bind_param($bindFormat, ...$bindParameters);
		$statement->execute();

		if ($statement->affected_rows === 0) {
			$statement->close();
			return false;
		}

		$statement->close();

		return true;
	}

	protected static function _remove(string $class, string $field, $value): bool
	{
		$sql = self::buildDeleteStatement($class, $field);

		self::connect();

		$statement = self::$connection->prepare($sql);
		$statement->bind_param(self::getTypesAsChars([$value]), $value);
		$statement->execute();

		if ($statement->affected_rows === 0) {
			$statement->close();
			return false;
		}

		$statement->close();

		return true;
	}

	private static function buildSelectStatement(string $class, string $field): string
	{
		$tableName = lcfirst(self::getClassName($class));
		$classFields = self::getClassVariables($class);
		$lastField = end($classFields);
		$statement = "SELECT ";

		foreach ($classFields as $classField) {
			if (
				$classField === 'user' ||
				$classField === 'category' ||
				$classField === 'post' ||
				$classField === 'reply'
			) {
				$classField .= 'Id';
			}

			$classFieldSnakeCase = self::camelToSnakeCase($classField);
			$statement .= "`$classFieldSnakeCase`";
			$statement .= $classField !== $lastField ? ', ' : ' ';
		}

		$statement .= "FROM `$tableName` WHERE `$field` = ?;";

		return $statement;
	}

	private static function buildInsertStatement(string $class, array $fields): string
	{
		$tableName = lcfirst(self::getClassName($class));
		$lastField = end($fields);
		$statement = "INSERT INTO `$tableName` (";

		foreach ($fields as $fieldKey => $fieldValue) {
			$classFieldSnakeCase = self::camelToSnakeCase($fieldKey);
			$statement .= "`$classFieldSnakeCase`";
			$statement .= $fieldValue !== $lastField ? ', ' : ') ';
		}

		$statement .= "VALUES (";

		foreach ($fields as $field) {
			$statement .= "?";
			$statement .= $field !== $lastField ? ', ' : ');';
		}

		return $statement;
	}

	private static function buildUpdateStatement(Model $model): array
	{
		$className = self::getClassName(get_class($model));
		$tableName = lcfirst($className);
		$classFields = self::getModelFields(get_class($model));
		$statementFields = [];
		$statement = "UPDATE `$tableName` SET ";

		// Only appends the fields to the UPDATE statement if their values have been modified in the model.
		foreach ($classFields as $classField) {
			$classFieldSnakeCase = self::camelToSnakeCase($classField);

			if (isset($model->$classField)) {
				$modelClassField = is_object($model->$classField) ? $model->$classField->getId() : $model->$classField;
			} else {
				continue;
			}

			$modelOriginalValue = $model->originalValues[$classFieldSnakeCase];

			if ($modelClassField !== $modelOriginalValue) {
				$statement .= "`$classFieldSnakeCase` = ?, ";
				$statementFields[] = $model->$classField;
			}
		}

		if (!empty($model->password)) {
			$model->password = password_hash($model->password, PASSWORD_DEFAULT);
			$statement .= "`password` = ?, ";
			$statementFields[] = $model->password;
		}

		$statement .= "`edited_at` = NOW() WHERE id = ?";
		$statementFields[] = $model->id;

		return ['statement' => $statement, 'fields' => $statementFields];
	}

	private static function buildDeleteStatement(string $class, string $field): string
	{
		$tableName = lcfirst(self::getClassName($class));
		$statement = "UPDATE `$tableName` SET `deleted_at` = NOW() WHERE `$field` = ?";

		return $statement;
	}

	private static function getClassVariables(string $className): array
	{
		foreach (array_keys(get_class_vars($className)) as $variable) {
			if (
				$variable !== 'connection' &&
				$variable !== 'originalValues' &&
				$variable !== 'password'
			) {
				$variables[] = $variable;
			}
		}

		return $variables;
	}

	private static function getClassName(string $className): string
	{
		$className = explode('\\', $className);
		return end($className);
	}

	private static function getModelFields(string $class): array
	{
		$modelFieldNames = self::getClassVariables($class);

		foreach ($modelFieldNames as $modelFieldName) {
			if (
				$modelFieldName === 'user' ||
				$modelFieldName === 'category' ||
				$modelFieldName === 'post' ||
				$modelFieldName === 'reply'
			) {
				$modelFieldName .= 'Id';
			}

			$modelFields[] = self::camelToSnakeCase($modelFieldName);
		}

		return $modelFields;
	}

	private static function getTypesAsChars($variables): string
	{
		$result = '';

		foreach ($variables as $variable) {
			switch (gettype($variable)) {
				case 'integer':
					$result .= 'i';
					break;
				case 'float':
					$result .= 'd';
					break;
				default:
					$result .= 's';
			}
		}

		return $result;
	}

	private static function camelToSnakeCase(string $camelCaseString): string
	{
		$pattern = '/((?=$|[A-Z][a-z])|[A-Za-z][a-z]+)/';
		preg_match_all($pattern, $camelCaseString, $matches);
		$matches = array_filter($matches[0]);

		if (sizeOf($matches) === 1) {
			return $camelCaseString;
		}

		$lastMatch = end($matches);
		$snakeCaseString = '';

		foreach ($matches as $match) {
			if (!empty($match)) {
				$snakeCaseString .= lcfirst($match);
				$snakeCaseString .= $match === $lastMatch ? '' : '_';
			}
		}

		return $snakeCaseString;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCreatedAt(): string
	{
		return $this->createdAt;
	}

	public function getEditedAt(): ?string
	{
		return $this->editedAt;
	}

	public function getDeletedAt(): ?string
	{
		return $this->deletedAt;
	}

	protected function setId(int $id): self
	{
		$this->id = $id;
		return $this;
	}

	protected function setCreatedAt(string $createdAt): self
	{
		$this->createdAt = $createdAt;
		return $this;
	}
	protected function setEditedAt(?string $editedAt): self
	{
		$this->editedAt = $editedAt;
		return $this;
	}
	protected function setDeletedAt(?string $deletedAt): self
	{
		$this->deletedAt = $deletedAt;
		return $this;
	}

	protected function isDeleted(): bool
	{
		return !empty($this->getDeletedAt());
	}
}
