# AgentDo

Cuaderno operativo multi-tenant para proyectos de clientes: diario de desarrollo, margen de anotaciones y adjuntos. Hecho para humanos y para agentes. Dominio: `agentdo.site`.

Base local: `http://localhost/agentdo/` (XAMPP). Tenant de prueba usado en desarrollo: empresa `hessy`, operador `fer` (rw). Kits en claro solo en la máquina del operador (`empresa.txt` / `operador.txt`); no van al repo.

## Entidades

- **Empresa** — tenant raíz. Código `[a-z0-9]`, 3–32. Usuario reservado `admin`.
- **Operador** — pertenece a una empresa. Usuario `[a-z0-9]`, 3–32. Permiso `rw` o `ro` (v1 a nivel empresa).
- **Cliente = instancia** — carpeta en disco. Código `[a-z0-9]`, 3–32.
- **Proyecto** — vive dentro del cliente. Código `[a-z0-9]`, 3–32.
- **Diario** — `diario.txt` en disco (texto plano, autoguardado).
- **Anotación (margen)** — texto, enlace o adjunto.

## Stack

| Capa | Tecnología |
|------|------------|
| Backend | PHP 7.3.33+ (sin sintaxis 7.4+) |
| Persistencia | SQLite por tenant vía **PDO** (`sqlite:`) + archivos en `webfiles/` |
| Frontend | Bootstrap 5.3 (CDN), Bootstrap Icons 1.11 (CDN), jQuery 3.7 |
| Servidor | Apache (XAMPP) + `mod_rewrite` |

**PDO, no `SQLite3`:** en este XAMPP la extensión `SQLite3` no está. Toda DB pasa por `Sqlite::open()` → `new PDO('sqlite:…')`. No reintroducir `new SQLite3()`.

## Disco: todo en minúsculas

Archivos y carpetas **nunca** llevan mayúsculas. Excepción de tooling: `AGENTS.md`.

Códigos de entidad: `[a-z0-9]`. Passhash (credencial): `[a-zA-Z0-9]`, no va en el path.

Nombres de clase PHP pueden ser PascalCase en el código; el archivo es `home.php`, no `HomeController.php`.

Si un path propuesto tiene mayúsculas, se rechaza. Los adjuntos se normalizan (`Informe.PDF` → `informe.pdf`).

`Storage::assertLowercasePath()` compara solo la parte **relativa** a `ROOT_PATH`. En Windows hay que recortar `C:` del path absoluto: si no, `C:\…` dispara `path_mayusculas` y el alta de empresa no avanza.

## Árbol (código)

```
agentdo/
  index.php                 # front controller: bootstrap + mapa de rutas
  .htaccess                 # rewrite a index.php; deny a webfiles/ y lock/
  AGENTS.md                 # este contrato (única excepción de mayúsculas)

  app/
    bootstrap.php           # constantes, sesión, autoload explícito, url()/e()/format_dt()/share_url()
    core/
      request.php           # path, método, input (POST + JSON)
      router.php            # GET/POST; params {nombre} = [a-z0-9]+; literales con preg_quote
      controller.php        # view(), redirect(), json(), requireCsrf()
      view.php              # View::render() → layout default + sección
      sqlite.php            # PDO sqlite + PRAGMA foreign_keys / busy_timeout
      lock.php              # flock() en lock/{empresa}.lock para escrituras
      csrf.php              # token de formulario humano
      auth.php              # sesión, login, requireLogin/Write, gate admin de empresa
    controllers/
      home.php              # portada /
      auth.php              # /ingresar, wizard /crear-empresa (3 pasos + kits), /salir
      panel.php             # hub /panel + workspace diario/margen
      cliente.php           # CRUD clientes (nombre editable; código inmutable)
      proyecto.php          # alta de proyecto
      empresa.php           # admin de empresa (gate passhash admin + operadores)
      share.php             # GET /a/{hash}.{ext} enlace público de adjunto
    models/
      empresa.php           # registry global + empresa.sqlite (meta, lock login, schema)
      operador.php          # find/list/create/update/rotatePasshash/delete
      cliente.php           # listado, alta, editar nombre, borrar (solo SQLite)
      proyecto.php          # listado, alta, updated_at
      margen.php            # notas/enlaces/adjuntos (metadata SQLite)
      share.php             # índice público webfiles/_shares.sqlite
    services/
      passhash.php          # generate 40 chars, store bcrypt, verify
      storage.php           # paths, códigos, minúsculas, dirs de diario/margen
    views/
      layouts/default.php   # header + body + footer
      partials/
        header.php          # logo, Panel, Administrar Empresa, usuario, Salir, tema
        footer.php
        logo.php
      home/index.php, 404.php
      auth/
        ingresar.php
        crear_empresa.php, crear_admin.php, crear_operador.php, crear_operador_hash.php
      panel/
        index.php                    # hub: proyectos + CTAs
        clientes_index.php           # listado clientes por fecha de alta
        cliente_nuevo.php, cliente_editar.php
        proyecto_nuevo.php
        workspace.php                # margen izq + diario der
        empresa_desbloquear.php      # pide passhash de admin (empresa.txt)
        empresa_admin.php            # ficha empresa + tabla operadores
        empresa_operador_nuevo.php
        empresa_operador_editar.php
        empresa_passhash.php         # muestra hash nuevo una vez (rotar o alta)

  assets/css/app.css        # tema, workspace, tablas admin
  assets/js/app.js          # tema, copiar, toggle secret, autosave diario, dropzone

  webfiles/                 # Apache deny — SQLite + diarios + adjuntos
  lock/                     # Apache deny — flock por empresa
```

Autoload: mapa **explícito** en `bootstrap.php`. Si creás un controller/modelo/servicio nuevo, hay que registrarlo ahí. No hay PSR-4.

Rutas `/panel/empresa…` van **antes** de `/panel/{cliente}/{proyecto}` en `index.php`. Si no, `empresa` se interpreta como cliente.

## Disco (datos)

```
webfiles/_registry.sqlite              # empresas (codigo, created_at)
webfiles/_shares.sqlite                # índice de enlaces públicos /a/{hash}.{ext}
webfiles/{empresa}/empresa.sqlite      # empresa, operadores, clientes, proyectos, margen
webfiles/{empresa}/{cliente}/{proyecto}/diario.txt
webfiles/{empresa}/{cliente}/{proyecto}/margen/{hash}/   # hash 40 [a-z0-9]
lock/{empresa}.lock                    # flock de escritura (no es el lock de login)
```

`{cliente}` en disco = instancia. En URLs humanas se usa `/panel/{cliente}/{proyecto}`.

## Vistas

Front controller → router → controller → `View::render()`.

El layout `app/views/layouts/default.php` pinta header + body + footer. Cada acción solo entrega una sección y un título. No devolver HTML suelto desde un controller.

## Auth (passhash)

1. El sistema **genera y entrega** un passhash de 40 chars `[a-zA-Z0-9]`. El usuario lo guarda (copia + kit `.txt`). No elige clave.
2. En SQLite se guarda `password_hash()` (bcrypt), nunca el valor en claro.
3. Login humano: `POST /ingresar` con `empresa` + `usuario` + **ese passhash** → sesión PHP. Sin passhash no hay sesión.
4. Agentes: `Authorization: Bearer {empresa}:{usuario}:{passhash}` o `X-AgentDo-Token`. *(contrato; API aún no implementada.)*
5. Usuario `admin` está reservado (superadmin de la empresa). Su passhash es el **passhash de empresa** (kit `empresa.txt`).

Hay dos passhash distintos: el del operador con el que se entra al panel, y el de `admin` para administrar la empresa. No se intercambian.

### Lock de login (v1)

Constante `LOGIN_FAIL_MAX = 10`.

Se suman **todos** los fallos de **todos** los operadores (incluido `admin`), histórico, sin ventana de tiempo. Un login correcto **no** resetea el contador. Al 10º fallo: `locked=1` y nadie entra (`error: empresa_locked`).

Desbloquear administración de empresa con passhash inválido **también** suma al contador.

Desbloqueo v1 (disco, con la empresa detenida):

1. Abrir `webfiles/{empresa}/empresa.sqlite`.
2. `UPDATE empresa SET locked = 0, login_fail_count = 0;`
3. No hay desbloqueo por login: el admin también quedó afuera.

## Persistencia

- SQLite estructurada (operadores, clientes, proyectos, metadata de margen).
- Diario y adjuntos = archivos. `diario.txt` es la fuente de verdad; no meter el diario solo en SQLite.
- Escrituras: `flock()` en `lock/{empresa}.lock`.
- `webfiles/` y `lock/` no se sirven por Apache.
- Borrar cliente: quita filas SQLite (cliente, proyectos, margen, shares). **No borra archivos en disco.**

## UI humana (implementada)

Wizard crear empresa (sesión `$_SESSION['wizard']`):

1. Código de empresa.
2. Passhash de `admin` + kit `empresa.txt`.
3. Primer operador + passhash + kit `operador.txt` → entra al panel.

Panel:

- `/panel` — listado de proyectos (última actualización) + crear cliente/proyecto.
- `/panel/clientes` — listado por fecha de alta; editar solo nombre; borrar con confirmación.
- `/panel/{cliente}/{proyecto}` — workspace: margen izquierda (nota, enlace, dropzone) + diario derecha. Autosave debounce 1.2s. Chips Escribiendo / Guardando / Guardado.
- Adjuntos: carpeta `margen/{hash}/`. Enlace público `GET /a/{hash}.{ext}` (ej. `/a/abc….pdf`). El punto de la extensión es literal: el router usa `preg_quote` en los trozos fijos; sin eso `{hash}.{ext}` no matchea.
- JSON público del proyecto: botón **JSON** al lado del título en el workspace. Enlace `GET /j/{hash}.json` (hash 40 `[a-z0-9]`, sin login). Índice en `webfiles/_shares.sqlite` tabla `json_shares`. El documento se arma en vivo (diario + margen + URLs de adjuntos). Borrar cliente también borra esas filas.

Administrar empresa (`/panel/empresa`):

1. Header: botón **Administrar Empresa**.
2. Gate: pide passhash de **admin** (`empresa.txt`). Si ok, `$_SESSION['empresa_admin'][{empresa}]`. Logout o “Cerrar administración” lo limpian.
3. Tras desbloquear: ficha (código, `created_at`) + tabla de operadores.
4. Operadores: añadir, editar (nombre/email/teléfono/permiso; usuario inmutable; `admin` no cambia permiso ni se borra), borrar (no a vos mismo), rotar passhash.
5. Rotar o crear operador → `/panel/empresa/passhash`: hash en claro una vez + copiar + kit. Hasta confirmar “Ya guardé este passhash” no se vuelve al admin.

`ro` ve el panel pero no escribe diario/margen ni crea clientes/proyectos.

## API (v1, para agentes) — pendiente

JSON, sin HTML. `GET /api/v1/help` lista rutas. **Aún no hay controllers `/api/v1/*`.** El contrato queda:

Errores: `{ "ok": false, "error": "auth_invalid" | "empresa_locked" | ... }`.

```
POST /api/v1/empresas
POST /api/v1/auth
GET|POST /api/v1/clientes
GET|POST /api/v1/clientes/{instancia}/proyectos
GET|PUT  /api/v1/clientes/{instancia}/proyectos/{proyecto}/diario
GET|POST /api/v1/clientes/{instancia}/proyectos/{proyecto}/margen
```

Adjuntos del margen: carpeta `margen/{hash}/` (hash 40 `[a-z0-9]`). Enlace público sin sesión: `GET /a/{hash}.{ext}`. Índice en `webfiles/_shares.sqlite`.

Ejemplo:

```bash
curl -s -X POST "$BASE/api/v1/auth" \
  -H "Authorization: Bearer acme:admin:ELPASSHASH" \
  -H "Content-Type: application/json"
```

## Qué no hacer

- Sintaxis exclusiva de PHP 7.4+ (typed properties, arrow functions, `?->`, named args, `match`, union types, constructor promotion).
- MySQL/Postgres u otra DB externa. Composer pesado. Frameworks que pidan PHP 8.
- Carpetas o archivos de proyecto con mayúsculas.
- Servir `webfiles/` o `lock/` en público.
- Guardar el passhash en claro. Dejar que el usuario “cree su contraseña”.
- Meter el diario solo en SQLite: el archivo `diario.txt` es la fuente de verdad.
- Inventar un login alternativo (magic link, OAuth, captcha) para “recuperar” sesión.
- Volver a `SQLite3` nativo; usar PDO.
- Validar paths en Windows sin recortar la letra de unidad.

## PHP 7.3 — permitido

`password_hash` / `password_verify`, nullable types en firmas, `void`, PDO, JSON, sesiones, `flock`.

## Notas para retomar

- **Hecho:** portada, login, wizard de empresa, panel, workspace (diario + margen + adjuntos públicos), CRUD clientes, admin de empresa con gate de passhash admin y CRUD de operadores (alta/editar/borrar/rotar).
- **Pendiente más claro:** API JSON v1 para agentes (auth Bearer, mismas entidades). Después: más pulido de `ro`, desbloqueo de empresa sin tocar SQLite a mano, y lo que pida el uso real.
- Al añadir clase: registrarla en el mapa de `bootstrap.php` y la ruta **estática** antes de las paramétricas en `index.php`.
- Kits de rotación/alta viven en `$_SESSION['rotate_flash']` hasta que el usuario confirma. No borrar ese flash al pintar la pantalla (hace falta para descargar el kit).
- Fallos de login y de desbloqueo de admin comparten el mismo contador. Probar mal el passhash de empresa puede lockear el tenant de prueba.
- PHP 7.3: closures sí; arrow functions / typed properties / `?->` no.
