<?php
    class HelpCommand extends YPICommand {
        public function help($parameters) {
            echo "ypi help [command-name]\n".
                 "Shows help about a command. If command-name is ommited, shows a list of all available commands\n";

            return YPFCommand::RESULT_OK;
        }

        public function run($parameters) {
            if (empty ($parameters)) {
                echo "YPInfrastructure: ypi command-name [options]\n\n";
                echo "List of available commands:\n";

                $this->showAllCommands();
            }
            else {
                $commandName = array_shift($parameters);
                $command = YPICommand::get($commandName);

                if ($command === false)
                    $this->exitNow (YPICommand::RESULT_COMMAND_NOT_FOUND, sprintf('command %s not found', $commandName));

                return $command->help($parameters);
            }
        }

        public function getDescription() {
            return 'shows help about a command or lists all available commands';
        }

        protected function showAllCommands($parent = '') {
            $commands = $this->getAllCommands();

            if ($commands) {
                uasort($commands, function($a, $b) {
                    $a = basename($a);
                    $b = basename($b);

                    $canta = preg_match_all('/:/', $a, $t);
                    $cantb = preg_match_all('/:/', $b, $t);

                    if ($canta < $cantb)
                        return -1;
                    elseif ($cantb < $canta)
                        return 1;
                    else
                        return strcasecmp($a, $b);
                });

                foreach($commands as $commandName) {
                    $command = YPICommand::get($commandName);

                    if ($command)
                        printf("    %-40s%s\n", $commandName, $command->getDescription());
                }
            }
        }
    }
?>
