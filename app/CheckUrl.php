<?php

namespace Check;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;

class CheckUrl
{
    public function checkUrlConnect($url)
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
            return [$result];
        }
    }
    public function getUrlCheckData($url)
    {
        $client = new Client();
        $res = $client->request("GET", $url);
        $statusCode = $res->getStatusCode();
        $document = new Document($res->getBody()->getContents(), false);
        $title = $document->first('title') ? $document->first('title')->text() : '';
        $h1 = $document->first('h1') ? $document->first('h1')->text() : '';
        $description = $document->first('meta[name="description"]') ?
            $document->first('meta[name="description"]')->getAttribute('content') : '';
        $result = [
            'statusCode' => $statusCode,
            'title' => $title,
            'h1' => $h1,
            'description' => $description];
        return $result;
    }
}
