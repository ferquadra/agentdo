<?php
$flash = '';
if (!empty($_SESSION['flash_error'])) {
    $flash = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>
<section class="section-narrow section-panel">
    <div class="container-xl">
        <div class="panel-head d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mono-label"><?php echo e($user['empresa']); ?> · clientes</p>
                <h1 class="page-title mb-1">Clientes</h1>
                <p class="muted mb-0">Ordenados por fecha de alta. Solo código y nombre.</p>
            </div>
            <div class="panel-actions d-flex flex-wrap gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel')); ?>">← Panel</a>
                <?php if ($canWrite) : ?>
                    <a class="btn btn-accent" href="<?php echo e(url('panel/clientes/nuevo')); ?>">+ Crear cliente</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($flash !== '') : ?>
            <div class="alert alert-app app-card-wide mb-3" role="alert"><?php echo e($flash); ?></div>
        <?php endif; ?>

        <?php if (count($clientes) === 0) : ?>
            <div class="app-card app-card-wide">
                <p class="mb-2">Todavía no hay clientes.</p>
                <?php if ($canWrite) : ?>
                    <a class="btn btn-accent" href="<?php echo e(url('panel/clientes/nuevo')); ?>">+ Crear cliente</a>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="admin-table app-card-wide">
                <div class="admin-table-head mono">
                    <span>Código</span>
                    <span>Nombre</span>
                    <span>Alta</span>
                    <span>Proyectos</span>
                    <span></span>
                </div>
                <?php foreach ($clientes as $c) : ?>
                    <div class="admin-table-row">
                        <span class="mono admin-code"><?php echo e($c['codigo']); ?></span>
                        <span class="admin-name"><?php echo e($c['nombre']); ?></span>
                        <span class="mono admin-date muted"><?php echo e(format_dt($c['created_at'])); ?></span>
                        <span class="mono admin-count"><?php echo (int) $c['proyectos']; ?></span>
                        <span class="admin-actions">
                            <a class="btn btn-ghost btn-sm" href="<?php echo e(url('panel/clientes/' . $c['codigo'] . '/editar')); ?>">
                                <?php echo $canWrite ? 'Editar' : 'Ver'; ?>
                            </a>
                            <?php if ($canWrite) : ?>
                                <form method="post" action="<?php echo e(url('panel/clientes/' . $c['codigo'] . '/borrar')); ?>" class="d-inline" onsubmit="return confirm('¿Borrar el cliente <?php echo e($c['codigo']); ?>?\n\nDesaparece del panel y sus proyectos dejan de listarse. Los archivos en disco no se borran.');">
                                    <?php echo Csrf::field(); ?>
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger">Borrar</button>
                                </form>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
