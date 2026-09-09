<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">nuevo cliente</p>
        <h1 class="page-title">Crear cliente</h1>
        <p class="muted">El código va en el path. El nombre puede tener espacios.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form" method="post" action="<?php echo e(url('panel/clientes/nuevo')); ?>" autocomplete="off">
            <?php echo Csrf::field(); ?>
            <div class="mb-3">
                <label class="form-label" for="codigo">Código</label>
                <input class="form-control" type="text" id="codigo" name="codigo" value="<?php echo e($codigo); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" spellcheck="false" placeholder="acme">
                <div class="form-hint">Solo <span class="mono">a-z0-9</span>.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="nombre">Nombre</label>
                <input class="form-control" type="text" id="nombre" name="nombre" value="<?php echo e($nombre); ?>" required maxlength="120" placeholder="Acme Corp">
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/clientes')); ?>">Cancelar</a>
                <button type="submit" class="btn btn-accent flex-grow-1">Crear cliente</button>
            </div>
        </form>
    </div>
</section>
