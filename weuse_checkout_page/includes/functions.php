<?php
function add_cors_http_header(){
    header("Access-Control-Allow-Origin: *");
}
add_action('init','add_cors_http_header');
//Adiciona os botões de compra da peça por variação
add_action('woocommerce_after_add_to_cart_form', 'weuse_add_to_cart_on_sale' );
//ENQUEUE SCRIPT Select Search Box Selectize.js (Pesquisar user na variação de produto);
add_action( 'admin_enqueue_scripts', 'enqueue_select2_jquery' );
function enqueue_select2_jquery() {
    wp_register_style( 'selectizecss', '//cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/css/selectize.bootstrap3.min.css', false, '1.0', 'all' );
    wp_register_script( 'selectize', '//cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.6/js/standalone/selectize.min.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_style( 'selectizecss' );
    wp_enqueue_script( 'selectize' );
}
//Salva variação
add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );
function save_variation_settings_fields( $variation_id, $i ) {
    $fh = fopen($i.'.txt', 'w');
    fwrite($fh, print_r($variation_id, true));
    fclose($fh);
    $fh = fopen($variation_id.'.txt', 'w');
    fwrite($fh, print_r($i, true));
    fclose($fh);

    if( isset( $_POST['variable_marketplace_checkbox'][ $variation_id ] ) ) {
        $variation_on_sale = $_POST['variable_marketplace_checkbox'][ $variation_id ];
        update_post_meta( $variation_id, '_weuse_variation_on_sale', esc_attr( $variation_on_sale ) ? true : false );
    }else{
        update_post_meta( $variation_id, '_weuse_variation_on_sale', false );
    }
    if( isset( $_POST['weuse_user_search'][ $variation_id ] ) ) {
        global $wpdb;

        $variation_owner = $_POST['weuse_user_search'][ $variation_id ];
        update_post_meta( $variation_id, '_weuse_variation_owner', esc_attr( $variation_owner ) );

        $query = $wpdb->get_results( 'DELETE FROM wp_weuse_marketplace_relations WHERE variation_id IN (' . $variation_id . ');');
        $query = $wpdb->get_results( 'INSERT INTO  wp_weuse_marketplace_relations (product_id, user_id, variation_id) VALUES ('. esc_attr($_POST['product_id']) .', '.esc_attr($variation_owner).', '.$variation_id.');');
    }
    if( isset( $_POST['weuse_marketplace_user_profit'][ $variation_id ] ) ) {
        $user_profit = $_POST['weuse_marketplace_user_profit'][ $variation_id ];
        update_post_meta( $variation_id, '_weuse_variation_user_profit', esc_attr( $user_profit ) );
    }
    if( isset( $_POST['weuse_marketplace_price'][ $variation_id ] ) ) {
        $variation_price = $_POST['weuse_marketplace_price'][ $variation_id ];
        update_post_meta( $variation_id, '_weuse_variation_price', esc_attr( $variation_price ) );
    }
}

//Cria Checkbox de "Variação a Venda"
add_action( 'woocommerce_variation_options', 'variation_settings_weuse_marketplace_checkbox', 10, 3 );
function variation_settings_weuse_marketplace_checkbox( $loop, $variation_data, $variation ) {
    $checked = get_post_meta( $variation->ID, '_weuse_variation_on_sale', true ) ? true : false;
    ?>
        <label class="tips" data-tip="<?php esc_attr_e( 'Ative essa opção para colocar essa variação da peça a venda.', 'woocommerce' ); ?>">
            <?php esc_html_e( 'Colocar a venda', 'woocommerce' ); ?>
            <input type="checkbox" class="checkbox variable_marketplace_checkbox_<?php echo esc_attr( $variation->ID ); ?>" name="variable_marketplace_checkbox[<?php echo esc_attr( $variation->ID ); ?>]" onChange="weuseOnSale_<?php echo esc_attr( $variation->ID ); ?>()" <?php checked( $checked, true ); // Use view context so 'parent' is considered. ?> />
        </label>
    <?php
}

//(Selectize.js) Caixa de Seleção de Usuário com Busca
add_action( 'woocommerce_variation_options_pricing', 'variation_settings_weuse_marketplace_user_search', 10, 3 );
function variation_settings_weuse_marketplace_user_search( $loop, $variation_data, $variation ){
    global $wpdb;
    $results = $wpdb->get_results('SELECT ID, display_name, user_email FROM wp_users;');
    $variation_owner = metadata_exists('post', $variation->ID, '_weuse_variation_owner') ? get_post_meta( $variation->ID, '_weuse_variation_owner', true ) : 1;
    $variation_price = get_post_meta( $variation->ID, '_weuse_variation_price', true );
    $user_profit = (get_post_meta( $variation->ID, '_weuse_variation_user_profit', true ))?get_post_meta( $variation->ID, '_weuse_variation_user_profit', true ) : "2.50";
    $options = '';
    foreach($results as $result){
        $selected = '';
        if ($result->ID == $variation_owner){ $selected = 'selected'; }
        $options = $options . '<option value="'.$result->ID.'" '.$selected.'>#'.$result->ID.' '.$result->display_name.' - '.$result->user_email.'</option>';
    }
    echo 
    '<div class="form-field weuse_marketplace_fields weuse_marketplace_field_' . esc_attr( $variation->ID ) . '">
        <p class="form-row form-row-wide" style="max-width:100%;">
            <label>MarketPlace - Usuário proprietário</label>
            <select class="weuse_user_search" name="weuse_user_search[' . esc_attr( $variation->ID ) . ']" id="weuse_user_search_' . esc_attr( $variation->ID ) . '" value="" placeholder="">   
                '.$options.'
            </select>
        </p>
        <p class="form-row form-row-first">
            <label for="weuse_marketplace_price_'.esc_attr( $variation->ID ).'">Marketplace - Preço atual (R$)</label>
            <input type="text" class="short" value="'.esc_attr( $variation_price ).'" name="weuse_marketplace_price['.esc_attr( $variation->ID ).']" id="weuse_marketplace_price_'.esc_attr( $variation->ID ).'"/>
        </p>
        <p class="form-row form-row-last">
            <label for="weuse_marketplace_user_user_profit_'.esc_attr( $variation->ID ).'">MarketPlace - Comissão por aluguél (R$)</label>
            <input type="text" class="short" value="'.esc_attr( $user_profit ).'" name="weuse_marketplace_user_profit['.esc_attr( $variation->ID ).']" id="weuse_marketplace_user_profit_'.esc_attr( $variation->ID ).'"/>
        </p>
    </div>
    <script>
        function weuseOnSale_'.esc_attr($variation->ID).'(){
            if (jQuery(".variable_marketplace_checkbox_'.esc_attr($variation->ID).'").is(":checked")){
                jQuery(".weuse_marketplace_field_'. esc_attr( $variation->ID ).'").show(); 
            }else{ 
                jQuery(".weuse_marketplace_field_'. esc_attr( $variation->ID ).'").hide();
            }
        }
        jQuery(document).ready(function() {
            jQuery("#weuse_user_search_' . esc_attr( $variation->ID ) . '").selectize([]);
            weuseOnSale_'.esc_attr($variation->ID).'();
        });
    </script>    
    ';
}
function weuse_add_to_cart_on_sale(){
    global $wpdb, $product;
    $variations_on_sale = '';
    $variations_instock = '';
    $query = $wpdb->get_results(
        'SELECT ID AS id, post_excerpt AS label, pm2.meta_value AS on_sale
        FROM wp_posts AS p
        RIGHT JOIN wp_postmeta AS pm1  
            ON p.id=pm1.post_id 
                AND pm1.meta_key = "_stock_status" 
                AND pm1.meta_value = "instock"
        LEFT JOIN wp_postmeta AS pm2 
            ON p.id=pm2.post_id 
                AND pm2.meta_key = "_weuse_variation_on_sale"
        WHERE (p.post_parent = "'.$product->get_id().'" 
            AND p.post_type = "product_variation" );
    ');
    ?><button class='custom_add_to_cart_button button' style='padding: 10px 20px; display: inline-block; font-weight: 700; position: relative;'>Comprar</button><?php
    foreach($query as $variation){
        if ($variations_instock != ''){ $variations_instock = $variations_instock.', '; }
        $variations_instock = $variations_instock.'"'.$variation->id.'"';
        if ($variation->on_sale == 1){
            if ($variations_on_sale != ''){ $variations_on_sale = $variations_on_sale.', '; }
            $variations_on_sale = $variations_on_sale.'"'.$variation->id.'"';
            ?><form method='post' action='/wordpress/checkout-2' style='display:inline-block; position: relative;'><button name='_variation_id' class='button button_<?php echo $variation->id; ?>' style='padding: 10px 20px; display: none; font-weight: 700; position: relative;' value='<?php echo $variation->id; ?>'>Comprar <?php echo $variation->id;?></button></form><?php
        }
    }
    ?>
    <script>
        jQuery(document).ready(function() {
            var customButton = jQuery('button.custom_add_to_cart_button');
            var variations_on_sale = [<?php echo $variations_on_sale; ?>];
            var variations_instock = [<?php echo $variations_instock; ?>];
            let variation_id = jQuery('input.variation_id').val();
            let instock = variations_instock.includes(variation_id);
            let enabled = customButton.hasClass('disabled') ? false : true;

            if ((!instock || variation_id == 0) && enabled){ customButton.addClass('alt disabled'); enabled = false; }else{ if (!enabled){ customButton.removeClass('disabled'); customButton.removeClass('alt'); enabled = true; } }
            variations_on_sale.map((id, index)=>{ if (variation_id == id){ jQuery('button.button_'+id).css('display', 'inline-block'); }else{ jQuery('button.button_'+id).css('display', 'none'); } });
            //if (instock && !enabled && variation_id > 0){ customButton.removeClass('disabled'); customButton.removeClass('alt'); console.log('b'); }
            /*jQuery('.variations_form').on('woocommerce_variation_select_change', function() {*/
            jQuery('input.variation_id').change(()=>{
                variation_id = jQuery('input.variation_id').val();
                instock = variations_instock.includes(variation_id);
                enabled = customButton.hasClass('disabled') ? false : true;
                let formatedName = jQuery('select#tamanhos option:selected').text().replace('/', '-').toLowerCase();
                if ((!instock || variation_id == 0) && enabled){ customButton.addClass('alt disabled'); enabled = false; }else{ if (!enabled){ customButton.removeClass('disabled'); customButton.removeClass('alt'); enabled = true; } }
                variations_on_sale.map((id, index)=>{ if (variation_id == id){ jQuery('button.button_'+id).css('display', 'inline-block'); }else{ jQuery('button.button_'+id).css('display', 'none'); } });
                console.log(variation_id, instock, enabled);
            });
            jQuery(document).on('click tap touchstart', 'button.custom_add_to_cart_button', ()=>{ if (enabled){ jQuery('form.variations_form').submit(); } });
        });
    </script>
    <style>
        /*.single_variation_wrap { display: none !important; }*/
    </style>
    <?php
}

add_shortcode('new_checkout_page', 'weuse_new_checkout_shortcode'); 
function weuse_new_checkout_shortcode() {  
    global $checkout, $woocommerce; 
	$cart_url = WC()->cart->get_cart();
    $javascript_inject = 'console.log("';
    $user_id = 0;
    $user = false;
    $user_active = 0;
    $customer = false;
    $product_exists = 0;
    $product_id = 0;
    $variation_exists = 0;
    $variation_id = 0;
    $valid = false;
    $user_group = 0;
    $variation_price = 0;
    $variation_on_sale = 0;
    $display_motoboy = 'none';
    if (is_user_logged_in()){ 
        $javascript_inject = $javascript_inject . 'is_user_logged_in: true'; 
        $user_id = get_current_user_id();
    }else{ 
        $javascript_inject = $javascript_inject . 'is_user_logged_in: false'; 
    }
    if (isset($_POST['_variation_id'])){ 
        $variation_id = $_POST['_variation_id']; 
        $javascript_inject = $javascript_inject . ', variation_id: '.$variation_id;
    }else{
        if ($user_id > 0){ if (metadata_exists( 'user', $user_id, '_weuse_last_product_checkout' )){ $variation_id = get_user_meta( $user_id, '_weuse_last_product_checkout', true ); } }
    }
    if (!$variation_id){
        if ($user_id > 0){
            if (metadata_exists( 'user', $user_id, '_weuse_last_product_checkout' )){ 
                $variation_id = get_user_meta( $user_id, '_weuse_last_product_checkout', true ); 
                $javascript_inject = $javascript_inject . ', variation_id: '.$variation_id;
            }else{ 
                $javascript_inject = $javascript_inject . ', variation_id: 0'; $variation_id = 0; 
            }
        }else{ 
            $javascript_inject = $javascript_inject . ', variation_id: 0'; $variation_id = 0; 
        }    
    }/*else{
        $javascript_inject = $javascript_inject . ', produt_exists: true';
        if ($user_id > 0){ update_user_meta( $user_id, '_weuse_last_product_checkout', $product_id ); }
    }*/
    if ($variation_id){
        $variation = wc_get_product($variation_id);
        if (!$variation){
            $javascript_inject = $javascript_inject . ', variation_exists: false, product_exists: false';
            $variation_exists = false;
            $variation_id = 0;
        }else{
            $variation_exists = true;
            if (get_class($variation) == 'WC_Product_Variation'){
                $javascript_inject = $javascript_inject . ', variation_exists: true';
                $product = wc_get_product( $variation->get_parent_id() );
                if (!$product){ 
                    $product_exists = false;
                    $javascript_inject = $javascript_inject . ', product_id = 0, product_exists: false'; 
                }else{
                    $product_exists = true;
                    $product_id = $product->get_id(); 
                    $javascript_inject = $javascript_inject . ', product_id = ' . $product_id . ', product_exists: true'; 
                    $valid = true;
                    if (!$variation->meta_exists('_weuse_variation_on_sale')){
                        $valid = false;
                    }else{
                        $variation_on_sale = true;
                        if ($variation->get_meta('_weuse_variation_on_sale', true) == false){ $valid = false; }
                    }
                    if (!$variation->meta_exists('_weuse_variation_owner')){ 
                        $variation_owner = 0;
                        if (!($variation->get_meta('_weuse_variation_owner') >= 0)){ $valid = false; } 
                    }else{
                        $variation_owner = $variation->get_meta('_weuse_variation_owner');
                    }
                    if (!$variation->meta_exists('_weuse_variation_price')){
                        if ($variation->get_meta('_weuse_variation_price', true) < 3){ $valid = false; }                        
                    }
                
                }
            }
        }            
    }else{
        $javascript_inject = $javascript_inject . ', variation_exists: false, product_exists: false, product_id = 0';
    }    
    
    if ($valid){
        if ($user_id > 0){ update_user_meta($user_id, '_weuse_last_product_checkout', $variation_id); }
        $user = wp_get_current_user();
        $customer = new WC_Customer($user_id);
        $user_data = array(
            'first_name' => ($customer->get_billing_first_name()) ? $customer->get_billing_first_name() : '',
            'last_name' => ($customer->get_billing_last_name()) ? $customer->get_billing_last_name() : '',
            'phone' => ($customer->get_billing_phone()) ? $customer->get_billing_phone() : '',
            'billing_cpf' => ($customer->meta_exists('billing_cpf')) ? $customer->get_meta('billing_cpf', true) : '',
            'user_group' => ($customer->meta_exists('_short_user_group')) ? $customer->get_meta('_short_user_group', true) : '',
            'birthdate' => ($customer->meta_exists('billing_birthdate')) ? $customer->get_meta('billing_birthdate', true) : '',
            'address_1' => ($customer->get_address()) ? $customer->get_address() : '',
            'address_2' => ($customer->get_address_2()) ? $customer->get_address_2() : '',
            'number' => ($customer->meta_exists('billing_number')) ? $customer->get_meta('billing_number', true) : '',
            'neighborhood' => ($customer->meta_exists('billing_neighborhood')) ? $customer->get_meta('billing_neighborhood', true) : '',
            'city' => ($customer->get_billing_city()) ? $customer->get_billing_city() : '',
            'state' => ($customer->get_billing_state()) ? $customer->get_billing_state() : '',
            'cep' => ($customer->get_billing_postcode()) ? $customer->get_billing_postcode() : '',
            'email' => $user->user_email,
            '_iugu_customer_id' => ($user->meta_exists('_iugu_customer_id')) ? $user->get_meta('_iugu_customer_id', true) : false,
            '_short_user_group' => ($user->meta_exists('_short_user_group')) ? $user->get_meta('_short_user_group', true) : ''
        );
        $iugu_response = false;
        if (!$user_data['_iugu_customer_id']){
            $iugu_response = wp_remote_post( 'https://api.iugu.com/v1/customers/', 
                array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'headers' => array(
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Basic NDk1QTlEMTlDNTU3N0QzNUZFQjYwNDVBNEU0RTRCRDhFNTFDNDVEOThGRTk5QUM1MzQ1MTg2RjFCMTQ0RkVGNzo='
                    ),
                    'body' => json_encode(
                        array( 
                            'email' => $user_data['email'], 
                            'name' => $user_data['first_name'].' '.$user_data['last_name'], 
                            'notes' => 'weuse-api-registration',                     
                            'zip_code' => $user_data['cep'], 
                            'street' => $user_data['address_1'],
                            'number' => $user_data['number'], 
                            'complement' => $user_data['address_2'],
                            'district' => $user_data['neighborhood'],  
                            'city' => $user_data['city'],  
                            'state' => $user_data['state']
                        )
                    )
                )
            );
            if ($iugu_response['response']['code'] == 200 || $iugu_response['response']['message'] == 'OK'){
                $iugu_id = json_decode($iugu_response['body']);
                update_user_meta( $user_id, '_iugu_customer_id', $iugu_id->id );
                $user_data['_iugu_customer_id'] = $iugu_id->id;
            }
        }

        $image_id  = $variation->get_image_id();
        $product_name = $product->get_name();
        $image_url = wp_get_attachment_image_url( $image_id, 'full' );
        $variation_size = $variation->get_attribute( 'tamanhos' );
        $variation_brand = 'Biotwo';
        $variation_price = $variation->get_meta('_weuse_variation_price', true);
        $product_link = $product->get_permalink();
        
        $user_subscriptions = wcs_get_users_subscriptions($user_id);
        $enable_motoboy = 'disabled';
        $query_result = '[]';
        foreach ($user_subscriptions as $subscription){
            if ($subscription->get_status() == 'active' || $subscription->get_status() == 'pending-cancel'){ 
                $user_active = true;
                $user_group = ($user->meta_exists('_short_user_group')) ? $user->get_meta('_short_user_group', true) : 0;
                if ( preg_match('/^[0-9]\.[0-9]\.[0-9]/i', $user_group) ){
                    $enable_motoboy = '';
                    $display_motoboy = 'flex';
                    $javascript_inject = $javascript_inject . ', user_group: '.$user_group;
                    $query = "SELECT wp_group_range.user_group, wp_group_range.min, wp_group_range.max FROM wp_group_range";
                    $query_result = $wpdb->get_results($query);
                    $query_result = json_encode($query_result);
                }
            }
        }
        $user_ip = "127.0.0.1";
        if (is_user_logged_in()){
            $javascript_inject = "
            var query_result = '".$query_result."';
            console.log(JSON.parse(query_result));
            var server_data = {
                user: {
                    name: '".$user_data['first_name']." ".$user_data['last_name']."',
                    first_name: '".$user_data['first_name']."',
                    last_name: '".$user_data['last_name']."',
                    logged: ".is_user_logged_in().", 
                    id: ".$user_id.",
                    email: '".wp_get_current_user()->user_email."',
                    cpf: '".$user_data['billing_cpf']."',
                    ip: '".$user_ip."',
                    subscription_active: ".$user_active.",
                    group: '".$user_group."',
                    iugu_id: '".$user_data['_iugu_customer_id']."',
                    address_1: '".$user_data['address_1']."',
                    address_2: '".$user_data['address_2']."',
                    number: '".$user_data['number']."',
                    neighborhood: '".$user_data['neighborhood']."',
                    city: '".$user_data['city']."',
                    state: '".$user_data['state']."',
                    postcode: '".$user_data['cep']."',
                    meta_postcode: '".$user_data['cep']."'                    
                },";
        }else{
            $javascript_inject = "var server_data = {
                user: {
                    logged: false,
                    ip: ".$user_id.",
                    email: '',
                    password: ''
                },";
        }
        if ($variation_exists){
            $javascript_inject = $javascript_inject."
                variation: {                    
                    id: ".$variation_id.",
                    variation_id: ".$variation_id.",
                    product_id: ".$product_id.",
                    name: '".$product_name."',
                    attributes: [
                        {brand: '".$variation_brand."'},
                        {size: '".$variation_size."'}
                    ],
                    owner_id: ".$variation_owner.",
                    on_sale: ".$variation_on_sale.",
                    price: ".$variation_price.",
                    quantity: 1,
                    error: null
                },";
        }else{
            $javascript_inject = $javascript_inject."
                variation: { 
                    id: null,
                    variation_id: null,
                    product_id: null,
                    name: null,
                    attritbutes: [],
                    price: null,
                    error: 'Não foi possível encontrar a variação do produto.' 
                },";
        }
        if ($product_exists){
            $javascript_inject = $javascript_inject."
                product: {
                    id: ".$product_id.",
                    name: '".$product_name."',
                    error: null
                },";
        }else{
            $javascript_inject = $javascript_inject."
                product: {
                    id: null,
                    name: null,
                    error: 'Infelizmente não foi possível encontrar o produto.'
                },";
        }
        $javascript_inject = $javascript_inject."
                payment: {
                    index: 2,
                    payment_id: 'iugu-credit-card',
                    payment_title: 'Iugu: Cartão de Crédito',
                    token: null
                }
            };";
    }

    return '
    <style>
        .ast-article-single {
            padding-left: 10px;
            padding-right: 10px;
        }
        .details-box {
            width: 55%;
            float: left;
            margin-right: 4%;
            margin-bottom: 2em;
            padding: 5px 15px 10px 15px; 
            border-radius: 5px; 
            border: 1px solid rgb(0 0 0 / 10%);
            box-shadow: 0 1px 10px 0 rgb(0 0 0 / 10%); 
            background-color: #FFF; 
            margin-bottom: 10px;
        }
        .order-review, .payment-form {
            width: 40%;
            margin-right: 0;
            clear: right;
            border: 1px solid rgb(0 0 0 / 10%);
            box-shadow: 0 1px 10px 0 rgb(0 0 0 / 10%); 
            background-color: #FFF;
            padding: 5px 15px 10px 15px;  
            margin-bottom: 10px !important;
        }
        .order-review, .customer-details, .customer-address, .payment-form {
            box-sizing: border-box;
            color: #404040;
            display: flex;
            flex-wrap: wrap;
            font-family: Poppins, sans-serif;
            font-size: 15px;
            line-height: 22.5px;
            margin: 0px;
            text-align: start;
        }
        .required {
            color: red;
            font-weight: bold;
            border: 0 !important;
            text-decoration: none;
        }
        label {
            font-size: 13px;
            font-weight: bold;
            line-height: 1em;
            letter-spacing: 0.3px;
            font-family: inherit; 
            margin-bottom: 8px;
            color: #404040;
            line-height: 13px;
            text-size-adjust: 100%;
        }
        .form-row {
            padding: 3px;
            margin: 0 0 6px;
            display: block;
            margin-bottom: 1.1em;
            padding: 3px 7px;
            position: relative;
        }
        .form-row-first {
            width: 50%;
            float: left;
            overflow: visible;
        }
        .form-row-last {
            width: 50%;
            float: right;
            overflow: visible;
        }
        .form-row-wide{
            clear: both;
            width: 100%;
        }
        input, select {
            display: block;
            width: 100%;
            min-height: 34px;
            padding: 11px 12px;
            font-family: inherit;
            font-weight: inherit;
            font-size: 14px;
            line-height: 1.42857143 !important;
            color: #555;
            background-color: #fff !important;
            background-image: none;
            border: 1px #d4d4d4 solid !important;
            border-radius: 3px;
            box-shadow: none;
            height: auto;
        }
        input.input-text{
            box-sizing: border-box;
            width: 100%;
            margin: 0;
        }
        .box-title {
            font-weight: bold; 
            padding: 15px 5px 15px 0px;
            font-size: 1.2rem !important;
            margin: 0 0 10px;
            border-bottom: 1px solid #ebebeb;
            line-height: 1.4 !important;
            clear: both;
        }
        input:focus,
        select:focus,
        textarea:focus,
        button:focus {
            outline: none;
        }
        #cupom-container {
            height: max-content;
            max-height: 80px;
            overflow: hidden;
            transition: max-height 1s;
        }
        #cupom-container.hide { max-height: 0; }
        #cupom-field-toggle { display: flex; }
        #cupom-field-toggle.hide{ display: none !important; }
        #cupom-message-success.hide { display: none !important; }
        #cupom-message-error.hide { display: none !important; }
        input[type="text"]:disabled{ background-color: #EFEFEF !important; }
        select#billing_state:disabled{ background-color: #EFEFEF !important; }
        table, tr, td { border: none; }
        .valid-input { border-color: limegreen !important; }
        .invalid-input { border-color: #FF5E5E !important; }
        .neutral-input { border-color: #CCCCCC; }
        #load-spinner {
            -webkit-transform: translateZ(0);
            -ms-transform: translateZ(0);
            transform: translateZ(0);
            -webkit-animation: spinner 2.2s infinite linear;
            animation: spinner 2.2s infinite linear;
        }
        @-webkit-keyframes spinner {
            0% { -webkit-transform: rotate(0deg); transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); transform: rotate(360deg); }
        }
        @keyframes spinner {
            0% { -webkit-transform: rotate(0deg); transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); transform: rotate(360deg); }
        }
      
    </style>
    <script type="text/javascript" src="https://js.iugu.com/v2"></script>
    <script src="https://github.com/digitalBush/jquery.maskedinput" type="text/javascript"></script>
    <script>
        '.$javascript_inject.'
        console.log(server_data);
        jQuery(document).ready(function(){
            var loading_spinner = jQuery("div#page-loading");            
            var loading = false;
            var checkout_data = server_data;  
            var final_price = {
                subtotal: '.$variation_price.',
                shipping: null,
                discount: null,
                total: null
            };
            var inputs = {
                first_name: jQuery("input#billing_first_name"),
                last_name: jQuery("input#billing_last_name_field"),
                birthdate: jQuery("input#billing_birthdate"),
                cpf: jQuery("input#billing_cpf_field"),
                phone: jQuery("input#billing_phone_field"),
                postcode: jQuery("input#billing_cep"),
                postcode_button: jQuery("button#postcode-fill"),
                address_1: jQuery("input#billing_address_1"),
                number: jQuery("input#billing_number_field"),
                address_2: jQuery("input#billing_address_2"),
                neighborhood: jQuery("input#billing_neighborhood"),
                city: jQuery("input#billing_city"),
                state: jQuery("select#billing_state")
            };
            var cupom = {
                button: jQuery("div#cupom-field-toggle"),
                container: jQuery("div#cupom-container"),
                code: jQuery("input#_cupom_code"),
            };
            var iugu_inputs = {
                cc_form_container: jQuery("div#cc-form"),
                credit_card: {
                    number: jQuery("input.credit_card_number"),
                    expiration: jQuery("input.credit_card_expiration"),
                    name: jQuery("input.credit_card_name"),
                    cvv: jQuery("input.credit_card_cvv")
                }  
            };
            var payment_options = {
                cc_radio: jQuery("input#payment_method_1"),
                pix_radio: jQuery("input#payment_method_0")
            };
            var outputs = {
                subtotal: jQuery("span.subtotal-value"),
                table: {
                    subtotal: jQuery("span#table-subtotal"),
                    discount: jQuery("span#table-cupom"),
                    shipping: jQuery("span#table-shipping"),
                    total: jQuery("span#table-total")
                }
            };
            var shipping = {
                index: 0,
                motoboy: {
                    input: jQuery("input#shipping-method-0"),
                    container: jQuery("div#shipping-conteiner-0"),
                    price: 0,
                    delivery_time: ""
                },
                sedex: {
                    input: jQuery("input#shipping-method-1"),
                    container: jQuery("div#shipping-conteiner-1"),
                    price: jQuery("span#shipping-price-sedex"),
                    delivery_time: jQuery("span#shipping-time-sedex")
                },
                pac: {
                    input: jQuery("input#shipping-method-2"),
                    container: jQuery("div#shipping-conteiner-2"),
                    price: jQuery("span#shipping-price-pac"),
                    delivery_time: jQuery("span#shipping-time-pac")
                }
            }
            var delivery = {
                index: 0,
                motoboy: null,
                sedex: null,
                pac: null
            };
            loading_spinner.hide();
            inputs.birthdate.mask("00/00/0000");
            inputs.cpf.mask("000.000.000-00");
            inputs.phone.mask("(00)90000-0000");
            inputs.postcode.mask("00000-000");
            iugu_inputs.credit_card.number.mask("0000 0000 0000 0000");
            iugu_inputs.credit_card.expiration.mask("00/00");

            outputs.subtotal.text(Number('.$variation_price.').toFixed(2).replace(".", ","));
            outputs.table.subtotal.text(Number('.$variation_price.').toFixed(2).replace(".", ","));
            
            console.log(inputs.postcode.val());
            cep_autocomplete_validation(inputs.postcode.val());

            payment_options.cc_radio.on("click touchstart", ()=>{
                if (loading){ return; }
                iugu_inputs.cc_form_container.show();
                checkout_data.payment = { 
                    index: 2,
                    method_id: "iugu-credit-card",
                    method_title: "Iugu: Cartãao de Crédito"                    
                };
                jQuery("div#weuse-pix-submit").css("display", "none");
            });
            payment_options.pix_radio.on("click touchstart", ()=>{
                if (loading){ return; }
                iugu_inputs.cc_form_container.hide();
                checkout_data.payment = { 
                    index: 1,
                    method_id: "paghiper-boleto-pix",
                    method_title: "PagHiper: Boleto Bancário / PIX",
                    token: null
                };
                jQuery("div#weuse-pix-submit").css("display", "block");
            });
             
            function update_checkout_data(){
                checkout_data.user.first_name = inputs.first_name.val();
                checkout_data.user.last_name = inputs.last_name.val();
                checkout_data.user.birthdate = inputs.birthdate.val();
                checkout_data.user.cpf = inputs.cpf.val();
                checkout_data.user.cnpj = inputs.cpf.val();
                checkout_data.user.phone = inputs.phone.val();
                checkout_data.user.postcode = inputs.postcode.val();
                checkout_data.user.address_1 = inputs.address_1.val();
                checkout_data.user.number = inputs.number.val();
                checkout_data.user.address_2 = inputs.address_2.val();
                checkout_data.user.neiborhood = inputs.neighborhood.val();
                checkout_data.user.city = inputs.city.val();
                checkout_data.user.state = inputs.state.val();
                
                checkout_data.box = {
                    altura: "8",
                    largura: "40",
                    comprimento: "60",
                    peso: "0,4"
                };
                checkout_data.delivery = {
                    index: delivery.index,
                    motoboy: 0,
                    sedex: delivery.sedex,
                    pac: delivery.pac
                };
                let shipping_data = [{ 
                    method_id: "",
                    method_title: "",
                    totals: ""
                },{
                    method_id: "weuse_motoboy",
                    method_title: "Motoboy",
                    totals: 0
                },{
                    method_id: "correios_sedex",
                    method_title: "Correios - Sedex",
                    totals: delivery.sedex
                },{
                    method_id: "correios_pac",
                    method_title: "Correios - PAC",
                    totals: delivery.pac 
                }];                
                checkout_data.shipping = shipping_data[delivery.index];
            }  

            inputs.postcode_button.on("click touchstart input", ()=>{
                if (loading){ return; }
                loading = true;         
                cep_autocomplete_validation(inputs.postcode.val());
            });
            function cep_autocomplete_validation(postcode){
                let caixa = { 
                    altura: "8",
                    largura: "40",
                    comprimento: "60",
                    peso: "0,4"
                };      
                checkout_data.box = caixa;
                console.log(checkout_data);
                
                inputs.postcode.removeClass("valid-input");
                inputs.address_1.removeClass("valid-input");
                inputs.neighborhood.removeClass("valid-input");
                inputs.city.removeClass("valid-input");
                inputs.state.removeClass("valid-input");
                
                inputs.postcode.removeClass("invalid-input");
                inputs.address_1.removeClass("invalid-input");
                inputs.neighborhood.removeClass("invalid-input");
                inputs.city.removeClass("invalid-input");
                inputs.state.removeClass("invalid-input");

                inputs.address_1.removeAttr("disabled");
                inputs.neighborhood.removeAttr("disabled");
                inputs.city.removeAttr("disabled");
                inputs.state.removeAttr("disabled");
            
                if (!(/^(\d{5})(\d{3})$/.test(postcode.replace("-", "")))){ return; }               

                fetch("https://viacep.com.br/ws/"+postcode+"/json/")
                .then((res)=>{ loading = false; return res.json(); })
                .then((res)=>{
                    console.log(res);
                    if (res.erro){ 
                        console.log("CEP inválido!");

                        inputs.postcode.removeClass("valid-input");
                        inputs.address_1.removeClass("valid-input");
                        inputs.neighborhood.removeClass("valid-input");
                        inputs.city.removeClass("valid-input");
                        inputs.state.removeClass("valid-input");

                        inputs.postcode.addClass("invalid-input");
                        inputs.address_1.addClass("invalid-input");
                        inputs.neighborhood.addClass("invalid-input");
                        inputs.city.addClass("invalid-input");
                        inputs.state.addClass("invalid-input");
                        return; 
                    }

                    let uf = res.uf.toUpperCase();
                    let state_initials = [];                    
                    state_initials["AC"] = "Acre"; state_initials["AL"] = "Alagoas"; state_initials["AP"] = "Amapá";
                    state_initials["AM"] = "Amazonas"; state_initials["BA"] = "Bahia"; state_initials["CE"] = "Ceará";
                    state_initials["DF"] = "Distrito Federal"; state_initials["ES"] = "Espírito Santo"; state_initials["GO"] = "Goiás";
                    state_initials["MA"] = "Maranhão"; state_initials["MT"] = "Mato Grosso"; state_initials["MS"] = "Mato Grosso do Sul";
                    state_initials["MG"] = "Minas Gerais"; state_initials["PA"] = "Pará"; state_initials["PB"] = "Paraíba";
                    state_initials["PR"] = "Paraná"; state_initials["PE"] = "Pernambuco"; state_initials["PI"] = "Piauí";
                    state_initials["RJ"] = "Rio de Janeiro"; state_initials["RN"] = "Rio Grande do Norte"; state_initials["RS"] = "Rio Grande do Sul";
                    state_initials["RO"] = "Rondônia"; state_initials["RR"] = "Roraima"; state_initials["SC"] = "Santa Catarina";
                    state_initials["SP"] = "São Paulo"; state_initials["SE"] = "Sergipe";

                    inputs.address_1.val(res.logradouro);				
                    inputs.neighborhood.val(res.bairro);
                    inputs.city.val(res.localidade);
                    if (!res.uf){ return; }

                    inputs.postcode.addClass("valid-input");                   

                    if (state_initials[uf]){ 
                        jQuery("#billing_state_option").attr("value", uf).html(state_initials[uf]); 
                    }                    
                    if (inputs.address_1.val()){ 
                        inputs.address_1.prop("disabled", true); 
                        inputs.address_1.addClass("valid-input");
                    }
                    if (inputs.neighborhood.val()){ 
                        inputs.neighborhood.prop("disabled", true);
                        inputs.neighborhood.addClass("valid-input"); 
                    }
                    if (inputs.city.val()){ 
                        inputs.city.prop("disabled", true);
                        inputs.city.addClass("valid-input");
                    }
                    inputs.state.prop("disabled", true);
                    inputs.state.addClass("valid-input");
                });                
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "http://127.0.0.1:4000/calcular-frete", true);
                xhr.onreadystatechange = ()=>{
                    if (xhr.readyState == XMLHttpRequest.DONE){
                        console.log(xhr)
                        if (!xhr.responseText){ return; }
                        let response = JSON.parse(xhr.responseText);
                        if (response.result){
                            jQuery("div#postcode-warning").hide();
                            jQuery("div#shipping-option").css("display", "block");
                            let valid = true;
                            let shipping_options = [];
                            if (checkout_data.user.subscription_active && server_data.user.meta_postcode.replace(/\D/g, "") == inputs.postcode.val().replace(/\D/g, "")){
                                jQuery("table#totals-table").css("display", "table");
                                shipping.motoboy.container.css("display", "flex"); 
                                delivery.motoboy = 0;
                                delivery.index = 1;
                                jQuery("input#shipping-method-0").prop("checked", true);
                                final_price.shipping = 0;
                                final_price.total = parseFloat(final_price.subtotal);
                                outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                                outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                            }
                            response.result.map((option, index)=>{
                                let totals_table = jQuery("table#totals-table");
                                let delivery_index = jQuery("input[name=shipping_method]:checked").val(); 
                                switch(option.serviceName){
                                    case "SEDEX":
                                        valid = true;
                                        if (parseFloat(option.price)){ 
                                            shipping.sedex.price.text( option.price.toString().replace(".", ",") ); 
                                        }else{ 
                                            shipping.sedex.price.text("--,--");
                                            valid = false; 
                                        }
                                        if (parseInt(option.deliveryTime)){ 
                                            shipping.sedex.delivery_time.text( option.deliveryTime.toString() ); 
                                        }else{ 
                                            shipping.sedex.delivery_time.text("--")
                                            valid = false; 
                                        }
                                        if (valid){
                                            totals_table.css("display", "table");
                                            shipping.sedex.container.css("display", "flex"); 
                                            delivery.sedex = parseFloat(option.price);
                                            shipping_options.push("SEDEX");                                            
                                            if (delivery_index == undefined || delivery_index == 3){
                                                jQuery("input#shipping-method-1").prop("checked", true);
                                                delivery.index = 2;
                                                final_price.shipping = parseFloat(option.price);
                                                final_price.total = parseFloat(final_price.subtotal) + parseFloat(option.price);
                                                outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                                                outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                                                console.log(final_price);
                                            }
                                        }else{ 
                                            shipping.sedex.container.css("display", "none"); 
                                        }
                                        break;
                                    case "PAC":
                                        valid = true;                                            
                                        if (parseFloat(option.price)){ 
                                            shipping.pac.price.text(option.price.toString().replace(".", ",")); 
                                        }else{ 
                                            shipping.pac.price.text("--,--");
                                            valid = false; 
                                        }
                                        if (parseInt(option.deliveryTime)){ 
                                            shipping.pac.delivery_time.text( option.deliveryTime.toString() ); 
                                        }else{ 
                                            shipping.pac.delivery_time.text( "--" ); 
                                            valid = false; 
                                        }
                                        if (valid){ 
                                            totals_table.css("display", "table");                                            
                                            shipping.pac.container.css("display", "flex"); 
                                            delivery.pac = parseFloat(option.price);
                                            shipping_options.push("PAC"); 
                                            if (delivery_index == undefined ){
                                                jQuery("input#shipping-method-2").prop("checked", true);
                                                delivery.index = 3;
                                                final_price.shipping = parseFloat(option.price);
                                                final_price.total = parseFloat(final_price.subtotal) + parseFloat(option.price);
                                                outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                                                outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                                                console.log(final_price);
                                            }
                                        }else{ 
                                            shipping.pac.container.css("display", "none"); 
                                        }
                                        break;   
                                } 
                                
                                /*                                                                       
                                if (shipping_options.includes("PAC")){                           
                                    jQuery("input#shipping-method-1").prop("checked", true);
                                    jQuery("table#totals-table").css("display", "table");
                                    final_price.shipping = parseFloat(option.price);
                                    final_price.total = parseFloat(final_price.subtotal) + parseFloat(option.price);
                                    outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                                    outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                                    console.log(final_price);
                                }else{
                                    if (shipping_options.includes("SEDEX")){                                        
                                    jQuery("input#shipping-method-2").prop("checked", true);
                                        final_price.shipping = parseFloat(option.price);
                                        final_price.total = parseFloat(final_price.subtotal) + parseFloat(option.price) * 100;
                                        outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                                        outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                                        console.log(final_price);
                                    }else{
                                        if (shipping_options.includes("MOTOBOY")){
                                            jQuery("input#shipping-method-0").prop("checked", true);
                                            jQuery("table#totals-table").css("display", "table");
                                            final_price.shipping = 0;
                                            final_price.total = parseFloat(final_price.subtotal);
                                            outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                                            outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));                                                
                                            console.log(final_price);                                            
                                        }
                                    }
                                }*/
                            });
                            console.log(jQuery("input[name=shipping_method]:checked").val());
                        }else{
                            jQuery("div#postcode-warning").text(response.errorLog[0]);
                            jQuery("div#postcode-warning").hide();
                            jQuery("div#shipping-option").css("display", "block");
                        }
                    }
                    checkout_data.price = final_price;
                    checkout_data.delivery = delivery;
                }
                xhr.withCredentials = false;
                xhr.setRequestHeader("Content-type", "application/json");
                xhr.send(JSON.stringify({ cep: postcode, comprimento: caixa.comprimento, largura: caixa.largura, altura: caixa.altura, peso: caixa.peso }));
            }
            function change_delivery_option(index){
                if (index == 1){
                    delivery.index = 1;
                    final_price.shipping = 0;
                    final_price.total = parseFloat(final_price.subtotal);
                    outputs.table.shipping.text("0,00")
                    outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                    checkout_data.price = final_price;
                }    
                if (index == 3){
                    delivery.index = 2;
                    final_price.shipping = delivery.pac;
                    final_price.total = parseFloat(final_price.subtotal) + final_price.shipping;
                    outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                    outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                    checkout_data.price = final_price;
                }    
                if (index == 2){
                    delivery.index = 3;
                    final_price.shipping = delivery.sedex;
                    final_price.total = parseFloat(final_price.subtotal) + final_price.shipping;
                    outputs.table.shipping.text(final_price.shipping.toFixed(2).replace(".", ","));
                    outputs.table.total.text(final_price.total.toFixed(2).replace(".", ","));
                    checkout_data.price = final_price;
                }
                checkout_data.delivery = delivery;
                checkout_data.price = final_price;
            }
            function submit_form(){
                update_checkout_data();
                let _xhr = new XMLHttpRequest();
                _xhr.withCredentials = false;
                _xhr.open("POST", "http://localhost:4000/checkout-individual", true);
                _xhr.onreadystatechange = ()=>{
                    if (_xhr.readyState == XMLHttpRequest.DONE){
                        loading = false;
                        console.log(_xhr)
                        if (!_xhr.responseText){ return; }
                        let response = JSON.parse(_xhr.responseText);
                        if (response.result){
                            console.log(response)
                        }else{
                            //Failed
                            console.log(response);
                        }
                    }
                };
                _xhr.setRequestHeader("Content-type", "application/json");
                _xhr.send(JSON.stringify(checkout_data));
            }

            jQuery("input[type=radio][name=shipping_method]").change((e)=>{ change_delivery_option(e.target.value); });
            cupom.button.on("click touchstart", ()=>{ if (loading){ return; } cupom.container.toggleClass("hide"); });   

            Iugu.setAccountID("C4857BD4557CDCB82BF2D6FA9C1C0A6C");
            Iugu.setTestMode(true);

            jQuery("form#cc-iugu-form").submit(function(evt) {
                evt.preventDefault();
                if (loading){ return; }
                loading = true;
                var form = jQuery(this);
                //if (jQuery("input:radio[name=payment_method]:checked").val() == 1)
                var tokenResponseHandler = function(data) {                    
                    if (data.errors) {
                        alert("Erro salvando cartão: " + JSON.stringify(data.errors));
                    } else {                        
                        //alert(data.id);
                        checkout_data.payment.token = data.id;
                        console.log(checkout_data);
                        submit_form();
                        return false;
                        //$("#token").val( data.id );
                        //form.get(0).submit();
                    }
                }                
                Iugu.createPaymentToken(this, tokenResponseHandler);
                return false;
            });   
            jQuery("form#user-data").submit(function(e){    
                e.preventDefault();
                if (loading){ return; }
                loading = true;
                submit_form();            
            });   
            jQuery("form#cc-form").submit(function(e){;
                e.preventDefault();
                if (loading){ return; }
                loading = true;
                submit_form();
            });            
            function error_handler(message, data_id, remove){ 
                if (!remove){
                    jQuery("<li />", { "html": message, "data-id": data_id, class:"data-id-"+data_id }).appendTo("ul.woocommerce-error"); 
                    return;
                }
                jQuery("li.data-id-"+data_id).remove();
            }
        });
    </script>

    <div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
        <ul class="woocommerce-error" role="alert">
            <li class="data-id-billing_first_name" data-id="billing_first_name"><strong>O campo "Nome" do endereço de faturamento</strong> é um campo obrigatório.</li>
            <li class="data-id-billing_last_name" data-id="billing_last_name"><strong>O campo "Sobrenome" do endereço de faturamento</strong> é um campo obrigatório.</li>
            <li class="data-id-billing_first_name" data-id="billing_postcode"><strong>O campo "CEP" do endereço de faturamento</strong> não é um CEP válido.</li>
            <li class="data-id-billing_first_name" data-id="billing_address_1"><strong>O campo "Endereço" do endereço de faturamento</strong> é um campo obrigatório.</li>
            <li class="data-id-billing_first_name" data-id="billing_number"><strong>O campo "Número" do endereço de faturamento</strong> é um campo obrigatório.</li>
        </ul>
    </div>
    <div class="checkout-container">
        <div class="checkout-form-wrapper" style="max-width: 1000px; margin: 0 auto;">            
            <form id="user-data" action="#">
            <div class="details-box">
                <h3 class="box-title">Dados Cadastrais</h3>
                <div class="customer-details">
                    <p class="form-row form-row-first" id="billing_first_name_field">
                        <label for="billing_first_name">
                        Nome&nbsp;
                        <abbr class="required" title="Obrigatório">*</abbr>
                        </label>
                        <span class="input-wrapper">
                            <input type="text" class="input-text" name="billing_first_name" id="billing_first_name" placeholder="" value="'.$user_data['first_name'].'"/>
                        </span>
                    </p>
                    <p class="form-row form-row-last" id="billing_last_name_field">
                        <label for="billing_last_name_field">
                        Sobrenome&nbsp;
                        <abbr class="required" title="Obrigatório">*</abbr>
                        </label>
                        <span class="input-wrapper">
                            <input type="text" class="input-text" name="billing_last_name_field" id="billing_last_name_field" placeholder="" value="'.$user_data['last_name'].'"/>
                        </span>
                    </p>
                    <p class="form-row form-row-first" id="billing_birthdate_field">
                        <label for="billing_birthdate">
                        Data de Nascimento&nbsp;
                        <abbr class="required" title="Obrigatório">*</abbr>
                        </label>
                        <span class="input-wrapper">
                            <input type="text" class="input-text" name="billing_birthdate" id="billing_birthdate" placeholder="" value="'.$user_data['birthdate'].'"/>
                        </span>
                    </p>
                    <p class="form-row form-row-last" id="billing_cpf_field">
                        <label for="billing_cpf_field">
                        CPF&nbsp;
                        <abbr class="required" title="Obrigatório">*</abbr>
                        </label>
                        <span class="input-wrapper">
                            <input type="text" class="input-text" name="billing_cpf_field" id="billing_cpf_field" placeholder="" value="'.$user_data['billing_cpf'].'"/>
                        </span>
                    </p>
                    <p class="form-row form-row-wide" id="billing_phone_field">
                        <label for="billing_phone_field">
                        WhatsApp&nbsp;
                        <abbr class="required" title="Obrigatório">*</abbr>
                        </label>
                        <span class="input-wrapper">
                            <input type="text" class="input-text" name="billing_phone_field" id="billing_phone_field" placeholder="" value="'.$user_data['phone'].'"/>
                        </span>
                    </p>
                </div>
            </div>
            <div class="details-box">
                <div class="customer-address">
                    <h3 class="box-title" style="width: 100%">Endereço de Entrega</h3>
                    <div class="customer-details" style="width: 100%;">
                        <p class="form-row form-row-first" id="billing_cep" style="margin-bottom: 10px;">
                            <label for="billing_cep">
                            CEP&nbsp;
                            <abbr class="required" title="Obrigatório">*</abbr>
                            </label>
                            <span class="input-wrapper">
                                <input type="text" class="input-text" name="billing_cep" id="billing_cep" placeholder="" value="'.$user_data['cep'].'"/>
                            </span>
                        </p>
                        <p class="form-row form-row-last" style="display: flex; margin-bottom: 10px;">
                            <span class="input-wrapper" style="margin-top: auto;">  
                                <button style="background-color: #67C295; padding: 14px 15px;" id="postcode-fill">Preencher</button>
                            </span>
                        </p>
                        <!--<div class="form-row form-row-wide" id="cep_info" style="border-bottom: 1px solid #EBEBEB; padding: 0px 25px 20px 10px;">
                            <span id="cep-message" style="font-size: 12px;">Insira seu CEP e clique em "Preencher" para as formas de envio disponíveis.</span>
                            <div style="width: 100%; max-width: 225px;">
                                    <div>
                                        <laber for="shipping_method_2" style="display: flex;"> 
                                            <input type="radio" id="shipping_method_0" name="shipping_method" value="0" style="min-height: auto; width: min-content; margin-top: auto; margin-bottom: auto; margin-right: 10px;">
                                            <label for="shipping_method_0" style="width: max-content; margin-top: auto; margin-bottom: auto;">Motboy</label>
                                            <div style="display:flex; color: #25c267; margin: 0px 0px auto auto; font-size: 12px;">Grátis</div>
                                        </div>
                                        <div style="padding: 2px; font-size: 12px; line-height: 14px;">Será entregue em sua próxima troca de sacolas.</div>
                                    </div>    
                                    <label for="shipping_method_2" style="display: flex; margin-top: 5px;">
                                        <input type="radio" id="shipping_method-1" name="shipping_method" value="0" style="min-height: auto; width: min-content; margin-right: 10px;" checked>
                                        <label for="shipping_method-1" style="width: max-content;">Correios - Sedex</label>
                                    </label>
                                    <label for="shipping_method_2" style="display: flex;">
                                        <input type="radio" id="shipping_method_2" name="shipping_method" value="0" style="min-height: auto; width: min-content; margin-right: 10px;" checked>
                                        <label for="shipping_method_2" style="width: max-content;">Correios - PAC</label>
                                    </label>
                            </div> 
                        </div>-->
                        <p class="form-row form-row-first" id="billing_address_1_field">
                            <label for="billing_address_1">
                            Endereço&nbsp;
                            <abbr class="required" title="Obrigatório">*</abbr>
                            </label>
                            <span class="input-wrapper">
                                <input type="text" class="input-text" name="billing_address_1" id="billing_address_1" placeholder="" value="'.$user_data['address_1'].'" '.$enable_motoboy.'/>
                            </span>
                        </p>
                        <p class="form-row form-row-last" id="billing_number_field">
                            <label for="billing_number_field">
                            Número&nbsp;
                            <abbr class="required" title="Obrigatório">*</abbr>
                            </label>
                            <span class="input-wrapper">
                                <input type="text" class="input-text" name="billing_number_field" id="billing_number_field" placeholder="" value="'.$user_data['number'].'"/>
                            </span>
                        </p>
                        <p class="form-row form-row-first" id="billing_address_2">
                            <label for="billing_address_2">
                            Complemento&nbsp(opcional)
                            </label>
                            <span class="input-wrapper">
                                <input type="text" class="input-text" name="billing_address_2" id="billing_address_2" placeholder="Apartamento, suíte, unidade, etc" value="'.$user_data['address_2'].'"/>
                            </span>
                        </p>
                        <p class="form-row form-row-last" id="billing_neighborhood_field">
                            <label for="billing_neighborhood">
                            Bairro&nbsp;
                            <abbr class="required" title="Obrigatório">*</abbr>
                            </label>
                            <span class="input-wrapper">
                                <input type="text" class="input-text" name="billing_neighborhood" id="billing_neighborhood" placeholder="" value="'.$user_data['neighborhood'].'" '.$enable_motoboy.'/>
                            </span>
                        </p>
                        <p class="form-row form-row-first" id="billing_city_field">
                            <label for="billing_city">
                            Cidade&nbsp;
                            <abbr class="required" title="Obrigatório">*</abbr>
                            </label>
                            <span class="input-wrapper">
                                <input type="text" class="input-text" name="billing_city" id="billing_city" placeholder="" value="'.$user_data['city'].'" '.$enable_motoboy.'/>
                            </span>
                        </p>
                        <p class="form-row form-row-last" id="billing_state_field">
                            <label for="billing_state">
                            Estado&nbsp;
                            <abbr class="required" title="Obrigatório">*</abbr>
                            </label>
                            <span class="input-wrapper">
                                <select name="billing_state" id="billing_state" '.$enable_motoboy.'>
                                    <option id="billing_state_option" value="São Paulo" selected>São Paulo</option>
                                </select>
                            </span>
                        </p>
                        <input type="hidden" class="hidden-input" name="iugu_cc_token" id="iugu_cc_token" placeholder="" value="" style="display: none;"/>
                    </div>
                </div>
            </div>
            <div class="order-review">
                <h3 class="box-title" style="width: 100%;">Carrinho e Forma de envio</h3>
                <div style="width: 100%; height: max-content; display: block">
                    <div style="display: flex; margin: 0px 0px 15px 15px;">
                        <a style="width: 90px; height: 110px; overflow: hidden; border-radius: 3px; display:flex;">
                            <img src="'.$image_url.'" style="width: 100%; object-fit: cover;"/>
                        </a>
                        <div style="margin-left: 15px; margin-top: 0px;">
                            <a href="'.$product_link.'" style="color: #555; font-weight: bold; font-size: 15px; text-decoration: none;">'.$product_name.' - '.$variation_brand.'</a><br>
                            <div style="display: flex; color: #888; font-weight: bold;">
                                <span style="font-size: 13px;">Tamanho:</span> <span style="font-size: 13px; margin-left: 5px; color: #666;"> '.$variation_size.'</span><br>
                            </div>
                            <div style="display: flex; font-weight: bold; font-size: 14px; color: #555; margin-top: 8px;">
                                <span style="">R$</span> <span class="subtotal-value"style="margin-left: 0px;">'.$variation_price.'</span>
                            </div>
                        </div> 
                    </div>
                    <div id="postcode-warning" style="margin-top: 15px; padding-top: 15px; margin-bottom: 10px; font-size: 12px; line-height: 13px; border-top: 1px solid #ebebeb; padding-left: 20px; padding-rigth: 20px">
                        Clique em "Preencher" para verificar as formas de envio disponíveis
                    </div>
                    <div id="shipping-option" style="margin-top: 15px; padding-top: 10px; margin-bottom: 10px; font-size: 14px; cursor: pointer; border-top: 1px solid #ebebeb; display: none;">
                        <div id="shipping-conteiner-0" style="padding: 10px 0px; display:'.$display_motoboy.'; border-top: 1px solid #ebebeb;">
                            <div style="width: fit-content; height: fit-content; margin: auto 0;">
                                <input type="radio" id="shipping-method-0" name="shipping_method" value="1" style="min-height: auto; width: min-content; margin: auto; margin-left: 20px; margin-right: 5px;">
                            </div>
                            <div style="width: 100%; height: min-content; margin: auto 5px;">
                                <div style="width: 100%; display: flex;">
                                    <label for="shipping-method-0" style="display: flex;">Motoboy</label>
                                    <div style="display:flex; color: #25c267; margin: 1px auto auto 15px; font-size: 12px; line-height: 12px;">Grátis</div>
                                </div>
                                <div style="font-size: 12px; line-height: 14px;">Será entregue em sua próxima troca de sacolas</div> 
                            </div>
                        </div>
                        <div id="shipping-conteiner-1" style="padding: 10px 0px; display:none; border-bottom: 1px solid #ebebeb; border-top: 1px solid #ebebeb;">
                            <div style="width: fit-content; height: fit-content; margin: auto 0;">
                                <input type="radio" id="shipping-method-1" name="shipping_method" value="2" style="min-height: auto; width: min-content; margin: auto; margin-left: 20px; margin-right: 5px;">
                            </div>
                            <div style="width: 100%; height: min-content; margin: auto 5px;">
                                <div style="width: 100%; display: flex;">
                                    <label for="shipping-method-1" style="display: flex;">Correios - Sedex</label>
                                    <div style="display:flex; color: #555; margin: 1px auto auto 15px; font-size: 12px; line-height: 12px;">R$<span id="shipping-price-sedex">0</span></div>
                                </div>
                                <div style="font-size: 12px; line-height: 14px;">Prazo de entrega: <span style="color:#666;">até <span id="shipping-time-sedex"></span> dias úteis</span></div> 
                            </div>
                        </div>
                        <div id="shipping-conteiner-2" style="padding: 10px 0px; display:none; border-bottom: 1px solid #ebebeb;">
                            <div style="width: fit-content; height: fit-content; margin: auto 0;">
                                <input type="radio" id="shipping-method-2" name="shipping_method" value="3" style="min-height: auto; width: min-content; margin: auto; margin-left: 20px; margin-right: 5px;">
                            </div>
                            <div style="width: 100%; height: min-content; margin: auto 5px;">
                                <div style="width: 100%; display: flex;">
                                    <label for="shipping-method-2" style="display: flex;">Correios - PAC</label>
                                    <div style="display:flex; color: #555; margin: 1px auto auto 15px; font-size: 12px; line-height: 12px;">R$<span id="shipping-price-pac">0</span></div>
                                </div>
                                <div style="font-size: 12px; line-height: 14px;">Prazo de entrega: <span style="color:#666;">até <span id="shipping-time-pac"></span> dias úteis</span></div> 
                            </div>
                        </div>
                    </div>
                    <div id="cupom-field-toggle" style="margin-top: 15px; padding-left: 20px; margin-bottom: 10px; padding-top: 10px; font-size: 14px; display: flex; cursor: pointer; border-top: 1px solid #ebebeb;">
                        <div>Você tem um cupom de desconto? <span style="color: #25c297;">Clique aqui</span></div>
                    </div>
                    <div style="display: flex; width: 100%" id="cupom-container" class="hide">
                        <p class="form-row form-row-wide" id="_cupom_code style="margin-bottom: 0px;">
                            <span class="input-wrapper" style="display: flex; margin-left: 10px">
                                <input type="text" class="input-text" name="_cupom_code" id="_cupom_code" placeholder="Código do Cupom" value="" style="max-width: 215px;"/>
                                <button style="background-color: #67C295; padding: 14px 20px; margin-left: 15px;" id="discount_button">Aplicar</button>
                            </span>
                        </p>   
                    </div>   
                    <div id="cupom-response" style="">
                        <span id="cupom-message-success" class="hide" style="color: #67c295; font-size: 14px; font-weight: bold;">
                            Cupom aplicado com sucesso!
                        </span>
                        <span id="cupom-message-error" class="hide" style="color: red; font-size: 14px; font-weight: bold;">
                            Infelizmente o cupom inserido não é valido!
                        </span>
                    </div> 
                    <div style="font-size: 13px; line-height: 22px; display:flex; border-top: 1px solid #ebebeb; padding: 10px 25px 10px 15px;">
                        <table id="totals-table" style="max-width: 250px; line-height: 18px; display: none;">
                            <tbody>
                                <tr style="">
                                    <td style="padding: 10px 0px 5px;">
                                        <div style="float: left; font-weight: bold; font-size: 13px; color:#444;">
                                            Subtotal
                                        </div>
                                    </td>
                                    <td style="padding: 10px 0px 5px;">
                                        <div style="float: right; color: #666; font-size: 12px;">
                                            R$ <span id="table-subtotal"></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr style="display: none;">
                                    <td style="padding: 0px 0px 5px;">
                                        <div style="float: left; font-weight: bold; color:#555; font-size: 13px;">
                                            Descontos
                                        </div>
                                    </td>
                                    <td style="padding: 0px 0px 5px;">
                                        <div style="float: right; color: #666; font-size: 12px;">
                                            R$ <span id="table-cupom"></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr style="">
                                    <td style="padding: 0px 0px 5px;">
                                        <div style="float: left; font-weight: bold; color:#555; font-size: 13px;">
                                            Entrega
                                        </div>
                                    </td>
                                    <td style="padding: 0px 0px 5px;">
                                        <div style="float: right; color: #666; font-size: 12px;">
                                            R$ <span id="table-shipping"></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr style="">
                                    <td style="padding: 0px 0px 5px;">
                                        <div style="float: left; font-weight: bold; color:#444; font-size: 15px;">
                                            Total
                                        </div>
                                    </td>
                                    <td style="padding: 0px 0px 5px;">
                                        <div style="float: Right; font-weight: bold; color: #666; font-size: 14px;">
                                            R$ <span id="table-total"></span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>  
                </div>            
            </div>
            </form>
            <div class="payment-form">
            <form id="cc-iugu-form" action="#" style="width:100%;">
                <h3 class="box-title" style="width: 100%; margin-bottom: 20px">Formas de Pagamento</h3>
                <div style="margin: 15px 5px; width: 100%;">
                    <div>
                        <div style="display: flex; margin: 10px 0px;"> 
                            <input type="radio" id="payment_method_0" name="payment_method" value="0" style="min-height: auto; width: min-content; margin-right: 10px;">
                            <label for="payment_method_0" style="width: max-content;">Boleto Bancário ou PIX</label>
                        </div>
                        <div style="display: flex; margin: 10px 0px;">
                            <input type="radio" id="payment_method_1" name="payment_method" value="1" style="min-height: auto; width: min-content; margin-right: 10px;" checked>
                            <label for="payment_method_1" style="width: max-content;">Cartão de Crédito</label>
                        </div>
                    </div>
                    <div id="weuse-pix-submit" style="width:100%; padding:7px; display:none;">
                        <button class="button alt" name="weuse_bankslip_checkout_place_order" id="weuse_bankslip_place_order" value="Finalizar compra" data-value="Finalizar compra" style="background-color: #25c267; width:100%; float:rigth; border-radius:3px; padding:15px 30px 15px 30px;">Finalizar compra</button>
                    </div>                      
                    <div id="cc-form">
                        <p class="form-row form-row-wide" id="_cc_number_field" style="margin-bottom: 5px;">
                            <label for="_cc_number">Número do cartão</label>
                            <span class="input-wrapper">
                                <input autocomplete="off" type="text" class="input-text credit_card_number" data-iugu="number" placeholder="•••• •••• •••• ••••" type="text" value="4242424242424242"/>
                            </span>
                        </p>
                        <p class="form-row form-row-wide" id="_cc_holder_field" style="margin-bottom: 5px;">
                            <label for="_cc_full_name">Nome do titular</label>
                            <span class="input-wrapper">
                                <input autocomplete="off" type="text" class="input-text credit_card_name" data-iugu="full_name" placeholder="Como no impresso no cartão" type="text" value="MATHEUS AQUINO"/>
                            </span>
                        </p>
                        <p class="form-row form-row-first" id="_cc_expiration_field" style="margin-bottom: 5px;">
                            <label for="_cc_expiration">Validade</label>
                            <span class="input-wrapper">
                                <input autocomplete="off" type="text" class="input-text credit_card_expiration" data-iugu="expiration" placeholder="MM/AA" name="_cc_expiration" value="10/26"/>
                            </span>
                        </p>
                        <p class="form-row form-row-last" id="_cc_cvv_field" style="margin-bottom: 15px;">
                            <label for="_cc_cvv">CVV</label>
                            <span class="input-wrapper">
                                <input autocomplete="off" type="text" class="input-text credit_card_cvv" data-iugu="verification_value" placeholder="CVV" value="781"/>
                            </span>
                        </p>
                        <div style="width:100%; padding:7px;">
                            <button class="button alt" name="weuse_cc_checkout_place_order" id="weuse_cc_place_order" value="Finalizar compra" data-value="Finalizar compra" style="background-color: #25c267; width:100%; float:rigth; border-radius:3px; padding:15px 30px 15px 30px;">Finalizar compra</button>
                        </div>
                    </div>  
                </div>                
            </form>
            </div>                       
        </div>
        <div id="page-loading" style="background-color: #00000040; position: fixed; width: 100%; height: 100%; display: flex; left: 0; right: 0; bottom: 0; top: 0;">
            <div style="width: fit-content; height:fit-content; position: relative; margin: auto; top: 0; bottom:0; left:0; right: 0;">
                <div id="load-spinner" style="width:65px; height:65px; margin: 0 auto; border: 12px solid #FFFFFF; border-top: 12px solid #67c295; border-radius: 50%;"></div>
                <div style="display: flex; margin: 5px auto 0px auto; color: #FFF">Processando...</div>
            </div>
        </div>
    ';
} 
add_shortcode('weuse_send_product_page', 'weuse_send_product_shortcode'); 
function weuse_send_product_shortcode() {  
    if (!is_user_logged_in()){ 
        return '
            <div style="width:100%; height:100%; display:flex;">
                <div style="width:fit-content; height:fit-content; margin: auto;">Você deve estar logado para acessar essa página</div>
            </div>';
    }
    //require_once( ABSPATH . 'wp-admin/includes/image.php' );
    //require_once( ABSPATH . 'wp-admin/includes/file.php' );
    //require_once( ABSPATH . 'wp-admin/includes/media.php' );         

    //$attachment_id = media_handle_upload( 'form-image-1', $_POST['post_id'] );
    //$attachments = array( get_attached_file( $attachment_id ) );
    //$mail = wp_mail('matheus.marques.aquino@gmail.com', 'Teste', '<div><div>', array('Content-Type: text/html; charset=UTF-8'), $attachments );
    //wp_delete_attachment( $attachment_id, true );
    
    if (isset($_POST)){
        $file = 'attachments.txt';
        $fh = fopen($file, 'w');
        fwrite($fh, print_r($attachments, true));
        fclose($fh);
    }
    if (isset($_FILES)){
        $file = 'files.txt';
        $fh = fopen($file, 'w');
        fwrite($fh, print_r($_FILES, true));
        fclose($fh);
    }
    return '
    <style>
        input:focus, select:focus, textarea:focus, button:focus { 
            outline: none;
        }
        .custom-label {
            font-weight: bold;
            font-size: 13px;
        }   
        #load-spinner {
            -webkit-transform: translateZ(0);
            -ms-transform: translateZ(0);
            transform: translateZ(0);
            -webkit-animation: spinner 2.2s infinite linear;
            animation: spinner 2.2s infinite linear;
        }
        @-webkit-keyframes spinner {
            0% { -webkit-transform: rotate(0deg); transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); transform: rotate(360deg); }
        }
        @keyframes spinner {
            0% { -webkit-transform: rotate(0deg); transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); transform: rotate(360deg); }
        }     
    </style>
    <script>
        jQuery(document).ready(function(){         
            var loading = false;
            var loading_spinner = jQuery("div#page-loading");   
            var input = {
                image_container: jQuery("div#add-image"),
                remove_button: jQuery("div#delete-icon"),
                file_upload: [ 
                    jQuery("input#form-upload-0"), 
                    jQuery("input#form-upload-1"), 
                    jQuery("input#form-upload-2"), 
                    jQuery("input#form-upload-3"), 
                    jQuery("input#form-upload-4")
                ],
                add_image: [
                    jQuery("label#upload-button-0"),
                    jQuery("label#upload-button-1"),
                    jQuery("label#upload-button-2"),
                    jQuery("label#upload-button-3"),
                    jQuery("label#upload-button-4"),
                    jQuery("label#upload-button-5")
                ],
                image: [
                    jQuery("div#image-0"),
                    jQuery("div#image-1"),
                    jQuery("div#image-2"),
                    jQuery("div#image-3"),
                    jQuery("div#image-4")
                ]
            }
            var images = [];
            var warnningList = [];
            loading_spinner.css("display", "none");

            input.file_upload[0].on("change", (e)=>{ validate_image_file(e.target.files, 0); });            
            input.file_upload[1].on("change", (e)=>{ validate_image_file(e.target.files, 1); });
            input.file_upload[2].on("change", (e)=>{ validate_image_file(e.target.files, 2); });
            input.file_upload[3].on("change", (e)=>{ validate_image_file(e.target.files, 3); });
            input.file_upload[4].on("change", (e)=>{ validate_image_file(e.target.files, 4); });
            
            input.remove_button.on("touchstart click tap", ()=>{ remove_image(); });

            function remove_image(){
                if (loading){ return; }
                if (!images[0]){ return; }
                loading = true;
                images.shift();
                if (images.length < 1){ 
                    input.file_upload[0].val(""); 
                    update_slider();
                    loading = false
                    return;
                }                            
            }
            function add_button_handler(){                
                let once = false;
                for(let i = 0; i < 5; i++){
                    console.log("i:", i);
                    console.log("once:", once);
                    console.log("image", iinput.image[i]);;
                    input.image[i].css("display", "none"); 
                    if (!images[i] && !once){                            
                        input.add_image[i].css("display", "block");
                        input.file_upload[i].prop("disable", false);
                        once = true;s
                    }else{                            
                        input.add_image[i].css("display", "none");
                        input.file_upload[i].prop("disable", true);
                    } 
                }
                if (images.length >= 5){ 
                    input.image_container.css("display", "none"); 
                }else{
                    input.image_container.css("display", "flex"); 
                }
                if (images.length == 0){
                    input.remove_button.css("display", "none");
                    input.add_image[5].css("display", "block");
                }else{
                    input.add_image[5].css("display", "none");
                    input.remove_button.css("display", "block");
                }
                input.image[0].css("display", "block");
            }
            function images_handler(){
                if (images.length >= 5){ 
                    input.image_container.css("display", "none"); 
                }else{
                    input.image_container.css("display", "flex"); 
                    input.a
                }
                for(let i = 0; i < 5; i ++){
                    if (!images){ continue; }
                    if (!images[i]){ 
                        input.image[i].css("display", "none");
                        continue; 
                    }
                    input.image[i].css("display", "block");
                    input.image[i].css("background-image", "url("+images[i].src+")")
                }
                input.image[0].css("display", "block");
            }
            function update_slider(){
                add_button_handler();
                images_handler();
               
            }
            function validate_image_file(files, index){
                if (loading){ return; }
                loading = true;
                if (!files){ loading = false; return; }
                if (files.length > 1){
                    warnningList.push("Só é permitido subir uma imagem por vez!"); 
                    loading = false; console.log(warnningList); return; 
                }
                if (images.length > 5){ 
                    warnningList.push("Você já atingiu o limite de 5 imagens."); 
                    loading = false; console.log(warnningList); return; 
                }
                let file = files[0];
                if (file.size > 12582912){ 
                    warnningList.push("O arquivo não deve ter até 12 MB."); 
                    loading = false; console.log(warnningList); return; 
                }
                if (!file.name){ 
                    warnningList.push("Ocorreu um erro durante o upload do arquivo."); 
                    console.log(warnningList); loading = false; return; 
                }
                if (!file.type){ 
                    warnningList.push("Ocorreu um erro durante o upload do arquivo."); 
                    console.log(warnningList); loading = false; return; 
                }
                if (!file.type.includes("image")){ 
                    warnningList.push("Só é permitido o upload de fotos nos formatos .PNG, .JPG ou JPEG."); 
                    console.log(warnningList); loading = false; return; 
                }                
                file.index = images.length - 1;
                file.src = URL.createObjectURL(file);
                images.unshift(file);
                update_slider();
                loading = false;
            }            
            update_slider();
            console.log(input);

        });
    </script>
    <form action="" method="post" enctype="multipart/form-data">
        <input name="form-upload-0" id="form-upload-0" type="file" accept="image/*" />
        <input name="form-upload-1" id="form-upload-1" type="file" accept="image/*" />
        <input name="form-upload-2" id="form-upload-2" type="file" accept="image/*" />
        <input name="form-upload-3" id="form-upload-3" type="file" accept="image/*" />
        <input name="form-upload-4" id="form-upload-4" type="file" accept="image/*" />
    </form>
    <div style="width: 100%; height: 100%;display: flex;">
		<div style="max-width: 1200px; margin: 0 auto;">
			<div style="padding: 25px 20px 15px; font-size:22px;">Envie sua roupa</div>
            <div style="font-size: 15px; margin-bottom: 25px; margin-left: 25px;">Nos envie fotos de sua peça e preencha os formulário abaixo para nossa avaliação que logo entraremos em contato!</div>	
			<div style="display:flex;">
				<div id="image-0" style="min-width: 250px; min-height: 333px; padding: 20px; margin: 0 20px 15px; border: 1px solid #eee; border-radius: 3px; display:flex; position: relative; background-position: center; background-repeat: no-repeat; background-size: contain;">                
                    <label id="upload-button-5" for="form-upload-0" style="margin: auto; display: block; cursor: pointer;">     
                        <div style="width: 100px; height: 100px; margin: 0 auto; opacity: 0.5; background-size: contain; background-position: center; background-repeat: no-repeat; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/add-image.png);"></div>
                        <div style="margin: 15px auto 0px; font-size: 13px;">Precisamos de pelo menos 2 fotos</div>
                    </label>
                    <div id="delete-icon" style="width: 25px; height: 30px; position: absolute; top: 5px; right: 5px; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/delete-icon.png); background-position: center; background-size: contain; background-repeat: no-repeat; opacity: 0.5; cursor: pointer; display: none;"></div>
                </div>		
				<div style="min-width: 60px; min-height: 60px; margin-right: 35px; margin-bottom: 15px;">
					<div id="add-image" container" style="width: 60px; height: 60px; margin-bottom: 10px; border: 1px solid #cdcdcd; border-radius: 3px; display: flex; cursor: pointer;">
                        <label id="upload-button-0" for="form-upload-0" style="margin: auto; cursor: pointer;">
                            <div style="width: 25px; height: 25px; margin: 0 auto; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/add-image.png); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.5;"></div>
                            <div style="font-size: 10px; width: fit-content; line-height: 10px; margin: 5px auto 0px; color: #555;">Clique aqui!</div>
                        </label>                        
                        <label id="upload-button-1" for="form-upload-1" style="margin: auto; cursor: pointer; display: none;">
                            <div style="width: 25px; height: 25px; margin: 0 auto; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/add-image.png); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.5;"></div>
                            <div style="font-size: 10px; width: fit-content; line-height: 10px; margin: 5px auto 0px; color: #555;">Clique aqui!</div>
                        </label>
                        <label id="upload-button-2" for="form-upload-2" style="margin: auto; cursor: pointer; display: none;">
                            <div style="width: 25px; height: 25px; margin: 0 auto; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/add-image.png); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.5;"></div>
                            <div style="font-size: 10px; width: fit-content; line-height: 10px; margin: 5px auto 0px; color: #555;">Clique aqui!</div>
                        </label>
                        <label id="upload-button-3" for="form-upload-3" style="margin: auto; cursor: pointer; display: none;">
                            <div style="width: 25px; height: 25px; margin: 0 auto; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/add-image.png); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.5;"></div>
                            <div style="font-size: 10px; width: fit-content; line-height: 10px; margin: 5px auto 0px; color: #555;">Clique aqui!</div>
                        </label>
                        <label id="upload-button-4" for="form-upload-4" style="margin: auto; cursor: pointer; display: none;">
                            <div style="width: 25px; height: 25px; margin: 0 auto; background-image: url(http://localhost/wordpress/wp-content/uploads/2022/02/add-image.png); background-size: contain; background-position: center; background-repeat: no-repeat; opacity: 0.5;"></div>
                            <div style="font-size: 10px; width: fit-content; line-height: 10px; margin: 5px auto 0px; color: #555;">Clique aqui!</div>
                        </label>                        
                    </div>
					<div id="image-1" style="width: 60px; height: 60px; margin-bottom: 10px; display: block; border: 1px solid #eee; background-size: contain; background-position: center; background-repeat: no-repeat; display: none;"></div>
					<div id="image-2" style="width: 60px; height: 60px; margin-bottom: 10px; display: block; border: 1px solid #eee; background-size: contain; background-position: center; background-repeat: no-repeat; display: none;"></div>
					<div id="image-3" style="width: 60px; height: 60px; margin-bottom: 10px; display: block; border: 1px solid #eee; background-size: contain; background-position: center; background-repeat: no-repeat; display: none;"></div>
					<div id="image-4" style="width: 60px; height: 60px; margin-bottom: 10px; display: block; border: 1px solid #eee; background-size: contain; background-position: center; background-repeat: no-repeat; display: none;"></div>
				</div>
                <div style="max-width: 360px">
                    <div style="width:100%; max-width: 350px; font-size: 13px; line-height: 19px;"> 
                        • Escolha de 2 a 5 fotos nítidas da peça de roupa, tente nos enviar fotos da peça inteira, de todos os ângulos.
                    </div>
                    <div style="width:100%; max-width: 350px; margin-top: 15px; font-size: 11px; line-height: 11px;"> 
                        - A foto nos será enviada no tamanho e resolução da imagem selecionada, o campo a esquerda serve apenas como referência.<br>
                    </div>
                    <div style="width:100%; max-width: 350px; font-size: 11px; margin-top: 10px; line-height: 11px;">
                        - Cada foto não deve ter mais de 12Mb e estar em formato .png, .jpeg ou .jpeg
                    </div> 
                </div>
			</div>
			<div style="margin: 25px 25px 15px;">Insira os dados da peça de roupa e faça uma breve descrição do estado atual dela para receber uma proposta!</div>
			<div class="custom-label" style="margin-top: 25px; margin-left: 25px">Nome da peça</div>
			<input id="product-name" name="product-name" style="width: 100%; max-width: 320px; line-height: 22px; font-size: 13px; margin: 0px 25px 15px; padding: 5px 10px; border-radius: 2px; border: 1px solid #eaeaea;"/>
			<div class="custom-label" style="margin-left: 25px">Marca da peça</div>
			<input id="product-name" name="product-name" style="width: 100%; max-width: 320px; font-size: 13px; line-height: 22px; margin: 0px 25px 15px; padding: 5px 10px; border-radius: 2px; border: 1px solid #eaeaea;"/>
			<div class="custom-label" style="margin-left: 25px">Descrição</div>
			<div style="padding: 0px 25px 5px 25px;">
				<textarea style="width:100%; padding: 5px; max-width: 620px; height: 120px; font-size: 13px; resize: none; border: 1px solid #eaeaea; background-color: #FFF;"></textarea>
			</div>
			<div style="width: 180px; line-height: 40px; margin-left: auto; margin-top: 30px; margin-right: 60px; background-color: #65C295; margin-bottom: 20px; color: #FFFFFF; text-align: center; font-size: 17;">Enviar</div>
		</div>
	</div>    
    <div id="page-loading" style="background-color: #00000040; position: fixed; width: 100%; height: 100%; display: flex; left: 0; right: 0; bottom: 0; top: 0;">
        <div style="width: fit-content; height:fit-content; position: relative; margin: auto; top: 0; bottom:0; left:0; right: 0;">
            <div id="load-spinner" style="width:65px; height:65px; margin: 0 auto; border: 12px solid #FFFFFF; border-top: 12px solid #67c295; border-radius: 50%;"></div>
            <div style="display: flex; margin: 5px auto 0px auto; color: #FFF; display: none;">Enviando...</div>
        </div>
    </div>';
}
add_shortcode('weuse_custom_ty_page', 'weuse_custom_ty_shortcode'); 
function weuse_custom_ty_shortcode() {
    return '<div></div>';
}  
class Weuse_Marketplace_Product_Meta {
    /**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *  Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_tab_content' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ) );
	}
    /**
	 * Add Weuse Marketplace tab.
	 *
	 * @param array $tabs tabs.dg
	 */
	public function add_tab( $tabs ) {

		$tabs['weuse'] = array(
			'label'    => __( 'Weuse Marketplace', 'cartflows' ),
			'target'   => 'weuse_product_data',
			'class'    => array(),
			'priority' => 90,
		);

		return $tabs;
	}
    /**
	 * Function to search coupons.
	 */
	public function json_search_flows() {

		if ( isset( $_POST['security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'wcf_json_search_flows' ) ) {

			global $wpdb;

			$term = (string) urldecode( sanitize_text_field( wp_unslash( $_POST['term'] ) ) ); // phpcs:ignore

			if ( empty( $term ) ) {
				die();
			}

			$posts = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					"SELECT *
								FROM {$wpdb->prefix}posts
								WHERE post_type = %s
								AND post_title LIKE %s
								AND post_status = %s",
					CARTFLOWS_FLOW_POST_TYPE,
					$wpdb->esc_like( $term ) . '%',
					'publish'
				)
			);

			$flows_found = array();

			if ( $posts ) {
				foreach ( $posts as $post ) {
					$flows_found[ $post->ID ] = get_the_title( $post->ID );
				}
			}

			wp_send_json( $flows_found );

		}
	}
    /**
	 * Tab content.
	 */
	public function add_tab_content() {

		echo '<div id="cartflows_product_data" class="panel woocommerce_options_panel hidden">';

		$this->woocommerce_select2(
			array(
				'id'          => 'cartflows_redirect_flow_id',
				'name'        => 'cartflows_redirect_flow_id',
				'value'       => get_post_meta( get_the_ID(), 'cartflows_redirect_flow_id', true ),
				'label'       => __( 'Select the Flow', 'cartflows' ),
				'class'       => '',
				'placeholder' => __( 'Type to search a flow...', 'cartflows' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => 'cartflows_add_to_cart_text',
				'value'       => get_post_meta( get_the_ID(), 'cartflows_add_to_cart_text', true ),
				'label'       => __( 'Add to Cart text', 'cartflows' ),
				'name'        => 'cartflows_add_to_cart_text',
				'placeholder' => __( 'Add to cart', 'cartflows' ),
			)
		);

		/* translators: %1$s,%2$s HTML content */
		echo '<span class="wcf-shortcode-notice"><p>' . sprintf( __( 'If you want to start the flow from the product page, select the appropriate flow & button text field if required. Refer %1$sthis article%2$s for more information.', 'cartflows' ), '<a href="https://cartflows.com/docs/how-to-start-a-flow-from-product-page/" style="text-decoration:none;" target="_blank">', '</a>' );

		/* //phpcs:ignore
		Commented.
		echo '<hr>';
		echo '<span class="wcf-shortcode-notice" ><p>' . __( 'If you want to add this product\'s add-to-cart button in the flow\'s landing step, then use the below shortcode.', 'cartflows' );
		echo '<p class="form-field cartflows_atc_shortocde_field ">';
			echo '<label for="cartflows_atc_shortocde">' . __( 'Add to Cart Shortcode', 'cartflows' ) . '</label>';
			echo '<input type="text" class="short" style="" name="cartflows_atc_shortocde" id="cartflows_atc_shortocde" value="' . sprintf( esc_html( '[cartflows_product_add_to_cart id="%s" text="Buy Now" ]' ), get_the_ID() ) . '" readonly="readonly">';
		echo '</p>';
		*/

		echo '</div>';

	}

	/**
	 * Woocommerce Select2 field.
	 *
	 * @param array $field field data.
	 */
	public function woocommerce_select2( $field ) {

		global $woocommerce;

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ">
		
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>
		
		<select data-action="wcf_json_search_flows" 
		id="' . esc_attr( $field['id'] ) . '" 
		name="' . esc_attr( $field['name'] ) . '" 
		class="wcf-flows-search ' . esc_attr( $field['class'] ) . '" 
		data-allow_clear="allow_clear" 
		data-placeholder="' . esc_attr( $field['placeholder'] ) . '" 
		style="width:50%" >';

		if ( ! empty( $field['value'] ) ) {
			// posts.
			$post_title = get_the_title( intval( $field['value'] ) );
			echo '<option value="' . $field['value'] . '" selected="selected" >' . $post_title . '</option>';
		}
		echo '</select> ';
		if ( ! empty( $field['description'] ) ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}
		echo '<input type="hidden" name="wcf_json_search_flows_nonce" value="' . wp_create_nonce( 'wcf_json_search_flows' ) . '" >';
		echo '</p>';

	}

	/**
	 * Save product meta.
	 *
	 * @param int $post_id product id.
	 */
	public function save_product_meta( $post_id ) {

		/*$product = wc_get_product( $post_id );

		$next_step = isset( $_POST['cartflows_redirect_flow_id'] ) ? intval( $_POST['cartflows_redirect_flow_id'] ) : ''; //phpcs:ignore

		$add_to_cart_text = isset( $_POST['cartflows_add_to_cart_text'] ) ? sanitize_text_field( $_POST['cartflows_add_to_cart_text'] ) : '';  //phpcs:ignore

		$product->update_meta_data( 'cartflows_redirect_flow_id', $next_step );
		$product->update_meta_data( 'cartflows_add_to_cart_text', $add_to_cart_text );

		$product->save();*/
	}
}
Weuse_Marketplace_Product_Meta::get_instance();
