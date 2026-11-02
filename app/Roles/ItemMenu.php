<?php

declare(strict_types=1);

namespace App\Roles;

/**
 * Objeto de valor inmutable: una opción del menú lateral.
 */
final readonly class ItemMenu
{
    public function __construct(
        public string $ruta,
        public string $titulo,
        public string $icono,
        public ?string $subtitulo = null,
    ) {
    }
}
