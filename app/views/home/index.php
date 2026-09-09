<section class="hero">
    <div class="container-xl">
        <div class="hero-logo">
            <?php
            $logoClass = 'brand-logo brand-logo-hero';
            $logoAnimate = !empty($animateLogo);
            require APP_PATH . '/views/partials/logo.php';
            ?>
        </div>
        <p class="hero-lead">El cuaderno de tus proyectos. Hecho para humanos y para agentes.</p>
        <ul class="hero-points">
            <li>
                <i class="bi bi-buildings"></i>
                <span>Multi-empresa: cada tenant en su carpeta, aislado.</span>
            </li>
            <li>
                <i class="bi bi-journal-text"></i>
                <span>Diario en texto plano, con autoguardado.</span>
            </li>
            <li>
                <i class="bi bi-paperclip"></i>
                <span>Margen de anotaciones: texto, enlaces y adjuntos.</span>
            </li>
        </ul>
        <div class="hero-cta">
            <a class="btn btn-outline-ghost btn-lg" href="<?php echo e(url('ingresar')); ?>">Ingresar</a>
            <a class="btn btn-accent btn-lg" href="<?php echo e(url('crear-empresa')); ?>">+ Crear empresa</a>
        </div>
    </div>
</section>
