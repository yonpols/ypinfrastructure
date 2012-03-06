<?php
    class InfoCommand extends YPICommand {
        public function getDescription() {
            return 'Print information about a package on the current working directory';
        }

        public function help($parameters) {
            echo "ypi info\n".
                 "Print information about a package on the current working directory\n";

            return YPFCommand::RESULT_OK;
        }

        public function run($parameters) {
            if (!empty($parameters)) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            $package = YPIPackage::get(realpath('.'));
            if (!$package)
                $this->exitNow (self::RESULT_BAD_ENVIRONMENT, "Package not valid");

            printf("Package: %s\n", $package->getName());
            if ($package->getDescription())
                printf("%s\n", $package->getDescription());

            printf("\ttype: %s\n", $package->getType());
            printf("\tversion: %s\n", implode('.', $package->getVersion()));
            printf("\tauthor: %s\n", $package->getAuthor());

            return self::RESULT_OK;
        }
    }
?>
