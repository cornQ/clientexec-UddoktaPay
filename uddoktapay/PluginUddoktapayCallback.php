<?php
require_once 'modules/admin/models/PluginCallback.php';
require_once 'modules/billing/models/class.gateway.plugin.php';
require_once 'modules/billing/models/Invoice_EventLog.php';

class PluginUddoktapayCallback extends PluginCallback
{

    function processCallback()
    {
        $apiUrl = $this->settings->get('plugin_uddoktapay_API URL');
        $apiKey = $this->settings->get('plugin_uddoktapay_API KEY');
        
        $response = file_get_contents('php://input');

        if (!empty($response)) {

            // Decode response data
            $data     = json_decode($response);

            $signature = trim($_SERVER['HTTP_RT_UDDOKTAPAY_API_KEY']);

            // Validate Signature
            if ($apiKey !== $signature) {
                CE_Lib::log(4, "** Invalid API Signature.");
                return;
            }

            if ('COMPLETED' == $data->status) {
                
                $invoiceNumber =  $data->metadata->invoice_id;
                $tempInvoice = new Invoice($invoiceNumber);
                $tInvoiceTotal = $tempInvoice->getBalanceDue();
                $txn_id =  $data->transaction_id;
                

                $cPlugin = new Plugin($invoiceNumber, "uddoktapay", $this->user);
                $cPlugin->setAmount($tInvoiceTotal);
                $cPlugin->setAction('charge');
                $cPlugin->setTransactionID($txn_id);
        
                $transaction = "UddoktaPay payment of {$tInvoiceTotal} has been completed.";
                $cPlugin->PaymentAccepted($tInvoiceTotal, $transaction, $invoiceNumber);
            }
        }
    }
}