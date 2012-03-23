<?php
    class UploadCommand extends YPICommand {
        const RESULT_EXISTENT_PACKAGE = 10;

        public function getDescription() {
            return 'Upload package to YPInfrastructure repository';
        }

        public function help($parameters) {
            echo "ypi upload <url-path>\n".
                 "Upload package to YPInfrastructure repository\n".
                 "You must pass an url path like the one that you can use on ypi install.\n".
                 "It only supports internet paths.\n";

            return YPICommand::RESULT_OK;
        }

        public function run($parameters) {
            if (count($parameters) != 1) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            $update = YPICommand::get('update');
            if (!$update)
                $this->exitNow (self::RESULT_FILES_ERROR, "Couldn't update db");

            if($update->run() != self::RESULT_OK)
                $this->exitNow (self::RESULT_FILES_ERROR, "Couldn't update db");

            echo "Loading package\n";
            $package = YPIPackage::get($parameters[0]);

            if (!$package)
                $this->exitNow (self::RESULT_BAD_ENVIRONMENT, "Package not valid");

            if (!$package->isRemote())
                $this->exitNow (self::RESULT_BAD_ENVIRONMENT, "You can't use a local package path.");

            $config = $this->getConfig(getFileName(DB_PATH, 'ypidb.yml'));

            $type = $package->getType().'s';
            $name = $package->getName();
            $version = implode('.', $package->getVersion());

            if (!isset($config[$type]))
                $config[$type] = array();

            if (!isset($config[$type][$name]))
                $config[$type][$name] = array();

            if (isset($config[$type][$name][$version]))
                $this->exitNow (self::RESULT_EXISTENT_PACKAGE, 'Package version exists on repository');

            $config[$type][$name][$version] = $parameters[0];

            $config['last_modification'] = time();
            $this->setConfig(getFileName(DB_PATH, 'ypidb.yml'), $config);

            echo "Uploading package information\n";
            $cwd = getcwd();
            chdir(DB_PATH);
            system('git add . && git commit -m "Package installation" && git push git@github.com:2047698.git', $result);
            chdir($cwd);


            return self::RESULT_OK;
        }
    }
?>
