<?php

class EM_Gateway_Banktransfer extends EM_Gateway {

	var $gateway = 'banktransfer';
	//var $title = 'Bank Transfer';
	var $status = 5;
    var $status_txt = 'Awaiting Bank Transfer Payment';
	var $button_enabled = true;
	var $payment_return = true;
	var $supports_multiple_bookings = true;

	public function __construct() {
            
		parent::__construct();
        
        $this->title = __('Bank Transfer', 'em-gateway-bank-transfer');
        
        if( get_option('em_'. $this->gateway . "_payment_txt_color" ) ) { $txtColor = get_option('em_'. $this->gateway . "_payment_txt_color" ); } else { $txtColor = '#333333'; }
        if( get_option('em_'. $this->gateway . "_payment_txt_bgcolor" ) ) { $txtBgColor = get_option('em_'. $this->gateway . "_payment_txt_bgcolor" ); } else { $txtBgColor = 'none'; }
        
        $this->status_txt = '<span style="color:'.$txtColor.';background-color:'.$txtBgColor.'">'.get_option('em_'. $this->gateway . "_payment_txt_status" ).'</span>';

        add_action('em_gateway_js', array(&$this,'em_gateway_js'));
        //add_action('em_template_my_bookings_header',array(&$this,'say_thanks')); //say thanks on my_bookings page
        //add_filter('em_booking_validate', array(&$this, 'em_booking_validate'),10,2); // Hook into booking validation
	}

	/*
	 * --------------------------------------------------
	 * Booking UI - modifications to booking pages and tables containing banktransfer bookings
	 * --------------------------------------------------
	 */

	/**
	 * Outputs some JavaScript during the em_gateway_js action, which is run inside a script html tag, located in gateways/gateway.banktransfer.js
	 */
	function em_gateway_js(){

        include(dirname(__FILE__).'/gateway.banktransfer.js');
 
	}


	/*
	 * --------------------------------------------------
	 * Booking Interception - functions that modify booking object behaviour
	 * --------------------------------------------------
	 */

    
	/**
	 * Intercepts return data after a booking has been made and adds banktransfer vars, modifies feedback message.
	 * @param array $return
	 * @param EM_Booking $EM_Booking
	 * @return array
	 */
	function booking_form_feedback( $return, $EM_Booking = false ){

		//Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
		if( is_object($EM_Booking) && $this->uses_gateway($EM_Booking) ){
            
			if( !empty($return['result']) && ( get_option('em_'. $this->gateway . "_redirect" ) != '' && get_option('em_'. $this->gateway . "_redirect" ) > 0) && $EM_Booking->get_price() > 0 && $EM_Booking->booking_status == $this->status ){
                
				$return['message'] = get_option('em_banktransfer_booking_feedback');
				$banktransfer_url = $this->get_banktransfer_url();
				$banktransfer_vars = $this->get_banktransfer_vars($EM_Booking);
				$banktransfer_return = array('banktransfer_url'=>$banktransfer_url, 'banktransfer_vars'=>$banktransfer_vars);
				$return = array_merge($return, $banktransfer_return);
                
			}else{
                
				//returning a free message
				$return['message'] = get_option('em_banktransfer_booking_feedback');
                
			}
		}    
		return $return;
	}

	/*
	 * ------------------------------------------------------------
	 * banktransfer Functions - functions specific to banktransfer payments
	 * ------------------------------------------------------------
	 */

	/**
	 * Retreive the banktransfer vars needed to send to the gatway to proceed with payment
	 * @param EM_Booking $EM_Booking
	 */
	function get_banktransfer_vars( $EM_Booking ) {
		global $wp_rewrite, $EM_Notices;

		$currency = get_option('dbem_bookings_currency', 'USD');
		$currency = apply_filters('em_gateway_banktransfer_get_currency', $currency, $EM_Booking );

		$amount = $EM_Booking->get_price();
		$amount = apply_filters('em_gateway_banktransfer_get_amount', $amount, $EM_Booking, $_REQUEST );
        

		$banktransfer_vars = array(
			//'instId' => get_option('em_'. $this->gateway . "_instId" ),
			'cartId' => $EM_Booking->booking_id,
			'currency' => $currency,
			'amount' => number_format( $amount, 2),
            'invoice' => 'EM-BOOKING#'. $EM_Booking->booking_id, //added to enable searching in event of failed IPNs
			'desc' => $EM_Booking->get_event()->event_name
		);
            
		return apply_filters('em_gateway_banktransfer_get_banktransfer_vars', $banktransfer_vars, $EM_Booking, $this);
	}

	/**
	 * gets banktransfer gateway url
	 * @returns string
	 */
	function get_banktransfer_url(){

        //error_log('redirect:'.get_option('em_'. $this->gateway . "_redirect" )); //not blank, all sorts of stuff
        if( get_option('em_'. $this->gateway . "_redirect" ) ) {
            $url = get_permalink( get_option('em_'.$this->gateway.'_redirect') );
            return $url;
        } else {
            return;
        }

	}

    /**
	 * Outputs extra custom information, e.g. payment details or procedure, which is displayed when this gateway is selected when booking (not when using Quick Pay Buttons)
	 */
	function booking_form(){
		echo get_option('em_'.$this->gateway.'_form');
	}

	/*
	 * --------------------------------------------------
	 * Gateway Settings Functions
	 * --------------------------------------------------
	 */

	function mysettings() {
		global $EM_options;
        
        $textFeedback = get_option('em_'. $this->gateway . "_booking_feedback" );
        $textStatus = get_option('em_'. $this->gateway . "_payment_txt_status" );

		?>
	<table class="form-table">
		<tbody>
			<tr valign="top">
				<th scope="row"><?php _e('Redirecting Message', 'em-gateway-bank-transfer') ?></th>
				<td>
					<input type="text" name="banktransfer_booking_feedback" value="<?php if( empty($textFeedback) ) { 
            echo esc_attr_e(__('Please wait, you will be redirected ...', 'em-gateway-bank-transfer')); 
        } else {
            echo esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" ));
        } ?>" style='width: 40em;' /><br />
					<em><?php _e('Message that is shown to a user when a booking is successful whilst being redirected to Bank Transfer page for payment.', 'em-gateway-bank-transfer'); ?></em>
				</td>
			</tr>
            <tr valign="top">
				<th scope="row"><?php _e('Status text', 'em-gateway-bank-transfer') ?></th>
				<td>
					<input type="text" name="banktransfer_payment_txt_status" value="<?php if( empty($textStatus) ) { 
            echo esc_attr_e(__('Awaiting Bank Transfer Payment', 'em-gateway-bank-transfer')); 
        } else {
            echo esc_attr_e(get_option('em_'. $this->gateway . "_payment_txt_status" ));
        } ?>" style='width: 40em;' /><br />
					<em><?php _e('By default: <i>Awaiting Bank Transfer Payment</i>', 'em-gateway-bank-transfer'); ?></em>
                </td>
			</tr>
            <tr>
                <th scope="row"><?php _e('Colors:', 'em-gateway-bank-transfer') ?></th>
                <td>
                    <em><?php _e('Select a color for the text. By default: <i>#333333</i>', 'em-gateway-bank-transfer'); ?></em><br />
                    <input type="text" value="<?php if( get_option('em_'. $this->gateway . "_payment_txt_color" ) ) { echo get_option('em_'. $this->gateway . "_payment_txt_color" ); } else { echo '#333333'; } ?>" name="banktransfer_payment_txt_color" class="wpempvir-color-field" data-default-color="#000000" /><br />
					<br />
                    <em><?php _e('Select a color for the background text. By default: <i>none</i>', 'em-gateway-bank-transfer'); ?></em><br />
                    <input type="text" value="<?php if( get_option('em_'. $this->gateway . "_payment_txt_bgcolor" ) ) { echo get_option('em_'. $this->gateway . "_payment_txt_bgcolor" ); } ?>" name="banktransfer_payment_txt_bgcolor" class="wpempvir-color-field" />
					<br />
				</td>
			</tr>
            
            <tr valign="top">
				<th scope="row"><?php _e('Redirection Page', 'em-gateway-bank-transfer') ?></th>
				<td>
                    <?php
                        if( get_option('em_'. $this->gateway . "_redirect" ) ) { 
                            $idSelectPage = get_option('em_'. $this->gateway . "_redirect" );
                        } else {
                            $idSelectPage = 0;
                        }
                        $args = array('name' => 'banktransfer_redirect', 'selected' => $idSelectPage, 'show_option_none' => __('Please select a page', 'em-gateway-bank-transfer') ); 
                        wp_dropdown_pages($args);
                    ?>
				</td>
            </tr>

		</tbody>
	</table>

		<?php
	}

	function update() {
        
         $gateway_options = array(
            $this->gateway . '_booking_feedback' => wp_kses_data($_REQUEST[ $this->gateway.'_booking_feedback' ]),
            //$this->gateway . "_invoice_option" => $_REQUEST[ $this->gateway.'_invoice_option' ],
            $this->gateway . "_payment_txt_status" => wp_kses_data($_REQUEST[ $this->gateway.'_payment_txt_status' ]),
            $this->gateway . "_payment_txt_color" => wp_kses_data($_REQUEST[ $this->gateway.'_payment_txt_color' ]),
            $this->gateway . "_payment_txt_bgcolor" => wp_kses_data($_REQUEST[ $this->gateway.'_payment_txt_bgcolor' ]),
            $this->gateway . "_redirect" => $_REQUEST[ $this->gateway.'_redirect' ],
        );
        foreach($gateway_options as $key=>$option){
			update_option('em_'.$key, stripslashes($option));
		}
        
        //add wp_kses filters for relevant options and merge in
		$options_wpkses[] = 'em_'. $this->gateway . '_booking_feedback';
		foreach( $options_wpkses as $option_wpkses ) add_filter('gateway_update_'.$option_wpkses,'wp_kses_post');

		//pass options to parent which handles saving
		return parent::update($gateway_options);
	}
}

EM_Gateways::register_gateway('banktransfer', 'EM_Gateway_Banktransfer');
