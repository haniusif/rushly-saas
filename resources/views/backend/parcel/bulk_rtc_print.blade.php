<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title> {{ ($report_title) ? $report_title : date('Y-m-d') }}</title>
    <link rel="shortcut icon" href="{{ asset(settings()->favicon_image)}}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('backend/')}}/css/bulk_print.css">
    
    <style>
    
    @media print {
    .no-print {
        display: none !important;
    }
}
    table.table {
        border-collapse: collapse;
        width: 100%;
        font-family: Arial, sans-serif;
        font-size: 14px;
    }

    table.table th,
    table.table td {
        border: 1px solid #333;
        padding: 8px;
        text-align: left;
    }

    table.table thead {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    table.table tfoot td {
        font-weight: bold;
        background-color: #f9f9f9;
    }

    tr.odd {
        background-color: #f9f9f9;
    }

    .table-responsive {
        overflow-x: auto;
    }
</style>

</head>
<body>
    <div class="print" style="text-align: right" >
    

        <button onclick="window.print();" class="btn-danger no-print">{{ __('Print') }}</button>

        @if(isset($reprint))
        
            <button onclick="window.close();" class="btn-danger no-print">{{ __('levels.save') }} & {{ __('levels.cancel') }}</button>
        @else
            <button href="{{ route('parcel.index') }}" class="btn-danger">{{ __('levels.cancel') }}</button>
        @endif
    </div>
    <div>
        <table width="100%" border="0" align="center" cellpadding="0" cellspacing="0" class="officehead">
            <tbody>
            <tr>
                <td class="left-col" style="height: 70px;  ">
                    <img alt="Logo" src="{{ asset(settings()->logo_image)}}" class="logo" style="max-height: 70px;">
                </td>
                
            </tr>
            </tbody>
        </table>
    </div>
    <div class="card" >
        <div class="card-body" >
            <div class="invoice" id="printablediv">
                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12  ">
                        <h1 style="text-align: center"> {{ __("RTC Runsheet") }}</h1>
                    </div>
                </div>
                <div class="row mt-3" style="width:100%">
                    <div class="col-sm-12 " style="overflow: hidden">
                        <span  style="float: left">
                            <font style="font-weight: bold">{{ __('Client') }} :</font>  <small>...............................</small>
                        </span>
                        <span style="float: right" > <font style="font-weight: bold">Date :</font>  {{ dateFormat(date('Y-m-d')) }}</span>
                    </div>
                </div>
                <hr>
                <div class="row" style="margin-top: 20px">
                    <div class="col-sm-12 table-responsive">
                        <table class="table table-striped" style="width: 100%;">
                            <thead class="tablehead">
                                <tr style="text-align: left;">
                                    <th >#</th>
                                    <th  >{{ __('merchant.track_id') }}</th>
                                    <th  >{{ __('Reference NO.') }}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i=0;
                                @endphp
                                @foreach($parcels as $key => $parcel)
                                    <tr @if($i%2 == 0) class="odd" @endif>
                                        <td data-label="#">{{ ++$i }}</td>
                                         <td data-label="tracking_id" >
                                             
                                             <?php 
                                             $bolded = preg_replace('/(\d{4})(?!.*\d)/', '<strong>$1</strong>', $parcel->tracking_id);
                                             ?>
                                            #{!! $bolded !!}
                                            <br>
                                              <?php
                $awb = "$parcel->id";
                echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($awb, 'C128') . '" alt="barcode" />';
            ?>
                                        </td>
                                         
                                        <td data-label="reference_number ">{{ $parcel->reference_number }}</td>

                                    </tr>

                                @endforeach
                            </tbody>
                         
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.print();
    </script>
</body>
</html>
