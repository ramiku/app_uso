<?php
declare(strict_types=1);

function get_routes(): array
{
    return [
        'home' => [
            'controller' => 'HomeController',
            'action' => 'index',
            'view' => 'home',
        ],
        'noticias' => [
            'controller' => 'NewsController',
            'action' => 'index',
            'view' => 'noticias',
        ],
        'contactanos' => [
            'controller' => 'HomeController',
            'action' => 'contact',
            'view' => 'contactanos',
        ],
        'documentacion' => [
            'controller' => 'HomeController',
            'action' => 'documentation',
            'view' => 'documentacion',
        ],
        'calendarios' => [
            'controller' => 'HomeController',
            'action' => 'calendars',
            'view' => 'calendarios',
        ],
        'asistente' => [
            'controller' => 'HomeController',
            'action' => 'assistant',
            'view' => 'asistente',
        ],
        'directorio' => [
            'controller' => 'HomeController',
            'action' => 'directory',
            'view' => 'directorio',
        ],
        'privacidad' => [
            'controller' => 'HomeController',
            'action' => 'privacy',
            'view' => 'privacidad',
        ],
        '404' => [
            'controller' => 'HomeController',
            'action' => 'notFound',
            'view' => '404',
        ],
    ];
}

function get_route(string $page): ?array
{
    $routes = get_routes();
    return $routes[$page] ?? null;
}
