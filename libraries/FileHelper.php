<?php

/**
 * FileHelper class
 */
class FileHelper {

    /**
     * This function use the PHP supper global variable $_FILES to upload a file
     * to the server more or less securely
     * 
     * @param string $index     The name of the index in the associative $_FILES array 
     * @param string $fileName  The new file name including the path to where the file will be saved, without extension
     * @param array $options    Extra options to make special check on the file to be uploaded like
     *                          'maxSize' max length of file in bytes, 'extensions' array of the allowed extensions,
     *                          'mimeTypes' array of the allowed mime types. An example of the complete option is as followed
     *                          $options = array('maxSize' => 2048000, 'extensions' => array('png', 'jpg', 'jpeg'),
     *                          'mimeTypes' => array('image/png', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg'))
     * 
     * @return false|string     Return false if failed or a string for the file name
     */
    public static function uploadFile($index = 'logo', $fileName = '', $options = array()) {
        $files = $_FILES;

        if (!is_string($index) || !is_string($fileName) || !is_array($options)) {
            return false;
        }

        $maxSize = (isset($options['maxSize']) && is_int($options['maxSize'])) ? $options['maxSize'] : 2000000;
        $extensions = (isset($options['extensions']) && is_array($options['extensions'])) ?
                $options['extensions'] : array('png', 'jpg', 'jpeg');
        $mimeTypes = (isset($options['mimeTypes']) && is_array($options['mimeTypes'])) ?
                $options['mimeTypes'] : array('image/png', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg');

        //Make sure a file was uploaded
        if ((!empty($files[$index])) && ($files[$index]['error'] == 0)) {
            $uploadFileName = basename($files[$index]['name']);
            $ext = strtolower(substr($uploadFileName, strrpos($uploadFileName, '.') + 1));
            $type = $files[$index]["type"];
            $size = $files[$index]["size"];
            $fileName = $fileName . "." . $ext;

            if (in_array($ext, $extensions) && in_array($type, $mimeTypes) && $size <= $maxSize) {
                if (!file_exists($fileName)) {
                    //Try moving the file
                    if ((move_uploaded_file($files[$index]['tmp_name'], $fileName))) {
                        return basename($fileName);
                    } else {
                        return false;
                    }
                } else {
                    return basename($fileName);
                }
            } else {
                return false;
            }
        } else { //File index does not exist or file uploaded with error
            return false;
        }
    }

    /**
     * This function use the PHP supper global variable $_FILES to upload multiple files
     * to the server more or less securely
     * 
     * @param string $index         The name of the index in the associative $_FILES array 
     * @param string $baseDir       The directory to where the files will be saved
     * @param string $filePrefix    A prefix string to add to the file name
     * @param string $fileNames     An array of the new files name without the path to where the file will be saved and without extension
     * @param array $options        Extra options to make special check on the file to be uploaded like
     *                              'maxSize' max length of file in bytes, 'extensions' array of the allowed extensions,
     *                              'mimeTypes' array of the allowed mime types. An example of the complete option is as followed
     *                              $options = array('maxSize' => 2048000, 'extensions' => array('png', 'jpg', 'jpeg'),
     *                              'mimeTypes' => array('image/png', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg'))
     * 
     * @return false|array          Return false if validation check failed or an array of the new files name and error key set 
     *                              within the same index that was passed to the function
     *                              Example result = array('index' => array('name' = array('2' => 'one.jpg', '3' =>'two.png'), 
     *                              'error' = array('2' => 0, '3' => 0)))
     *                              The key index is the original php file name key index
     */
    public static function uploadMultipleFiles($index, $baseDir = '', $filePrefix = '', $fileNames = array(), $options = array()) {
        $files = $_FILES;
        $errors = array();
        $result = array();
        $result[$index]['name'] = array();

        if (!is_string($index) || !is_array($fileNames) || !is_array($options) || !is_string($filePrefix) || !is_string($baseDir)) {
            return false;
        }

        //Setting configurations options
        $maxSize = (isset($options['maxSize']) && is_int($options['maxSize'])) ? $options['maxSize'] : 2000000;
        $extensions = (isset($options['extensions']) && is_array($options['extensions'])) ?
                $options['extensions'] : array('png', 'jpg', 'jpeg');
        $mimeTypes = (isset($options['mimeTypes']) && is_array($options['mimeTypes'])) ?
                $options['mimeTypes'] : array('image/png', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg');

        //Make sure a file was uploaded
        if (!isset($files[$index]) || !isset($files[$index]['name'])) {
            return false;
        }

        //Iterate the uploaded files
        foreach ($files[$index]['name'] as $key => $name) {
            $uploadFileName = basename($name);
            $ext = strtolower(substr($uploadFileName, strrpos($uploadFileName, '.') + 1));
            $type = $files[$index]['type'][$key];
            $size = $files[$index]['size'][$key];
            $fileName = isset($fileNames[$key]) && is_string($fileNames[$key]) ? $fileNames[$key] :
                    (!empty($filePrefix) ? $filePrefix : '') . sha1(uniqid('', true));
            $fileName .= '.' . $ext;

            if ($files[$index]['error'][$key] != 0) {
                $errors[$key] = array(
                    'name' => $files[$index]['name'][$key],
                    'type' => $files[$index]['type'][$key],
                    'tmp_name' => $files[$index]['tmp_name'][$key],
                    'error' => $files[$index]['error'][$key],
                    'size' => $files[$index]['size'][$key]
                );
            } else {
                if (in_array($ext, $extensions) && in_array($type, $mimeTypes) && $size <= $maxSize) {
                    if (!file_exists($fileName)) {
                        //Try moving the file
                        if ((move_uploaded_file($files[$index]['tmp_name'][$key], $baseDir . $fileName))) {
                            $result[$index]['name'][$key] = basename($fileName);
                        } else { //File could not be moved
                            $errors[$key] = array(
                                'name' => $files[$index]['name'][$key],
                                'type' => $files[$index]['type'][$key],
                                'tmp_name' => $files[$index]['tmp_name'][$key],
                                'error' => $files[$index]['error'][$key],
                                'size' => $files[$index]['size'][$key]
                            );
                        }
                    } else { //The file already exists
                        $errors[$key] = array(
                            'name' => $files[$index]['name'][$key],
                            'type' => $files[$index]['type'][$key],
                            'tmp_name' => $files[$index]['tmp_name'][$key],
                            'error' => $files[$index]['error'][$key],
                            'size' => $files[$index]['size'][$key]
                        );
                    }
                } else { //File validation failed
                    return false;
                }
            }
        }

        $result[$index]['error'] = $errors;
        return $result;
    }

    /**
     * Check the upload file in the specific index and remove empty indexes
     * 
     * @param tring $index
     * 
     * @return void
     */
    public static function removeEmpty($index, array &$files = array()) {
        if (count($files) === 0) {
            $files = &$_FILES;
        }
        
        if (isset($files[$index]) && is_array($files[$index]) && isset($files[$index]['name']) && is_array($files[$index]['name'])) {
            foreach ($files[$index]['name'] as $key => $name) {
                $vName = trim($name);
                if (empty($vName)) {
                    unset($files[$index]['name'][$key]);
                    unset($files[$index]['type'][$key]);
                    unset($files[$index]['tmp_name'][$key]);
                    unset($files[$index]['error'][$key]);
                    unset($files[$index]['size'][$key]);
                }
            }
        }
    }

}
