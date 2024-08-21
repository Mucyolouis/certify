div<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baptism Certificate</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 50px;
            height: 50px;
            background-color: #ccc; /* Placeholder for logo */
        }
        .title {
            font-weight: bold;
            margin: 10px 0;
        }
        .form-row {
            margin-bottom: 10px;
        }
        .form-row label {
            display: inline-block;
            width: 150px;
        }
        .form-row .input {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: calc(100% - 160px);
        }
        .footer {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo"></div>
        <div class="title">URUREMBO RWA GICUMBI</div>
        <div>PARUWASE - TUMBA</div>
        <div>Tel : 0788744505</div>
        <div>E-mail : ndeprtumba@gmail.com</div>
    </div>

    <div class="title">ICYEMEZO CY'URUBATIZO RW'UMUKRISITO No</div>

    <div class="form-row">
        <label>Twabatije Pasitori</label>
        <span class="input">{{ $baptism->pastor_name }}</span>
    </div>

    <div class="form-row">
        <label>Umushamba wa</label>
        <span class="input">{{ $baptism->church_name }}</span>
    </div>

    <!-- Add more form rows for other fields -->

    <div class="footer">
        <div>Bikorewe i ............................ kuwa ...../...../20....</div>
        <div>Umuyobozi w'Itorero rya ...........................</div>
        <div>Umushamba wa Paruwase ya TUMBA</div>
        <div>Amazina: Rev. NDAHIMANA Innocent</div>
        <div>Telefone: +250788744505</div>
        <div>E-mail: ndahimanainnocent1974@gmail.com</div>
        <div>Umukono: .............................</div>
    </div>
    <div>
        {!! DNS2D::getBarcodeHTML("$baptism->id",'QRCODE',4,4) !!}
    </div>
    <!-- Include Bootstrap JS (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
