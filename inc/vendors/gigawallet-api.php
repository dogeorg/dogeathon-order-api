<?php
// include configurations
include("../conf.php");

// GigaWallet Bridge
class GigaWalletBridge {

    private $config; 
    public function __construct($config) {
        $this->config = $config;
        $this->firstrunDB();
    }

    // Check DB and create Table & Import Structure if not exists
    private function firstrunDB() {

        try {        
            $conn = new mysqli($this->config["dbHost"], $this->config["dbUser"], $this->config["dbPass"], $this->config["dbName"], $this->config["dbPort"]);
        
            if ($conn->connect_error) {
                die("Much Sad, Connection failed: " . $conn->connect_error);
            }
        
            // Check if the `shibes` table exists
            $tableExists = $conn->query("SHOW TABLES LIKE 'shibes'");
            if ($tableExists->num_rows == 0) {
                // SQL to create the `shibes` table
                $sql = <<<SQL
                    CREATE TABLE `shibes` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(255) DEFAULT NULL,
                        `email` varchar(255) DEFAULT NULL,
                        `country` varchar(255) DEFAULT NULL,                                                
                        `github` varchar(255) DEFAULT NULL,
                        `x` varchar(255) DEFAULT NULL,
                        `dogeAddress` varchar(255) DEFAULT NULL,
                        `amount` decimal(20,8) DEFAULT NULL,
                        `PaytoDogeAddress` varchar(255) DEFAULT NULL,
                        `paid` tinyint(1) DEFAULT NULL,
                        `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                SQL;
        
                // Execute the query
                if ($conn->query($sql) === TRUE) {
                    //echo "Much wow, Table `shibes` created successfully.\n";
                } else {
                    die("Much Sad, Error creating table: " . $conn->error);
                }
            } else {
                //echo "Much wow, Table `shibes` already exists.\n";
            }
        
            $conn->close();
        } catch (Exception $e) {
            echo " - DB Test Failled ❌\n";
        }        
    }
    


    // Test DB
    public function testDbConnection() {
        
        try {
            $conn = new mysqli($this->config["dbHost"], $this->config["dbUser"], $this->config["dbPass"], $this->config["dbName"], $this->config["dbPort"]);
    
            // Check connection
            if ($conn->connect_error) {
                echo " - DB Test Failled\n";
            }else{
                echo " - DB Test Passed ✅\n";
            }
    
        } catch (Exception $e) {
            echo " - DB Test Failled ❌\n";
        }
        
    }

    // Test GigaWallet
    public function testGigaWallet() {
        
        try {
            $gigawallet = json_decode($this->GetInvoices('suchdummy',null), true);
            if (isset($gigawallet['error'])) {
                echo " - GigaWallet Test Passed ✅\n";
            } else {
                echo " - GigaWallet Test Failled ❌\n";
            }
        } catch (Exception $e) {
            echo " - GigaWallet Test Failled ❌\n";
        }

    }       

    // Test SMTP
    public function testtSMTPConnection() {

        try {
            $email = $this->mailx("test@dogecoin.com",$this->config["email_from"],$this->config["email_name_from"],$this->config["email_username"],$this->config["email_password"],$this->config["email_port"],$this->config["email_stmp"],"test","test");
            if ($email != "Error") {
                echo " - SMTP Test Passed ✅\n";
            } else {
                echo " - SMTP Test Failled ❌\n";
            }
        } catch (Exception $e) {
            echo " - SMTP Test Failled ❌\n";
        }

    }     

    // Connects to MariaDB
    public function getDbConnection() {
    
        try {
            $conn = new mysqli($this->config["dbHost"], $this->config["dbUser"], $this->config["dbPass"], $this->config["dbName"], $this->config["dbPort"]);
    
            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
    
            return $conn;
        } catch (Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    // Insert Shibe
    public function insertShibe($name, $email, $country, $github, $x, $dogeAddress, $amount, $paytoDogeAddress) {
        try {
            $conn = $this->getDbConnection();
            
            // First check if the table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'shibes'");
            if ($tableCheck->num_rows == 0) {
                // Create the table if it doesn't exist
                $createTable = "CREATE TABLE shibes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255),
                    country VARCHAR(255),
                    github VARCHAR(255),
                    x VARCHAR(255),
                    dogeAddress VARCHAR(255),
                    amount DECIMAL(20,8),
                    paytoDogeAddress VARCHAR(255),
                    paid TINYINT(1) DEFAULT 0,
                    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                if (!$conn->query($createTable)) {
                    throw new Exception("Error creating table: " . $conn->error);
                }
            }

            $sql = "INSERT INTO shibes (name, email, country, github, x, dogeAddress, amount, paytoDogeAddress) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            // Prepare the statement
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }

            // Bind parameters
            $stmt->bind_param("ssssssds", $name, $email, $country, $github, $x, $dogeAddress, $amount, $paytoDogeAddress);
            
            // Execute the statement
            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Error executing statement: " . $stmt->error);
            }

            // Close statement and connection
            $stmt->close();
            $conn->close();

            // Send email
            try {
                include("../mail/order_template.php");
                $emailResult = $this->mailx(
                    $email,
                    $this->config["email_from"],
                    $this->config["email_name_from"],
                    $this->config["email_username"],
                    $this->config["email_password"],
                    $this->config["email_port"],
                    $this->config["email_stmp"],
                    $mail_subject,
                    $mail_message
                );
                
                if ($emailResult === "Error") {
                    error_log("Failed to send email to: " . $email);
                }
            } catch (Exception $e) {
                error_log("Email error: " . $e->getMessage());
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error in insertShibe: " . $e->getMessage());
            throw $e;
        }
    }

    // Update Dogecoin Payment on Shibe
    public function updateDogePaidStatus($paytoDogeAddress) {
        $conn = $this->getDbConnection();
    
        $stmt = $conn->prepare("UPDATE shibes SET paid = 1 WHERE PaytoDogeAddress = ?");
        $stmt->bind_param("s", $paytoDogeAddress);
    
        $stmt->execute();
        $stmt->close();
        $conn->close();

        // We get the name and email to send the payment confirmation email
        $stmt = $conn->prepare("SELECT name, email, dogeAddress FROM shibes WHERE PaytoDogeAddress = ?");
        $stmt->bind_param("s", $paytoDogeAddress);
        $stmt->execute();

        // Fetch the record details
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();

        // Get name and email from shibe
        $name = $record['name'];
        $email = $record['email'];
        $dogeAddress = $record['dogeAddress'];

        // Close the statement and connection
        $stmt->close();
        $conn->close();

        // We include the email payment template
        include("../mail/payment_template.php");
        
        $this->mailx($email,$this->config["email_from"],$this->config["email_name_from"],$this->config["email_username"],$this->config["email_password"],$this->config["email_port"],$this->config["email_stmp"],$mail_subject,$mail_message);            
    }

    // Creates/Gets a GigaWallet Account
    public function account($foreign_id,$payout_address = NULL,$payout_threshold = 0,$payout_frequency = 0,$method = "POST") {

        // Builds the Gigawallet Command
        //$command = "/account/" . $foreign_id . "/" . $payout_address . "/" . $payout_threshold . "/" . $payout_frequency;
        $command = "/account/" . $foreign_id;
        $data["payout_address"] = $payout_address; // address to receive payments
        $data["payout_threshold"] = strval($payout_threshold); // minimum doge value to reach to then send the payment
        $data["payout_frequency"] = strval($payout_frequency); // wen do we want the payment to be sent        

        // Sends the GigaWallet Command
        return $this->sendGigaCommand($this->config["GigaServer"][0] . ":" . $this->config["GigaPort"][0] . $command, $method, $data);
    }

    // Gets a GigaWallet Account Balance
    public function accountBalance($foreign_id) {

        // Builds the Gigawallet Command
        $command = "/account/" . $foreign_id . "/balance";

        // Sends the GigaWallet Command
        return $this->sendGigaCommand($this->config["GigaServer"][0] . ":" . $this->config["GigaPort"][0] . $command, 'GET', NULL);
    }    

    // Creates a GigaWallet Invoice
    public function invoice($foreign_id,$data) {

        // Builds the Gigawallet Command
        $command = "/account/" . $foreign_id . "/invoice/";

        // Sends the GigaWallet Command
        return $this->sendGigaCommand($this->config["GigaServer"][0]. ":" . $this->config["GigaPort"][0] . $command, 'POST', $data);
    } 

    // Gets one GigaWallet Invoice
    public function GetInvoice($foreign_id,$invoice_id) {

        // Builds the Gigawallet Command
        $command = "/account/".$foreign_id."/invoice/" . $invoice_id . "";

        // Sends the GigaWallet Command
        return $this->sendGigaCommand($this->config["GigaServer"][0] . ":" . $this->config["GigaPort"][0] . $command, 'GET', NULL);
    }      

    // Gets all GigaWallet Invoices from that shibe
    public function GetInvoices($foreign_id,$data) {

        // Builds the Gigawallet Command
        $command = "/account/" . $foreign_id . "/invoices?cursor=".$data["cursor"]."&limit=".$data["limit"]."";
        $data = null;
        // Sends the GigaWallet Command
        return $this->sendGigaCommand($this->config["GigaServer"][0] . ":" . $this->config["GigaPort"][0] . $command, 'GET', $data);
    }      

    // Gets a GigaWallet QR code Invoice
    public function qr($invoice,$fg = "000000",$bg = "ffffff") {

        // Builds the Gigawallet Command
        $command = "/invoice/" . $invoice . "/qr.png?fg=".$fg."&bg=".$bg;

        // Sends the GigaWallet Command
        return  $this->sendGigaCommand($this->config["GigaServer"][1] . ":" . $this->config["GigaPort"][1] . $command, 'GET');
    } 
    
    // Pay to an address
    public function PayTo($foreign_id,$data) {

        // Builds the Gigawallet Command
        $command = "/account/" . $foreign_id . "/pay";

        // Deduct dust to the payment to be able to send it successfull because of network fees
        foreach ($data["pay"] as $key => $payment) {
            $data["pay"][$key]["amount"] = floatval($payment["amount"] - $this->config["GigaDust"]);
        }        

        // Sends the GigaWallet Command
        return $this->sendGigaCommand($this->config["GigaServer"][0] . ":" . $this->config["GigaPort"][0] . $command, 'POST', $data);
    }       

    // Sends commands to the GigaWallet Server
    public function sendGigaCommand($url, $method = 'GET', $data = array()) {
        $ch = curl_init();
    
        // Set the URL
        curl_setopt($ch, CURLOPT_URL, $url);
    
        // Set the request method
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            // Set the Content-Type header to specify JSON data
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
        // Set the option to return the response as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Execute the request
        $response = curl_exec($ch);
    
        // Check for errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("GigaWallet Error: $error");
        }
        //print_r($response);

        // Close the curl handle
        curl_close($ch);
    
        // Return the response
        return $response;
    }   
    
// Send emails using SMTP
public function mailx($email_to,$email_from,$email_from_name,$email_username,$email_password,$email_port,$email_stmp,$email_subject,$email_body){

    if (!class_exists('PHPMailer\PHPMailer\Exception'))
    {
      require("PHPMailer/src/PHPMailer.php");
      require("PHPMailer/src/SMTP.php");
      require("PHPMailer/src/Exception.php");
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPOptions = array(
      'ssl' => array(
      'verify_peer' => false,
      'verify_peer_name' => false,
      'allow_self_signed' => true
      )
    );

    $mail->CharSet="UTF-8";
    $mail->Host = $email_stmp;
    $mail->SMTPDebug = 0;
    $mail->Port = $email_port ; //465 or 587

    $mail->SMTPSecure = 'ssl';
    $mail->SMTPAuth = true;
    $mail->IsHTML(true);

    //Authentication
    $mail->Username = $email_username;
    $mail->Password = $email_password;

    //Set Params
    $mail->SetFrom($email_from, $email_from_name);
    $mail->AddAddress($email_to);
    $mail->addReplyTo($this->config["email_reply_to"], $email_from_name);
    $mail->Subject = $email_subject;
    $mail->Body = $email_body;

     if(!$mail->Send()) {
        if (isset($this->config["tests"])){
            return "Error";
        }
      //echo "Mailer Error: " . $mail->ErrorInfo;
     } else {
      //echo "Message has been sent";
     }
  return null;
  }    
/*
    public function createOrder($name, $email, $country, $github, $dogeAddress, $amount, $paytoDogeAddress) {
        $this->insertShibe($name, $email, $country, $github, null, $dogeAddress, $amount, $paytoDogeAddress);
        return $this->createPayment($amount, $paytoDogeAddress);
    }

    private function createPayment($amount, $paytoDogeAddress) {
        $conn = $this->getDbConnection();
        $sql = "INSERT INTO payments (amount, address, status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ds", $amount, $paytoDogeAddress);
        $stmt->execute();
        $paymentId = $stmt->insert_id;
        $stmt->close();
        $conn->close();
        return $paymentId;
    }
*/        

}

$G = new GigaWalletBridge($config);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $rawInput = file_get_contents('php://input');
        error_log("Raw input: " . $rawInput);
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input: ' . json_last_error_msg());
        }
        
        if (!$input) {
            throw new Exception('Empty input data');
        }

        // Log received data
        error_log("Received data: " . print_r($input, true));

        // Validate required fields
        $requiredFields = ['name', 'email', 'country', 'dogeAddress', 'amount'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Create Account
        $foreign_id = $input['dogeAddress'];
        error_log("Creating account for: " . $foreign_id);
        
        $GigaAccountCreate = json_decode($G->account($foreign_id, $config["payout_address"], 0, 0, "POST"));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error creating account: ' . json_last_error_msg());
        }
        error_log("Account create response: " . print_r($GigaAccountCreate, true));

        // Get Account
        $GigaAccountGet = json_decode($G->account($foreign_id, NULL, NULL, NULL, "GET"));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error getting account: ' . json_last_error_msg());
        }
        error_log("Account get response: " . print_r($GigaAccountGet, true));

        // Create Invoice
        $data = [
            "required_confirmations" => 1,
            "items" => [
                [
                    "type" => "item",
                    "name" => $input['sku'] ?? 'dogeathon-2025',
                    "sku" => $input['sku'] ?? 'dogeathon-2025',
                    "value" => (float)$input['amount'],
                    "quantity" => 1
                ]
            ]
        ];
        error_log("Creating invoice with data: " . print_r($data, true));

        $GigaInvoiceCreate = json_decode($G->invoice($GigaAccountGet->foreign_id, $data));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error creating invoice: ' . json_last_error_msg());
        }
        error_log("Invoice create response: " . print_r($GigaInvoiceCreate, true));

        // Get invoice
        $GigaInvoiceGet = json_decode($G->GetInvoice($GigaAccountGet->foreign_id, $GigaInvoiceCreate->id));
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error getting invoice: ' . json_last_error_msg());
        }
        error_log("Invoice get response: " . print_r($GigaInvoiceGet, true));

        // Insert Shibe
        $insertResult = $G->insertShibe(
            $input['name'],
            $input['email'],
            $input['country'],
            $input['github'] ?? null,
            $input['x'] ?? null,
            $input['dogeAddress'],
            (float)$input['amount'],
            $GigaInvoiceCreate->id
        );
        error_log("Insert shibe result: " . ($insertResult ? 'success' : 'failed'));

        // Get QR
        $GigaQR = base64_encode($G->qr($GigaInvoiceGet->id, "000000", "ffffff"));
        error_log("QR code generated successfully");

        // Prepare response
        $response = [
            'success' => true,
            'GigaQR' => $GigaQR,
            'PaytoDogeAddress' => $GigaInvoiceCreate->id
        ];

        // Send response
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (Exception $e) {
        // Log error with stack trace
        error_log("GigaWallet API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
        // Send error response
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'details' => $e->getTraceAsString()
        ]);
    }
} else {
    // Send error for non-POST requests
    header('HTTP/1.1 405 Method Not Allowed');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Only POST requests are allowed'
    ]);
}