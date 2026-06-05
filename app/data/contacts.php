<?php
declare(strict_types=1);

/**
 * Datos de contacto centralizados.
 * Usados por:  app/api/assistant.php  y  app/views/pages/directorio.php
 * Modifica aquí y se actualiza en ambos sitios automáticamente.
 */

function get_contact_data(): array
{
    return [

        /* ── Teléfonos de guardia ──────────────────────────────────────── */
        'guardia' => [
            'heading' => 'Teléfonos de guardia',
            'description' => 'Disponibles fuera del horario habitual de oficina y fines de semana.',
            'groups' => [
                [
                    'label' => 'Recepción OEST',
                    'items' => [
                        ['type' => 'tel',   'label' => 'Recepción OEST',              'value' => '+34984045653'],
                    ],
                ],
                [
                    'label' => 'Atención al cliente',
                    'items' => [
                        ['type' => 'tel',    'label' => 'Atención al cliente (FRONT)',  'value' => '+34656167004'],
                        ['type' => 'tel',    'label' => 'Atención al cliente (BO)',     'value' => '+34656167001'],
                        ['type' => 'email',  'label' => 'Atención cliente (sáb. y dom.)', 'value' => 'atencionresidencial.oest@masorange.es'],
                    ],
                ],
                [
                    'label' => 'RSS-Messaging',
                    'items' => [
                        ['type' => 'tel',    'label' => 'RSS-Messaging',               'value' => '+34656167005'],
                        ['type' => 'tel',    'label' => 'RSS-Messaging',               'value' => '+34656167002'],
                        ['type' => 'email',  'label' => 'Messaging (sáb. y dom.)',     'value' => 'messaging.oest@masorange.es'],
                    ],
                ],
                [
                    'label' => 'Retención',
                    'items' => [
                        ['type' => 'tel',   'label' => 'Retención',                   'value' => '+34665957600'],
                    ],
                ],
                [
                    'label' => 'SS Comerciales',
                    'items' => [
                        ['type' => 'tel',   'label' => 'SS Comerciales',              'value' => '+34656167000'],
                        ['type' => 'tel',   'label' => 'SS Comerciales',              'value' => '+34656167003'],
                    ],
                ],[
                    'label' => 'Incidencias fines de semana',
                    'items' => [
                        ['type' => 'tel',   'label' => 'Incidencias fines de semana',              'value' => '+34900900682'],
                    ],
                ],
            ],
        ],

        /* ── Administración y RRHH ─────────────────────────────────────── */
        'admin' => [
            'heading' => 'Administración y RRHH',
            'description' => 'Planificación, recursos humanos, ausencias, excedencias, nóminas y prevención.',
            'groups' => [
                [
                    'label' => 'Planificación',
                    'items' => [
                        ['type' => 'email', 'label' => 'Planificación',               'value' => 'solicitudesplani.oest@masorange.es'],
                    ],
                ],
                [
                    'label' => 'Recursos Humanos',
                    'items' => [
                        ['type' => 'email', 'label' => 'Recursos Humanos',            'value' => 'recursoshumanos.oest@es.orange.com'],
                        ['type' => 'tel',   'label' => 'WhatsApp Recursos Humanos',   'value' => '+34653495501'],
                    ],
                ],
                [
                    'label' => 'Ausencias e incapacidades',
                    'items' => [
                        ['type' => 'email', 'label' => 'Justificantes e IT',          'value' => 'gestiondeausencias.oest@es.orange.com'],
                        ['type' => 'email', 'label' => 'Excedencias',                 'value' => 'solicitudes.excedencias@masorange.es'],
                    ],
                ],
                [
                    'label' => 'Nóminas y prevención',
                    'items' => [
                        ['type' => 'email', 'label' => 'Nóminas y jornada',           'value' => 'admonhr.oest@masorange.es'],
                        ['type' => 'email', 'label' => 'Prevención',                  'value' => 'lauramanzano.oest@masorange.es'],
                    ],
                ],
                [
                    'label' => 'Mutua y soporte médico',
                    'items' => [
                        ['type' => 'tel',   'label' => 'Amigo 980',                   'value' => '+34912520678'],
                        ['type' => 'tel',   'label' => 'MC Mutual Gijón',             'value' => '+34985174000'],
                        ['type' => 'tel',   'label' => 'MC Mutual Oviedo',             'value' => '+34985277707'],
                    ],
                ],
            ],
        ],

        /* ── Enlaces de interés ────────────────────────────────────────── */
        'enlaces' => [
            'heading' => 'Enlaces de interés',
            'description' => 'Portales corporativos y herramientas de uso habitual.',
            'items' => [
                ['label' => 'Nosotros',          'url' => 'https://nosotros.masorange.es'],
                ['label' => 'Aspect (WFM)',          'url' => 'https://wfm.orange.es'],
                ['label' => 'How Vacaciones',        'url' => 'https://howtb.orange.es/howtb_ext/index.php?profile=oest'],
                ['label' => 'How 360 (enlace interno)', 'url' => 'https://how.si.orange.es/how360'],
            ],
        ],

    ];
}

/**
 * Devuelve la sección 'guardia' como array de links planos para el asistente.
 */
function get_guardia_links(): array
{
    $links = [];
    foreach (get_contact_data()['guardia']['groups'] as $group) {
        foreach ($group['items'] as $item) {
            $prefix = $item['type'] === 'tel' ? '📞 ' : '📧 ';
            $href   = $item['type'] === 'tel'
                ? 'tel:' . $item['value']
                : 'mailto:' . $item['value'];
            $label  = $item['type'] === 'tel'
                ? $prefix . $item['label'] . ': ' . $item['value']
                : $prefix . $item['label'];
            $links[] = ['label' => $label, 'url' => $href];
        }
    }
    return $links;
}

/**
 * Devuelve la sección 'admin' como array de links planos para el asistente.
 */
function get_admin_links(): array
{
    $links = [];
    foreach (get_contact_data()['admin']['groups'] as $group) {
        foreach ($group['items'] as $item) {
            $prefix = $item['type'] === 'tel' ? '📞 ' : '📧 ';
            $href   = $item['type'] === 'tel'
                ? 'tel:' . $item['value']
                : 'mailto:' . $item['value'];
            $label  = $item['type'] === 'tel'
                ? $prefix . $item['label'] . ': ' . $item['value']
                : $prefix . $item['label'];
            $links[] = ['label' => $label, 'url' => $href];
        }
    }
    return $links;
}

/**
 * Devuelve los enlaces de interés como array de links planos para el asistente.
 */
function get_enlaces_links(): array
{
    $links = [];
    foreach (get_contact_data()['enlaces']['items'] as $item) {
        $links[] = ['label' => '🔗 ' . $item['label'], 'url' => $item['url']];
    }
    return $links;
}
