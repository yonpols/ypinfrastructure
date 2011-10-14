<?php
    class ApplicationPackage extends Package
    {
        private $deployMode;
        private $deployUrl;
        private $deployInset;

        protected function __construct($packageRoot, $config) {
            parent::__construct($packageRoot, $config);
            if (isset($config['package']['deploy_mode'])) {
                $this->deployMode = $config['package']['deploy_mode'];
                $this->deployUrl = $config['application'][$this->deployMode]['url'];
            }
        }

        public function install() {
            $path = parent::install();
            if ($this->deployMode) {
                $url = parse_url($this->deployUrl);
                $contents = file($path.DIRECTORY_SEPARATOR.'www/.htaccess');

                if (!isset ($url['path']))
                    $url['path'] = '';

                $fd = fopen($path.DIRECTORY_SEPARATOR.'www/.htaccess', 'w');
                foreach ($contents as $line => $text)
                    if (strpos($text, 'RewriteBase') !== false)
                        fputs ($fd, sprintf("  RewriteBase %s/\n", $url['path']));
                    else
                        fputs ($fd, $text."\n");

                fclose($fd);
            }

        }
    }
?>
