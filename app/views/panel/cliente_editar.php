<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">cliente · <?php echo e($cliente['codigo']); ?></p>
        <h1 class="page-title"><?php echo $canWrite ? 'Editar cliente' : 'Cliente'; ?></h1>
        <p class="muted">El código no se cambia (va en la ruta). Alta: <span class="mono"><?php echo e(format_dt($cliente['created_at'])); ?></span></p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form" method="post" action="<?php echo e(url('panel/clientes/' . $cliente['codigo'] . '/editar')); ?>" autocomplete="off">
            <?php echo Csrf::field(); ?>
            <div class="mb-3">
                <label class="form-label" for="codigo">Código</label>
                <input class="form-control mono-input" type="text" id="codigo" value="<?php echo e($cliente['codigo']); ?>" readonly disabled>
            </div>
            <div class="mb-4">
                <label class="form-label" for="nombre">Nombre</label>
                <input class="form-control" type="text" id="nombre" name="nombre" value="<?php echo e($nombre); ?>" required maxlength="120" <?php echo $canWrite ? '' : 'readonly'; ?>>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/clientes')); ?>">Volver</a>
                <?php if ($canWrite) : ?>
                    <button type="submit" class="btn btn-accent flex-grow-1">Guardar</button>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($canWrite) : ?>
            <form class="mt-4" method="post" action="<?php echo e(url('panel/clientes/' . $cliente['codigo'] . '/borrar')); ?>" onsubmit="return confirm('¿Borrar el cliente <?php echo e($cliente['codigo']); ?>?\n\nDesaparece del panel y sus proyectos dejan de listarse. Los archivos en disco no se borran.');">
                <?php echo Csrf::field(); ?>
                <button type="submit" class="btn btn-outline-ghost text-danger w-100">Borrar cliente</button>
            </form>
        <?php endif; ?>
    </div>
</section>
