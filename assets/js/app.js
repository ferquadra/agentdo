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

    var $clientFilter = $('.js-client-filter');
    if ($clientFilter.length) {
        initClientFilter($clientFilter);
    }

    /* Workspace */
    var $ws = $('.workspace');
    if ($ws.length) {
        initWorkspace($ws);
    }

    function initClientFilter($root) {
        var rootEl = $root.get(0);
        var $trigger = $root.find('.js-client-filter-trigger');
        var $menu = $root.find('.js-client-filter-menu');
        var $search = $root.find('.js-client-filter-search');
        var $items = $root.find('.js-client-filter-item');
        var $empty = $root.find('.js-client-filter-empty');
        var $options = $items.find('.client-filter-option');

        function visibleItems() {
            return $items.filter(function () {
                return $(this).css('display') !== 'none';
            });
        }

        function setFocusIndex(index) {
            var $vis = visibleItems();
            $options.removeClass('is-focus');
            if (!$vis.length) {
                return -1;
            }
            if (index < 0) {
                index = $vis.length - 1;
            }
            if (index >= $vis.length) {
                index = 0;
            }
            $vis.eq(index).find('.client-filter-option').addClass('is-focus');
            return index;
        }

        function currentFocusIndex() {
            var $vis = visibleItems();
            var i = -1;
            $vis.each(function (idx) {
                if ($(this).find('.client-filter-option').hasClass('is-focus')) {
                    i = idx;
                    return false;
                }
            });
            return i;
        }

        function isOpen() {
            return $root.hasClass('is-open');
        }

        function open() {
            $menu.removeAttr('hidden');
            $root.addClass('is-open');
            $trigger.attr('aria-expanded', 'true');
            $search.val('');
            filterItems('');
            var active = 0;
            visibleItems().each(function (idx) {
                if ($(this).find('.client-filter-option').hasClass('is-active')) {
                    active = idx;
                    return false;
                }
            });
            setFocusIndex(active);
            window.setTimeout(function () {
                $search.trigger('focus');
            }, 0);
        }

        function close() {
            $root.removeClass('is-open');
            $menu.attr('hidden', 'hidden');
            $trigger.attr('aria-expanded', 'false');
            $options.removeClass('is-focus');
        }

        function filterItems(q) {
            q = $.trim(q).toLowerCase();
            var visible = 0;
            $items.each(function () {
                var hay = ($(this).attr('data-search') || '').indexOf(q) !== -1;
                $(this).toggle(hay);
                if (hay) {
                    visible += 1;
                }
            });
            $empty.toggleClass('d-none', visible !== 0);
            setFocusIndex(0);
        }

        $trigger.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (isOpen()) {
                close();
            } else {
                open();
            }
        });

        $search.on('input', function () {
            filterItems($(this).val());
        });

        $search.on('keydown', function (e) {
            var key = e.key || e.which;
            if (key === 'ArrowDown' || key === 40) {
                e.preventDefault();
                setFocusIndex(currentFocusIndex() + 1);
            } else if (key === 'ArrowUp' || key === 38) {
                e.preventDefault();
                setFocusIndex(currentFocusIndex() - 1);
            } else if (key === 'Enter' || key === 13) {
                var $vis = visibleItems();
                var idx = currentFocusIndex();
                if (idx >= 0 && $vis.eq(idx).length) {
                    e.preventDefault();
                    window.location.href = $vis.eq(idx).find('a').attr('href');
                }
            } else if (key === 'Escape' || key === 27) {
                e.preventDefault();
                close();
                $trigger.trigger('focus');
            }
        });

        $(document).on('mousedown.clientFilter', function (e) {
            if (!isOpen() || !rootEl || rootEl.contains(e.target)) {
                return;
            }
            close();
        });
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
