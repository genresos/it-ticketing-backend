<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Email Notify</title>
    <style>
        body {
            background-color: #bdc3c7;
            margin: 0;
        }

        .card {
            background-color: #fff;
            padding: 20px;
            margin: 20%;
            text-align: left;
            margin: 0px auto;
            width: 1280px;
            max-width: 1280px;
            margin-top: 10%;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
        }

        .cardfooter {
            background-color: #fff;
            padding: 20px;
            margin: 20%;
            text-align: center;
            margin: 0px auto;
            width: 580px;
            max-width: 580px;
            margin-top: 10%;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
        }

        .garis {
            width: 75%;
        }
    </style>
</head>

<body>
    <div class="card">
        <p><span style='font-family: "Lucida Console", Monaco, monospace; font-size: 19px;'>Dear Recepients, email berikut terkait PO yang Workend date sudah due date dan belum ada Invoice. Diantaranya :</span></p>
        <p><span style='font-family: "Lucida Console", Monaco, monospace; font-size: 19px;'>No PO :&nbsp;</span></p>

        @foreach($details as $data => $val)

        <p><span style="font-family: 'Lucida Console', Monaco, monospace;"><strong>{{ $val['no_so'] }} - Kode Project : {{ $val['project_code'] }}</strong></span></p>

        <p><span style='font-family: "Lucida Console", Monaco, monospace; font-size: 19px;'>Item :&nbsp;</span></p>

        @foreach($val['items'] as $items => $itm)

        <p><span style="font-family: 'Lucida Console', Monaco, monospace;">
                <strong> ● {{ $itm['description'] }} - {{ $itm['site_id'] }} - {{ $itm['site_name'] }}</strong>
            </span></p>
        @endforeach

        @endforeach

        <p><br></p>
        <p><span style='font-family: "Lucida Console", Monaco, monospace; font-size: 19px;'>Silakan di follow up untuk nomor-nomor PO tersebut. Terimakasih.</span></p>
        <p><br></p>
        <p><br></p>

        <p><span style='font-family: "Lucida Console", Monaco, monospace; font-size: 11px;'>#automatically sent from Adyawinsa app.</span></p>
    </div>
</body>
<footer>
    <div class="cardfooter">
        <p>&copy; 2023 PT. Adyawinsa Telecommunication & Electrical</p>
    </div>
</footer>

</html>