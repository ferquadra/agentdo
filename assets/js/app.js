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

    var $proyectoNuevo = $('.js-proyecto-nuevo');
    if ($proyectoNuevo.length) {
        initProyectoNuevo($proyectoNuevo);
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

        initWorkspaceProps($root, csrf, canWrite);
    }

    function initWorkspaceProps($root, csrf, canWrite) {
        var propsUrl = $root.attr('data-props-url');
        if (!propsUrl || !canWrite) {
            return;
        }

        var $estado = $root.find('.js-prop-estado');
        var $aprob = $root.find('.js-prop-aprob');
        var $dateLabel = $root.find('.js-deadline-label');
        var $dateWrap = $root.find('.prop-deadline');
        var $clear = $root.find('.js-deadline-clear');
        var $clock = $root.find('.js-saved-at');
        var $glass = $root.find('.js-deadline-glass');
        var $calGrid = $root.find('.js-cal-grid');
        var $calTitle = $root.find('.js-cal-title');
        var $calPicked = $root.find('.js-cal-picked');
        var saving = false;
        var pending = false;
        var calYear = 0;
        var calMonth = 0;
        var calDraft = '';
        var monthsEs = [
            'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
        ];

        function pad2(n) {
            return n < 10 ? '0' + n : String(n);
        }

        function ymdFromDate(d) {
            return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        }

        function parseYmd(ymd) {
            if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(ymd)) {
                return null;
            }
            var p = ymd.split('-');
            return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        }

        function labelFromYmd(ymd) {
            var d = parseYmd(ymd);
            if (!d) {
                return 'Sin fecha';
            }
            var mon = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return pad2(d.getDate()) + ' ' + mon[d.getMonth()] + ' ' + d.getFullYear();
        }

        function currentEstado() {
            return $estado.attr('data-estado') === 'cerrado' ? 'cerrado' : 'abierto';
        }

        function currentAprob() {
            return $aprob.attr('data-aprobacion') === 'requiere' ? 'requiere' : 'aprobado';
        }

        function currentFecha() {
            return $.trim($dateWrap.attr('data-fecha') || '');
        }

        function paintEstado(estado) {
            $estado
                .attr('data-estado', estado)
                .attr('aria-pressed', estado === 'abierto' ? 'true' : 'false')
                .removeClass('is-abierto is-cerrado')
                .addClass('is-' + estado)
                .text(estado === 'cerrado' ? 'Cerrado' : 'Abierto');
        }

        function paintAprob(aprob) {
            $aprob
                .attr('data-aprobacion', aprob)
                .removeClass('is-requiere is-aprobado')
                .addClass('is-' + aprob)
                .text(aprob === 'requiere' ? 'Requiere aprobación' : 'Aprobado');
        }

        function paintFecha(ymd, label, vencida) {
            $dateWrap.attr('data-fecha', ymd || '');
            $dateLabel.text(ymd ? (label || labelFromYmd(ymd)) : 'Fecha límite');
            $dateWrap.toggleClass('has-value', !!ymd);
            $dateWrap.toggleClass('is-overdue', !!(ymd && vencida && currentEstado() === 'abierto'));
            $clear.toggleClass('d-none', !ymd);
        }

        function saveProps() {
            if (saving) {
                pending = true;
                return;
            }
            saving = true;
            pending = false;
            $.ajax({
                url: propsUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    csrf: csrf,
                    estado: currentEstado(),
                    aprobacion: currentAprob(),
                    fecha_limite: currentFecha()
                }
            }).done(function (res) {
                if (res && res.ok) {
                    paintEstado(res.estado);
                    paintAprob(res.aprobacion);
                    paintFecha(res.fecha_limite || '', res.fecha_label || '', !!res.fecha_vencida);
                    if (res.saved_label) {
                        $clock.text(res.saved_label);
                    }
                }
            }).always(function () {
                saving = false;
                if (pending) {
                    saveProps();
                }
            });
        }

        function applyFecha(ymd) {
            paintFecha(ymd, '', false);
            saveProps();
        }

        function renderCal() {
            var first = new Date(calYear, calMonth, 1);
            var startPad = (first.getDay() + 6) % 7;
            var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
            var today = ymdFromDate(new Date());
            var html = '';
            var i;
            var day;
            var ymd;
            var cls;
            $calTitle.text(monthsEs[calMonth] + ' ' + calYear);
            $calPicked.text(calDraft ? labelFromYmd(calDraft) : 'Sin fecha');
            for (i = 0; i < startPad; i += 1) {
                html += '<span></span>';
            }
            for (day = 1; day <= daysInMonth; day += 1) {
                ymd = calYear + '-' + pad2(calMonth + 1) + '-' + pad2(day);
                cls = 'deadline-cal-day';
                if (ymd === today) {
                    cls += ' is-today';
                }
                if (ymd === calDraft) {
                    cls += ' is-selected';
                }
                html += '<button type="button" class="' + cls + '" data-ymd="' + ymd + '">' + day + '</button>';
            }
            $calGrid.html(html);
        }

        function openGlass() {
            var cur = currentFecha();
            var base = parseYmd(cur) || new Date();
            calDraft = cur;
            calYear = base.getFullYear();
            calMonth = base.getMonth();
            renderCal();
            $glass.removeAttr('hidden');
        }

        function closeGlass() {
            $glass.attr('hidden', 'hidden');
        }

        $estado.on('click', function () {
            paintEstado(currentEstado() === 'abierto' ? 'cerrado' : 'abierto');
            if (currentEstado() === 'cerrado') {
                $dateWrap.removeClass('is-overdue');
            }
            saveProps();
        });

        $aprob.on('click', function () {
            paintAprob(currentAprob() === 'aprobado' ? 'requiere' : 'aprobado');
            saveProps();
        });

        $root.find('.js-deadline-open').on('click', function () {
            openGlass();
        });

        $root.find('.js-deadline-dismiss').on('click', function () {
            closeGlass();
        });

        $root.find('.js-cal-prev').on('click', function () {
            calMonth -= 1;
            if (calMonth < 0) {
                calMonth = 11;
                calYear -= 1;
            }
            renderCal();
        });

        $root.find('.js-cal-next').on('click', function () {
            calMonth += 1;
            if (calMonth > 11) {
                calMonth = 0;
                calYear += 1;
            }
            renderCal();
        });

        $calGrid.on('click', '.deadline-cal-day', function () {
            calDraft = $(this).attr('data-ymd') || '';
            renderCal();
        });

        $root.find('.js-deadline-today').on('click', function () {
            var now = new Date();
            calDraft = ymdFromDate(now);
            calYear = now.getFullYear();
            calMonth = now.getMonth();
            renderCal();
        });

        $root.find('.js-deadline-clear-pop').on('click', function () {
            applyFecha('');
            closeGlass();
        });

        $root.find('.js-deadline-apply').on('click', function () {
            applyFecha(calDraft);
            closeGlass();
        });

        $clear.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            applyFecha('');
        });

        $(document).on('keydown.deadlineGlass', function (e) {
            if ($glass.is('[hidden]')) {
                return;
            }
            if ((e.key || e.which) === 'Escape' || e.which === 27) {
                closeGlass();
            }
        });
    }

    function codigoFromTitulo(titulo) {
        var map = {
            'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u',
            'à': 'a', 'è': 'e', 'ì': 'i', 'ò': 'o', 'ù': 'u',
            'ä': 'a', 'ë': 'e', 'ï': 'i', 'ö': 'o', 'ü': 'u',
            'â': 'a', 'ê': 'e', 'î': 'i', 'ô': 'o', 'û': 'u',
            'ñ': 'n', 'ç': 'c'
        };
        var s = String(titulo || '').toLowerCase();
        var out = '';
        var i;
        var ch;
        for (i = 0; i < s.length; i += 1) {
            ch = s.charAt(i);
            if (map[ch]) {
                ch = map[ch];
            }
            if (/[a-z0-9]/.test(ch)) {
                out += ch;
            }
            if (out.length >= 32) {
                break;
            }
        }
        return out;
    }

    function initProyectoNuevo($form) {
        var codes = {};
        try {
            codes = JSON.parse($form.attr('data-codes') || '{}') || {};
        } catch (err) {
            codes = {};
        }
        var $titulo = $form.find('.js-proyecto-titulo');
        var $cliente = $form.find('.js-proyecto-cliente');
        var $hint = $form.find('.js-proyecto-code-hint');
        var $submit = $form.find('.js-proyecto-submit');

        function taken(cliente, codigo) {
            var list = codes[cliente] || [];
            var i;
            for (i = 0; i < list.length; i += 1) {
                if (list[i] === codigo) {
                    return true;
                }
            }
            return false;
        }

        function refresh() {
            var codigo = codigoFromTitulo($titulo.val());
            var cliente = $cliente.val() || '';
            $hint.removeClass('is-ok is-bad');
            if (!codigo) {
                $hint.text('código · —');
                $submit.prop('disabled', true);
                return;
            }
            if (codigo.length < 3) {
                $hint.addClass('is-bad').text('código · ' + codigo + ' · faltan letras o números (mínimo 3)');
                $submit.prop('disabled', true);
                return;
            }
            if (taken(cliente, codigo)) {
                $hint.addClass('is-bad').text('código · ' + codigo + ' · ya existe en este cliente. Cambiá el nombre, por ejemplo un 2 al final.');
                $submit.prop('disabled', true);
                return;
            }
            $hint.addClass('is-ok').text('código · ' + codigo);
            $submit.prop('disabled', false);
        }

        $titulo.on('input', refresh);
        $cliente.on('change', refresh);
        refresh();
    }
})(jQuery);
