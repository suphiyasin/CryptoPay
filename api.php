<?php

function get_transaction_status($transaction_hash) {
    $api_url = "https://apilist.tronscanapi.com/api/transaction-info?hash={$transaction_hash}";

    try {
        $response = file_get_contents($api_url);
        $data = json_decode($response, true);

        if (isset($data['contractRet'])) {
            return $data;
        } else {
            return null;
        }
    } catch (Exception $e) {
        echo "An error occurred: {$e->getMessage()}";
        return null;
    }
}

function simulate_transaction_verification($transaction_datetime) {
    $current_datetime = new DateTime();
    $time_difference = $current_datetime->diff($transaction_datetime);

    // If the transaction is sent within 5 minutes
    if ($time_difference->i <= 5) {
        return true;
    } else {
        return false;
    }
}

function verify_payment_with_hash($expected_amount) {
    $transaction_hash = $_GET['hash'];
    $transaction_data = get_transaction_status($transaction_hash);
    $receiver_address = isset($transaction_data['contractData']['to_address']) ? $transaction_data['contractData']['to_address'] : '';
    $myTrxAddress = 'TRsWGn75MPwMgKaEuETPqB4P67e6w9L9JT';
    $result = array(
        "Status" => "",
        "SentAmount" => "",
        "ExpectedAmount" => "",
        "ReceivedAmount" => "NOT",
        "Message" => ""
    );

    if ($transaction_data !== null && isset($transaction_data['contractRet']) && $myTrxAddress == $receiver_address) {
        $transaction_timestamp = intval($transaction_data['timestamp']);
        $transaction_datetime = DateTime::createFromFormat('U', $transaction_timestamp / 1000.0);
        $verification_result = simulate_transaction_verification($transaction_datetime);

        if ($verification_result) {
            $status = $transaction_data['contractRet'];
            $result["Status"] = $status;
            if ($status == "SUCCESS") {
                if (isset($transaction_data['contractData']) && isset($transaction_data['contractData']['amount'])) {
                    $actual_amount = intval($transaction_data['contractData']['amount']) / 10**6;

                    $result["SentAmount"] = $actual_amount;
                    if ($actual_amount >= $expected_amount) {
                        $result["ExpectedAmount"] = $expected_amount;
                        $result["ReceivedAmount"] = "OK";
                        $result["Message"] = "Your TRX has been successfully received.";
                    } else {
                        $result["ExpectedAmount"] = $expected_amount;
                        $result["ReceivedAmount"] = "NOT";
                        $result["Message"] = "Please send the requested amount of TRX.";
                    }
                } else {
                    $result["ExpectedAmount"] = $expected_amount;
                    $result["ReceivedAmount"] = "NOT";
                    $result["Message"] = "There might be a problem with the hash information, please try again.";
                }
            } else {
                $result["Status"] = "NOT";
                $result["ExpectedAmount"] = $expected_amount;
                $result["ReceivedAmount"] = "NOT";
                $result["Message"] = "Your TRX is still in the sending stage, we are waiting :)";
            }
        } else {
            $result["Status"] = "NOT";
            $result["ExpectedAmount"] = $expected_amount;
            $result["ReceivedAmount"] = "NOT";
            $result["Message"] = "Payment did not occur within the expected time. Transaction failed.";
        }
    } else {
        $result["Status"] = "NOT";
        $result["ExpectedAmount"] = $expected_amount;
        $result["ReceivedAmount"] = "NOT";
        $result["Message"] = "Invalid hash information or wrong address.";
    }
    echo json_encode($result);
}

$expected_amount = $_GET['amount'];  // Adjust the expected amount as needed
verify_payment_with_hash($expected_amount);

?>
