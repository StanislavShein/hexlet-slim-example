<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;

session_start();

$container = new Container();

$container->set('renderer', function () {
    // подключение шаблонов
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    // подключение флеш-сообщений
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!'); 
    return $response;
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("this : {$id}");
});

$app->get('/courses/{courseId}/lessons/{id}', function ($request, $response, array $args) {
    $courseId = $args['courseId'];
    $id = $args['id'];

    return $response->write("Course id: {$courseId}")
    ->write("<br/>  Lesson id: {$id}");
});

// СПИСОК ПОЛЬЗОВАТЕЛЕЙ

$app->get('/users', function ($request, $response) use ($router) {
    $file = file_get_contents('src/Users.php'); // открываем файл
    $users = json_decode($file, true); // приводим данные к массиву
    $messages = $this->get('flash')->getMessages(); // извлечение флеш-сообщений

    $term = $request->getQueryParam('term'); // достаем поисковые данные
    $result = collect($users)->filter(
        fn ($user) => str_contains($user['name'], $term) ? $user['name'] : false
    ); // фильтруем данные по запросу
    $params = ['users' => $result, 'term' => $term, 'flash' => $messages];

    return $this->get('renderer')->render($response, 'users/users.phtml', $params);
})->setName('get-users');

// СОЗДАНИЕ НОВОГО ПОЛЬЗОВАТЕЛЯ

$app->post('/users', function ($request, $response) use ($router) {
    $id = rand(1, 100); // создаём id
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);

    // запись файла
    if (count($errors) === 0) {
        $path = 'src/Users.php';
        $this->get('flash')->addMessage('success', 'Пользователь был добавлен'); // добавляем флеш-сообщение о регистрации
        $file = file_get_contents($path); // открываем файл
        $fileArray = json_decode($file, true); // приводим данные к массиву
        $data = ['name' => $user['name'], 'email' => $user['email'], 'id' => $id];
        $fileArray[] = $data; // добавляем в массив данные нового пользователя
        file_put_contents($path, json_encode($fileArray)); // сохраняем массив в json формате

        return $response->withRedirect('/users');
    }
    $params = ['user' => $user, 'errors' => $errors];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('post-users');

// ФОРМА СОЗДАНИЯ НОВОГО ПОЛЬЗОВАТЕЛЯ

$app->get('/users/new', function ($request, $response) {
    $params = ['user' => ['name' => '', 'email' => '']];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users-new');

// ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ

$app->get('/users/{id}', function ($request, $response, $args) {
    $file = file_get_contents('src/Users.php'); //открываем файл
    $users = json_decode($file, true); // приводим данные к массиву

    foreach ($users as $user) {
        if (in_array($args['id'], $user) === true) { // П Р О В Е Р И Т Ь //////////////////////////////////////////////////////
            $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
            return $this->get('renderer')->render($response,'users/show.phtml', $params);
        }
    }
    
    return $response->withStatus(404);
})->setName('userID');

// ФОРМА ОБНОВЛЕНИЯ ДАННЫХ ПОЛЬЗОВАТЕЛЯ

$app->get('/users/{id}/edit', function ($request, $response, $args) {
    $id = $args['id']; // id пользователя
    $file = file_get_contents('src/Users.php'); // открываем файл
    $users = json_decode($file, true); // приводим данные к массиву
    $user = collect($users)->firstWhere('id', $id);

    $params = [
        'user' => $user,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

$app->run();
