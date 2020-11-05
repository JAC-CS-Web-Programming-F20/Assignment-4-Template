<?php

namespace AssignmentFour\Views;

use League\Plates\Engine;
use League\Plates\Template\Template;

class View
{
	private Engine $engine;
	private Template $template;

	public function __construct()
	{
		$this->engine = new Engine(__DIR__);
	}

	public function setTemplate(string $templateName): void
	{
		$this->template = $this->engine->make($templateName);
	}

	public function setData(array $data): void
	{
		$this->template->data($data);
	}

	public function render(): string
	{
		return $this->template->render();
	}
}
