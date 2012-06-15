<?php

use Silex\WebTestCase as BaseWebTestCase;

class UsersTest extends BaseWebTestCase
{
    public function userFormDataProvider()
    {
        return array(
            'empty all' => array(
                array(),
                'User.firstName:
    This value should not be null.
User.firstName:
    This value should not be blank.
User.lastName:
    This value should not be null.
User.lastName:
    This value should not be blank.
User.email:
    This value should not be null.
User.email:
    This value should not be blank.'
            ),
            'empty first, last and email' => array(
                array(
                    'firstName' => '',
                    'lastName' => '',
                    'email' => '',
                ),
                'User.firstName:
    This value should not be blank.
User.lastName:
    This value should not be blank.
User.email:
    This value should not be blank.'
            ),
            'wrong email' => array(
                array(
                    'firstName' => 'first name',
                    'lastName' => 'last name',
                    'email' => 'foo',
                ),
                'User.email:
    This value is not a valid email address.'
            )
        );
    }

    /**
     * @dataProvider userFormDataProvider
     */
    public function testCreatingNewUserWithValidationShouldProduceError($params, $expected = '')
    {
        $response = $this->request('POST', '/users', $params);
        $this->assertFalse($this->client->getResponse()->isSuccessful());
        $this->assertEquals($expected, trim($response->message), "Actually: $response->message");
    }

    public function testCreatesUserSuccessfully()
    {
        $userCreated = $this->request('POST', '/users', array(
            'firstName' => 'Wonnie',
            'lastName' => 'Baar',
            'email' => 'wonnie@baar.com'
        ));

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->assertTrue(isset($userCreated->id));

        $user = $this->request('GET', "/users/{$userCreated->id}");
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Wonnie', $user->firstName);
        $this->assertEquals('Baar', $user->lastName);
        $this->assertEquals('wonnie@baar.com', $user->email);
    }

    public function testFetchingDeadUserShould404()
    {
        $this->request('GET', '/users/6661');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdatingDeadUserShould404()
    {
        $this->request('PUT', '/users/6661');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeletingDeadUserShould404()
    {
        $this->request('DELETE', '/users/6661');
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdatingUserShouldReallyUpdate()
    {
        $userCreated = $this->request('POST', '/users', array(
            'firstName' => 'Wonnie',
            'lastName' => 'Baar',
            'email' => 'wonnie@baar.com'
        ));

        $this->request('PUT', "/users/{$userCreated->id}", array(
            'email' => 'new@email.com'
        ));

        $user = $this->request('GET', "/users/{$userCreated->id}");
        $this->assertEquals('new@email.com', $user->email);
        $this->assertEquals('Wonnie', $user->firstName);
        $this->assertEquals('Baar', $user->lastName);
    }

    public function testDeletingShouldWork()
    {
        $userCreated = $this->request('POST', '/users', array(
            'firstName' => 'Wonnie',
            'lastName' => 'Baar',
            'email' => 'wonnie@baar.com'
        ));

        $this->request('DELETE', "/users/{$userCreated->id}");
        $this->request('GET', "/users/{$userCreated->id}");

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexShouldShowAllUsers()
    {
        $user0 = $this->request('POST', '/users', array(
            'firstName' => 'Wonnie',
            'lastName' => 'Baar',
            'email' => 'wonnie@baar.com'
        ));

        $user1 = $this->request('POST', '/users', array(
            'firstName' => 'Poogle',
            'lastName' => 'Pig',
            'email' => 'piglet@baar.com'
        ));

        $user2 = $this->request('POST', '/users', array(
            'firstName' => 'Lion',
            'lastName' => 'Ox',
            'email' => 'manager@baar.com'
        ));

        $index = $this->request('GET', '/users');

        $this->assertEquals(array($user0->id, $user1->id, $user2->id), $index);
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../app/app.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function tearDown()
    {
        $this->app['mongo']->users->remove();
    }

    /**
     * @var \Symfony\Component\BrowserKit\Client
     */
    protected $client;

    protected function request($method, $uri, $params = array()) {
        $this->client = $this->createClient();

        $this->client->request($method, $uri, $params);

        return json_decode($this->client->getResponse()->getContent());
    }
}