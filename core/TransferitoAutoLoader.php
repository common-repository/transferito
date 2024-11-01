<?php

class TransferitoAutoLoader {

    static public function load($className)
    {
        $filename = "src/" . str_replace('\\', '/', $className) . ".php";
        $updatedFilename = str_replace("Transferito/", "", $filename);
        $fullPath = plugin_dir_path( __DIR__ ) . "" . $updatedFilename;

        if (file_exists($fullPath)) {
            include($fullPath);
            if (class_exists($className)) {
                return true;
            }
        }
        return false;
    }

}

spl_autoload_register('TransferitoAutoLoader::load');
