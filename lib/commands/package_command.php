<?php
    class PackageCommand extends YPICommand {
        public function getDescription() {
            return 'Create a single file from the package on the current working directory';
        }

        public function help($parameters) {
            echo "ypi package [file-name | path]\n".
                 "Create a single file from the package on the current working directory\n".
                 "You can set a file name, a path where the file will be stored or none\n".
                 "In the last two cases, filename will be the package name and version\n";

            return YPFCommand::RESULT_OK;
        }

        public function run($parameters) {
            if (count($parameters) > 1) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            $package = YPIPackage::get(realpath('.'));
            if (!$package)
                $this->exitNow (self::RESULT_BAD_ENVIRONMENT, "Package not valid");

            if (isset($parameters[0])) {
                if (is_dir($parameters[0]))
                    $packageFile = getFileName($parameters[0], $package->getName().'-'.implode('.', $package->getVersion()).'.ypp');
                else
                    $packageFile = $parameters[0];
            } else
                $packageFile = getFileName(realpath('.'), $package->getName().'-'.implode('.', $package->getVersion()).'.ypp');

            $tempFile = getTempPath().'.zip';
            system(sprintf('zip -r9 "%s" . -x ".*" -x "dist/"', $tempFile), $result);
            if (($result != 0) || !rename ($tempFile, $packageFile))
                $this->exitNow(self::RESULT_FILES_ERROR, "Couldn't create package.");

            return self::RESULT_OK;
        }
    }
?>
