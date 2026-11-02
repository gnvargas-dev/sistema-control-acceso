<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Documentos\GeneradorBoletin;
use App\Documentos\GeneradorDocumento;
use App\Documentos\GeneradorReporte;
use App\Informes\Informe;
use App\Informes\InformeAlertaRiesgo;
use App\Informes\InformeComparativoCursos;
use App\Informes\InformeCuadroHonor;
use App\Informes\InformeResumenEjecutivo;
use App\Services\ReporteService;
use PHPUnit\Framework\TestCase;

final class GeneradoresYInformesTest extends TestCase
{
    public function test_los_generadores_comparten_interfaz_y_usan_su_propia_vista(): void
    {
        $boletin = new GeneradorBoletin();
        $reporte = new GeneradorReporte();

        $this->assertInstanceOf(GeneradorDocumento::class, $boletin);
        $this->assertInstanceOf(GeneradorDocumento::class, $reporte);
        $this->assertSame('admin.boletin-pdf', $boletin->vista());
        $this->assertSame('admin.reporte-pdf', $reporte->vista());
    }

    public function test_cada_informe_sobrescribe_el_tipo_y_hereda_el_titulo_del_servicio(): void
    {
        $servicio = new ReporteService();

        $informes = [
            1 => new InformeResumenEjecutivo($servicio),
            2 => new InformeComparativoCursos($servicio),
            3 => new InformeCuadroHonor($servicio),
            4 => new InformeAlertaRiesgo($servicio),
        ];

        foreach ($informes as $tipo => $informe) {
            $this->assertInstanceOf(Informe::class, $informe);
            $this->assertSame($tipo, $informe->tipo());
            $this->assertSame(ReporteService::TITULOS[$tipo], $informe->titulo());
            $this->assertSame($servicio->titulo($tipo), $informe->titulo());
        }
    }
}
