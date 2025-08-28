<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class EH_Stripe_Log 
{
    public static function init_live_log()
    {
        $content="<------------------- WebToffee Stripe Payment Live Log File ( ".EH_STRIPE_VERSION." ) ------------------->\n";
        return $content;
    }
    public static function init_dead_log()
    {
        $content="<------------------- WebToffee Stripe Payment Dead Log File ( ".EH_STRIPE_VERSION." ) ------------------->\n";
        return $content;
    }
    public static function log_update($type,$msg,$title)
    {
        $check=  get_option('woocommerce_eh_stripe_pay_settings');
        if('yes' === $check['eh_stripe_logging'])
        {
            if(WC()->version >= '2.7.0')
            {
                $log = wc_get_logger();
                $head="<------------------- WebToffee Stripe Payment ( ".$title." ) ------------------->\n";
                switch ($type)
                {
                    case 'live':
                        //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $log_text=$head.print_r((object)$msg,true);
                        $live_context = array( 'source' => 'eh_stripe_pay_live' );
                        $log->log("debug", $log_text,$live_context);
                        break;
                    case 'dead':
                        //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $log_text=$head.print_r((object)$msg,true);
                        $dead_context = array( 'source' => 'eh_stripe_pay_dead' );
                        $log->log("debug", $log_text,$dead_context);
                        break;
                    case 'oauth':
                        $head="<------------------- ".$title."------------------->\n";
                        //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $log_text=$head.print_r((object)$msg,true);
                        $oauth_context = array( 'source' => 'eh_stripe_pay_oauth' );
                        $log->log("info", $log_text,$oauth_context);
                        break;
                }
            }
            else
            {
                $log=new WC_Logger();
                $head="<------------------- WebToffee Stripe Payment ( ".$title." ) ------------------->\n";
                switch ($type)
                {
                    case 'live':
                        //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $log_text=$head.print_r((object)$msg,true);
                        $log->add("eh_stripe_pay_live",$log_text);
                        break;
                    case 'dead':
                        //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $log_text=$head.print_r((object)$msg,true);
                        $log->add("eh_stripe_pay_dead",$log_text);
                        break;
                     case 'oauth':
                        $head="<------------------- ".$title."------------------->\n";
                        //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                        $log_text=$head.print_r((object)$msg,true);
                        $log->add("eh_stripe_pay_oauth",$log_text);
                        break;
                }
            }
        }
    }
}
