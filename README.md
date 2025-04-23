# Dogehathon Order API

### Requirements:
- PHP 8.X
- MariaDB or MySQL (Latest)
- SMTP Server
- GigaWallet Server

 ### Create a MariaDb/MySQL Database and User and the script will auto verify if exists and create the DB Table and fields if needed
 ### Configuration needed on conf.php to connect to all servers 
 ### Subscribe on GigaWallet for PAYMENT_RECEIVED to URL/callback/ to update payments

 ### Add this to load the DogeAddress validator and the data form to be submited
```
    <!-- Load Dogecoin Address validator from Patrick -->
    <script src="inc/vendors/bs58caddr.bundle.min.js"></script>
    
    <!-- Load Custom JavaScript -->
    <script src="js/dogeathon.js"></script>
```

### Configuration needed

```
// Order host URL
$config['orderHost'] = 'https://localhost/order/';

// Dogeathon Fee
$config["fee"] = 269;

// GigaWallet Server configuration
// Attenttion **
// Subscribe on GigaWallet for INV_TOTAL_PAYMENT_DETECTED to /inc/callback/ to update payments
// Attenttion **
$config['GigaServer'][0] = 'localhost'; // admin server
$config['GigaPort'][0] = 420; // admin server port
$config['GigaServer'][1] = 'localhost'; // public server
$config['GigaPort'][1] = 69; // public server port
$config['GigaDust'] = 0; // GigaWallet deduct dust to the payment to be able to send it successfull because of network fees
$config['payout_address'] = 'Dxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Dogecoin payout address to move to a secure wallet

// MariaDB Server configuration
$config['dbHost'] = 'localhost';
$config['dbUser'] = 'suchuser';
$config['dbPass'] = 'suchpass';
$config['dbName'] = 'dogeathon';
$config['dbPort'] = 3306;

// SMTP Email Server Configuration
$config['email_name_from'] = 'Dogeathon Portugal'; // name to show on all emails sent
$config['email_from'] = 'no-reply@localhost'; // email to show and reply on all emails sent
$config['email_reply_to'] = 'no-reply@localhost'; // email to reply
$config['email_port'] = 465; // SSL 465 / TLS 587
$config['email_username'] = 'suchuser';
$config['email_password'] = 'suchpass';
$config['email_stmp'] = 'localhost';

```

### To run a test open your browser on https://localhost/tests/