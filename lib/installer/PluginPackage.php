<?php
    class PluginPackage extends Package
    {
        public function configureTo($package, $packagePath) {
            if (!($package instanceof ApplicationPackage))
            {
                Logger::log('ERROR', sprintf('Could not configure %s to %s because it is not an application', $this->name, $package->getName()));
                return false;
            }

            $pluginsPath = getFileName($packagePath, 'plugins');

            if (is_dir($pluginsPath)) {
                $pluginPath = getFileName($pluginsPath, $this->name);

                if (!@symlink($this->packageRoot, $pluginPath)) {
                    Logger::log('ERROR', sprintf("Could not create symlink %s -> %s", $pluginPath, $this->packageRoot));
                    return false;
                }
            }

            if (parent::configureTo($package, $packagePath)) {
                Logger::log('INFO', sprintf("%s-%s configured to package %s", $this->name, implode('.', $this->version), $package->getName()));
                return true;
            }

            return false;
        }
    }
?>
