# D3. Valores y reglas (D3a, D3b y D3c): guía para revisar los diagramas contra el código

Repositorio: `gnvargas-dev/sistema-escolar`, commit `c6e87b4`. Los archivos están en `app/Valores/`, `app/Reglas/` y `app/Enums/`.

## Convenciones del dibujo

- D3 se dibuja en tres figuras que forman un único modelo con D1 y D2: **D3a** (objetos de valor: `Calificacion`, `ResultadoSemestral`, `PorcentajeAsistencia`), **D3b** (interfaz `ReglaAsistencia`, sus tres reglas y `EstadoAsistencia`) y **D3c** (los enums `TipoEvaluacion`, `NivelAsistencia` y `EstadoLegajo`). `Nota` (D1a), `ReglaAsistencia` y `EstadoAsistencia` (D3b) aparecen en D3a solo con el nombre. `Asistencia` (D1a) aparece en D3b y `Evaluacion` (D1b) en D3c, también solo con el nombre.
- Las constantes se muestran con su valor exacto (`MINIMA = 1.0`) y subrayadas, porque son estáticas. El código no declara el tipo de las constantes, por eso el dibujo tampoco lo indica.
- `-readonly` marca los atributos privados de solo lectura. `-__construct` es el constructor privado. Los métodos estáticos van subrayados. Los tipos de retorno con `?` lo llevan antes del tipo, como en PHP.
- Los métodos abstractos van en *cursiva* (`ReglaAsistencia::cuenta`).
- **Línea punteada con triángulo vacío** = implementación de la interfaz. **Línea punteada con flecha** = dependencia: la clase de origen recibe, devuelve o usa la de destino; la etiqueta dice el método o atributo.
- `final` se muestra como estereotipo (Mermaid no tiene notación propia). Los enums llevan `<<enumeration>>` y sus casos con el valor de texto exacto.
- No se dibujan cardinalidades: no hay asociaciones entre atributos ni claves.
- `JsonSerializable` y `Stringable` no se dibujan: están en el texto de esta guía (ver "Interfaces de PHP implementadas").

## Tabla 1. Clases (11)

| Figura | Clase | Archivo | Tipo | Miembros dibujados |
| --- | --- | --- | --- | --- |
| D3a | `Calificacion` | `app/Valores/Calificacion.php` | final | Constantes `MINIMA = 1.0`, `MAXIMA = 5.0`, `APROBACION = 2.0`; atributo `valor` (privado, readonly); constructor privado; estáticos `de(int\|float\|string\|self)` y `promedioDe(self ...)`; 6 métodos de instancia. En total, 9 métodos con el constructor |
| D3a | `ResultadoSemestral` | `app/Valores/ResultadoSemestral.php` | final | Constantes `PESO_PARCIALES = 60`, `PESO_EXAMEN = 40`, `MINIMO_POR_BLOQUE = 70`; 4 atributos privados readonly; **21 métodos**: constructor privado (1), estáticos `calcular` y `centesimas` (2, el segundo privado), 16 métodos públicos de instancia y 2 privados (`numerador`, `denominador`) |
| D3a | `PorcentajeAsistencia` | `app/Valores/PorcentajeAsistencia.php` | final | Atributos `contadas` y `total` (privados, readonly); 10 métodos: constructor privado, estáticos `deConteos`, `deEstados` y `sinRegistros`, y 6 de instancia |
| D3b | `ReglaAsistencia` | `app/Reglas/ReglaAsistencia.php` | interfaz | `cuenta(EstadoAsistencia estado): bool` (abstracto) |
| D3b | `SoloPresentes` | `app/Reglas/SoloPresentes.php` | final, implementa `ReglaAsistencia` | `cuenta(EstadoAsistencia estado): bool` |
| D3b | `PresentesYTardanzas` | `app/Reglas/PresentesYTardanzas.php` | final, implementa `ReglaAsistencia` | `cuenta(EstadoAsistencia estado): bool` |
| D3b | `PresentesTardanzasYJustificados` | `app/Reglas/PresentesTardanzasYJustificados.php` | final, implementa `ReglaAsistencia` | `cuenta(EstadoAsistencia estado): bool` |
| D3b | `EstadoAsistencia` | `app/Enums/EstadoAsistencia.php` | enum con valores de texto | `Presente = 'presente'`, `Ausente = 'ausente'`, `Tarde = 'tarde'`, `Justificado = 'justificado'`; `desde(self\|string estado)` (estático); `etiqueta()` |
| D3c | `NivelAsistencia` | `app/Enums/NivelAsistencia.php` | enum con valores de texto | `Excelente`, `Bueno`, `Regular`, `Malo` (valores en minúscula); `desdePorcentaje(float)` (estático); `etiqueta()`; `comoArreglo()` |
| D3c | `TipoEvaluacion` | `app/Enums/TipoEvaluacion.php` | enum con valores de texto | `Parcial = 'parcial'`, `Examen = 'examen'`; `etiqueta()` |
| D3c | `EstadoLegajo` | `app/Enums/EstadoLegajo.php` | enum con valores de texto | `SinDocumentos = 'sin_documentos'`, `Incompleto = 'incompleto'`, `Completo = 'completo'`; `desdeConteo(int entregados, int total)` (estático) |

## Tabla 2. Relaciones (13) y su fundamento

| # | Figura | Relación | Tipo | Dónde está en el código |
| --- | --- | --- | --- | --- |
| 1 | D3a | `ResultadoSemestral` → `Calificacion` | dependencia "nota()" | `ResultadoSemestral::nota(): Calificacion` (usa `Calificacion::de()`) |
| 2 | D3a | `Nota` → `Calificacion` | dependencia "calificacion()" | `Nota::calificacion(): ?Calificacion`; `Nota::asignarCalificacion(Calificacion\|int\|float\|string)` |
| 3 | D3a | `PorcentajeAsistencia` → `ReglaAsistencia` | dependencia "deEstados()" | `deEstados(iterable $estados, ReglaAsistencia $regla)` |
| 4 | D3a | `PorcentajeAsistencia` → `EstadoAsistencia` | dependencia "deEstados()" | `deEstados()` llama a `EstadoAsistencia::desde($estado)` |
| 5–7 | D3b | `SoloPresentes`, `PresentesYTardanzas`, `PresentesTardanzasYJustificados` → `ReglaAsistencia` | implementación | `final class ... implements ReglaAsistencia` |
| 8 | D3b | `ReglaAsistencia` → `EstadoAsistencia` | dependencia | `cuenta(EstadoAsistencia $estado): bool` |
| 9–11 | D3b | cada regla → `EstadoAsistencia` | dependencia | parámetro de `cuenta()` y comparación con los casos del enum (`Presente`, `Tarde`, `Ausente`) |
| 12 | D3b | `Asistencia` → `EstadoAsistencia` | dependencia "estadoAsistencia()" | `Asistencia::estadoAsistencia(): ?EstadoAsistencia`; `marcar(EstadoAsistencia\|string)` |
| 13 | D3c | `Evaluacion` → `TipoEvaluacion` | dependencia "tipo" | `casts()` de `Evaluacion`: `'tipo' => TipoEvaluacion::class`; `esParcial()` |

`NivelAsistencia` y `EstadoLegajo` no tienen relaciones dentro de D3 (se dibujan en D3c): las usan clases de servicio (ver abajo).

## Evidencia de encapsulamiento y de sobrecarga simulada (I-13, I-14)

- **Constructor privado:** `Calificacion`, `ResultadoSemestral` y `PorcentajeAsistencia` solo se crean con constructores estáticos con nombre (`Calificacion::de`, `ResultadoSemestral::calcular`, `PorcentajeAsistencia::deConteos`, `deEstados`, `sinRegistros`). `Calificacion::promedioDe` también usa el constructor privado.
- **Atributos `readonly` privados:** los tres objetos de valor son inmutables.
- **Sobrecarga simulada con tipos de unión:** `Calificacion::de(int|float|string|self)`, `EstadoAsistencia::desde(self|string)` y `ResultadoSemestral::calcular(array, int|float|string, int)`.
- **Constantes:** `Calificacion::MINIMA`, `MAXIMA` y `APROBACION`; `ResultadoSemestral::PESO_PARCIALES`, `PESO_EXAMEN` y `MINIMO_POR_BLOQUE`.

## Qué regla usa cada servicio (commit `c6e87b4`)

| Servicio y método | Regla | Cómo la usa |
| --- | --- | --- |
| `AsistenciaMetricasService::deAlumno()` | `PresentesYTardanzas` | La pasa a `calcular(iterable, ReglaAsistencia, ...)`. La llama `AlumnoController::asistencia` |
| `AsistenciaMetricasService::deProfesor()` | `SoloPresentes` | La pasa a `calcular(...)`. La llama `ProfesorController::historialAsistencias` |
| `PanelAlumnoService::inicio()` | `SoloPresentes` | `PorcentajeAsistencia::deEstados($estados, $soloPresentes)` |
| `PanelAlumnoService::asistencia()` | `PresentesYTardanzas` | `PorcentajeAsistencia::deEstados($estados, $regla)` |
| `AsistenciaService::porcentajeDe()` | `PresentesTardanzasYJustificados` | Llama a `$regla->cuenta(...)` sobre `EstadoAsistencia::cases()` para armar la lista de estados que cuentan y los filtra en la consulta. No usa `PorcentajeAsistencia::deEstados()` |
| `EstadisticasService::asistenciaGeneral()` | **ninguna clase de regla** | Cuenta con una consulta (`where estado = Presente`) y llama a `PorcentajeAsistencia::deConteos()`. El criterio coincide con `SoloPresentes`, pero la clase no se usa; solo la menciona un comentario |
| `ReporteService` (porcentaje de asistencia, con y sin filtro de alumno) | **ninguna clase de regla** | Igual que el anterior: consulta de presentes y `deConteos()` |

Con esto, las tres reglas están en uso desde `c6e87b4`. `EstadisticasService` alimenta el panel del administrador, que por eso no usa ninguna clase de regla.

Otros usos fuera del dibujo (clases de servicio de D5): `NivelAsistencia::desdePorcentaje` lo llama `PanelAlumnoService`; `EstadoLegajo::desdeConteo` lo llama `LegajoService`; `ResultadoSemestral::calcular` lo llama `EvaluacionService`.

## Interfaces de PHP implementadas (solo texto)

- `Calificacion` implementa `JsonSerializable` y `Stringable`.
- `PorcentajeAsistencia` y `ResultadoSemestral` implementan `JsonSerializable`.

## Lo que el código no permite comprobar

1. **Reglas y enums por tipo.** La dependencia de las tres reglas con `EstadoAsistencia` surge de la firma de `cuenta()` y de la comparación con casos del enum; no hay atributo ni clave que la relacione.
2. **`Nota`, `Asistencia` y `Evaluacion`.** Se dibujan solo con el nombre para mostrar las dependencias hacia los tipos de D3. Sus miembros están en D1a y D1b.
3. **Constantes sin tipo.** El código declara `public const MINIMA = 1.0;` sin tipo; por eso el dibujo no pone tipo en las constantes.
4. **`final`.** Mermaid no tiene notación propia; va como estereotipo.
5. **Comentario del código.** El comentario de `Calificacion.php` (línea 12) mencionaba una escala del MEC en el commit `c6e87b4`; el dibujo no lo incluye. Se ajustó en el commit `59a7449`.

## Versión

Los diagramas se generaron sobre el commit `c6e87b4`. El commit `59a7449` solo modificó cinco comentarios y el texto del README (verificado con git diff) y no cambia código. Si se hacen commits posteriores, hay que comprobar que solo cambien comentarios o documentación; si cambia código (clases, firmas, relaciones o esquema), hay que revisar los diagramas y esta guía.
