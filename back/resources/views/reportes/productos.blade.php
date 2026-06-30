<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productos Farmacia</title>
    <style>
        @page { margin: 15px 18px; size: letter landscape; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8px; line-height: 1.3; }
        .header { border-bottom: 2px solid #00695C; padding-bottom: 5px; margin-bottom: 7px; overflow: hidden; }
        .brand { color: #00695C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        .summary { background: #E0F2F1; border: 1px solid #80CBC4; padding: 5px 8px; margin-bottom: 7px; overflow: hidden; }
        .s-box { display: inline-block; width: 32%; vertical-align: top; }
        .s-label { color: #546E7A; font-size: 7px; text-transform: uppercase; display: block; }
        .s-val { color: #004D40; font-size: 12px; font-weight: bold; display: block; }
        .filters { border: 1px solid #ddd; padding: 3px 6px; margin-bottom: 6px; font-size: 7px; color: #475569; }
        .th { background: #00695C; color: #fff; font-size: 7.5px; font-weight: bold; text-transform: uppercase; padding: 4px 3px; overflow: hidden; }
        .cell { display: inline-block; vertical-align: top; overflow: hidden; }
        .c-cod  { width:  7%; }
        .c-nom  { width: 28%; }
        .c-marc { width: 12%; }
        .c-desc { width: 23%; }
        .c-fab  { width: 15%; }
        .c-unid { width:  7%; }
        .c-tipo { width:  6%; text-align: center; }
        .row-item { border-bottom: 1px solid #dbe4ee; padding: 3px; page-break-inside: avoid; }
        .row-item:nth-child(even) { background: #F1F8F7; }
        .empty { border: 1px dashed #cbd5e1; color: #64748b; padding: 22px; text-align: center; margin-top: 18px; }
        .badge { background: #00695C; color: #fff; padding: 1px 4px; border-radius: 3px; font-size: 7px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}<br>Total: {{ $total }} registros</div>
        <div class="brand">Clínica URME · Farmacia</div>
        <h1>Catálogo de Productos</h1>
    </div>

    <div class="summary">
        <div class="s-box">
            <span class="s-label">Total productos</span>
            <span class="s-val">{{ number_format($total, 0, ',', '.') }}</span>
        </div>
        <div class="s-box">
            <span class="s-label">Tipo</span>
            <span class="s-val">{{ $tipo ?: 'Todos' }}</span>
        </div>
        <div class="s-box">
            <span class="s-label">Búsqueda</span>
            <span class="s-val" style="font-size:9px">{{ $q ?: '—' }}</span>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="empty">No existen productos para los filtros aplicados.</div>
    @else
        <div class="th">
            <span class="cell c-cod">Código</span>
            <span class="cell c-nom">Nombre</span>
            <span class="cell c-marc">Marca</span>
            <span class="cell c-desc">Descripción</span>
            <span class="cell c-fab">Fabricante</span>
            <span class="cell c-unid">Unidad</span>
            <span class="cell c-tipo">Tipo</span>
        </div>
        @foreach ($items as $item)
            <div class="row-item">
                <span class="cell c-cod">{{ $item->codigo ?: '—' }}</span>
                <span class="cell c-nom">{{ $item->nombre }}</span>
                <span class="cell c-marc">{{ $item->marca ?: '—' }}</span>
                <span class="cell c-desc">{{ $item->descripcion ?: '—' }}</span>
                <span class="cell c-fab">{{ $item->fabricante?->nombre ?: '—' }}</span>
                <span class="cell c-unid">{{ $item->unidad?->abreviatura ?: ($item->unidad?->nombre ?: '—') }}</span>
                <span class="cell c-tipo"><span class="badge">{{ $item->tipo }}</span></span>
            </div>
        @endforeach
    @endif
</body>
</html>
