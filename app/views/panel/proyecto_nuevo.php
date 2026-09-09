<section class="section-narrow">
    <div class="container-xl">
        <p class="mono-label">nuevo proyecto</p>
        <h1 class="page-title">Crear proyecto</h1>
        <p class="muted">Asociá el proyecto a un cliente existente.</p>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-app" role="alert"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($needsCliente)) : ?>
            <div class="app-card">
                <p class="mb-3">Todavía no hay clientes.</p>
                <a class="btn btn-accent" href="<?php echo e(url('panel/clientes/nuevo')); ?>">+ Crear cliente</a>
            </div>
        <?php else : ?>
            <form class="app-card app-form" method="post" action="<?php echo e(url('panel/proyectos/nuevo')); ?>" autocomplete="off">
                <?php echo Csrf::field(); ?>
                <div class="mb-3">
                    <label class="form-label" for="cliente">Cliente</label>
                    <select class="form-select" id="cliente" name="cliente" required>
                        <?php foreach ($clientes as $c) : ?>
                            <option value="<?php echo e($c['codigo']); ?>"<?php echo $cliente === $c['codigo'] ? ' selected' : ''; ?>>
                                <?php echo e($c['nombre']); ?> (<?php echo e($c['codigo']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="codigo">Código de proyecto</label>
                    <input class="form-control" type="text" id="codigo" name="codigo" value="<?php echo e($codigo); ?>" required minlength="3" maxlength="32" pattern="[a-z0-9]+" autocapitalize="off" spellcheck="false" placeholder="portal">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="titulo">Título</label>
                    <input class="form-control" type="text" id="titulo" name="titulo" value="<?php echo e($titulo); ?>" required maxlength="160" placeholder="Portal web">
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-ghost" href="<?php echo e(url('panel')); ?>">Cancelar</a>
                    <button type="submit" class="btn btn-accent flex-grow-1">Crear proyecto</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>
