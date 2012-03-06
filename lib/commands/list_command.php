<?php
    class ListCommand extends YPICommand {
        public function getDescription() {
            return 'List all packages currently installed on the repository';
        }

        public function help($parameters) {
            echo "ypi list [type [name]]\n".
                 "List installed packages. Can be filtered by type\n".
                 "(application, framework, lib, plugin) and name\n";

            return YPFCommand::RESULT_OK;
        }

        public function run($parameters) {
            if (count($parameters) > 2) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            $type = null;
            $name = null;

            if (isset($parameters[0])) {
                $type = $parameters[0];
                if (substr($type, -1) == 's')
                    $type = substr ($type, 0, -1);

                if (isset($parameters[1]))
                    $name = $parameters[1];
            }

            $packages = YPIPackage::listAll($type, $name);

            if ($type !== null) {
                foreach ($packages as $name => $versions) {
                    printf("%s: %s\n", $name, implode(', ', array_keys($versions)));
                }
            } else
                foreach ($packages as $type => $names) {
                    if (empty($names))
                        continue;

                    printf("%ss:\n", $type);
                    foreach ($names as $name => $versions) {
                        printf("  - %s: %s\n", $name, implode(', ', array_keys($versions)));
                    }
                }

            return self::RESULT_OK;
        }
    }
?>
