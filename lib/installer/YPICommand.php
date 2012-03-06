<?php
    /**
     * Abstract class to implement command line options on ypi cli command.
     */
    abstract class YPICommand {
        const RESULT_OK = 0;
        const RESULT_INVALID_PARAMETERS = 1;
        const RESULT_FILESYSTEM_ERROR = 2;
        const RESULT_FILES_ERROR = 3;
        const RESULT_COMMAND_NOT_FOUND = 4;
        const RESULT_BAD_ENVIRONMENT = 5;
        const RESULT_BAD_DEPENDENCIES = 6;

        /**
         * Get a command instance for the command name passed
         * @param string $commandName
         * @return YPICommand command instance or false
         */
        public static function get($commandName) {
            $commandName = str_replace(':', '_', $commandName).'_command';

            $classFileName = getFileName(LIB_PATH, 'commands', $commandName.'.php');
            $className = camelize($commandName);

            if (is_readable($classFileName)) {
                require_once $classFileName;

                if (class_exists($className, false))
                    return new $className;
            }

            return false;
        }

        /**
         * Method that a command class must implement. This method
         * is called when the command is run. It must return YPICommand::RESULT_OK
         * if finished succesfully.
         *
         * @param   array   $parameters Is an array with parameters passed to the command. This array does not contain parameters passed to ypf
         * @return  int     Must return an integer to be passed to the shell
         */
        public abstract function run($parameters);

        /**
         * Method that a command class must implement. This method
         * is called when the help command is called. It's intended to
         * print some help about the command on the screen.
         *
         * @param   array   $parameters Is an array with parameters passed to the command. This array does not contain parameters passed to ypf
         */
        public abstract function help($parameters);

        /**
         * Method that a command class must implement. This method
         * must return a brief description of the command that will be
         * printed when the command list is printed on scren.
         *
         * @return  string
         */
        public abstract function getDescription();

        /**
         * Gets all available commands for YPI
         * @return array list of all command names
         */
        protected function getAllCommands() {
            $commands = glob(getFileName(LIB_PATH, 'commands', '*_command.php'));
            $command_names = array();

            if (is_array($commands))
                $command_names = array_map (function($path) {
                    return str_replace('_', ':', substr(basename($path), 0, -12));
                }, $commands);

            return $command_names;
        }

        /**
         * Terminate command execution with an exit code and a text.
         * @param integer $code
         * @param string $text
         */
        protected function exitNow($code = YPICommand::RESULT_OK, $text = '') {
            if ($text != '')
                $this->error($text);
            exit($code);
        }

        /**
         * Output an error message to STDERR.
         * @param string $text
         */
        protected function error($text) {
            fprintf(STDERR, $text."\n");
        }

        /**
         * Read configurations of a YAML file
         * @param string $configFileName
         * @return mixed
         */
        protected function getConfig($configFileName) {
            $yaml = new sfYamlParser();
            try
            {
                $config = $yaml->parse(file_get_contents($configFileName));
                return $config;
            }
            catch (InvalidArgumentException $e) {
                $this->exitNow(self::RESULT_FILES_ERROR, sprintf('%s config file corrupted', $configFileName));
            }
        }

        /**
         * Write configurations to a YAML file
         * @param string $configFileName
         * @param mixed $config
         */
        protected function setConfig($configFileName, $config) {
            $yaml = new sfYamlDumper();
            if (!file_put_contents($configFileName, $yaml->dump($config, 4)))
                $this->exitNow(self::RESULT_FILES_ERROR, sprintf('can\'t write %s config file', $configFileName));
        }
    }
?>
