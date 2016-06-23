<?php

namespace Megaplan\SimpleClient;

/**
 * Объект-контейнер параметров запроса
 *
 * @property string ContentType
 * @property string ContentMD5
 * @property string Method
 * @property string PostFields
 * @property string Uri
 * @property string Date
 * @property string Host
 */
class Request
{
    /**
     * @var array Список поддерживаемых HTTP-методов
     */
    protected static $supportingMethods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * @var array Список принимаемых HTTP-заголовков
     */
    protected static $acceptedHeaders = array('Date', 'Content-Type', 'Content-MD5', 'Post-Fields');

    /**
     * @var array Список параметров
     */
    protected $params;

    /**
     * Создает объект
     *
     * @param array $Params Параметры запроса
     */
    protected function __construct(array $Params)
    {
        $this->params = $Params;
    }

    /**
     * Создает и возвращает объект
     *
     * @param string $method  Метод запроса
     * @param string $host    Хост мегаплана
     * @param string $uri     URI запроса
     * @param array  $headers Заголовки запроса
     *
     * @return Request
     * @throws \Exception
     */
    public static function create($method, $host, $uri, array $headers = [])
    {
        $method = mb_strtoupper($method);
        if (!in_array($method, self::$supportingMethods, true)) {
            throw new \BadMethodCallException("Non supported HTTP-Method '$method'");
        }

        $params = array(
            'Method' => $method,
            'Host'   => $host,
            'Uri'    => $uri,
        );

        // фильтруем заголовки
        $validHeaders = array_intersect_key($headers, array_flip(self::$acceptedHeaders));
        $params = array_merge($params, $validHeaders);

        return new self($params);
    }

    /**
     * Возвращает параметры запроса
     *
     * @param string $name
     *
     * @return string
     */
    public function __get($name)
    {
        $name = preg_replace('/([a-z]{1})([A-Z]{1})/u', '$1-$2', $name);

        if (!empty($this->params[$name])) {
            return $this->params[$name];
        } else {
            return '';
        }
    }
}
