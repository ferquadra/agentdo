<?php
$nuevoProyectoUrl = url('panel/proyectos/nuevo');
if (!empty($filtroCliente)) {
    $nuevoProyectoUrl .= '?cliente=' . rawurlencode($filtroCliente['codigo']);
}
$panelHref = function ($clienteCodigo, $cerrados) {
    $q = array();
    if ($clienteCodigo !== '') {
        $q['cliente'] = $clienteCodigo;
    }
    if ($cerrados) {
        $q['cerrados'] = '1';
    }
    $href = url('panel');
    if (count($q) > 0) {
        $href .= '?' . http_build_query($q);
    }
    return $href;
};
$filtroCodigo = !empty($filtroCliente) ? $filtroCliente['codigo'] : '';
$todosCount = !empty($mostrarCerrados) ? (int) $totalProyectos : (int) $totalAbiertos;
$cerradosCount = isset($counts['cerrados']) ? (int) $counts['cerrados'] : 0;
$abiertosCount = isset($counts['abiertos']) ? (int) $counts['abiertos'] : 0;
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
            <div class="panel-actions d-flex flex-wrap align-items-center justify-content-end gap-2">
                <?php if ($clientesCount > 0) : ?>
                    <div class="client-filter js-client-filter">
                        <div class="client-filter-box">
                            <button type="button" class="btn btn-outline-ghost client-filter-trigger js-client-filter-trigger" aria-expanded="false" aria-haspopup="listbox" aria-label="Filtrar por cliente">
                                <i class="bi bi-funnel"></i>
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
                                <a class="btn btn-outline-ghost client-filter-clear" href="<?php echo e($panelHref('', !empty($mostrarCerrados))); ?>" title="Quitar filtro" aria-label="Quitar filtro">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="client-filter-menu js-client-filter-menu" hidden>
                            <div class="client-filter-search-wrap">
                                <i class="bi bi-search"></i>
                                <input class="client-filter-search js-client-filter-search" type="search" placeholder="Buscar cliente…" autocomplete="off" spellcheck="false">
                            </div>
                            <ul class="client-filter-list" role="listbox">
                                <li class="js-client-filter-item" data-search="todos los clientes">
                                    <a class="client-filter-option<?php echo empty($filtroCliente) ? ' is-active' : ''; ?>" href="<?php echo e($panelHref('', !empty($mostrarCerrados))); ?>">
                                        <span class="client-filter-option-main">
                                            <span class="client-filter-option-name">Todos los clientes</span>
                                            <span class="client-filter-option-hint">Sin filtro</span>
                                        </span>
                                        <span class="mono client-filter-option-count"><?php echo $todosCount; ?></span>
                                    </a>
                                </li>
                                <?php foreach ($clientes as $c) : ?>
                                    <?php
                                    $isActive = !empty($filtroCliente) && $filtroCliente['codigo'] === $c['codigo'];
                                    $search = strtolower($c['nombre'] . ' ' . $c['codigo']);
                                    ?>
                                    <li class="js-client-filter-item" data-search="<?php echo e($search); ?>">
                                        <?php $cliCount = !empty($mostrarCerrados) ? (int) $c['proyectos'] : (int) $c['proyectos_abiertos']; ?>
                                        <a class="client-filter-option<?php echo $isActive ? ' is-active' : ''; ?>" href="<?php echo e($panelHref($c['codigo'], !empty($mostrarCerrados))); ?>">
                                            <span class="client-filter-option-main">
                                                <span class="client-filter-option-name"><?php echo e($c['nombre']); ?></span>
                                                <span class="mono client-filter-option-code"><?php echo e($c['codigo']); ?></span>
                                            </span>
                                            <span class="mono client-filter-option-count"><?php echo $cliCount; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="client-filter-empty js-client-filter-empty muted d-none">Ningún cliente coincide.</p>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($totalProyectos > 0) : ?>
                    <a class="btn btn-outline-ghost<?php echo !empty($mostrarCerrados) ? ' is-on' : ''; ?>" href="<?php echo e($panelHref($filtroCodigo, empty($mostrarCerrados))); ?>" title="<?php echo !empty($mostrarCerrados) ? 'Ocultar proyectos cerrados' : 'Incluir proyectos cerrados al final'; ?>">
                        <i class="bi bi-archive"></i>
                        Cerrados
                        <span class="mono panel-filter-count"><?php echo $cerradosCount; ?></span>
                    </a>
                <?php endif; ?>
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

        <?php if (count($proyectos) === 0) : ?>
            <div class="app-card app-card-wide">
                <?php if (!empty($filtroCliente) && $abiertosCount === 0 && $cerradosCount > 0 && empty($mostrarCerrados)) : ?>
                    <p class="mb-2"><?php echo e($filtroCliente['nombre']); ?> no tiene proyectos abiertos.</p>
                    <a class="btn btn-outline-ghost" href="<?php echo e($panelHref($filtroCodigo, true)); ?>">Ver <?php echo $cerradosCount; ?> cerrado<?php echo $cerradosCount === 1 ? '' : 's'; ?></a>
                <?php elseif (!empty($filtroCliente)) : ?>
                    <p class="mb-2"><?php echo e($filtroCliente['nombre']); ?> todavía no tiene proyectos.</p>
                    <?php if ($canWrite) : ?>
                        <a class="btn btn-accent" href="<?php echo e($nuevoProyectoUrl); ?>">+ Crear proyecto</a>
                    <?php endif; ?>
                <?php elseif ($clientesCount === 0) : ?>
                    <p class="mb-2">Todavía no hay clientes ni proyectos.</p>
                    <p class="muted mb-0">Creá un cliente y después un proyecto para empezar el diario.</p>
                <?php elseif ($abiertosCount === 0 && $cerradosCount > 0 && empty($mostrarCerrados)) : ?>
                    <p class="mb-2">No hay proyectos abiertos.</p>
                    <a class="btn btn-outline-ghost" href="<?php echo e($panelHref('', true)); ?>">Ver <?php echo $cerradosCount; ?> cerrado<?php echo $cerradosCount === 1 ? '' : 's'; ?></a>
                <?php else : ?>
                    <p class="mb-2">Hay clientes, pero todavía no hay proyectos.</p>
                    <p class="muted mb-0">Creá el primero y abrí el workspace.</p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="project-list">
                <?php foreach ($proyectos as $p) : ?>
                    <?php
                    $isCerrado = isset($p['estado']) && $p['estado'] === 'cerrado';
                    $isRequiere = isset($p['aprobacion']) && $p['aprobacion'] === 'requiere';
                    $fechaLimite = !empty($p['fecha_limite']) ? $p['fecha_limite'] : '';
                    $fechaVencida = $fechaLimite !== '' && !$isCerrado && date_is_past($fechaLimite);
                    ?>
                    <a class="project-row<?php echo $isCerrado ? ' is-closed' : ''; ?>" href="<?php echo e(url('panel/' . $p['cliente'] . '/' . $p['codigo'])); ?>">
                        <div class="project-row-main">
                            <span class="project-title"><?php echo e($p['titulo']); ?></span>
                            <span class="project-meta mono">
                                <?php echo e($p['cliente']); ?>/<?php echo e($p['codigo']); ?>
                                <?php if (!empty($p['cliente_nombre'])) : ?>
                                    · <?php echo e($p['cliente_nombre']); ?>
                                <?php endif; ?>
                                <?php if (!empty($p['operador'])) : ?>
                                    · <?php echo e($p['operador']); ?>
                                <?php endif; ?>
                            </span>
                            <?php if ($isCerrado || $isRequiere || $fechaLimite !== '') : ?>
                                <span class="project-tags">
                                    <?php if ($isCerrado) : ?>
                                        <span class="project-tag tag-cerrado">Cerrado</span>
                                    <?php endif; ?>
                                    <?php if ($isRequiere) : ?>
                                        <span class="project-tag tag-requiere">Requiere aprobación</span>
                                    <?php endif; ?>
                                    <?php if ($fechaLimite !== '') : ?>
                                        <span class="project-tag tag-fecha<?php echo $fechaVencida ? ' is-overdue' : ''; ?>"><?php echo e(format_date($fechaLimite)); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
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
