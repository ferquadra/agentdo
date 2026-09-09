(function ($) {
    'use strict';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        try {
            localStorage.setItem('agentdo-theme', theme);
        } catch (e) {}
    }

    $('#theme-toggle').on('click', function () {
        var current = document.documentElement.getAttribute('data-theme') || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });

    $('.js-copy').on('click', function () {
        var id = $(this).attr('data-target');
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        copyText(el.value || '', $(this).find('i'));
    });

    $('.js-copy-link').on('click', function () {
        var link = $(this).attr('data-link') || '';
        if (!link) {
            return;
        }
        if (link.indexOf('http') !== 0) {
            link = window.location.origin + link;
        }
        copyText(link, $(this).find('i'));
    });

    function copyText(text, $icon) {
        var done = function () {
            if (!$icon || !$icon.length) {
                return;
            }
            $icon.removeClass('bi-clipboard').addClass('bi-check2');
            setTimeout(function () {
                $icon.removeClass('bi-check2').addClass('bi-clipboard');
            }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            done();
        }
    }

    $('.js-toggle-secret').on('click', function () {
        var id = $(this).attr('data-target');
        var $input = $('#' + id);
        var show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $(this).find('i').toggleClass('bi-eye bi-eye-slash');
    });

    var $hero = $('.js-brand-hero');
    if ($hero.length) {
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var played = false;
        try {
            played = sessionStorage.getItem('agentdo-logo-played') === '1';
        } catch (e) {}

        if (reduce || played) {
            $hero.addClass('is-ready');
        } else {
            $hero.addClass('is-animate');
            try {
                sessionStorage.setItem('agentdo-logo-played', '1');
            } catch (e2) {}
        }
    }

    /* Workspace */
    var $ws = $('.workspace');
    if ($ws.length) {
        initWorkspace($ws);
    }

    function initWorkspace($root) {
        var canWrite = $root.attr('data-can-write') === '1';
        var diarioUrl = $root.attr('data-diario-url');
        var archivoUrl = $root.attr('data-archivo-url');
        var csrf = $root.attr('data-csrf');
        var $ta = $('#diario');
        var $chip = $('.js-status-chip');
        var $clock = $('.js-saved-at');
        var timer = null;
        var saving = false;
        var dirty = false;

        function setStatus(state, label) {
            $chip
                .removeClass('status-writing status-saving status-saved')
                .addClass('status-' + state)
                .attr('data-state', state)
                .text(label);
        }

        function saveDiario() {
            if (!canWrite || saving) {
                return;
            }
            saving = true;
            dirty = false;
            setStatus('saving', 'Guardando...');
            $.ajax({
                url: diarioUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    csrf: csrf,
                    texto: $ta.val()
                }
            }).done(function (res) {
                if (res && res.ok) {
                    if (res.saved_label) {
                        $clock.text(res.saved_label);
                    }
                    setStatus('saved', 'Guardado');
                } else {
                    setStatus('writing', 'Error al guardar');
                }
            }).fail(function () {
                setStatus('writing', 'Error al guardar');
                dirty = true;
            }).always(function () {
                saving = false;
                if (dirty) {
                    scheduleSave();
                }
            });
        }

        function scheduleSave() {
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(saveDiario, 1200);
        }

        if (canWrite) {
            $ta.on('input', function () {
                dirty = true;
                setStatus('writing', 'Escribiendo...');
                scheduleSave();
            });
        }

        $('.js-toggle-form').on('click', function () {
            var id = $(this).attr('data-form');
            var $form = $('#' + id);
            var open = !$form.hasClass('d-none');
            $('.rail-form').addClass('d-none');
            $('.js-toggle-form').removeClass('is-active');
            if (!open) {
                $form.removeClass('d-none');
                $(this).addClass('is-active');
            }
        });

        var $dz = $('.js-dropzone');
        var $file = $('.js-file-input');

        function uploadFile(file) {
            if (!file || !canWrite) {
                return;
            }
            var fd = new FormData();
            fd.append('csrf', csrf);
            fd.append('archivo', file);
            setStatus('saving', 'Subiendo...');
            $.ajax({
                url: archivoUrl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function (res) {
                if (res && res.ok) {
                    window.location.reload();
                } else {
                    alert((res && res.error) ? res.error : 'No se pudo subir');
                    setStatus('saved', 'Guardado');
                }
            }).fail(function () {
                alert('No se pudo subir el archivo');
                setStatus('saved', 'Guardado');
            });
        }

        $dz.on('dragenter dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dz.addClass('is-drag');
        });
        $dz.on('dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $dz.removeClass('is-drag');
            if (e.type === 'drop' && e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
                uploadFile(e.originalEvent.dataTransfer.files[0]);
            }
        });
        $file.on('change', function () {
            if (this.files && this.files[0]) {
                uploadFile(this.files[0]);
                this.value = '';
            }
        });
    }
})(jQuery);
