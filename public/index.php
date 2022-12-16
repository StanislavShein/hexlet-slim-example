<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use App\Validator;

use function Symfony\Component\String\s;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

$app->get('/users/new', function ($request, $response) {
    $id = rand();
    $params = ['user' => ['name' => '', 'email' => '', 'id' => $id], 'errors' => []];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('users-new');

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

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->get('/users', function ($request, $response) {
    $file = file_get_contents('src/Users.php');
    $users = json_decode($file, true);

    $term = $request->getQueryParam('term');
    $result = collect($users)->filter(
        fn ($user) => str_contains($user['name'], $term) ? $user['name'] : false
    );
    $params = ['users' => $result, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/users.phtml', $params);
})->setName('get-users');

$app->post('/users', function ($request, $response) {
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $path = 'src/Users.php';
        $file = file_get_contents($path);
        $fileArray = json_decode($file, true);
        $data = ['name' => $user['name'], 'email' => $user['email'], 'id' => $user['id']];
        $fileArray[] = $data;
        file_put_contents($path, json_encode($fileArray));
        return $response->withRedirect('/users');
    }
    $params = ['user' => $user, 'errors' => $errors];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('post-users');

$router = $app->getRouteCollector()->getRouteParser();

$app->run();
