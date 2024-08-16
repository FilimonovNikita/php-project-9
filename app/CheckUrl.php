<?php

namespace Check;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;

class CheckUrl
{
    public function checkUrlConnect($url): array
    {
        try {
            $client = new Client();
            $req = $client->request("GET", $url);
            $result = [];
        } catch (ConnectException $e) {
            $result["ConnectException"] = 'Произошла ошибка при проверке, не удалось подключиться';
            return $result;
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() != 200) {
                $result['status'] = $e->getResponse()->getStatusCode();
                $result["ClientException"] = 'Проверка была выполнена успешно, но сервер ответил с ошибкой';
                return $result;
            }
            return $result;
        }
    }
    public function getUrlCheckData(string $url): array
    {
        $client = new Client();
        $res = $client->request("GET", $url);

        $statusCode = $res->getStatusCode();
        $document = new Document($res->getBody()->getContents(), false);

        // Проверка и работа с элементом title
        $titleElement = $document->first('title');
        $title = ($titleElement instanceof Element) ? $titleElement->text() : '';

        // Проверка и работа с элементом h1
        $h1Element = $document->first('h1');
        $h1 = ($h1Element instanceof Element) ? $h1Element->text() : '';

        // Проверка и работа с элементом meta[name="description"]
        $descriptionElement = $document->first('meta[name="description"]');
        $description = ($descriptionElement instanceof Element) ? $descriptionElement->getAttribute('content') : '';

        return [
            'statusCode' => $statusCode,
            'title' => $title,
            'h1' => $h1,
            'description' => $description
        ];
    }
}
