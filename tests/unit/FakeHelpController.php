<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\console\tests\unit;

use yii\console\controllers\HelpController;

class FakeHelpController extends HelpController
{
    private static $_actionIndexLastCallParams;

    public function actionIndex(string $command = null): int
    {
        self::$_actionIndexLastCallParams = \func_get_args();
    }

    public static function getActionIndexLastCallParams()
    {
        $params = self::$_actionIndexLastCallParams;
        self::$_actionIndexLastCallParams = null;

        return $params;
    }
}
