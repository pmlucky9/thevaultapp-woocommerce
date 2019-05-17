<?php
/**
 * get http request using wordpress api.
 *
 * @param string $url Request Url
 * 
 * @param string $action_type POST, GET
 * 
 * @param object $params
 *
 * @return object
 */
function getHttpData($url, $action_type, $params) 
{
    try {
        if ($action_type == 'POST') {
            $body = json_encode($params);
            $args = array(
                'body' => $body,
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                    'Content-Type: application/xml',
                    'Accept: application/json',
                ),
                'cookies' => array()
            );
            $response = wp_remote_post($url, $args);

        } else {
            $url = $url . http_build_query($params);
            $response = wp_remote_get($url);
        }
    
        /* Process $content here */
        $http_code = wp_remote_retrieve_response_code( $response );  
        $data = wp_remote_retrieve_body( $response );      
        
        if ($http_code != 200) {
            $data = NULL;
        } else {
            $data = json_decode($data, true);
        }

    } catch(Exception $e) {
        var_dump($e->getMessage());
        exit(1);
    }

    return $data;
}

/**
 * send vault app request
 * 
 * @param $order_id Woocommerce Order
 * 
 * @param $token thevaultapp token_id
 * 
 * @param $phone Customer phone number
 * 
 * @param $amount pay amount
 * 
 * @param $quantity $quantity of ordered thing
 * 
 * @param $store Business Store Name
 * 
 * @return object
 */
function sendVaultOrderRequest($order, $url, $token, $store)
{
    // get order data
    $order_data = $order->get_data(); // The Order data

    $order_id = $order_data['id'];		
    $order_currency = $order_data['currency'];
    $order_billing_phone = $order_data['billing']['phone'];
    $order_total = $order->get_total();
    $quantity = $order->get_item_count();    
    
    $params = Array(
        'token' => $token,        
        'phone' => $order_billing_phone,
        'amount' => $order_total,
        'subid1' => $order_id,
        'quantity' => $quantity,
        'store' => $store,
    );

    // get response from vault app website
    $result = getHttpData($url, 'POST', $params);

    if ($result == NULL)
    {
        $result = Array(
            'status' => 'error',
            'errors' => Array('Can not connet server'),
        );
    }
    
    return $result;	
}
