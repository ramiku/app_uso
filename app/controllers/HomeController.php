<?php
declare(strict_types=1);

class HomeController
{
    public static function index(array $query): array
    {
        $pagination = fetch_news_from_db(1, 6);

        return [
            'news' => $pagination['items'],
        ];
    }

    public static function contact(array $query): array
    {
        return [
            'title' => 'Contáctanos',
            'body' => 'Puedes escribirnos para consultas, propuestas de colaboración o información institucional.',
        ];
    }

    public static function documentation(array $query): array
    {
        $catalog = fetch_documents_catalog_from_db();

        return [
            'title' => 'Documentación',
            'body' => 'Accede a recursos, comunicados y documentos públicos organizados por temática.',
            'documents' => $catalog['documents'] ?? [],
            'calendars' => $catalog['calendars'] ?? [],
        ];
    }

    public static function assistant(array $query): array
    {
        return [
            'title' => 'Asistente Virtual USO',
            'body' => 'Espacio preparado para integrar un asistente virtual con respuestas frecuentes y apoyo al usuario.',
        ];
    }

    public static function directory(array $query): array
    {
        require_once APP_PATH . '/data/contacts.php';
        $contactData = get_contact_data();
        return [
            'title'    => 'Directorio de contacto',
            'sections' => [
                $contactData['guardia'],
                $contactData['admin'],
            ],
            'enlaces'  => $contactData['enlaces']['items'],
        ];
    }

    public static function privacy(array $query): array
    {
        return [
            'title' => 'Política de privacidad',
            'lastUpdated' => '14 de marzo de 2026',
            'developerAlias' => 'ramiku',
            'contactEmail' => 'ramiku@gmail.com',
            'appName' => 'USO OEST',
        ];
    }

    public static function calendars(array $query): array
    {
        ensure_calendar_tables();

        $years = calendar_list_years();
        $selectedYearValue = isset($query['year']) ? (int)$query['year'] : 0;
        $selectedYear = null;

        if ($selectedYearValue > 0) {
            $selectedYear = calendar_get_year_by_value($selectedYearValue);
        }

        if ($selectedYear === null && $years !== []) {
            $selectedYear = $years[0];
            $selectedYearValue = (int)($selectedYear['year'] ?? 0);
        }

        $rotations = [];
        $selectedRotation = null;
        $generated = [];
        $generatedByMonth = [];

        if (is_array($selectedYear)) {
            $yearId = (int)($selectedYear['id'] ?? 0);
            $rotations = calendar_list_rotations($yearId);

            $requestedRotationId = isset($query['rotation']) ? (int)$query['rotation'] : 0;
            if ($requestedRotationId > 0) {
                $rotation = calendar_get_rotation($requestedRotationId);
                if (is_array($rotation) && (int)($rotation['calendar_year_id'] ?? 0) === $yearId) {
                    $selectedRotation = $rotation;
                }
            }

            if ($selectedRotation === null) {
                foreach ($rotations as $rotationItem) {
                    if ((int)($rotationItem['is_default'] ?? 0) === 1) {
                        $selectedRotation = $rotationItem;
                        break;
                    }
                }
            }

            if ($selectedRotation === null && $rotations !== []) {
                $selectedRotation = $rotations[0];
            }

            if ($selectedRotation !== null) {
                $generated = calendar_generate_calendar((int)$selectedYearValue, (int)($selectedRotation['id'] ?? 0));
                $generatedByMonth = calendar_group_generated_by_month($generated);
            }
        }

        return [
            'years' => $years,
            'selectedYear' => $selectedYear,
            'rotations' => $rotations,
            'selectedRotation' => $selectedRotation,
            'generated' => $generated,
            'generatedByMonth' => $generatedByMonth,
        ];
    }

    public static function notFound(array $query): array
    {
        return [
            'title' => 'Página no encontrada',
            'body' => 'La ruta solicitada no existe. Puedes volver al inicio para seguir navegando.',
        ];
    }
}
