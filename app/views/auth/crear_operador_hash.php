<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">alta · paso 3 de 3</p>
        <h1 class="page-title">Guardá el passhash del operador</h1>
        <p class="muted">Usuario <span class="mono"><?php echo e($wizard['op_usuario']); ?></span> en <span class="mono"><?php echo e($wizard['empresa']); ?></span>. No lo vamos a volver a mostrar.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="app-card app-form">
            <label class="form-label" for="op-pass">Passhash operador</label>
            <div class="input-secret mb-3">
                <input class="form-control" type="text" id="op-pass" readonly value="<?php echo e($wizard['op_pass']); ?>">
                <button type="button" class="btn btn-ghost btn-sm js-copy" data-target="op-pass" aria-label="Copiar">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            <a class="btn btn-outline-ghost w-100 mb-4" href="<?php echo e(url('crear-empresa/kit?kind=operador')); ?>">
                <i class="bi bi-download"></i> Descargar kit operador.txt
            </a>

            <form method="post" action="<?php echo e(url('crear-empresa')); ?>">
                <?php echo Csrf::field(); ?>
                <input type="hidden" name="action" value="entrar">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="guardado" name="guardado" required>
                    <label class="form-check-label" for="guardado">Ya guardé este passhash.</label>
                </div>
                <button type="submit" class="btn btn-accent w-100">Entrar al panel</button>
            </form>
        </div>
    </div>
</section>
