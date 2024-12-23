<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        /* Receipt paper size and styling */
        body {
            width: 80mm; /* Typical receipt width */
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
        }
        .receipt {
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h2 {
            margin: 0;
        }
        .line {
            border-top: 1px dashed black;
            margin: 10px 0;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
        }
        .items td {
            padding: 5px 0;
        }
        .items .total-row {
            font-weight: bold;
            border-top: 1px solid black;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h2>Your Store Name</h2>
            <p>Address Line 1<br>Address Line 2<br>Phone: 123-456-7890</p>
        </div>
        <div class="line"></div>
        <table class="items">
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['quantity'] }} x {{ $item['price'] }}</td>
                    <td style="text-align: right;">{{ $item['quantity'] * $item['price'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">Total:</td>
                    <td style="text-align: right;">{{ $total }}</td>
                </tr>
            </tfoot>
        </table>
        <div class="line"></div>
        <div class="footer">
            <p>Thank you for your purchase!</p>
        </div>
    </div>

                        <button onclick="window.print()" class="btn btn-primary btn-sm ms-3">Print Only</button>

</body>
</html>
