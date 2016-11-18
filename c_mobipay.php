<?php
/**
 * PHP класс для работы с сервисом Mobipay.ua
 * Для работы с классом нужно расширение cURL для отправки запросов
 *
 * 18.11.2016
 *
 * @author MobiPay.ua
 * @mail support@mobipay.ua
 * @link https://api.mobipay.ua/apidocs/api.ru.zip
 * @link https://api.mobipay.ua/apidocs/api.en.zip
 *
 */
class c_mobipay {

	/**
	 * @var string идентификатор сайта
	 */
	protected static $sid       = "c6141e460404f2dfca833084631912a5";

	/**
	 * @var string пароль сайта
	 */
	protected static $secretkey = "A$@BPSZ@(K~W1Q|YM";

	/**
	 * @var boolean debugger
	 */
	public static $debugger = false;

	/**
	 * @var string url куда будут отправляться все запросы
	 */
    protected static $server    = "https://api.mobipay.ua/api/json/json.php";

	/**
	 * @var int версия API mobipay.ua
	 */
	protected static $version   = 1004;

	/**
	 * @var string язык интерфейса
	 */
	private static $lang        = "ru";

	/**
	 * @var array данные для отправки на сервер mobipay.ua
	 */
	private static $request     = array();

	/**
	 * @var array внутрение ошибки
	 */
	private static $error       = array(
		1 => 'No request for data',
		2 => 'Hash does not match',
		3 => 'An unknown error',
		4 => 'No Information about ordering',
		5 => 'Not all required fields',
		6 => 'Choose %s'
	);


	/**
	 * установка языка интерфейса
	 *
	 * @param string $lang язык (en,ru,uk,fr)
	*/
	public static function setLang($lang)
	{
		self::$lang = $lang;
	}

	/**
	 * получение QR для регистрации/авторизации
	 *
	 * @param string $return_url куда вернуть ответ (otphash) после авторизации пользователя
	 *
	 * @return object
	 * ->otp = One Time Password
	 * ->qr  = url картинки для отображения QR кода
	 * ->otpview = OTP для отображения пользователю вида 123-456
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_getotp  описание функции
	 *
	*/
	public static function getOTP($return_url=NULL)
	{
		self::$request['cmd']  = "getOTP";
		self::$request['data'] = array(
			'return_url' => !empty($return_url) ? urlencode($return_url) : NULL
		);
		return self::send_cmd();
	}

	/**
	 * получаем otphash пользователя, который авторизовался по OTP
	 *
	 * @param string $otp OTP пароль
	 *
	 * @return object
	 * ->success = true в случаи успеха
	 * ->otphash = OTP HASH
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_userauth  описание функции
	 *
	*/
	public static function getOTPhash($otp)
	{
		if(!empty($otp))
		{
			self::$request['cmd']  = "getOTPhash";
			self::$request['data'] = array(
					'otp' => $otp
			);
			return self::send_cmd();
		}
		return false;
	}

    /**
     * Создаем QR данных
     *
     * @param array|object $data*
     * @param string $url_request
     * @param string $queueId
     *
     * @return object QR
     * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
     *
     * @link https://mobipay.ua/apidocs/#php_getqrdata  описание функции
     *
     */
    public static function getQRData($data,$queueId=NULL,$url_request=NULL)
	{
		self::$request['cmd']  = "getQRData";
		self::$request['data'] = array(
				'request'     => is_array($data) || is_object($data) ? $data : NULL,
                'url_request' => $url_request,
                'queueId'     => $queueId
		);
		return self::send_cmd();
	}

	/**
	 * получаем уникальный идентификатор пользователя, который авторизовался в этой сессии
	 *
	 * @param string $otphash OTP хеш
	 *
	 * @return string номер wp пользователя в случаи успеха
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_userauth  описание функции
	 *
	*/
	public static function authUser($otphash=NULL)
	{
		$otphash = !empty($otphash) ? $otphash : ((isset($_REQUEST['otphash']) && strlen($_REQUEST['otphash']) == 64) ? $_REQUEST['otphash'] : NULL);
		if(!empty($otphash))
		{
			self::$request['cmd']  = "authUser";
			self::$request['data'] = array(
					'otphash' => $otphash
			);
			return self::send_cmd();
		}
		return false;
	}

	/**
	 * получаем аватару пользователя
	 *
	 * @param string $myqr идентификатор пользователя
	 * @param array[width,height] $size размер аватары
	 *
	 * @return string url аватары пользователя в случаи успеха
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_userphoto  описание функции
	 *
	*/
	public static function userPhoto($myqr,$size)
	{
		self::$request['cmd']  = "userPhoto";
		self::$request['data'] = array(
				'myqr' => $myqr,
				'size' => is_array($size) && count($size) == 2 ? $size : NULL
		);
		return self::send_cmd();
	}

	/**
	 * получаем информацию о пользователе
	 *
	 * @param string $myqr идентификатор пользователя
	 *
	 * @return object данных пользователя
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_userinfo  описание функции
	 *
	*/
	public static function userInfo($myqr)
	{
		self::$request['cmd']  = "userInfo";
		self::$request['data'] = array(
				'myqr'    => $myqr
		);
		return self::send_cmd();
	}

	/**
	 * выставляем счет для оплаты и получаем QR
	 *
	 * @param array $invoice данные выставленного счета и список продаваемых товаров/услуг
	 *
	 * @return object
	 * ->token	  = токен платежа в системе WP
	 * ->qr       = url QR кода
	 * ->link_app = ссылка для открытия счета в приложении WP, если пользователь зашел на сайт с мобильного
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_getinvoice  описание функции
	 *
	*/
	public static function getInvoice($invoice)
	{
		if(!is_array($invoice))
		{
			return (object) array(
				'error' => 4,
				'desc'  => self::$error[4]
			);
		}
		elseif(!isset($invoice['order_id']))
		{
			return (object) array(
				'error' => 6,
				'desc'  => sprintf(self::$error[6],'order_id')
			);
		}
		elseif(!isset($invoice['desc']))
		{
			return (object) array(
				'error' => 6,
				'desc'  => sprintf(self::$error[6],'desc')
			);
		}

		if(isset($invoice['products']) && is_array($invoice['products']))
		{
			foreach($invoice['products'] as $k => $v)
			{
				$invoice['products'][$k]['name']  = self::is_utf8($v['name'])  ? $v['name']  : self::change_encoding($v['name'],'UTF-8');
				$invoice['products'][$k]['image'] = self::is_utf8($v['image']) ? $v['image'] : self::change_encoding($v['image'],'UTF-8');
			}
		}

		if(isset($invoice['fields_app']) && is_array($invoice['fields_app']))
		{
			foreach($invoice['fields_app'] as $k => $v)
			{
				foreach($v['data'] as $k2 => $v2)
				{
					$invoice['fields_app'][$k]['data'][$k2]['text']  = self::is_utf8($v2['text'])  ? $v2['text']  : self::change_encoding($v2['text'],'UTF-8');
				}
			}
		}

		self::$request['cmd']  = "getInvoice";
		self::$request['data'] = array(
				'order_id'   	   => $invoice['order_id'],
				'desc'       	   => self::is_utf8($invoice['desc']) ? $invoice['desc'] : self::change_encoding($invoice['desc'],'UTF-8'),
				'amount'     	   => isset($invoice['amount']) && floatval($invoice['amount']) > 0 ? $invoice['amount'] : 0,
				'currency'   	   => isset($invoice['currency']) ? $invoice['currency'] : 'UAH',
				'test'       	   => isset($invoice['test']) ? $invoice['test'] : 0,
				'transtype'    	   => isset($invoice['transtype']) ? $invoice['transtype'] : 0,
				'products'   	   => isset($invoice['products']) && is_array($invoice['products']) ? $invoice['products'] : NULL,
				'user_to'    	   => isset($invoice['user_to']) ? $invoice['user_to'] : NULL,
				'date_life'  	   => isset($invoice['date_life']) ? $invoice['date_life'] : NULL,
				'date_start_push'  => isset($invoice['date_start_push']) ? $invoice['date_start_push'] : NULL,
				'count_push'  	   => isset($invoice['count_push']) ? $invoice['count_push'] : NULL,
				'result_url' 	   => isset($invoice['result_url']) ? urlencode($invoice['result_url']) : NULL,
				'success_url' 	   => isset($invoice['success_url']) ? urlencode($invoice['success_url']) : NULL,
				'fail_url' 	 	   => isset($invoice['fail_url']) ? urlencode($invoice['fail_url']) : NULL,
				'fields_app'	   => isset($invoice['fields_app']) && is_array($invoice['fields_app']) ? $invoice['fields_app'] : NULL,
				'fields_other'	   => isset($invoice['fields_other']) && is_array($invoice['fields_other']) ? $invoice['fields_other'] : NULL,
				'long_term'    	   => isset($invoice['long_term']) ? $invoice['long_term'] : 0
		);
		return self::send_cmd();
	}


	/**
	 * создать голосование
	 *
	 * @param array $vote данные голосования
	 *
	 * @return object
	 * ->qr       = url QR кода
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_vote  описание функции
	 *
	*/
	public static function getVote($vote)
	{
		if(!is_array($vote))
		{
			return (object) array(
				'error' => 4,
				'desc'  => self::$error[4]
			);
		}
		elseif(!isset($vote['title']))
		{
			return (object) array(
				'error' => 6,
				'desc'  => sprintf(self::$error[6],'title')
			);
		}
		elseif(!isset($vote['form']))
		{
			return (object) array(
				'error' => 6,
				'desc'  => sprintf(self::$error[6],'form')
			);
		}

		self::$request['cmd']  = "getVote";
		self::$request['data'] = array(
				'title'        => self::is_utf8($vote['title']) ? $vote['title'] : self::change_encoding($vote['title'],'UTF-8'),
				'callback_url' => isset($vote['callback_url']) ? urlencode($vote['callback_url']) : NULL,
				'info_url'     => isset($vote['info_url']) ? urlencode($vote['info_url']) : NULL,
				'anonymous'	   => isset($vote['anonymous']) ? $vote['anonymous'] : 0,
				'form'		   => $vote['form']

		);
		return self::send_cmd();
	}

	/**
	 * получаем информацию о результате платежа по его токену
	 *
	 * @param string $tokenpay токен платежа
	 * @param int $mark маркер (только для долгосрочных QR кодов)
	 *
	 * @return object статус платежа
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_status_payment  описание функции
	 *
	*/
	public static function statusPayment($_)
	{
		self::$request['cmd']  = "statusPayment";
		self::$request['data'] = array(
				'tokenpay' => $_['keypay'],
				'mark'	   => $_['mark'],
                'order_id' => $_['order_id']
		);
		return self::send_cmd();
	}

	/**
	 * обновить маркер оплаченного счета (только для долгосрочных QR кодов)
	 *
	 * @param string $keypay ключ платежа
	 * @param int $mark маркер
	 *
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_update_mark  описание функции
	 *
	*/
	public static function updateMark($keypay,$mark=NULL)
	{
		self::$request['cmd']  = "updateMark";
		self::$request['data'] = array(
				'keypay' => $keypay,
				'mark'	 => $mark
		);
		return self::send_cmd();
	}

	/**
	 * снять заблокированную сумму
	 *
	 * @param string $keypay ключ платежа
     * @param float $amount сумма
	 *
	 * @return object статус платежа
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_settle  описание функции
	 *
	*/
	public static function settlePayment($keypay,$amount)
	{
		self::$request['cmd']  = "settlePayment";
		self::$request['data'] = array(
				'keypay' => $keypay,
                'amount' => $amount
		);
		return self::send_cmd();
	}

	/**
	 * Возврат/Отмена платежа
	 *
	 * @param string $keypay ключ платежа
	 *
	 * @return object статус платежа
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_refund  описание функции
	 *
	*/
	public static function refundPayment($keypay,$amount)
	{
		self::$request['cmd']  = "refundPayment";
		self::$request['data'] = array(
				'keypay' => $keypay,
                'amount' => $amount
		);
		return self::send_cmd();
	}

    /**
     * вывод денег на карты VISA & MasterCard
     *
     * @param string $myqr идентификатор пользователя
     * @param float $amount сумма
     *
     * @return object
     * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
     *
     * @link https://mobipay.ua/apidocs/#php_account2card  описание функции
     *
     */
	public static function account2card($myqr,$amount)
	{
		self::$request['cmd']  = "account2card";
		self::$request['data'] = array(
				'myqr' => $myqr,
				'amount' => $amount
		);
		return self::send_cmd();
	}

    /**
     * Пополнение мобильного телефона
     * @param mixed $mobile номер который пополнить
     * @param mixed $amount сумма пополнения
     *
     * @return object
     * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
     *
     * @link https://mobipay.ua/apidocs/#php_p2phone  описание функции
     */
    public static function p2Phone($mobile,$amount)
	{
		self::$request['cmd']  = "p2Phone";
		self::$request['data'] = array(
				'mobile' => $mobile,
				'amount' => $amount
		);
		return self::send_cmd();
	}


    /**
     * Инициализация модального окна JavaScript
     */
    public static function initModalWindow()
    {
        return '
            <script type="text/javascript">
                window.MobiPayAsyncInit = function () {
                    MobiPay.init({
                        lang: "'.self::$lang.'",
                        sid:  "'.self::$sid.'"
      		        });
                };
            </script>
            <script type="text/javascript" src="https://api.mobipay.ua/windows/1002/script.js"></script>
        ';
    }

    /**
     * Создать сигнатуру для модального окна JavaScript
     * @param string (json_encode) $invoice
     * @return string (64)
     */
    public static function signModalWindow($invoice)
    {
        return hash('sha256',self::$sid.':::'.$invoice.':::'.self::$secretkey);
    }

	/**
	 * создание токена для подписи запросов
	 *
	 * @link https://mobipay.ua/apidocs/#php_token  описание функции
	 *
	*/
	private static function token()
	{
		self::$request['version'] = self::$version;
		self::$request['sid']     = self::$sid;
		self::$request['mktime']  = self::getmicrotime();
		self::$request['lang']	   = self::$lang;
		self::$request['hash']    = hash_hmac("sha256", json_encode(self::$request), self::$secretkey);
	}

	/**
	 * получение и обработка статуса оплаты
	 *
	 * ВНИМАНИЕ! Используется только для сайтов (серверов) когда при выставлении счета указывается result_url
	 *
	 * @return object
	 *
	 * @link https://mobipay.ua/apidocs/#php_resultpay  описание функции
	 *
	*/
	public static function result_pay()
	{
		$result = array(
			'error'    => 5,
			'desc'     => self::$error[5],
			'status'   => @$_POST['status_pay'],
			'order_id' => @$_POST['order_id']
		);

		if(isset($_POST['keypay']) 	   && $_POST['keypay'] != ''     &&
		   isset($_POST['status_pay']) && $_POST['status_pay'] != '' &&
		   isset($_POST['amount'])     && $_POST['amount'] != ''     &&
		   isset($_POST['currency'])   && $_POST['currency'] != ''   &&
		   isset($_POST['order_id'])   && $_POST['order_id'] != ''   &&
		   isset($_POST['mktime'])     && $_POST['mktime'] != ''     &&
		   isset($_POST['hash'])       && $_POST['hash'] != ''       &&
		   isset($_POST['test'])       && $_POST['test'] != '')
		{
			$hash = hash('sha256', md5($_POST['keypay'].':::'.$_POST['status_pay'].':::'.self::$sid.':::'.self::$secretkey.':::'.$_POST['order_id'].':::'.$_POST['amount'].':::'.$_POST['currency'].':::'.$_POST['mktime'].':::'.$_POST['test']));
			if($hash === $_POST['hash']){
				$result = array(
					'success'  => true,
					'status'   => intval($_POST['status_pay']),
					'order_id' => $_POST['order_id']
				);
			} else {
				$result = array(
					'error'    => 2,
					'desc'     => self::$error[2],
					'status'   => intval($_POST['status_pay']),
					'order_id' => $_POST['order_id']
				);
			}
		}

		return (object) $result;
	}


	/**
	 * получение и обработка голосов (голосование)
	 *
	 * ВНИМАНИЕ! Используется только для сайтов (серверов) когда при создании голосования указывается callback_url
	 *
	 * @return object
	 *
	 * @link https://mobipay.ua/apidocs/#php_resultvote  описание функции
	 *
	*/
	public static function result_vote()
	{
		$result = array(
			'error'    => 5,
			'desc'     => self::$error[5]
		);

		if(isset($_POST['answer']) && $_POST['answer'] != '' &&
		   isset($_POST['my_qr'])  && $_POST['my_qr'] != ''  &&
		   isset($_POST['mktime']) && $_POST['mktime'] != '' &&
		   isset($_POST['hash'])   && $_POST['hash'] != ''    )
		{
			$hash = hash('sha256', md5($_POST['token'].':::'.$_POST['answer'].':::'.$_POST['my_qr'].':::'.$_POST['mktime']));
			if($hash === $_POST['hash']){
				$result = array(
					'success' => true,
					'data'    => $_POST
				);
			} else {
				$result = array(
					'error'    => 2,
					'desc'     => self::$error[2]
				);
			}
		}

		return (object) $result;
	}

	/**
	 * Если данные не в UTF-8, то перекодируем
	*/
	private static function change_encoding($text, $encoding)
	{
		return mb_convert_encoding($text, $encoding, mb_detect_encoding($text));
	}

	/**
	 * функция обнаружения того, что строка $str закодирвана UTF-8 (бинарно)
	 *
	 * @param string $str строка символов
	 *
	 * @return boolean true если UTF-8 или false если ASCII
	 *
	*/
    private static function is_utf8($str)
	{
		for($i = 0; $i < strlen($str); $i++)
		{
			if(ord($str[$i]) < 0x80) $n=0; # 0bbbbbbb
			elseif ((ord($str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif ((ord($str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif ((ord($str[$i]) & 0xF0) == 0xF0) $n=3; # 1111bbbb
			else return false; # Does not match any model
			for($j = 0; $j < $n; $j++)
			{ # n octets that match 10bbbbbb follow ?
				if((++$i == strlen($str)) || ((ord($str[$i]) & 0xC0) != 0x80)) return false;
			}
		}
		return true;
    }

	/**
	 * Возвращает microtime
	 *
	 * @return string
	 *
	*/
	public static function getmicrotime()
	{
		list($usec, $sec) = explode(" ", substr(microtime(), 2));
		return substr($sec.$usec, 0, 15);
	}


	/**
	 * отправка запроса и получение ответа с сервера WEB Passport
	 *
	 * @return object ответ с сервера
	 * @return object при ошибке ->error = номер ошибки; ->desc =  описание ошибки
	 *
	 * @link https://mobipay.ua/apidocs/#php_sendcmd  описание функции
	 *
	*/
	private static function send_cmd()
	{
		if (!function_exists('curl_version')) {
            die('To work correctly, you need to install cURL library - https://php.net/curl');
        }

		if(!is_array(self::$request) || count(self::$request) == 0)
		{
			return (object) array(
				'error' => 1,
				'desc'  => self::$error[1]
			);
		}

		self::token(); // подпись запроса hash

		if(self::$debugger)
		{
			echo '<pre>'; print_r(self::$request); echo '</pre>';
            echo '<pre>'; print_r(self::$request['data']); echo '</pre>';
			echo '<pre>'; echo json_encode(self::$request); echo '</pre>';
		}

		$ch = curl_init();
    		  curl_setopt($ch, CURLOPT_URL, self::$server);
			  curl_setopt($ch, CURLOPT_HEADER, 0);
		      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			  curl_setopt($ch, CURLOPT_POST, 1);
		      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(self::$request));
	    	  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    	$result = curl_exec($ch);

		self::$request = array(); // очищаем память

		if(curl_errno($ch) != 0)
		{
			$result = array(
				'error' => curl_errno($ch),
				'desc'  => curl_error($ch)
			);
			curl_close($ch);
			return (object) $result;
		}

		curl_close($ch);

		if(self::$debugger)
		{
			echo '<pre>'; var_dump($result); echo '</pre>';
		}

		$result = json_decode($result);

		if(self::$debugger)
		{
			echo '<pre>'; print_r($result); echo '</pre>';
		}

		if(is_object($result))
		{
			$hash1 = $result->hash; unset($result->hash);
			$hash2 = hash_hmac('sha256', json_encode($result), self::$secretkey);
			if($hash1 === $hash2)
			{
				return $result->data;
			}
			 else
			{
				return (object) array(
					'error' => 2,
					'desc'  => self::$error[2]
				);
			}
		}


		return (object) array(
			'error' => 3,
			'desc'  => self::$error[3]
		);
	}
}
?>