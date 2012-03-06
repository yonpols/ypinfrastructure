<?php
    class UninstallCommand extends YPICommand {
        public function getDescription() {
            return 'Uninstall a package from the current ypi repository';
        }

        public function help($parameters) {
            echo "ypi uninstall <package-path>\n".
                 "Remove a package from the current ypi repository\n".
                 "Currently YPI does not check dependencies, so be careful when\n".
                 "uninstalling packages";

            return YPICommand::RESULT_OK;
        }

        public function run($parameters) {
            if (count($parameters) != 1) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            /* TODO
             * Determine if a package has dependants and if that is the case,
             * stop uninstallation.
             */

            $package = YPIPackage::get($parameters[0]);
            if (!$package)
                $this->exitNow (self::RESULT_BAD_DEPENDENCIES, "Package not valid");

            if (!$package->uninstall())
                $this->exitNow (self::RESULT_FILES_ERROR, "Couldn't uninstall package.");

            return self::RESULT_OK;
        }
    }
?>
