<?php
    class IterateCommand extends YPICommand {
        const RESULT_ERROR_ITERATING = 7;

        public function getDescription() {
            return 'Change the version of a package';
        }

        public function help($parameters) {
            echo "ypi iterate [--to <version>|--add <version-unit>|--delete <version-unit>]\n".
                 "Changes version of package. If no argument is passed, it increments\n".
                 "build number\n".
                 "      --to <version>            Changes version to version specified\n".
                 "      --add <version-unit>      Adds the amount specified by version-unit\n".
                 "      --delete <version-unit>   Substracts the amount specified by version-unit\n".
                 "      version-unit = number(v,s,b) v:version, s:subversion, b:build number\n".
                 "      ej: 1v\n";

            return YPFCommand::RESULT_OK;
        }

        public function run($parameters) {
            if (count($parameters) > 1)
                $this->exitNow ($this->help ($parameters));

            $package = YPIPackage::get(realpath('.'));
            if (!$package)
                $this->exitNow (self::RESULT_BAD_ENVIRONMENT, "Package not valid");

            if (!isset($parameters[0]))
                $result = $package->versionAdd('0.0.1');
            elseif (!isset($parameters[1])) {
                $this->help($parameters);
                $this->exitNow(self::RESULT_INVALID_PARAMETERS);
            } elseif ($parameters[0] == '--to') {
                if (!preg_match('/[0-9]+(\\.[0-9]+(\\.[0-9]+)?)?/', $parameters[1]))
                    $this->exitNow(self::RESULT_INVALID_PARAMETERS, "Bad version number");
                $result = $package->versionTo($parameters[1]);
            } elseif ($parameters[0] == '--add') {
                if (!preg_match('/([0-9]+)(v|s|b)/', $parameters[1], $match))
                    $this->exitNow(self::RESULT_INVALID_PARAMETERS, "Bad increment value");

                if ($match[2] == 'v')
                    $result = $package->versionAdd('1.0.0');
                elseif ($match[2] == 's')
                    $result = $package->versionAdd('0.1.0');
                else
                    $result = $package->versionAdd('0.0.1');
            } elseif ($parameters[0] == '--delete') {
                if (!preg_match('/([0-9]+)(v|s|b)/', $parameters[1], $match))
                    $this->exitNow(self::RESULT_INVALID_PARAMETERS, "Bad decrement value");

                if ($match[2] == 'v')
                    $result = $package->versionDelete('1.0.0');
                elseif ($match[2] == 's')
                    $result = $package->versionDelete('0.1.0');
                else
                    $result = $package->versionDelete('0.0.1');
            } else {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            if (!$result)
                $this->exitNow(self::RESULT_ERROR_ITERATING, "Couldn't change version of package.");

            return self::RESULT_OK;
        }
    }
?>
