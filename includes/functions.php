<?php
/**
 * create curl request.
 *
 * @param string $url Curl Request Url
 * 
 * @param string $action_type POST, GET
 * 
 * @param object $params
 *
 * @return object
 */
function get_curl_data($url, $action_type, $params) 
{
    try {
        // get curl object.
        $handle = curl_init(); 	
                
        // make parameters
        $postData = $params;    
        
        if ($action_type == 'POST') {
            $is_post = true;        
        } else {
            $is_post = false;
        }
    
        
        curl_setopt_array($handle,
            array(
                CURLOPT_URL => $url,
                // Enable the post response.
                CURLOPT_POST       => $is_post,
                // The data to transfer with the response.
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_RETURNTRANSFER     => true,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/xml',
                    'Accept: application/json',
                ),
            )
        );

        // excute curl command
        $data = curl_exec($handle);         
          // Check the return value of curl_exec(), too
        if ($data === false) {
            throw new Exception(curl_error($handle), curl_errno($handle));
        }

        /* Process $content here */
        $http_status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        
        if ($http_status != 200) {
            $data = NULL;
        } else {
            $data = json_decode($data, true);
        }

        // close curl instance
        curl_close($handle);
    } catch(Exception $e) {

        var_dump(sprintf(
            'Curl failed with error #%d: %s',
            $e->getCode(), $e->getMessage()));
        exit(1);

    }

    //return curl result
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
function send_vault_order($order, $url, $token, $store)
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

    $result = get_curl_data($url, 'POST', $params);

    if ($result == NULL)
    {
        $result = Array(
            'status' => 'error',
            'errors' => Array('Can not connet server'),
        );
    }
    
    return $result;	
}

/*
add_filter('woocommerce_order_button_html', function(){
    return '<button class="new_submit button">Place Order</button>';
});*/