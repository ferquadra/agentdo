(function (global) {
    'use strict';

    // Fuente de matriz 5x7. Cada glifo son 5 columnas.
    // Bits 0..6 = filas del cuerpo. Bit 7 y bit 8 = las dos filas de acento
    // que quedan arriba del cuerpo (para mayúsculas acentuadas).
    var ACC_LOW = 0x80;
    var ACC_TOP = 0x100;

    var GLYPH_COLS = 5;
    var CHAR_GAP = 1;
    var BODY_ROWS = 7;
    var ACCENT_ROWS = 2;
    var BOARD_ROWS = ACCENT_ROWS + BODY_ROWS + 1;
    var BODY_TOP = ACCENT_ROWS;

    var FONT = {
        ' ': [0x00, 0x00, 0x00, 0x00, 0x00],
        '!': [0x00, 0x00, 0x5f, 0x00, 0x00],
        '"': [0x00, 0x07, 0x00, 0x07, 0x00],
        '#': [0x14, 0x7f, 0x14, 0x7f, 0x14],
        '$': [0x24, 0x2a, 0x7f, 0x2a, 0x12],
        '%': [0x23, 0x13, 0x08, 0x64, 0x62],
        '&': [0x36, 0x49, 0x55, 0x22, 0x50],
        "'": [0x00, 0x05, 0x03, 0x00, 0x00],
        '(': [0x00, 0x1c, 0x22, 0x41, 0x00],
        ')': [0x00, 0x41, 0x22, 0x1c, 0x00],
        '*': [0x14, 0x08, 0x3e, 0x08, 0x14],
        '+': [0x08, 0x08, 0x3e, 0x08, 0x08],
        ',': [0x00, 0x50, 0x30, 0x00, 0x00],
        '-': [0x08, 0x08, 0x08, 0x08, 0x08],
        '.': [0x00, 0x60, 0x60, 0x00, 0x00],
        '/': [0x20, 0x10, 0x08, 0x04, 0x02],
        '0': [0x3e, 0x51, 0x49, 0x45, 0x3e],
        '1': [0x00, 0x42, 0x7f, 0x40, 0x00],
        '2': [0x42, 0x61, 0x51, 0x49, 0x46],
        '3': [0x21, 0x41, 0x45, 0x4b, 0x31],
        '4': [0x18, 0x14, 0x12, 0x7f, 0x10],
        '5': [0x27, 0x45, 0x45, 0x45, 0x39],
        '6': [0x3c, 0x4a, 0x49, 0x49, 0x30],
        '7': [0x01, 0x71, 0x09, 0x05, 0x03],
        '8': [0x36, 0x49, 0x49, 0x49, 0x36],
        '9': [0x06, 0x49, 0x49, 0x29, 0x1e],
        ':': [0x00, 0x36, 0x36, 0x00, 0x00],
        ';': [0x00, 0x56, 0x36, 0x00, 0x00],
        '<': [0x00, 0x08, 0x14, 0x22, 0x41],
        '=': [0x14, 0x14, 0x14, 0x14, 0x14],
        '>': [0x41, 0x22, 0x14, 0x08, 0x00],
        '?': [0x02, 0x01, 0x51, 0x09, 0x06],
        '@': [0x32, 0x49, 0x79, 0x41, 0x3e],
        'A': [0x7e, 0x11, 0x11, 0x11, 0x7e],
        'B': [0x7f, 0x49, 0x49, 0x49, 0x36],
        'C': [0x3e, 0x41, 0x41, 0x41, 0x22],
        'D': [0x7f, 0x41, 0x41, 0x22, 0x1c],
        'E': [0x7f, 0x49, 0x49, 0x49, 0x41],
        'F': [0x7f, 0x09, 0x09, 0x01, 0x01],
        'G': [0x3e, 0x41, 0x49, 0x49, 0x3a],
        'H': [0x7f, 0x08, 0x08, 0x08, 0x7f],
        'I': [0x00, 0x41, 0x7f, 0x41, 0x00],
        'J': [0x20, 0x40, 0x41, 0x3f, 0x01],
        'K': [0x7f, 0x08, 0x14, 0x22, 0x41],
        'L': [0x7f, 0x40, 0x40, 0x40, 0x40],
        'M': [0x7f, 0x02, 0x0c, 0x02, 0x7f],
        'N': [0x7f, 0x04, 0x08, 0x10, 0x7f],
        'O': [0x3e, 0x41, 0x41, 0x41, 0x3e],
        'P': [0x7f, 0x09, 0x09, 0x09, 0x06],
        'Q': [0x3e, 0x41, 0x51, 0x21, 0x5e],
        'R': [0x7f, 0x09, 0x19, 0x29, 0x46],
        'S': [0x46, 0x49, 0x49, 0x49, 0x31],
        'T': [0x01, 0x01, 0x7f, 0x01, 0x01],
        'U': [0x3f, 0x40, 0x40, 0x40, 0x3f],
        'V': [0x1f, 0x20, 0x40, 0x20, 0x1f],
        'W': [0x7f, 0x20, 0x18, 0x20, 0x7f],
        'X': [0x63, 0x14, 0x08, 0x14, 0x63],
        'Y': [0x03, 0x04, 0x78, 0x04, 0x03],
        'Z': [0x61, 0x51, 0x49, 0x45, 0x43],
        '[': [0x00, 0x00, 0x7f, 0x41, 0x41],
        '\\': [0x02, 0x04, 0x08, 0x10, 0x20],
        ']': [0x41, 0x41, 0x7f, 0x00, 0x00],
        '^': [0x04, 0x02, 0x01, 0x02, 0x04],
        '_': [0x40, 0x40, 0x40, 0x40, 0x40],
        '`': [0x00, 0x01, 0x02, 0x04, 0x00],
        'a': [0x20, 0x54, 0x54, 0x54, 0x78],
        'b': [0x7f, 0x48, 0x44, 0x44, 0x38],
        'c': [0x38, 0x44, 0x44, 0x44, 0x20],
        'd': [0x38, 0x44, 0x44, 0x48, 0x7f],
        'e': [0x38, 0x54, 0x54, 0x54, 0x18],
        'f': [0x08, 0x7e, 0x09, 0x01, 0x02],
        'g': [0x08, 0x14, 0x54, 0x54, 0x3c],
        'h': [0x7f, 0x08, 0x04, 0x04, 0x78],
        'i': [0x00, 0x44, 0x7d, 0x40, 0x00],
        'j': [0x20, 0x40, 0x44, 0x3d, 0x00],
        'k': [0x00, 0x7f, 0x10, 0x28, 0x44],
        'l': [0x00, 0x41, 0x7f, 0x40, 0x00],
        'm': [0x7c, 0x04, 0x18, 0x04, 0x78],
        'n': [0x7c, 0x08, 0x04, 0x04, 0x78],
        'o': [0x38, 0x44, 0x44, 0x44, 0x38],
        'p': [0x7c, 0x14, 0x14, 0x14, 0x08],
        'q': [0x08, 0x14, 0x14, 0x18, 0x7c],
        'r': [0x7c, 0x08, 0x04, 0x04, 0x08],
        's': [0x48, 0x54, 0x54, 0x54, 0x20],
        't': [0x04, 0x3f, 0x44, 0x40, 0x20],
        'u': [0x3c, 0x40, 0x40, 0x20, 0x7c],
        'v': [0x1c, 0x20, 0x40, 0x20, 0x1c],
        'w': [0x3c, 0x40, 0x30, 0x40, 0x3c],
        'x': [0x44, 0x28, 0x10, 0x28, 0x44],
        'y': [0x0c, 0x50, 0x50, 0x50, 0x3c],
        'z': [0x44, 0x64, 0x54, 0x4c, 0x44],
        '{': [0x00, 0x08, 0x36, 0x41, 0x00],
        '|': [0x00, 0x00, 0x7f, 0x00, 0x00],
        '}': [0x00, 0x41, 0x36, 0x08, 0x00],
        '~': [0x08, 0x08, 0x2a, 0x1c, 0x08],
        '·': [0x00, 0x00, 0x08, 0x00, 0x00],
        '¡': [0x00, 0x00, 0x7d, 0x00, 0x00],
        '¿': [0x30, 0x48, 0x45, 0x40, 0x20],
        'á': [0x20, 0x54, 0x56, 0x55, 0x78],
        'é': [0x38, 0x54, 0x56, 0x55, 0x18],
        'í': [0x00, 0x44, 0x7e, 0x41, 0x00],
        'ó': [0x38, 0x44, 0x46, 0x45, 0x38],
        'ú': [0x3c, 0x40, 0x42, 0x21, 0x7c],
        'ü': [0x3c, 0x41, 0x40, 0x21, 0x7c],
        'ñ': [0x7c, 0x09, 0x05, 0x05, 0x78],
        'Á': [0x7e, 0x11, 0x11 | ACC_LOW, 0x11 | ACC_TOP, 0x7e],
        'É': [0x7f, 0x49, 0x49 | ACC_LOW, 0x49 | ACC_TOP, 0x41],
        'Í': [0x00, 0x41, 0x7f | ACC_LOW, 0x41 | ACC_TOP, 0x00],
        'Ó': [0x3e, 0x41, 0x41 | ACC_LOW, 0x41 | ACC_TOP, 0x3e],
        'Ú': [0x3f, 0x40, 0x40 | ACC_LOW, 0x40 | ACC_TOP, 0x3f],
        'Ü': [0x3f, 0x40 | ACC_LOW, 0x40, 0x40 | ACC_LOW, 0x3f],
        'Ñ': [0x7f, 0x04 | ACC_LOW, 0x08 | ACC_LOW, 0x10 | ACC_LOW, 0x7f]
    };

    // Caracteres sin glifo propio: se dibujan sin el acento.
    var FALLBACK = {
        'à': 'a', 'â': 'a', 'ä': 'a', 'ã': 'a', 'å': 'a',
        'è': 'e', 'ê': 'e', 'ë': 'e',
        'ì': 'i', 'î': 'i', 'ï': 'i',
        'ò': 'o', 'ô': 'o', 'ö': 'o', 'õ': 'o',
        'ù': 'u', 'û': 'u',
        'ç': 'c', 'Ç': 'C',
        'À': 'A', 'Â': 'A', 'Ä': 'A', 'Ã': 'A',
        'È': 'E', 'Ê': 'E', 'Ë': 'E',
        'Ì': 'I', 'Î': 'I', 'Ï': 'I',
        'Ò': 'O', 'Ô': 'O', 'Ö': 'O', 'Õ': 'O',
        'Ù': 'U', 'Û': 'U',
        '–': '-', '—': '-', '‑': '-',
        '‘': "'", '’': "'", '“': '"', '”': '"',
        '•': '·', '°': 'o', 'º': 'o', 'ª': 'a',
        '\u00a0': ' ', '\t': ' ', '\n': ' ', '\r': ' '
    };

    var DEFAULTS = {
        color: '#f54e00',
        speed: 90,
        direction: 'left',
        gap: 0.24,
        separator: ' · '
    };

    function glyphFor(ch) {
        if (FONT.hasOwnProperty(ch)) {
            return FONT[ch];
        }
        if (FALLBACK.hasOwnProperty(ch)) {
            var alt = FALLBACK[ch];
            if (FONT.hasOwnProperty(alt)) {
                return FONT[alt];
            }
        }
        return FONT[' '];
    }

    function parseMessages(el) {
        var raw = el.getAttribute('data-messages');
        if (!raw) {
            return [];
        }
        try {
            var parsed = JSON.parse(raw);
            if (Object.prototype.toString.call(parsed) === '[object Array]') {
                return parsed.map(function (m) {
                    return String(m);
                }).filter(function (m) {
                    return m.length > 0;
                });
            }
        } catch (e) {}
        return [];
    }

    function hexToRgb(hex) {
        var h = String(hex || '').replace('#', '');
        if (h.length === 3) {
            h = h.charAt(0) + h.charAt(0) + h.charAt(1) + h.charAt(1) + h.charAt(2) + h.charAt(2);
        }
        if (h.length !== 6) {
            return { r: 245, g: 78, b: 0 };
        }
        return {
            r: parseInt(h.slice(0, 2), 16),
            g: parseInt(h.slice(2, 4), 16),
            b: parseInt(h.slice(4, 6), 16)
        };
    }

    function prefersReducedMotion() {
        return !!(global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function parseOptions(el, options) {
        var opts = options ? options : {};
        var out = {};
        var key;
        for (key in DEFAULTS) {
            if (DEFAULTS.hasOwnProperty(key)) {
                out[key] = DEFAULTS[key];
            }
        }
        for (key in opts) {
            if (opts.hasOwnProperty(key) && opts[key] !== undefined) {
                out[key] = opts[key];
            }
        }
        if (!out.messages || !out.messages.length) {
            out.messages = parseMessages(el);
        }
        if (el.getAttribute('data-color')) {
            out.color = el.getAttribute('data-color');
        }
        if (el.getAttribute('data-speed')) {
            var sp = parseFloat(el.getAttribute('data-speed'));
            if (!isNaN(sp) && sp > 0) {
                out.speed = sp;
            }
        }
        if (el.getAttribute('data-direction')) {
            out.direction = el.getAttribute('data-direction');
        }
        return out;
    }

    function LedBoard(el, options) {
        this.el = el;
        this.opts = parseOptions(el, options);

        this.messages = this.opts.messages && this.opts.messages.length
            ? this.opts.messages.slice()
            : ['AGENTDO'];

        this.rgb = hexToRgb(this.opts.color);
        this.reduced = prefersReducedMotion();
        this.paused = false;
        this.visible = true;
        this.offset = 0;
        this.lastTs = 0;
        this.raf = 0;
        this.rows = BOARD_ROWS;
        this.cell = 4;
        this.cols = 0;
        this.strip = null;
        this.stripCols = 0;
        this.canvas = null;
        this.ctx = null;

        this._onResize = this._onResize.bind(this);
        this._onEnter = this._onEnter.bind(this);
        this._onLeave = this._onLeave.bind(this);
        this._onVisibility = this._onVisibility.bind(this);
        this._tick = this._tick.bind(this);

        this._build();
        this._bind();
        this._layout();
        this._buildStrip();
        this._drawFrame();
        if (!this.reduced) {
            this._start();
        }
    }

    LedBoard.prototype._build = function () {
        this.el.classList.add('led-board', 'is-ready');
        this.el.setAttribute('role', 'presentation');

        var bezel = document.createElement('div');
        bezel.className = 'led-bezel';

        this.canvas = document.createElement('canvas');
        this.canvas.className = 'led-canvas';
        this.canvas.setAttribute('aria-hidden', 'true');

        bezel.appendChild(this.canvas);
        this.el.appendChild(bezel);
        this.ctx = this.canvas.getContext('2d');
    };

    LedBoard.prototype._bind = function () {
        this.el.addEventListener('mouseenter', this._onEnter);
        this.el.addEventListener('mouseleave', this._onLeave);
        document.addEventListener('visibilitychange', this._onVisibility);

        if (typeof ResizeObserver !== 'undefined') {
            this._ro = new ResizeObserver(this._onResize);
            this._ro.observe(this.el);
        } else {
            global.addEventListener('resize', this._onResize);
        }

        if (typeof IntersectionObserver !== 'undefined') {
            var self = this;
            this._io = new IntersectionObserver(function (entries) {
                var entry = entries[0];
                self.visible = !!(entry && entry.isIntersecting);
                if (self.visible && !self.reduced && !self.raf) {
                    self._start();
                }
            }, { threshold: 0.05 });
            this._io.observe(this.el);
        }
    };

    LedBoard.prototype._onEnter = function () {
        this.paused = true;
    };

    LedBoard.prototype._onLeave = function () {
        this.paused = false;
        this.lastTs = 0;
    };

    LedBoard.prototype._onVisibility = function () {
        if (document.hidden) {
            this.lastTs = 0;
        }
    };

    LedBoard.prototype._onResize = function () {
        var self = this;
        if (this._resizeTimer) {
            clearTimeout(this._resizeTimer);
        }
        this._resizeTimer = setTimeout(function () {
            self._layout();
            self._buildStrip();
            self._drawFrame();
        }, 80);
    };

    LedBoard.prototype._layout = function () {
        var rect = this.el.getBoundingClientRect();
        var cssW = Math.max(1, Math.floor(rect.width));
        var cssH = Math.max(1, Math.floor(rect.height));
        var dpr = Math.min(global.devicePixelRatio || 1, 2);

        this.cell = cssH / this.rows;
        this.cols = Math.max(GLYPH_COLS, Math.floor(cssW / this.cell));

        this.canvas.style.width = cssW + 'px';
        this.canvas.style.height = cssH + 'px';
        this.canvas.width = Math.round(cssW * dpr);
        this.canvas.height = Math.round(cssH * dpr);
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        this.cssW = cssW;
        this.cssH = cssH;
    };

    LedBoard.prototype._joinText = function () {
        if (this.reduced) {
            return this.messages[0];
        }
        return this.messages.join(this.opts.separator) + this.opts.separator;
    };

    LedBoard.prototype._buildStrip = function () {
        var chars = this._joinText().split('');
        var step = GLYPH_COLS + CHAR_GAP;
        var textCols = chars.length * step;
        var cols = this.reduced
            ? Math.max(textCols, this.cols)
            : Math.max(textCols, this.cols + step);
        var rows = this.rows;
        var strip = new Uint8Array(cols * rows);
        var start = this.reduced ? Math.max(0, Math.floor((cols - textCols) / 2)) : 0;
        var i;
        var c;
        var r;

        for (i = 0; i < chars.length; i++) {
            var glyph = glyphFor(chars[i]);
            var base = start + i * step;
            for (c = 0; c < GLYPH_COLS; c++) {
                var col = base + c;
                if (col >= cols) {
                    break;
                }
                var bits = glyph[c];
                for (r = 0; r < BODY_ROWS; r++) {
                    if (bits & (1 << r)) {
                        strip[(BODY_TOP + r) * cols + col] = 1;
                    }
                }
                if (bits & ACC_LOW) {
                    strip[1 * cols + col] = 1;
                }
                if (bits & ACC_TOP) {
                    strip[0 * cols + col] = 1;
                }
            }
        }

        this.strip = strip;
        this.stripCols = cols;
        this.offset = this.reduced ? 0 : this.offset % cols;
    };

    LedBoard.prototype._start = function () {
        if (this.raf) {
            return;
        }
        this.lastTs = 0;
        this.raf = global.requestAnimationFrame(this._tick);
    };

    LedBoard.prototype._tick = function (ts) {
        this.raf = 0;
        if (this.reduced) {
            this._drawFrame();
            return;
        }
        if (!document.hidden && this.visible && !this.paused) {
            if (this.lastTs) {
                var dt = Math.min(0.05, (ts - this.lastTs) / 1000);
                var dir = this.opts.direction === 'right' ? -1 : 1;
                this.offset += dir * (this.opts.speed / this.cell) * dt;
                var cols = this.stripCols;
                if (cols > 0) {
                    this.offset = ((this.offset % cols) + cols) % cols;
                }
            }
            this.lastTs = ts;
            this._drawFrame();
        } else {
            this.lastTs = 0;
        }
        this.raf = global.requestAnimationFrame(this._tick);
    };

    LedBoard.prototype._drawFrame = function () {
        var ctx = this.ctx;
        var cols = this.cols;
        var rows = this.rows;
        var cell = this.cell;
        var radius = cell * (0.5 - this.opts.gap * 0.5);
        if (radius < 0.6) {
            radius = 0.6;
        }
        var rgb = this.rgb;
        var strip = this.strip;
        var stripCols = this.stripCols || 1;
        // El desplazamiento avanza de LED en LED, como un cartel real.
        var baseCol = this.reduced ? 0 : Math.floor(this.offset);
        var on = 'rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ')';
        var off = 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',0.07)';
        var c;
        var r;

        ctx.clearRect(0, 0, this.cssW, this.cssH);
        ctx.fillStyle = '#050505';
        ctx.fillRect(0, 0, this.cssW, this.cssH);

        for (r = 0; r < rows; r++) {
            var y = r * cell + cell * 0.5;
            for (c = 0; c < cols; c++) {
                var srcCol = (baseCol + c) % stripCols;
                var lit = strip[r * stripCols + srcCol] === 1;
                ctx.beginPath();
                ctx.fillStyle = lit ? on : off;
                ctx.arc(c * cell + cell * 0.5, y, lit ? radius : radius * 0.8, 0, Math.PI * 2);
                ctx.fill();
            }
        }
    };

    LedBoard.prototype.destroy = function () {
        if (this.raf) {
            global.cancelAnimationFrame(this.raf);
            this.raf = 0;
        }
        this.el.removeEventListener('mouseenter', this._onEnter);
        this.el.removeEventListener('mouseleave', this._onLeave);
        document.removeEventListener('visibilitychange', this._onVisibility);
        if (this._ro) {
            this._ro.disconnect();
        } else {
            global.removeEventListener('resize', this._onResize);
        }
        if (this._io) {
            this._io.disconnect();
        }
        this.el.innerHTML = '';
    };

    function mount(el, options) {
        if (!el || el._ledBoard) {
            return el ? el._ledBoard : null;
        }
        var board = new LedBoard(el, options || {});
        el._ledBoard = board;
        return board;
    }

    function autoMount() {
        var nodes = document.querySelectorAll('.js-led-marquee');
        var i;
        for (i = 0; i < nodes.length; i++) {
            mount(nodes[i]);
        }
    }

    global.LedMarquee = {
        mount: mount,
        autoMount: autoMount
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoMount);
    } else {
        autoMount();
    }
})(window);
