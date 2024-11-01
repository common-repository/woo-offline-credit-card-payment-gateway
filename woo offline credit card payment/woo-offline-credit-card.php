<?php
/* Offline Credit Card Payment Gateway Class */

class Offline_Credit_Card extends WC_Payment_Gateway {

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "offline_credit_card";

		// title shown on the top of the Payment Gateways
		$this->method_title = __( "Offline Credit Card", 'offline_credit_card' );

		// description for this Payment Gateway
		$this->method_description = __( "Offline Credit Card Payment Gateway Plug-in for WooCommerce", 'offline-credit-card' );

		// title used for the vertical tabs which can be ordered top to bottom
		$this->title = __( "Offline Credit Card", 'offline-credit-card' );

		// image next to the gateway's name on the frontend
		$this->icon = null;

		// Bool. Can be set to true if you want payment fields to show on the checkout 
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = true;

		// Supports the default credit card form
		$this->supports = array( 'default_credit_card_form' );

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}
		
		// Lets check for SSL
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
		// Save settings
		if ( is_admin() ) {
			// add one more option to payment type list
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // end this construct()

	// make administration field for this payment Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'offline-credit-card' ),
				'label'		=> __( 'Enable this payment gateway', 'offline-credit-card' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'offline-credit-card' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the user can see during the checkout.', 'offline-credit-card' ),
				'default'	=> __( 'Offline Credit card', 'offline-credit-card' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'offline-credit-card' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'offline-credit-card' ),
				'default'	=> __( 'Offline payment using your credit card detail.', 'offline-credit-card' ),
				'css'		=> 'max-width:350px;'
			)
		);		
	}


         
	
	// handel payment process
	public function process_payment( $order_id ) {
		global $woocommerce;
		
		// Customer who pay this 
		$customer_order = new WC_Order( $order_id );
		
		//set the form data of checkout page
		$payload = array(
            
			"x_version"            	=> "3.1",
			
			// total of order amount
			"x_amount"             	=> $customer_order->order_total,
			
			// credit card dtail of cusotmer
			"x_card_num"           	=> str_replace( array(' ', '-' ), '', $_POST['offline_credit_card-card-number'] ),
			"x_card_code"          	=> ( isset( $_POST['offline_credit_card-card-cvc'] ) ) ? $_POST['offline_credit_card-card-cvc'] : '',			
		    "x_invoice_num"        	=> str_replace( "#", "", $customer_order->get_order_number() ),
			"x_delim_char"         	=> '|',
			"x_encap_char"         	=> '',
			"x_delim_data"         	=> "TRUE",
			"x_relay_response"     	=> "FALSE",
			"x_method"             	=> "CC",
			
			// billing detail
			"x_first_name"         	=> $customer_order->billing_first_name,
			"x_last_name"          	=> $customer_order->billing_last_name,
			"x_address"            	=> $customer_order->billing_address_1,
			"x_city"              	=> $customer_order->billing_city,
			"x_state"              	=> $customer_order->billing_state,
			"x_zip"                	=> $customer_order->billing_postcode,
			"x_country"            	=> $customer_order->billing_country,
			"x_phone"              	=> $customer_order->billing_phone,
			"x_email"              	=> $customer_order->billing_email,
			
			// shipping detail
			"x_ship_to_first_name" 	=> $customer_order->shipping_first_name,
			"x_ship_to_last_name"  	=> $customer_order->shipping_last_name,
			"x_ship_to_company"    	=> $customer_order->shipping_company,
			"x_ship_to_address"    	=> $customer_order->shipping_address_1,
			"x_ship_to_city"       	=> $customer_order->shipping_city,
			"x_ship_to_country"    	=> $customer_order->shipping_country,
			"x_ship_to_state"      	=> $customer_order->shipping_state,
			"x_ship_to_zip"        	=> $customer_order->shipping_postcode,
			
			// Some Customer Information
			"x_cust_id"            	=> $customer_order->user_id,
			"x_customer_ip"        	=> $_SERVER['REMOTE_ADDR'],
			
		);
	
     
		//set the validation  
        $suc = 1; // by default success set as valid means 1.
        if(empty(trim($payload['x_card_num']))){
           $suc = 0; // 0 means get error
           $message = 'Card number is required.'; //message print on the checkout page.
		}

		else if(empty(trim($_POST['offline_credit_card-card-expiry']))){
		   $suc = 0;
           $message = 'Card expiry date is required';
		}

		else if(empty(trim($payload['x_card_code']))){
		   $suc = 0;
           $message = 'Card code is required';
		}
        else{

             //for credit card number
			function luhn_check($number) {

			  //useful for credit card numbers with spaces and hyphens
			  $number=preg_replace('/\D/', '', $number);

			  // Set the string length and parity
			  $number_length=strlen($number);
			  $parity=$number_length % 2;

			  // Loop through each digit and do the maths
			  $total=0;
			  for ($i=0; $i<$number_length; $i++) {
			    $digit=$number[$i];
			    // Multiply alternate digits by two
			    if ($i % 2 == $parity) {
			      $digit*=2;
			      // If the sum is two digits, add them together (in effect)
			      if ($digit > 9) {
			        $digit-=9;
			      }
			    }
			    // Total up the digits
			    $total+=$digit;
			  }

			  // If the total mod 10 equals 0, the number is valid
			  return ($total % 10 == 0) ? TRUE : FALSE;

			}


            //this for expiry date of credit card 
			 $get_exp_date = '01-'.str_replace('/','-',str_replace(' ','',$_POST['offline_credit_card-card-expiry']));
			 $today_date = date('d-m-Y');

		    if(luhn_check($payload['x_card_num'])==false){
		      // to chekck the card cnumber is valid or not
              $suc=0; 
			  $message = 'Card number is not valid.';
			}
			else if(strtotime($get_exp_date) < strtotime($today_date)){
			    //check expiry date is valid or not.
			 	$suc = 0;
			 	$message = 'Card expiry date is not valid.';
			}
			else if(strlen($payload['x_card_code'])<3 or strlen($payload['x_card_code'])>4){
               //check the card code number
                $suc = 0;
			 	$message = 'Card Code is not valid.';        
			 } 

		}

		// set respose code and message
		$respose['code']         = $suc;
		$respose['message']      = $message;
		
		// 1 means all validation is okay
		if ( ( $respose['code'] == 1 ) ) {
			// payment successful 
			$customer_order->add_order_note( __( 'Offline credit card payment done.
				</p>
				<h1>Credit Card Detail</h1>
				<b>Card Number : '.$payload['x_card_num'].'</b><br/>
				<b>Expiry Date : '.$_POST['offline_credit_card-card-expiry'].'</b><br/>
				<b>Card Code   : '.$payload['x_card_code'].'</b><br/>

				<p>', 'offline-credit-card' ) );
												 
			// Payment is done
			$customer_order->payment_complete();

			// empty the cart when order completes
			$woocommerce->cart->empty_cart();

			// Redirect to success pge
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			// Transaction not succesful
			wc_add_notice( $respose['message'], 'error' );
		
		}

	}
	
	// Validate fields
	public function validate_fields() {
		return true;
	}
	
	// Check if we are forcing SSL on checkout pages
	// Custom function not required by the Gateway
	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "Payment gateway have been successfully disabled." ) ) ."</p></div>";	
			}
		}		
	}

} // End of Offline_Credit_Card

