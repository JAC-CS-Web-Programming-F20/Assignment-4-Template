<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Exceptions\PostException;
use AssignmentFour\Models\Post;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;

class PostController extends Controller
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
		$postId = $parameters['header'][0];
		$post = Post::findById($postId);

		if (empty($post)) {
			throw new PostException("Cannot find Post: Post does not exist with ID $postId.");
		}

		$this->response->setResponse([
			'message' => 'Post was retrieved successfully!',
			'payload' => $post
		]);

		return $this->response;
	}

	protected function new(array $parameters): Response
	{
		$this->validate($parameters);

		$post = Post::create(
			$parameters['body']['userId'],
			$parameters['body']['categoryId'],
			$parameters['body']['title'],
			$parameters['body']['type'],
			$parameters['body']['content']
		);

		$this->response->setResponse([
			'message' => 'Post was created successfully!',
			'payload' => $post
		]);

		return $this->response;
	}

	protected function edit(array $parameters): Response
	{
		$postId = $parameters['header'][0];
		$post = Post::findById($postId);

		if (empty($post)) {
			throw new PostException("Cannot update Post: Post does not exist with ID $postId.");
		}

		if (isset($parameters['body']['content'])) {
			if (empty($parameters['body']['content'])) {
				throw new PostException("Cannot update Post: Missing content.");
			} else {
				$post->setContent($parameters['body']['content']);
			}
		} else {
			throw new PostException('Cannot update Post: Missing content.');
		}

		$post->save();

		$this->response->setResponse([
			'message' => 'Post was updated successfully!',
			'payload' => $post
		]);

		return $this->response;
	}

	protected function destroy(array $parameters): Response
	{
		$postId = $parameters['header'][0];
		$post = Post::findById($postId);

		if (empty($post)) {
			throw new PostException("Cannot delete Post: Post does not exist with ID $postId.");
		}

		$post->remove();

		$this->response->setResponse([
			'message' => 'Post was deleted successfully!',
			'payload' => $post
		]);

		return $this->response;
	}

	private function validate(array $parameters)
	{
		if (isset($parameters['body']['userId'])) {
			if (!is_numeric($parameters['body']['userId'])) {
				throw new PostException('Cannot create Post: User ID must be an integer.');
			}
		} else {
			throw new PostException('Cannot create Post: Missing User ID.');
		}

		if (isset($parameters['body']['categoryId'])) {
			if (!is_numeric($parameters['body']['categoryId'])) {
				throw new PostException('Cannot create Post: Category ID must be an integer.');
			}
		} else {
			throw new PostException('Cannot create Post: Missing Category ID.');
		}

		if (!isset($parameters['body']['title'])) {
			throw new PostException('Cannot create Post: Missing title.');
		}

		if (isset($parameters['body']['type'])) {
			if ($parameters['body']['type'] !== 'Text' && $parameters['body']['type'] !== 'URL') {
				throw new PostException("Cannot create Post: Type must be 'Text' or 'URL'.");
			}
		} else {
			throw new PostException('Cannot create Post: Missing type.');
		}

		if (!isset($parameters['body']['content'])) {
			throw new PostException('Cannot create Post: Missing content.');
		}
	}
}
