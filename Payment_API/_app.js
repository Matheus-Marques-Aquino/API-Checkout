const express = require('express');
const bodyParser = require('body-parser');
const request = require('request');
const { calcularPrecoPrazo, consultarCep, rastrearEncomenda } = require('correios-brasil');
const app = express();
const port = 4000;

const domain_url = 'http://localhost/wordpress';
const authentication_basic = 'Basic YWRtaW46c2VuaGE=';
const apiKey_PagHiper = 'apk_45442557-elEDgAbnpUpUHNDodleWZhtFcfELaGnF';
const apiToken_PagHiper = '';
const mysql      = require('mysql');

var connection = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'wordpress'
});
var CPF = require('cpf_cnpj').CPF;
var CNPJ = require('cpf_cnpj').CNPJ;

connection.connect();

app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(function (req, res, next) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE');
    res.setHeader('Access-Control-Allow-Headers', 'X-Requested-With,Content-Type');
    res.setHeader('Access-Control-Allow-Credentials', false);
    next();
});

app.listen(port, ()=>{ console.log(`Listening at http://localhost:${port}`); });

app.post('/checkout-individual', async (req, res)=>{
    let data = req.body;
    let checkout_data = {};
    let errorLog = [];
    let valid = true;
    let validation_data = {
        payment_data: {},
        product: { on_sale: null, instock: null, price: null },
        delivery: { method_id: null, price: null },
        subtotal: { products: [], price: null },
        cupom: { cupom_code: null, price: null },
        final_price: null
    }
    if (!data){ return; }
    //if (JSON.stringify(data) == JSON.stringify({})){ return; }
    //console.log('---')
    console.log('Data:', data)
    
    if (!data){ errorLog.push('Ocorreu um erro durante o envio do formulário, atualize a página e tente novamente.'); }

    if (!data.user){ data.user = {}; }
    if (!data.variation){ data.variation = {}; }
    if (!data.payment){ data.payment = {}; }
    if (!data.box){ data.box = {}; }
    if (!data.price){ data.price = {}; }
    if (!data.delivery){ data.delivery = {}; }
    if (!data.shipping){ data.shipping = {}; }
    

    checkout_data.user = {
        id: data.user.id,//'1',
        login: data.user.login,
        iugu_cutomer_id: data.user.iugu_id,
        name: data.user.name,
        first_name: data.user.first_name,//'Matheus',
        last_name: data.user.last_name,//'Marques',
        address_1: data.user.address_1,//'Rua Coronel Camisão',
        number: data.user.number,//'91',
        address_2: data.user.address_2,//'Casa',
        city: data.user.city,//'São Caetano do Sul',
        state: data.user.state,//'SP',
        neighborhood: data.user.neighborhood,//'',        
        postcode: data.user.postcode,//'09571-020',
        country: 'Brasil',//'Brasil',
        email: data.user.email,//'matheus.marques.aquino@gmail.com',
        phone: data.user.phone,//'(11) 97954-4109',
        cpf: data.user.cpf,//'461.716.198-84',
        subscriber: data.user.subscription_active,//true
        group: data.user.group,
        ip: data.user.ip,
    };
    checkout_data.payment = {
        index: data.payment.index,
        iugu_token: data.payment.token,//'c5daa161-6bfa-49c0-afad-484d515cfd2a',
        method_id: data.payment.payment_id,//'iugu-cc',
        method_title: data.payment.payment_title,//'Iugu - Cartão de Crédito',
        total: data.price.total,//'3350'
    };
    checkout_data.discount = [{
        code: '',//data.,//'',
        total: parseFloat(0)//data.,//'0',
    }],
    checkout_data.products = [{
        id: data.variation.id,//'45',
        name: data.variation.name,//'Roupa A',        
        product_id: data.variation.product_id,//'127',
        variation_id: data.variation.variation_id,//'0',
        quantity: data.variation.quantity,//'1',
        price: parseFloat(data.variation.price),//'2000'
        attributes: data.variation.attributes,
        owner_id: data.variation.owner_id,
        on_sale: data.variation.on_sale
    }],
    checkout_data.shipping = {
        method_index: data.delivery.index,//1,
        method_id: data.shipping.method_id,//'correios-sedex',
        method_title: data.shipping.method_title,//'Correios - SEDEX',
        total: data.shipping.total,//'1350'
    }
    checkout_data.box = {
        altura: data.box.altura,//'6',
        largura: data.box.largura,//'40',
        comprimento: data.box.comprimento,//'50',
        peso: data.box.peso,// '0,4'
    }

    if (!checkout_data.user){ errorLog.push('A'); }
    if (!checkout_data.user.id){ errorLog.push('Erro: O usuário não foi encntrado, tente sair e entrar novamente em sua conta.'); }
    if (!checkout_data.user.first_name){ errorLog.push('B'); valid = false; }else{
        if (checkout_data.user.first_name.length < 2){ errorLog.push('B1'); valid = false; }
    }
    if (!checkout_data.user.last_name){ errorLog.push('C'); valid = false; }else{
        if (checkout_data.user.last_name.length < 2){ errorLog.push('C1'); valid = false; }
    }
    if (!checkout_data.user.address_1){ errorLog.push('D'); valid = false; }else{
        if (checkout_data.user.city.length < 2){ errorLog.push('D1'); valid = false; }
    }
    if (!checkout_data.user.number){ errorLog.push('E'); valid = false; }
    if (!checkout_data.user.city){ errorLog.push('F'); valid = false; }else{
        if (checkout_data.user.city.length < 2){ errorLog.push('F1'); valid = false; }
    }
    if (!checkout_data.user.neighborhood){ errorLog.push('G'); valid = false; }else{
        if (checkout_data.user.neighborhood.length < 2){ errorLog.push('G1'); valid = false; }
    }
    if (!checkout_data.products){ errorLog.push('H'); valid = false; }
    if (!checkout_data.user.email){ errorLog.push('I'); valid = false; }
    if (!checkout_data.user.phone){ errorLog.push('J'); valid = false; }else{
        if (checkout_data.user.phone.replace('(', '').replace(')', '').replace('-', '').length < 11){ errorLog.push('J1'); valid = false; }
    }
    if (!checkout_data.user.cpf){ errorLog.push('K'); valid = false; }else{        
        if (!CPF.isValid(checkout_data.user.cpf)){ errorLog.push('K1'); valid = false; }
    }
    if (!checkout_data.user.subscriber){ errorLog.push('L'); valid = false; }
    if (!checkout_data.user.group && checkout_data.shipping.index == 1){ errorLog.push('M'); valid = false; }else{
        //if (!(/^[0-9]\.[0-9]\.[0-9]/.test(data.user.group))){ errorLog.push('M1'); }
    } 
    let base = {
        sCepOrigem: '03162040',
        sCepDestino: data.user.postcode.replace('-', ''),
        nVlPeso: data.box.peso,
        nCdFormato: '1',
        nVlComprimento: data.box.comprimento,
        nVlAltura: data.box.altura,
        nVlLargura: data.box.largura,
        nVlDiametro: '0',
    }
    if (!data.delivery.index){ valid = false; }else{
        switch(data.delivery.index.toString()){
            case '1':
                if (data.delivery.motoboy != 0){ valid = false; break; }
                if (!data.user.subscriber){ valid = false; }
                validation_data.delivery.price = 0;
                validation_data.delivery.method_id = 'weuse_motoboy';
                break;
            case '3':
                if (!data.delivery.sedex){ valid = false; break; }
                base.nCdServico = ['04014'];
                await calcularPrecoPrazo(base).then((correios)=>{
                    if (correios.length < 1){ valid = false; }
                    validation_data.delivery.price = parseInt(correios[0].Valor.replace(/\D+/g, '')) * 1.1;
                    validation_data.delivery.method_id = 'correios_sedex';
                });
                break;
            case '2':
                base.nCdServico = ['04510'];
                //if (!data.delivery.pac){ valid = false; break; }
                await calcularPrecoPrazo(base).then((correios)=>{
                    if (correios.length < 1){ valid = false; }
                    validation_data.delivery.price = parseInt(correios[0].Valor.replace(/\D+/g, '')) * 1.1;
                    validation_data.delivery.method_id = 'correios_pac';
                });
                break;
        }
    }    
    validation_data.product = await get_product(data.variation.id, 1);
    if (validation_data.product.error){ errorLog.push('Ocorreu um erro inesperado durante a validação do produto.'); valid = false; }
    if (!validation_data.product.price){ errorLog.push('Ocorreu um erro inesperado durante a validação do produto.'); valid = false; }
    if (validation_data.product.price < 300){ errorLog.push('Ocorreu um erro inesperado durante a validação do produto.'); valid = false; }
    //if (!validation_data.product.instock){ errorLog.push('Infelizmente este produto já foi vendido.'); } aluguél bugaria o sistema
    if (!validation_data.product.on_sale){ errorLog.push('Infelizmente este produto esta indisponível.'); valid = false; }
    

    validation_data.cupom = { cupom_code: '', price: 0 };
    validation_data.subtotal.products.push(validation_data.product);
    validation_data.subtotal.products.map((product)=>{ if (!product.error){ if (product.price){ validation_data.subtotal.price += product.price * product.quantity; } } });
    validation_data.final_price = validation_data.subtotal.price + validation_data.delivery.price - validation_data.cupom.price;
   
    if (validation_data.final_price / 100 != data.price.total){ errorLog.push('Ocorreu um erro durante o processamento do pedido, tente novamente mais tarde.'); valid = false; }
    
    validation_data.valid = valid;
    //validation_data.subtotal = variation.price * 
    console.log('Final Result:', errorLog);
    console.log('Valid Data:', validation_data);
    
    res.status(200).json({result: 'ok', errorLog: []});
});
function get_product(id, quantity){
    return new Promise(function (resolve, reject) {
        request.get({
            url: domain_url + '/wp-json/wc/v3/products/'+id,// + data.variation.id,      
            headers: {
                'Accept' : 'application/json', 
                'Content-Type': 'application/json',
                'Authorization': authentication_basic        
            }
        },
        (error, res, body)=>{ 
            /*if (error){ console.log(error); }
            //console.log(error);  
            //console.log(res); 
            //console.log(); */   
            let product = {id: id, quantity: quantity, images: [], attributes: [], error: false};    
            if ( error ){
                product.error = true;
                product.error_data = error;
                reject( error );
                return product; 
            }
            let product_data = JSON.parse(body);
            let product_meta = product_data.meta_data;   
            let product_images = product_data.images;
            let product_attributes = product_data.attributes;
            
            product.instock = (product_data.stock_status == 'instock') ? true : false;
            if (Array.isArray(product_images)){ product_images.map((image)=>{ if (image.src){ product.images.push({id: image.id, name: image.name, src: image.src}); } }); }
            if (Array.isArray(product_attributes)){ product_attributes.map((attribute)=>{ if (attribute.name && attribute.option){ product.attributes.push({id: attribute.id, name: attribute.name, value: attribute.option}); } }); }
            if (Array.isArray(product_meta)){ 
                product_meta.map((meta)=>{
                    if (meta.key == '_weuse_variation_price'){ product.price = parseFloat(meta.value) * 100; }
                    if (meta.key == '_weuse_variation_on_sale'){product.on_sale = meta.value ? true : false; }
                }); 
            }      
            //console.log('Product:', product);
            resolve(product);
        });
    });
}
app.post('/calcular-frete', (req, res)=>{  
    let data = req.body;
    //console.log('Data:', data);
    
    let errorLog = [];
    let result = [];
    let base = {
        sCepOrigem: '03162040',
        sCepDestino: '', //<-- 
        nVlPeso: '0.4', //<--LOCKED 
        nCdFormato: '1',
        nVlComprimento: '40', //<-- locked
        nVlAltura: '6', //<-- locked
        nVlLargura: '50', //<-- locked
        nCdServico: ['04014', '04510'], //(SEDEX), (PAC))
        nVlDiametro: '0',       
    }
    if (data.cep == undefined){
        errorLog.push('O CEP inserido não é válido.');
        res.status(200).json({result: false, errorLog: errorLog});
        return;
    }
    if (!(/^[0-9]{8}$/.test(data.cep.toString().replace('-', '')))){
        errorLog.push('O CEP inserido não é válido.');
        res.status(200).json({result: false, errorLog: errorLog});
        return;
    }
    if (data.altura == undefined || data.largura == undefined || data.comprimento == undefined || data.peso == undefined){
        errorLog.push('Ocorreu um erro durante o cálculo do frete.');
        res.status(200).json({result: false, errorLog: errorLog});
        return;
    }
    base.sCepDestino = data.cep.replace('-', '');   
    base.nVlAltura = data.altura;
    base.nVlLargura = data.largura; 
    base.nVlComprimento = data.comprimento;
    base.nVlPeso = data.peso;
    calcularPrecoPrazo(base).then((correios)=>{
        let shippingOptions = [];
        shippingOptions['04014'] = 'SEDEX';
        shippingOptions['04065'] = 'SEDEX';
        shippingOptions['04510'] = 'PAC';
        shippingOptions['04707'] = 'PAC';
        shippingOptions['40169'] = 'SEDEX 12';
        shippingOptions['40215'] = 'SEDEX 10';
        shippingOptions['40290'] = 'SEDEX Hoje';
        correios.map((service, index)=>{
            let _service = {
                serviceName: ( shippingOptions[service.Codigo] ) ? shippingOptions[ service.Codigo ] : 'Correios',
                serviceCode: service.Codigo,
                price: parseFloat(service.Valor.replace(',', '.') * 1.1).toFixed(2),
                deliveryTime: parseInt(service.PrazoEntrega) + 2,
                error: service.Erro,
                errorMsg: service.MsgErro
            }
            result.push(_service);
        }); 
        console.log(result);
        res.status(200).json({result: result, errorLog: errorLog});
    });   
    
});

function validate_basic_data(checkout_data){
    let valid = true;
    console.log(JSON.parse(checkout_data));
    if (!checkout_data.user){ valid = false; errorLog.push('A'); }
    if (!checkout_data.user.id){ valid = false; errorLog.push('Erro: O usuário não foi encntrado, tente sair e entrar novamente em sua conta.'); }
    if (!checkout_data.user.first_name){ valid = false; errorLog.push('B'); }else{
        if (checkout_data.user.first_name.length < 2){ valid = false; errorLog.push('B1'); }
    }
    if (!checkout_data.user.last_name){ valid = false; errorLog.push('C'); }else{
        if (checkout_data.user.last_name.length < 2){ valid = false; errorLog.push('C1'); }
    }
    if (!checkout_data.user.address_1){ valid = false; errorLog.push('D'); }else{
        if (checkout_data.user.city.length < 2){ valid = false; errorLog.push('D1'); }
    }
    if (!checkout_data.user.number){ valid = false; errorLog.push('E'); }
    if (!checkout_data.user.city){ valid = false; errorLog.push('F'); }else{
        if (checkout_data.user.city.length < 2){ valid = false; errorLog.push('F1'); }
    }
    if (!checkout_data.user.neighborhood){ valid = false; errorLog.push('G'); }else{
        if (checkout_data.user.neighborhood.length < 2){ valid = false; errorLog.push('G1'); }
    }
    if (!checkout_data.products){ valid = false; errorLog.push('H'); }
    if (!checkout_data.user.email){ valid = false; errorLog.push('I'); }
    if (!checkout_data.user.phone){ valid = false; errorLog.push('J'); }else{
        if (checkout_data.user.phone.replace('(', '').replace(')', '').replace('-', '').length < 11){ valid = false; errorLog.push('J1'); }
    }
    if (!checkout_data.user.cpf){ valid = false; errorLog.push('K'); }else{        
        if (!CPF.isValid(checkout_data.user.cpf)){ valid = false; errorLog.push('K1'); }
    }
    if (!checkout_data.user.subscriber){ valid = false; errorLog.push('L'); }
    if (!checkout_data.user.group && checkout_data.shipping.index == 1){ valid = false; errorLog.push('M'); }else{
        //if (!(/^[0-9]\.[0-9]\.[0-9]/.test(data.user.group))){ errorLog.push('M1'); }
    }
    console.log('LOG:', errorLog);
    validate_user(checkout_data);
}

function validate_user(data){
    if (!data){ errorLog.push('Não foi possível autenticar seu usuário, tente atualizar a página ou iniciar uma nova sessão. 2'); return }
    if (!data.user){ errorLog.push('Não foi possível autenticar seu usuário, tente atualizar a página ou iniciar uma nova sessão. 3'); return; }
    if (!data.user.id){ errorLog.push('Não foi possível autenticar seu usuário, tente atualizar a página ou iniciar uma nova sessão. 4'); return; }
    connection.query('SELECT id, user_login FROM wp_users WHERE id = ' + data.user.id + ';', function (error, results, fields) {
        if (error){ 
            console.log(error); 
            errorLog.push('Não foi possível autenticar seu usuário, tente atualizar a página ou iniciar uma nova sessão. B');
        }
        if (results.id != data.user.id){
            errorLog.push('Não foi possível autenticar seu usuário, tente atualizar a página ou iniciar uma nova sessão. A');
        }        
        console.log(results); 
        return { valid: true, errorLog: [] };
        
    });
    //connection.end();
}

function checkout_chain(data){
    let checkout_data = {};      
    if (!data){ return {}; } 
    //order_id: 220,
    if (!data.user){ data.user = {}; }
    if (!data.variation){ data.variation = {}; }
    if (!data.payment){ data.payment = {}; }
    if (!data.box){ data.box = {}; }
    if (!data.price){ data.price = {}; }
    if (!data.delivery){ data.delivery = {}; }
    if (!data.shipping){ data.shipping = {}; }

    checkout_data.user = {
        id: data.user.id,//'1',
        login: data.user.login,
        iugu_cutomer_id: data.user.iugu_id,
        name: data.user.name,
        first_name: data.user.first_name,//'Matheus',
        last_name: data.user.last_name,//'Marques',
        address_1: data.user.address_1,//'Rua Coronel Camisão',
        number: data.user.number,//'91',
        address_2: data.user.address_2,//'Casa',
        city: data.user.city,//'São Caetano do Sul',
        state: data.user.state,//'SP',
        neighborhood: data.user.neighborhood,//'',        
        postcode: data.user.postcode,//'09571-020',
        country: 'Brasil',//'Brasil',
        email: data.user.email,//'matheus.marques.aquino@gmail.com',
        phone: data.user.phone,//'(11) 97954-4109',
        cpf: data.user.cpf,//'461.716.198-84',
        subscriber: data.user.subscription_active,//true
        group: data.user.group,
        ip: data.user.ip,
    };
    checkout_data.payment = {
        index: data.payment.index,
        iugu_token: data.payment.token,//'c5daa161-6bfa-49c0-afad-484d515cfd2a',
        method_id: data.payment.payment_id,//'iugu-cc',
        method_title: data.payment.payment_title,//'Iugu - Cartão de Crédito',
        total: data.price.total * 100,//'3350'
    };
    checkout_data.discount = [{
        code: '',//data.,//'',
        total: 0//data.,//'0',
    }],
    checkout_data.products = [{
        id: data.variation.id,//'45',
        name: data.variation.name,//'Roupa A',        
        product_id: data.variation.product_id,//'127',
        variation_id: data.variation.variation_id,//'0',
        quantity: data.variation.quantity,//'1',
        price: data.variation.price * 100,//'2000'
        attributes: data.variation.attributes,
        owner_id: data.variation.owner_id,
        on_sale: data.variation.on_sale
    }],
    checkout_data.shipping = {
        method_index: data.delivery.index,//1,
        method_id: data.shipping.method_id,//'correios-sedex',
        method_title: data.shipping.method_title,//'Correios - SEDEX',
        total: data.shipping.total * 100,//'1350'
    }
    checkout_data.box = {
        altura: data.box.altura,//'6',
        largura: data.box.largura,//'40',
        comprimento: data.box.comprimento,//'50',
        peso: data.box.peso,// '0,4'
    }
    console.log(checkout_data)
    //validate_basic_data(checkout_data);
}
var _data = {
    id: 220,
    user: {
        id: '1',
        first_name: 'Matheus',
        last_name: 'Marques',
        address_1: 'Rua Coronel Camisão',
        number: '91',
        address_2: 'Casa',
        city: 'São Caetano do Sul',
        state: 'SP',
        neighborhood: '',        
        postcode: '09571-020',
        country: 'Brasil',
        email: 'matheus.marques.aquino@gmail.com',
        phone: '(11) 97954-4109',
        cpf: '461.716.198-84',
        subscriber: true
    },
    payment: {
        iugu_token: 'c5daa161-6bfa-49c0-afad-484d515cfd2a',
        method_id: 'iugu-cc',
        method_title: 'Iugu - Cartão de Crédito',
        total: '3350'
    },
    discount:[{
        code: '',
        total: '0',
    }],
    products:[{
        id: '45',
        name: 'Roupa A',        
        product_id: '127',
        variation_id: '0',
        quantity: '1',
        price: '2000'
    }],
    shipping: {
        method_index: 1,
        method_id: 'correios-sedex',
        method_title: 'Correios - SEDEX',
        total: '1350'
    },
    box: {
        altura: '6',
        largura: '40',
        comprimento: '50',
        peso: '0,4'
    }
};
//check_delivery_price(_data);
function check_delivery_price(_data){
    var result = [];
    let codeName = {
        sedex: '04014',
        sedex_2: '04065',
        pac: '04510',
        pac_2: '04707',
        sedex_12: '40169',
        sedex_10: '40215',
        sedex_hoje: '40290'
    }
    let base = {
        sCepOrigem: '03162040',
        sCepDestino: _data.user.postcode.replace('-', ''), //<-- 
        nVlPeso: _data.box.peso, //<--LOCKED 
        nCdFormato: '1',
        nVlComprimento: _data.box.comprimento, //<-- locked
        nVlAltura: _data.box.altura, //<-- locked
        nVlLargura: _data.box.largura, //<-- locked
        nCdServico: ['04014', '04510'], //(SEDEX), (PAC))
        nVlDiametro: '0',       
    };
    console.log(base)
    calcularPrecoPrazo(base).then((correios)=>{
        let shippingOptions = [];
        shippingOptions['04014'] = 'SEDEX';
        shippingOptions['04065'] = 'SEDEX';
        shippingOptions['04510'] = 'PAC';
        shippingOptions['04707'] = 'PAC';
        shippingOptions['40169'] = 'SEDEX 12';
        shippingOptions['40215'] = 'SEDEX 10';
        shippingOptions['40290'] = 'SEDEX Hoje';
        correios.map((service, index)=>{
            let _service = {
                serviceName: ( shippingOptions[service.Codigo] ) ? shippingOptions[ service.Codigo ] : 'Correios',
                serviceCode: service.Codigo,
                price: parseFloat(service.Valor.replace(',', '.') * 1.1).toFixed(2),
                deliveryTime: parseInt(service.PrazoEntrega) + 2,
                error: service.Erro,
                errorMsg: service.MsgErro
            }
            result.push(_service);
        });         
    });
    console.log(result)
    return result;
}

//validate_shippping(_data);
function validate_shippping(data){
    let base = {
        sCepOrigem: '03162040',
        sCepDestino: data.user.postcode.replace('-', ''),
        nVlPeso: data.box.peso,
        nCdFormato: '1',
        nVlComprimento: data.box.comprimento,
        nVlAltura: data.box.altura,
        nVlLargura: data.box.largura,
        nVlDiametro: '0',
    }

    switch(data.shipping.method_index.toString()){
        case '0':
            if (data.user.subscriber){ return true; }
            break;
        case '1':
            base.nCdServico = ['04014'];
            calcularPrecoPrazo(base).then((correios)=>{
                if (correios.length < 1){ return false; }
                console.log(correios);
            });
            return;
        case '2':
            base.nCdServico = ['04510'];
            calcularPrecoPrazo(base).then((correios)=>{
                if (correios.length < 1){ return false; }
                console.log(correios);
            });
            return;
    }
}

//get_user_data(_data);
function get_user_data(data){
    request.get({
        url: domain_url + '/wp-json/wp/v2/users/' + data.user.id,      
        headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json',
            'Authorization': authentication_basic        
        },
        body: JSON.stringify({
            id: data.user.id,
            context: "edit"
        })
    },
    (error, res, body)=>{ 
        if (error){ console.log(error); return; }
        //console.log(error);  
        //console.log(res); 
        console.log(JSON.parse(body));           
    });
}
//create_order(_data);
function create_order(data){
    let products = [];
    let user = data.user;
    let shipping = data.shipping;
    if (data.products){
        data.products.map((product, index)=>{
            let item = {
                product_id: parseInt(product.product_id),
                variation_id: parseInt(product.variation_id),
                quantity: parseInt(product.quantity),
                price: parseFloat('20.00')
            }
            products.push(item);       
        });
    }
    /*request.post({ 
        url: domain_url + '/wp-json/wc/v3/orders',      
        headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json',
            'Authorization': authentication_basic        
        },
        body: JSON.stringify({
            customer_id: user.id,
            payment_method: payment.method_id,
            payment_method_title: payment.method_title,
            set_paid: false,
            billing: {
                first_name: user.first_name,
                last_name: user.last_name,
                address_1: user.address_1,
                address_2: user.address_2,
                city: user.city,
                state: user.state,
                postcode: user.postcode,
                country: 'Brasil',
                email: user.email,
                phone: user.phone
            },
            shipping: {
                first_name: user.first_name,
                last_name: user.last_name,
                address_1: user.address_1,
                address_2: user.address_2,
                city: user.city,
                state: user.state,
                postcode: user.postcode,
                country: 'BR'
            },
            line_items: products,
            shipping_lines: [{
                method_id: shipping.method_id,
                method_title: shipping.method_title,
                total: shipping.total.toString()
            }],
            metadata:[
                { key: '_billing_number', value:  },
                { key: '_billing_cpf', value:  },
                { key: '_billing_birthdate', value:  },
                { key: '_billing_number', value:  },
                { key: '_billing_neighborhood', value: user.a },
                { key: 'invoice_id', 'value': '1' }
            ]
            	
        })   
        },
        (error, res, body)=>{ 
            if (error){ console.log(error); return; }
            console.log(body);            
        }
    );*/
}
let order_data = {
    order_id: '198',
    updates: { status: random_status() }
};
function random_status(){
    let status_list = [ 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ];
    return status_list[Math.floor(Math.random()*status_list.length)];
}
//update_order(order_data)
function update_order(data){
    request.put({
        uri: domain_url + '/wp-json/wc/v3/orders/' + data.order_id,
        headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json',
            'Authorization': authentication_basic        
        },
        body: JSON.stringify(data.updates)    
    },
    (error, res, body)=>{ 
        if (error){ console.log(error); return; }
        console.log(body);            
    });
}
function find_order(data){
    connection.connect();

    connection.end();
}
function fatura_boleto_paghiper(data){
    let products = [];
    let user = data.user;
    let address = data.address;
    let notification_url = domain_url + '/payment_notification/paghiper-boleto';
    if (data.products){
        data.products.map((product, index)=>{
            let item = {
                item_id: product.product_id,
                description: product.name,
                quantity: parseInt(product.quantity),
                price_cents: parseInt(product.price)
            }
            products.push(item);
        });
    }
    request.post({ 
        url: 'https://api.paghiper.com/transaction/create/',      
        headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json'       
        },
        body: JSON.stringify({
            apiKey: apiKey_PagHiper,
            order_id: data.order.order_id,
            payer_email: user.email,
            payer_name: user.name, 
            payer_cpf_cnpj: user.cpf.replace(/\D/g, ''),
            payer_phone: user.phote.replace(/\D/g, ''), 
            payer_street: address.address_1,
            payer_number: address.number, 
            payer_complement: address.address_2,
            payer_district: address.neiborhood,
            payer_city: address.city,
            payer_state: address.uf,
            days_due_date: 5,
            notification_url: notification_url,
            discount_cents: data.discount.total,
            shipping_price_cents: data.shipping.total,
            shipping_methods: data.shipping.method_title,
            fixed_description: true,
            type_bank_slip: "boletoA4", 
            per_day_interest: true, 
            items: products
        })},        
        (error, res, body)=>{ 
            if (error){ console.log(error); return; }
            console.log(body);            
        }
    );
}
//fatura_pix_paghiper(_data);
function fatura_pix_paghiper(data){
    let products = [];
    let user = data.user;
    let notification_url = domain_url + '/payment_notification/paghiper-pix';
    if (data.products){
        data.products.map((product, index)=>{
            let item = {
                item_id: (product.variation_id) ? product.variation_id : product.product_id,
                description: product.name,
                quantity: parseInt(product.quantity),
                price_cents: parseInt(product.price)
            }
            products.push(item);
        });
    }
    request.post({ 
        url: 'https://api.paghiper.com/transaction/create/',      
        headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json'       
        },
        body: JSON.stringify({
            apiKey: apiKey_PagHiper,
            order_id: data.order_id,
            payer_email: user.email,
            payer_name: user.first_name.trim() + ' ' + user.last_name.trim(),
            payer_cpf_cnpj: user.cpf.replace(/\D/g, ''),
            payer_phone: user.phone,
            discount_cents : data.discount.total,
            shipping_price_cents : data.payment.total,
            shipping_methods : data.shipping.method_title,
            fixed_description : true,
            days_due_date : 3,
            discount_cents: data.discount.total,
            type_bank_slip: 'boletoA4',
            items: products
        })},
        (error, res, body)=>{ 
            if (error){ console.log(error); return; }
            console.log(body);            
        }
    );        
}
function fatura_iugu_credit_card(data){
    let user = data.user;
    let address = data.user;
    let payment = data.payment;
    let products = [];
    let notification_url = domain_url + '/payment_notification/iugu-credit-card'

    if (data.products){
        data.products.map((product, index)=>{
            let item = {
                description: product.name,
                quantity: parseInt(product.quantity),
                price_cents: parseInt(product.price)
            }
            products.push(item);
        });
    }
    request.post({ 
        url: 'https://api.iugu.com/v1/charge',      
        headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json',
            'Authorization': 'Basic NDk1QTlEMTlDNTU3N0QzNUZFQjYwNDVBNEU0RTRCRDhFNTFDNDVEOThGRTk5QUM1MzQ1MTg2RjFCMTQ0RkVGNzo='        
        },
        body: JSON.stringify({
            notification_url: notification_url,
            restrict_payment_method: false,
            token: payment.iugu_token,
            customer_id: payment.iugu_id,
            email: user.email,
            items: products,
            payer: { 
                cpf_cnpj: user.cpf,
                name: user.frist_name + ' ' + user.last_name, 
                phone_prefix: user.phone.replace(/\D/g, '').slice(0, 2),
                phone: user.phone.replace(/\D/g, '').slice(2), 
                email: user.email, 
                address:{
                        zip_code: address.postcode, 
                        street: address.address_1,
                        number: address.number,
                        district: address.neiborhood,
                        city: address.city,
                        state: address.state,
                        country: 'Brasil',
                        complement: address.address_2
                    }	
                }
            }    
        )},
        (error, res, body)=>{ 
            if (error){ console.log(error); return; }
            console.log(body);            
        }
    );
}
/*
function faturaIugu(data){    
    request.post({ headers: {
            'Accept' : 'application/json', 
            'Content-Type': 'application/json',
            'Authorization': 'Basic NDk1QTlEMTlDNTU3N0QzNUZFQjYwNDVBNEU0RTRCRDhFNTFDNDVEOThGRTk5QUM1MzQ1MTg2RjFCMTQ0RkVGNzo='
        }, 
        url: 'https://api.iugu.com/v1/charge', 
        body: JSON.stringify({	
            notification_url: 'http://localhost:4000/iugu-credit-card-status',
            restrict_payment_method: false,
            token:'1a24315e-0733-4d7a-b11d-76a3940c36f2',
            customer_id: '7FA67FBCA1504C31B429A3A6B910C1E5',
            email: 'matheusm.aquino1@gmail.com',
            items: [{
                description: 'Camiseta A', 
                quantity: '1', 
                price_cents: '2000'
            }],
            payer: { 
            cpf_cnpj: '46171619884', 
            name: 'Matheus Aquino', 
            phone_prefix: '11', 
            phone: '979544109', 
            email: 'matheusm.aquino1@gamil.com', 
            address:{
                    zip_code: '09571-020', 
                    street: 'Rua Coronel Camisão',
                    number: '91',
                    district: 'Oswaldo Cruz',
                    city: 'São Caetano do Sul',
                    state: 'São Paulo',
                    country: 'Brasil',
                    complement: ''
                }	
            },
            order_id: '144272'
        })
    }, 
    (error, res, body)=>{ 
        if (error){ console.log(error); return; }
        console.log(body);            
    });
}
faturaIugu();
var json = {
    user_id: '12345',
    user_email: 'matheus.marques@gmail.com',
    user_ip: '127.0.0.1',
    product_id: '168',
    variation_id: '169',
    firstName: 'Matheus', 
    lastName: 'Marques',
    birthdate: '29/08/1997',
    cpf: '461.716.198-84',
    cnpj: '',
    phone: '(11)97954-4109',
    address_1: 'Rua Coronel Camisão', 
    number: '91',
    address_2: 'Casa',
    neiborhood: 'Oswaldo Cruz',
    city: 'São Caetano do Sul',
    state: 'São Paulo',
    cep: '09571-020',
    payment_totals: {
        total: '100',
        subtotal: '90',
        shipping_total: '10',
        cupom: { code: '', discount: '0' }
    },
    payment_data: {
        id: 1,
        payment_method_id: 'iugu-credit-card',
        payment_method_title: 'Cartão de Crédito',
        iugu_token: '',
        iugu_customer_id: '',
        status: ''
    }
};
*/
