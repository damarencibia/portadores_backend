<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Consumo de Combustible</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            margin: 30px;
            color: #333;
            line-height: 1.6;
        }

        h1,
        h2,
        h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            /* Mantener esto si la tabla podría ser más ancha que la pantalla en algunos casos */
            -webkit-overflow-scrolling: touch;
            margin-top: 15px;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
            table-layout: auto;
            /* Permite que el ancho de las columnas se ajuste al contenido */
        }

        th,
        td {
            border: 1px solid #dcdcdc;
            padding: 10px 8px;
            text-align: left;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
        }

        th {
            background-color: #e9eff5;
            font-weight: bold;
            color: #34495e;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tr:nth-child(even) {
            background-color: #f8fafd;
        }

        tr:hover {
            background-color: #e0f2f7;
            transition: background-color 0.2s ease-in-out;
        }

        .right {
            text-align: right;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 10px;
            border-bottom: 2px solid #a7d9ed;
            padding-bottom: 8px;
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .info-block {
            background-color: #f0f8ff;
            border: 1px solid #b7dff2;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .info-block p {
            margin: 5px 0;
        }

        .totals-row {
            background-color: #d1e7dd;
            font-weight: bold;
            color: #1a5e3a;
            font-size: 14px;
        }

        .saldo-row {
            background-color: #ffecc6;
            font-weight: bold;
            color: #8b6e2d;
            font-size: 14px;
        }

        .fecha-cell {
            width: 60px;
            color: #555;
        }

        @media print {
            body {
                margin: 0;
                font-size: 10px;
            }

            table {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            th,
            td {
                border: 1px solid #ccc;
                padding: 5px;
            }

            .section-title,
            .info-block,
            .totals-row,
            .saldo-row {
                background-color: transparent !important;
                -webkit-print-color-adjust: exact;
                color: #333 !important;
            }
        }

        @media (max-width: 768px) {
            body {
                font-size: 12px;
                margin: 15px;
            }

            th,
            td {
                padding: 6px 4px;
                font-size: 11px;
            }

            .section-title {
                font-size: 16px;
            }

            .info-block {
                font-size: 12px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <h1>Reporte de Consumo de Combustible</h1>

    @foreach ($reportData as $data)
        <div class="section-title">Tarjeta: {{ $data['tarjeta_info']['numero'] }}</div>
        <div class="info-block">
            <p><strong>Chofer:</strong> {{ $data['tarjeta_info']['chofer_nombre'] }}</p>
            <p><strong>Vehículo:</strong> {{ $data['tarjeta_info']['vehiculo_chapa'] }}</p>
            <p><strong>Tipo de Combustible:</strong> {{ $data['tarjeta_info']['tipo_combustible_nombre'] }} (Precio:
                ${{ number_format(floatval($data['tarjeta_info']['tipo_combustible_precio'] ?? 0), 2, ',', '.') }})</p>
            <p><strong>Mes del Reporte:</strong> {{ $data['tarjeta_info']['mes_reporte'] }}</p>
        </div>

        <h3>Movimientos</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2">Fecha</th>
                        <th rowspan="2">Hora</th>
                        <th rowspan="2">Lugar</th>
                        <th rowspan="2">No Chips</th>
                        <th colspan="2">Saldo Antes del Movimiento</th> {{-- Nueva columna para Saldo Anterior --}}
                        <th colspan="2">Entradas</th>
                        <th colspan="2">Salidas</th>
                        <th colspan="2">Saldo Después del Movimiento</th> {{-- Renombrado para Saldo Actual --}}
                    </tr>
                    <tr>
                        <th style="font-size: 7px;" >Combustible (L)</th>
                        <th style="font-size: 7px;" >Saldo ($)</th>
                        <th style="font-size: 7px;" >Combustible (L)</th>
                        <th style="font-size: 7px;" >Saldo ($)</th>
                        <th style="font-size: 7px;" >Combustible (L)</th>
                        <th style="font-size: 7px;" >Saldo ($)</th>
                        <th style="font-size: 7px;" >Combustible (L)</th>
                        <th style="font-size: 7px;" >Saldo ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="saldo-row">
                        <td colspan="4"><strong>Saldo del mes anterior</strong></td>
                        {{-- El saldo inicial del mes se muestra en las columnas "Saldo Antes del Movimiento" --}}
                        <td class="right">
                            {{ is_numeric($data['saldo_anterior']['cantidad_combustible']) ? number_format(floatval($data['saldo_anterior']['cantidad_combustible']), 2, ',', '.') : 'N/D' }}
                        </td>
                        <td class="right">
                            {{ is_numeric($data['saldo_anterior']['saldo_monetario']) ? number_format(floatval($data['saldo_anterior']['saldo_monetario']), 2, ',', '.') : 'N/D' }}
                        </td>
                        <td colspan="4"></td> {{-- Celdas vacías para Entradas y Salidas --}}
                        <td colspan="2"></td> {{-- Celdas vacías para Saldo Después del Movimiento --}}
                    </tr>

                    @foreach ($data['movimientos'] as $movimiento)
                        <tr>
                            <td class="fecha-cell">{{ \Carbon\Carbon::parse($movimiento['fecha'])->format('d/m/Y') }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($movimiento['hora'])->format('H:i') }}</td>
                            <td>
                                {{ $movimiento['lugar'] ?? 'N/A' }}
                            </td>
                            <td class="right">
                                {{ $movimiento['no_chip'] ?? 'N/A' }}
                            </td>

                            {{-- Saldo Antes del Movimiento (Cant.) y Saldo Antes del Movimiento (Imp.) --}}
                            <td class="right">
                                @php
                                    $saldoCantidadAnterior = $movimiento['cantidad_combustible_anterior'] ?? 'No disponible';
                                @endphp
                                {{ is_numeric($saldoCantidadAnterior) ? number_format(floatval($saldoCantidadAnterior), 2, ',', '.') : 'N/D' }}
                            </td>
                            <td class="right">
                                @php
                                    // Este campo solo existe para Cargas en tu modelo CargaCombustible.
                                    // Para Retiros, si no está en DB, mostrará N/A.
                                    $saldoMonetarioAnterior = $movimiento['saldo_monetario_anterior'] ?? 'No disponible';
                                @endphp
                                {{ is_numeric($saldoMonetarioAnterior) ? number_format(floatval($saldoMonetarioAnterior), 2, ',', '.') : 'N/D' }}
                            </td>

                            @if ($movimiento['tipo_movimiento'] == 'CARGA')
                                <td class="right">
                                    {{ number_format(floatval($movimiento['cantidad'] ?? 0), 2, ',', '.') }}</td>
                                <td class="right">
                                    {{ number_format(floatval($movimiento['importe'] ?? 0), 2, ',', '.') }}</td>
                                <td class="right">0,00</td>
                                <td class="right">0,00</td>
                            @else
                                <td class="right">0,00</td>
                                <td class="right">0,00</td>
                                <td class="right">
                                    {{ number_format(floatval($movimiento['cantidad'] ?? 0), 2, ',', '.') }}</td>
                                <td class="right">
                                    {{ number_format(floatval($movimiento['importe'] ?? 0), 2, ',', '.') }}</td>
                            @endif

                            {{-- Saldo Después del Movimiento (Cant.) y Saldo Después del Movimiento (Imp.) --}}
                            <td class="right">
                                @php
                                    $saldoCantidadActual =
                                        $movimiento['tipo_movimiento'] == 'CARGA'
                                            ? $movimiento['cantidad_combustible_al_momento_carga'] ?? 'No disponible'
                                            : $movimiento['cantidad_combustible_al_momento_retiro'] ?? 'No disponible';
                                @endphp
                                {{ is_numeric($saldoCantidadActual) ? number_format(floatval($saldoCantidadActual), 2, ',', '.') : 'N/D' }}
                            </td>
                            <td class="right">
                                @php
                                    // Similar a Saldo Anterior Monetario, este campo solo existe en Cargas.
                                    // Si no está en RetiroCombustible, mostrará N/A para retiros.
                                    $saldoMonetarioActual =
                                        $movimiento['tipo_movimiento'] == 'CARGA'
                                            ? $movimiento['saldo_monetario_al_momento_carga'] ?? 'No disponible'
                                            : ($movimiento['saldo_monetario_al_momento_retiro'] ?? 'No disponible'); // Asegurar compatibilidad si se añade
                                @endphp
                                {{ is_numeric($saldoMonetarioActual) ? number_format(floatval($saldoMonetarioActual), 2, ',', '.') : 'N/D' }}
                            </td>
                        </tr>
                    @endforeach

                    <tr class="totals-row">
                        <td colspan="4"><strong>Totales del mes</strong></td>
                        <td colspan="2"></td> {{-- Vacío para los saldos anteriores --}}
                        <td class="right">
                            {{ number_format(floatval($data['totales_mes']['total_cargas_cantidad'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="right">
                            {{ number_format(floatval($data['totales_mes']['total_cargas_importe'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="right">
                            {{ number_format(floatval($data['totales_mes']['total_retiros_cantidad'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="right">
                            {{ number_format(floatval($data['totales_mes']['total_retiros_importe'] ?? 0), 2, ',', '.') }}
                        </td>
                        <td class="right">
                            {{ is_numeric($data['saldo_final']['cantidad_combustible_final']) ? number_format(floatval($data['saldo_final']['cantidad_combustible_final']), 2, ',', '.') : 'N/D' }}
                        </td>
                        <td class="right">
                            {{ is_numeric($data['saldo_final']['saldo_monetario_final']) ? number_format(floatval($data['saldo_final']['saldo_monetario_final']), 2, ',', '.') : 'N/D' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
