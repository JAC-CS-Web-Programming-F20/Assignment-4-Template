<?php

require_once('../vendor/autoload.php');

use AssignmentFour\Router\{
	HtmlResponse,
	JsonResponse,
	Request,
	Router
};

$requestMethod = $_POST['method'] ?? $_SERVER['REQUEST_METHOD'];
$queryString = $_SERVER['QUERY_STRING'];
$responseType = apache_request_headers()['Accept'];
$parameters = [];

switch ($requestMethod) {
	case 'POST':
		$parameters = $_POST;
		break;
	case 'PUT':
		parse_str(file_get_contents("php://input"), $parameters);
}

$request = new Request($requestMethod, $queryString, $parameters);

switch ($responseType) {
	case 'application/json':
		$response = new JsonResponse();
		break;
	default:
		$response = new HtmlResponse();
		break;
}

$router = new Router($request, $response);
$response = $router->dispatch();

print $response;
