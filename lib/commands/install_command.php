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
                $fromPath = $parameters[0];

                if (substr($fromPath, 0, 7) == 'http://')
                    $tempPath = $this->loadPackageFromHttp($fromPath);
                elseif (substr($fromPath, 0, 8) == 'https://')
                    $tempPath = $this->loadPackageFromHttp($fromPath);
                elseif (substr($fromPath, 0, 6) == 'ssh://')
                    $tempPath = $this->loadPackageFromSsh(substr($fromPath, 6));
                elseif (substr($fromPath, 0, 6) == 'git://')
                    $tempPath = $this->loadPackageFromGit(substr($fromPath, 6));
                elseif (substr($fromPath, 0, 6) == 'ftp://')
                    $tempPath = $this->loadPackageFromFtp(substr($fromPath, 6));
                elseif (substr($fromPath, 0, 7) == 'file://')
                    $tempPath = substr($fromPath, 7);
                elseif (is_file($fromPath))
                    $tempPath = $fromPath;
                else {
                    $this->help ($parameters);
                    $this->exitNow (self::RESULT_INVALID_PARAMETERS);
                }
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
            if (!$package) {
                if (substr($tempPath, 0, strlen(TMP_PATH)) == TMP_PATH)
                    recursive_delete($tempPath, true);

                $this->exitNow(self::RESULT_BAD_ENVIRONMENT, "Package not valid");
            }

            //Check if package dependencies are all met
            if (!$package->checkDependencies($unmet)) {
                $this->error("Package needs the following packages:");

                foreach($unmet as $dep)
                    $this->error("\t%s", $dep);

                if (substr($tempPath, 0, strlen(TMP_PATH)) == TMP_PATH) {
                    recursive_delete($tempPath, true);
                }

                $this->exitNow(self::RESULT_BAD_DEPENDENCIES);
            }

            if (!$package->install(true)) {
                $this->error("Couldn't install package.");

                if (substr($tempPath, 0, strlen(TMP_PATH)) == TMP_PATH) {
                    recursive_delete($tempPath, true);
                }

                $this->exitNow(self::RESULT_FILES_ERROR);
            }

            if (substr($tempPath, 0, strlen(TMP_PATH)) == TMP_PATH)
                recursive_delete($tempPath, true);

            return self::RESULT_OK;
        }

        protected function loadPackageFromHttp($srcPath) {
            $destPath = getTempPath();

            $content = file_get_contents($srcPath);
            if (!$content)
                return false;

            $result = file_put_contents($destPath, $content);

            if ($result === false)
                return false;
            else
                return $destPath;
        }

        protected function loadPackageFromSsh($srcPath) {
            $destPath = getTempPath();
            $cmd = sprintf('scp -r "%s" "%s"', addslashes($srcPath), addslashes($destPath));
            system($cmd, $value);
            return ($value == 0)? $destPath: false;
        }

        protected function loadPackageFromGit($srcPath) {
            $destPath = getTempPath();
            system(sprintf('git clone "%s" "%s"', addslashes($srcPath), addslashes($destPath)), $value);
            return ($value == 0)? $destPath: false;
        }

        protected function loadPackageFromFtp($srcPath) {
            $destPath = getTempPath();
            //system(sprintf('git clone "%s" "%s"', addslashes($srcPath), addslashes($destPath)), $value);
            return false;//($value == 0)? $destPath: false;
        }
    }
?>
