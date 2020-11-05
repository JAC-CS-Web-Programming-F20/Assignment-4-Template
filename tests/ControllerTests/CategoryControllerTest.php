<?php

namespace AssignmentFourTests\ControllerTests;

use AssignmentFour\Controllers\CategoryController;
use AssignmentFour\Exceptions\CategoryException;
use AssignmentFour\Models\Category;
use AssignmentFour\Router\JsonResponse;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;
use AssignmentFourTests\AssignmentFourTest;

final class CategoryControllerTest extends AssignmentFourTest
{
	public function testCategoryControllerCalledShow(): void
	{
		$category = $this->generateCategory();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('GET');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$category->getId()]
		]);

		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('show', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was retrieved successfully!', $response->getMessage());
		$this->assertEquals($category->getId(), $response->getPayload()->getId());
		$this->assertEquals($category->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($category->getDescription(), $response->getPayload()->getDescription());
	}

	/**
	 * @dataProvider findCategoryProvider
	 */
	public function testExceptionWasThrownShowingCategory(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CategoryController($request, new JsonResponse()))->doAction();
	}

	public function findCategoryProvider()
	{
		yield 'invalid ID' => [
			CategoryException::class,
			'Cannot find Category: Category does not exist with ID 1.',
			'GET',
			[
				'body' => [],
				'header' => [1]
			],
		];
	}

	public function testCategoryControllerCalledNew(): void
	{
		$category = $this->generateCategoryData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('POST');
		$request->method('getParameters')->willReturn([
			'body' => [
				'createdBy' => $category['createdBy'],
				'title' => $category['title'],
				'description' => $category['description']
			],
			'header' => []
		]);

		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('new', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was created successfully!', $response->getMessage());
		$this->assertEquals($category['title'], $response->getPayload()->getTitle());
		$this->assertEquals($category['description'], $response->getPayload()->getDescription());
		$this->assertNotEmpty($response->getPayload()->getId());
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testExceptionWasThrownNewingCategory(string $exception, string $message, string $requestMethod, array $parameters, bool $generateCategory = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generateCategory) {
			$this->generateCategory($this->generateUser(), 'Pokemon');
		}

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CategoryController($request, new JsonResponse()))->doAction();
	}

	public function createCategoryProvider()
	{
		yield 'blank user ID' => [
			CategoryException::class,
			'Cannot create Category: Invalid user ID.',
			'POST',
			[
				'body' => [
					'createdBy' => 0,
					'title' => 'Pokemon',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
		];

		yield 'blank title' => [
			CategoryException::class,
			'Cannot create Category: Missing title.',
			'POST',
			[
				'body' => [
					'createdBy' => 1,
					'title' => '',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
		];

		yield 'duplicate title' => [
			CategoryException::class,
			'Cannot create Category: Title already exists.',
			'POST',
			[
				'body' => [
					'createdBy' => 1,
					'title' => 'Pokemon',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
			true
		];

		yield 'non-existant user' => [
			CategoryException::class,
			'Cannot create Category: User does not exist with ID 999.',
			'POST',
			[
				'body' => [
					'createdBy' => 999,
					'title' => 'Pokemon',
					'description' => 'The best Pokemon community!'
				],
				'header' => []
			],
		];
	}

	public function testCategoryControllerCalledEdit(): void
	{
		$category = $this->generateCategory();
		$newCategoryData = $this->generateCategoryData();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('PUT');
		$request->method('getParameters')->willReturn([
			'body' => [
				'title' => $newCategoryData['title'],
				'description' => $newCategoryData['description'],
			],
			'header' => [$category->getId()]
		]);

		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('edit', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was updated successfully!', $response->getMessage());
		$this->assertEquals($newCategoryData['title'], $response->getPayload()->getTitle());
		$this->assertEquals($newCategoryData['description'], $response->getPayload()->getDescription());
		$this->assertNotEquals($category->getTitle(), $response->getPayload()->getDescription());
		$this->assertNotEquals($category->getDescription(), $response->getPayload()->getTitle());
	}

	/**
	 * @dataProvider editCategoryProvider
	 */
	public function testExceptionWasThrownEditingCategory(string $exception, string $message, string $requestMethod, array $parameters, bool $generateCategory = false): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		if ($generateCategory) {
			$this->generateCategory($this->generateUser(), 'Pokemon');
		}

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CategoryController($request, new JsonResponse()))->doAction();
	}

	public function editCategoryProvider()
	{
		yield 'blank title' => [
			CategoryException::class,
			'Cannot update Category: Missing title.',
			'PUT',
			[
				'body' => [
					'title' => ''
				],
				'header' => [1]
			],
			true
		];

		yield 'invalid ID' => [
			CategoryException::class,
			'Cannot edit Category: Category does not exist with ID 999.',
			'PUT',
			[
				'body' => [
					'title' => 'Pokemon'
				],
				'header' => [999]
			]
		];
	}

	public function testCategoryControllerCalledDestroy(): void
	{
		$category = $this->generateCategory();

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn('DELETE');
		$request->method('getParameters')->willReturn([
			'body' => [],
			'header' => [$category->getId()]
		]);

		$controller = new CategoryController($request, new JsonResponse());

		$this->assertEquals('destroy', $controller->getAction());

		$response = $controller->doAction();

		$this->assertTrue($response instanceof Response);
		$this->assertEquals('Category was deleted successfully!', $response->getMessage());
		$this->assertEquals($category->getId(), $response->getPayload()->getId());
		$this->assertEquals($category->getTitle(), $response->getPayload()->getTitle());
		$this->assertEquals($category->getDescription(), $response->getPayload()->getDescription());
	}

	/**
	 * @dataProvider deleteCategoryProvider
	 */
	public function testExceptionWasThrownDestroyingCategory(string $exception, string $message, string $requestMethod, array $parameters): void
	{
		$this->expectException($exception);
		$this->expectExceptionMessage($message);

		$request = $this->createMock(Request::class);
		$request->method('getRequestMethod')->willReturn($requestMethod);
		$request->method('getParameters')->willReturn($parameters);

		(new CategoryController($request, new JsonResponse()))->doAction();
	}

	public function deleteCategoryProvider()
	{
		yield 'invalid ID' => [
			CategoryException::class,
			'Cannot delete Category: Category does not exist with ID 999.',
			'DELETE',
			[
				'body' => [],
				'header' => [999]
			],
		];
	}
}
