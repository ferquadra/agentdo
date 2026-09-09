<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">alta · paso 1 de 3</p>
        <h1 class="page-title">Crear empresa</h1>
        <p class="muted">Código alfanumérico, sin espacios ni mayúsculas. Tiene que ser único.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form" method="post" action="<?php echo e(url('crear-empresa')); ?>" autocomplete="off">
            <?php echo Csrf::field(); ?>
            <input type="hidden" name="action" value="codigo">
            <div class="mb-4">
                <label class="form-label" for="codigo">Código de empresa</label>
                <input class="form-control form-control-lg mono-input" type="text" id="codigo" name="codigo" value="<?php echo e($codigo); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" autocorrect="off" spellcheck="false" placeholder="acme">
                <div class="form-hint">Solo <span class="mono">a-z</span> y <span class="mono">0-9</span>. Ejemplo: <span class="mono">acme</span>.</div>
            </div>
            <button type="submit" class="btn btn-accent w-100">Continuar</button>
        </form>
    </div>
</section>
