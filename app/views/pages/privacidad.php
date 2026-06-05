<?php
declare(strict_types=1);

$title = (string)($data['title'] ?? 'Política de privacidad');
$lastUpdated = (string)($data['lastUpdated'] ?? '14 de marzo de 2026');
$developerAlias = (string)($data['developerAlias'] ?? 'ramiku');
$contactEmail = (string)($data['contactEmail'] ?? 'ramiku@gmail.com');
$appName = (string)($data['appName'] ?? 'USO OEST');
?>
<section class="section container" aria-labelledby="privacy-title">
    <h1 id="privacy-title" class="section__title"><?php echo e($title); ?></h1>
    <p class="section__lead">Última actualización: <?php echo e($lastUpdated); ?></p>

    <div class="contact-card">
        <p>
            Esta política de privacidad aplica a la aplicación y sitio web <strong><?php echo e($appName); ?></strong>,
            una plataforma informativa del sindicato USO orientada a trabajadores de OEST en temas laborales.
        </p>

        <h2>1. Responsable</h2>
        <p>
            Desarrollador: <?php echo e($developerAlias); ?><br>
            Correo de contacto: <a href="mailto:<?php echo e($contactEmail); ?>"><?php echo e($contactEmail); ?></a>
        </p>

        <h2>2. Datos personales</h2>
        <p>
            Esta aplicación no recopila, almacena, comparte ni vende datos personales de los usuarios.
            No requiere registro de cuenta ni solicita información identificable para su uso general.
        </p>

        <h2>3. Finalidad del servicio</h2>
        <p>
            El servicio se limita a ofrecer información laboral y contenidos de interés sindical relacionados con USO OEST.
        </p>

        <h2>4. Permisos y terceros</h2>
        <p>
            La aplicación no utiliza permisos invasivos para tratamiento de datos personales.
            Si en el futuro se incorporan servicios de terceros, esta política será actualizada para informar con transparencia.
        </p>

        <h2>5. Menores de edad</h2>
        <p>
            El contenido es informativo y no está diseñado específicamente para menores de 13 años.
            No se realiza recopilación intencional de datos de menores.
        </p>

        <h2>6. Cambios en esta política</h2>
        <p>
            Esta política puede actualizarse para reflejar cambios funcionales o legales. Cualquier modificación se publicará en esta misma página.
        </p>

        <h2>7. Contacto</h2>
        <p>
            Para cualquier consulta sobre privacidad, puedes escribir a
            <a href="mailto:<?php echo e($contactEmail); ?>"><?php echo e($contactEmail); ?></a>.
        </p>
    </div>
</section>