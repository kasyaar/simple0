<?php
/**
 * @author nfx
 */
require_once __DIR__.'/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->get('/', function () {
    return 'index';
});

$app['debug'] = true;

$app['mongo'] = $app->share(function($app) {
    $mongo = new Mongo();
    return $mongo->selectDB('test');
});


require_once 'User.php';

// returns list of all of the users
$app->get('/users', function () use ($app) {
    $cursor = $app['mongo']->users->find(array(), array('_id'));

    foreach($cursor as $document) {
        $ids[] = $document['_id'].'';
    }

    return $app->json($ids);
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

    $result = $app['mongo']->users->update(array('_id' => new MongoId($id)),
        $user->toArray(), array('upsert' => false, 'safe' => true, 'multiple' => false));

    if(!$result) {
        return $app->json(array('message' => 'error connecting to server'), 500);
    }

    if(!$result['updatedExisting']) {
        return $app->json(array('message' => 'user not found'), 404);
    }

    return $app->redirect("/users/$id");
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

    return $app->redirect('/');
});

return $app;