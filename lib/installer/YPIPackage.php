<?php
    require 'YPIApplicationPackage.php';
    require 'YPIFrameworkPackage.php';
    require 'YPILibPackage.php';
    require 'YPIPluginPackage.php';
    require 'YPIPackageInstaller.php';

    class YPIPackage
    {
        public static $supportedTypes = array('application', 'lib', 'plugin', 'framework');

        protected $type;
        protected $name;
        protected $version;
        protected $description;
        protected $author;
        protected $dependencies;
        protected $installer;
        protected $packageRoot;

        private $temporary;
        private $remote;

        public static function listAll($type = null, $name = null, $min_version = null) {
            $packages = array();

            if ($type === null)
            {
                foreach (YPIPackage::$supportedTypes as $type)
                    $packages[$type] = self::listAll($type);
            } else
            if (array_search($type, YPIPackage::$supportedTypes) !== false) {

                $typePath = getFileName(PKG_PATH, $type.'s');

                if ($name !== null) {
                    $namePath = getFileName($typePath, $name);
                    if (is_dir($namePath)) {
                        $dir2 = opendir($namePath);

                        if ($dir2)

                        $packages[$name] = array();
                        while ($version = readdir($dir2)) {
                            if ($version[0] == '.')
                                continue;

                            if (($min_version !== null) && (compareVersion($version, $min_version) < 0))
                                continue;

                            $packagePath = getFileName($namePath, $version);
                            $packages[$name][$version] = YPIPackage::load($packagePath);
                        }

                        uksort($packages[$name], 'compareVersion');
                        closedir($dir2);
                    }
                } else {
                    $dir = opendir($typePath);
                    while ($name = readdir($dir)) {
                        if ($name[0] == '.')
                            continue;

                        $namePath = getFileName($typePath, $name);
                        $dir2 = opendir($namePath);

                        $packages[$name] = array();
                        while ($version = readdir($dir2)) {
                            if ($version[0] == '.')
                                continue;

                            $packagePath = getFileName($namePath, $version);
                            $packages[$name][$version] = YPIPackage::load($packagePath);
                        }
                        uksort($packages[$name], 'compareVersion');
                        closedir($dir2);
                    }
                    closedir($dir);
                }
            }

            return $packages;
        }

        public static function get($fromPath) {
            $temporary = true;
            $remote = true;

            if (substr($fromPath, 0, 7) == 'http://')
                $tempPath = self::loadPackageFromHttp($fromPath);
            elseif (substr($fromPath, 0, 8) == 'https://')
                $tempPath = self::loadPackageFromHttp($fromPath);
            elseif (substr($fromPath, 0, 6) == 'ssh://')
                $tempPath = self::loadPackageFromSsh(substr($fromPath, 6));
            elseif (substr($fromPath, 0, 6) == 'git://')
                $tempPath = self::loadPackageFromGit(substr($fromPath, 6));
            elseif (substr($fromPath, 0, 6) == 'ftp://')
                $tempPath = self::loadPackageFromFtp(substr($fromPath, 6));
            elseif (substr($fromPath, 0, 7) == 'file://') {
                $tempPath = substr($fromPath, 7);
                $temporary = false;
                $remote = false;
            } else {
                $tempPath = $fromPath;
                $temporary = false;
                $remote = false;
            }

            if (is_file($tempPath)) {
                $tempDestPath = getTempPath();
                mkdir($tempDestPath);
                $temporary = true;

                system(sprintf('unzip "%s" -d "%s"', $tempPath, $tempDestPath), $result);
                if ($result != 0) {
                    recursive_delete($tempDestPath, true);
                    return false;
                }

                $tempPath = $tempDestPath;
            }

            $package = self::load($tempPath, $temporary, $remote);
            return $package;
        }

        private static function loadPackageFromHttp($srcPath) {
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

        private static function loadPackageFromSsh($srcPath) {
            $destPath = getTempPath();
            $cmd = sprintf('scp -r "%s" "%s"', addslashes($srcPath), addslashes($destPath));
            system($cmd, $value);
            return ($value == 0)? $destPath: false;
        }

        private static function loadPackageFromGit($srcPath) {
            $destPath = getTempPath();
            system(sprintf('git clone "%s" "%s"', addslashes($srcPath), addslashes($destPath)), $value);
            return ($value == 0)? $destPath: false;
        }

        private static function loadPackageFromFtp($srcPath) {
            $destPath = getTempPath();
            //system(sprintf('git clone "%s" "%s"', addslashes($srcPath), addslashes($destPath)), $value);
            return false;//($value == 0)? $destPath: false;
        }

        protected function __construct($packageRoot, $config) {
            $this->packageRoot = $packageRoot;

            $this->type = $config['package']['type'];

            $this->version = normalizeVersion($config['package']['version']);

            $this->name = $config['package']['name'];

            if (isset($config['package']['author']))
                $this->author = $config['package']['author'];

            if (isset($config['package']['description']))
                $this->description = $config['package']['description'];

            if (isset($config['package']['dependencies']))
                $this->dependencies = $config['package']['dependencies'];

            if (isset($config['package']['installer']))
                $this->installer = $config['package']['installer'];
        }

        public function __destruct() {
            if ($this->temporary)
                recursive_delete ($this->packageRoot, true);
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

        public function getPackageRoot() {
            return $this->packageRoot;
        }

        public function isRemote() {
            return $this->remote;
        }

        public function install($skipDependencies = false) {
            if (!$skipDependencies)
                if (!$this->checkDependencies($unmet))
                    return false;

            $path = getFileName(PKG_PATH, $this->type.'s', $this->name, implode('.', $this->version));
            $update = false;

            if (is_dir($path)) {
                rename($path, $path.'_old');
                $update = true;
            }

            if (!mkdir($path, 0777, true)) {
                YPILogger::log('ERROR', sprintf('Can not install %s package. Directory %s could not be created.', $this->name, $path));
                if ($update)
                    rename($path.'_old', $path);
                return false;
            }

            if (!recursive_copy($this->packageRoot, $path)) {
                YPILogger::log('ERROR', sprintf('Can not install %s package. Error while copying files.', $this->name));
                recursive_delete($path, true);
                if ($update)
                    rename($path.'_old', $path);
                return false;
            }

            if ($this->dependencies) {
                foreach ($this->dependencies as $type => $names)
                    foreach ($names as $name => $version)
                    {
                        $path = getFileName(PKG_PATH, $type.'s', $name, $version);
                        $package = YPIPackage::get($path);
                        if (!$package) {
                            YPILogger::log('ERROR', sprintf('Can not install dependency %s', $name));
                            recursive_delete($path, true);
                            if ($update)
                                rename($path.'_old', $path);
                            return false;
                        }

                        if (!$package->configureTo($this, $path)) {
                            YPILogger::log('ERROR', sprintf('Can not install dependency %s', $name));
                            recursive_delete($path, true);
                            if ($update)
                                rename($path.'_old', $path);
                            return false;
                        }
                    }
            }

            if (($installer = $this->getInstallerInstance($path)) !== false) {
                if (!$installer->install($path)) {
                    recursive_delete($path, true);
                    if ($update)
                        rename($path.'_old', $path);
                    return false;
                }
            }

            if ($update) {
                recursive_delete($path.'_old');
                rmdir($path.'_old');
                YPILogger::log('INFO', sprintf('Package %s updated on %s', $this->name, $path));
            } else
                YPILogger::log('INFO', sprintf('Package %s installed on %s', $this->name, $path));

            return $path;
        }

        public function uninstall() {
            if (($installer = $this->getInstallerInstance()) !== false) {
                if (!$installer->uninstall())
                    return false;
            }

            if (!recursive_delete($this->packageRoot)) {
                YPILogger::log('ERROR', sprintf('Can not uninstall %s package. Error while deleting files.', $this->name));
                return false;
            }

            if (!rmdir($this->packageRoot)) {
                YPILogger::log('ERROR', sprintf('Can not uninstall %s package. Error while deleting package root directory.', $this->name));
                return false;
            }

            YPILogger::log('INFO', sprintf('Package %s uninstalled', $this->name));
            return true;
        }

        public function checkDependencies(&$unmet) {
            if (!is_array($this->dependencies))
                return true;

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

        public function configureTo($package, $packagePath) {
            if (($installer = $this->getInstallerInstance()) !== false)
                return $installer->configureTo($package, $packagePath);

            return true;
        }

        public function versionAdd($version) {
            $version = normalizeVersion($version);
            $this->version[0] += $version[0];
            $this->version[1] += $version[1];
            $this->version[2] += $version[2];

            return $this->versionTo(implode('.', $this->version));
        }

        public function versionDelete($version) {
            $version = normalizeVersion($version);
            $this->version[0] -= $version[0];
            $this->version[1] -= $version[1];
            $this->version[2] -= $version[2];

            if (($this->version[0]<0) || ($this->version[1]<0) || ($this->version[2]<0))
                return false;

            return $this->versionTo(implode('.', $this->version));
        }

        public function versionTo($version) {
            $this->version = normalizeVersion($version);

            $configFileName = getFileName($this->packageRoot, 'config.yml');
            if (!is_file($configFileName))
            {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. config.yml missing", $packageRoot));
                return false;
            }

            $yaml = new sfYamlParser();
            $config = null;
            try {
                $config = $yaml->parse(file_get_contents($configFileName));
            }
            catch (InvalidArgumentException $e) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                return false;
            }
            require LIB_PATH.'/sfYaml/sfYamlDumper.php';

            $config['package']['version'] = implode('.', $this->version);
            $yaml = new sfYamlDumper;
            return (file_put_contents($configFileName, $yaml->dump($config, 5)) !== false);
        }

        protected function getInstallerInstance($packageRoot = null) {
            if ($this->installer) {
                if ($packageRoot === null)
                    $packageRoot = $this->packageRoot;

                $installerPath = getFileName($this->packageRoot, $this->installer);
                if (is_file($installerPath)) {
                    require_once $installerPath;
                    $className = camelize($this->name.'_installer');

                    if (class_exists($className))
                        return new $className($this);
                }
            }

            return false;
        }

        private static function load($packageRoot, $temporary, $remote) {
            $configFileName = getFileName($packageRoot, 'config.yml');
            if (!is_file($configFileName))
            {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. config.yml missing", $packageRoot));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            $yaml = new sfYamlParser();
            $config = null;
            try {
                $config = $yaml->parse(file_get_contents($configFileName));
            }
            catch (InvalidArgumentException $e) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            if (!isset($config['package'])) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. config.yml corrupted", $packageRoot));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            if (!isset($config['package']['type'])) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. Type of package not included in config.yml", $packageRoot));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            if (array_search($config['package']['type'], self::$supportedTypes) === false) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. Type of package not supported: %s", $packageRoot, $this->type));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            if (!isset($config['package']['version'])) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. Version of package not included in config.yml", $packageRoot));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            if (!isset($config['package']['name'])) {
                YPILogger::log('ERROR', sprintf("%s does not point to a valid package. Name of package not included in config.yml", $packageRoot));
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }

            try {
                $type = $config['package']['type'];
                $class = 'YPI'.strtoupper($type[0]).substr($type, 1).'Package';
                $package = new $class($packageRoot, $config);
                $package->temporary = $temporary;
                $package->remote = $remote;
                return $package;
            } catch (Exception $e) {
                if ($temporary)
                    recursive_delete ($packageRoot, true);
                return false;
            }
        }
    }
?>
