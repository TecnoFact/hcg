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
    @if($cfdi33 && $customer_invoice->customerInvoiceCfdi->pac->test)
    <h2 style="position: absolute; left: 0; top: -25px; color: red;">*** @lang('general.text_test_cfdi') ***</h2>
    @endif
    <div>
    <table cellpadding="0" cellspacing="0" class="" width="100%" style="">
        <tr>
            <td width="25%" class="" style="vertical-align: top; ">
                @if(!empty(setting('enabled_projects')) && !empty(setting('projects_logo_docs')))
                    <img src="{{ \App\Helpers\Helper::logoCompanyDocsProjects($customer_invoice->company_id,$customer_invoice->project_id) }}" class="invoice-logo" style="max-width: 160px !important;"/>
                @elseif(!empty(setting('branch_office_logo_docs')))
                    <img src="{{ \App\Helpers\Helper::logoCompanyDocsBranchOffices($customer_invoice->company_id,$customer_invoice->branch_office_id) }}" class="invoice-logo" style="max-width: 160px !important;"/>
                @else
                    <img src="{{ \App\Helpers\Helper::logoCompanyDocs($customer_invoice->company_id) }}" class="invoice-logo" style="max-width: 160px !important;"/>
                @endif
            </td>
            <td width="25%" style="vertical-align: top; text-transform: uppercase;">
                @if(!empty($customer_invoice->company->comercial_name))
                <span class="text-primary" style="font-size: 8pt;">{{ mb_strtoupper($customer_invoice->company->comercial_name) }}</span>
                <br/>
                <br/>
                @endif
                <strong>{{ mb_strtoupper($customer_invoice->company->name) }}</strong>
                @if(!empty($customer_invoice->company->address_1) || !empty($customer_invoice->company->address_2) || !empty($customer_invoice->company->address_3) || !empty($customer_invoice->company->address_4))
                    <br/>
                    {{ $customer_invoice->company->address_1 ?? '' }} {{ $customer_invoice->company->address_2 ?? '' }} {{ $customer_invoice->company->address_3 ?? '' }} {{ $customer_invoice->company->address_4 ?? '' }}
                @endif
                @if(!empty($customer_invoice->company->city->name) || !empty($customer_invoice->company->state->name))
                    <br/>
                    {{ !empty($customer_invoice->company->city->name) ? $customer_invoice->company->city->name . ', ' : '' }}{{ $customer_invoice->company->state->name ?? '' }}
                @endif
                <br/>
                {{ $customer_invoice->company->country->name ?? '' }}{!! ($customer_invoice->company->postcode ? '&nbsp;&nbsp;' . __('base/company.entry_postcode') . ': ' . $customer_invoice->company->postcode : '') !!}
                <br/>
                {{ mb_strtoupper($customer_invoice->company->taxid) }}
                <br/>
                ---
                <br/>
                {{ $customer_invoice->company->phone ?? ' ' }}
                <br/>
                <span style="text-transform: lowercase;">{{ $customer_invoice->company->email ?? ' ' }}</span>
            </td>
            <td width="22%" style="vertical-align: top; text-transform: uppercase;">
                <strong>@lang('general.text_issued_in')</strong>
                <br/>
                @if(!empty($customer_invoice->branchOffice->address_1) || !empty($customer_invoice->branchOffice->address_2))
                    <br/>
                    {{ $customer_invoice->branchOffice->address_1 ?? '' }} {{ $customer_invoice->branchOffice->address_2 ?? '' }}
                @endif
                @if(!empty($customer_invoice->branchOffice->address_3) || !empty($customer_invoice->branchOffice->address_4))
                    <br/>
                    {{ $customer_invoice->branchOffice->address_3 ?? '' }} {{ $customer_invoice->branchOffice->address_4 ?? '' }}
                @endif
                @if(!empty($customer_invoice->branchOffice->city->name) || !empty($customer_invoice->branchOffice->state->name))
                    <br/>
                    {{ $customer_invoice->branchOffice->city->name ?? '' }}, {{ $customer_invoice->branchOffice->state->name ?? '' }}
                @endif
                <br/>
                {{ $customer_invoice->branchOffice->country->name ?? '' }}{!! ($customer_invoice->branchOffice->postcode ? '&nbsp;&nbsp;' . __('base/branch_office.entry_postcode') . ': ' . ($cfdi33['LugarExpedicion'] ?? $customer_invoice->branchOffice->postcode) : '') !!}
            </td>
            <td width="28%" class="text-right" style="vertical-align: top;">
                <div>
                <table cellpadding="0" cellspacing="1" class="" width="100%">
                    <tr>
                        <td width="40%" class="cell-primary" style="">@lang('sales/customer_invoice.entry_date')</td>
                        <td class="cell-primary" style="">{{ mb_strtoupper($customer_invoice->documentType->name) }}</td>
                    </tr>
                    <tr>
                        <td class="cell-primary-border-line text-center" style="">{!! str_replace(' ','<br>',\App\Helpers\Helper::convertSqlToDateTime($customer_invoice->date)) !!}</td>
                        <td class="cell-primary-border-line text-center" style="">{{ $customer_invoice->name }}</td>
                    </tr>
                    <tr>
                        <td width="" class="" colspan="2" style="">&nbsp;</td>
                    </tr>
                    <tr>
                        <td width="" class="cell-primary" colspan="2" style="">@lang('general.text_cfdi_uuid')</td>
                    </tr>
                    <tr>
                        <td class="cell-primary-border-line text-center" colspan="2" style="">{{ $customer_invoice->customerInvoiceCfdi->uuid ?? '' }}</td>
                    </tr>
                </table>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td colspan="2" style="padding-top: 13px;">
                {{mb_strtoupper(__('base/company.column_tax_regimen'))}}: {{ $data['tax_regimen'] ?? ($customer_invoice->taxRegimen->name_sat ?? $customer_invoice->company->taxRegimen->name_sat) }}
            </td>
        </tr>
    </table>
    </div>
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 10px;">
            <tr>
                <td width="" class="cell-primary" colspan="2" style="">@lang('general.text_customer')</td>
            </tr>
            <tr>
                <td width="50%" class="cell-primary-border-line text-left" style="vertical-align: top; height: 50px; padding: 5px;">
                    <strong>{{ mb_strtoupper(!empty($cfdi33->Receptor['Nombre']) ? $cfdi33->Receptor['Nombre'] : $customer_invoice->customer->name) }}</strong>
                    <br>
                    {{ mb_strtoupper($cfdi33->Receptor['Rfc'] ?? $customer_invoice->customer->taxid) }}
                </td>
                <td class="cell-primary-border-line text-left" style="vertical-align: top; height: 50px; padding: 5px;">
                    @if(!empty($customer_invoice->customer->address_1) || !empty($customer_invoice->customer->address_2) || !empty($customer_invoice->customer->address_3) || !empty($customer_invoice->customer->address_4))
                        {{ $customer_invoice->customer->address_1 ?? '' }} {{ $customer_invoice->customer->address_2 ?? '' }} {{ $customer_invoice->customer->address_3 ?? '' }} {{ $customer_invoice->customer->address_4 ?? '' }}
                        <br/>
                    @endif
                    @if(!empty($customer_invoice->customer->city->name) || !empty($customer_invoice->customer->state->name))
                        {{ !empty($customer_invoice->customer->city->name) ? $customer_invoice->customer->city->name . ', ' : '' }}{{ $customer_invoice->customer->state->name ?? '' }}
                        <br/>
                    @endif
                    {{ $customer_invoice->customer->country->name ?? '' }}{!! ($customer_invoice->customer->postcode ? '&nbsp;&nbsp;' . __('sales/customer.entry_postcode') . ': ' . $customer_invoice->customer->postcode : '') !!}

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
                @lang('general.text_cfdi')
            </td>
            <td width="25%" class="text-right">
                @lang('general.text_page') <span class="pagenum"></span>
            </td>
        </tr>
    </table>
    </div>
</section>
@if($customer_invoice->status == \App\Models\Sales\CustomerInvoice::CANCEL)
    <section class="watermark" style="bottom: 450px !important;">
        @if(!empty($customer_invoice->customerInvoiceCfdi->uuid))
            <span>@lang('general.text_canceled_cfdi')</span>
        @else
            <span>@lang('general.text_canceled')</span>
        @endif
    </section>
@endif
@if($draft)
    <section class="watermark" style="bottom: 450px !important;">
        <span>@lang('general.text_pre_invoice')</span>
    </section>
@endif
<section>
    <div>
    <table cellpadding="0" cellspacing="0" class="table-items" width="100%">
        <thead>
        <tr>
            <th width="9%">{{ mb_strtoupper(__('sales/customer_invoice.column_line_sat_product_id')) }}</th>
            <th width="7%">{{ mb_strtoupper(__('sales/customer_invoice.column_line_quantity')) }}</th>
            <th class="text-left">{{ mb_strtoupper(__('sales/customer_invoice.column_line_name')) }}</th>
            <th width="14%">{{ mb_strtoupper(__('sales/customer_invoice.column_line_unit_measure_id')) }}</th>
            <th width="9%">{{ mb_strtoupper(__('sales/customer_invoice.column_line_price_unit')) }}</th>
            <th width="7%">{{ mb_strtoupper(__('sales/customer_invoice.column_line_discount')) }}</th>
            <th width="11%">{{ mb_strtoupper(__('sales/customer_invoice.column_line_amount_untaxed')) }}</th>
        </tr>
        </thead>
        <tbody>
        @if($conceptos)
        @foreach($conceptos as $result)
            @php
                //Complemento concepto

            @endphp
            <tr>
                <td class="text-center">{{ $result['ClaveProdServ'] }}</td>
                <td class="text-center">{{ \App\Helpers\Helper::numberFormatRealDecimalPlace($result['Cantidad']) }}</td>
                <td>
                    @if(!empty(setting('show_product_code_on_pdf')))
                        {{$result['NoIdentificacion'] ?? 'N/A'}} -
                    @endif
                    {!! nl2br($result['Descripcion']) !!}
                    @php
                    //Complemento IEDU
                    foreach(($result->ComplementoConcepto)() as $result2){
                        if((string)$result2 == 'instEducativas'){
                            echo '<br/><br/>';
                            echo __('sales/customer_invoice.column_line_iedu_iedu_nombre_alumno') . ': '.$result2['nombreAlumno'].'<br/>';
                            echo __('sales/customer_invoice.column_line_iedu_iedu_curp') . ': '.$result2['CURP'].'<br/>';
                            echo __('sales/customer_invoice.column_line_iedu_iedu_nivel_educativo') . ': '.$result2['nivelEducativo'].'<br/>';
                            echo __('sales/customer_invoice.column_line_iedu_iedu_aut_rvoe') . ': '.$result2['autRVOE'].'<br/>';
                            if(!empty($result2['rfcPago'])){
                                echo __('sales/customer_invoice.column_line_iedu_iedu_rfc_pago') . ': '.$result2['rfcPago'].'<br/>';
                            }
                            break;
                        }
                    }
                    //Numero pedimento
                    foreach(($result)() as $result2){
                        if((string)$result2 == 'InformacionAduanera'){
                            echo '<br/><br/>';
                            echo __('sales/customer_invoice.column_line_numero_pedimento') . ': '.$result2['NumeroPedimento'].'<br/>';
                            break;
                        }
                    }
                    @endphp
                </td>
                <td class="text-center">[{{ $result['ClaveUnidad'] }}] {{ $result['Unidad'] }}</td>
                <td class="text-center">
                            {{ \App\Helpers\Helper::numberFormatMoneyRealDecimalPlace($result['ValorUnitario'], $customer_invoice->currency->code) }}
                        </td>
                <td class="text-center">{{ \App\Helpers\Helper::numberFormatMoney(!empty($result['Descuento']) ? $result['Descuento'] : 0,2, $customer_invoice->currency->code) }}</td>
                <td class="text-right">{{ money($result['Importe'],$customer_invoice->currency->code,true) }}</td>
            </tr>
        @endforeach
        @else
            @foreach($customer_invoice->customerActiveInvoiceLines as $result)
                <tr>
                    <td class="text-center">{{ $result->satProduct->code }}</td>
                    <td class="text-center">{{ \App\Helpers\Helper::numberFormat($result->quantity,$result->unitMeasure->decimal_place) }}</td>
                    <td>
                        @if(!empty(setting('show_product_code_on_pdf')))
                            {{$result->product->code ?? 'N/A'}} -
                        @endif
                        {!! nl2br($result->name) !!}
                    </td>
                    <td class="text-center">{{ $result->unitMeasure->name_sat }}</td>
                    <td class="text-center">
                            {{ \App\Helpers\Helper::numberFormatMoney($result->price_unit,!empty($result->product->price_decimal_place) ? $result->product->price_decimal_place : \App\Helpers\Helper::companyProductPriceDecimalPlace($customer_invoice->company_id), $customer_invoice->currency->code) }}
                        </td>
                    <td class="text-center">{{ $result->discount_type == 'A' ? \App\Helpers\Helper::numberFormatMoney($result->discount,2, $customer_invoice->currency->code) : \App\Helpers\Helper::numberFormatPercentRealDecimalPlace($result->discount) }}</td>
                    <td class="text-right">{{ money($result->amount_untaxed,$customer_invoice->currency->code,true) }}</td>
                </tr>
            @endforeach
        @endif
        </tbody>
        <tfoot>
        <tr>
            <td colspan="7" class="text-left" style="vertical-align: top; height: 50px;">
                {!! nl2br($customer_invoice->comment) !!}
            </td>
        </tr>
        <tr>
            <td colspan="4" class="text-left" style="vertical-align: top;">
                <span style="line-height: 16px;">
                    ***({{\App\Helpers\Helper::numberToWordCurrency($customer_invoice->amount_total,$customer_invoice->currency->code,$customer_invoice->currency->decimal_place)}})***
                </span>
            </td>
            <td colspan="2" class="text-right" style="vertical-align: top; padding-right: 4px;">
                <strong>@lang('general.text_amount_untaxed')</strong><br/>
                @if($customer_invoice->customerInvoiceTaxes->isNotEmpty())
                    @foreach($customer_invoice->customerInvoiceTaxes as $result)
                        <span style="line-height: 16px;"><strong>{{$result->name}}</strong></span><br/>
                    @endforeach
                @endif
                <span style="line-height: 16px;"><strong>{{ mb_strtoupper(__('general.text_amount_total')) }}</strong></span>
            </td>
            <td class="text-right">
                {{ money($customer_invoice->amount_untaxed,$customer_invoice->currency->code,true) }}<br/>
                @if($customer_invoice->customerInvoiceTaxes->isNotEmpty())
                    @foreach($customer_invoice->customerInvoiceTaxes as $result)
                        <span style="line-height: 16px;">{{money(abs($result->amount_tax),$customer_invoice->currency->code,true)}}</span><br/>
                    @endforeach
                @endif
                <span style="line-height: 16px;"><strong>{{ money($customer_invoice->amount_total,$customer_invoice->currency->code,true) }}</strong></span>
            </td>
        </tr>
        </tfoot>
    </table>
    </div>
    @if(!empty($customer_invoice->cfdi_relation_id))
        <div>
            <table cellpadding="0" cellspacing="0" class="table-secundary" width="100%" style="margin-top: 5px; table-layout: fixed;">
                <tr>
                    <td class="cell-primary" colspan="2">@lang('sales/customer_invoice.tab_relations') - {{ $customer_invoice->cfdiRelation->name_sat }}</td>
                </tr>
                <tr>
                    <td width="15%" class="text-center" style="vertical-align: middle; padding: 5px;">
                        @if($customer_invoice->customerInvoiceRelations->isNotEmpty())
                            @foreach($customer_invoice->customerInvoiceRelations as $result)
                                {{$result->relation->name ?? ''}}<br/>
                            @endforeach
                        @endif
                    </td>
                    <td width="85%" style="vertical-align: middle; padding: 5px;">
                        @if($customer_invoice->customerInvoiceRelations->isNotEmpty())
                            @foreach($customer_invoice->customerInvoiceRelations as $result)
                                {{$result->uuid_related}}<br/>
                            @endforeach
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif
    @if($customer_invoice->customerInvoiceTaxLegends->isNotEmpty())
        <div>
            <table cellpadding="0" cellspacing="0" class="table-secundary" width="100%" style="margin-top: 5px; table-layout: fixed;">
                <tr>
                    <td class="cell-primary" colspan="3">@lang('general.text_complement_tax_legend')</td>
                </tr>
                <tr>
                    <td width="20%" class="text-center">
                        <b>@lang('sales/customer_invoice.column_tax_legend_disposicion_fiscal')</b>
                    </td>
                    <td width="25%" class="text-center">
                        <b>@lang('sales/customer_invoice.column_tax_legend_norma')</b>
                    </td>
                    <td width="50%" class="text-left">
                        <b>@lang('sales/customer_invoice.column_tax_legend_texto_leyenda')</b>
                    </td>
                </tr>
                @foreach($customer_invoice->customerInvoiceTaxLegends as $result)
                    <tr>
                        <td class="text-center">{{ $result->disposicion_fiscal }}</td>
                        <td class="text-center">{{ $result->norma }}</td>
                        <td>
                            {!! nl2br($result->texto_leyenda) !!}
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
    @if(!empty($customer_invoice_complement->ine_process_type))
    <div>
        <table cellpadding="0" cellspacing="0" class="table-secundary" width="100%">
            <tr>
                <td class="cell-primary" colspan="2">@lang('general.text_complement_ine')</td>
            </tr>
            <tr>
                <td  width="35%" class="text-left" style="vertical-align: top;">
                    <b>@lang('sales/customer_invoice.entry_ine_process_type'): </b> {{$customer_invoice_complement->ine_process_type}}
                    <br/>
                    <b>@lang('sales/customer_invoice.entry_ine_committee_type'): </b> {{$customer_invoice_complement->ine_committee_type}}
                    <br/>
                    <b>@lang('sales/customer_invoice.entry_ine_id_accounting'): </b> {{$customer_invoice_complement->ine_id_accounting}}
                    <br/>
                </td>
                <td  width="65%" class="text-center">
                    <div>
                        <table cellpadding="0" cellspacing="0" class="table-secundary" width="100%">
                            <tr>
                                <td width="20%" class="text-center" style="padding: 2px;">
                                    <b>@lang('sales/customer_invoice.column_ine_ine_entity')</b>
                                </td>
                                <td width="25%" class="text-center" style="padding: 2px;">
                                    <b>@lang('sales/customer_invoice.column_ine_ambit')</b>
                                </td>
                                <td width="50%" class="text-left" style="padding: 2px;">
                                    <b>@lang('sales/customer_invoice.column_ine_id_accounting')</b>
                                </td>
                            </tr>
                            @foreach($customer_invoice->customerActiveInvoiceInes as $result)
                                <tr>
                                    <td class="text-center" style="padding: 2px;">{{ $result->ine_entity_s }}</td>
                                    <td class="text-center" style="padding: 2px;">{{ $result->ambit }}</td>
                                    <td class="text-left" style="padding: 2px;">
                                        {!! $result->id_accounting !!}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif
    @if(!empty($cfdi33))
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="" class="cell-primary" style="">@lang('general.text_cfdi_tfd_cadena_origen')</td>
            </tr>
            <tr>
                <td width="" class="cell-primary-border-line text-left" style="vertical-align: top; height: 45px; padding: 5px;  word-wrap:break-word;">
                    {{ $data['tfd_cadena_origen'] }}
                </td>
            </tr>
        </table>
    </div>
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="" class="cell-primary" style="">@lang('general.text_cfdi_tfd_sello_cfdi')</td>
            </tr>
            <tr>
                <td width="" class="cell-primary-border-line text-left" style="vertical-align: top; height: 35px; padding: 5px;  word-wrap:break-word;">
                    {{ $cfdi33->complemento->timbreFiscalDigital['SelloCFD'] }}
                </td>
            </tr>
        </table>
    </div>
    <div>
        <table cellpadding="0" cellspacing="0" class="" width="100%" style="margin-top: 5px; table-layout: fixed;">
            <tr>
                <td width="" class="cell-primary" style="">@lang('general.text_cfdi_tfd_sello_sat')</td>
            </tr>
            <tr>
                <td width="" class="cell-primary-border-line text-left" style="vertical-align: top; height: 35px; padding: 5px;  word-wrap:break-word;">
                    {{ $cfdi33->complemento->timbreFiscalDigital['SelloSAT'] }}
                </td>
            </tr>
        </table>
    </div>
    @endif
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
                                @if(!empty($cfdi33))
                                    <img src="{{ $data['qr'] }}" width="120px" style="margin: 5px 0;"/>
                                @endif
                            </td>
                            <td width="18%" class="" style="vertical-align: top; padding-top: 12px;">
                                @lang('base/document_type.entry_cfdi_type_id'):<br>
                                @lang('sales/customer_invoice.entry_date'):<br>
                                @lang('sales/customer_invoice.entry_date_due'):<br>
                                @if(!empty($cfdi33))
                                @lang('general.text_cfdi_tfd_fecha_timbrado'):<br>
                                @lang('general.text_cfdi_certificado'):<br>
                                @lang('general.text_cfdi_tfd_no_certificado_sat'):<br>
                                @endif
                                @lang('sales/customer_invoice.entry_payment_way_id'):<br>
                                @lang('sales/customer_invoice.entry_payment_method_id'):<br>
                                @lang('sales/customer_invoice.entry_payment_term_id'):<br>
                                @lang('sales/customer_invoice.entry_cfdi_use_id'):<br>
                                @if(!empty($customer_invoice->origin))
                                    @lang('sales/customer_invoice.entry_origin'):<br/>
                                @endif
                            </td>
                            <td width="" class="" style="vertical-align: top; padding-top: 12px;">
                                {{ $customer_invoice->documentType->cfdiType->name_sat ?? '' }}<br>
                                {{\App\Helpers\Helper::convertSqlToDateTime($customer_invoice->date)}}<br>
                                {{ \App\Helpers\Helper::convertSqlToDate($customer_invoice->date_due) }}<br>
                                @if(!empty($cfdi33))
                                {{ \App\Helpers\Helper::convertSqlToDateTime(str_replace('T',' ',$cfdi33->complemento->timbreFiscalDigital['FechaTimbrado'])) }}<br>
                                {{ $cfdi33['NoCertificado'] }}<br>
                                {{ $cfdi33->complemento->timbreFiscalDigital['NoCertificadoSAT'] }}<br>
                                @endif
                                {{ $customer_invoice->paymentWay->name_sat }}<br>
                                {{ $customer_invoice->paymentMethod->name_sat }}<br>
                                {{ $customer_invoice->paymentTerm->name }}<br>
                                {{ $customer_invoice->cfdiUse->name_sat }}<br>
                                @if(!empty($customer_invoice->origin))
                                    {{ $customer_invoice->origin }}<br/>
                                @endif
                            </td>
                            <td width="7%" class="" style="vertical-align: top; padding-top: 12px;">
                                @lang('sales/customer_invoice.entry_currency_id'):<br>
                                @lang('sales/customer_invoice.entry_currency_value'):<br>
                            </td>
                            <td width="10%" class="text-left" style="vertical-align: top; padding-top: 12px;">
                                {{ $customer_invoice->currency->code }}<br>
                                {!! ($customer_invoice->currency->code!='MXN' ? round($customer_invoice->currency_value,4) :'') !!}<br>
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
