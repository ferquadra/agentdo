<section class="section-narrow section-panel">
    <div class="container-xl">
        <div class="panel-head mb-4">
            <p class="mono-label"><?php echo e($user['empresa']); ?> · administración</p>
            <h1 class="page-title mb-1">Administrar empresa</h1>
            <p class="muted mb-0">Ingresá el passhash de empresa (usuario <span class="mono">admin</span>, el de <span class="mono">empresa.txt</span>). No es el passhash de tu operador.</p>
        </div>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app app-card-wide mb-3" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form app-card-wide" method="post" action="<?php echo e(url('panel/empresa')); ?>">
            <?php echo Csrf::field(); ?>
            <div class="mb-4">
                <label class="form-label" for="passhash">Passhash de empresa (admin)</label>
                <div class="input-secret">
                    <input class="form-control" type="password" id="passhash" name="passhash" required minlength="8" maxlength="64" autocomplete="off" spellcheck="false">
                    <button type="button" class="btn btn-ghost btn-sm js-toggle-secret" data-target="passhash" aria-label="Mostrar passhash">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-accent">Desbloquear administración</button>
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel')); ?>">← Panel</a>
            </div>
        </form>
    </div>
</section>
