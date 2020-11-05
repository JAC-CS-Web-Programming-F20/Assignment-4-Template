<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Router\Response;
use AssignmentFour\Router\Request;

abstract class Controller
{
	protected Request $request;
	protected string $action;

	public function doAction(): Response
	{
		if ($this->action === 'error') {
			return new Response('404');
		}

		return call_user_func([$this, $this->action], $this->request->getParameters());
	}

	public function getAction(): string
	{
		return $this->action;
	}
}
