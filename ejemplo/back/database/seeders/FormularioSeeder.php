<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Formularios;

class FormularioSeeder extends Seeder
{
    public function run(): void
    {
        $formularios = [

            // -----------------------------------------------------------------
            // 1) HORMONAS TIROIDEAS – ELISA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Hormonas Tiroideas – Elisa',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>TIROTROPINA (TSH)</td><td></td><td>uIU/ml</td><td>0,28 a 6,82 uIU/ml</td></tr>
        <tr><td>TRIYODOTIRONINA (T3)</td><td></td><td>ng/ml</td><td>0,52 a 1,85 ng/ml</td></tr>
        <tr><td>TIROXINA LIBRE (FT4)</td><td></td><td>ng/dl</td><td>0,8 a 2,0 ng/dl</td></tr>
        <tr><td>TIROXINA TOTAL (T4)</td><td></td><td>ng/dl</td><td>5,0 a 13,0 ng/dl</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 2) TSH – FT4 (CLIA)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'TSH – FT4 (CLIA)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>TIROTROPINA (TSH)</td><td></td><td>uIU/ml</td><td>0,35 a 5,1 uIU/ml</td></tr>
        <tr><td>TIROXINA LIBRE (FT4)</td><td></td><td>ng/dl</td><td>0,87 a 1,85 ng/dl</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 3) HORMONAS TIROIDEAS – CLIA COMPLETO
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Hormonas Tiroideas – CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>TIROTROPINA (TSH)</td><td></td><td>uIU/ml</td><td>0,35 a 5,1 uIU/ml</td></tr>
        <tr><td>TRIYODOTIRONINA (T3)</td><td></td><td>ng/ml</td><td>0,58 a 1,62 ng/ml</td></tr>
        <tr><td>TIROXINA LIBRE (FT4)</td><td></td><td>ng/dl</td><td>0,87 a 1,85 ng/dl</td></tr>
        <tr><td>TIROXINA TOTAL (T4)</td><td></td><td>ug/dl</td><td>5,0 a 14,9 ug/dl</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 4) CORTISOL BASAL + ACTH – CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Cortisol Basal – CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>CORTISOL BASAL<br><small>08:00 a.m.</small></td>
            <td></td>
            <td>ug/dl</td>
            <td>Mañana: 6,4 a 22,8 ug/dl (Adultos)</td>
        </tr>
        <tr>
            <td>ADRENOCORTICOTROPINA (ACTH)<br><small>08:00 a.m.</small></td>
            <td></td>
            <td>pg/mL</td>
            <td>7,0 a 65 pg/mL</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 5) CORTISOL – ELISA (mañana / tarde + ACTH)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Cortisol – Elisa',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>CORTISOL BASAL<br><small>08:00 a.m.</small></td>
            <td></td>
            <td>ng/ml</td>
            <td>Mañana: 50 a 230 ng/ml</td>
        </tr>
        <tr>
            <td>CORTISOL<br><small>20:00 p.m.</small></td>
            <td></td>
            <td>ng/ml</td>
            <td>Tarde: 30 a 150 ng/ml</td>
        </tr>
        <tr>
            <td>ADRENOCORTICOTROPINA (ACTH)<br><small>09:30 a.m.</small></td>
            <td></td>
            <td>pg/mL</td>
            <td>7,0 a 63 pg/mL</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 6) MARCADORES TIROIDEOS – TPO / TG (CLIA)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Marcadores Tiroideos – TPO/Tg (CLIA)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>TIROGLOBULINA (Tg)</td><td></td><td>ng/ml</td><td>1,28 a 50 ng/ml</td></tr>
        <tr><td>ANTI-PEROXIDASA (Anti TPO)</td><td></td><td>UI/ml</td><td>Hasta 9,0 IU/ml</td></tr>
        <tr><td>ANTI-TIROGLOBULINA (Anti Tg)</td><td></td><td>UI/ml</td><td>Hasta 4,0 IU/ml</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 7) HORMONAS DE FERTILIDAD – CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Hormonas de Fertilidad – CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>ESTRADIOL</td>
            <td></td>
            <td>pg/ml</td>
            <td>
                Fase folicular: 9 a 175 pg/ml<br>
                Fase ovulatoria: 107 a 281 pg/ml<br>
                Fase lútea: 44 a 196 pg/ml<br>
                Menopausia: menor a 18,0 pg/ml<br>
                Hombres: 4,0 a 94,0 pg/ml
            </td>
        </tr>
        <tr>
            <td>HORMONA FOLÍCULO ESTIMULANTE (FSH)</td>
            <td></td>
            <td>mIU/L</td>
            <td>
                Fase folicular: 3,5 a 12,5 mIU/L<br>
                Fase ovulatoria: 4,7 a 21,5 mIU/L<br>
                Fase lútea: 1,7 a 7,7 mIU/L<br>
                Posmenopáusica: 25,8 a 134,8 mIU/L<br>
                Varones: 1,5 a 12,4 mIU/L
            </td>
        </tr>
        <tr>
            <td>HORMONA LUTENIZANTE (LH)</td>
            <td></td>
            <td>mIU/L</td>
            <td>
                Fase folicular: 1,9 a 11,6 mIU/L<br>
                Fase ovulatoria: 12,9 a 105,2 mIU/L<br>
                Fase lútea: 0,8 a 10,5 mIU/L<br>
                Posmenopáusica: 6,6 a 66,4 mIU/L<br>
                Varones: 1,4 a 7,7 mIU/L
            </td>
        </tr>
        <tr>
            <td>PROLACTINA (PRL)</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Mujer adulta: 3,8 a 16,5 ng/ml<br>
                Posmenopáusica: 3,2 a 24,9 ng/ml<br>
                Hombre adulto: 3,0 a 16,5 ng/ml
            </td>
        </tr>
        <tr>
            <td>PROGESTERONA</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Fase folicular: 0,2 a 1,6 ng/ml<br>
                Fase ovulatoria: 0,3 a 2,1 ng/ml<br>
                Fase lútea: 1,8 a 22,5 ng/ml<br>
                Posmenopáusica: menor a 1,05 ng/ml<br>
                1er trimestre de embarazo: 3,9 a 60,0 ng/ml<br>
                2do trimestre de embarazo: 15,4 a 60,0 ng/ml<br>
                Varones: 0,1 a 2,1 ng/ml
            </td>
        </tr>
        <tr>
            <td>TESTOSTERONA</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Hombres: 2,27 a 9,76 ng/ml<br>
                Mujeres: menor a 1,23 ng/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 8) HORMONAS DE FERTILIDAD – ELISA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Hormonas de Fertilidad – Elisa',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>ESTRADIOL</td>
            <td></td>
            <td>pg/ml</td>
            <td>
                Fase folicular: 9 a 175 pg/ml<br>
                Fase ovulatoria: 107 a 281 pg/ml<br>
                Fase lútea: 44 a 196 pg/ml<br>
                Menopausia: menor a 18,0 pg/ml
            </td>
        </tr>
        <tr>
            <td>HORMONA LUTENIZANTE (LH)</td>
            <td></td>
            <td>mIU/L</td>
            <td>
                Fase folicular: 0,5 a 10,5 mIU/L<br>
                Fase ovulatoria: 18,4 a 61,2 mIU/L<br>
                Fase lútea: 0,5 a 10,5 mIU/L<br>
                Menopausia: 8,2 a 40,8 mIU/L<br>
                Hombres: 0,7 a 7,4 mIU/L
            </td>
        </tr>
        <tr>
            <td>TESTOSTERONA</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Hombres: 2,5 a 10,0 ng/ml<br>
                Mujeres: 0,2 a 0,95 ng/ml
            </td>
        </tr>
        <tr>
            <td>TESTOSTERONA LIBRE</td>
            <td></td>
            <td>pg/ml</td>
            <td>
                Hombres: 6,1 a 30,3 pg/ml<br>
                Mujeres: 0,2 a 6,1 pg/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 9) HORMONA DE CRECIMIENTO (HG)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Hormona de Crecimiento – HG',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>HORMONA</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>hHG</td>
            <td></td>
            <td>uUI/ml</td>
            <td>0 a 55 uUI/ml<br><small>Sin previa estimulación</small></td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 10) HCG – ELISA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'HCG – Elisa',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>HCG</td>
            <td></td>
            <td>mIU/ml</td>
            <td>
                1–10 semanas: 10 a 30 mIU/ml<br>
                11–15 semanas: 30 a 100 mIU/ml<br>
                16–24 semanas: 100 a 1000 mIU/ml<br>
                25–40 semanas: 1000 a 10.000 mIU/ml<br>
                Mujeres no embarazadas: menor a 0,5 a 3,0 mIU/ml<br>
                Hombres: menor a 0,5 a 2,2 mIU/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 11) PSA – CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Antígeno Prostático Específico – PSA (CLIA)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>PSA TOTAL</td><td></td><td>ng/ml</td><td>Hasta 4,0 ng/ml</td></tr>
        <tr><td>PSA LIBRE</td><td></td><td>ng/ml</td><td>Hasta 1,0 ng/ml</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 12) PSA – ELISA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Antígeno Prostático Específico – PSA (Elisa)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>PSA TOTAL</td><td></td><td>ng/ml</td><td>Hasta 4,0 ng/ml</td></tr>
        <tr><td>PSA LIBRE</td><td></td><td>ng/ml</td><td>Hasta 1,3 ng/ml</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 13) HCG – CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'HCG – CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>HCG</td>
            <td></td>
            <td>mIU/ml</td>
            <td>
                1.ª semana: 10 a 30 mIU/ml<br>
                2.ª semana: 30 a 100 mIU/ml<br>
                3.ª semana: 100 a 1000 mIU/ml<br>
                4.ª semana: 1000 a 10.000 mIU/ml<br>
                2.º a 3.er mes: 30.000 a 100.000 mIU/ml<br>
                2.º trimestre: 10.000 a 30.000 mIU/ml<br>
                3.er trimestre: 5.000 a 15.000 mIU/ml<br>
                Mujeres no embarazadas y varones: Hasta 5,0 mIU/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 14) OTROS MARCADORES – FERRITINA / PARATOHORMONA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Otros Marcadores – Ferritina / Paratohormona',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>FERRITINA (FTN)</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Hombre: 16 a 220 ng/ml<br>
                Mujeres: 10 a 124 ng/ml
            </td>
        </tr>
        <tr>
            <td>PARATOHORMONA</td>
            <td></td>
            <td>pg/ml</td>
            <td>9,0 a 94,0 pg/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 15) DÍMERO-D – NEFELOMETRÍA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Dímero-D – Nefelometría',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>DÍMEROS-D</td>
            <td></td>
            <td>mg/L</td>
            <td>Menor a 0,5 mg/L</td>
        </tr>
    </tbody>
</table>
HTML,
            ],
            // -----------------------------------------------------------------
            // 6) PSA – ELISA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Antígeno Prostático Específico – PSA (Elisa)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>PSA TOTAL</td><td></td><td>ng/ml</td><td>Hasta 4,0 ng/ml</td></tr>
        <tr><td>PSA LIBRE</td><td></td><td>ng/ml</td><td>Hasta 1,3 ng/ml</td></tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 7) HCG – CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Hormona Gonadotropina Coriónica – HCG (CLIA)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>HCG</td>
            <td></td>
            <td>mUI/ml</td>
            <td>
                1º Semana: 10 a 30 mUI/ml<br>
                2º Semana: 30 a 100 mUI/ml<br>
                3º Semana: 100 a 1.000 mUI/ml<br>
                4º Semana: 1.000 a 10.000 mUI/ml<br>
                2º a 3º mes: 30.000 a 100.000 mUI/ml<br>
                2º Trimestre: 10.000 a 30.000 mUI/ml<br>
                3º Trimestre: 5.000 a 15.000 mUI/ml<br>
                Mujeres no embarazadas y varones: Hasta 5,0 mUI/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 8) OTROS MARCADORES – FERRITINA / PARATOHORMONA (FTN – OTROS)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Otros Marcadores – Ferritina y Paratohormona (Elisa)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>FERRITINA (FTN)</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Hombres: 16 a 220 ng/ml<br>
                Mujeres: 10 a 124 ng/ml
            </td>
        </tr>
        <tr>
            <td>PARATOHORMONA</td>
            <td></td>
            <td>pg/ml</td>
            <td>9,0 a 94,0 pg/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 9) DÍMEROS-D – NEFELOMETRÍA (DD NEFELO)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Dímeros-D – Nefelometría',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>DÍMEROS-D</td>
            <td></td>
            <td>mg/L</td>
            <td>Menor a 0,5 mg/L</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 10) PROCALCITONINA – PCT (ELISA)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Procalcitonina – PCT (Elisa)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>PROCALCITONINA (PCT)</td>
            <td></td>
            <td>ng/ml</td>
            <td>Hasta 0,25 ng/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 11) OTROS MARCADORES – DÍMEROS-D (ELISA) + PCT (CLIA)  [Hoja "DD"]
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Otros Marcadores – Dímeros-D y PCT (Elisa/CLIA)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>DÍMEROS-D</td>
            <td></td>
            <td>ng/ml</td>
            <td>Menor a 500 ng/ml</td>
        </tr>
        <tr>
            <td>PROCALCITONINA (PCT)</td>
            <td></td>
            <td>ng/ml</td>
            <td>Hasta 0,05 ng/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 12) VITAMINAS – VITAMINA D + PARATOHORMONA (ELISA)
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Vitaminas – Vitamina D y Paratohormona (Elisa)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>VITAMINA D</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Deficiencia muy grave: &lt; 5 ng/ml<br>
                Deficiencia severa: 5 a 10 ng/ml<br>
                Deficiencia de: 10 a 20 ng/ml<br>
                Provisión subóptima: 20 a 30 ng/ml<br>
                Nivel óptimo de: 30 a 50 ng/ml<br>
                Valor alto de: 50 a 70 ng/ml<br>
                Sobredosis de: &gt; 70 a 150 ng/ml<br>
                Intoxicación: &gt; 150 ng/ml
            </td>
        </tr>
        <tr>
            <td>PARATOHORMONA</td>
            <td></td>
            <td>pg/ml</td>
            <td>9,0 a 94,0 pg/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 13) VITAMINA D – CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Vitamina D – CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>VITAMINA D</td>
            <td></td>
            <td>ng/ml</td>
            <td>
                Suficiente: &ge; 30 ng/ml<br>
                Deficiencia severa: &lt; 20 ng/ml<br>
                Insuficiente: 20 a 30 ng/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 14) MARCADORES DE DIABETES – PÉPTIDO C + INSULINA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Marcadores de Diabetes – Péptido C e Insulina',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>PÉPTIDO C</td>
            <td></td>
            <td>ng/ml</td>
            <td>0,7 a 1,9 ng/ml</td>
        </tr>
        <tr>
            <td>INSULINA</td>
            <td></td>
            <td>uUI/ml</td>
            <td>
                Niños: &lt; a 10 uUI/ml<br>
                Adultos: 0,7 a 9,0 uUI/ml<br>
                Diabéticos: 0,7 a 25,0 uUI/ml
            </td>
        </tr>
    </tbody>
</table>
HTML,
            ],

            // -----------------------------------------------------------------
            // 15) MARCADORES TUMORALES – ELISA + CLIA
            // -----------------------------------------------------------------
            [
                'nombre'  => 'Marcadores Tumorales – Elisa / CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALOR DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>CA-19-9</td><td></td><td>U/ml</td><td>Hasta 40,0 U/ml</td></tr>
        <tr><td>CA-125</td><td></td><td>U/ml</td><td>Hasta 35,0 U/ml</td></tr>
        <tr><td>CEA</td><td></td><td>ng/ml</td><td>Hasta 10,0 ng/ml</td></tr>
        <tr><td>AFP</td><td></td><td>ng/ml</td><td>Hasta 8,5 ng/ml</td></tr>
        <tr><td>CA-15-3</td><td></td><td>U/ml</td><td>Hasta 32,0 U/ml</td></tr>
    </tbody>
</table>
HTML,
            ],
            [
                'nombre'  => 'Paratohormona (PTH) – CLIA',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>PARATOHORMONA (PTH)</td>
            <td></td>
            <td>pg/ml</td>
            <td>9,0 a 94,0 pg/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],
            [
                'nombre'  => 'Marcadores Cardiacos – Troponina I (cTnI)',
                'area_id' => 5,
                'html'    => <<<HTML
<table border="1" cellpadding="6" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ANALITO</th>
            <th colspan="2">RESULTADOS</th>
            <th>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>TROPONINA-I (cTnI)</td>
            <td></td>
            <td>ng/ml</td>
            <td>Menor a 1,3 ng/ml</td>
        </tr>
    </tbody>
</table>
HTML,
            ],


        ];

        foreach ($formularios as $formulario) {
            Formularios::create($formulario);
        }
    }
}
