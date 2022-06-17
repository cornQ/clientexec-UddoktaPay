<?php

require_once 'modules/billing/models/class.gateway.plugin.php';

class PluginUddoktapay extends GatewayPlugin
{
    public function getVariables()
    {
        $variables = array (
            lang("Plugin Name") => array (
                "type"          => "hidden",
                "description"   => lang("How CE sees this plugin (not to be confused with the Signup Name)"),
                "value"         => lang("UddpktaPay")
            ),
            lang("API KEY") => array (
                "type"          => "text",
                "description"   => lang("Enter your API Key from your Panel"),
                "value"         => ""
            ),
            lang("API URL") => array (
                "type"          => "text",
                "description"   => lang("Enter your API URL from your Panel"),
                "value"         => ""
            ),
            lang("USD Exchange Rate") => array (
                "type"          => "text",
                "description"   => lang("Enter USD Exchange Rate"),
                "value"         => "88"
            ),
            lang("Signup Name") => array (
                "type"          => "text",
                "description"   => lang("Select the name to display in the signup process for this payment type. Example: Bangladeshi Payment Gateway or bKash/Rocket/Nagad/Upay."),
                "value"         => "UddpktaPay"
            )

        );
        return $variables;
    }

    public function credit($params)
    {
    }

    public function singlepayment($params, $test = false)
    {
        
       $response = $this->payment_url($params);
        if ($response->status) {
            header("Location:" . $response->payment_url);
        }
        echo $response->message;
        exit;
    }
    
    public function payment_url($params)
    {
        if ($params['isSignup']==1) {
            if ($this->settings->get('Signup Completion URL') != '') {
                $returnURL = $this->settings->get('Signup Completion URL'). '?success=1';
                $returnURLCancel = $this->settings->get('Signup Completion URL');
            } else {
                $returnURL = $params["clientExecURL"]."/order.php?step=complete&pass=1";
                $returnURLCancel = $params["clientExecURL"]."/order.php?step=3";
            }
        } else {
            $returnURL = $params["invoiceviewURLSuccess"];
            $returnURLCancel = $params["invoiceviewURLCancel"];
        }
        
        // UuddoktaPay Gateway Specific Settings
        $api_url = $params['plugin_uddoktapay_API URL'];
    
        // Gateway Configuration Parameters
        $apiKey = $params['plugin_uddoktapay_API KEY'];
    
        // Invoice Parameters
        $invoiceId = $params['invoiceNumber'];
        $amount = $params['invoiceTotal'];
        
        $currency = $params['userCurrency'];
        
        if('USD' == $currency)
        {
            $amount = $params['invoiceTotal'] * $params['plugin_uddoktapay_USD Exchange Rate'];
        }
    
        // Client Parameters
        $fullname = $params['userFirstName'] . " " . $params['userLastName'];
        $email = $params['userEmail'];
    

        $webhookUrl = $params['clientExecURL'] . '/plugins/gateways/uddoktapay/callback.php';
    
        $metaData = [
            'invoice_id' => $invoiceId
        ];
    
        // Compiled Post from Variables
        $postfields = [
            'amount' => $amount,
            'full_name' => $fullname,
            'email' => $email,
            'metadata' => $metaData,
            'redirect_url' => $returnURL,
            'cancel_url' => $returnURLCancel,
            'webhook_url' => $webhookUrl
        ];
    
        // Setup request to send json via POST.
        $headers = [];
        $headers[] = "Content-Type: application/json";
        $headers[] = "RT-UDDOKTAPAY-API-KEY: {$apiKey}";
    
        // Contact UuddoktaPay Gateway and get URL data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postfields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response);
        return $result;
    }
}
