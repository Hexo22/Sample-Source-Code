<?php

class File {
    public function readFile($FilePath, $FileName) {        
        if($file = fopen($FilePath . $FileName, 'r')) {
            $contents = fread($file, filesize($FileName));
            fclose($file);
            return $contents;
        } else {
            throw new FileException('Can not open file for reading.');
        }
    }
    
    public function appendTextToFile($FilePath, $FileName, $Text) {
        // This creates a new file if the file does not exist.
        if($file = fopen($FilePath . $FileName, 'a')) {
            fwrite($file, $Text);
            fclose($file);
        } else {
            throw new FileException('Can not open file for appending.');
        }
    }
}