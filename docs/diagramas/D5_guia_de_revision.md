# Guía de revisión — D5 (Servicios y controladores, anexo)

Los diagramas se generaron sobre el commit `c6e87b4`. El commit `59a7449` solo modificó cinco comentarios y el texto del README (verificado con git diff) y no cambia código. Si se hacen commits posteriores, hay que comprobar que solo cambien comentarios o documentación; si cambia código, hay que revisar los diagramas.

Figuras: **D5a** servicios (`app/Services`), **D5b** controladores web, **D5c** controladores de la API. Indicadores: I-03 / I-16 (anexo del UML).

## 1. Convenciones
- Solo nombres de clase: los miembros no se repiten (están en las tablas de esta guía).
- `<<final>>`: la clase está declarada `final` (servicios).
- Flecha sólida con `1`: atributo `private readonly` de tipo no nulo, recibido por constructor.
- Flecha punteada con etiqueta: la clase aparece como tipo de un parámetro de método (inyección por método), no como atributo.
- No se dibujan: herencia de `Controller` (ver nota y tabla 3), Requests, modelos, fachadas ni consultas (ver tablas 5 y 6).
- La nota "todos los controladores extienden Controller, una clase abstracta vacía" sustituye las 20 líneas de herencia.

## 2. Cantidad de controladores (exacta)
| Grupo | Cantidad | Ubicación |
|---|---|---|
| Web | 10 | `Admin/` (6): Alumnos, Boletines, Legajos, Materias, Panel, Reportes; raíz (4): AlumnoController, ProfesorController, SesionController, UsuarioAdminController |
| API | 10 | raíz: Asistencia, Auth, Boletin, HistorialAcademico, Legajo, Materia, Nota, RegistroAlumno, Reporte, Usuario |
| Total concretos | 20 | todos `extends Controller` |
| Base | 1 | `Controller` (abstracta, sin miembros) |
| Archivos | 21 | 20 + `Controller.php` |

## 3. Tabla 1 — Clases (generada del código)
Servicios (14, todos `final`; públicos / privados-protegidos, sin constructor):
| Clase | Pub. | Priv. | Métodos públicos |
|---|---|---|---|
| AlumnoService | 5 | 0 | listado, estadisticas, actualizar, eliminar, cursos |
| AsistenciaMetricasService | 3 | 4 | deAlumno, deProfesor, calcular |
| AsistenciaService | 7 | 0 | pantallaDeCarga, guardarLote, historial, deAlumno, porcentajeDe, crear, actualizar |
| BoletinService | 6 | 2 | pantalla, alumnosParaBoletin, generar, existeArchivo, descargar, ver |
| DocenciaService | 5 | 0 | materiasDe, materiasConAlumnos, materiaAutorizada, alumnoDeMateria, filasDeAlumnos |
| EstadisticasService | 3 | 0 | resumenInstitucional, promedioGeneral, asistenciaGeneral |
| EvaluacionService | 11 | 9 | periodoDe, evaluacionesDe, resultadoDe, configurarSemestre, guardarParcial, guardarExamen, quitarEvaluacion, registrarResultados, registrarResultado, pantallaDeCarga, semestresDelAlumno |
| LegajoService | 4 | 1 | pantalla, documentosDe, guardarDocumentos, totalDocumentos |
| MateriaService | 9 | 0 | datosDeIndice, guardarDesdePanel, eliminar, activas, conDetalle, crear, actualizar, darDeBaja, alumnosActivos |
| NotaService | 5 | 0 | registrar, deAlumno, promedioDe, crear, actualizar |
| PanelAlumnoService | 5 | 0 | alumnoDe, inicio, asistencia, registrosDeHistorial, historialPorPeriodo |
| PanelProfesorService | 1 | 1 | inicio |
| ReporteService | 13 | 6 | pantalla, titulo, contextoDePdf, nombreDeArchivo, filtros, resumen, estadisticasCurso, promedioPorCurso, distribucionPromedios, cuadroDeHonor, alertaRiesgo, notasPorMateria, asistenciasPorMateria |
| UsuarioService | 9 | 1 | datosDeIndice, reglasExtraPara, registrar, eliminar, autenticar, paginado, crearCuenta, actualizarCuenta, darDeBaja |

Controladores web (10):
| Clase | Pub. | Priv. | Métodos públicos |
|---|---|---|---|
| Admin\AlumnosController | 3 | 0 | index, actualizar, eliminar |
| Admin\BoletinesController | 4 | 0 | index, generar, descargar, ver |
| Admin\LegajosController | 3 | 0 | index, documentos, guardarDocumentos |
| Admin\MateriasController | 4 | 1 | index, guardar, actualizar, eliminar |
| Admin\PanelController | 1 | 0 | inicio |
| Admin\ReportesController | 2 | 1 | index, exportar |
| AlumnoController | 4 | 1 | inicio, notas, historial, asistencia |
| ProfesorController | 9 | 1 | inicio, cargarNotas, guardarEvaluaciones, guardarResultados, guardarNota, cargarAsistencias, guardarAsistencias, historialAsistencias, misAlumnos |
| SesionController | 3 | 0 | formulario, iniciar, cerrar |
| UsuarioAdminController | 3 | 0 | index, store, destroy |

Controladores de la API (10):
| Clase | Pub. | Priv. | Métodos públicos |
|---|---|---|---|
| AsistenciaController | 4 | 0 | index, porcentaje, store, update |
| AuthController | 3 | 0 | login, logout, me |
| BoletinController | 1 | 0 | generar |
| HistorialAcademicoController | 1 | 0 | index |
| LegajoController | 2 | 0 | show, update |
| MateriaController | 5 | 0 | index, store, show, update, destroy |
| NotaController | 4 | 0 | index, promedio, store, update |
| RegistroAlumnoController | 1 | 0 | porMateria |
| ReporteController | 2 | 0 | notas, asistencias |
| UsuarioController | 5 | 0 | index, store, show, update, destroy |

Base: `Controller` — abstracta, 0 miembros.

## 4. Tabla 2 — Relaciones dibujadas (30)
**D5a (5, constructor, cardinalidad 1):** AsistenciaService→DocenciaService (`docencia`); NotaService→DocenciaService (`docencia`); PanelProfesorService→DocenciaService (`docencia`); EvaluacionService→DocenciaService (`docencia`); EvaluacionService→NotaService (`notas`).

**D5b (17 = 15 asociaciones + 2 punteadas):**
AlumnosController→AlumnoService; BoletinesController→BoletinService; LegajosController→LegajoService; MateriasController→MateriaService; PanelController→EstadisticasService; ReportesController→ReporteService y →GeneradorReporte; AlumnoController→PanelAlumnoService y →EvaluacionService; ProfesorController→DocenciaService, PanelProfesorService, NotaService, AsistenciaService, EvaluacionService; UsuarioAdminController→UsuarioService.
Punteadas (parámetro de método): AlumnoController `asistencia()` ⇢ AsistenciaMetricasService; ProfesorController `historialAsistencias()` ⇢ AsistenciaMetricasService.
Sin relación dibujada: SesionController (no recibe servicios; usa `Auth` y `Rol::tryDesde()`).

**D5c (8, constructor):** AsistenciaController→AsistenciaService; AuthController→UsuarioService; UsuarioController→UsuarioService; HistorialAcademicoController→PanelAlumnoService; MateriaController→MateriaService; RegistroAlumnoController→MateriaService; NotaController→NotaService; ReporteController→ReporteService.
Sin relación dibujada: BoletinController y LegajoController (no reciben servicios por constructor).

Total: 5 + 17 + 8 = 30. BoletinService→GeneradorBoletin y ReporteService/GeneradorReporte se muestran en D4 (el constructor de BoletinService recibe `GeneradorBoletin`; ReportesController recibe `GeneradorReporte` y se dibuja en D5b).

## 5. Tabla 3 — Herencia de Controller (texto, no dibujada)
Los 20 controladores concretos (10 web + 10 API) declaran `extends Controller`. `Controller` es abstracta y no tiene métodos ni atributos. Se verificó con la lectura de cada archivo.

## 6. Tabla 4 — Web vs API
| | Web | API |
|---|---|---|
| Cantidad | 10 | 10 |
| Rutas | `routes/web.php` | `routes/api.php` (Sanctum; grupos `role:alumno`, `role:profesor`, `role:profesor,administrador`, `role:administrador`) |
| Uso de Requests | ninguno (reciben `Illuminate\Http\Request`) | AsistenciaController, AuthController, MateriaController, NotaController, UsuarioController |

## 7. Tabla 5 — Requests, modelos y clases de Laravel (solo texto)
Requests (5), usados como tipo de parámetro: LoginRequest (AuthController::login), StoreAsistenciaRequest (AsistenciaController::store/update), StoreMateriaRequest (MateriaController::store/update), StoreNotaRequest (NotaController::store/update), StoreUsuarioRequest (UsuarioController::store/update).

Modelos como parámetros enlazados por ruta: Materia (MateriaController show/update/destroy; Admin\MateriasController actualizar/eliminar; RegistroAlumnoController::porMateria), Alumno (BoletinController::generar; LegajoController show/update; Admin\AlumnosController actualizar/eliminar; Admin\LegajosController documentos/guardarDocumentos), Usuario (UsuarioController show/update/destroy; UsuarioAdminController::destroy), Asistencia (AsistenciaController::update), Nota (NotaController::update), BoletinGenerado (Admin\BoletinesController descargar/ver).

Clases y fachadas de Laravel / terceros referenciadas: `Auth` (SesionController, AlumnoController, ProfesorController), `Rol::tryDesde()` (SesionController), `Pdf` de DomPDF (API BoletinController), `Illuminate\Http\Request`. No se dibujan, según la regla de D5.

## 8. Observación (hallazgo): dos caminos para generar el boletín PDF
- **API** (`BoletinController::generar(Alumno $alumno)`, ruta `GET /boletines/{alumno}/pdf`, grupo `role:administrador`): carga relaciones y llama `Pdf::loadView('boletines.boletin', ['alumno' => $alumno])`; descarga `boletin-alumno-{id}.pdf`. No usa `BoletinService` ni `GeneradorBoletin`, no guarda archivo ni crea `BoletinGenerado`. Vista: `resources/views/boletines/boletin.blade.php`.
- **Web, administrador** (`Admin\BoletinesController` → `BoletinService` → `GeneradorBoletin`): vista `admin.boletin-pdf`, guarda el archivo en `storage` (`boletines/`) y crea un registro `BoletinGenerado`.
Son dos caminos y dos plantillas distintas. Consecuencia: el mantenimiento se duplica y el PDF de ambos caminos puede diferir. Se registra como limitación **L-05** (sección 1.3.2) y como mejora futura en las conclusiones (unificar en `BoletinService`/`GeneradorBoletin`).

## 9. Lo que el código no permite comprobar
- Qué controladores se usan realmente en producción: solo consta lo que declaran `routes/web.php` y `routes/api.php`.
- Si los dos PDF producen contenido equivalente: no se compararon las dos vistas Blade campo por campo.
- Que las dependencias por fachada o consulta a modelos estén ausentes de las clases: no se dibujan por regla, pero existen.
- Las relaciones deducidas del uso (no del constructor o de la firma de método) no figuran en D5.

## 10. Cumplimiento
| Indicador | Qué pide la guía | Cómo lo cubre | Qué falta / riesgo de valer 0 |
|---|---|---|---|
| I-03 / I-16 | UML con clases reales, coherente con el código | D5a–c con nombres, tipos y relaciones verificados contra c6e87b4; tablas 1–2 | Que Gonzalo confirme y que cada captura/figura lleve versión y fecha |
