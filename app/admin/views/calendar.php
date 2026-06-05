<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Ruta de navegación">
            <a href="<?= e(admin_url()) ?>">Inicio</a>
            <span class="breadcrumb__sep" aria-hidden="true">›</span>
            <span class="breadcrumb__current">Calendario</span>
        </nav>
        <h1 class="page-header__title">Calendario laboral</h1>
    </div>
</div>

<?php /* ── Selector de año ── */ ?>
<div class="panel" style="margin-bottom:1rem">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">

        <?php if (!empty($calendarYears)): ?>
        <form method="get" action="<?= e(admin_url()) ?>" style="display:flex;gap:.5rem;align-items:center">
            <input type="hidden" name="admin_path" value="calendar">
            <select name="year_id" class="select" style="width:auto">
                <option value="">— Selecciona año —</option>
                <?php foreach ($calendarYears as $yr): ?>
                <option value="<?= (int)$yr['id'] ?>"
                    <?= (int)$yr['id'] === $calendarSelectedYearId ? 'selected' : '' ?>>
                    <?= e((string)($yr['year'] ?? $yr['year_value'] ?? '')) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--ghost btn--sm">Ver</button>
        </form>

        <?php if ($calendarSelectedYearId > 0): ?>
        <form method="post" action="<?= e(admin_url()) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="delete_calendar_year">
            <input type="hidden" name="year_id" value="<?= $calendarSelectedYearId ?>">
            <button type="submit" class="btn btn--danger btn--sm"
                    onclick="return confirm('¿Eliminar el año <?= e((string)($calendarSelectedYear['year'] ?? $calendarSelectedYear['year_value'] ?? '')) ?> con todos sus festivos y rotaciones?')">
                Eliminar año
            </button>
        </form>
        <?php endif; ?>

        <?php endif; ?>

        <form method="post" action="<?= e(admin_url()) ?>" style="display:flex;gap:.5rem;align-items:center">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="create_calendar_year">
            <input type="number" name="calendar_year" class="input" style="width:100px"
                   min="2000" max="2100"
                   placeholder="<?= date('Y') ?>"
                   value="<?= empty($calendarYears) ? date('Y') : '' ?>"
                   required>
            <button type="submit" class="btn btn--primary btn--sm">Crear año</button>
        </form>

    </div>
</div>

<?php if ($calendarSelectedYearId > 0 && is_array($calendarSelectedYear)): ?>

<?php $yearValue = e((string)($calendarSelectedYear['year'] ?? $calendarSelectedYear['year_value'] ?? '')); ?>

<div class="grid-2" style="gap:1rem">

    <?php /* ── Festivos ── */ ?>
    <div>
        <div class="panel">
            <h2 class="panel__title">Festivos <?= $yearValue ?></h2>

            <?php if (empty($calendarHolidays)): ?>
            <p style="color:var(--a-muted);margin-bottom:1rem;font-size:.9rem">No hay festivos registrados para este año.</p>
            <?php else: ?>
            <ul class="attachments-list" style="margin-bottom:1rem">
                <?php foreach ($calendarHolidays as $h): ?>
                <li class="attachments-list__item">
                    <div>
                        <strong style="font-size:.9rem"><?= e((string)($h['holiday_date'] ?? '')) ?></strong>
                        <span style="font-size:.82rem;color:var(--a-muted);margin-left:.4rem">
                            <?= e(ucfirst((string)($h['holiday_type'] ?? ''))) ?>
                        </span><br>
                        <span style="font-size:.9rem"><?= e((string)($h['holiday_label'] ?? '')) ?></span>
                    </div>
                    <form method="post" action="<?= e(admin_url()) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete_calendar_holiday">
                        <input type="hidden" name="year_id" value="<?= $calendarSelectedYearId ?>">
                        <input type="hidden" name="holiday_id" value="<?= (int)$h['id'] ?>">
                        <button type="submit" class="btn btn--danger btn--sm"
                                onclick="return confirm('¿Eliminar este festivo?')">
                            Quitar
                        </button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <form method="post" action="<?= e(admin_url()) ?>" autocomplete="off" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="add_calendar_holiday">
                <input type="hidden" name="year_id" value="<?= $calendarSelectedYearId ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label label--required" for="holiday_date">Fecha</label>
                        <input id="holiday_date" name="holiday_date" type="date" class="input"
                               min="<?= $yearValue ?>-01-01" max="<?= $yearValue ?>-12-31" required>
                    </div>
                    <div class="form-group">
                        <label class="label label--required" for="holiday_label">Nombre</label>
                        <input id="holiday_label" name="holiday_label" type="text" class="input"
                               maxlength="100" required placeholder="Navidad, Año Nuevo…">
                    </div>
                    <div class="form-group">
                        <label class="label" for="holiday_type">Tipo</label>
                        <select id="holiday_type" name="holiday_type" class="select">
                            <option value="nacional">Nacional</option>
                            <option value="autonomico">Autonómico</option>
                            <option value="local">Local</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn--primary btn--sm">Añadir festivo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php /* ── Rotaciones ── */ ?>
    <div>
        <div class="panel">
            <h2 class="panel__title">
                Rotaciones <?= $yearValue ?>
                <a class="btn btn--primary btn--sm"
                   href="<?= e(admin_calendar_url(['year_id' => $calendarSelectedYearId, 'rotation_mode' => 'new'])) ?>">
                    + Nueva
                </a>
            </h2>

            <?php if (empty($calendarRotations)): ?>
            <p style="color:var(--a-muted);margin-bottom:1rem;font-size:.9rem">No hay rotaciones para este año.</p>
            <?php else: ?>
            <ul class="attachments-list" style="margin-bottom:1rem">
                <?php foreach ($calendarRotations as $rot): ?>
                <?php $rotId = (int)($rot['id'] ?? 0); ?>
                <li class="attachments-list__item">
                    <div>
                        <strong style="font-size:.9rem"><?= e((string)($rot['rotation_name'] ?? '')) ?></strong>
                        <?php if (!empty($rot['is_default'])): ?>
                        <span class="badge badge--publicada" style="margin-left:.35rem">Por defecto</span>
                        <?php endif; ?>
                        <?php if (empty($rot['is_active'])): ?>
                        <span class="badge badge--neutral" style="margin-left:.35rem">Inactiva</span>
                        <?php endif; ?>
                        <br>
                        <span style="font-size:.8rem;color:var(--a-muted)">
                            Ciclo: <?= (int)($rot['weeks_cycle'] ?? 1) ?> semana(s)
                        </span>
                    </div>
                    <div class="btn-group">
                        <a class="btn btn--ghost btn--sm"
                           href="<?= e(admin_calendar_url(['year_id' => $calendarSelectedYearId, 'rotation_mode' => 'edit', 'rotation_id' => $rotId])) ?>">
                            Editar
                        </a>
                        <form method="post" action="<?= e(admin_url()) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete_calendar_rotation">
                            <input type="hidden" name="year_id" value="<?= $calendarSelectedYearId ?>">
                            <input type="hidden" name="rotation_id" value="<?= $rotId ?>">
                            <button type="submit" class="btn btn--danger btn--sm"
                                    onclick="return confirm('¿Eliminar la rotación «<?= e(addslashes((string)($rot['rotation_name'] ?? ''))) ?>»?')">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

</div><?php /* .grid-2 */ ?>

<?php /* ── Editor de patrón de rotación ── */ ?>
<?php if ($calendarShowRotationEditor): ?>

<div class="panel" style="margin-top:1rem">
    <h2 class="panel__title">
        <?= $calendarRotationMode === 'edit' ? 'Editar rotación' : 'Nueva rotación' ?>
        <a class="btn btn--ghost btn--sm"
           href="<?= e(admin_calendar_url(['year_id' => $calendarSelectedYearId])) ?>">
            Cancelar
        </a>
    </h2>

    <form method="post"
          action="<?= e(admin_url()) ?>"
          autocomplete="off"
          novalidate
          class="js-calendar-form">

        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="action" value="save_calendar_rotation">
        <input type="hidden" name="year_id" value="<?= $calendarSelectedYearId ?>">
        <?php if ($calendarRotationMode === 'edit'): ?>
        <input type="hidden" name="rotation_id" value="<?= $calendarSelectedRotationId ?>">
        <?php endif; ?>

        <div class="form-grid">

            <div class="grid-2">
                <div class="form-group">
                    <label class="label label--required" for="rotation_name">Nombre de la rotación</label>
                    <input id="rotation_name" name="rotation_name" type="text" class="input"
                           maxlength="100" required
                           value="<?= e((string)($calendarEditingRotation['rotation_name'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="label label--required" for="weeks_cycle">Semanas en el ciclo</label>
                    <select id="weeks_cycle" name="weeks_cycle" class="select" data-calendar-weeks>
                        <?php foreach ([1, 2, 3, 4] as $w): ?>
                        <option value="<?= $w ?>"
                            <?= (int)($calendarEditingRotation['weeks_cycle'] ?? 1) === $w ? 'selected' : '' ?>>
                            <?= $w ?> semana<?= $w > 1 ? 's' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
                <label class="checkbox-row">
                    <input type="checkbox" name="is_active" value="1" class="input"
                           <?= !empty($calendarEditingRotation['is_active'] ?? true) ? 'checked' : '' ?>>
                    Rotación activa
                </label>
                <label class="checkbox-row">
                    <input type="checkbox" name="is_default" value="1" class="input"
                           <?= !empty($calendarEditingRotation['is_default'] ?? false) ? 'checked' : '' ?>>
                    Rotación por defecto
                </label>
            </div>

            <?php /* Patrón semanal */ ?>
            <div>
                <label class="label" style="margin-bottom:.5rem">Patrón semanal</label>
                <p class="helper" style="margin-bottom:.75rem">
                    Pulsa cada día para alternar entre <strong>Trabajo</strong> y <strong>Descanso</strong>.
                </p>

                <div class="calendar-pattern-grid">
                    <?php
                    $weekLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
                    $maxWeeks   = 4;
                    for ($week = 1; $week <= $maxWeeks; $week++):
                        $patternWeek = (array)($calendarEditorGrid[$week] ?? []);
                        $hiddenWeek  = $week > $calendarEditorWeeks;
                    ?>
                    <div class="calendar-week"
                         data-week="<?= $week ?>"
                         <?= $hiddenWeek ? 'hidden' : '' ?>>
                        <p class="calendar-week__label">Semana <?= $week ?></p>
                        <div class="calendar-days">
                            <?php for ($day = 1; $day <= 7; $day++):
                                $isWorking = (bool)($patternWeek[$day] ?? ($day < 6)); // default weekdays working
                                $inputId   = 'pattern_' . $week . '_' . $day;
                                $inputName = 'pattern[' . $week . '][' . $day . ']';
                                $stateClass = $isWorking ? 'is-working' : 'is-rest';
                                $stateText  = $isWorking ? 'Trabajo' : 'Descanso';
                            ?>
                            <button type="button"
                                    class="calendar-day <?= $stateClass ?>"
                                    data-calendar-toggle
                                    data-target="<?= $inputId ?>">
                                <span class="calendar-day__name"><?= $weekLabels[$day - 1] ?></span>
                                <span class="calendar-day__status day-status"><?= $stateText ?></span>
                            </button>
                            <input type="hidden"
                                   id="<?= $inputId ?>"
                                   name="<?= $inputName ?>"
                                   value="<?= $isWorking ? '1' : '0' ?>">
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn--primary js-submit-btn">
                    <?= $calendarRotationMode === 'edit' ? 'Guardar cambios' : 'Crear rotación' ?>
                </button>
            </div>

        </div><?php /* .form-grid */ ?>
    </form>
</div>

<?php endif; /* calendarShowRotationEditor */ ?>

<?php endif; /* year selected */ ?>
