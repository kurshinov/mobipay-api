<?php
error_reporting(E_ALL);	ini_set('display_errors', 1);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: text/html; charset=utf-8');

function __autoload($class)
{
    require_once $class.".php";
}

// установка языка интерфейса
c_mobipay::setLang('ru');

// Включить отладку
c_mobipay::$debugger = true;

echo '<pre>';
// ** получение QR для регистрации/авторизации
//print_r(c_mobipay::getOTP("http://mysite.com/result"));

// ** получаем otphash пользователя, который авторизовался по OTP
//print_r(c_mobipay::getOTPhash("316094"));

// ** получаем номер WP пользователя, который авторизовался в этой сессии
//print_r(c_mobipay::authUser('0710b7096e924ccd09a96afcfcef988d'));

// ** получаем аватару пользователя
//print_r(c_mobipay::userPhoto("006727488",array(300,300)));

// ** получаем информацию о пользователе
//print_r(c_mobipay::userInfo('006727488'));

// ** выставляем счет для оплаты и получаем QR
// * список продуктов (необязательно)
/*$products = [
    [
	    'id'       => '1',
	    'name'     => 'Trainz Classics Volume 3 DVD',
	    'amount'   => '35',
	    'currency' => 'UAH',
	    'count'    => 1,
	    'image'    => 'http://camelot.multilocal.ru/pic/games/TraCla.jpg'
    ],
    [
	    'id'       => '2',
	    'name'     => 'Velvet Assassin',
	    'amount'   => '199',
	    'currency' => 'UAH',
	    'count'    => 2,
	    'image'    => 'https://upload.wikimedia.org/wikipedia/ru/thumb/b/bd/Velvetassassin_box_large.jpg/250px-Velvetassassin_box_large.jpg'
    ],
    [
	    'id'       => '3',
	    'name'     => 'Сборник игр: Code Of Honor 3: Desperate Measu',
	    'amount'   => '56',
	    'currency' => 'UAH',
	    'count'    => 3,
	    'image'    => 'http://torrent-zona.com/_ld/68/16807033.jpg'
    ]
];
// * массив дополнительных полей для выбора покупателя
$ar = [
	[
		'type' => 'select',
		'id'   => 'gift',
		'data' => [
			[
				'value' => '1',
				'text'  => 'Подарок так себе'
			],
			[
				'value' => '2',
				'text'  => 'Подарок дорогой'
			]
		]
	]
];
// * создаем счет
$invoice = array(
	'test' => 1,
	'user_to' => '380935960444',
	'date_life' => '2016-09-10',
	'date_start_push' => '2016-07-08',
	'count_push' => 1,
	'order_id'  => time(),
	'amount'	=> '601',
	'currency'  => 'UAH',
	'desc'      => 'Покупка товаров согласно счета #6598741',
	'transtype' => 1,
	'fields_app'=> $ar,
	'products'  => $products,
	'long_term' => 0,
	'fields_other'=> [
		[
			'test' => 1
		]
	]
);
print_r(c_mobipay::getInvoice($invoice));*/

// ** получаем информацию о результате платежа по его токену
/*print_r(c_mobipay::statusPayment([
    'keypay' => '459711637402',
    'mark' => null,
    'order_id' => null
    ]));*/

// ** обновить маркер оплаченного счета (только для долгосрочных QR кодов)
//print_r(c_mobipay::updateMark("647436497146",2));

// ** снять заблокированную сумму
//print_r(c_mobipay::settlePayment("540572937768",1));

// ** Возврат/Отмена платежа
//print_r(c_mobipay::refundPayment("384660489750",1));

// ** создание голосования
/*$vote = [
	'title' => 'Вопрос',
	'callback_url' => 'http://site.com',
	'info_url' => 'http://site.com.ua',
	'anonymous' => 1,
	'form'	=> [
		[
			'type' => 'button',
			'id'   => 'btn_1',
			'data' => [
				[
					'value' => urlencode(base64_encode('Ответ 1'))
				]
			]
		],
		[
			'type' => 'button',
			'id'   => 'btn_2',
			'data' => [
				[
					'value' => urlencode(base64_encode('Ответ 2'))
				]
			]
		]
	]
];
print_r(c_mobipay::getVote($vote));*/


// ** вывести деньги на карту
//print_r(c_mobipay::account2card('006727488',1));

// ** Пополнить мобильный телефон
//print_r(c_mobipay::p2Phone('380675769813',200));
?>