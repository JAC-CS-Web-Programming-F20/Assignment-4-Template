<?php

namespace AssignmentFourTests\RouterTests;

use AssignmentFour\Models\Category;
use AssignmentFourTests\AssignmentFourTest;
use Facebook\WebDriver\Exception\NoSuchElementException;

final class CategoryBrowserTest extends AssignmentFourTest
{
	public function testCategoryWasCreatedSuccessfully(): void
	{
		$categoryData = $this->generateCategoryData();

		self::$driver->get(self::$baseUri);

		$h1 = $this->findElement("h1");
		$createdByInput = $this->findElement("form#new-category-form input[name=\"createdBy\"]");
		$titleInput = $this->findElement("form#new-category-form input[name=\"title\"]");
		$descriptionInput = $this->findElement("form#new-category-form input[name=\"description\"]");
		$submitButton = $this->findElement("form#new-category-form button");

		$this->assertStringContainsString("Welcome", $h1->getText());

		$createdByInput->sendKeys($categoryData["createdBy"]);
		$titleInput->sendKeys($categoryData["title"]);
		$descriptionInput->sendKeys($categoryData["description"]);
		$submitButton->click();

		$category = Category::findByTitle($categoryData['title']);
		$categoryId = $category->getId();
		$commentElement = $this->findElement("tr[category-id=\"$categoryId\"]");

		$this->assertStringContainsString($categoryData["title"], $commentElement->getText());
		$this->assertStringContainsString($category->getCreatedAt(), $commentElement->getText());
		$this->assertStringContainsString($category->getCreatedBy()->getUsername(), $commentElement->getText());
		$this->assertStringContainsString("No", $commentElement->getText());
	}

	public function testManyCategoriesWereCreatedSuccessfully(): void
	{
		for ($i = 0; $i < rand(2, 5); $i++) {
			$categoryData = $this->generateCategoryData();

			self::$driver->get(self::$baseUri);

			$h1 = $this->findElement("h1");
			$createdByInput = $this->findElement("form#new-category-form input[name=\"createdBy\"]");
			$titleInput = $this->findElement("form#new-category-form input[name=\"title\"]");
			$descriptionInput = $this->findElement("form#new-category-form input[name=\"description\"]");
			$submitButton = $this->findElement("form#new-category-form button");

			$this->assertStringContainsString("Welcome", $h1->getText());

			$createdByInput->sendKeys($categoryData["createdBy"]);
			$titleInput->sendKeys($categoryData["title"]);
			$descriptionInput->sendKeys($categoryData["description"]);
			$submitButton->click();

			$category = Category::findByTitle($categoryData['title']);
			$categoryId = $category->getId();
			$commentElement = $this->findElement("tr[category-id=\"$categoryId\"]");

			$this->assertStringContainsString($categoryData["title"], $commentElement->getText());
			$this->assertStringContainsString($category->getCreatedAt(), $commentElement->getText());
			$this->assertStringContainsString($category->getCreatedBy()->getUsername(), $commentElement->getText());
			$this->assertStringContainsString("No", $commentElement->getText());
		}
	}

	/**
	 * @dataProvider createCategoryProvider
	 */
	public function testCategoryWasNotCreated(array $categoryData, string $message, bool $generateCategory = false): void
	{
		if ($generateCategory) {
			self::generateCategory(null, "Pokemon", "The best Pokemon community!");
		}

		self::$driver->get(self::$baseUri);

		$createdByInput = $this->findElement("form#new-category-form input[name=\"createdBy\"]");
		$titleInput = $this->findElement("form#new-category-form input[name=\"title\"]");
		$descriptionInput = $this->findElement("form#new-category-form input[name=\"description\"]");
		$submitButton = $this->findElement("form#new-category-form button");

		$createdByInput->sendKeys($categoryData["createdBy"]);
		$titleInput->sendKeys($categoryData["title"]);
		$descriptionInput->sendKeys($categoryData["description"]);
		$submitButton->click();

		$h1 = $this->findElement("h1");
		$body = $this->findElement("body");

		$this->assertStringContainsString("Error", $h1->getText());
		$this->assertStringContainsString($message, $body->getText());
	}

	public function createCategoryProvider()
	{
		yield "blank user ID" => [
			[
				"createdBy" => 0,
				"title" => "Pokemon",
				"description" => "The best Pokemon community!"
			],
			"Cannot create Category: Invalid user ID."
		];

		yield "blank title" => [
			[
				"createdBy" => 1,
				"title" => "",
				"description" => "The best Pokemon community!"
			],
			"Cannot create Category: Missing title."
		];

		yield "duplicate title" => [
			[
				"createdBy" => 1,
				"title" => "Pokemon",
				"description" => "The best Pokemon community!"
			],
			"Cannot create Category: Title already exists.",
			true
		];

		yield "invalid user ID" => [
			[
				"createdBy" => 999,
				"title" => "Pokemon",
				"description" => "The best Pokemon community!"
			],
			"Cannot create Category: User does not exist with ID 999."
		];
	}

	public function testCategoryWasFoundById(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();

		$categoryTitle = $this->findElement("#category-title");
		$categoryDescription = $this->findElement("#category-description");

		$this->assertStringContainsString($category->getTitle(), $categoryTitle->getText());
		$this->assertStringContainsString($category->getDescription(), $categoryDescription->getText());
	}

	public function testCategoryWasNotFoundByWrongId(): void
	{
		$randomCategoryId = rand(1, 100);
		self::$driver->get(self::$baseUri . "/category/$randomCategoryId");

		$h1 = $this->findElement("h1");
		$body = $this->findElement("body");

		$this->assertStringContainsString("Error", $h1->getText());
		$this->assertStringContainsString("Cannot find Category: Category does not exist with ID $randomCategoryId.", $body->getText());
	}

	public function testCategoryWasUpdatedSuccessfully(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();
		$newCategoryData = $this->generateCategoryData();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"category/$categoryId/edit\"]")->click();

		$titleInput = $this->findElement("form#edit-category-form input[name=\"title\"]");
		$descriptionInput = $this->findElement("form#edit-category-form input[name=\"description\"]");
		$submitButton = $this->findElement("form#edit-category-form button");

		$this->assertStringContainsString($category->getTitle(), $titleInput->getAttribute("value"));
		$this->assertStringContainsString($category->getDescription(), $descriptionInput->getAttribute("value"));

		$titleInput->sendKeys($newCategoryData["title"]);
		$descriptionInput->sendKeys($newCategoryData["description"]);
		$submitButton->click();

		$categoryTitle = $this->findElement("#category-title");
		$categoryDescription = $this->findElement("#category-description");

		$this->assertStringContainsString($newCategoryData["title"], $categoryTitle->getText());
		$this->assertStringContainsString($newCategoryData["description"], $categoryDescription->getText());
	}

	public function testCategoryWasNotUpdatedWithBlankTitle(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("a[href*=\"category/$categoryId/edit\"]")->click();

		$titleInput = $this->findElement("form#edit-category-form input[name=\"title\"]");
		$submitButton = $this->findElement("form#edit-category-form button");

		$this->assertStringContainsString($category->getTitle(), $titleInput->getAttribute("value"));

		$titleInput->clear();
		$submitButton->click();

		$h1 = $this->findElement("h1");
		$body = $this->findElement("body");

		$this->assertStringContainsString("Error", $h1->getText());
		$this->assertStringContainsString("Cannot update Category: Missing title.", $body->getText());
	}

	public function testCategoryWasDeletedSuccessfully(): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("form#delete-category-form button")->click();

		self::$driver->get(self::$baseUri);
		$categoriesRow = $this->findElement("tr[category-id=\"$categoryId\"]");

		$this->assertStringContainsString($category->getTitle(), $categoriesRow->getText());
		$this->assertStringContainsString($category->getCreatedBy()->getUsername(), $categoriesRow->getText());
		$this->assertStringContainsString("Yes", $categoriesRow->getText());
	}

	/**
	 * @dataProvider deleteCategoryProvider
	 */
	public function testDeletedCategoryShouldHaveAnInterface(string $selector): void
	{
		$category = $this->generateCategory();
		$categoryId = $category->getId();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();
		$this->findElement("form#delete-category-form button")->click();

		self::$driver->get(self::$baseUri);
		$this->findElement("a[href*=\"category/$categoryId\"]")->click();

		$this->expectException(NoSuchElementException::class);
		$this->findElement($selector);
	}

	public function deleteCategoryProvider()
	{
		yield "no edit category form" => ["form#edit-category-form"];
		yield "no delete category form" => ["form#delete-category-form"];
		yield "no new post form" => ["form#new-post-form"];
	}
}
