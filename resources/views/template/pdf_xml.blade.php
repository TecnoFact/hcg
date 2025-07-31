<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <title>CFDI-{{$uuid}}</title>
    <link href="{{ public_path('css/custom_pdf.css') }}" rel="stylesheet">
    <style type="text/css">
        @page {
            margin-top: 285px;
            margin-bottom: 27px;
            margin-left: 23px;
            margin-right: 23px;
        }

        .header h1 {
            margin: 0;
        }

        .footer {
            bottom: 15px;
            font-size: 7pt;
            color: #333333;
        }

        .pagenum:before {
            content: counter(page);
        }

        .text-primary {
            color: #2299dd!important;
        }

        .table-items thead th {
            background-color: #2299dd!important;
        }
        .cell-primary {
            border: 1px solid #2299dd!important;
            background-color: #2299dd!important;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 2px;
            text-align: center !important;
            vertical-align: middle !important;
            text-transform: uppercase;
            font-size: 8pt;
        }
        .cell-primary-border-line {
            border: 1px solid #2299dd!important;
            border-radius: 0 0 3px 3px;
            padding: 5px 1px;
        }
    </style>
</head>
<body style="background-color: white;">

<section class="header" style="top: -257px;">



    <div>
    <table cellpadding="0" cellspacing="0" class="" width="100%" style="">
        <tr>
            <td width="25%" class="" style="vertical-align: top; ">
                @if($emisor->logo)
                <img src="{{ $logo }}" class="invoice-logo" style="max-width: 160px !important;"/>
                @endif
            </td>
            <td width="25%" style="vertical-align: top; text-transform: uppercase;">
                <br/>
            </td>

            <td width="22%" style="vertical-align: top; text-transform: uppercase;">
                <br/>
            </td>

            <td width="28%" class="text-right" style="vertical-align: top;">
                <div>
                <table cellpadding="0" cellspacing="1" class="" width="100%">
                    <tr>
                        <td class="cell-primary-border-line text-center" style=""> <strong>Fecha: </strong>{!! str_replace(' ','<br>',$fecha) !!}</td>
                        <td class="cell-primary-border-line text-center" style="">{{ $customer_invoice->id }}</td>
                    </tr>
                    <tr>
                        <td width="" class="" colspan="2" style="">&nbsp;</td>
                    </tr>
                    <tr>
                        <td width="" class="cell-primary" colspan="2" style="">UUID</td>
                    </tr>
                    <tr>
                        <td class="cell-primary-border-line text-center" colspan="2" style="">{{ $uuid ?? '' }}</td>
                    </tr>
                </table>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td colspan="2" style="padding-top: 13px;">
                {{mb_strtoupper("Regimen Fiscal:")}}: {{ $emisorRegimenFiscal }}
            </td>
        </tr>
    </table>
    </div>
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 10px;">
            <tr>
                <td width="" class="cell-primary" colspan="2" style="">Cliente</td>
            </tr>
            <tr>
                <td width="50%" class="cell-primary-border-line text-left" style="vertical-align: top; height: 50px; padding: 5px;">
                    <strong>{{ mb_strtoupper($receptorNombre) }}</strong>
                    <br>
                    {{ mb_strtoupper($receptorRfc) }}
                </td>

                <td class="cell-primary-border-line text-left" style="vertical-align: top; height: 50px; padding: 5px;">
                    <br>
                </td>
            </tr>
        </table>
    </div>
</section>
<section class="footer">
    <hr>
    <div>
    <table cellpadding="0" cellspacing="0" class="" width="100%">
        <tr>
            <td width="25%">
                <span>{{!empty(config('app.cfdi_footer')) ? config('app.cfdi_footer')  : config('app.name') . ' ' . 'v.' . config('app.version') }}</span>
            </td>
            <td width="50%" class="text-center">
                CFDI
            </td>
            <td width="25%" class="text-right">
                Página <span class="pagenum"></span>
            </td>
        </tr>
    </table>
    </div>
</section>

<section>
    <div>
    <table cellpadding="0" cellspacing="0" class="table-items" width="100%">
        <thead>
        <tr>
            <th width="9%">{{ mb_strtoupper('ID') }}</th>
            <th width="7%">{{ mb_strtoupper('Cantidad') }}</th>
            <th class="text-left">{{ mb_strtoupper('Descripción') }}</th>
            <th width="14%">{{ mb_strtoupper('Unidad de Medida') }}</th>
            <th width="9%">{{ mb_strtoupper('Precio Unitario') }}</th>
            <th width="7%">{{ mb_strtoupper('Descuento') }}</th>
            <th width="11%">{{ mb_strtoupper('Importe') }}</th>
        </tr>
        </thead>
        <tbody>
        @if($conceptos)
            @foreach($conceptos as $result)
                <tr>
                    <td class="text-center">{{ $result['clave'] }}</td>
                    <td class="text-center">{{ $result['cantidad'] }}</td>
                    <td>
                        {!! nl2br($result['descripcion']) !!}

                    </td>
                    <td class="text-center">[{{ $result['claveUnidad'] }}] {{ $result['unidad'] }}</td>
                    <td class="text-center">
                                {{ money($result['valorUnitario'] ?? 0) }}
                            </td>
                    <td class="text-center">{{ !empty($result['descuento']) ? $result['descuento'] : 0 }}</td>
                    <td class="text-right">{{ money($result['importe'] ?? 0) }}</td>
                </tr>
            @endforeach
        @endif
        </tbody>
        <tfoot>
        <tr>
            <td colspan="7" class="text-left" style="vertical-align: top; height: 50px;">
                -------------------------------------------------------
            </td>
        </tr>
        <tr>
            <td colspan="4" class="text-left" style="vertical-align: top;">
                <span style="line-height: 16px;">
                    ***({{ NumberToWords\NumberToWords::transformNumber('es', $customer_invoice->total) }})***
                </span>
            </td>
            <td colspan="2" class="text-right" style="vertical-align: top; padding-right: 4px;">
                <strong>SubTotal</strong><br/>
               {{--
                @if($customer_invoice->impuestos)
                    @foreach($customer_invoice->impuestos as $result)
                        <span style="line-height: 16px;"><strong>{{$result->name}}</strong></span><br/>
                    @endforeach
                @endif
               --}}
                <span style="line-height: 16px;"><strong>{{ mb_strtoupper('Total') }}</strong></span>
            </td>
            <td class="text-right">
                {{ money($customer_invoice->subTotal ?? 0) }}<br/>
                {{--
                @if($customer_invoice->impuestos)
                    @foreach($customer_invoice->impuestos as $result)
                        <span style="line-height: 16px;">{{ abs($result->amount_tax) }}</span><br/>
                    @endforeach
                @endif
                 --}}
                <span style="line-height: 16px;"><strong>{{ money($customer_invoice->total ?? 0) }}</strong></span>
            </td>
        </tr>
        </tfoot>
    </table>
    </div>
    @if(!empty($customer_invoice->cfdi_relation_id))
        <div>
            <table cellpadding="0" cellspacing="0" class="table-secundary" width="100%" style="margin-top: 5px; table-layout: fixed;">
                <tr>
                    <td class="cell-primary" colspan="2">Relacion - {{ $customer_invoice->cfdiRelation->name_sat }}</td>
                </tr>
                <tr>
                    <td width="15%" class="text-center" style="vertical-align: middle; padding: 5px;">
                       {{--
                        @if($customer_invoice->customerInvoiceRelations)
                            @foreach($customer_invoice->customerInvoiceRelations as $result)
                                {{$result->relation->name ?? ''}}<br/>
                            @endforeach
                        @endif
                         --}}
                    </td>
                    <td width="85%" style="vertical-align: middle; padding: 5px;">
                        {{--
                        @if($customer_invoice->customerInvoiceRelations)
                            @foreach($customer_invoice->customerInvoiceRelations as $result)
                                {{$result->uuid_related}}<br/>
                            @endforeach
                        @endif
 --}}
                    </td>
                </tr>
            </table>
        </div>
    @endif


    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="" class="cell-primary" style="">Cadena Original</td>
            </tr>
            <tr>
                <td width="" class="cell-primary-border-line text-left" style="vertical-align: top; height: 45px; padding: 5px;  word-wrap:break-word;">
                    {{ $cadenaOrigen }}
                </td>
            </tr>
        </table>
    </div>
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="" class="cell-primary" style="">Sello CFDI</td>
            </tr>
            <tr>
                <td width="" class="cell-primary-border-line text-left" style="vertical-align: top; height: 35px; padding: 5px;  word-wrap:break-word;">
                    {{ $selloCFD }}
                </td>
            </tr>
        </table>
    </div>
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="" class="cell-primary" style="">Sello SAT</td>
            </tr>
            <tr>
                <td width="" class="cell-primary-border-line text-left" style="vertical-align: top; height: 35px; padding: 5px;  word-wrap:break-word;">
                    {{ $selloSAT }}
                </td>
            </tr>
        </table>
    </div>

    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="100%" class="cell-primary" style=""></td>
            </tr>
            <tr>
                <td width="100%" class="cell-primary-border-line text-left" style="vertical-align: top; padding: 5px;  word-wrap:break-word;">
                    <table cellpadding="0" cellspacing="0" class="" width="100%" style="table-layout: fixed;">
                        <tr>
                            <td width="20%" class="text-center" style="vertical-align: top;">
                                <img src="{{ $qr }}" width="120px" style="margin: 5px 0;"/>
                            </td>
                            <td width="18%" class="" style="vertical-align: top; padding-top: 12px;">
                               Tipo:<br>
                                Fecha de Entrada:<br>


                                Fecha de Timbrado:<br>
                                Certificado:<br>
                                No. de Certificado SAT:<br>

                                Forma de Pago:<br>
                                Método de Pago:<br>
                                Lugar de Expedición:<br>

                            </td>
                            <td width="" class="" style="vertical-align: top; padding-top: 12px;">
                                {{ $customer_invoice->documentType->cfdiType->name_sat ?? '' }}<br>
                                {{ $fecha }}<br>

                                {{ str_replace('T',' ',$fechaTimbrado) }}<br>
                                {{ $noCertificado }}<br>
                                {{ $data['complemento']['timbreFiscalDigital']['NoCertificadoSAT'] }}<br>

                                {{ $data['FormaPago'] }}<br>
                                {{ $data['MetodoPago'] }}<br>
                                {{ $data['LugarExpedicion'] }}<br>

                            </td>
                            <td width="7%" class="" style="vertical-align: top; padding-top: 12px;">
                                Moneda:<br>
                                Tipo de Cambio:<br>
                            </td>
                            <td width="10%" class="text-left" style="vertical-align: top; padding-top: 12px;">
                                {!! 'MXN'  !!}<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</section>
</body>
</html>
