<?php
    require 'ApplicationPackage.php';
    require 'FrameworkPackage.php';
    require 'LibPackage.php';
    require 'PluginPackage.php';

    class Package
    {
        public static $supportedTypes = array('application', 'lib', 'plugin', 'framework');

        private $type;
        private $name;
        private $version;
        private $description;
        private $author;
        private $dependencies;
        private $packageRoot;

        public static function listAll($type = null, $name = null, $min_version = null)
        {
            $packages = array();

            if ($type === null)
            {
                foreach (Package::$supportedTypes as $type)
                    $packages[$type] = self::listAll($type);
            } else
            if (array_search($type, Package::$supportedTypes) !== false) {
                $typePath = PKG_PATH.DIRECTORY_SEPARATOR.$type.'s';

                if ($name !== null) {
                    if (is_dir($typePath.DIRECTORY_SEPARATOR.$name)) {
                        $namePath = $typePath.DIRECTORY_SEPARATOR.$name;
                        $dir2 = opendir($namePath);

                        if ($dir2)

                        $packages[$name] = array();
                        while ($version = readdir($dir2)) {
                            if ($version[0] == '.')
                                continue;

                            if (($min_version !== null) && (compareVersion($version, $min_version) < 0))
                                continue;

                            $packagePath = $namePath.DIRECTORY_SEPARATOR.$version;
                            $packages[$name][$version] = Package::load($packagePath);
                        }
                        uksort($packages[$name], 'compareVersion');
                    }
                } else {
                    $dir = opendir($typePath);
                    while ($name = readdir($dir)) {
                        if ($name[0] == '.')
                            continue;

                        $namePath = $typePath.DIRECTORY_SEPARATOR.$name;
                        $dir2 = opendir($namePath);

                        $packages[$name] = array();
                        while ($version = readdir($dir2)) {
                            if ($version[0] == '.')
                                continue;

                            $packagePath = $namePath.DIRECTORY_SEPARATOR.$version;
                            $packages[$name][$version] = Package::load($packagePath);
                        }
                        uksort($packages[$name], 'compareVersion');
                    }
                }
            }

            return $packages;
        }

        public static function get($fromPath) {
            if (is_file($fromPath)) {
                $tempPath = getTempPath();
                mkdir($tempPath);

                $zip = new ZipArchive;
                $res = $zip->open($fromPath);
                if ($res === true) {
                    $zip->extractTo($tempPath);
                    $zip->close();
                } else
                    return false;

                $fromPath = $tempPath;
            }

            $package = self::load($fromPath);
            return $package;
        }

        protected function __construct($packageRoot, $config)
        {
            $this->packageRoot = $packageRoot;

            $this->type = $config['package']['type'];

            $this->version = normalizeVersion($config['package']['version']);

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

        public function install($skipDependencies = false) {
            if (!$skipDependencies)
                if (!$this->checkDependencies($unmet))
                    return false;

            $path = PKG_PATH.DIRECTORY_SEPARATOR.$this->type.'s'.DIRECTORY_SEPARATOR.$this->name.DIRECTORY_SEPARATOR.implode('.', $this->version);
            $update = false;

            if (is_dir($path)) {
                rename($path, $path.'_old');
                $update = true;
            }

            if (!mkdir($path, 0777, true)) {
                Logger::log('ERROR', sprintf('Can not install %s package. Directory %s could not be created.', $this->name, $path));
                if ($update)
                    rename($path.'_old', $path);
                return false;
            }

            if (!recursive_copy($this->packageRoot, $path)) {
                Logger::log('ERROR', sprintf('Can not install %s package. Error while copying files.', $this->name));
                @`rm -r $path`;
                if ($update)
                    rename($path.'_old', $path);
                return false;
            }

            if ($update) {
                recursive_delete($path.'_old');
                rmdir($path.'_old');
                Logger::log('INFO', sprintf('Package %s updated on %s', $this->name, $path));
            } else
                Logger::log('INFO', sprintf('Package %s installed on %s', $this->name, $path));

            return $path;
        }

        public function uninstall() {
            if (!recursive_delete($this->packageRoot)) {
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

        public function checkDependencies(&$unmet) {
            if (!is_array($this->dependencies))
                return false;

            $unmet = array();
            foreach ($this->dependencies as $type => $names)
                foreach ($names as $name => $version) {
                    if (substr($version, 0, 2) == '>=') {
                        $mod = '>=';
                        $version = normalizeVersion(trim(substr($version, 2)), true);
                    }
                    elseif (substr($version, 0, 1) == '>') {
                        $mod = '>';
                        $version = normalizeVersion(trim(substr($version, 1)));
                        $version[2]++;
                        $version = implode('.', $version);
                    } else {
                        $mod = '=';
                        $version = normalizeVersion($version, true);
                    }

                    $installed = self::listAll($type, $name, $version);

                    if (empty($installed[$name])) {
                        $unmet[] = sprintf('%s: "%s" %s%s', $type, $name, $mod, $version);
                        continue;
                    }

                    if (($mod == '=') && !isset($installed[$name][$version])) {
                        $unmet[] = sprintf('%s: "%s" %s%s', $type, $name, $mod, $version);
                        continue;
                    }
                }
            return (empty($unmet));
        }

        private static function load($packageRoot) {
            $configFileName = realpath($packageRoot.DIRECTORY_SEPARATOR.'config.yml');
            if (!is_file($configFileName))
            {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. config.yml missing", $packageRoot));
                return false;
            }

            $yaml = new sfYamlParser();
            $config = null;
            try {
                $config = $yaml->parse(file_get_contents($configFileName));
            }
            catch (InvalidArgumentException $e) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                return false;
            }

            if (!isset($config['package'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                return false;
            }

            if (!isset($config['package']['type'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Type of package not included in config.yml", $packageRoot));
                return false;
            }

            if (array_search($config['package']['type'], self::$supportedTypes) === false) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Type of package not supported: %s", $packageRoot, $this->type));
                return false;
            }

            if (!isset($config['package']['version'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Version of package not included in config.yml", $packageRoot));
                return false;
            }

            if (!isset($config['package']['name'])) {
                Logger::log('ERROR', sprintf("%s does not point to a valid package. Name of package not included in config.yml", $packageRoot));
                return false;
            }

            try {
                $type = $config['package']['type'];
                $class = strtoupper($type[0]).substr($type, 1).'Package';
                $package = new $class($packageRoot, $config);
                return $package;
            } catch (Exception $e) {
                return false;
            }
        }
    }
?>
