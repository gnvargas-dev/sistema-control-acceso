# D4. Informes y generadores de PDF (D4a y D4b): guía para revisar los diagramas contra el código

Repositorio: `gnvargas-dev/sistema-escolar`, commit `c6e87b4`. Los archivos están en `app/Informes/`, `app/Documentos/` y `app/Services/`.

## Convenciones del dibujo

- D4 se dibuja en dos figuras que forman un único modelo con D1 a D3: **D4a** (`Informe`, sus cuatro hijas y `ReporteService`) y **D4b** (`GeneradorDocumento`, `GeneradorPdf`, sus dos hijas, `BoletinService` y la clase `PDF` de la librería DomPDF).
- Las firmas, la visibilidad y los modificadores son los del código: `#` es protegido, `-` es privado y `+` es público. `final` aparece como estereotipo en las clases y como palabra antes del nombre en el método `final generar()` (Mermaid no tiene notación propia). `readonly` va antes del tipo del atributo.
- Los métodos abstractos van en *cursiva*.
- **Flecha con triángulo vacío** = herencia (`extends`); **línea punteada con triángulo vacío** = implementación de interfaz. **Línea punteada con flecha** = dependencia (crea, devuelve o usa); la etiqueta dice el método. **Flecha continua** = asociación por atributo, con la cardinalidad del lado del destino.
- `PDF` es la clase `Barryvdh\DomPDF\PDF` de la librería DomPDF; se dibuja solo con el nombre y el estereotipo `<<DomPDF>>`.
- `TITULOS` es una constante con un arreglo de 4 textos; sus valores exactos están en la tabla 3.

## Tabla 1. Clases (12 del sistema y 1 externa)

| Figura | Clase | Archivo | Tipo | Miembros dibujados |
| --- | --- | --- | --- | --- |
| D4a | `Informe` | `app/Informes/Informe.php` | abstracta | Atributo `reportes` (`protected readonly ReporteService`, promovido en el constructor); `__construct(ReporteService)`; abstractos `tipo(): int` y `datos(array): array`; concreto `titulo(): string` |
| D4a | `InformeResumenEjecutivo` | `app/Informes/InformeResumenEjecutivo.php` | final, extiende `Informe` | Sobrescribe `tipo()` (devuelve 1) y `datos()` |
| D4a | `InformeComparativoCursos` | `app/Informes/InformeComparativoCursos.php` | final, extiende `Informe` | Sobrescribe `tipo()` (2) y `datos()` |
| D4a | `InformeCuadroHonor` | `app/Informes/InformeCuadroHonor.php` | final, extiende `Informe` | Sobrescribe `tipo()` (3) y `datos()` |
| D4a | `InformeAlertaRiesgo` | `app/Informes/InformeAlertaRiesgo.php` | final, extiende `Informe` | Sobrescribe `tipo()` (4) y `datos()` |
| D4a | `ReporteService` | `app/Services/ReporteService.php` | final | Constante `TITULOS`; **19 métodos**: 13 públicos (`pantalla`, `titulo`, `contextoDePdf`, `nombreDeArchivo`, `filtros`, `resumen`, `estadisticasCurso`, `promedioPorCurso`, `distribucionPromedios`, `cuadroDeHonor`, `alertaRiesgo`, `notasPorMateria`, `asistenciasPorMateria`) y 6 privados (`informe`, `variacion`, `promedioEnPeriodo`, `asistenciaEnPeriodo`, `promedioAlumnoEnPeriodo`, `asistenciaAlumnoEnPeriodo`). No declara constructor |
| D4b | `GeneradorDocumento` | `app/Documentos/GeneradorDocumento.php` | interfaz | Abstractos `vista(): string` y `generar(array): PDF` |
| D4b | `GeneradorPdf` | `app/Documentos/GeneradorPdf.php` | abstracta, implementa `GeneradorDocumento` | `final public generar(array): PDF` y `protected configurar(PDF): void` (cuerpo vacío). No declara `vista()` |
| D4b | `GeneradorBoletin` | `app/Documentos/GeneradorBoletin.php` | final, extiende `GeneradorPdf` | Sobrescribe `vista()` |
| D4b | `GeneradorReporte` | `app/Documentos/GeneradorReporte.php` | final, extiende `GeneradorPdf` | Sobrescribe `vista()` y `protected configurar(PDF): void` |
| D4b | `BoletinService` | `app/Services/BoletinService.php` | final | Atributo `generador` (`private readonly GeneradorBoletin`); **9 miembros de método contando el constructor**: constructor con valor por defecto `new GeneradorBoletin()`, 6 métodos públicos (`pantalla`, `alumnosParaBoletin`, `generar`, `existeArchivo`, `descargar`, `ver`) y 2 privados (`historial`, `estadisticas`) |
| D4b | `PDF` (externa) | clase de la librería DomPDF (`Barryvdh\DomPDF\PDF`) | clase | Solo el nombre |

## Tabla 2. Relaciones (16) y su fundamento

| # | Figura | Relación | Tipo | Dónde está en el código |
| --- | --- | --- | --- | --- |
| 1–4 | D4a | `InformeResumenEjecutivo`, `InformeComparativoCursos`, `InformeCuadroHonor`, `InformeAlertaRiesgo` → `Informe` | herencia | `final class ... extends Informe` |
| 5 | D4a | `Informe` → `ReporteService` | asociación "reportes", cardinalidad 1 | `__construct(protected readonly ReporteService $reportes)`: tipo no nulo, sin valor por defecto |
| 6 | D4a | `ReporteService` → `Informe` | dependencia "informe()" | `private function informe(int $tipo): Informe` |
| 7–10 | D4a | `ReporteService` → cada hija | dependencia "crea" | `informe()`: `match` con `new InformeResumenEjecutivo($this)`, `new InformeComparativoCursos($this)`, `new InformeCuadroHonor($this)` y `new InformeAlertaRiesgo($this)` |
| 11 | D4b | `GeneradorPdf` → `GeneradorDocumento` | implementación | `abstract class GeneradorPdf implements GeneradorDocumento` |
| 12–13 | D4b | `GeneradorBoletin`, `GeneradorReporte` → `GeneradorPdf` | herencia | `final class ... extends GeneradorPdf` |
| 14 | D4b | `BoletinService` → `GeneradorBoletin` | asociación "generador", cardinalidad 1 | `__construct(private readonly GeneradorBoletin $generador = new GeneradorBoletin())`: tipo no nulo y con valor por defecto |
| 15 | D4b | `GeneradorDocumento` → `PDF` | dependencia "generar()" | `generar(array $datos): PDF` |
| 16 | D4b | `GeneradorPdf` → `PDF` | dependencia "generar(), configurar()" | `generar()` devuelve `DomPdf` (alias de `Barryvdh\DomPDF\PDF`) y `configurar(DomPdf $pdf)` lo recibe |

## Tabla 3. Lo que cada informe pide a `ReporteService`

| Clase | `tipo()` | `datos(array $filtros)` llama a | Título (`ReporteService::TITULOS`) |
| --- | --- | --- | --- |
| `InformeResumenEjecutivo` | 1 | `resumen($filtros)` y `distribucionPromedios($filtros)` | `'Resumen Ejecutivo Institucional'` |
| `InformeComparativoCursos` | 2 | `estadisticasCurso($curso, $filtros)` por cada curso (`Curso::query()`) | `'Reporte Comparativo por Curso'` |
| `InformeCuadroHonor` | 3 | `cuadroDeHonor($filtros)` | `'Cuadro de Honor y Alumnos Destacados'` |
| `InformeAlertaRiesgo` | 4 | `alertaRiesgo($filtros)` | `'Alerta de Riesgo Académico y Ausentismo'` |

`Informe::titulo()` devuelve `ReporteService::TITULOS[$this->tipo()]`. `ReporteService::contextoDePdf()` llama a `titulo()` y a `datos()` sobre el objeto `Informe` que elige `informe()`, sin conocer la clase concreta.

## Evidencia de herencia, encapsulamiento y polimorfismo (I-04, I-13, I-14)

- **Herencia:** `Informe` con cuatro hijas; `GeneradorPdf` con dos hijas; `GeneradorPdf` implementa `GeneradorDocumento`.
- **Polimorfismo (sobrescritura):** cada hija de `Informe` sobrescribe `tipo()` y `datos()`; cada hija de `GeneradorPdf` sobrescribe `vista()`; `GeneradorReporte` también sobrescribe `configurar()` (`setPaper('a4', 'portrait')`). `GeneradorBoletin` usa el `configurar()` heredado, que no hace nada.
- **Método plantilla:** `GeneradorPdf::generar()` es `final`: carga la vista que indica `vista()`, llama a `configurar()` y devuelve el PDF. Las hijas no pueden cambiar ese orden; solo completan `vista()` y, si quieren, `configurar()`.
- **Encapsulamiento:** `Informe::$reportes` es `protected readonly`; `BoletinService::$generador` es `private readonly`; `configurar()` es `protected`; `ReporteService` y las seis hijas son `final`.

## Quién usa estas clases (controladores, para D5)

| Controlador | Usa | Cómo |
| --- | --- | --- |
| `Admin\ReportesController` | `ReporteService` y `GeneradorReporte` | Ambos como atributos `private readonly`; `exportar()` llama a `$this->generador->generar($this->reportes->contextoDePdf(...))->download(...)` |
| `Admin\BoletinesController` | `BoletinService` | Atributo `private readonly` |
| `ReporteController` (API) | `ReporteService` | Atributo `private readonly`; llama a `notasPorMateria()` y `asistenciasPorMateria()` |

No se dibujan en D4: se dibujan en D5 (anexo).

## Dependencias por firma no dibujadas

- `Curso`: parámetro de `ReporteService::estadisticasCurso()` y consulta de `InformeComparativoCursos::datos()`.
- `BoletinGenerado`: lo crea `BoletinService::generar()` y es parámetro de `existeArchivo()`, `descargar()` y `ver()`.
- Clases de Laravel y PHP: `Carbon`, `Collection`, `LengthAwarePaginator`, `StreamedResponse`, `BinaryFileResponse`.

## Lo que el código no permite comprobar

1. **Cardinalidad en el lado de `Informe`.** `ReporteService` crea cada `Informe` dentro de `informe()` y no los guarda, así que no hay cuántos `Informe` por `ReporteService`. Solo se dibuja el `1` del lado de `ReporteService`.
2. **`ReporteService` → `Informe`.** Es una dependencia por el tipo de retorno de un método privado y por los `new`; no hay atributo.
3. **`vista()` en `GeneradorPdf`.** `GeneradorPdf` no declara `vista()`; lo hereda como abstracto de la interfaz. Por eso `GeneradorPdf` es abstracta. El dibujo lo muestra en la interfaz y en las dos hijas, no en `GeneradorPdf`.
4. **Fachada `Pdf`.** `GeneradorPdf::generar()` llama a `Pdf::loadView(...)`, la fachada `Barryvdh\DomPDF\Facade\Pdf`. No se dibuja: la fachada y la clase `PDF` son dos clases distintas de la librería, y solo la segunda figura en las firmas.
5. **Vistas.** `'admin.boletin-pdf'` y `'admin.reporte-pdf'` son cadenas, no clases.
6. **`final`.** Mermaid no tiene notación propia; ver convenciones.

## Versión

Los diagramas se generaron sobre el commit `c6e87b4`. El commit `59a7449` solo modificó cinco comentarios y el texto del README (verificado con git diff) y no cambia código. Si se hacen commits posteriores, hay que comprobar que solo cambien comentarios o documentación; si cambia código (clases, firmas, relaciones o esquema), hay que revisar los diagramas y esta guía.
