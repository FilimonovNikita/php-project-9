<?php

namespace Check;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;

function optional(?object $value): object
{
    return new class ($value)
    {
        protected ?object $value;

        public function __construct(?object $value)
        {
            $this->value = $value;
        }

        public function __call(string $method, array $args): mixed
        {
            if (is_null($this->value)) {
                return null;
            }
            return $this->value->{$method}(...$args);
        }

        public function __get(string $property): mixed
        {
            if (is_null($this->value)) {
                return null;
            }
            return $this->value->{$property};
        }
    };
}
class CheckUrl
{
    public function checkUrlConnect($url): array
    {
        $result = [];
        try {
            $client = new Client();
            $req = $client->request("GET", $url);
        } catch (ConnectException $e) {
            $result["ConnectException"] = 'Произошла ошибка при проверке, не удалось подключиться';
            return $result;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() != 200) {
                $result['status'] = $e->getResponse()->getStatusCode();
                $result["ClientException"] = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
                return $result;
            }
        }
        return $result;
    }

    public function getUrlCheckData(string $url): array
    {
        $client = new Client();
        $res = $client->request('GET', $url);
        $statusCode = $res->getStatusCode();
        $document = new Document($res->getBody()->getContents(), false);

        $h1 = optional($document->first('h1'))->text();
        $title = optional($document->first('title'))->text();
        $description = optional($document->first('meta[name=description]'))->content;

        $result = [
            'statusCode' => $statusCode,
            'title' => $title,
            'h1' => $h1,
            'description' => $description
        ];
        return $result;
    }
}
