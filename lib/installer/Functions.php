<?php
    function getTempPath() {
        $i = time();
        do {
            $path = TMP_PATH.DIRECTORY_SEPARATOR.'package-'.$i;
            $i++;
        } while(file_exists($path));

        return $path;
    }

    function compareVersion($version1, $version2) {
        $version1 = normalizeVersion($version1);
        $version2 = normalizeVersion($version2);

        if ($version1[0] > $version2[0])
            return -1;
        elseif ($version1[0] < $version2[0])
            return 1;
        else {
            if ($version1[1] > $version2[1])
                return -1;
            elseif ($version1[1] < $version2[1])
                return 1;
            else {
                if ($version1[2] > $version2[2])
                    return -1;
                elseif ($version1[2] < $version2[2])
                    return 1;
                else
                    return 0;
            }
        }
    }

    function normalizeVersion($version, $stringify = false) {
        if (is_string($version)) {
            $version = explode('.', $version);
            while (count($version) < 3) $version[] = 0;
        }

        if ($stringify)
            return implode('.', $version);
        else
            return $version;
    }

    function loadPackageFromSsh($srcPath) {
        $destPath = getTempPath();
        $cmd = sprintf('scp -r "%s" "%s"', addslashes($srcPath), addslashes($destPath));
        system($cmd, $value);
        return ($value == 0)? $destPath: false;
    }

    function loadPackageFromGit($srcPath) {
        $destPath = getTempPath();
        system(sprintf('git clone "%s" "%s"', addslashes($srcPath), addslashes($destPath)), $value);
        return ($value == 0)? $destPath: false;
    }

    function loadPackageFromFtp($srcPath) {
        $destPath = getTempPath();
        //system(sprintf('git clone "%s" "%s"', addslashes($srcPath), addslashes($destPath)), $value);
        return false;//($value == 0)? $destPath: false;
    }

    function recursive_copy($from, $to) {
        $dir = opendir($from);
        $result = ($dir !== false);
        while ($result && ($file = readdir($dir))) {
            $pathFrom = $from . DIRECTORY_SEPARATOR . $file;
            $pathTo = $to . DIRECTORY_SEPARATOR . $file;

            if (($file == '.') || ($file == '..'))
                continue;

            if (is_dir($pathFrom)) {
                $result = $result && (mkdir($pathTo));
                $result = $result && recursive_copy($pathFrom, $pathTo);
            } else {
                $result = $result && (copy($pathFrom, $pathTo));
            }
        }

        return $result;
    }

    function recursive_delete($from) {
        $dir = opendir($from);
        $result = ($dir !== false);
        while ($result && ($file = readdir($dir))) {
            $pathFrom = $from . DIRECTORY_SEPARATOR . $file;

            if (($file == '.') || ($file == '..'))
                continue;

            if (is_dir($pathFrom)) {
                $result = $result && recursive_delete($pathFrom);
                $result = $result && (rmdir($pathFrom));
            } else
                $result = $result && (unlink($pathFrom));
        }

        return $result;
    }

?>
