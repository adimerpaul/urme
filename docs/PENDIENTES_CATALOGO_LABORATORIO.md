# Pendientes del catálogo de laboratorio — para revisión del bioquímico

Revisión del catálogo de pruebas del sistema (22/10/2026). **No se corrigió nada**: esta lista solo
describe lo encontrado, para que usted decida cómo debe quedar cada prueba.

> Los datos se tomaron de una copia local del sistema, cargada con el catálogo inicial. Si en
> producción ya se editaron algunas pruebas desde la pantalla «Catálogo de pruebas», es posible que
> algún punto ya esté resuelto. Antes de actuar conviene confirmarlo con el diagnóstico de solo
> lectura (`docs/diagnostico_electrolitos_solo_lectura.php`).

Cómo se corrige cada punto: en **Laboratorio → Catálogo de pruebas**, abra la prueba y use
«Modificar dato» en el parámetro correspondiente (unidad, método, muestra, rango de referencia).

---

## 1. Electrolitos sin valores de referencia, método ni muestra

Estas 7 pruebas se piden por separado (no existe una prueba «Ionograma» única). Cada una tiene un
solo parámetro llamado «RESULTADO», y **ninguna tiene rango de referencia, método ni muestra**. En el
informe salen con «-» en esas columnas.

| Prueba | Unidad actual | Rango | Método | Muestra |
|---|---|---|---|---|
| SODIO | MEQ/L | vacío | vacío | vacío |
| POTASIO | MEQ/L | vacío | vacío | vacío |
| CLORO | MEQ/L | vacío | vacío | vacío |
| CALCIO IONICO | MG/DL | vacío | vacío | vacío |
| CALCIO TOTAL | MG/DL | vacío | vacío | vacío |
| MAGNESIO | MG/DL | vacío | vacío | vacío |
| FOSFORO | MG/DL | vacío | vacío | vacío |

**A decidir:** rango de referencia, método (p. ej. ion selectivo, colorimétrico) y muestra de cada uno.

## 2. Sodio y potasio con datos distintos según la prueba

El sodio y el potasio también aparecen dentro de **GASOMETRÍA ARTERIAL CON LACTATO**, y ahí sí
tienen rango. El mismo analito queda así con datos diferentes según cómo se pida:

| Analito | Como prueba suelta | Dentro de la gasometría |
|---|---|---|
| Sodio | MEQ/L, sin rango | mEq/L, 135 – 145 |
| Potasio | MEQ/L, sin rango | mEq/L, 3,5 – 5,1 |

**A decidir:** si deben compartir unidad y rango.

## 3. Calcio total y calcio iónico

- Los dos están en **mg/dL**, sin rango ni método. Hoy solo los distingue el nombre de la prueba;
  el parámetro de ambos se llama «RESULTADO».
- El calcio iónico se informa a menudo en **mmol/L** (con su propio rango). Si se mantiene en mg/dL,
  el rango debe ser el del calcio iónico y no el del total (8,5 – 10,5 mg/dL).
- El calcio iónico tiene «MUESTRA: SUERO» en su descripción y los demás electrolitos
  «MUESTRA: SUERO/ORINA».

**A decidir:** unidad y rango de cada uno, y si conviene renombrar el parámetro (p. ej. «CALCIO IÓNICO»
en lugar de «RESULTADO»).

## 4. Unidades escritas en MAYÚSCULAS

Muchas unidades quedaron escritas en mayúsculas desde la carga inicial del arancel (no las cambia el
sistema al guardar). Se imprimen tal cual en el informe, p. ej. «140 MEQ/L» o «5.1 MG/DL».

| Unidad guardada | Escritura habitual | N.º de parámetros |
|---|---|---|
| MG/DL | mg/dL | 17 |
| NG/ML | ng/mL | 19 |
| UUI/ML | µUI/mL | 8 |
| PG/ML | pg/mL | 7 |
| U/ML | U/mL | 6 |
| MEQ/L | mEq/L | 3 |
| MG/L | mg/L | 3 |
| G/DL | g/dL | 1 |
| UG/DL | µg/dL | 1 |
| MM/H | mm/h | 1 |
| SEGUNDOS | s | 2 |

**A decidir:** si se corrigen a la escritura habitual. El campo «Unidad» guarda exactamente lo que se
escribe, así que basta con editarlo.

## 5. Perfil lipídico: colesterol total y triglicéridos repetidos

La prueba **HDL-LDL-VLDL COLESTEROL** ya incluye cinco parámetros: colesterol total, triglicéridos,
HDL, VLDL y LDL (con rangos y en mg/dL). Además existen las pruebas sueltas **COLESTEROL TOTAL** y
**TRIGLICERIDOS** (en MG/DL y **sin rango**).

Si se piden juntas, el informe muestra dos veces «COLESTEROL TOTAL» y dos veces «TRIGLICÉRIDOS»,
con distinto formato.

**A decidir:** si la prueba HDL-LDL-VLDL debe incluir colesterol total y triglicéridos, o si estos
deben pedirse siempre como pruebas sueltas.

## 6. Gasometría arterial «CON NA, K, HTO, HEMOGLOBINA»

Su nombre promete sodio, potasio, hematocrito y hemoglobina, pero **no tiene esos parámetros**: solo
pH, pCO2, pO2, bicarbonato, exceso de base, saturación de oxígeno y lactato.

También escribe los rangos con punto decimal (`7.35-7.45`), mientras la otra gasometría usa coma y
espacios (`7,35 - 7,45`).

**A decidir:** agregar los parámetros que faltan o corregir el nombre, y unificar el formato de los
rangos.

---

## Nuevo: agrupación en el informe

Ahora cada prueba puede tener un **«Grupo en el informe»** y un **«Orden en el grupo»** (tarjeta
«Agrupación en el informe» en la configuración de la prueba). Las pruebas de una misma área con el
mismo grupo se imprimen juntas, bajo un solo título. Por ejemplo:

- **ELECTROLITOS:** Sodio 1, Potasio 2, Cloro 3, Calcio iónico 4, Calcio total 5, Magnesio 6, Fósforo 7.
- **PERFIL LIPÍDICO:** Colesterol total, Triglicéridos, HDL-LDL-VLDL.
- **PERFIL RENAL:** Creatinina, Urea.

En producción ninguna prueba tiene grupo asignado todavía: hasta que se asignen, el informe sale
igual que antes. La nota de «ENTREGA / MUESTRA» se imprime una sola vez al pie del grupo si es igual
en todas las pruebas del bloque; si alguna difiere (como el calcio iónico, punto 3), no se imprime.
