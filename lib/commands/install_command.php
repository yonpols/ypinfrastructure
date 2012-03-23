<?php
    class InstallCommand extends YPICommand {
        const RESULT_PACKAGE_NOT_FOUND = 9;

        public function getDescription() {
            return 'Install a package on the current ypi repository';
        }

        public function help($parameters) {
            echo "ypi install <package-path> | (<package-type> <package-name> [<package-version>])\n".
                 "Install a package on the current ypi repository\n".
                 "Package path may be a file system path, a git repository (git:// prefix),\n".
                 "a ssh path (ssh:// prefix), a ftp path (ftp:// prefix), a http resource \n".
                 "(http:// or https:// prefix)\n";

            return YPICommand::RESULT_OK;
        }

        public function run($parameters) {
            if (empty($parameters)) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            if (count($parameters) > 3) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            //Get package from different sources
            if (count($parameters) == 1) {
                $tempPath = $parameters[0];
            } else {
                $type = $parameters[0];
                $name = $parameters[1];
                if (isset($parameters[2]))
                    $version = $parameters[2];

                if (substr($type, -1) != 's')
                    $type .= 's';

                $config = $this->getConfig(getFileName(DB_PATH, 'ypidb.yml'));

                if (!isset ($config[$type]) || !isset ($config[$type][$name]))
                    $this->exitNow (self::RESULT_PACKAGE_NOT_FOUND, 'Package not found');

                $packages = $config[$type][$name];

                if (empty($packages))
                    $this->exitNow (self::RESULT_PACKAGE_NOT_FOUND, 'Package not found');

                uksort($packages, 'compareVersion');

                if (isset($parameters[2]))
                    $version = $parameters[2];
                else
                    $version = array_shift (array_keys ($packages));

                if (!isset ($packages[$version]))
                    $this->exitNow (self::RESULT_PACKAGE_NOT_FOUND, 'Package version not found');

                printf("Loading package: %s\n", $packages[$version]);
                return $this->run(array($packages[$version]));
            }

            //Load package
            $package = YPIPackage::get($tempPath);
            if (!$package)
                $this->exitNow(self::RESULT_BAD_ENVIRONMENT, "Package not valid");

            //Check if package dependencies are all met
            if (!$package->checkDependencies($unmet)) {
                $this->error("Package needs the following packages:");

                foreach($unmet as $dep)
                    $this->error("\t%s", $dep);

                $this->exitNow(self::RESULT_BAD_DEPENDENCIES);
            }

            if (!$package->install(true))
                $this->exitNow(self::RESULT_FILES_ERROR, "Couldn't install package.");

            return self::RESULT_OK;
        }
    }
?>
