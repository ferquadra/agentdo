<?php
$isAdmin = $rotate['usuario'] === 'admin';
$kitName = $isAdmin ? 'empresa.txt' : 'operador.txt';
?>
<section class="section-narrow section-panel">
    <div class="container-xl">
        <p class="mono-label"><?php echo e($empresa['codigo']); ?> · passhash nuevo</p>
        <h1 class="page-title">Guardá el passhash<?php echo $isAdmin ? ' de empresa' : ' del operador'; ?></h1>
        <p class="muted">
            Usuario <span class="mono"><?php echo e($rotate['usuario']); ?></span>
            en <span class="mono"><?php echo e($empresa['codigo']); ?></span>.
            No lo vamos a volver a mostrar.
        </p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app app-card-wide mb-3" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="app-card app-form app-card-wide">
            <label class="form-label" for="rotate-pass">Passhash</label>
            <div class="input-secret mb-3">
                <input class="form-control mono" type="text" id="rotate-pass" readonly value="<?php echo e($rotate['passhash']); ?>">
                <button type="button" class="btn btn-ghost btn-sm js-copy" data-target="rotate-pass" aria-label="Copiar">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            <a class="btn btn-outline-ghost w-100 mb-4" href="<?php echo e(url('panel/empresa/kit?usuario=' . rawurlencode($rotate['usuario']))); ?>">
                <i class="bi bi-download"></i> Descargar kit <?php echo e($kitName); ?>
            </a>

            <form method="post" action="<?php echo e(url('panel/empresa/passhash')); ?>">
                <?php echo Csrf::field(); ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="guardado" name="guardado" required>
                    <label class="form-check-label" for="guardado">Ya guardé este passhash.</label>
                </div>
                <button type="submit" class="btn btn-accent w-100">Volver a administración</button>
            </form>
        </div>
    </div>
</section>
