<!DOCTYPE html>
<html lang="en">
<style>
    .p3 {
        font-family: "Lucida Console", "Courier New", monospace;
        font-size: 40px;
    }
</style>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="container">

        @foreach($response as $data => $val)

        <center>
            <p class="font-weight-bolder" style="font-size:32px;font-family: 'Times New Roman', 'Times' , serif">{{ $val['project_code'] }} : {{ $val['counter'] }}/{{ $val['total_item'] }}</p>

            <img style="display: block; margin-left: auto; margin-right: auto;" src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&amp;data={{ $val['item_code'] }}_{{ $val['grn_batch_id'] }}_{{ $val['project_code'] }}_{{ $val['po_detail_item'] }}_{{ $val['counter'] }}" />
        </center>
        <br>

        <center>
            <p class="font-weight-bolder" style="font-size:32px;font-family: 'Times New Roman', 'Times' , serif">{{ Illuminate\Support\Str::limit(  $val['description']  , 30)}}</p>
        </center>
        @endforeach

    </div>
</body>

</html>