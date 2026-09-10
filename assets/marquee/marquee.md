# Marquesina LED

Widget de cartel LED retro: una cinta de puntos que muestra texto corriendo hacia el costado, con la estética de los carteles de matriz de puntos (estación de tren, verdulería, ticker bursátil).

Esta carpeta es **autocontenida y portable**. El widget no sabe nada de AgentDo: recibe una lista de frases y las pinta. Para llevarlo a otro proyecto se copia la carpeta entera.

## Contenido de la carpeta

| Archivo | Rol |
|---|---|
| `marquee.js` | El widget completo. Fuente 5x7 + render + animación. Es lo único imprescindible. |
| `marquee.css` | Clases `.led-board`, `.led-bezel`, `.led-canvas`. |
| `marquee.md` | Este documento. |

## Dependencias

**Ninguna.** JavaScript vanilla y Canvas 2D.

No usa jQuery, ni Bootstrap, ni webfonts, ni build step, ni npm. La fuente es una tabla de bitmaps dentro del propio `marquee.js`, así que el cartel se ve idéntico aunque no cargue ninguna tipografía externa.

APIs del browser que usa, todas con fallback: `ResizeObserver`, `IntersectionObserver`, `matchMedia`, `requestAnimationFrame`.

## Llevarlo a otro proyecto

1. Copiar esta carpeta.
2. Enlazar los dos archivos:

```html
<link rel="stylesheet" href="ruta/marquee/marquee.css">
<script src="ruta/marquee/marquee.js"></script>
```

3. Poner el markup donde vaya el cartel.

No hay más pasos: el widget se auto-monta sobre cualquier `.js-led-marquee` del documento al cargar la página.

### Markup

```html
<div class="js-led-marquee" data-messages='["21 Sep 2026 · Fox Insumos","01 Dec 2026 · Cliente Demo"]'>
    <p class="visually-hidden">21 Sep 2026 · Fox Insumos · 01 Dec 2026 · Cliente Demo</p>
</div>
```

El `data-messages` es un array JSON. Desde PHP:

```php
$ledJson = json_encode($ledList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo '<div class="js-led-marquee" data-messages="' . e($ledJson) . '">';
```

Si se usan comillas dobles en el atributo (así lo hace AgentDo con `e()`), el JSON queda escapado como `&quot;` y el browser lo desescapa solo. Ambas formas funcionan.

El `<p class="visually-hidden">` es opcional pero recomendado: ver la sección de accesibilidad. Si el proyecto destino no usa Bootstrap, hace falta definir esa clase o usar la equivalente del framework.

## Opciones

Por atributo HTML:

| Atributo | Default | Qué hace |
|---|---|---|
| `data-messages` | — | Array JSON de frases. |
| `data-color` | `#f54e00` | Color del LED encendido (hex). |
| `data-speed` | `90` | Píxeles por segundo. |
| `data-direction` | `left` | `left` o `right`. |

Por JavaScript:

```js
LedMarquee.mount(document.querySelector('#cartel'), {
    messages: ['HAY TRABAJO', 'HAY CREATIVIDAD'],
    color: '#f54e00',
    speed: 90,
    direction: 'left',
    gap: 0.24,
    separator: ' · '
});
```

- `gap`: proporción de aire entre puntos (0 = puntos pegados, 1 = puntos invisibles).
- `separator`: qué se intercala entre frases al unirlas en una cinta continua.

Otros métodos: `LedMarquee.autoMount()` monta todo lo que haya en el DOM (se llama solo al cargar), y `board.destroy()` desmonta y limpia listeners.

## El CSS

El alto lo define el CSS; el widget calcula el tamaño de cada LED a partir de ese alto. Cambiar `height` en `.led-board` es la forma de agrandar o achicar el cartel.

`.led-board` y `.is-ready` las agrega el JS al montar. `.led-bezel` está pensada para ponerle marco de cartel físico (borde, gradiente, sombra interior) si se quiere; en AgentDo va sin marco.

El interior del cartel es negro fijo (`#050505`) y no sigue el tema claro/oscuro de la app: un LED sobre fondo blanco no se lee como LED.

## Cómo funciona

### Fuente de matriz 5x7

Este es el punto clave de nitidez. El widget **no** dibuja texto con una tipografía y después lo muestrea a la grilla: ese camino produce anti-aliasing y las letras salen borrosas.

En cambio, cada carácter es un patrón fijo de LEDs. La tabla `FONT` mapea carácter a 5 columnas, y cada columna es un entero donde cada bit es una fila:

```js
'A': [0x7e, 0x11, 0x11, 0x11, 0x7e],
```

- Bits 0 a 6: las 7 filas del cuerpo de la letra.
- Bit 7 (`ACC_LOW`) y bit 8 (`ACC_TOP`): dos filas extra reservadas arriba, para acentos de mayúsculas.

Las minúsculas acentuadas (`á`, `é`, `ñ`, `ü`) llevan el acento adentro de las 7 filas, porque su cuerpo es más bajo. Las mayúsculas ocupan las 7 filas completas, así que su acento usa las filas de arriba:

```js
'Á': [0x7e, 0x11, 0x11 | ACC_LOW, 0x11 | ACC_TOP, 0x7e],
```

Hay un mapa `FALLBACK` para caracteres sin glifo propio (`â` cae en `a`, `—` en `-`, comillas tipográficas en rectas). Lo que no está en ninguno de los dos se dibuja como espacio.

Para agregar un carácter alcanza con sumar una entrada a `FONT` con sus 5 columnas.

### Geometría

```
BOARD_ROWS = 10   -> 2 filas de acento + 7 de cuerpo + 1 de aire abajo
GLYPH_COLS = 5    -> ancho de cada carácter
CHAR_GAP   = 1    -> una columna de LEDs apagados entre letras
```

El alto del contenedor dividido 10 da el tamaño de celda. Con 72px de alto, cada LED ocupa 7.2px. El ancho disponible dividido la celda da cuántas columnas entran en pantalla.

### El strip

Al montar (y en cada resize) se arma un `Uint8Array` de `columnas * filas` con toda la cinta ya resuelta: un `1` por LED encendido. Es la única parte cara, y corre una sola vez.

Si el texto es más corto que el cartel, se rellena con columnas vacías hasta superar el ancho visible, para que al hacer loop no aparezca repetido en pantalla.

### La animación

Cada frame solo lee el strip con un desplazamiento y dibuja círculos. Dos detalles definen el look:

- **El avance es de LED en LED**, no continuo. Se usa `Math.floor(offset)`, así que el cartel salta de columna en columna como uno real. Con la velocidad por defecto son unos 12 saltos por segundo.
- **Los LEDs apagados se dibujan igual**, con opacidad 0.07. Esa grilla tenue de fondo es lo que hace que se lea como hardware y no como texto naranja flotando.

Encendido y apagado son binarios: no hay medios tonos ni halo. Cualquier degradado vuelve a ensuciar las letras.

### Pausas y accesibilidad

El cartel se detiene solo cuando:

- el mouse está encima (para poder leer),
- la pestaña está en segundo plano (`document.hidden`),
- el cartel está fuera del viewport (`IntersectionObserver`).

Con `prefers-reduced-motion: reduce` no hay scroll: se muestra la primera frase, centrada y fija.

El `<canvas>` es `aria-hidden`. El texto real va en el `<p class="visually-hidden">` de adentro del contenedor, así que un lector de pantalla lee el contenido completo sin ver el cartel.

## Ojo con la caché de assets

Si los cambios en `marquee.js` "no se aplican", casi seguro es caché del browser, no el widget.

En AgentDo pasó: `asset()` versionaba las URLs con una constante fija (`APP_VERSION = '1.2'`), así que `marquee.js?v=1.2` nunca cambiaba y el browser servía la copia vieja después de cada edición. La solución fue agregar el `filemtime` del archivo:

```php
function asset($path)
{
    $rel = ltrim((string) $path, '/');
    $ver = APP_VERSION;
    $file = ROOT_PATH . '/assets/' . $rel;
    if (is_file($file)) {
        $mtime = filemtime($file);
        if ($mtime !== false) {
            $ver .= '.' . $mtime;
        }
    }
    return url('assets/' . $rel) . '?v=' . rawurlencode($ver);
}
```

Queda `marquee.js?v=1.2.1789014584` y la URL cambia sola con cada guardado, en desarrollo y en producción. Al portar el widget conviene revisar que el versionado de assets del proyecto destino no sea una constante fija.

## La integración en AgentDo

Sirve como ejemplo de referencia. Tres puntos de contacto, nada más:

**1. Carga condicional** en `app/views/layouts/default.php`, solo en el panel:

```php
<?php if ($page === 'panel') : ?>
    <link rel="stylesheet" href="<?php echo e(asset('marquee/marquee.css')); ?>">
<?php endif; ?>
```

```php
<?php if ($page === 'panel') : ?>
    <script src="<?php echo e(asset('marquee/marquee.js')); ?>"></script>
<?php endif; ?>
```

**2. El markup** en `app/views/panel/index.php`, entre el encabezado y el listado de proyectos.

**3. Las frases** las arma `PanelController::buildLedMessages()` en `app/controllers/panel.php`. Un proyecto entra si cumple las tres condiciones:

1. Estado `abierto`.
2. Tiene `fecha_limite` cargada.
3. Esa fecha es de hoy en adelante.

La aprobación no filtra: entran igual los `aprobado` y los `requiere`.

Se ordenan por fecha ascendente (desempate por nombre de cliente) y cada frase queda como `fecha · nombre del cliente`:

```
21 Sep 2026 · Fox Insumos
01 Dec 2026 · Cliente Demo
```

Si no queda ninguno, el cartel muestra `SIN VENCIMIENTOS PRÓXIMOS`. El listado respeta el filtro por cliente del panel: si la URL trae `?cliente=fox`, la cinta habla solo de ese cliente.

## Limitaciones conocidas

- La fuente cubre ASCII imprimible más los acentos del español. Otros alfabetos (cirílico, griego, CJK) requieren agregar glifos a la tabla.
- Todos los caracteres miden 5 columnas: es monoespaciada por diseño, como los carteles reales.
- Un solo color por cartel. No hay texto multicolor ni por-frase.
- El `requestAnimationFrame` sigue corriendo cuando el cartel está fuera de pantalla, pero no dibuja nada.
