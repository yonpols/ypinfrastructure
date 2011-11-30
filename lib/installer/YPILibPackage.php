<?php
    class YPILibPackage extends YPIPackage
    {
        public function configureTo($package, $packagePath) {
            $libsPath = getFileName($packagePath, 'libs');

            if (is_dir($libsPath)) {
                $libPath = getFileName($libsPath, $this->name);

                if (!@symlink($this->packageRoot, $libPath)) {
                    YPILogger::log('ERROR', sprintf("Could not create symlink %s -> %s", $libPath, $this->packageRoot));
                    return false;
                }
            }

            if (parent::configureTo($package, $packagePath)) {
                YPILogger::log('INFO', sprintf("%s-%s configured to package %s", $this->name, implode('.', $this->version), $package->getName()));
                return true;
            }

            return false;
        }
    }
?>
