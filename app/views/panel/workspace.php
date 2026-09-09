<?php
$flash = '';
if (!empty($_SESSION['flash_error'])) {
    $flash = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
$diarioUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/diario');
$margenUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/margen');
$archivoUrl = url('panel/' . $cliente['codigo'] . '/' . $proyecto['codigo'] . '/margen/archivo');
$csrf = Csrf::token();
$savedLabel = format_dt($proyecto['updated_at']);
?>
<section class="workspace"
    data-diario-url="<?php echo e($diarioUrl); ?>"
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
                                <span class="margen-date muted"><?php echo e(format_dt($item['created_at'])); ?></span>
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
                <span class="editor-clock mono js-saved-at"><?php echo e($savedLabel); ?></span>
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
</section>
