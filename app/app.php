<?php
/**
 * @author nfx
 */
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

$app = new Silex\Application();
$app->register(new Silex\Provider\ValidatorServiceProvider());


$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

$app->get('/', function () {
    include 'views/index.phtml';
});

$app['debug'] = true;

$app['mongo'] = $app->share(function($app) {
    $mongo = new Mongo();
    return $mongo->selectDB('test');
});


require_once 'User.php';

// returns list of all of the users
$app->get('/users', function () use ($app) {
    $cursor = $app['mongo']->users->find();

    $users = array();
    foreach($cursor as $document) {
        $user = new User();
        $user->bindDocument($document);
        $users[] = $user;
    }

    return $app->json($users);
});

// show user info
$app->get('/users/{id}', function($id) use ($app)  {
    $user = new User();

    $res = $app['mongo']->users->findOne(array('_id' => new MongoId($id)));
    if (empty($res)) {
        return $app->json(array('message' => 'user not found'), 404);
    }

    $user->bindDocument($res);

    return $app->json($user);
});

// add new user
$app->post('/users', function(Request $request) use ($app)  {
    $user = new User();
    $user->bindRequest($request);

    $violations = $app['validator']->validate($user);
    if(sizeof($violations) > 0) {
        return $app->json(array('message' => (string) $violations), 406);
    }

    $document = $user->toArray();
    $success = $app['mongo']->users->insert($document, array('safe' => true));
    if(!$success) {
        return $app->json(array('message' => 'error connecting to server'), 500);
    }

    return $app->json(array('id' => $document['_id'].''), 201);
});

// edits information about the user
$app->put('/users/{id}', function(Request $request, $id) use ($app)  {
    $user = new User();
    $user->bindRequest($request);

    $fieldsToUpdate = $user->toArray();
    if(0 == sizeof($fieldsToUpdate)) {
        return $app->json(array('message' => 'empty body'), 400);
    }

    $violations = array();
    foreach(array_keys($fieldsToUpdate) as $field) {
        $violation = $app['validator']->validateProperty($user, $field);
        if (0 !== $violation->count()) {
            $violations[] = "User.$field:\n    {$violation[0]->getMessage()}";
        }
    }

    if(sizeof($violations) > 0) {
        return $app->json(array('message' => join("\n", $violations)), 406);
    }

    $result = $app['mongo']->users->update(
        array('_id' => new MongoId($id)),
        array('$set' => $user->toArray()),
        array('upsert' => false, 'safe' => true, 'multiple' => false));

    if(!$result) {
        return $app->json(array('message' => 'error connecting to server'), 500);
    }

    if(!$result['updatedExisting']) {
        return $app->json(array('message' => 'user not found'), 404);
    }

    return $app->json(array('success' => true));
});

// deletes user
$app->delete('/users/{id}', function($id) use ($app)  {
    $result = $app['mongo']->users->remove(array('_id' => new MongoId($id)),
        array('justOne' => true, 'safe' => true));

    if(!$result) {
        return $app->json(array('message' => 'error connecting to server'), 500);
    }

    if(0 == $result['n']) {
        return $app->json(array('message' => 'user not found'), 404);
    }

    return $app->json(array('success' => true));
});

return $app;