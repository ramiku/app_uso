<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/routes.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/controllers/HomeController.php';
require_once __DIR__ . '/app/controllers/NewsController.php';

function parse_clean_request(array $query): array
{
    $path = trim((string)($query['path'] ?? ''), '/');
    $result = [
        'page' => 'home',
    ];

    if ($path === '') {
        return $result;
    }

    $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
    if ($segments === []) {
        return $result;
    }

    $first = urldecode($segments[0]);
    $result['page'] = $first;

    if ($first !== 'noticias') {
        return $result;
    }

    if (isset($segments[1])) {
        $second = urldecode($segments[1]);

        if ($second === 'pagina' && isset($segments[2]) && ctype_digit($segments[2])) {
            $result['p'] = (int)$segments[2];
            return $result;
        }

        if ($second === 'buscar' && isset($segments[2])) {
            $searchRaw = implode('/', array_slice($segments, 2));
            $result['q'] = urldecode($searchRaw);
            return $result;
        }

        if ($second === 'slug' && isset($segments[2])) {
            $slugRaw = implode('/', array_slice($segments, 2));
            $result['slug'] = urldecode($slugRaw);
            return $result;
        }

        if (ctype_digit($second)) {
            $result['id'] = (int)$second;
            return $result;
        }
    }

    return $result;
}

function build_canonical_public_url(array $request): string
{
    $page = trim((string)($request['page'] ?? 'home'));
    if ($page === '') {
        $page = 'home';
    }

    $params = [];
    foreach (['id', 'slug', 'q', 'p', 'year', 'rotation'] as $key) {
        if (isset($request[$key])) {
            $params[$key] = $request[$key];
        }
    }

    return url_for($page, $params);
}

$parsedRequest = parse_clean_request($_GET);

if (isset($_GET['year']) && ctype_digit((string)$_GET['year'])) {
    $parsedRequest['year'] = (int)$_GET['year'];
}
if (isset($_GET['rotation']) && ctype_digit((string)$_GET['rotation'])) {
    $parsedRequest['rotation'] = (int)$_GET['rotation'];
}

if (isset($_GET['page']) || isset($_GET['q']) || isset($_GET['p']) || isset($_GET['id']) || isset($_GET['slug'])) {
    $legacyRequest = [
        'page' => (string)($_GET['page'] ?? ($parsedRequest['page'] ?? 'home')),
    ];

    foreach (['id', 'slug', 'q', 'p', 'year', 'rotation'] as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $legacyRequest[$key] = $_GET[$key];
        }
    }

    $canonical = build_canonical_public_url($legacyRequest);
    header('Location: ' . $canonical, true, 302);
    exit;
}

if (!empty($_GET['path']) && (isset($_GET['q']) || isset($_GET['p']))) {
    $requestWithQuery = $parsedRequest;
    if (isset($_GET['q']) && trim((string)$_GET['q']) !== '') {
        $requestWithQuery['q'] = (string)$_GET['q'];
        unset($requestWithQuery['p'], $requestWithQuery['id'], $requestWithQuery['slug']);
    }
    if (isset($_GET['p']) && (int)$_GET['p'] > 1 && !isset($requestWithQuery['q'])) {
        $requestWithQuery['p'] = (int)$_GET['p'];
    }

    $canonical = build_canonical_public_url($requestWithQuery);
    header('Location: ' . $canonical, true, 302);
    exit;
}

$requestedPage = $parsedRequest['page'] ?? 'home';
$route = get_route($requestedPage);

if ($route === null) {
    $requestedPage = '404';
    $route = get_route('404');
    http_response_code(404);
}

$controllerClass = $route['controller'];
$action = $route['action'];

$controllerData = [];
if (class_exists($controllerClass) && method_exists($controllerClass, $action)) {
    $controllerData = $controllerClass::$action($parsedRequest);
}

$meta = get_page_meta($requestedPage);

$viewFile = APP_PATH . '/views/pages/' . $route['view'] . '.php';
if (!file_exists($viewFile)) {
    $viewFile = APP_PATH . '/views/pages/404.php';
    http_response_code(404);
}

$layoutData = [
    'page' => $requestedPage,
    'meta' => $meta,
    'data' => $controllerData,
    'viewFile' => $viewFile,
];

extract($layoutData, EXTR_SKIP);
require APP_PATH . '/views/layouts/base.php';
