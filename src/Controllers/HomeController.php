<?php

namespace AssignmentFour\Controllers;

use AssignmentFour\Router\Request;
use AssignmentFour\Router\Response;

class HomeController extends Controller
{
	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->action = 'home';
	}

	protected function home(): Response
	{
		return new Response('Homepage!');
	}
}
