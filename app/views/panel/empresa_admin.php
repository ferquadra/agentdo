<section class="section-narrow section-panel">
    <div class="container-xl">
        <div class="panel-head d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mono-label"><?php echo e($empresa['codigo']); ?> · administración</p>
                <h1 class="page-title mb-1">Administrar empresa</h1>
                <p class="muted mb-0">Alta: <span class="mono"><?php echo e(format_dt($empresa['created_at'])); ?></span></p>
            </div>
            <div class="panel-actions d-flex flex-wrap gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel')); ?>">← Panel</a>
                <form method="post" action="<?php echo e(url('panel/empresa/cerrar')); ?>" class="d-inline">
                    <?php echo Csrf::field(); ?>
                    <button type="submit" class="btn btn-ghost">Cerrar administración</button>
                </form>
            </div>
        </div>

        <?php if ($flash !== '') : ?>
            <div class="alert alert-app app-card-wide mb-3" role="alert"><?php echo e($flash); ?></div>
        <?php endif; ?>

        <div class="app-card app-card-wide mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h6 mb-0">Passhash de empresa (admin)</h2>
                <form method="post" action="<?php echo e(url('panel/empresa/admin/rotar')); ?>" onsubmit="return confirm('¿Rotar el passhash de admin?\n\nEl anterior deja de funcionar. Vas a ver el nuevo una sola vez.');">
                    <?php echo Csrf::field(); ?>
                    <button type="submit" class="btn btn-ghost btn-sm">Rotar passhash admin</button>
                </form>
            </div>
            <p class="muted small mb-0">Es el mismo que pedimos al entrar a esta sección.</p>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="h6 mb-0">Operadores</h2>
            <a class="btn btn-accent btn-sm" href="<?php echo e(url('panel/empresa/operadores/nuevo')); ?>">+ Añadir operador</a>
        </div>

        <?php if (count($operadores) === 0) : ?>
            <div class="app-card app-card-wide">
                <p class="mb-2 muted">Todavía no hay operadores además de admin.</p>
                <a class="btn btn-accent btn-sm" href="<?php echo e(url('panel/empresa/operadores/nuevo')); ?>">+ Añadir operador</a>
            </div>
        <?php else : ?>
            <div class="admin-table admin-table-operadores app-card-wide">
                <div class="admin-table-head mono">
                    <span>Usuario</span>
                    <span>Nombre</span>
                    <span>Email</span>
                    <span>Teléfono</span>
                    <span>Permiso</span>
                    <span>Alta</span>
                    <span></span>
                </div>
                <?php foreach ($operadores as $op) : ?>
                    <div class="admin-table-row">
                        <span class="mono admin-code"><?php echo e($op['usuario']); ?></span>
                        <span class="admin-name"><?php echo e($op['nombre']); ?></span>
                        <span class="admin-email muted"><?php echo e($op['email']); ?></span>
                        <span class="mono admin-phone muted"><?php echo e($op['telefono'] !== null && $op['telefono'] !== '' ? $op['telefono'] : '—'); ?></span>
                        <span class="mono admin-perm"><?php echo e($op['permiso']); ?></span>
                        <span class="mono admin-date muted"><?php echo e(format_dt($op['created_at'])); ?></span>
                        <span class="admin-actions">
                            <a class="btn btn-ghost btn-sm" href="<?php echo e(url('panel/empresa/operadores/' . $op['usuario'] . '/editar')); ?>">Editar</a>
                            <form method="post" action="<?php echo e(url('panel/empresa/operadores/' . $op['usuario'] . '/rotar')); ?>" class="d-inline" onsubmit="return confirm('¿Rotar passhash de <?php echo e($op['usuario']); ?>?\n\nVas a ver el nuevo una sola vez.');">
                                <?php echo Csrf::field(); ?>
                                <button type="submit" class="btn btn-ghost btn-sm">Rotar</button>
                            </form>
                            <?php if ($op['usuario'] !== 'admin') : ?>
                                <form method="post" action="<?php echo e(url('panel/empresa/operadores/' . $op['usuario'] . '/borrar')); ?>" class="d-inline" onsubmit="return confirm('¿Borrar al operador <?php echo e($op['usuario']); ?>?\n\nNo podrá volver a ingresar.');">
                                    <?php echo Csrf::field(); ?>
                                    <button type="submit" class="btn btn-ghost btn-sm text-danger"<?php echo $op['usuario'] === $user['usuario'] ? ' disabled title="No podés borrarte mientras administrás"' : ''; ?>>Borrar</button>
                                </form>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
