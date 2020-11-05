<?php

namespace AssignmentFour\Router;

use JsonSerializable;

class JsonResponse extends Response implements JsonSerializable
{
	private string $message;
	private $payload;

	public function __construct()
	{
		$this->addHeader('Content-Type: application/json');
	}

	public function setResponse(array $data): void
	{
		$this->message = $data['message'] ?? '';
		$this->payload = $data['payload'] ?? [];
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getPayload()
	{
		return $this->payload;
	}

	public function setMessage(string $message): void
	{
		$this->message = $message;
	}

	public function setPayload(string $payload): void
	{
		$this->payload = $payload;
	}

	public function jsonSerialize(): array
	{
		return [
			'message' => $this->message,
			'payload' => $this->payload
		];
	}

	public function __toString(): string
	{
		foreach ($this->headers as $header) {
			header($header);
		}

		return json_encode($this);
	}
}
