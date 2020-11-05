<?php

namespace AssignmentFour\Router;

class Request
{
	private string $controller;
	private array $parameters;
	private string $requestMethod;

	public function __construct(string $requestMethod, string $queryString, array $bodyParameters)
	{
		$this->requestMethod = $requestMethod;
		$queryStringPieces = explode('/', $queryString, 2);
		$this->controller = $queryStringPieces[0] ?? '';
		$headerParameters = $queryStringPieces[1] ?? '';

		$this->setParameters($headerParameters, $bodyParameters);
	}

	private function setParameters(string $headerParameters, array $bodyParameters): void
	{
		$this->parameters['body'] = $bodyParameters ?? [];

		if (empty($bodyParameters)) {
			$this->parameters['body'] = [];
		} else {
			$this->parameters['body'] = array_map(function ($parameter) {
				return is_numeric($parameter) ? intval($parameter) : $parameter;
			}, $bodyParameters);
		}

		$this->parameters['header'] = [];

		if (!empty($headerParameters)) {
			$this->parameters['header'] = explode('/', $headerParameters);
		}
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function getController(): string
	{
		return $this->controller;
	}

	public function getRequestMethod(): string
	{
		return $this->requestMethod;
	}
}
