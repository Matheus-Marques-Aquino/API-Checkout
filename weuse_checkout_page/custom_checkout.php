<?php
/*
Plugin Name: Weuse Checkout
Description: Adiciona novos campos ao checkout, assim como funções como auto completar o endereço apartir do CEP. 
Author: Matheus Marques
*/
function jquery_form_mask() {
    wp_enqueue_script( 'jquery-mask-form', plugin_dir_url( __FILE__ ) . 'includes/assets/js/jquery.mask.min.js', array('jquery'),'',true  );
}
add_action( 'wp_enqueue_scripts', 'jquery_form_mask' );
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
