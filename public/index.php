<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use PostgreSQL\Connection;
use PostgreSQL\PostgreSQLCreateTable;
use Check\CheckUrl;

session_start();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeload();

$pdo = null;

try {
    $pdo = Connection::get()->connect();

    // Загрузка и выполнение SQL-файла для создания таблиц
    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
    } else {
        echo "SQL file not found.";
    }

    // Создание и запрос таблицы из базы данных
    $tableCreator = new PostgreSQLCreateTable($pdo);
    $tables = $tableCreator->createTables();
} catch (\PDOException $e) {
    echo $e->getMessage();
}

$container = new Container();
if ($pdo !== null) {
    $container = new Container();
    $container->set('pdo', function () use ($pdo) {
        return $pdo;
    });
} else {
    // Обработка ситуации, если $pdo не инициализирован
    echo "PDO connection could not be established.";
}
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});


AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$container->set('router', function () use ($app) {
    $router = $app->getRouteCollector()->getRouteParser();
    return $router;
});

$app->get('/', function ($request, $response) {
    $errors = $_SESSION['errors'] ?? '';
    $params = ['errors' => $errors];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('home');

////////////////////     /urls     ////////////////////

$app->get('/urls', function ($request, $response) {
    $pdo = $this->get('pdo');
    $insertUrl = new PostgreSQLCreateTable($pdo);
    $allData = $insertUrl->getAllLastCheksData();
    $params = [
        'data' => $allData
        ];
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('home');

$app->post('/urls', function ($request, $response) {
    $url = $request->getParsedBodyParam('url')['name'];
    $pdo = $this->get('pdo');
    $insertUrl = new PostgreSQLCreateTable($pdo);
    $validateResult = $insertUrl->validateUrls($url);

    if (isset($validateResult[0]['id'])) {
        $this->get('flash')->addMessage('success', "Страница уже существует");

        $id = $validateResult[0]['id'];
        $curUrl = $this->get('router')->urlFor('currentUrl', ['id' => $id]);
        return $response->withHeader('Location', $curUrl)->withStatus(302);
    } elseif (empty($validateResult)) {
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');

        $id = $insertUrl->insertUrls($url);

        $curUrl = $this->get('router')->urlFor('currentUrl', ['id' => $id]);
        return $response->withHeader('Location', $curUrl)->withStatus(302);
    } else {
        $params = ['errors' => $validateResult];
        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
    }
})->setName('insertUrl');

////////////////////     /urls/{id}     ////////////////////

$app->get('/urls/{id}', function ($request, $response, $args) {
    $flash = $this->get('flash')->getMessages();
    $databse = $this->get('pdo');
    $id = $args['id'];
    $lastUrl = new PostgreSQLCreateTable($databse);
    $dataUrl = $lastUrl->getURLData($id);
    $dataUrlCheks = $lastUrl->getUrlChecksData($id);
    $params = [
            'id' => $dataUrl[0]['id'],
            'name' => $dataUrl[0]['name'],
            'created_at' => $dataUrl[0]['create_at'],
            'flash' => $flash,
            'dataUrlCheks' => $dataUrlCheks];
    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('currentUrl');

////////////////////     /urls{id}/checks     ////////////////////

$app->post('/urls/{id}/checks', function ($request, $response, $args) {
    $id = $args["id"];

    $databse = $this->get('pdo');
    $lastUrl = new PostgreSQLCreateTable($databse);
    $dataUrl = $lastUrl->getURLData($id);
    $urlCheck = new CheckUrl();
    $connect = $urlCheck->checkUrlConnect($dataUrl[0]['name']);
    if (isset($connect['ConnectException'])) {
        $this->get('flash')->addMessage('failure', 'Произошла ошибка при проверке, не удалось подключиться');
    } elseif (isset($connect['ClientException'])) {
        $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
        $time = date('Y-m-d H:i:s');
        $result = [
            'url_id' => $dataUrl[0]['id'],
            'status_code' => $connect['status'],
            'h1' => 'Доступ ограничен: проблема с IP',
            'title' => 'Доступ ограничен: проблема с IP',
            'description' => 'Доступ ограничен: проблема с IP',
            'name' => $dataUrl[0]['name'],
            'create_at' => $time];
        $lasId = $lastUrl->insertUrlsChecks($result);
    } else {
        $data = $urlCheck->getUrlCheckData($dataUrl[0]['name']);
        $this->get('flash')->addMessage('success', "Страница успешно проверена");

        $result = [
            'url_id' => $dataUrl[0]['id'],
            'status_code' => $data['statusCode'],
            'h1' => $data['h1'],
            'title' => $data['title'],
            'description' => $data['description'],
            'name' => $dataUrl[0]['name'],
            'create_at' => $dataUrl[0]['create_at']];
        $lasId = $lastUrl->insertUrlsChecks($result);
    }
    $curUrl = $this->get('router')->urlFor('currentUrl', ['id' => $id]);
    return $response->withHeader('Location', $curUrl)->withStatus(302);
});

$app->run();
