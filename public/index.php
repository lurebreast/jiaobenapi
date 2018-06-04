<?php
Phalcon\Debug::disable();
try {
    $config_basedir = __DIR__ . '/../apps/config/';
    Phalcon\Config\Adapter\Json::setBasePath($config_basedir);
    $config = new Phalcon\Config\Adapter\Php('config.php');
    // Create a DI
    $di = new Phalcon\Di\FactoryDefault();
    $di->set('db', function () use ($config) {
        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            'host' => $config->database->host,
            'port' => $config->database->port,
            'username' => $config->database->username,
            'password' => $config->database->password,
            'dbname' => $config->database->dbname,
            'charset'=> $config->database->charset
        ));
    }, true);

    $di->set('config', function () use ($config) {
        $config['docroot'] = __DIR__ . DIRECTORY_SEPARATOR;
        return $config;
    });

    // Setup a base URI so that all generated URIs include the "tutorial" folder
    $di->set('url', function () {
        $url = new Phalcon\Mvc\Url();
        $url->setBaseUri('/');
        return $url;
    });
    $di->set('flashSession', function() use ($config) {
        $flash = new \Phalcon\Flash\Session(array(
            'error' => $config->style->error,
            'success' => $config->style->success,
            'notice' => $config->style->notice,
        ));
        return $flash;
    });

    //session_set_cookie_params(0, '/', $config->cookie->domain, false, true);

    $session = new \Phalcon\Session\Adapter\Files();
    $session->start();


    $di->set('session', function () use ($session) {
        return $session;
    });

    $loader = new \Phalcon\Loader();

        $dirs = array(
            $config->controllersDir,
            $config->publicControllerDir,
            $config->modelsDir,
            $config->publicModelsDir,
            $config->collectionDir,
            $config->libraryDir,
        );

    $loader->registerDirs($dirs)->register();
    $di->set('view', function () use ($config) {
        $view = new \Phalcon\Mvc\View();
            $dirs = array($config->viewsDir, $config->publicViewDir);
        $view->setBasePath($dirs);
        return $view;
    }, true);
    // Handle the request
    $application = new Phalcon\Mvc\Application($di);

    echo $application->handle()->getContent();

} catch (\Exception $e) {
    echo "Exception: ", $e->getMessage();
}