# D1. Dominio (D1a y D1b): guía para revisar los diagramas contra el código

Repositorio: `gnvargas-dev/sistema-control-acceso`, commit `c6e87b4`. Los modelos están en `app/Models/`; el esquema, en `database/migrations/`.

## Convenciones del dibujo

- El dominio se dibuja en dos figuras que forman un único modelo: **D1a** (núcleo académico: 8 clases, 11 asociaciones) y **D1b** (evaluaciones, legajo y boletines: 5 clases propias, 9 asociaciones). En D1b, `Alumno`, `Materia`, `Usuario` y `Curso` aparecen solo con el nombre; sus miembros están en D1a.
- Se muestran los **atributos** que el modelo declara o que el código usa. No se dibujan: `id`, `created_at`, `updated_at`, `remember_token` ni las claves foráneas (las representan las asociaciones). `?` después del tipo significa que la columna admite `NULL`.
- Los **métodos** son los propios de cada clase. No se dibujan los heredados de `Model` ni `casts()`. Los métodos de relación de Eloquent (por ejemplo `Alumno::notas()`) se representan como asociaciones; la tabla 2 los lista. Los 7 scopes sí se dibujan como métodos.
- Los tipos de parámetros y de retorno son los que declara el código. En los tipos de retorno, `?` va antes del tipo, como en PHP (`?Persona`). `static` es el tipo de retorno declarado de `Nota::asignarCalificacion(Calificacion|int|float|string)` y de `Asistencia::marcar(EstadoAsistencia|string)`.
- **Línea simple** = asociación en la que ambos modelos tienen el método de relación. **Flecha** = solo la clase de origen tiene el método; la otra no puede navegar hacia ella.
- **Línea punteada** = dependencia (el método devuelve o usa esa clase). En D1a solo hay una: `Usuario ..> Persona`. Las dependencias hacia `Rol`, `Calificacion`, `EstadoAsistencia` y `TipoEvaluacion` no se dibujan en D1: constan como tipo en la firma o en el atributo y se detallan en D2 y D3.
- Todas las clases extienden `Illuminate\Database\Eloquent\Model`, salvo `Usuario`, que extiende `Authenticatable`. Esa clase base no se dibuja.

## Tabla 1. Clases (13)

| Figura | Clase | Archivo | Atributos | Métodos propios |
| --- | --- | --- | --- | --- |
| D1a | `Usuario` | `Usuario.php` | cedula, email?, password, nombre, rol, activo | `es`, `rolObjeto`, `perfil` |
| D1a | `Persona` (abstracta) | `Persona.php` | ATRIBUTOS_COMUNES (constante privada), activo | `__construct`, `identificador`*, `descripcion`*, `nombre`, `cedula`, `estaActivo`, `scopeActivos`, `scopeInactivos` |
| D1a | `Alumno` | `Alumno.php` | legajo?, fecha_nacimiento?, telefono?, foto? | `identificador`, `descripcion`, `nuevoLegajo` (estático), `legajoLibre` (estático) |
| D1a | `Profesor` | `Profesor.php` | (ninguno propio) | `identificador`, `descripcion` |
| D1a | `Curso` | `Curso.php` | nombre, anio | `booted` (estático, protegido), `nombre` (mutador, protegido) |
| D1a | `Materia` | `Materia.php` | nombre, activa | (ninguno propio) |
| D1a | `Nota` | `Nota.php` | PREFIJO_SEMESTRE (constante), valor, periodo, observacion? | `calificacion`, `asignarCalificacion`, `scopeDeSemestres`, `scopeParaPromedio` |
| D1a | `Asistencia` | `Asistencia.php` | fecha, estado, observacion? | `estadoAsistencia`, `marcar` |
| D1b | `Evaluacion` | `Evaluacion.php` | semestre, tipo, orden, nombre, puntos_max | `esParcial`, `scopeDelSemestre`, `scopeParciales`, `scopeExamenes` |
| D1b | `ResultadoEvaluacion` | `ResultadoEvaluacion.php` | puntos | (ninguno propio) |
| D1b | `HistorialAcademico` | `HistorialAcademico.php` | detalle, periodo | (ninguno propio) |
| D1b | `BoletinGenerado` | `BoletinGenerado.php` | periodo, archivo, descargas | (ninguno propio) |
| D1b | `DocumentoAlumno` | `DocumentoAlumno.php` | categoria, documento, fecha_entrega? | (ninguno propio) |

\* método abstracto.

`Usuario.email` no figura en `$fillable` del modelo, pero se dibuja porque el código la usa (`UsuarioService.php`, `UsuarioAdminController.php` y la vista `admin/usuarios.blade.php`).

## Tabla 2. Asociaciones (20: 11 en D1a y 9 en D1b) y su fundamento

Hay **5 asociaciones con flecha** (solo la clase de origen tiene el método de relación): `Evaluacion` → `Materia`, `ResultadoEvaluacion` → `Alumno`, `ResultadoEvaluacion` → `Usuario`, `BoletinGenerado` → `Curso` y `BoletinGenerado` → `Usuario`. Las 15 restantes son líneas simples.

| # | Figura | Asociación | Cardinalidad (en el dibujo) | Métodos de relación | Clave foránea en la base |
| --- | --- | --- | --- | --- | --- |
| 1 | D1a | `Usuario` – `Alumno` | 1 – 0..1 | `Usuario::alumno()`, `Persona::usuario()` | `alumnos.usuario_id` NOT NULL, único |
| 2 | D1a | `Usuario` – `Profesor` | 1 – 0..1 | `Usuario::profesor()`, `Persona::usuario()` | `profesores.usuario_id` NOT NULL, único |
| 3 | D1a | `Curso` – `Alumno` | 1 – * | `Curso::alumnos()`, `Alumno::curso()` | `alumnos.curso_id` NOT NULL |
| 4 | D1a | `Curso` – `Materia` | 1 – * (ver nota) | `Curso::materias()`, `Materia::curso()` | `materias.curso_id` **NULL** en el esquema |
| 5 | D1a | `Profesor` – `Materia` | * – * | `Profesor::materias()`, `Materia::profesores()` | tabla `profesor_materia` (clave compuesta) |
| 6 | D1a | `Alumno` – `Nota` | 1 – * | `Alumno::notas()`, `Nota::alumno()` | `notas.alumno_id` NOT NULL |
| 7 | D1a | `Materia` – `Nota` | 1 – * | `Materia::notas()`, `Nota::materia()` | `notas.materia_id` NOT NULL |
| 8 | D1a | `Usuario` – `Nota` | 1 – * | `Usuario::notasCargadas()`, `Nota::cargador()` | `notas.cargado_por` NOT NULL |
| 9 | D1a | `Alumno` – `Asistencia` | 1 – * | `Alumno::asistencias()`, `Asistencia::alumno()` | `asistencias.alumno_id` NOT NULL |
| 10 | D1a | `Materia` – `Asistencia` | 1 – * | `Materia::asistencias()`, `Asistencia::materia()` | `asistencias.materia_id` NOT NULL |
| 11 | D1a | `Usuario` – `Asistencia` | 1 – * | `Usuario::asistenciasCargadas()`, `Asistencia::cargador()` | `asistencias.cargado_por` NOT NULL |
| 12 | D1b | `Evaluacion` → `Materia` | * → 1 | `Evaluacion::materia()` (`Materia` no tiene el inverso) | `evaluaciones.materia_id` NOT NULL |
| 13 | D1b | `Evaluacion` – `ResultadoEvaluacion` | 1 – * | `Evaluacion::resultados()`, `ResultadoEvaluacion::evaluacion()` | `resultados_evaluacion.evaluacion_id` NOT NULL |
| 14 | D1b | `ResultadoEvaluacion` → `Alumno` | * → 1 | `ResultadoEvaluacion::alumno()` (`Alumno` no tiene el inverso) | `resultados_evaluacion.alumno_id` NOT NULL |
| 15 | D1b | `ResultadoEvaluacion` → `Usuario` | * → 1 | `ResultadoEvaluacion::cargador()` (sin inverso) | `resultados_evaluacion.cargado_por` NOT NULL |
| 16 | D1b | `Alumno` – `HistorialAcademico` | 1 – * | `Alumno::historialAcademico()`, `HistorialAcademico::alumno()` | `historial_academico.alumno_id` NOT NULL |
| 17 | D1b | `Alumno` – `DocumentoAlumno` | 1 – * | `Alumno::documentos()`, `DocumentoAlumno::alumno()` | `documentos_alumno.alumno_id` NOT NULL |
| 18 | D1b | `Alumno` – `BoletinGenerado` | 1 – * | `Alumno::boletinesGenerados()`, `BoletinGenerado::alumno()` | `boletines_generados.alumno_id` NOT NULL |
| 19 | D1b | `BoletinGenerado` → `Curso` | * → 0..1 | `BoletinGenerado::curso()` (sin inverso) | `boletines_generados.curso_id` **NULL** (SET NULL al borrar) |
| 20 | D1b | `BoletinGenerado` → `Usuario` | * → 0..1 | `BoletinGenerado::generadoPor()` (sin inverso) | `boletines_generados.generado_por` **NULL** (SET NULL al borrar) |

**Dependencia dibujada** (línea punteada, D1a): `Usuario::perfil()` devuelve `Persona`.

**Dependencias solo en texto** (se dibujan en D2 y D3): `Usuario::rolObjeto()` devuelve `Rol`; `Nota::calificacion()` devuelve `Calificacion`; `Asistencia::estadoAsistencia()` devuelve `EstadoAsistencia`; el atributo `Evaluacion.tipo` es un `TipoEvaluacion`.

## Lo que el código no permite comprobar (y cómo quedó en el dibujo)

1. **`Usuario` con alumno y profesor a la vez.** El esquema lo permite; el código solo crea el perfil que corresponde al rol (`UsuarioService::registrar`). Dibujado como 0..1 y 0..1, con nota.
2. **`Curso` – `Materia`.** El esquema admite `NULL` (migración `ajustar_materias_y_asistencias`), pero la web y la API exigen curso (`required` en `MateriasController` y en `StoreMateriaRequest`) y el código asume su existencia (por ejemplo `$materia->curso->alumnos()` en `AsistenciaService`). Dibujado como 1 – * y con nota.
3. **Sin composición.** El esquema borra en cascada desde `Alumno` hacia varias tablas, pero el código no declara esa dependencia de ciclo de vida. Todas son asociaciones simples.
4. **Tabla duplicada.** Existe `historial_academicos` (`anio`, `promedio`, `asistencia`) y ningún modelo la usa. El modelo `HistorialAcademico` usa `historial_academico`; solo esa se dibujó.
5. **Esquema de evaluaciones.** `mysql-schema.sql` no incluye `evaluaciones` ni `resultados_evaluacion`; sus claves salen de las migraciones `2026_11_02_000001` y `2026_11_02_000002`.

## Versión

Los diagramas se generaron sobre el commit `c6e87b4`. El commit `59a7449` solo modificó cinco comentarios y el texto del README (verificado con git diff) y no cambia código. Si se hacen commits posteriores, hay que comprobar que solo cambien comentarios o documentación; si cambia código (clases, firmas, relaciones o esquema), hay que revisar los diagramas y esta guía.
