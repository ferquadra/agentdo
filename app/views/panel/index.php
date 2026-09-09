<?php
$nuevoProyectoUrl = url('panel/proyectos/nuevo');
if (!empty($filtroCliente)) {
    $nuevoProyectoUrl .= '?cliente=' . rawurlencode($filtroCliente['codigo']);
}
?>
<section class="section-narrow section-panel">
    <div class="container-xl">
        <div class="panel-head d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
            <div>
                <p class="mono-label"><?php echo e($user['empresa']); ?> · <?php echo e($user['usuario']); ?></p>
                <h1 class="page-title mb-1">Panel</h1>
                <p class="muted mb-0">
                    <?php if (!empty($filtroCliente)) : ?>
                        Proyectos de <?php echo e($filtroCliente['nombre']); ?>
                        <span class="mono"> · <?php echo e($filtroCliente['codigo']); ?></span>
                    <?php else : ?>
                        <?php echo $canWrite ? 'Escritura y lectura.' : 'Solo lectura.'; ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="panel-actions d-flex flex-wrap gap-2">
                <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/clientes')); ?>">Clientes</a>
                <?php if ($canWrite) : ?>
                    <a class="btn btn-outline-ghost" href="<?php echo e(url('panel/clientes/nuevo')); ?>">+ Crear cliente</a>
                    <?php if ($clientesCount > 0) : ?>
                        <a class="btn btn-accent" href="<?php echo e($nuevoProyectoUrl); ?>">+ Crear proyecto</a>
                    <?php else : ?>
                        <button type="button" class="btn btn-accent" disabled title="Primero creá un cliente">+ Crear proyecto</button>
                    <?php endif; ?>
                <?php else : ?>
                    <button type="button" class="btn btn-outline-ghost" disabled>+ Crear cliente</button>
                    <button type="button" class="btn btn-accent" disabled>+ Crear proyecto</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($clientesCount > 0) : ?>
            <div class="client-filter js-client-filter mb-4" data-open="0">
                <label class="client-filter-label mono-label" for="client-filter-search">Filtrar por cliente</label>
                <div class="client-filter-box">
                    <button type="button" class="client-filter-trigger js-client-filter-trigger" aria-expanded="false" aria-haspopup="listbox">
                        <?php if (!empty($filtroCliente)) : ?>
                            <span class="client-filter-chip">
                                <span class="client-filter-chip-name"><?php echo e($filtroCliente['nombre']); ?></span>
                                <span class="mono client-filter-chip-code"><?php echo e($filtroCliente['codigo']); ?></span>
                            </span>
                        <?php else : ?>
                            <span class="client-filter-placeholder">Todos los clientes</span>
                        <?php endif; ?>
                        <i class="bi bi-chevron-down client-filter-caret"></i>
                    </button>
                    <?php if (!empty($filtroCliente)) : ?>
                        <a class="client-filter-clear" href="<?php echo e(url('panel')); ?>" title="Quitar filtro" aria-label="Quitar filtro">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                    <div class="client-filter-menu js-client-filter-menu" hidden>
                        <div class="client-filter-search-wrap">
                            <i class="bi bi-search"></i>
                            <input class="client-filter-search js-client-filter-search" id="client-filter-search" type="search" placeholder="Buscar por nombre o código…" autocomplete="off" spellcheck="false">
                        </div>
                        <ul class="client-filter-list" role="listbox">
                            <li class="js-client-filter-item" data-search="todos los clientes">
                                <a class="client-filter-option<?php echo empty($filtroCliente) ? ' is-active' : ''; ?>" href="<?php echo e(url('panel')); ?>">
                                    <span class="client-filter-option-main">
                                        <span class="client-filter-option-name">Todos los clientes</span>
                                        <span class="client-filter-option-hint">Sin filtro</span>
                                    </span>
                                    <span class="mono client-filter-option-count"><?php echo (int) $totalProyectos; ?></span>
                                </a>
                            </li>
                            <?php foreach ($clientes as $c) : ?>
                                <?php
                                $isActive = !empty($filtroCliente) && $filtroCliente['codigo'] === $c['codigo'];
                                $search = strtolower($c['nombre'] . ' ' . $c['codigo']);
                                ?>
                                <li class="js-client-filter-item" data-search="<?php echo e($search); ?>">
                                    <a class="client-filter-option<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo e(url('panel') . '?cliente=' . rawurlencode($c['codigo'])); ?>">
                                        <span class="client-filter-option-main">
                                            <span class="client-filter-option-name"><?php echo e($c['nombre']); ?></span>
                                            <span class="mono client-filter-option-code"><?php echo e($c['codigo']); ?></span>
                                        </span>
                                        <span class="mono client-filter-option-count"><?php echo (int) $c['proyectos']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="client-filter-empty js-client-filter-empty muted d-none">Ningún cliente coincide.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (count($proyectos) === 0) : ?>
            <div class="app-card app-card-wide">
                <?php if (!empty($filtroCliente)) : ?>
                    <p class="mb-2"><?php echo e($filtroCliente['nombre']); ?> todavía no tiene proyectos.</p>
                    <?php if ($canWrite) : ?>
                        <a class="btn btn-accent" href="<?php echo e($nuevoProyectoUrl); ?>">+ Crear proyecto</a>
                    <?php endif; ?>
                <?php elseif ($clientesCount === 0) : ?>
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
