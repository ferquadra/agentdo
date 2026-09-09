<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">sesión</p>
        <h1 class="page-title">Ingresar</h1>
        <p class="muted">Usá el passhash que te dimos. El browser puede recordarlo.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form class="app-card app-form" method="post" action="<?php echo e(url('ingresar')); ?>" autocomplete="on">
            <?php echo Csrf::field(); ?>
            <div class="mb-3">
                <label class="form-label" for="empresa">Empresa</label>
                <input class="form-control" type="text" id="empresa" name="empresa" value="<?php echo e($empresa); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" autocorrect="off" spellcheck="false" autocomplete="organization">
            </div>
            <div class="mb-3">
                <label class="form-label" for="usuario">Usuario operador</label>
                <input class="form-control" type="text" id="usuario" name="usuario" value="<?php echo e($usuario); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" autocorrect="off" spellcheck="false" autocomplete="username">
            </div>
            <div class="mb-4">
                <label class="form-label" for="passhash">Passhash del operador</label>
                <div class="input-secret">
                    <input class="form-control" type="password" id="passhash" name="passhash" required minlength="8" maxlength="64" autocomplete="current-password" spellcheck="false">
                    <button type="button" class="btn btn-ghost btn-sm js-toggle-secret" data-target="passhash" aria-label="Mostrar passhash">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-accent w-100">Recuperar sesión</button>
        </form>
    </div>
</section>
