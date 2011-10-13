<?php
    define('BASE_PATH', dirname(__FILE__));
    define('LIB_PATH', realpath(BASE_PATH.DIRECTORY_SEPARATOR.'lib'));
    define('LOG_PATH', realpath(BASE_PATH.DIRECTORY_SEPARATOR.'log'));
    define('PKG_PATH', realpath(BASE_PATH.DIRECTORY_SEPARATOR.'pkg'));
    define('TMP_PATH', realpath(BASE_PATH.DIRECTORY_SEPARATOR.'tmp'));

    require LIB_PATH.'/sfYaml/sfYamlParser.php';
    require LIB_PATH.'/installer/Logger.php';
    require LIB_PATH.'/installer/Package.php';

    Logger::initialize();
    $p = new Package('pkg/plugins/captcha/0.0.1');
    var_dump($p->uninstall());
?>
