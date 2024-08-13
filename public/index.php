<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;
use PostgreSQL\Connection;
use PostgreSQL\PostgreSQLCreateTable;

session_start();
putenv('DATABASE_URL=postgresql://user1:sql@localhost:5432/project9');
try {
    $pdo = Connection::get()->connect();
    //echo 'A connection to the PostgreSQL database sever has been established successfully.';
    $tableCreator = new PostgreSQLCreateTable($pdo);

    // создание и запрос таблицы из
    // базы данных
    $tables = $tableCreator->createTables();
    //print ('1');
} catch (\PDOException $e) {
    echo $e->getMessage();
}

$container = new Container();
$container->set('pdo', function () use ($pdo) {
    return $pdo;
});
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
    $errors = $_SESSION['errors'];
    $params = ['errors' => $errors];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('home');

$app->get('/urls', function ($request, $response) {
    $pdo = $this->get('pdo');
    $insertUrl = new PostgreSQLCreateTable($pdo);
    $allData = $insertUrl->getAllData();
    usort($allData, function ($a, $b) {
        return $b['id'] <=> $a['id'];
    });
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
    } elseif (!empty($validateResult)) {
        $_SESSION['errors'] = $validateResult;
        return $response->withHeader('Location', '/')->withStatus(302);
    }
})->setName('insertUrl');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $flash = $this->get('flash')->getMessages();
    $databse = $this->get('pdo');
    $id = $args['id'];
    $lastUrl = new PostgreSQLCreateTable($databse);
    $dataUrl = $lastUrl->getURLData($id);
    $params = [
            'id' => $dataUrl[0]['id'],
            'name' => $dataUrl[0]['name'],
            'created_at' => $dataUrl[0]['create_at'],
            'flash' => $flash];

    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('currentUrl');

$app->get('/routes', function ($request, $response, $args) {
    /*$routes = $this->getRouteCollector()->getRoutes();
    foreach ($routes as $route) {
        echo $route->getPattern() . "<br>";
    }*/
    print ('Hellow');
    return $response;
});

$app->run();
