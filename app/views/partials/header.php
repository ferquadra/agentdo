<?php
$user = Auth::user();
?>
<header class="app-header">
    <div class="container-xl d-flex align-items-center justify-content-between gap-3">
        <?php
        $logoClass = 'brand-logo brand-logo-sm';
        $logoAnimate = false;
        require APP_PATH . '/views/partials/logo.php';
        ?>
        <div class="d-flex align-items-center gap-2">
            <?php if ($user) : ?>
                <a class="btn btn-ghost btn-sm d-none d-sm-inline" href="<?php echo e(url('panel')); ?>">Panel</a>
                <a class="btn btn-ghost btn-sm d-none d-md-inline" href="<?php echo e(url('panel/empresa')); ?>">Administrar Empresa</a>
                <span class="header-user mono d-none d-md-inline"><?php echo e($user['empresa']); ?>/<?php echo e($user['usuario']); ?></span>
                <a class="btn btn-ghost btn-sm" href="<?php echo e(url('salir')); ?>">Salir</a>
            <?php elseif ($page !== 'ingresar') : ?>
                <a class="btn btn-ghost btn-sm" href="<?php echo e(url('ingresar')); ?>">Ingresar</a>
            <?php endif; ?>
            <button type="button" class="btn btn-theme" id="theme-toggle" aria-label="Cambiar tema">
                <i class="bi bi-moon-stars theme-icon-dark"></i>
                <i class="bi bi-sun theme-icon-light"></i>
            </button>
        </div>
    </div>
</header>
