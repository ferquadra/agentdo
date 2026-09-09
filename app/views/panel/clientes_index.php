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
                <p class="muted mb-0">Ordenados por fecha de alta. El código o el nombre abren los proyectos de ese cliente.</p>
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
            <div class="admin-table admin-table-clientes app-card-wide">
                <div class="admin-table-head mono">
                    <span>Código</span>
                    <span>Nombre</span>
                    <span>Alta</span>
                    <span>Proyectos</span>
                    <span></span>
                </div>
                <?php foreach ($clientes as $c) : ?>
                    <?php $proyectosUrl = url('panel') . '?cliente=' . rawurlencode($c['codigo']); ?>
                    <div class="admin-table-row">
                        <a class="admin-link mono admin-code" href="<?php echo e($proyectosUrl); ?>"><?php echo e($c['codigo']); ?></a>
                        <a class="admin-link admin-name" href="<?php echo e($proyectosUrl); ?>"><?php echo e($c['nombre']); ?></a>
                        <span class="mono admin-date muted"><?php echo e(format_dt($c['created_at'])); ?></span>
                        <a class="admin-link mono admin-count" href="<?php echo e($proyectosUrl); ?>" title="Ver proyectos"><?php echo (int) $c['proyectos']; ?></a>
                        <span class="admin-actions">
                            <?php if ($canWrite) : ?>
                                <a class="btn btn-ghost btn-sm" href="<?php echo e(url('panel/proyectos/nuevo') . '?cliente=' . rawurlencode($c['codigo'])); ?>">Crear proyecto</a>
                            <?php endif; ?>
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
