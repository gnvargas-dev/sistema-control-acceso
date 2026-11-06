# D2. Roles y permisos (D2a y D2b): guía para revisar los diagramas contra el código

Repositorio: `gnvargas-dev/sistema-escolar`, commit `c6e87b4`. Los archivos están en `app/Roles/`, `app/Enums/`, `app/Policies/` y `app/Http/Middleware/`.

## Convenciones del dibujo

- D2 se dibuja en dos figuras que forman un único modelo con D1: **D2a** (jerarquía de roles) y **D2b** (control de acceso). `Usuario` se define en D1a y `Rol` en D2a; donde se repiten, aparecen solo con el nombre.
- Las firmas se muestran con los tipos y nombres de parámetro que declara el código. Los métodos **abstractos** van en *cursiva*. Los estáticos van subrayados. `+` es público y `-` privado.
- **Flecha con triángulo vacío** = herencia (`extends`). **Línea punteada con flecha** = dependencia (la clase de origen crea, devuelve o usa la de destino). La etiqueta dice cuál.
- Mermaid no tiene marca para `final` ni `readonly`: se muestran como estereotipo (`<<final>>`, `<<final readonly>>`).
- No se dibujan cardinalidades: no hay asociaciones entre atributos ni claves (ver "Cardinalidad de `Usuario` y `Rol`").
- `ItemMenu` muestra como atributos las propiedades públicas que declara el constructor (propiedades promovidas).

## Tabla 1. Clases (10)

| Figura | Clase | Archivo | Tipo | Miembros dibujados |
| --- | --- | --- | --- | --- |
| D2a | `Rol` | `app/Roles/Rol.php` | abstracta | 11 abstractos: `clave`, `rutaInicio`, `menu`, `perfilDe`, `veListadoDeAlumnos`, `veAlumnosDeMateria`, `veAlumno`, `cargaDatosDeMateria`, `esTitularDelRegistro`, `reglasDeRegistro`, `crearPerfil`. 5 concretos: `nombre`, `esUnoDe`, `desde` (estático), `tryDesde` (estático), `instanciar` (estático, privado) |
| D2a | `RolAdministrador` | `app/Roles/RolAdministrador.php` | final, extiende `Rol` | Los 11 métodos sobrescritos |
| D2a | `RolProfesor` | `app/Roles/RolProfesor.php` | final, extiende `Rol` | Los 11 métodos sobrescritos y `dictaLaMateria` (privado) |
| D2a | `RolAlumno` | `app/Roles/RolAlumno.php` | final, extiende `Rol` | Los 11 métodos sobrescritos |
| D2a | `ItemMenu` | `app/Roles/ItemMenu.php` | final readonly | `ruta`, `titulo`, `icono`, `subtitulo?`, `__construct` |
| D2a | `RolUsuario` | `app/Enums/RolUsuario.php` | enum con valores de texto | `Administrador = 'administrador'`, `Profesor = 'profesor'`, `Alumno = 'alumno'`, `etiqueta()` |
| D2b | `NotaPolicy` | `app/Policies/NotaPolicy.php` | clase | `view`, `create`, `update` |
| D2b | `AsistenciaPolicy` | `app/Policies/AsistenciaPolicy.php` | clase | `view`, `create`, `update` |
| D2b | `AlumnoPolicy` | `app/Policies/AlumnoPolicy.php` | clase | `viewAny`, `viewForMateria`, `view` |
| D2b | `CheckRole` | `app/Http/Middleware/CheckRole.php` | clase | `handle(Request, Closure, ...$roles): Response` |

`Usuario` (D1a) aparece solo con el nombre en D2a y D2b, y `Rol` solo con el nombre en D2b.

Comprobación de la sobrescritura: las tres hijas definen los 11 métodos que `Rol` declara abstractos, con las mismas firmas. `nombre()` y `esUnoDe()` no se sobrescriben; las hijas los heredan.

## Tabla 2. Relaciones (18) y su fundamento

| # | Figura | Relación | Tipo | Dónde está en el código |
| --- | --- | --- | --- | --- |
| 1–3 | D2a | `RolAdministrador`, `RolProfesor`, `RolAlumno` → `Rol` | herencia | `final class ... extends Rol` |
| 4–6 | D2a | `Rol` → cada hija | dependencia "crea" | `Rol::instanciar()`: `match` con `new RolAdministrador()`, `new RolProfesor()` y `new RolAlumno()` (líneas 98 a 100) |
| 7 | D2a | `Rol` → `RolUsuario` | dependencia | `Rol::clave(): RolUsuario`; `desde()` y `tryDesde()` reciben `RolUsuario\|string` |
| 8–10 | D2a | cada hija → `ItemMenu` | dependencia "crea" | `new ItemMenu(...)` en `menu()` de cada hija (7, 5 y 4 veces) |
| 11 | D2a | `Usuario` → `Rol` | dependencia "rolObjeto()" | `Usuario::rolObjeto(): Rol` (`Rol::desde((string) $this->rol)`); `Usuario::es()` usa `Rol::tryDesde()` |
| 12–14 | D2b | cada policy → `Usuario` | dependencia "recibe" | `Usuario $usuario` es el primer parámetro de todos sus métodos |
| 15–17 | D2b | cada policy → `Rol` | dependencia "delega vía `Usuario::rolObjeto()`" | Cada método llama a `$usuario->rolObjeto()->...` (ver tabla 3) |
| 18 | D2b | `Usuario` → `Rol` | dependencia "rolObjeto()" | igual que la 11 |

## Tabla 3. Delegación de las policies en `Rol`

| Policy | Método | Método de `Rol` al que delega |
| --- | --- | --- |
| `NotaPolicy` | `view` | `esTitularDelRegistro($usuario, $nota->alumno_id)` |
| `NotaPolicy` | `create` | `cargaDatosDeMateria($usuario, $datos['materia_id'])` |
| `NotaPolicy` | `update` | `cargaDatosDeMateria($usuario, $nota->materia_id)` |
| `AsistenciaPolicy` | `view` | `esTitularDelRegistro($usuario, $asistencia->alumno_id)` |
| `AsistenciaPolicy` | `create` | `cargaDatosDeMateria($usuario, $datos['materia_id'])` |
| `AsistenciaPolicy` | `update` | `cargaDatosDeMateria($usuario, $asistencia->materia_id)` |
| `AlumnoPolicy` | `viewAny` | `veListadoDeAlumnos()` |
| `AlumnoPolicy` | `viewForMateria` | `veAlumnosDeMateria($usuario, $materia)` |
| `AlumnoPolicy` | `view` | `veAlumno($usuario, $alumno)` |

Las policies se registran en `AppServiceProvider` con `Gate::policy(Nota::class, NotaPolicy::class)` (y las equivalentes para `Asistencia` y `Alumno`). Ese registro no se dibuja. `CheckRole` se registra con el alias `role` en `bootstrap/app.php`.

## Cardinalidad de `Usuario` y `Rol`

No se dibuja cardinalidad. `usuarios.rol` es una sola columna de texto, por lo que en cada momento un usuario tiene un único rol. Pero `Rol` no es una entidad guardada: `Usuario::rolObjeto()` crea un objeto nuevo en cada llamada y no lo guarda en ningún atributo. Por eso la relación es una dependencia y no una asociación, y las dependencias no llevan cardinalidad.

## Dependencias por firma no dibujadas

Aparecen en los tipos de parámetros o de retorno, pero no se dibujan para no saturar las figuras:

- `Persona`: retorno de `perfilDe()` (`Rol` y las tres hijas).
- `Alumno`: parámetro de `veAlumno()`; `RolAlumno::crearPerfil()` llama a `Alumno::nuevoLegajo()`; `AlumnoPolicy::view()`.
- `Materia`: parámetro de `veAlumnosDeMateria()`; `AlumnoPolicy::viewForMateria()`.
- `Nota` y `Asistencia`: parámetros de las policies correspondientes.
- `Collection` (de Laravel): parámetro de `crearPerfil()`.
- `Request`, `Closure` y `Response` (framework): firma de `CheckRole::handle()`.
- `DB` (fachada de Laravel): usada en `crearPerfil()` de `RolProfesor` y `RolAlumno`.

## Lo que el código no permite comprobar

1. **`CheckRole` → `Usuario`.** `CheckRole` no nombra a `Usuario` en ninguna firma: obtiene el usuario con `$request->user()` (tipo no declarado) y llama a `es(...$roles)`. Ese método solo existe en `Usuario`, pero la relación se deduce y no consta en el código. No se dibuja; D2b lo dice en una nota.
2. **Policies → `Rol`.** Ninguna policy nombra la clase `Rol`: llega a ella por el valor que devuelve `$usuario->rolObjeto()`. Se dibuja como dependencia con esa etiqueta.
3. **`Rol` → `ItemMenu`.** `Rol::menu()` declara `array` como tipo de retorno; `ItemMenu` aparece solo en el comentario (`list<ItemMenu>`). Por eso la dependencia se dibuja desde las hijas, que sí crean los objetos.
4. **`Usuario` → `RolUsuario`.** `Usuario` no usa el enum: `rol` es una cadena y no tiene cast a enum (`casts()` solo declara `password` y `activo`). `RolUsuario` se relaciona con `Rol` y no con `Usuario`.
5. **`final` y `readonly`.** Mermaid no tiene notación propia; van como estereotipo.

## Versión

Los diagramas se generaron sobre el commit `c6e87b4`. El commit `59a7449` solo modificó cinco comentarios y el texto del README (verificado con git diff) y no cambia código. Si se hacen commits posteriores, hay que comprobar que solo cambien comentarios o documentación; si cambia código (clases, firmas, relaciones o esquema), hay que revisar los diagramas y esta guía.
