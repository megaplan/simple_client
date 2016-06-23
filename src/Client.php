<?php

namespace Megaplan\SimpleClient;

use DateTime;

/**
 * Библиотека для формирования запроса к API Мегаплана
 */
class Client
{
    /**
     * @var string Идентификатор пользователя
     */
    protected $accessId;

    /**
     * @var string Секретный ключ
     */
    protected $secretKey;

    /**
     * @var string Название хоста
     */
    protected $host;

    /**
     * @var bool Индикатор использования https
     */
    protected $https = true;

    /**
     * @var string Результат последнего запроса
     */
    protected $result;

    /**
     * @var array Информация о последнем запросе
     */
    protected $info;

    /**
     * @var integer Таймаут соединения в секундах
     */
    protected $timeout;

    /**
     * @var string Последняя ошибка CURL-запроса
     */
    protected $error;

    /**
     * @var string Путь к файлу, который будет записан всё содержимое ответа
     */
    protected $outputFile;

    /**
     * Создает объект
     *
     * @param string  $host    Имя хоста мегаплана
     * @param integer $timeout Таймаут подключения
     */
    public function __construct($host, $timeout = 10)
    {
        $this->host = $host;
        $this->timeout = $timeout;
    }

    public function auth($login, $password)
    {
        $response = $this->post('/BumsCommonApiV01/User/authorize.api', [
            'Login' => $login,
            'Password' => md5($password)
        ]);

        if (false === $response) {
            throw new \InvalidArgumentException('Error on auth: '.$this->getError());
        }

        $this->accessId = $response->data->AccessId;
        $this->secretKey = $response->data->SecretKey;

        return $this;
    }

    /**
     * @param string $accessId
     *
     * @return Client
     */
    public function setAccessId($accessId)
    {
        $this->accessId = $accessId;

        return $this;
    }

    /**
     * @param string $secretKey
     *
     * @return Client
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * Устанавливает нужно ли использовать https-соединение
     *
     * @param bool $https
     *
     * @return $this
     */
    public function useHttps($https = true)
    {
        $this->https = $https;

        return $this;
    }

    /**
     * Устанавливает путь к файлу, в который будет записан всё содержимое ответа
     *
     * @param string $filePath Путь к файлу
     */
    public function setOutputFile($filePath)
    {
        $this->outputFile = $filePath;
    }

    /**
     * Отправляет GET-запрос
     *
     * @param string $uri
     * @param array  $params GET-параметры
     *
     * @return string|object|array Ответ на запрос
     * @throws \Exception
     */
    public function get($uri, array $params = null)
    {
        $date = new DateTime();

        $uri = $this->processUri($uri, $params);

        $request = Request::create(
            'GET',
            $this->host,
            $uri,
            array('Date' => $date->format('r'))
        );

        return $this->send($request);
    }

    /**
     * Собирает строку запроса из URI и параметров
     *
     * @param string $uri    URI
     * @param array  $params Параметры запроса
     *
     * @return string
     */
    public function processUri($uri, array $params = null)
    {
        $part = parse_url($uri);

        if (!preg_match('/\.[a-z]+$/u', $part['path'])) {
            $part['path'] .= '.easy';
        }

        $uri = $part['path'];

        if ($params) {
            if (!empty($part['query'])) {
                parse_str($part['query'], $params);
            }
            $uri .= '?'.http_build_query($params);
        } elseif (!empty($part['query'])) {
            $uri .= '?'.$part['query'];
        }

        return $uri;
    }

    /**
     * Осуществляет отправку запроса
     *
     * @param Request $request Параметры запроса
     *
     * @return string Ответ на запрос
     */
    protected function send(Request $request)
    {
        $signature = self::calcSignature($request, $this->secretKey);

        $headers = array(
            'Date: '.$request->Date,
            'X-Authorization: '.$this->accessId.':'.$signature,
            'Accept: application/json',
        );
        if ($request->ContentType) {
            $headers[] = 'Content-Type: '.$request->ContentType;
        }
        if ($request->ContentMD5) {
            $headers[] = 'Content-MD5: '.$request->ContentMD5;
        }

        $url = 'http'.($this->https ? 's' : '').'://'.$this->host.$request->Uri;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, __CLASS__);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->Method);

        if ($request->Method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($request->PostFields) {
                $postFields = is_array($request->PostFields) ? http_build_query(
                    $request->PostFields
                ) : $request->PostFields;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($this->https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $fh = null;
        if ($this->outputFile) {
            $fh = fopen($this->outputFile, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fh);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        if ($this->outputFile) {
            curl_exec($ch);
            $this->result = null;
            fclose($fh);
        } else {
            $this->result = curl_exec($ch);
        }

        $this->info = curl_getinfo($ch);
        $this->error = curl_error($ch);

        curl_close($ch);

        if ($this->result && 'api' === substr($request->Uri, -3)) {
            $this->result = json_decode($this->result);
            if (null === $this->result) {
                $this->error = json_last_error_msg().' in: '.PHP_EOL.$this->result;
            }
        }

        return $this->result;
    }

    /**
     * Вычисляет подпись запроса
     *
     * @param Request $Request   Параметры запроса
     * @param string  $SecretKey Секретный ключ
     *
     * @return string Подпись запроса
     */
    public static function calcSignature(Request $Request, $SecretKey)
    {
        $stringToSign = $Request->Method."\n".
            $Request->ContentMD5."\n".
            $Request->ContentType."\n".
            $Request->Date."\n".
            $Request->Host.$Request->Uri;

        return base64_encode(self::hashHmac('sha1', $stringToSign, $SecretKey));
    }

    /**
     * Клон функции hash_hmac
     *
     * @param string  $algorithm алгоритм, по которому производится шифрование
     * @param string  $data      строка для шифрования
     * @param string  $key       ключ
     * @param boolean $rawOutput
     *
     * @return string
     */
    public static function hashHmac($algorithm, $data, $key, $rawOutput = false)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac($algorithm, $data, $key, $rawOutput);
        }
        $algorithm = strtolower($algorithm);
        $pack = 'H'.strlen($algorithm('test'));
        $size = 64;
        $opad = str_repeat(chr(0x5C), $size);
        $ipad = str_repeat(chr(0x36), $size);

        if (strlen($key) > $size) {
            $key = str_pad(pack($pack, $algorithm($key)), $size, chr(0x00));
        } else {
            $key = str_pad($key, $size, chr(0x00));
        }

        for ($i = 0; $i < strlen($key) - 1; $i++) {
            $opad[$i] ^= $key[$i];
            $ipad[$i] ^= $key[$i];
        }

        $output = $algorithm($opad.pack($pack, $algorithm($ipad.$data)));

        return $rawOutput ? pack($pack, $output) : $output;
    }

    /**
     * Отправляет POST-запрос
     *
     * @param string $uri
     * @param array  $params GET-параметры
     *
     * @return string|object|array Ответ на запрос
     * @throws \Exception
     */
    public function post($uri, array $params = null)
    {
        $date = new DateTime();

        $uri = $this->processUri($uri);

        $headers = array(
            'Date'         => $date->format('r'),
            'Post-Fields'  => $params,
            'Content-Type' => 'application/x-www-form-urlencoded',
        );

        $request = Request::create('POST', $this->host, $uri, $headers);

        return $this->send($request);
    }

    /**
     * Возвращает результат последнего запроса
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Возвращает информацию о последнем запросе
     *
     * @param string $param Параметр запроса (если не указан, возвращается вся информация)
     *
     * @return mixed
     */
    public function getInfo($param = null)
    {
        if ($param) {
            return isset($this->info[$param]) ? $this->info[$param] : null;
        } else {
            return $this->info;
        }
    }

    /**
     * Возвращает последнюю ошибку запроса
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }
}
