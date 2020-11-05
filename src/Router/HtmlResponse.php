<?php

namespace AssignmentFour\Router;

use AssignmentFour\Views\View;

class HtmlResponse extends Response
{
	private View $view;

	public function __construct()
	{
		$this->addHeader('Content-Type: text/html');
		$this->view = new View();
	}

	public function setResponse(array $data): void
	{
		$data['baseUri'] = self::BASE_URI;

		if (!empty($data['template'])) {
			$this->view->setTemplate($data['template']);
			$this->view->setData($data);
		}

		empty($data['redirect']) ?: $this->redirect($data['redirect']);
	}

	public function __toString(): string
	{
		foreach ($this->headers as $header) {
			header($header);
		}

		return $this->view->render();
	}
}
