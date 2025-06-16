<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Coeficiente de Disponibilidad Técnica (CDT)</title>
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

        .header-section {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #a7d9ed;
        }

        .header-section h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header-section p {
            font-size: 16px;
            color: #555;
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
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

        .total-cdt-section {
            background-color: #d1e7dd;
            border: 1px solid #1a5e3a;
            border-radius: 5px;
            padding: 15px;
            margin-top: 30px;
            text-align: center;
        }

        .total-cdt-section h3 {
            color: #1a5e3a;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .total-cdt-section p {
            font-size: 16px;
            margin: 5px 0;
            color: #0c4323;
        }

        /* NEW CSS for page break */
        .new-page {
            page-break-before: always;
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

            .header-section,
            .section-title,
            .info-block,
            .total-cdt-section {
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

            .header-section h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="header-section">
        <h1>Reporte de Coeficiente de Disponibilidad Técnica (CDT)</h1>
        @if (isset($vehiculo_chapa_reporte))
            <p>Vehículo: {{ $vehiculo_chapa_reporte }}</p>
        @else
            <p>Parque Automotor General</p>
        @endif
        <p>Mes del Reporte: {{ $mes_reporte_str }}</p>
    </div>

    <h3>CDT por Vehículo</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Chapa</th>
                    <th>Tipo Vehículo</th>
                    <th>Días Operativos del Mes</th>
                    <th>Días Paralizado por Averías</th>
                    <th>Días Trabajando</th>
                    <th>CDT (%)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vehiculos_data as $vehiculo)
                    <tr>
                        <td>{{ $vehiculo['chapa'] }}</td>
                        <td>{{ $vehiculo['tipo_vehiculo'] }}</td>
                        <td class="right">{{ $vehiculo['dias_operativos_mes'] }}</td>
                        <td class="right">{{ $vehiculo['dias_paralizado_por_averias'] }}</td>
                        <td class="right">{{ $vehiculo['dias_trabajando'] }}</td>
                        <td class="right">{{ number_format($vehiculo['CDT'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center;">No hay datos de CDT disponibles para los vehículos seleccionados en este mes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (isset($reporte_total))
        <div class="total-cdt-section new-page"> <h3>CDT Total del Parque Automotor</h3>
            <p><strong>Días Operativos Totales:</strong> {{ $reporte_total['dias_operativos_totales'] }}</p>
            <p><strong>Días Paralizados por Averías Totales:</strong> {{ $reporte_total['dias_paralizado_por_averias_totales'] }}</p>
            <p><strong>Días Trabajando Totales:</strong> {{ $reporte_total['dias_trabajando_totales'] }}</p>
            <h3><strong>CDT Total del Parque: {{ number_format($reporte_total['CDT_total_parque'], 2, ',', '.') }}%</strong></h3>
        </div>
    @endif

</body>

</html>