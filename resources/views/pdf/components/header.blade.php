<table style="
    width:100%;
    border-collapse:collapse;
    margin-bottom:18px;
">
    <tr>

        <!-- LOGO -->
        <td style="
            width:25%;
            text-align:center;
            vertical-align:middle;
            padding:10px;
        ">

            @php
                $logoSpa = public_path('images/salon.png');
            @endphp

            @if(file_exists($logoSpa))
                <img
                    src="{{ $logoSpa }}"
                    style="
                        max-width:120px;
                        max-height:90px;
                    "
                >
            @else
                <div style="
                    font-size:22px;
                    font-weight:bold;
                ">
                    MAJU GIMENA SALÓN & SPA
                </div>
            @endif

        </td>


        <!-- DATOS EMPRESA -->
        <td style="
            width:45%;
            vertical-align:top;
            padding:12px 8px;
        ">

            <div style="
                font-size:17px;
                font-weight:bold;
                margin-bottom:7px;
            ">
                {{ $company->nombre_comercial ?: $company->razon_social }}
            </div>

            <div style="
                font-size:10px;
                line-height:1.5;
            ">

                <strong>
                    Belleza • Estética • Bienestar
                </strong>

                <br>

                {{ $company->direccion }}

                <br>

                {{ $company->distrito }},
                {{ $company->provincia }}

                @if($company->telefono)
                    <br>
                    TELÉFONO / WHATSAPP:
                    {{ $company->telefono }}
                @endif

                @if($company->email)
                    <br>
                    EMAIL:
                    {{ $company->email }}
                @endif

            </div>

        </td>


        <!-- COMPROBANTE -->
        <td style="
            width:30%;
            vertical-align:middle;
            padding:7px;
        ">

            <div style="
                border:1.5px solid #111;
                border-radius:10px;
                text-align:center;
                padding:15px 7px;
                line-height:1.45;
            ">

                <div style="
                    font-size:12px;
                    font-weight:bold;
                ">
                    RUC {{ $company->ruc }}
                </div>

                <div style="
                    font-size:14px;
                    font-weight:bold;
                    margin-top:5px;
                ">
                    {{ $tipo_documento_nombre }}
                </div>

                <div style="
                    font-size:14px;
                    font-weight:bold;
                    margin-top:5px;
                ">
                    {{ $document->numero_completo }}
                </div>

            </div>

        </td>

    </tr>
</table>

<div style="
    border-bottom:1px solid #444;
    margin-bottom:18px;
"></div>