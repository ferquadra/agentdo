<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">alta · paso 3 de 3</p>
        <h1 class="page-title">Primer operador</h1>
        <p class="muted">Empresa <span class="mono"><?php echo e($wizard['empresa']); ?></span>. Este usuario crea clientes y proyectos.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form" method="post" action="<?php echo e(url('crear-empresa')); ?>" autocomplete="off">
            <?php echo Csrf::field(); ?>
            <input type="hidden" name="action" value="operador">
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre</label>
                <input class="form-control" type="text" id="nombre" name="nombre" value="<?php echo e($op['nombre']); ?>" required minlength="2" maxlength="80">
            </div>
            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario</label>
                <input class="form-control" type="text" id="usuario" name="usuario" value="<?php echo e($op['usuario']); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" spellcheck="false">
                <div class="form-hint">No puede ser <span class="mono">admin</span>.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?php echo e($op['email']); ?>" required maxlength="120">
            </div>
            <div class="mb-3">
                <label class="form-label" for="telefono">Teléfono <span class="muted">(opcional)</span></label>
                <input class="form-control" type="text" id="telefono" name="telefono" value="<?php echo e($op['telefono']); ?>" maxlength="40">
            </div>
            <div class="mb-4">
                <label class="form-label" for="permiso">Permiso</label>
                <select class="form-select" id="permiso" name="permiso">
                    <option value="rw"<?php echo $op['permiso'] === 'rw' ? ' selected' : ''; ?>>Escritura y lectura</option>
                    <option value="ro"<?php echo $op['permiso'] === 'ro' ? ' selected' : ''; ?>>Solo lectura</option>
                </select>
            </div>
            <button type="submit" class="btn btn-accent w-100">Crear operador</button>
        </form>
    </div>
</section>
