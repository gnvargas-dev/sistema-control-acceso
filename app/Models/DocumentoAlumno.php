<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoAlumno extends Model
{
    protected $table = 'documentos_alumno';

    protected $fillable = [
        'alumno_id',
        'categoria',
        'documento',
        'fecha_entrega',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'date',
        ];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class);
    }
}