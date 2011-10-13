<?php

    class Package
    {
        private $type;
        private $name;
        private $version;
        private $description;
        private $author;
        private $dependencies;
        private $packageRoot;

        public function __construct($packageRoot)
        {
            $this->packageRoot = $packageRoot;

            $configFileName = realpath($packageRoot.DIRECTORY_SEPARATOR.'config.yml');
            if (!is_file($configFileName))
            {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. config.yml missing", $packageRoot));
                return;
            }

            $yaml = new sfYamlParser();
            try {
                $config = $yaml->parse(file_get_contents($configFileName));
            }
            catch (InvalidArgumentException $e) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                return;
            }

            if (!isset($config['package'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                return;
            }

            if (!isset($config['package']['type'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Type of package not included in config.yml", $packageRoot));
                return;
            }
            $this->type = $config['package']['type'];

            if (array_search($this->type, array('application', 'lib', 'plugin', 'framework')) === false) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Type of package not supported: %s", $packageRoot, $this->type));
                return;
            }

            if (!isset($config['package']['version'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Version of package not included in config.yml", $packageRoot));
                return;
            }
            $this->version = explode('.', $config['package']['version']);
            while (count($this->version) < 3)
                $this->version[] = 0;

            if (!isset($config['package']['name'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Name of package not included in config.yml", $packageRoot));
                return;
            }
            $this->name = $config['package']['name'];

            if (isset($config['package']['author']))
                $this->author = $config['package']['author'];

            if (isset($config['package']['description']))
                $this->description = $config['package']['description'];

            if (isset($config['package']['dependencies']))
                $this->description = $config['package']['dependencies'];
        }

        public function getType() {
            return $this->type;
        }

        public function getName() {
            return $this->name;
        }

        public function getVersion() {
            return $this->version;
        }

        public function getDescription() {
            return $this->description;
        }

        public function getAuthor() {
            return $this->author;
        }

        public function getDependencies() {
            return $this->dependencies;
        }

        public function install() {
            $path = PKG_PATH.DIRECTORY_SEPARATOR.$this->type.'s'.DIRECTORY_SEPARATOR.$this->name.DIRECTORY_SEPARATOR.implode('.', $this->version);
            if (is_dir($path)) {
                Logger::log('ERROR', sprintf('Can not install %s package because path already exists: %s', $this->name, $path));
                return false;
            }

            if (!mkdir($path, 0777, true)) {
                Logger::log('ERROR', sprintf('Can not install %s package. Directory %s could not be created.', $this->name, $path));
                return false;
            }

            if (!$this->copy($this->packageRoot, $path)) {
                Logger::log('ERROR', sprintf('Can not install %s package. Error while copying files.', $this->name));
                @`rm -r $path`;
                return false;
            }

            Logger::log('INFO', sprintf('Package %s installed on %s', $this->name, $path));
            return true;
        }

        public function uninstall() {
            if (!$this->delete($this->packageRoot)) {
                Logger::log('ERROR', sprintf('Can not uninstall %s package. Error while deleting files.', $this->name));
                return false;
            }

            if (!rmdir($this->packageRoot)) {
                Logger::log('ERROR', sprintf('Can not uninstall %s package. Error while deleting package root directory.', $this->name));
                return false;
            }

            Logger::log('INFO', sprintf('Package %s uninstalled', $this->name));
            return true;
        }

        private function copy($from, $to) {
            $dir = opendir($from);
            $result = ($dir !== false);
            while ($result && ($file = readdir($dir))) {
                $pathFrom = $from . DIRECTORY_SEPARATOR . $file;
                $pathTo = $to . DIRECTORY_SEPARATOR . $file;

                if (($file == '.') || ($file == '..'))
                    continue;

                if (is_dir($pathFrom)) {
                    $result = $result && (mkdir($pathTo));
                    $result = $result && $this->copy($pathFrom, $pathTo);
                } else {
                    $result = $result && (copy($pathFrom, $pathTo));
                }
            }

            return $result;
        }

        private function delete($from) {
            $dir = opendir($from);
            $result = ($dir !== false);
            while ($result && ($file = readdir($dir))) {
                $pathFrom = $from . DIRECTORY_SEPARATOR . $file;

                if (($file == '.') || ($file == '..'))
                    continue;

                if (is_dir($pathFrom)) {
                    $result = $result && $this->delete($pathFrom);
                    $result = $result && (rmdir($pathFrom));
                } else
                    $result = $result && (unlink($pathFrom));
            }

            return $result;
        }
    }

?>
