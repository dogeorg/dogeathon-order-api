<?php

$logo = "<img src='".$this->config["orderHost"]."img/dogeathon-email.png' style='width:100%;' alet='dogeathon' />";
$mail_subject = "Much Wow! Dogeathon Payment Fee Confirmed!";

$mail_message = <<<EOD
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: rgb(20, 22, 24);
            font-family:  'Comic Sans MS', 'Comic Sans', cursive;
            color: #ffffff !important;
        }
        .email-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: rgb(20, 22, 24);
        }
        .email-header {
            text-align: center;
            padding: 5px 0;
        }
        .email-body {
            padding: 0 20px 20px 20px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header with centered logo -->
        <div class="email-header">
            $logo
        </div>

        <!-- Email body with white text -->
        <div class="email-body">
            <h2>Hello $name</h2>
            Your payment for Dogeathon Portugal has been received.
            <br><br>
            Dont forget to visit <a href="https://dogecoin.com/dogeathon" target="_blank">https://dogecoin.com/dogeathon</a> for all details.
            <br><br>
            Need help? Contact us at <a href="mailto:hackathon@dogecoin.org">hackathon@dogecoin.org</a>
            <br><br>
            Much Thanks!
        </div>
    </div>
</body>
</html>
EOD;

?>