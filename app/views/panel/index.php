<section class="section-narrow section-panel">
    <div class="container-xl">
        <div class="panel-head d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mono-label"><?php echo e($user['empresa']); ?> · <?php echo e($user['usuario']); ?></p>
                <h1 class="page-title mb-1">Panel</h1>
                <p class="muted mb-0">
                    <?php echo $canWrite ? 'Escritura y lectura.' : 'Solo lectura.'; ?>
                </p>
            </div>
            <div class="panel-actions d-flex flex-wrap gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/clientes')); ?>">Clientes</a>
                <?php if ($canWrite) : ?>
                    <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/clientes/nuevo')); ?>">+ Crear cliente</a>
                    <?php if ($clientesCount > 0) : ?>
                        <a class="btn btn-accent" href="<?php echo e(url('panel/proyectos/nuevo')); ?>">+ Crear proyecto</a>
                    <?php else : ?>
                        <button type="button" class="btn btn-accent" disabled title="Primero creá un cliente">+ Crear proyecto</button>
                    <?php endif; ?>
                <?php else : ?>
                    <button type="button" class="btn btn-outline-ghost" disabled>+ Crear cliente</button>
                    <button type="button" class="btn btn-accent" disabled>+ Crear proyecto</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($proyectos) === 0) : ?>
            <div class="app-card app-card-wide">
                <?php if ($clientesCount === 0) : ?>
                    <p class="mb-2">Todavía no hay clientes ni proyectos.</p>
                    <p class="muted mb-0">Creá un cliente y después un proyecto para empezar el diario.</p>
                <?php else : ?>
                    <p class="mb-2">Hay clientes, pero todavía no hay proyectos.</p>
                    <p class="muted mb-0">Creá el primero y abrí el workspace.</p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="project-list">
                <?php foreach ($proyectos as $p) : ?>
                    <a class="project-row" href="<?php echo e(url('panel/' . $p['cliente'] . '/' . $p['codigo'])); ?>">
                        <div class="project-row-main">
                            <span class="project-title"><?php echo e($p['titulo']); ?></span>
                            <span class="project-meta mono">
                                <?php echo e($p['cliente']); ?>/<?php echo e($p['codigo']); ?>
                                <?php if (!empty($p['cliente_nombre'])) : ?>
                                    · <?php echo e($p['cliente_nombre']); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="project-row-date">
                            <span class="muted">Actualizado</span>
                            <span class="mono"><?php echo e(format_dt($p['updated_at'])); ?></span>
                        </div>
                        <i class="bi bi-chevron-right project-row-arrow"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
