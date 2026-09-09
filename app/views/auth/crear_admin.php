<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">alta · paso 2 de 3</p>
        <h1 class="page-title">Guardá el passhash de admin</h1>
        <p class="muted">Empresa <span class="mono"><?php echo e($wizard['empresa']); ?></span>. Usuario reservado <span class="mono">admin</span>. No lo vamos a volver a mostrar.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="app-card app-form">
            <label class="form-label" for="admin-pass">Passhash admin</label>
            <div class="input-secret mb-3">
                <input class="form-control" type="text" id="admin-pass" readonly value="<?php echo e($wizard['admin_pass']); ?>">
                <button type="button" class="btn btn-ghost btn-sm js-copy" data-target="admin-pass" aria-label="Copiar">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            <a class="btn btn-outline-ghost w-100 mb-4" href="<?php echo e(url('crear-empresa/kit?kind=admin')); ?>">
                <i class="bi bi-download"></i> Descargar kit empresa.txt
            </a>

            <form method="post" action="<?php echo e(url('crear-empresa')); ?>">
                <?php echo Csrf::field(); ?>
                <input type="hidden" name="action" value="guardar_admin">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="guardado" name="guardado" required>
                    <label class="form-check-label" for="guardado">Ya guardé este passhash.</label>
                </div>
                <button type="submit" class="btn btn-accent w-100">Continuar</button>
            </form>
        </div>
    </div>
</section>
