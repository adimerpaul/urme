<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 18px 20px; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #172033;
            font-size: 8px;
            line-height: 1.2;
        }

        .header {
            border-bottom: 2px solid #0f5ea8;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .brand {
            color: #0f5ea8;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        h1 {
            margin: 2px 0 0;
            font-size: 16px;
            color: #111827;
        }

        .row { clear: both; width: 100%; }
        .row:after { clear: both; content: ""; display: block; }
        .left { float: left; width: 70%; }
        .meta { float: right; width: 30%; color: #64748b; font-size: 8px; text-align: right; }
        .muted { color: #64748b; font-size: 7px; }
        .code { color: #0f5ea8; font-weight: bold; white-space: nowrap; }
        .right { text-align: right; }

        .summary {
            clear: both;
            margin: 8px 0;
            border: 1px solid #c8e0f7;
            background: #eef6ff;
            padding: 5px 7px;
        }

        .summary-box {
            display: inline-block;
            width: 32%;
            vertical-align: top;
        }

        .summary-label {
            color: #64748b;
            display: block;
            font-size: 7px;
            text-transform: uppercase;
        }

        .summary-value {
            color: #0f172a;
            display: block;
            font-size: 11px;
            font-weight: bold;
        }

        .filters {
            clear: both;
            border: 1px solid #dbe4ee;
            margin: 6px 0 8px;
            padding: 4px 5px;
        }

        .filter-line { margin-bottom: 2px; }
        .filter-label { color: #475569; font-weight: bold; display: inline-block; width: 70px; }

        .table-head {
            background: #0f5ea8;
            color: #fff;
            font-size: 7px;
            font-weight: bold;
            padding: 4px 3px;
            text-transform: uppercase;
        }

        .item-row {
            border-bottom: 1px solid #dbe4ee;
            page-break-inside: avoid;
            padding: 3px;
        }

        .item-row:nth-child(even) { background: #f8fbff; }
        .cell { display: inline-block; vertical-align: top; }
        .c-item { width: 34%; }
        .c-unidad { width: 8%; }
        .c-cantidad { width: 8%; text-align: right; }
        .c-precio { width: 8%; text-align: right; }
        .c-clasificador { width: 39%; padding-left: 2%; }

        .empty {
            border: 1px dashed #cbd5e1;
            color: #64748b;
            margin-top: 20px;
            padding: 22px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header row">
        <div class="left">
            <div class="brand">Sistema de almacen</div>
            <h1>{{ $title }}</h1>
            <div class="muted">Clasificador presupuestario 2026</div>
        </div>
        <div class="meta">
            Generado: {{ now()->format('d/m/Y H:i') }}<br>
            Tipo: {{ $existente ? 'Solo material existente' : 'Todos los items' }}
        </div>
    </div>

    <div class="summary">
        <div class="summary-box">
            <span class="summary-label">Items</span>
            <span class="summary-value">{{ number_format($summary['items'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="summary-box">
            <span class="summary-label">Cantidad total</span>
            <span class="summary-value">{{ number_format($summary['cantidad'] ?? 0, 2, ',', '.') }}</span>
        </div>
        <div class="summary-box">
            <span class="summary-label">Registros impresos</span>
            <span class="summary-value">{{ number_format($items->count(), 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="filters">
        <div class="filter-line">
            <span class="filter-label">Grupo</span>{{ $filters['grupo'] }}
        </div>
        <div class="filter-line">
            <span class="filter-label">Partida</span>{{ $filters['partida'] }}
        </div>
        <div class="filter-line">
            <span class="filter-label">Subpartida</span>{{ $filters['subpartida'] }}
        </div>
        <div class="filter-line">
            <span class="filter-label">Busqueda</span>{{ $filters['busqueda'] }}
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="empty">No existen registros para los filtros seleccionados.</div>
    @else
        <div class="table-head">
            <span class="cell c-item">Item</span>
            <span class="cell c-unidad">Unidad</span>
            <span class="cell c-cantidad">Cantidad</span>
            <span class="cell c-precio">P.U.</span>
            <span class="cell c-clasificador">Clasificador</span>
        </div>

        @foreach ($items as $item)
            <div class="item-row">
                <span class="cell c-item">{{ $item->nombre }}</span>
                <span class="cell c-unidad">{{ $item->unidad_medida ?: '-' }}</span>
                <span class="cell c-cantidad">{{ number_format($item->cantidad ?? 0, 2, ',', '.') }}</span>
                <span class="cell c-precio">{{ number_format($item->precio_unitario ?? 0, 2, ',', '.') }}</span>
                <span class="cell c-clasificador">
                    <span class="code">{{ $item->subpartida_codigo }}</span>
                    {{ $item->subpartida_nombre }}<br>
                    <span class="muted">
                        {{ $item->partida_codigo }} - {{ $item->partida_nombre }} |
                        {{ $item->grupo_codigo }} - {{ $item->grupo_nombre }}
                    </span>
                </span>
            </div>
        @endforeach
    @endif
</body>
</html>
