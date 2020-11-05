<?php

namespace AssignmentFour\Router;

abstract class Response
{
	protected array $headers;

	// protected const BASE_URI = 'http://localhost/Assignments/4/assignment-4-username/public/';
	protected const BASE_URI = 'http://apache/Assignments/4/assignment-4-username/public/';

	protected function redirect(string $location): void
	{
		$this->addHeader("Location: " . self::BASE_URI . $location);
	}

	protected function addHeader(string $header): void
	{
		$this->headers[] = $header;
	}

	public abstract function setResponse(array $data): void;
}
