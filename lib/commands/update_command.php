<?php
    class UpdateCommand extends YPICommand {
        public function getDescription() {
            return 'Update list of official available packages';
        }

        public function help($parameters) {
            echo "ypi update\n".
                 "Update list of official available packages\n";

            return YPFCommand::RESULT_OK;
        }

        public function run($parameters) {
            if (!empty($parameters)) {
                $this->help ($parameters);
                $this->exitNow (self::RESULT_INVALID_PARAMETERS);
            }

            if (!is_dir(DB_PATH))
                system(sprintf('git clone git://gist.github.com/2047698.git %s', escapeshellarg(DB_PATH)), $result);
            else {
                $cwd = getcwd();
                chdir(DB_PATH);
                system('git pull', $result);
                chdir($cwd);
            }

            if ($result)
                $this->exitNow (self::RESULT_FILES_ERROR, 'Couldn\'t get the list from internet');

            $list = $this->getConfig(getFileName(DB_PATH, 'ypidb.yml'));

            printf("List updated. Last modification: %s\n", date('d/m/Y H:i:s', $list['last_modification']));

            return self::RESULT_OK;
        }
    }
?>
