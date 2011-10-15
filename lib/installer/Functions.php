<?php
    function underscore($string)
    {
        $result = '';

        for ($i = 0; $i < strlen($string); $i++)
        {
            if (($i > 0) && (strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $string[$i]) !== false))
                $result .= '_';

            $result .= strtolower($string[$i]);
        }

        return $result;
    }

    function camelize($string, $firstUp = true)
    {
        $result = '';
        $last = 0;

        while (($pos = stripos($string, '_', $last)) !== false)
        {
            $portion = substr($string, $last, $pos-$last);
            $result .= strtoupper($portion[0]).substr($portion, 1);
            $last = $pos+1;
        }
        $portion = substr($string, $last);
        $result .= strtoupper($portion[0]).substr($portion, 1);

        if ($firstUp)
            return strtoupper($result[0]).substr($result, 1);
        else
            return strtolower($result[0]).substr($result, 1);
    }

    function getFileName()
    {
        $filePath = '';

        foreach (func_get_args() as $path)
        {
            if ($path == '')
                continue;

            if (substr($path, -1) != DIRECTORY_SEPARATOR)
                $filePath .= $path . DIRECTORY_SEPARATOR;
            else
                $filePath .= $path;
        }

        if (substr($filePath, -1) == DIRECTORY_SEPARATOR)
            return substr($filePath, 0, -1);
        else
            return $filePath;
    }

    function getTempPath() {
        $i = time();
        do {
            $path = getFileName(TMP_PATH, 'package-'.$i);
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
            $pathFrom = getFileName($from, $file);
            $pathTo = getFileName($to, $file);

            if (($file == '.') || ($file == '..'))
                continue;

            if (is_dir($pathFrom)) {
                $result = $result && (mkdir($pathTo));
                $result = $result && recursive_copy($pathFrom, $pathTo);
            } else {
                $result = $result && (copy($pathFrom, $pathTo));
            }
        }
        closedir($dir);

        return $result;
    }

    function recursive_delete($from, $deletePath = false) {
        $dir = opendir($from);
        $result = ($dir !== false);
        while ($result && ($file = readdir($dir))) {
            $pathFrom = getFileName($from, $file);

            if (($file == '.') || ($file == '..'))
                continue;

            if (is_dir($pathFrom) && !is_link($pathFrom)) {
                $result = $result && recursive_delete($pathFrom);
                $result = $result && (rmdir($pathFrom));
            } else
                $result = $result && (unlink($pathFrom));
        }
        closedir($dir);

        if ($deletePath && $result)
            rmdir($from);

        return $result;
    }

?>
