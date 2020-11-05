<?php

namespace AssignmentFourTests\ModelTests;

use AssignmentFour\Exceptions\CategoryException;
use AssignmentFour\Models\Category;
use AssignmentFourTests\AssignmentFourTest;

final class CategoryTest extends AssignmentFourTest
{
	public function testCategoryWasCreatedSuccessfully(): void
	{
		$this->assertInstanceOf(Category::class, $this->generateCategory());
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testExceptionWasThrownWhenCreatingCategory(array $parameters, string $exception, string $message): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		Category::create($parameters['userId'], $parameters['title'], $parameters['description']);
	}

	public function createCategoryProvider()
	{
		yield 'blank title' => [
			['userId' => 1, 'title' => '', 'description' => 'The best Pokemon community ever!'],
			CategoryException::class,
			'Cannot create Category: Missing title.'
		];

		yield 'invalid user ID 0' => [
			['userId' => 0, 'title' => 'Pokemon', 'description' => 'The best Pokemon community ever!'],
			CategoryException::class,
			'Cannot create Category: Invalid user ID.'
		];

		yield 'invalid user ID 999' => [
			['userId' => 999, 'title' => 'Pokemon', 'description' => 'The best Pokemon community ever!'],
			CategoryException::class,
			'Cannot create Category: User does not exist with ID 999.'
		];
	}

	public function testCategoryWasNotCreatedWithDuplicateTitle(): void
	{
		$this->expectException(CategoryException::class);
		$this->expectExceptionMessage("Cannot create Category: Title already exists.");

		$newCategory = $this->generateCategory();

		Category::create(
			$newCategory->getCreatedBy()->getId(),
			$newCategory->getTitle(),
			$newCategory->getDescription()
		);
	}

	public function testCategoryWasFoundById(): void
	{
		$newCategory = $this->generateCategory();
		$retrievedCategory = Category::findById($newCategory->getId());

		$this->assertEquals($newCategory->getId(), $retrievedCategory->getId());
		$this->assertEquals($newCategory->getTitle(), $retrievedCategory->getTitle());
		$this->assertEquals($newCategory->getDescription(), $retrievedCategory->getDescription());
	}

	public function testCategoryWasNotFoundByWrongId(): void
	{
		$newCategory = $this->generateCategory();
		$retrievedCategory = Category::findById($newCategory->getId() + 1);

		$this->assertNull($retrievedCategory);
	}

	public function testCategoryWasFoundByTitle(): void
	{
		$newCategory = $this->generateCategory();
		$retrievedCategory = Category::findByTitle($newCategory->getTitle());

		$this->assertEquals(
			$retrievedCategory->getTitle(),
			$newCategory->getTitle()
		);
	}

	public function testCategoryWasNotFoundByWrongTitle(): void
	{
		$newCategory = $this->generateCategory();
		$retrievedCategory = Category::findByTitle($newCategory->getTitle() . '.wrong');

		$this->assertNull($retrievedCategory);
	}

	public function testCategoryWasUpdatedSuccessfully(): void
	{
		$user = $this->generateUser();
		$oldCategory = Category::create(
			$user->getId(),
			self::$faker->word,
			self::$faker->sentence
		);

		$newCategoryTitle = self::$faker->word;

		$oldCategory->setTitle($newCategoryTitle);
		$this->assertNull($oldCategory->getEditedAt());
		$this->assertTrue($oldCategory->save());

		$retrievedCategory = Category::findById($oldCategory->getId());
		$this->assertEquals($newCategoryTitle, $retrievedCategory->getTitle());
		$this->assertNotNull($retrievedCategory->getEditedAt());
	}

	/**
	 * @dataProvider updateCategoryProvider
	 */
	public function testExceptionWasThrownWhenUpdatingCategory(string $functionName, $updatedValue, string $exception, string $message): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$category = self::generateCategory(null, 'Pokemon', null);

		call_user_func([$category, $functionName], $updatedValue);
		$category->save();
	}

	public function updateCategoryProvider()
	{
		yield 'blank title' => [
			'setTitle',
			'',
			CategoryException::class,
			'Cannot update Category: Missing title.'
		];
	}

	public function testCategoryWasDeletedSuccessfully(): void
	{
		$user = $this->generateUser();
		$category = Category::create(
			$user->getId(),
			self::$faker->word,
			self::$faker->sentence
		);

		$this->assertNull($category->getDeletedAt());
		$this->assertTrue($category->remove());

		$retrievedCategory = Category::findById($category->getId());
		$this->assertNotNull($retrievedCategory->getDeletedAt());
	}
}
