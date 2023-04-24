<?php
class WC_Settings_Tab_User_Group {
    public static function init(){       
        add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 ); 
        add_action('woocommerce_settings_tabs_settings_tab_user_group', __CLASS__ . '::settings_tab' );
        add_action('woocommerce_update_options_settings_tab_user_group', __CLASS__ . '::update_settings' );
        add_action('woocommerce_settings_status_content_custom_', __CLASS__ . '::custom_content');
    }
    function weuse_checkout_styles() {        
    }
    public static function add_settings_tab($tabs){ 
        $tabs['settings_tab_user_group'] = 'Faixas de CEP'; 
        return $tabs; 
    }
    public static function settings_tab(){
        
        add_action('wp_enqueue_scripts', 'weuse_checkout_styles');
        defined( 'ABSPATH' ) || exit;
        $file = 'css.txt';
        $fh = fopen($file, 'w');
        fwrite($fh, print_r(WC()->plugin_url(), true));
        fclose($fh);
        global $wpdb;
        $q = 'SELECT wp_group_range.user_group, wp_group_range.line, wp_group_range.min, wp_group_range.max FROM wp_group_range ORDER BY wp_group_range.user_group, wp_group_range.line;';
        $q_results = $wpdb->get_results($q);
        $values = json_encode($q_results);
        $textarea_texts = array();
        ?>
            <style>
                .group-title {
                    font-size: 15px;
                    letter-spacing: 3px;
                }
                .textarea-user-group{
                    width: 200px;
                    margin: 0 auto;
                    line-height:16px;
                    padding-top: 10px;
                    padding-left: 35px;
                    border-color:#CCC;
                    background-image: url(http://localhost/wordpress/wp-content/uploads/2021/09/line-numbers.png);
                    background-attachment: local;
                    background-color: #FFF;
                    background-repeat: no-repeat;
                    resize: none;
                    box-sizing: border-box;
                    -webkit-box-sizing: border-box;
                    -moz-box-sizing: border-box;
                    white-space: pre-wrap; 
                }                
            </style>
            <table class="form table" style='width: 100%; max-width: 900px; border-collapse: collapse;'>
                <tbody>
                <tr>
                        <td colspan='100'>
                            <div style='width: 100%'><h3>Grupo 1.X.X - Segunda-feira</h3></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='width: 200px; margin: auto; padding: 5px 0px; text-align: center;'>
                                <span class='group-title'><b>1.1.1</b></span> (Manhã - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>1.1.2</b></span> (Manhã - 2)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>1.2.1</b></span> (Tarde - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>1.2.2</b></span> (Tarde - 2)
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='111' name='postcode-list-111' class='textarea-user-group' rows='10' placeholder='Exemplo:&#13;&#10;01000 a 01099&#13;&#10;01100 a 01135&#13;&#10;01136 a 01199'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='112' name='postcode-list-112' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='121' name='postcode-list-121' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='122' name='postcode-list-122' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='100'>
                            <div style='width: 100%; padding-top: 15px;'><h3>Grupo 2.X.X - Terça-feira</h3></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='width: 200px; margin: auto; padding: 5px 0px; text-align: center;'>
                                <span class='group-title'><b>2.1.1</b></span> (Manhã - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>2.1.2</b></span> (Manhã - 2)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>2.2.1</b></span> (Tarde - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>2.2.2</b></span> (Tarde - 2)
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='211' name='postcode-list-211' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='212' name='postcode-list-212' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='221' name='postcode-list-221' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='222' name='postcode-list-222' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='100'>
                            <div style='width: 100%; padding-top: 15px;'><h3>Grupo 3.X.X - Quarta-feira</h3></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='width: 200px; margin: auto; padding: 5px 0px; text-align: center;'>
                                <span class='group-title'><b>3.1.1</b></span> (Manhã - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>3.1.2</b></span> (Manhã - 2)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>3.2.1</b></span> (Tarde - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>3.2.2</b></span> (Tarde - 2)
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='311' name='postcode-list-311' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='312' name='postcode-list-312' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='321' name='postcode-list-321' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='322' name='postcode-list-322' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='100'>
                            <div style='width: 100%; padding-top: 15px;'><h3>Grupo 4.X.X - Quinta-feira</h3></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='width: 200px; margin: auto; padding: 5px 0px; text-align: center;'>
                                <span class='group-title'><b>4.1.1</b></span> (Manhã - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>4.1.2</b></span> (Manhã - 2)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>4.2.1</b></span> (Tarde - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>4.2.2</b></span> (Tarde - 2)
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='411' name='postcode-list-411' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='412' name='postcode-list-412' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='421' name='postcode-list-421' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='422' name='postcode-list-422' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='100'>
                            <div style='width: 100%; padding-top: 15px;'><h3>Grupo 5.X.X - Sexta-feira</h3></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='width: 200px; margin: auto; padding: 5px 0px; text-align: center;'>
                                <span class='group-title'><b>5.1.1</b></span> (Manhã - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>5.1.2</b></span> (Manhã - 2)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>5.2.1</b></span> (Tarde - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>5.2.2</b></span> (Tarde - 2)
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='511' name='postcode-list-511' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='512' name='postcode-list-512' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='521' name='postcode-list-521' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='522' name='postcode-list-522' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='100'>
                            <div style='width: 100%; padding-top: 15px;'><h3>Grupo 6.X.X - Sábado</h3></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='width: 200px; margin: auto; padding: 5px 0px; text-align: center;'>
                                <span class='group-title'><b>6.1.1</b></span> (Manhã - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>6.1.2</b></span> (Manhã - 2)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>6.2.1</b></span> (Tarde - 1)
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='width: fit-content; margin: auto; padding: 5px 0px;'>
                                <span class='group-title'><b>6.2.2</b></span> (Tarde - 2)
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='611' name='postcode-list-611' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='612' name='postcode-list-612' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='621' name='postcode-list-621' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                        <td colspan='25'>
                            <div style='display:flex; padding: 5px;'>
                                <textarea id='622' name='postcode-list-622' class='textarea-user-group' rows='10'></textarea>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>            
            <script>
                let json_q_result = '<?php echo $values; ?>';
                let q_result = JSON.parse(json_q_result);
                let formated_result = [];
                let textarea_line = [];                    
                q_result.map((result, index)=>{
                    if (result.user_group){
                        let short_group = result.user_group.replace(/\D+/g, '');
                        let line = result.line
                        let min = (result.min > result.max) ? result.max : result.min;
                        let max = (result.max > result.min) ? result.max : result.min;
                        let data = [line, min, max]; 
                        if (!textarea_line[short_group]){ textarea_line[short_group] = ''; }      
                        textarea_line[short_group] = textarea_line[short_group] + min + ' a ' + max + '\r';
                        if (!formated_result[result.user_group]){ formated_result[result.user_group]= []; }                 
                        formated_result[result.user_group].push(data);
                        //console.log(short_group);
                    }
                });
                jQuery( document ).ready(function() {
                    for(let i = 1; i <= 6; i++){
                        for(let j = 1; j <= 2; j++){
                            for(let k = 1; k <= 2; k++){
                                jQuery('textarea#'+''+i+''+j+''+k).val(textarea_line[i+''+j+''+k]);
                            }
                        }
                    }
                }); 
            </script>
        <?php
    }
    public static function update_settings() {
        if (isset($_POST)){            
            global $wpdb;
            $q_base = "INSERT INTO wp_group_range ( wp_group_range.user_group, wp_group_range.line,wp_group_range.min, wp_group_range.max) VALUES ";
            $data = array();
            $errors = '';
            for($i = 1; $i <= 6; $i++){
                for($j = 1; $j <= 2; $j++){
                    for($k = 1; $k <= 2; $k++){
                        if (isset($_POST['postcode-list-'.$i.$j.$k])){
                            $data[$i.$j.$k] = str_replace("\r", '', $_POST['postcode-list-'.$i.$j.$k]);
                            $data[$i.$j.$k] = str_replace("\n" , ';', $data[$i.$j.$k]);
                            $data[$i.$j.$k] = explode(';', $data[$i.$j.$k]);
                            $index = 1;
                            foreach($data[$i.$j.$k] as $line){
                                if (!$line){ continue; }
                                if (preg_match('/\d{5}\sa\s\d{5}/', $line)){
                                    $postcodes = $data[$i.$j.$k];
                                    $postcodes = explode(' a ', $line);
                                    $value = '("'.$i.'.'.$j.'.'.$k.'", "'.$index.'", "'.$postcodes[0].'", "'.$postcodes[1].'"),';
                                    if (!str_contains($q_base, $value)){ 
                                        $q_base = $q_base.'("'.$i.'.'.$j.'.'.$k.'", "'.$index.'", "'.$postcodes[0].'", "'.$postcodes[1].'"),';
                                        
                                    }
                                }else{ 
                                    if ($errors == ''){ $errors = 'Grupo: '.$i.'.'.$j.'.'.$k.' linha '.$index.';'; }else{ $errors = $errors.' Grupo: '.$i.'.'.$j.'.'.$k.' linha '.$index.';'; }
                                }
                                $index = $index + 1;
                            }
                        }
                    }
                }
            }
            if ($errors == ''){
                $q = rtrim($q_base, ', ');
                $q = $q . ';';
                $wpdb->get_results('TRUNCATE TABLE wp_group_range_backup;');                   
                $wpdb->get_results('INSERT INTO wp_group_range_backup SELECT * FROM wp_group_range;');
                $wpdb->get_results('TRUNCATE TABLE wp_group_range;');                
                $wpdb->get_results($q);
                ?><script>alert('Seus dados foram atualizados com sucesso.');</script><?php
                //WC()->wc_add_notice( 'Seus dados foram atualizados com sucesso.', 'success' );
            }else{
                //WC()->wc_print_notice( 'Verifique se não há nenhuma faixa de CEP inválida .', 'error' );
                ?><script>alert('<?php echo $errors; ?>');</script><?php
            }
        }
        woocommerce_update_options( self::get_settings() );
    }
    public static function get_settings(){
        $settings = array();
        return apply_filters( 'wc_settings_tab_user_group_settings', $settings );
    }
}
WC_Settings_Tab_User_Group::init();