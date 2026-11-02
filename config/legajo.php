<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo de documentos del legajo
    |--------------------------------------------------------------------------
    |
    | Lista fija de documentos que debería tener el legajo de cada alumno,
    | agrupados por categoría. La clave de cada ítem (ej: "cedula_dni") se
    | guarda en la tabla documentos_alumno para saber qué documentos ya
    | fueron entregados.
    |
    */

    'categorias' => [
        [
            'clave' => 'personal',
            'nombre' => 'Documentación Personal e Identificatoria',
            'items' => [
                'cedula_dni' => 'Cédula de Identidad o DNI escaneado de ambos lados',
                'partida_nacimiento' => 'Partida o certificado de nacimiento',
                'foto_carnet' => 'Fotografía actualizada tipo carnet',
            ],
        ],
        [
            'clave' => 'academica',
            'nombre' => 'Documentación Académica y de Admisión',
            'items' => [
                'ficha_inscripcion' => 'Ficha de inscripción o matrícula firmada',
                'certificado_estudios' => 'Certificado de estudios o boletín de calificaciones anterior / analítico',
                'constancia_pase' => 'Constancia de pase o traslado de otra institución (si aplica)',
            ],
        ],
        [
            'clave' => 'salud',
            'nombre' => 'Salud y Bienestar',
            'items' => [
                'certificado_medico' => 'Certificado médico y tipo de sangre',
                'libreta_vacunacion' => 'Libreta de vacunación al día',
                'ficha_medica' => 'Ficha médica con autorizaciones de emergencia o alergias',
            ],
        ],
        [
            'clave' => 'convivencia',
            'nombre' => 'Seguimiento y Convivencia',
            'items' => [
                'observaciones_pedagogicas' => 'Observaciones pedagógicas o informes de apoyo psicopedagógico',
                'registro_autorizaciones' => 'Registro de autorizaciones (quiénes pueden retirar al alumno, permisos de salida)',
                'notificaciones_disciplinarias' => 'Notificaciones disciplinarias o actas de acuerdo si el colegio lo requiere',
            ],
        ],
    ],

];