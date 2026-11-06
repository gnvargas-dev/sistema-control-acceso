# Sistema Escolar

Sistema de gestión escolar desarrollado con **Laravel** y PHP 8.3 (tipado estricto) en el marco del proyecto
CodeFusion 2026 (Paradigmas de Programación). Permite administrar usuarios, cursos, materias, alumnos,
notas, asistencias, legajos, boletines y reportes, con tres perfiles de acceso.

## Perfiles y funciones

| Perfil | Qué puede hacer |
|---|---|
| **Administrador** | Panel de control con estadísticas, altas y bajas de usuarios, alumnos y materias, legajos (documentos entregados), boletines en PDF y reportes exportables en PDF. |
| **Profesor** | Cargar evaluaciones y puntos por materia y semestre (la nota final se calcula sola), cargar asistencias por fecha, ver el historial de asistencias y sus alumnos. |
| **Alumno** | Ver su resumen, sus notas por semestre, su asistencia con gráficos y su historial académico. |

El ingreso es con **cédula** y contraseña.

## Reglas de negocio

- **Escala de 1 a 5 (basada en la del sistema educativo paraguayo):** notas enteras de 1 a 5; se aprueba con 2 o más (`Calificacion`).
- **Nota semestral:** parciales 60 % + examen final 40 %; se exige 70 % como mínimo en parciales y en examen por separado, de lo contrario la nota queda en 1 (`ResultadoSemestral`).
- **Asistencia mínima:** 70 % (`AsistenciaMetricasService::MINIMO`).
- Cada semestre admite de 2 a 5 parciales y un examen final (`EvaluacionService`).

## Arquitectura

```
app/
├── Enums/        Estados y tipos cerrados (EstadoAsistencia, RolUsuario, TipoEvaluacion, ...)
├── Valores/      Objetos de valor inmutables: Calificacion, PorcentajeAsistencia, ResultadoSemestral
├── Models/       Entidades Eloquent; Persona (abstracta) → Alumno, Profesor
├── Roles/        Rol (abstracta) → RolAdministrador, RolProfesor, RolAlumno (menú, permisos, alta de perfil)
├── Reglas/       Estrategias de asistencia: SoloPresentes, PresentesYTardanzas, PresentesTardanzasYJustificados
├── Informes/     Informe (abstracta) → resumen ejecutivo, comparativo de cursos, cuadro de honor, alerta de riesgo
├── Documentos/   GeneradorPdf (abstracta) → GeneradorBoletin, GeneradorReporte
├── Services/     Lógica de aplicación (cálculos, consultas y altas)
├── Policies/     Autorización por recurso, delegada en el Rol del usuario
└── Http/         Controladores delgados, requests de validación y middleware
```

### Programación orientada a objetos

- **Herencia:** `Persona`, `Rol`, `Informe` y `GeneradorPdf` son clases base abstractas con hijas concretas.
- **Polimorfismo:** cada `Rol` responde a su manera (menú, permisos, creación de perfil) y reemplaza a los
  condicionales por rol; cada `Informe` implementa `datos()`; cada `ReglaAsistencia` decide qué estados cuentan
  como presente. Donde PHP no tiene sobrecarga, se simula con tipos de unión (por ejemplo
  `Calificacion::de(int|float|string)`).
- **Encapsulamiento:** constructores privados y `readonly` en los objetos de valor, propiedades `private` y
  `protected` en servicios y bases, clases `final` en servicios y hojas de las jerarquías.
- **Modularidad:** cada carpeta tiene una responsabilidad; los controladores validan la petición y delegan
  los cálculos y las consultas en los servicios.

## Instalación

Requisitos: PHP 8.3 o superior, Composer, Node.js y MySQL (las migraciones usan `ALTER TABLE ... MODIFY`, que SQLite no soporta).

```bash
composer install
cp .env.example .env
php artisan key:generate
# configurar DB_* en .env
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## Pruebas

```bash
php artisan test                                   # SQLite en memoria: las pruebas que usan la base de datos se omiten
php artisan test --configuration=phpunit.mysql.xml # MySQL (base sistema_escolar_test)
```

Las pruebas de pantallas comparan el HTML con capturas guardadas en `tests/fixtures/pantallas`.
Si un cambio de diseño es intencional, se regeneran con:

```bash
ACTUALIZAR_SNAPSHOTS=1 php artisan test --configuration=phpunit.mysql.xml --filter=Caracterizacion
```

## API

Las rutas de `routes/api.php` usan Sanctum y el middleware `role:`. Autenticación en `POST /api/auth/login`;
luego se envía el token como `Authorization: Bearer ...`. Hay recursos para notas, asistencias, historial
(alumno), carga de notas y asistencias (profesor), y usuarios, materias, boletines, reportes y legajos
(administrador).
