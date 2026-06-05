<?php
declare(strict_types=1);

/** @var array $data */
$data ??= [];

$years = is_array($data['years'] ?? null) ? $data['years'] : [];
$selectedYear = is_array($data['selectedYear'] ?? null) ? $data['selectedYear'] : null;
$rotations = is_array($data['rotations'] ?? null) ? $data['rotations'] : [];
$selectedRotation = is_array($data['selectedRotation'] ?? null) ? $data['selectedRotation'] : null;
$generated = is_array($data['generated'] ?? null) ? $data['generated'] : [];

$selectedYearValue = (int)($selectedYear['year'] ?? 0);
$selectedRotationId = (int)($selectedRotation['id'] ?? 0);
$selectedRotationName = (string)($selectedRotation['rotation_name'] ?? '');

$generatedByDate = [];
foreach ($generated as $item) {
    if (!is_array($item)) {
        continue;
    }

    $date = (string)($item['fecha'] ?? '');
    if ($date === '') {
        continue;
    }

    $generatedByDate[$date] = [
        'tipo' => (string)($item['tipo'] ?? 'descanso'),
        'label_festivo' => (string)($item['label_festivo'] ?? ''),
        'tipo_festivo' => (string)($item['tipo_festivo'] ?? ''),
    ];
}

$monthNames = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre',
];
?>
<section class="section container" aria-labelledby="calendarios-title">
    <div class="section__heading">
        <h1 id="calendarios-title" class="section__title">Calendarios</h1>
    </div>

    <?php if ($years === []): ?>
        <p class="section__lead">Todavía no hay calendarios configurados.</p>
    <?php else: ?>
        <form class="calendar-filters" method="get" action="<?php echo e(url_for('calendarios')); ?>">
            <div class="calendar-filters__field">
                <label for="calendar-year">Año</label>
                <select id="calendar-year" name="year" required>
                    <?php foreach ($years as $yearItem): ?>
                        <?php
                        $yearValue = (int)($yearItem['year'] ?? 0);
                        ?>
                        <option value="<?php echo e((string)$yearValue); ?>" <?php echo $yearValue === $selectedYearValue ? 'selected' : ''; ?>><?php echo e((string)$yearValue); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="calendar-filters__field">
                <label for="calendar-rotation">Rotación</label>
                <select id="calendar-rotation" name="rotation" <?php echo $rotations === [] ? 'disabled' : ''; ?>>
                    <?php if ($rotations === []): ?>
                        <option value="">Sin rotaciones disponibles</option>
                    <?php else: ?>
                        <?php foreach ($rotations as $rotationItem): ?>
                            <?php
                            $rotationId = (int)($rotationItem['id'] ?? 0);
                            $rotationName = (string)($rotationItem['rotation_name'] ?? 'Rotación');
                            ?>
                            <option value="<?php echo e((string)$rotationId); ?>" <?php echo $rotationId === $selectedRotationId ? 'selected' : ''; ?>><?php echo e($rotationName); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="calendar-filters__actions">
                <button type="submit" class="button">Generar calendario</button>
                <?php if ($selectedRotation !== null && $generatedByDate !== []): ?>
                    <button type="button" class="button button--ghost" onclick="window.print();">Imprimir</button>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($rotations === []): ?>
            <p class="section__lead">No hay rotaciones configuradas para el año seleccionado.</p>
        <?php elseif ($selectedRotation === null || $generatedByDate === []): ?>
            <p class="section__lead">Selecciona una rotación para generar el calendario anual.</p>
        <?php else: ?>
            <div class="calendar-print-sheet">
            <div class="calendar-print-watermark" aria-hidden="true">
                <img src="<?php echo e(asset('img/logo_uso_oest.png')); ?>" alt="">
                <img src="<?php echo e(asset('img/logo_uso_oest.png')); ?>" alt="">
                <img src="<?php echo e(asset('img/logo_uso_oest.png')); ?>" alt="">
            </div>
            <header class="calendar-print-header" aria-hidden="true">
                <div class="calendar-print-header__logo-wrap">
                    <img class="calendar-print-header__logo" src="<?php echo e(asset('img/logo_uso_oest.png')); ?>" alt="USO OEST">
                </div>
                <div class="calendar-print-header__meta">
                    <p class="calendar-print-header__year">Año <?php echo e((string)$selectedYearValue); ?></p>
                    <p class="calendar-print-header__rotation">Rotación: <?php echo e($selectedRotationName !== '' ? $selectedRotationName : '—'); ?></p>
                </div>
            </header>

            <div class="calendar-legend" aria-label="Leyenda de tipos de día">
                <span class="calendar-legend__item"><span class="calendar-legend__dot calendar-day--trabajo"></span>Laborable</span>
                <span class="calendar-legend__item"><span class="calendar-legend__dot calendar-day--descanso"></span>Descanso semanal</span>
                <span class="calendar-legend__item"><span class="calendar-legend__dot calendar-day--festivo-local"></span>Festivo local</span>
                <span class="calendar-legend__item"><span class="calendar-legend__dot calendar-day--festivo-nacional"></span>Festivo nacional</span>
            </div>

            <div class="calendar-year-grid" aria-label="Calendario anual completo">
                <?php foreach ($monthNames as $month => $monthName): ?>
                <?php
                $firstDay = DateTimeImmutable::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $selectedYearValue, $month));
                if (!$firstDay instanceof DateTimeImmutable) {
                    continue;
                }
                $daysInMonth = (int)$firstDay->format('t');
                $startDow = (int)$firstDay->format('N');
                ?>
                <section class="calendar-month" data-month="<?php echo e((string)$month); ?>">
                    <h2 class="calendar-month__title"><?php echo e($monthName); ?></h2>
                    <div class="calendar-grid-wrap">
                        <table class="calendar-grid" aria-label="Calendario de <?php echo e($monthName); ?>">
                            <thead>
                            <tr>
                                <th>Lun</th>
                                <th>Mar</th>
                                <th>Mié</th>
                                <th>Jue</th>
                                <th>Vie</th>
                                <th>Sáb</th>
                                <th>Dom</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $dayCounter = 1;
                            $cell = 1;
                            while ($dayCounter <= $daysInMonth):
                                echo '<tr>';
                                for ($dow = 1; $dow <= 7; $dow++, $cell++) {
                                    if ($cell < $startDow || $dayCounter > $daysInMonth) {
                                        echo '<td class="calendar-grid__empty"></td>';
                                        continue;
                                    }

                                    $date = sprintf('%04d-%02d-%02d', $selectedYearValue, $month, $dayCounter);
                                    $dayData = $generatedByDate[$date] ?? ['tipo' => 'descanso', 'label_festivo' => ''];
                                    $dayType = (string)($dayData['tipo'] ?? 'descanso');
                                    if (!in_array($dayType, ['trabajo', 'descanso', 'festivo'], true)) {
                                        $dayType = 'descanso';
                                    }
                                    $holidayLabel = trim((string)($dayData['label_festivo'] ?? ''));
                                    $holidayType = (string)($dayData['tipo_festivo'] ?? '');
                                    $holidayTypeLabel = $holidayType === 'local' ? 'Festivo local' : 'Festivo nacional';

                                    $dayCssType = $dayType;
                                    if ($dayType === 'festivo') {
                                        $dayCssType = $holidayType === 'local' ? 'festivo-local' : 'festivo-nacional';
                                    }

                                    $title = '';
                                    if ($dayType === 'festivo') {
                                        $title = $holidayLabel !== '' ? e($holidayTypeLabel . ': ' . $holidayLabel) : e($holidayTypeLabel);
                                    }

                                    echo '<td class="calendar-day calendar-day--' . e($dayCssType) . '" ' . ($title !== '' ? 'title="' . $title . '"' : '') . '>';
                                    echo '<span class="calendar-day__number">' . e((string)$dayCounter) . '</span>';
                                    echo '</td>';

                                    $dayCounter++;
                                }
                                echo '</tr>';
                            endwhile;
                            ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="calendar-month__notes" aria-hidden="true">
                        <span class="calendar-month__note-line"></span>
                        <span class="calendar-month__note-line"></span>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
