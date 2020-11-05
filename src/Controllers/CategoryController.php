<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Exceptions\CategoryException;
use AssignmentFour\Models\Category;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;

class CategoryController extends Controller
{
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;

		switch ($this->request->getRequestMethod()) {
			case 'GET':
				$this->action = empty($this->request->getParameters()['header'][0]) ? 'list' : 'show';
				break;
			case 'POST':
				$this->action = 'new';
				break;
			case 'PUT':
				$this->action = 'edit';
				break;
			case 'DELETE':
				$this->action = 'destroy';
				break;
			default:
				$this->action = 'error';
		}
	}

	protected function show(array $parameters): Response
	{
		$categoryId = $parameters['header'][0];
		$category = Category::findById($categoryId);

		if (empty($category)) {
			throw new CategoryException("Cannot find Category: Category does not exist with ID $categoryId.");
		}

		$this->response->setResponse([
			'message' => 'Category was retrieved successfully!',
			'payload' => $category
		]);

		return $this->response;
	}

	protected function new(array $parameters): Response
	{
		$category = Category::create(
			$parameters['body']['createdBy'],
			$parameters['body']['title'],
			$parameters['body']['description']
		);

		$this->response->setResponse([
			'message' => 'Category was created successfully!',
			'payload' => $category
		]);

		return $this->response;
	}

	protected function edit(array $parameters): Response
	{
		$categoryId = $parameters['header'][0];
		$category = Category::findById($categoryId);

		if (empty($category)) {
			throw new CategoryException("Cannot edit Category: Category does not exist with ID $categoryId.");
		}

		$error = false;

		if (isset($parameters['body']['title'])) {
			is_string($parameters['body']['title']) ? $category->setTitle($parameters['body']['title']) : $error = true;
		}

		if (isset($parameters['body']['description'])) {
			is_string($parameters['body']['description']) ? $category->setDescription($parameters['body']['description']) : $error = true;
		}

		if ($error) {
			throw new CategoryException("Could not update Category.");
		}

		$category->save();

		$this->response->setResponse([
			'message' => 'Category was updated successfully!',
			'payload' => $category
		]);

		return $this->response;
	}

	protected function destroy(array $parameters): Response
	{
		$categoryId = $parameters['header'][0];
		$category = Category::findById($categoryId);

		if (empty($category)) {
			throw new CategoryException("Cannot delete Category: Category does not exist with ID $categoryId.");
		}

		$category->remove();

		$this->response->setResponse([
			'message' => 'Category was deleted successfully!',
			'payload' => $category
		]);

		return $this->response;
	}
}
