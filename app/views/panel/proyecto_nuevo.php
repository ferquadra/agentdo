<?php
$codesJson = json_encode(isset($codesByCliente) ? $codesByCliente : array());
if ($codesJson === false) {
    $codesJson = '{}';
}
?>
<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">nuevo proyecto</p>
        <h1 class="page-title">Crear proyecto</h1>
        <p class="muted">El código se arma solo con las letras y números del nombre. Tiene que ser único por cliente.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($needsCliente)) : ?>
            <div class="app-card">
                <p class="mb-3">Todavía no hay clientes.</p>
                <a class="btn btn-accent" href="<?php echo e(url('panel/clientes/nuevo')); ?>">+ Crear cliente</a>
            </div>
        <?php else : ?>
            <form class="app-card app-form js-proyecto-nuevo" method="post" action="<?php echo e(url('panel/proyectos/nuevo')); ?>" autocomplete="off" data-codes="<?php echo e($codesJson); ?>">
                <?php echo Csrf::field(); ?>
                <div class="mb-3">
                    <label class="form-label" for="cliente">Cliente</label>
                    <select class="form-select js-proyecto-cliente" id="cliente" name="cliente" required>
                        <?php foreach ($clientes as $c) : ?>
                            <option value="<?php echo e($c['codigo']); ?>"<?php echo $cliente === $c['codigo'] ? ' selected' : ''; ?>>
                                <?php echo e($c['nombre']); ?> (<?php echo e($c['codigo']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="titulo">Nombre</label>
                    <input class="form-control js-proyecto-titulo" type="text" id="titulo" name="titulo" value="<?php echo e($titulo); ?>" required maxlength="160" placeholder="Portal web">
                    <p class="proyecto-code-hint mono js-proyecto-code-hint" aria-live="polite">código · —</p>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="fecha_limite">Fecha límite</label>
                    <input class="form-control" type="date" id="fecha_limite" name="fecha_limite" value="<?php echo e(isset($fechaLimite) ? $fechaLimite : ''); ?>">
                    <p class="muted form-hint mb-0">Opcional. Sirve para ordenar prioridades en el panel.</p>
                </div>
                <div class="mb-4 form-check">
                    <input class="form-check-input" type="checkbox" id="aprobacion" name="aprobacion" value="requiere"<?php echo (!empty($aprobacion) && $aprobacion === 'requiere') ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="aprobacion">Requiere aprobación</label>
                </div>
                <div class="d-flex gap-2">
                    <?php
                    $cancelUrl = url('panel');
                    if ($cliente !== '' && Storage::isCode($cliente)) {
                        $cancelUrl .= '?cliente=' . rawurlencode($cliente);
                    }
                    ?>
                    <a class="btn btn-outline-ghost" href="<?php echo e($cancelUrl); ?>">Cancelar</a>
                    <button type="submit" class="btn btn-accent flex-grow-1 js-proyecto-submit">Crear proyecto</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
