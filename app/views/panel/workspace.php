<?php
$flash = '';
if (!empty($_SESSION['flash_error'])) {
    $flash = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
$diarioUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/diario');
$margenUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/margen');
$archivoUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/margen/archivo');
$propsUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/props');
$csrf = Csrf::token();
$savedLabel = format_dt($proyecto['updated_at']);
$estado = (!empty($proyecto['estado']) && $proyecto['estado'] === 'cerrado') ? 'cerrado' : 'abierto';
$aprobacion = (!empty($proyecto['aprobacion']) && $proyecto['aprobacion'] === 'requiere') ? 'requiere' : 'aprobado';
$fechaLimite = !empty($proyecto['fecha_limite']) ? $proyecto['fecha_limite'] : '';
$fechaLabel = $fechaLimite !== '' ? format_date($fechaLimite) : 'Fecha límite';
$fechaVencida = $fechaLimite !== '' && $estado === 'abierto' && date_is_past($fechaLimite);
?>
<section class="workspace"
    data-diario-url="<?php echo e($diarioUrl); ?>"
    data-props-url="<?php echo e($propsUrl); ?>"
    data-archivo-url="<?php echo e($archivoUrl); ?>"
    data-csrf="<?php echo e($csrf); ?>"
    data-can-write="<?php echo $canWrite ? '1' : '0'; ?>"
    data-share-base="<?php echo e($shareBase); ?>">
    <div class="workspace-top">
        <a class="workspace-back mono" href="<?php echo e(url('panel')); ?>">← panel</a>
        <div class="workspace-crumb">
            <span class="mono"><?php echo e($cliente['codigo']); ?></span>
            <span class="muted">/</span>
            <strong><?php echo e($proyecto['titulo']); ?></strong>
            <a class="btn btn-ghost btn-sm workspace-json" href="<?php echo e($jsonUrl); ?>" target="_blank" rel="noopener noreferrer" title="Exportar proyecto en JSON (enlace público)">JSON</a>
        </div>
    </div>

    <?php if ($flash !== '') : ?>
        <div class="alert alert-app workspace-flash" role="alert"><?php echo e($flash); ?></div>
    <?php endif; ?>

    <div class="workspace-grid">
        <aside class="workspace-rail">
            <?php if ($canWrite) : ?>
                <div class="rail-actions">
                    <button type="button" class="btn btn-rail js-toggle-form" data-form="form-nota">+ Nota</button>
                    <button type="button" class="btn btn-rail js-toggle-form" data-form="form-enlace">+ Enlace</button>
                </div>

                <form id="form-nota" class="rail-form app-form d-none" method="post" action="<?php echo e($margenUrl); ?>">
                    <?php echo Csrf::field(); ?>
                    <input type="hidden" name="tipo" value="nota">
                    <textarea class="form-control" name="cuerpo" rows="3" required maxlength="4000" placeholder="Nota al margen…"></textarea>
                    <button type="submit" class="btn btn-accent btn-sm w-100 mt-2">Guardar nota</button>
                </form>

                <form id="form-enlace" class="rail-form app-form d-none" method="post" action="<?php echo e($margenUrl); ?>">
                    <?php echo Csrf::field(); ?>
                    <input type="hidden" name="tipo" value="enlace">
                    <input class="form-control" type="url" name="cuerpo" required maxlength="2000" placeholder="https://…">
                    <button type="submit" class="btn btn-accent btn-sm w-100 mt-2">Guardar enlace</button>
                </form>

                <div class="dropzone js-dropzone" tabindex="0">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <p>Arrastrá un archivo o hacé click</p>
                    <span class="mono dropzone-hint">zip pdf xlsx docx jpg png webp mp3 mp4 wmv · 25MB</span>
                    <input type="file" class="dropzone-input js-file-input" accept=".zip,.pdf,.xlsx,.docx,.jpg,.png,.webp,.mp3,.mp4,.wmv">
                </div>
            <?php endif; ?>

            <div class="margen-list">
                <?php if (count($margen) === 0) : ?>
                    <p class="muted margen-empty">El margen está vacío.</p>
                <?php else : ?>
                    <?php foreach ($margen as $item) : ?>
                        <article class="margen-card margen-<?php echo e($item['tipo']); ?>">
                            <div class="margen-card-head">
                                <span class="margen-type mono"><?php echo e($item['tipo']); ?></span>
                                <span class="margen-head-meta">
                                    <?php if (!empty($item['operador'])) : ?>
                                        <span class="margen-op mono muted"><?php echo e($item['operador']); ?></span>
                                    <?php endif; ?>
                                    <span class="margen-date muted"><?php echo e(format_dt($item['created_at'])); ?></span>
                                </span>
                            </div>
                            <?php if ($item['tipo'] === 'nota') : ?>
                                <p class="margen-body"><?php echo nl2br(e($item['cuerpo'])); ?></p>
                            <?php elseif ($item['tipo'] === 'enlace') : ?>
                                <a class="margen-link" href="<?php echo e($item['cuerpo']); ?>" target="_blank" rel="noopener noreferrer"><?php echo e($item['cuerpo']); ?></a>
                            <?php else : ?>
                                <div class="margen-file">
                                    <a class="margen-link" href="<?php echo e(share_url($item['id'], $item['archivo'])); ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-paperclip"></i> <?php echo e($item['archivo']); ?>
                                    </a>
                                    <button type="button" class="btn btn-ghost btn-sm js-copy-link" data-link="<?php echo e(share_url($item['id'], $item['archivo'])); ?>" title="Copiar enlace">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <?php if ($canWrite) : ?>
                                <form method="post" action="<?php echo e(url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/margen/' . $item['id'] . '/borrar')); ?>" class="margen-delete" onsubmit="return confirm('¿Borrar este ítem?');">
                                    <?php echo Csrf::field(); ?>
                                    <button type="submit" class="btn btn-ghost btn-sm">Borrar</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <div class="workspace-editor">
            <div class="editor-bar">
                <div class="editor-bar-left">
                    <span class="editor-clock mono js-saved-at"><?php echo e($savedLabel); ?></span>
                    <div class="editor-props">
                        <?php if ($canWrite) : ?>
                            <button type="button" class="prop-chip prop-estado js-prop-estado is-<?php echo e($estado); ?>" data-estado="<?php echo e($estado); ?>" aria-pressed="<?php echo $estado === 'abierto' ? 'true' : 'false'; ?>" title="Cambiar abierto / cerrado">
                                <?php echo $estado === 'cerrado' ? 'Cerrado' : 'Abierto'; ?>
                            </button>
                            <div class="prop-deadline<?php echo $fechaLimite !== '' ? ' has-value' : ''; ?><?php echo $fechaVencida ? ' is-overdue' : ''; ?>" data-fecha="<?php echo e($fechaLimite); ?>">
                                <button type="button" class="prop-chip prop-date js-deadline-open" aria-haspopup="dialog" aria-controls="deadline-glass" title="Elegir fecha límite">
                                    <i class="bi bi-calendar3"></i>
                                    <span class="js-deadline-label"><?php echo e($fechaLabel); ?></span>
                                </button>
                                <button type="button" class="prop-clear js-deadline-clear<?php echo $fechaLimite === '' ? ' d-none' : ''; ?>" title="Quitar fecha" aria-label="Quitar fecha">&times;</button>
                            </div>
                            <button type="button" class="prop-chip prop-aprob js-prop-aprob is-<?php echo e($aprobacion); ?>" data-aprobacion="<?php echo e($aprobacion); ?>" title="Cambiar requiere aprobación / aprobado">
                                <?php echo $aprobacion === 'requiere' ? 'Requiere aprobación' : 'Aprobado'; ?>
                            </button>
                        <?php else : ?>
                            <span class="prop-chip prop-estado is-<?php echo e($estado); ?>"><?php echo $estado === 'cerrado' ? 'Cerrado' : 'Abierto'; ?></span>
                            <span class="prop-deadline<?php echo $fechaLimite !== '' ? ' has-value' : ''; ?><?php echo $fechaVencida ? ' is-overdue' : ''; ?>">
                                <span class="prop-chip prop-date">
                                    <i class="bi bi-calendar3"></i>
                                    <span><?php echo e($fechaLabel); ?></span>
                                </span>
                            </span>
                            <span class="prop-chip prop-aprob is-<?php echo e($aprobacion); ?>"><?php echo $aprobacion === 'requiere' ? 'Requiere aprobación' : 'Aprobado'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="status-chip status-saved js-status-chip" data-state="saved">Guardado</span>
            </div>
            <textarea
                id="diario"
                class="diario-textarea"
                <?php echo $canWrite ? '' : 'readonly'; ?>
                spellcheck="true"
                placeholder="<?php echo $canWrite ? 'Escribí el diario del proyecto…' : 'Solo lectura'; ?>"
            ><?php echo e($diario); ?></textarea>
        </div>
    </div>

    <?php if ($canWrite) : ?>
        <div id="deadline-glass" class="deadline-glass js-deadline-glass" hidden>
            <button type="button" class="deadline-glass-dim js-deadline-dismiss" aria-label="Cerrar"></button>
            <div class="deadline-glass-card" role="dialog" aria-modal="true" aria-labelledby="deadline-glass-title">
                <p class="deadline-glass-kicker" id="deadline-glass-title">Fecha límite</p>
                <p class="deadline-glass-value js-cal-picked">Sin fecha</p>
                <div class="deadline-cal-nav">
                    <button type="button" class="deadline-cal-nav-btn js-cal-prev" aria-label="Mes anterior"><i class="bi bi-chevron-left"></i></button>
                    <span class="deadline-cal-month js-cal-title"></span>
                    <button type="button" class="deadline-cal-nav-btn js-cal-next" aria-label="Mes siguiente"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="deadline-cal-dow" aria-hidden="true">
                    <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
                </div>
                <div class="deadline-cal-grid js-cal-grid"></div>
                <div class="deadline-glass-actions">
                    <button type="button" class="btn btn-ghost btn-sm js-deadline-today">Hoy</button>
                    <button type="button" class="btn btn-ghost btn-sm js-deadline-clear-pop">Quitar</button>
                    <button type="button" class="btn btn-accent btn-sm js-deadline-apply">Listo</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
