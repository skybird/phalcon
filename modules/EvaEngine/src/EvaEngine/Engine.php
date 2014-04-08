<?php

namespace Eva\EvaEngine;

use Phalcon\Mvc\Router;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\DI\FactoryDefault;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Config;
use Phalcon\Mvc\View;
use Phalcon\Loader;
use Phalcon\Mvc\Application;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Logger\Adapter\File as FileLogger;

use Eva\EvaEngine\ModuleManager;

class Engine
{
    protected $appRoot;

    protected $modulesPath;

    protected $di;

    protected $application;

    protected $configPath;

    public function setConfigPath($path)
    {
        $this->configPath = $path;
        return $this;
    }

    public function getConfigPath()
    {
        if($this->configPath) {
            return $this->configPath;
        }

        return $this->configPath = $this->appRoot . '/config';
    }

    public function initErrorHandler()
    {
    
    }

    public function getApplication()
    {
        if($this->application) {
            return $this->application;
        }
        return $this->application = new Application();
    }

    public function getDI()
    {
        if($this->di) {
            return $this->di;
        }

        $di = new FactoryDefault();
        $self = $this;

        $di->set('config', function () use ($di, $self) {
            $config = new Config(include $self->getConfigPath() . "/config.default.php");


            //merge all loaded module configs
            $modules = $di->get('modules');
            if($modules) {
                $modulesArray = $modules->getModules();
                foreach($modulesArray as $moduleName => $module) {
                    $moduleConfig = $modules->getModuleConfig($moduleName);
                    if($moduleConfig instanceof Config) {
                        $config->merge($moduleConfig);
                    } else {
                        $config->merge(new Config($moduleConfig));
                    }
                }
            }

            if(false === file_exists($self->getConfigPath() . "/config.local.php")) {
                return $config;
            }

            $config->merge(new Config(include $self->getConfigPath() . "/config.local.php"));
            return $config;
        });

        $di->set('router', function () use ($di) {
            $config = $di->get('config');
            $router = new Router();
            $router->removeExtraSlashes(true);
            if(isset($config->routes)) {
                foreach($config->routes as $url => $route) {
                    $router->add($url, $route->toArray());
                }
            }
            return $router;
        });

        $di->set('url', function () use ($di) {
            $config = $di->get('config');
            $url = new UrlResolver();
            $url->setBaseUri($config->baseUri);
            return $url;
        });

        $di->set('session', function () {
            $session = new SessionAdapter();
            if(!$session->isStarted()) {
                @$session->start();
            }
            return $session;
        });

        $di->set('cookies', function() {
            $cookies = new \Phalcon\Http\Response\Cookies();
            $cookies->useEncryption(false);
            return $cookies;
        });

        $di->set('view', function () {
            $view = new View();
            $view->setViewsDir(__DIR__ . '/views/');
            return $view;
        });

        $di->set('mailer', function () use ($di) {
            $config = $di->get('config');
            $transport = \Swift_SmtpTransport::newInstance()
                ->setHost($config->mailer->host)
                ->setPort($config->mailer->port)
                ->setEncryption($config->mailer->encryption)
                ->setUsername($config->mailer->username)
                ->setPassword($config->mailer->password)
            ;

            $mailer = \Swift_Mailer::newInstance($transport);
            return $mailer;
        });

        $di->set('mailMessage', 'Eva\EvaEngine\MailMessage');

        $di->set('queue', function() use ($di) {
            $config = $di->get('config');
            $client = new \GearmanClient();
            $client->setTimeout(1000);
            foreach($config->queue->servers as $key => $server) {
                $client->addServer($server->host, $server->port);
            }
            return $client;
        });

        $di->set('worker', function() use ($di) {
            $config = $di->get('config');
            $worker = new \GearmanWorker();
            foreach($config->queue->servers as $key => $server) {
                $worker->addServer($server->host, $server->port);
            }
            return $worker;
        });

        $di->set('flash', function(){
            $flash = new \Phalcon\Flash\Session();
            return $flash;
        });

        $di->set('escaper', function(){
            return new \Phalcon\Escaper();
        });

        $di->set('translate', function () use ($di) {
            $config = $di->get('config');
            $file = $config->translate->path . $config->translate->forceLang . '.csv';
            if(false === file_exists($file)) {
                $file = $config->translate->path . 'empty.csv';
            }
            $translate = new \Phalcon\Translate\Adapter\Csv(array(
                'file' => $file,
                'delimiter' => ',',
            ));
            return $translate;
        });

        $di->set('tag', function () use ($di) {
            \Eva\EvaEngine\Tag::setDi($di);
            return new \Eva\EvaEngine\Tag();
        });

        $di->set('logException', function () use ($di) {
            $config = $di->get('config');
            return $logger = new FileLogger($config->logger->path . 'error_' . date('Y-m-d') . '.log');
        });

        $di->set('modelsMetadata', function() use ($di) {
            $config = $di->get('config');
            $metaData = new \Phalcon\Mvc\Model\Metadata\Files($config->modelsMetadata->options->toArray());
            return $metaData;
        });


        $di->set('db', function () use ($di) {
            $config = $di->get('config');
            $dbAdapter = new DbAdapter(array(
                'host' => $config->dbAdapter->master->host,
                'username' => $config->dbAdapter->master->username,
                'password' => $config->dbAdapter->master->password,
                'dbname' => $config->dbAdapter->master->database,
            ));
            $eventsManager = new EventsManager();
            $logger = new FileLogger($config->logger->path . date('Y-m-d') . '.log');
            $eventsManager->attach('db', function($event, $dbAdapter) use ($logger) {
                if ($event->getType() == 'beforeQuery') {
                    $sqlVariables = $dbAdapter->getSQLVariables();
                    if (count($sqlVariables)) {
                        $query = str_replace(array('%', '?'), array('%%', "'%s'"), $dbAdapter->getSQLStatement());
                        $query = vsprintf($query, $sqlVariables);
                        //
                        $logger->log($query, \Phalcon\Logger::INFO);
                    } else {
                        $logger->log($dbAdapter->getSQLStatement(), \Phalcon\Logger::INFO);
                    }
                }
            });

            $dbAdapter->setEventsManager($eventsManager);
            return $dbAdapter;
        });

        $di->set('dbMaster', function () use ($di) {
            $config = $di->get('config');

            $dbAdapter = new DbAdapter(array(
                'host' => $config->dbAdapter->master->host,
                'username' => $config->dbAdapter->master->username,
                'password' => $config->dbAdapter->master->password,
                'dbname' => $config->dbAdapter->master->database,
                'charset' => isset($config->dbAdapter->master->charset) ? $config->dbAdapter->master->charset : 'utf8',
            ));

            if ($config->debug) {
                $eventsManager = new EventsManager();
                $logger = new FileLogger($config->logger->path . date('Y-m-d') . '.log');
                $eventsManager->attach('db', function($event, $dbAdapter) use ($logger) {
                    if ($event->getType() == 'beforeQuery') {
                        $sqlVariables = $dbAdapter->getSQLVariables();
                        if (count($sqlVariables)) {
                            $query = str_replace(array('%', '?'), array('%%', "'%s'"), $dbAdapter->getSQLStatement());
                            $query = vsprintf($query, $sqlVariables);
                            //
                            $logger->log($query, \Phalcon\Logger::INFO);
                        } else {
                            $logger->log($dbAdapter->getSQLStatement(), \Phalcon\Logger::INFO);
                        }
                    }
                });
                $dbAdapter->setEventsManager($eventsManager);
            }

            return $dbAdapter;
        });


        $di->set('dbSlave', function () use ($di) {
            $config = $di->get('config');
            $slaves = $config->dbAdapter->slave;
            $slaveKey = array_rand($slaves->toArray());
            $dbAdapter = new DbAdapter(array(
                'host' => $config->dbAdapter->slave->$slaveKey->host,
                'username' => $config->dbAdapter->slave->$slaveKey->username,
                'password' => $config->dbAdapter->slave->$slaveKey->password,
                'dbname' => $config->dbAdapter->slave->$slaveKey->database,
                'charset' => isset($config->dbAdapter->slave->$slaveKey->charset) ? $config->dbAdapter->slave->$slaveKey->charset : 'utf8',
            ));

            if ($config->debug) {
                $eventsManager = new EventsManager();
                $logger = new FileLogger($config->logger->path . date('Y-m-d') . '.log');
                $eventsManager->attach('db', function($event, $dbAdapter) use ($logger) {
                    if ($event->getType() == 'beforeQuery') {
                        $sqlVariables = $dbAdapter->getSQLVariables();
                        if (count($sqlVariables)) {
                            $query = str_replace(array('%', '?'), array('%%', "'%s'"), $dbAdapter->getSQLStatement());
                            $query = vsprintf($query, $sqlVariables);
                            //
                            $logger->log($query, \Phalcon\Logger::INFO);
                        } else {
                            $logger->log($dbAdapter->getSQLStatement(), \Phalcon\Logger::INFO);
                        }
                    }
                });
                $dbAdapter->setEventsManager($eventsManager);
            }
            return $dbAdapter;
        });


        $di->set('fileSystem', function () use ($di) {
            $config = $di->get('config');
            $adapter = new \Gaufrette\Adapter\Local();
            $filesystem = new Filesystem($adapter);
            return $filesystem;
        });

        return $this->di = $di;
    }

    public function setModulesPath($modulesPath)
    {
        $this->modulesPath = $modulesPath;
        return $this;
    }

    public function getModulesPath()
    {
        if($this->modulesPath) {
            return $this->modulesPath;
        }

        return $this->modulesPath = $this->appRoot . '/modules';
    }

    public function loadModules(array $modules)
    {
        $moduleArray = array();
        $modulesPath = $this->getModulesPath();

        foreach($modules as $key => $module) {
            if(is_array($module)) {
                $moduleArray[$key] = $module;
            } elseif(is_string($module)) {
                //Only Module Name means its a Eva Standard module
                $moduleArray[$module] = array(
                    'className' => "Eva\\$module\\Module",
                    'path' => "$modulesPath/$module/Module.php",
                );
            } else {
                throw new \Exception('Module not load by incorrect format');
            }
        }

        $application = $this->getApplication();
        $application->registerModules($moduleArray);

        $modules = $application->getModules();
        $loader = new Loader();
        $loaderArray = array();
        foreach($moduleArray as $module) {
            $loaderArray[$module['className']] = $module['path'];
        }
        $loader->registerClasses($loaderArray)->register();
        $loaderArray = array();
        foreach($moduleArray as $module) {
            $moduleLoader = method_exists($module['className'], 'registerGlobalAutoloaders') ? 
            $module['className']::registerGlobalAutoloaders() :
            array();
            if($moduleLoader instanceof $loader) {
                continue;
            }
            $loaderArray += $moduleLoader;
        }
        if($loaderArray) {
            $loader->registerNamespaces($loaderArray)->register();
        }

        $di = $this->getDI();
        $di->set('modules', function() use ($moduleArray){
            return new ModuleManager($moduleArray);
        });
        return $this;
    }

    public function bootstrap()
    {
        $configPath = $this->getConfigPath();
        $application = $this->getApplication();
        $application->setDI($this->getDI());

        //Error Handler must run before router start
        $this->initErrorHandler();
        return $this;
    }

    public function run()
    {
        echo $this->getApplication()->handle()->getContent();
    }

    public function __construct($appRoot = null)
    {
        $this->appRoot = $appRoot ? $appRoot : __DIR__;

    }

}
