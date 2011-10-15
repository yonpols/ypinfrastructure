<?php
    abstract class PackageInstaller {
        protected $package;

        public function __construct($package) {
            $this->package = $package;
        }
        
        public abstract function install($packagePath);
        public abstract function uninstall();
        public abstract function configureTo($package, $packagePath);
    }
?>
