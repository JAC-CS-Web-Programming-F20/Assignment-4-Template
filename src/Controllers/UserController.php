<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Exceptions\UserException;
use AssignmentFour\Models\User;
use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;

class UserController extends Controller
{
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;

		$subAction = $this->request->getParameters()['header'][1] ?? 'show';

		switch ($this->request->getRequestMethod()) {
			case 'GET':
				switch ($subAction) {
					case 'comment':
						$this->action = 'comment';
						break;
					case 'post':
						$this->action = 'post';
						break;
					default:
						$this->action = 'show';
				}
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
		$userId = $parameters['header'][0];
		$user = User::findById($userId);

		if (empty($user)) {
			throw new UserException("Cannot find User: User does not exist with ID $userId.");
		}

		$this->response->setResponse([
			'message' => 'User was retrieved successfully!',
			'payload' => $user
		]);

		return $this->response;
	}

	protected function new(array $parameters): Response
	{
		$user = User::create(
			$parameters['body']['username'],
			$parameters['body']['email'],
			$parameters['body']['password']
		);

		$this->response->setResponse([
			'message' => 'User was created successfully!',
			'payload' => $user
		]);

		return $this->response;
	}

	protected function edit(array $parameters): Response
	{
		$userId = $parameters['header'][0];
		$user = User::findById($userId);

		if (empty($user)) {
			throw new UserException("Cannot edit User: User does not exist with ID $userId.");
		}

		$error = false;
		$fields = ['username', 'email', 'password', 'postScore', 'commentScore', 'avatar'];

		foreach ($fields as $field) {
			if (isset($parameters['body'][$field])) {
				is_string($parameters['body'][$field]) ? call_user_func([$user, 'set' . ucfirst($field)], $parameters['body'][$field]) : $error = true;
			}
		}

		if ($error) {
			throw new UserException('User was not updated.');
		}

		$user->save();

		$this->response->setResponse([
			'message' => 'User was updated successfully!',
			'payload' => $user
		]);

		return $this->response;
	}

	protected function destroy(array $parameters): Response
	{
		$userId = $parameters['header'][0];
		$user = User::findById($userId);

		if (empty($user)) {
			throw new UserException("Cannot delete User: User does not exist with ID $userId.");
		}

		$user->remove();

		$this->response->setResponse([
			'message' => 'User was deleted successfully!',
			'payload' => $user
		]);

		return $this->response;
	}
}
