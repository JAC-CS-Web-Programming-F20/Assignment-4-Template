<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Exceptions\CommentException;
use AssignmentFour\Models\Comment;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;

class CommentController extends Controller
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
		$commentId = $parameters['header'][0];
		$comment = Comment::findById($commentId);

		if (empty($comment)) {
			throw new CommentException("Cannot find Comment: Comment does not exist with ID $commentId.");
		}

		$this->response->setResponse([
			'message' => 'Comment was retrieved successfully!',
			'payload' => $comment
		]);

		return $this->response;
	}

	protected function new(array $parameters): Response
	{
		$this->validate($parameters);

		$comment = Comment::create(
			$parameters['body']['postId'],
			$parameters['body']['userId'],
			$parameters['body']['content'],
			$parameters['body']['replyId'] ?? null
		);

		$this->response->setResponse([
			'message' => 'Comment was created successfully!',
			'payload' => $comment
		]);

		return $this->response;
	}

	protected function edit(array $parameters): Response
	{
		$commentId = $parameters['header'][0];
		$comment = Comment::findById($commentId);

		if (empty($comment)) {
			throw new CommentException("Cannot update Comment: Comment does not exist with ID $commentId.");
		}

		$error = false;

		if (isset($parameters['body']['postId'])) {
			is_numeric($parameters['body']['postId']) ? $comment->setPost($parameters['body']['postId']) : $error = true;
		}

		if (isset($parameters['body']['userId'])) {
			is_numeric($parameters['body']['userId']) ? $comment->setUser($parameters['body']['userId']) : $error = true;
		}

		if (isset($parameters['body']['content'])) {
			is_string($parameters['body']['content']) ? $comment->setContent($parameters['body']['content']) : $error = true;
		}

		if ($error) {
			throw new CommentException('Could not update Comment.');
		}

		$comment->save();

		$this->response->setResponse([
			'message' => 'Comment was updated successfully!',
			'payload' => $comment
		]);

		return $this->response;
	}

	protected function destroy(array $parameters): Response
	{
		$commentId = $parameters['header'][0];
		$comment = Comment::findById($commentId);

		if (empty($comment)) {
			throw new CommentException("Cannot delete Comment: Comment does not exist with ID $commentId.");
		}

		$comment->remove();

		$this->response->setResponse([
			'message' => 'Comment was deleted successfully!',
			'payload' => $comment
		]);

		return $this->response;
	}

	private function validate(array $parameters)
	{
		if (isset($parameters['body']['userId'])) {
			if (!is_numeric($parameters['body']['userId'])) {
				throw new CommentException('Cannot create Comment: User ID must be an integer.');
			}
		} else {
			throw new CommentException('Cannot create Comment: Missing user ID.');
		}

		if (isset($parameters['body']['postId'])) {
			if (!is_numeric($parameters['body']['postId'])) {
				throw new CommentException('Cannot create Comment: Post ID must be an integer.');
			}
		} else {
			throw new CommentException('Cannot create Comment: Missing post ID.');
		}

		if (isset($parameters['body']['content'])) {
			if (empty($parameters['body']['content'])) {
				throw new CommentException('Cannot create Comment: Missing content.');
			}
		}

		if (isset($parameters['body']['replyId'])) {
			if (!is_numeric($parameters['body']['replyId'])) {
				throw new CommentException('Cannot create Comment: User ID must be an integer.');
			}
		}
	}
}
