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
const Authorization_Iugu = 'Basic NDk1QTlEMTlDNTU3N0QzNUZFQjYwNDVBNEU0RTRCRDhFNTFDNDVEOThGRTk5QUM1MzQ1MTg2RjFCMTQ0RkVGNzo=';
const mysql      = require('mysql');

var connection = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'wordpress'
});
var CPF = require('cpf_cnpj').CPF;
var CNPJ = require('cpf_cnpj').CNPJ;



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
        products: [{ on_sale: null, instock: null, price: null }],
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
        total: data.shipping.totals,//'1350'
    }
    checkout_data.box = {
        altura: data.box.altura,//'6',
        largura: data.box.largura,//'40',
        comprimento: data.box.comprimento,//'50',
        peso: data.box.peso,// '0,4'
    }
    let error_text = [
        'Erro: O usuário não foi identificado, tente sair e entrar novamente em sua conta.',
        'Nome',
        'Sobrenome',
        'Cidade',
        'Rua',
        'Bairro',
        'Ocorreu um erro durante a validação dos produtos, verifique seu carrinho e tente novamente.',
        'E-mail',
        'WhatsApp',
        'CPF',
        'Frete',
        'Área de cobertura'
    ]
    if (!checkout_data.user){ errorLog.push('A'); }
    if (!checkout_data.user.id){ errorLog.push('Erro: O usuário não foi identificado, tente sair e entrar novamente em sua conta.'); }
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

    for(let i = 0; i < validation_data.products.length; i++){
        let product =  await get_product(data.variation.id, 1);
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

    let order = {};
    if (valid){
        order = await create_order(checkout_data); 
        checkout_data.order = order;
        console.log('Checkout Data:', checkout_data);
        //console.log(order);
        if (order.error){ 
            errorLog.push(order.errorLog); 
        }else{
            console.log(data.payment.index);
            switch(data.payment.index){
                case 1:
                    let invoice =  await paghiper_boleto(checkout_data);
                    console.log('Invoice:', invoice);
                    break;
                case 2:
                    if (!data.payment.token){ errorLog('Ocorreu um erro ao processar o pagamento, tente novamente.'); }
                    if (!data.user.iugu_id){ errorLog('Ocorreu um erro ao processar o pagamento, tente novamente.'); }
                    
                    let iugu_customer = await validate_iugu_customer(data.user.iugu_id);
                    console.log('Checkout Data:', checkout_data);
                    if (!iugu_customer.error){ 
                        let invoice =  await iugu_cobrança_direta(checkout_data);
                        console.log('Invoice:', invoice);           
                        //Query para catalogar a venda.
                        //Encerra Conexão MySQL
                        //Envia objeto com status da transção ou mensagens de erro
                    }
                    break;
                default:
                    errorLog('Ocorreu um erro desconhecido.');
                    break;
            }
        }
       
    }
    console.log(valid);
    //validation_data.subtotal = variation.price 
    /* 
    console.log('Final Result:', errorLog);
    console.log('Valid Data:', validation_data);    
    console.log('Order:', order);*/ 
    //connection.end();
    res.status(200).json({result: valid, errorLog: errorLog});
});
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
        //console.log(result);
        res.status(200).json({result: result, errorLog: errorLog});
    });   
    
});
function paghiper_pix(data){
    return new Promise(function (resolve, reject) {
        let products = [];
        let user = data.user;
        let address = data;
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
            url: 'https://pix.paghiper.com/invoice/create/',      
            headers: {
                'Accept' : 'application/json', 
                'Content-Type': 'application/json'       
            },
            body: JSON.stringify({
                apiKey: apiKey_PagHiper,
                order_id: data.order.id,
                payer_email: user.email,
                payer_name: user.name,
                payer_cpf_cnpj: user.cpf.replace(/\D/g, ''),
                payer_phone: user.phone.replace(/\D/g, ''), 
                payer_street: address.address_1,
                payer_number: address.number, 
                payer_complement: address.address_2,
                payer_district: address.neiborhood,
                payer_city: address.city,
                payer_state: address.uf,
                discount_cents : data.discount.total,
                shipping_price_cents : data.payment.total,
                shipping_methods : data.shipping.method_title,
                fixed_description : true,
                days_due_date : 5,
                discount_cents: data.discount.total,
                type_bank_slip: 'boletoA4',
                items: products
            })},
            (error, res, body)=>{ 
                let invoice = { error: false, errorLog: '' };
                if (error){
                    invoice.error = true;
                    invoice.errorLog = '[Iugu] Ocorreu um erro ao processar o pagamento.';
                    reject( error );
                    return invoice;
                }
                invoice = JSON.parse(body);
                resolve(invoice);                      
            }
        );
    });
}
function paghiper_boleto(data){
    return new Promise(function (resolve, reject) {
        let products = [];
        let user = data.user;
        let address = data;
        let notification_url = domain_url + '/payment_notification/paghiper-boleto';
        if (data.products){
            data.products.map((product, index)=>{
                let item = {
                    item_id: product.product_id,
                    description: product.name,
                    quantity: parseInt(product.quantity),
                    price_cents: parseInt(product.price*100)
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
                order_id: data.order.id,
                payer_email: user.email,
                payer_name: user.name, 
                payer_cpf_cnpj: user.cpf.replace(/\D/g, ''),
                payer_phone: user.phone.replace(/\D/g, ''), 
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
                let invoice = {error: false, errorLog: ''}
                if (error){ 
                    console.log(error); 
                    invoice.error = true; 
                    invoice.errorLog = 'Ocorreu um erro ao processar o pagamento, atualize a página e tente novamente.'; 
                    reject(invoice);
                    return invoice; 
                }
                if (!body){
                    console.log('error: body vazio'); 
                    invoice.error = true; 
                    invoice.errorLog = 'Ocorreu um erro ao processar o pagamento, atualize a página e tente novamente.'; 
                    reject(invoice);
                    return invoice;                    
                }
                invoice = JSON.parse(body);
                resolve(invoice);
                return invoice;         
            }           
        )
    });
}
function validate_iugu_customer(iugu_id){
    return new Promise(function (resolve, reject) {
        request.get({
            url: 'https://api.iugu.com/v1/customers/'+iugu_id,
            headers: {
                'Accept' : 'application/json', 
                'Content-Type': 'application/json',
                'Authorization': Authorization_Iugu      
            }
            },(error, res, body)=>{ 
                let customer = { error: false, errorLog: '' };
                if (error){
                    customer.error = true;
                    customer.errorLog = '[Iugu] Ocorreu um erro durante a autenticação do usuário, atualize a página e tente novamente.';
                    reject( error );
                    return customer;
                }
                customer = JSON.parse(body);
                if (!customer){
                    customer.error = true;
                    customer.errorLog = '[Iugu] Ocorreu um erro durante a autenticação do usuário, atualize a página e tente novamente.';
                    reject( error );
                    return customer;
                }
                if (customer.error){
                    customer.error = true;
                    customer.errorLog = '[Iugu] Ocorreu um erro durante a autenticação do usuário, atualize a página e tente novamente.';
                    reject( error );
                    return customer;
                }
                resolve(customer);
                return customer;
            }
        );
    });    
}
function iugu_cobrança_direta(data){
    return new Promise(function (resolve, reject) {
        let user = data.user;
        let address = data.user;
        let payment = data.payment;
        let products = [];
        let notification_url = domain_url + '/payment_notification/iugu-credit-card';

        if (data.products){
            data.products.map((product, index)=>{
                let item = {
                    description: product.name,
                    quantity: parseInt(product.quantity),
                    price_cents: parseInt(product.price * 100)
                }
                products.push(item);
            });
        }

        request.post({
            url: 'https://api.iugu.com/v1/charge',
            headers: {
                'Accept' : 'application/json', 
                'Content-Type': 'application/json',
                'Authorization': Authorization_Iugu      
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
            )},(error, res, body)=>{ 
                let invoice = { error: false, errorLog: '' };
                if (error){
                    invoice.error = true;
                    invoice.errorLog = '[Iugu] Ocorreu um erro ao processar o pagamento.';
                    reject( error );
                    return invoice;
                }
                invoice = JSON.parse(body);
                console.log(invoice)
            }    
        );
    });
}
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
function create_order(data){
    return new Promise(function (resolve, reject) {
        let products = [];
        let user = data.user;
        let shipping = data.shipping;
        let payment = data.payment;
        if (data.products){
            data.products.map((product, index)=>{
                let item = {
                    product_id: parseInt(product.product_id),
                    variation_id: parseInt(product.variation_id),
                    quantity: parseInt(product.quantity),
                    subtotal: product.price.toFixed(2)
                }
                products.push(item);       
            });
        }
        request.post({ 
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
                    method_id: shipping.method_id.replace('_', '-'),
                    method_title: shipping.method_title,
                    total: shipping.total.toString()
                }],
                metadata:[
                    { key: '_billing_number', value: user.number },
                    { key: '_billing_cpf', value: user.cpf },
                    { key: '_billing_birthdate', value: user.birthdate },
                    { key: '_billing_neighborhood', value: user.neiborhood },
                    { key: '_shipping_number', value: user.number },
                    { key: '_shipping_neighborhood', value: user.neiborhood }
                ]                    
            })   
            },
            (error, res, body)=>{ 
                if (error){ console.log(error); reject(error); }
                //console.log(body);  
                let order = JSON.parse(body);
                if (!order){ resolve({error: true, errorLog: 'Ocorreu um erro inesperado.'}); return; }
                if (order.data){ if (order.data >= 400){ resolve({error: true, errorLog: 'Ocorreu um erro inesperado.'}); return; } }
                resolve(order);            
            }
        );
    });
}

function fatura_iugu_credit_card(data){
    let user = data.user;
    let address = data.user;
    let payment = data.payment;
    let products = [];
    let notification_url = domain_url + '/payment_notification/iugu-credit-card';

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
/*function create_order(data){
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
    request.post({ 
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
    );
}
let order_data = {
    order_id: '198',
    updates: { status: random_status() }
};*/
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
