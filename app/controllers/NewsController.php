<?php
declare(strict_types=1);

class NewsController
{
    public static function index(array $query): array
    {
        $selectedId = isset($query['id']) ? (int)$query['id'] : null;
        $selectedSlug = isset($query['slug']) ? trim((string)$query['slug']) : null;

        if (($selectedId !== null && $selectedId > 0) || ($selectedSlug !== null && $selectedSlug !== '')) {
            $selectedNews = fetch_news_detail_from_db($selectedId, $selectedSlug);

            if ($selectedNews !== null) {
                return [
                    'selectedNews' => $selectedNews,
                    'isDetail' => true,
                ];
            }
        }

        $searchQuery = trim((string)($query['q'] ?? ''));
        if ($searchQuery !== '') {
            $results = search_news_in_db($searchQuery);

            return [
                'news' => $results,
                'pagination' => null,
                'isDetail' => false,
                'isSearch' => true,
                'searchQuery' => $searchQuery,
                'searchTotal' => count($results),
            ];
        }

        $page = isset($query['p']) ? (int)$query['p'] : 1;
        $perPage = 6;
        $pagination = fetch_news_from_db($page, $perPage);

        return [
            'news' => $pagination['items'],
            'pagination' => $pagination,
            'isDetail' => false,
            'isSearch' => false,
            'searchQuery' => '',
            'searchTotal' => 0,
        ];
    }
}
