<section class="section-narrow section-panel">
    <div class="container-xl">
        <p class="mono-label"><?php echo e($empresa['codigo']); ?> · operador nuevo</p>
        <h1 class="page-title">Añadir operador</h1>
        <p class="muted mb-0">Empresa <span class="mono"><?php echo e($empresa['codigo']); ?></span>. Tras crearlo vas a ver el passhash una sola vez.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app app-card-wide mt-3" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form app-card-wide mt-4" method="post" action="<?php echo e(url('panel/empresa/operadores/nuevo')); ?>" autocomplete="off">
            <?php echo Csrf::field(); ?>
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre</label>
                <input class="form-control" type="text" id="nombre" name="nombre" value="<?php echo e($nombre); ?>" required minlength="2" maxlength="80">
            </div>
            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario</label>
                <input class="form-control" type="text" id="usuario" name="usuario" value="<?php echo e($usuario); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" spellcheck="false">
                <div class="form-hint">No puede ser <span class="mono">admin</span>.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?php echo e($email); ?>" required maxlength="120">
            </div>
            <div class="mb-3">
                <label class="form-label" for="telefono">Teléfono <span class="muted">(opcional)</span></label>
                <input class="form-control" type="text" id="telefono" name="telefono" value="<?php echo e($telefono); ?>" maxlength="40">
            </div>
            <div class="mb-4">
                <label class="form-label" for="permiso">Permiso</label>
                <select class="form-select" id="permiso" name="permiso">
                    <option value="rw"<?php echo $permiso === 'rw' ? ' selected' : ''; ?>>Escritura y lectura</option>
                    <option value="ro"<?php echo $permiso === 'ro' ? ' selected' : ''; ?>>Solo lectura</option>
                </select>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/empresa')); ?>">Volver</a>
                <button type="submit" class="btn btn-accent flex-grow-1">Crear operador</button>
            </div>
        </form>
    </div>
</section>
