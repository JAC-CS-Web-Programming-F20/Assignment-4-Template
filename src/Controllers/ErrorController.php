<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Router\Request;

class ErrorController extends Controller
{
	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->action = 'error';
	}
}
