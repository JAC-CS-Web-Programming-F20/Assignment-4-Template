<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFourTests\AssignmentFourTest;

final class CategoryRouterTest extends AssignmentFourTest
{
	public function testCategoryWasCreatedSuccessfully(): void
	{
		$categoryData = $this->generateCategoryData();

		$response = $this->getResponse(
			'POST',
			'category',
			$categoryData
		);

		$this->assertArrayHasKey('message', $response);
		$this->assertArrayHasKey('payload', $response);
		$this->assertArrayHasKey('id', $response['payload']);
		$this->assertArrayHasKey('title', $response['payload']);
		$this->assertArrayHasKey('description', $response['payload']);
		$this->assertArrayHasKey('createdBy', $response['payload']);
		$this->assertEquals(1, $response['payload']['id']);
		$this->assertEquals($categoryData['title'], $response['payload']['title']);
		$this->assertEquals($categoryData['description'], $response['payload']['description']);
		$this->assertIsArray($response['payload']['createdBy']);
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testCategoryWasNotCreated(array $categoryData, string $message, bool $generateCategory = false): void
	{
		if ($generateCategory) {
			self::generateCategory(null, 'Pokemon', 'The best Pokemon community!');
		}

		$response = $this->getResponse(
			'POST',
			'category',
			$categoryData
		);

		$this->assertEmpty($response['payload']);
		$this->assertEquals($message, $response['message']);
	}

	public function createCategoryProvider()
	{
		yield 'blank user ID' => [
			[
				'createdBy' => 0,
				'title' => 'Pokemon',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: Invalid user ID.'
		];

		yield 'blank title' => [
			[
				'createdBy' => 1,
				'title' => '',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: Missing title.'
		];

		yield 'duplicate title' => [
			[
				'createdBy' => 1,
				'title' => 'Pokemon',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: Title already exists.',
			true
		];

		yield 'invalid user ID' => [
			[
				'createdBy' => 999,
				'title' => 'Pokemon',
				'description' => 'The best Pokemon community!'
			],
			'Cannot create Category: User does not exist with ID 999.'
		];
	}

	public function testCategoryWasFoundById(): void
	{
		$category = $this->generateCategory();

		$retrievedCategory = $this->getResponse(
			'GET',
			'category/' . $category->getId()
		)['payload'];

		$this->assertArrayHasKey('id', $retrievedCategory);
		$this->assertArrayHasKey('title', $retrievedCategory);
		$this->assertArrayHasKey('description', $retrievedCategory);
		$this->assertEquals($category->getId(), $retrievedCategory['id']);
		$this->assertEquals($category->getTitle(), $retrievedCategory['title']);
		$this->assertEquals($category->getDescription(), $retrievedCategory['description']);
	}

	public function testCategoryWasNotFoundByWrongId(): void
	{
		$retrievedCategory = $this->getResponse(
			'GET',
			'category/1',
		);

		$this->assertEquals('Cannot find Category: Category does not exist with ID 1.', $retrievedCategory['message']);
		$this->assertEmpty($retrievedCategory['payload']);
	}

	/**
	 * @dataProvider updatedCategoryProvider
	 */
	public function testCategoryWasUpdated(array $oldCategoryData, array $newCategoryData, array $editedFields): void
	{
		$this->generateUser();

		$oldCategory = $this->getResponse(
			'POST',
			'category',
			$oldCategoryData
		)['payload'];

		$editedCategory = $this->getResponse(
			'PUT',
			'category/' . $oldCategory['id'],
			$newCategoryData
		)['payload'];

		/**
		 * Check every Category field against all the fields that were supposed to be edited.
		 * If the Category field is a field that's supposed to be edited, check if they're not equal.
		 * If the Category field is not supposed to be edited, check if they're equal.
		 */
		foreach ($oldCategory as $oldCategoryKey => $oldCategoryValue) {
			foreach ($editedFields as $editedField) {
				if ($oldCategoryKey === $editedField) {
					$this->assertNotEquals($oldCategoryValue, $editedCategory[$editedField]);
					$this->assertEquals($editedCategory[$editedField], $newCategoryData[$editedField]);
				}
			}
		}
	}

	public function updatedCategoryProvider()
	{
		yield 'valid title' => [
			['title' => 'Pokemon', 'description' => 'The best Pokemon community!', 'createdBy' => 1],
			['title' => 'Pokeyman'],
			['title'],
		];

		yield 'valid description' => [
			['title' => 'Pokemon', 'description' => 'The best Pokemon community!', 'createdBy' => 1],
			['description' => 'The #1 Pokemon community!'],
			['description'],
		];

		yield 'valid title and description' => [
			['title' => 'Pokemon', 'description' => 'The best Pokemon community!', 'createdBy' => 1],
			['title' => 'Pokeyman', 'description' => 'The #1 Pokemon community!'],
			['title', 'description'],
		];
	}

	/**
	 * @dataProvider updateCategoryProvider
	 */
	public function testCategoryWasNotUpdated(int $categoryId, array $newCategoryData, string $message): void
	{
		self::generateCategory(null, 'Pokemon', 'The best Pokemon community!');

		$editedCategory = $this->getResponse(
			'PUT',
			'category/' . $categoryId,
			$newCategoryData
		);

		$this->assertEquals($message, $editedCategory['message']);
		$this->assertEmpty($editedCategory['payload']);
	}

	public function updateCategoryProvider()
	{
		yield 'blank title' => [
			1,
			['title' => ''],
			'Cannot update Category: Missing title.'
		];

		yield 'invalid ID' => [
			999,
			['title' => 'Pokemon'],
			'Cannot edit Category: Category does not exist with ID 999.'
		];
	}

	public function testCategoryWasDeletedSuccessfully(): void
	{
		$randomCategory = $this->generateCategoryData();

		$oldCategory = $this->getResponse(
			'POST',
			'category',
			$randomCategory
		)['payload'];

		$this->assertEmpty($oldCategory['deletedAt']);

		$deletedCategory = $this->getResponse(
			'DELETE',
			'category/' . $oldCategory['id']
		)['payload'];

		$this->assertEquals($oldCategory['id'], $deletedCategory['id']);
		$this->assertEquals($oldCategory['title'], $deletedCategory['title']);
		$this->assertEquals($oldCategory['description'], $deletedCategory['description']);

		$retrievedCategory = $this->getResponse(
			'GET',
			'category/' . $oldCategory['id'],
		)['payload'];

		$this->assertNotEmpty($retrievedCategory['deletedAt']);
	}

	public function testCategoryWasNotDeleted(): void
	{
		$deletedCategory = $this->getResponse(
			'DELETE',
			'category/999'
		);

		$this->assertEquals('Cannot delete Category: Category does not exist with ID 999.', $deletedCategory['message']);
		$this->assertEmpty($deletedCategory['payload']);
	}
}
