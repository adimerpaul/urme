<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: legal landscape; margin: 10px 12px; }
        * { box-sizing: border-box; }
        body{ margin:0; padding:0; font-family: DejaVu Sans, sans-serif; font-size: 10px; color:#111; line-height: 1; }

        .sheet{ width:100%; overflow:hidden; }
        .half{ width:48%; float:left; overflow:hidden; padding:0; }
        .half-left{ transform: scale(1.02); transform-origin: top left; padding-right: 6px; }
        .half-right{ transform: scale(1.02); transform-origin: top left; padding-left: 6px; }

        .title { font-weight:700; font-size: 10.2px; text-align:center; }
        .subtitle { font-size: 8px; text-align:center; margin-top: 1px; }
        .muted { color:#555; }

        .hr { border-top: 1.8px solid #111; margin: 2px 0; }
        .small { font-size: 7.6px; }
        .center { text-align:center; }
        .right { text-align:right; }
        .bold{ font-weight:700; }
        .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }

        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        .no-border td, .no-border th{ border:none; padding:0; }

        .form-grid td{
            border:none;
            padding: 2px 3px 2px 0;
            vertical-align: bottom;
            font-size: 7.6px;
        }
        .label{ font-weight:700; }
        .line{
            border-bottom: 1px solid #111;
            height: 12px;
            padding: 0 3px;
            font-size: 7.7px;
        }

        /* Contenido HTML “impreso” */
        .content{
            margin-top: 6px;
            /*border: 1px solid #111;*/
            padding: 6px;
            min-height: 210px;
        }
        .content table{ width:100%; border-collapse: collapse; }
        .content td, .content th{ border:1px solid #111; padding: 3px 4px; font-size: 9px; }
        .content h1,.content h2,.content h3{ margin: 4px 0; }
        .content p{ margin: 3px 0; }
        .clearfix::after{ content:""; display:block; clear:both; }
    </style>
</head>
<body>

@php
    $val = fn($v, $d='—') => ($v === null || $v === '') ? $d : $v;
@endphp

<div class="sheet clearfix">
    @foreach(['left','right'] as $side)
        <div class="half half-{{ $side }}" style="margin: 10px 6px;">

            {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud'=>$row->created_at])->render() !!}

            <div class="center" style="margin-top:6px; font-weight:700; font-size:10px;">
                INMUNOLOGÍA · {{ $row->nombre ?? 'FORMULARIO' }}
            </div>

            <div class="content">
                {!! $row->html !!}
            </div>

            <table class="no-border" style="margin-top:6px;">
                <tr>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">Firma / Sello</span>
                    </td>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">
                            {{ $row->user ? $row->user->name : '________________' }}<br>
                            Bioquímico(a) / Responsable
                        </span>
                    </td>
                    <td class="center" style="width:33%;">
                        {{-- QR CODE --}}
                        @php
                            $url = url("/api/inmunologia/solicitude-formulario/{$row->id}/pdf");
                            $qrSvgBase64 = base64_encode(
                            QrCode::format('svg')->size(110)->margin(1)->generate($url)
                            );
                        @endphp
                        @if($qrSvgBase64)
                            <img src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}" style="width:70px;">
                        @endif
                    </td>
                </tr>
            </table>

        </div>
    @endforeach
</div>

</body>
</html>
