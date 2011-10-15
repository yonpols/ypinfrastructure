<?php
    class ApplicationPackage extends Package
    {
        protected $deployMode;
        protected $deployUrl;
        protected $deployInset;

        protected function __construct($packageRoot, $config) {
            parent::__construct($packageRoot, $config);
            if (isset($config['package']['deploy_mode'])) {
                $this->deployMode = $config['package']['deploy_mode'];
                $this->deployUrl = $config['application'][$this->deployMode]['url'];
            }
        }

        public function getDeployMode() {
            return $this->deployMode;
        }

        public function getDeployUrl() {
            return $this->deployUrl;
        }

        public function getDeployInset() {
            return $this->deployInset;
        }
    }
?>
