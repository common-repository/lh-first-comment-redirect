<?php
/**
 * Plugin Name: LH First Comment Redirect
 * Plugin URI: https://lhero.org/portfolio/lh-first-comment-redirect/
 * Description: This plugin redirects non-logged users to the login page when they follow a link to a post, page, or cpt restricted by post status.
 * Author: Peter Shaw
 * Author URI: https://shawfactor.com
 * Text Domain: lh_fcr
 * Version: 1.01
 * License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('LH_First_comment_redirect_plugin')) {

class LH_First_comment_redirect_plugin {
    
    private static $instance;
    
    static function return_plugin_namespace(){
    
        return 'lh_fcr';
    
    }
    
    static function plugin_name(){
        
        return 'LH First Comment Redirect';
        
    }

    static function return_opt_name(){
    
        return self::return_plugin_namespace().'-options';
    
    }

    static function return_redirect_page_field_name(){
        
        return self::return_plugin_namespace().'-redirect_page';    
        
    }


    static function curpageurl() {
        
    	$pageURL = 'http';
    
    	if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on")){
    	    
    		$pageURL .= "s";
    		
        }
    
    	$pageURL .= "://";
    
    	if (($_SERVER["SERVER_PORT"] != "80") and ($_SERVER["SERVER_PORT"] != "443")){
    	    
    		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    
    	} else {
    	    
    		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    
        }
    
    	return $pageURL;
    	
    }

    static function isValidURL($url){
        
        if (empty($url)){
            
            return false;
            
        } else {
            
            return (bool)parse_url($url);
            
        }
    
    }

    public function filter_redirect( $old_url, $comment ) {
        
        $options = get_option( self::return_opt_name() );    
    
        if (!empty($options[self::return_redirect_page_field_name()]) && self::isValidURL(get_permalink($options[self::return_redirect_page_field_name()]))){
        
            $comment_count = get_comments(	                                                                
                array(
                    'author_email' => $comment->comment_author_email,
                    'count' => true,
                )
            );
         
            if ( $comment_count == 1 ) {
            
                return apply_filters(self::return_plugin_namespace().'_filter_redirect', get_permalink($options[self::return_redirect_page_field_name()]),$old_url, $comment, $options);
            
            } 
        
        }

        return $old_url;
        
    }

    public function render_pages_dropdown($args) {  // Textbox Callback

        $options = get_option( self::return_opt_name() );

        if (!empty($options[ $args[0] ])){
    
            $selected = $options[ $args[0] ];
    
        } else {
    
            $selected = false;
    
        }

        $dropdown_args = array(
            'selected'              => $selected,
            'echo'                  => 1,
            'name'                  => self::return_opt_name().'['.$args[0].']',
            'show_option_none'      => __( '&mdash; Select &mdash;' ) // string
        ); 

        wp_dropdown_pages( $dropdown_args);

        if (!empty($selected) && get_permalink($selected)){
            
            echo '<a href="'.get_permalink($selected).'">'.__('Link', self::return_plugin_namespace()).'</a>'."\n";
            echo '<a href="'.get_edit_post_link($selected).'">'.__('Edit', self::return_plugin_namespace()).'></a>'."\n";

        }

    }

    public function validate_options( $input ) { 
        
        $output = $input;
    
        // Return the array processing any additional functions filtered by this action
        return apply_filters( self::return_plugin_namespace().'_validate_options', $output, $input );
    
    }
    

    public function settings_section_callback($arguments){
        
        
        
    }



    public function add_settings_section() {  
        
        add_settings_section(  
            self::return_opt_name(), // Section ID 
            __('First Comment Options', self::return_plugin_namespace()), // Section Title
            array($this, 'settings_section_callback'), // Callback
            'reading' // What Page?  
        );
    
        add_settings_field( // Option 1
            self::return_redirect_page_field_name(), // Option ID
            __('Redirect To', self::return_plugin_namespace()), // Label
            array($this, 'render_pages_dropdown'), // !important - This is where the args go!
            'reading', // Page it will be displayed (General Settings)
            self::return_opt_name(), // Name of our section
            array( // The $args
                self::return_redirect_page_field_name(), // Should match Option ID
            )  
        ); 
    
        register_setting('reading',self::return_opt_name(), array($this, 'validate_options'));
        
    }



    public function plugin_init(){
        
        //potentially load translations
        load_plugin_textdomain( self::return_plugin_namespace(), false, basename( dirname( __FILE__ ) ) . '/languages' );
        
        //maybe redirect the commenter    
        add_filter( 'comment_post_redirect', array( $this, 'filter_redirect' ), 10, 2 );
        
        //add a section to discussion settings
        add_action('admin_init', array($this,'add_settings_section'));  
    
    }

    /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        
        if (null === self::$instance) {
            
            self::$instance = new self();
            
        }
 
        return self::$instance;
        
    }
    



    public function __construct() {
        
        //run our hooks on plugins loaded to as we may need checks       
        add_action( 'plugins_loaded', array($this,'plugin_init'));
    
    }

}

$lh_first_comment_redirect_instance = LH_First_comment_redirect_plugin::get_instance();

}

?>