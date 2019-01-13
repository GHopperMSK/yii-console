<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\console;

use yii\console\exceptions\UnknownCommandException;
use yii\exceptions\InvalidRouteException;

// define STDIN, STDOUT and STDERR if the PHP SAPI did not define them (e.g. creating console application in web env)
// http://php.net/manual/en/features.commandline.io-streams.php
\defined('STDIN') or \define('STDIN', fopen('php://stdin', 'r'));
\defined('STDOUT') or \define('STDOUT', fopen('php://stdout', 'w'));
\defined('STDERR') or \define('STDERR', fopen('php://stderr', 'w'));

/**
 * Application represents a console application.
 *
 * Application extends from [[\yii\base\Application]] by providing functionalities that are
 * specific to console requests. In particular, it deals with console requests
 * through a command-based approach:
 *
 * - A console application consists of one or several possible user commands;
 * - Each user command is implemented as a class extending [[\yii\console\Controller]];
 * - User specifies which command to run on the command line;
 * - The command processes the user request with the specified parameters.
 *
 * The command classes should be under the namespace specified by [[controllerNamespace]].
 * Their naming should follow the same naming convention as controllers. For example, the `help` command
 * is implemented using the `HelpController` class.
 *
 * To run the console application, enter the following on the command line:
 *
 * ```
 * yii <route> [--param1=value1 --param2 ...]
 * ```
 *
 * where `<route>` refers to a controller route in the form of `ModuleID/ControllerID/ActionID`
 * (e.g. `sitemap/create`), and `param1`, `param2` refers to a set of named parameters that
 * will be used to initialize the controller action (e.g. `--since=0` specifies a `since` parameter
 * whose value is 0 and a corresponding `$since` parameter is passed to the action method).
 *
 * A `help` command is provided by default, which lists available commands and shows their usage.
 * To use this command, simply type:
 *
 * ```
 * yii help
 * ```
 *
 * @property ErrorHandler $errorHandler The error handler application component. This property is read-only.
 * @property Request $request The request component. This property is read-only.
 * @property Response $response The response component. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Application extends \yii\base\Application
{
    /**
     * The option name for specifying the application configuration file path.
     */
    public const OPTION_APPCONFIG = 'appconfig';

    /**
     * @var string the default route of this application. Defaults to 'help',
     * meaning the `help` command.
     */
    public $defaultRoute = 'help';
    /**
     * @var Controller the currently active controller instance
     */
    public $controller;


    /**
     * Loads the configuration.
     * This method will check if the command line option [[OPTION_APPCONFIG]] is specified.
     * If so, the corresponding file will be loaded as the application configuration.
     * Otherwise, the configuration provided as the parameter will be returned back.
     * @param array $config the configuration provided in the constructor.
     * @return array the actual configuration to be used by the application.
     */
    protected function loadConfig(array $config): array
    {
        if (!empty($_SERVER['argv'])) {
            $option = '--' . self::OPTION_APPCONFIG . '=';
            foreach ($_SERVER['argv'] as $param) {
                if (strpos($param, $option) !== false) {
                    $path = substr($param, \strlen($option));
                    if (!empty($path) && is_file($file = $this->app->getAlias($path))) {
                        return require $file;
                    }

                    exit("The configuration file does not exist: $path\n");
                }
            }
        }

        return $config;
    }

    /**
     * Handles the specified request.
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    public function handleRequest(Request $request): Response
    {
        [$route, $params] = $request->resolve();
        $this->requestedRoute = $route;
        $result = $this->runAction($route, $params);
        if ($result instanceof Response) {
            return $result;
        }

        $response = $this->getResponse();
        $response->exitStatus = $result;

        return $response;
    }

    /**
     * Runs a controller action specified by a route.
     * This method parses the specified route and creates the corresponding child module(s), controller and action
     * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
     * If the route is empty, the method will use [[defaultRoute]].
     *
     * For example, to run `public function actionTest($a, $b)` assuming that the controller has options the following
     * code should be used:
     *
     * ```php
     * $this->app->runAction('controller/test', ['option' => 'value', $a, $b]);
     * ```
     *
     * @param string $route the route that specifies the action.
     * @param array $params the parameters to be passed to the action
     * @return int|Response the result of the action. This can be either an exit code or Response object.
     * Exit code 0 means normal, and other values mean abnormal. Exit code of `null` is treaded as `0` as well.
     * @throws Exception if the route is invalid
     */
    public function runAction(string $route, array $params = [])
    {
        try {
            $res = parent::runAction($route, $params);
            return \is_object($res) ? $res : (int) $res;
        } catch (InvalidRouteException $e) {
            throw new UnknownCommandException($route, $this, 0, $e);
        }
    }
}
