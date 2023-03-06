<?php
/**
 * Utils for manage document processing
 * needed in ENV variables:
 * - OFFICE_CONVERT_URL convertion url service
 * - OFFICE_STORAGE_PATH storage path where document live
 */

namespace App\Utils;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Utils\Jwt;
use Exception;

/**
 * Class Utils
 *
 * @package App\Utils
 */
final class Document
{
    /**
     * Translation key to a supported form.
     *
     * @param string $expected_key  Expected key
     *
     * @return Supported key
     */
    public static function GenerateRevisionId($expected_key)
    {
        if (strlen($expected_key) > 20) $expected_key = crc32( $expected_key);  // if the expected key length is greater than 20, calculate the crc32 for it
        $key = preg_replace("[^0-9-.a-zA-Z_=]", "_", $expected_key);
        $key = substr($key, 0, min(array(strlen($key), 20)));  // the resulting key length is 20 or less
        return $key;
    }

    /**
     * The method is to convert the file to the required format.
     *
     * Example:
     * string convertedDocumentUri;
     * GetConvertedUri("http://helpcenter.onlyoffice.com/content/GettingStarted.pdf", ".pdf", ".docx", "http://helpcenter.onlyoffice.com/content/GettingStarted.pdf", false, out convertedDocumentUri);
     *
     * @param string $document_uri            Uri for the document to convert
     * @param string $from_extension          Document extension
     * @param string $to_extension            Extension to which to convert
     * @param string $document_revision_id    Key for caching on service
     * @param bool   $is_async                Perform conversions asynchronously
     * @param string $converted_document_uri  Uri to the converted document
     *
     * @return The percentage of completion of conversion
     */
    public static function GetConvertedUri($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async, &$converted_document_uri, $filePass = '')
    {
        $converted_document_uri = "";
        $responceFromConvertService = self::SendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async, $filePass);
        $json = json_decode($responceFromConvertService, true);

        // if an error occurs, then display an error message
        $errorElement = isset($json["error"]) ? $json["error"] : NULL;
        if ($errorElement != NULL && $errorElement != "") self::ProcessConvServResponceError($errorElement);

        $isEndConvert = $json["endConvert"];
        $percent = $json["percent"];

        // if the conversion is completed successfully
        if ($isEndConvert != NULL && $isEndConvert == true)
        {
            // then get the file url
            $converted_document_uri = $json["fileUrl"];
            $percent = 100;
        }
        // otherwise, get the percentage of conversion completion
        else if ($percent >= 100)
            $percent = 99;

        return $percent;
    }

    /**
     * Request for conversion to a service.
     *
     * @param string $document_uri            Uri for the document to convert
     * @param string $from_extension          Document extension
     * @param string $to_extension            Extension to which to convert
     * @param string $document_revision_id    Key for caching on service
     * @param bool   $is_async                Perform conversions asynchronously
     *
     * @return Document request result of conversion
     */
    public static function SendRequestToConvertService($document_uri, $from_extension, $to_extension, $document_revision_id, $is_async, $filePass = '')
    {
        if (empty($from_extension))
        {
            $path_parts = pathinfo($document_uri);
            $from_extension = strtolower($path_parts['extension']);
        }

        // if title is undefined, then replace it with a random guid
        $title = basename($document_uri);
        if (empty($title)) {
            $title = guid();
        }

        if (empty($document_revision_id)) {
            $document_revision_id = $document_uri;
        }

        // generate document token
        $document_revision_id = self::GenerateRevisionId($document_revision_id);

        $urlToConverter = env('OFFICE_CONVERT_URL', 'https://amdalnet-dev.menlhk.go.id/oods/ConvertService.ashx');

        $arr = [
            "async" => $is_async,
            "url" => $document_uri,
            "outputtype" => trim($to_extension,'.'),
            "filetype" => trim($from_extension, '.'),
            "title" => $title,
            "key" => $document_revision_id,
            "password" => $filePass
        ];

        // add header token
        $headerToken = "";
        if (Jwt::isEnabled()) {
            $headerToken = Jwt::encode([ "payload" => $arr ]);
            $arr["token"] = Jwt::encode($arr);
        }

        $data = json_encode($arr);

        // request parameters
        $opts = array(
            'http' => array(
                'method'  => 'POST',
                'header'=> "Content-type: application/json\r\n" .
                    "Accept: application/json\r\n" .
                    (empty($headerToken) ? "" : "Authorization: Bearer $headerToken\r\n"),
                'content' => $data
            )
        );

        if (substr($urlToConverter, 0, strlen("https")) === "https") {
            $opts['ssl'] = array( 'verify_peer'   => FALSE );
        }

        $context = stream_context_create($opts);
        $response_data = file_get_contents($urlToConverter, FALSE, $context);

        // $curl = curl_init();
        //     curl_setopt_array($curl, [
        //     CURLOPT_URL => $urlToConverter,
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => '',
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 0,
        //     CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => 'POST',
        //     CURLOPT_POSTFIELDS => $data,
        //     CURLOPT_HTTPHEADER => [
        //        'Authorization: Bearer'. $headerToken,
        //        'Content-Type: application/json',
        //        'Accept: application/json',
        //       ],
        //     ]);
        //     $response_data = curl_exec($curl);
        //     curl_close($curl);

        return $response_data;
    }

    /**
     * Generate an error code table
     *
     * @param string $errorCode   Error code
     *
     * @return null
     */
    public static function ProcessConvServResponceError($errorCode)
    {
        $errorMessageTemplate = "Error occurred in the document service: ";
        $errorMessage = '';

        // add the error message to the error message template depending on the error code
        switch ($errorCode)
        {
            case -8:
                $errorMessage = $errorMessageTemplate . "Error document VKey";
                break;
            case -7:
                $errorMessage = $errorMessageTemplate . "Error document request";
                break;
            case -6:
                $errorMessage = $errorMessageTemplate . "Error database";
                break;
            case -5:
                $errorMessage = $errorMessageTemplate . "Incorrect password";
                break;
            case -4:
                $errorMessage = $errorMessageTemplate . "Error download error";
                break;
            case -3:
                $errorMessage = $errorMessageTemplate . "Error convertation error";
                break;
            case -2:
                $errorMessage = $errorMessageTemplate . "Error convertation timeout";
                break;
            case -1:
                $errorMessage = $errorMessageTemplate . "Error convertation unknown";
                break;
            case 0:  // if the error code is equal to 0, the error message is empty
                break;
            default:
                $errorMessage = $errorMessageTemplate . "ErrorCode = " . $errorCode;  // default value for the error message
                break;
        }

        throw new Exception($errorMessage);
    }
    //// common ////
    /**
     * Get correct name
     *
     * @param string $fileName
     * @param string $userAddress
     */
    public static function GetCorrectName($fileName, $userAddress = NULL) {
        $path_parts = pathinfo($fileName);

        $ext = strtolower($path_parts['extension']);
        $name = $path_parts['basename'];
        $baseNameWithoutExt = substr($name, 0, strlen($name) - strlen($ext) - 1);  // get file name from the basename without extension
        $name = $baseNameWithoutExt . "." . $ext;

        $dir_disk = env('OFFICE_STORAGE_DISK', 'public');
        // for ($i = 1; file_exists(self::getStoragePath($name, $userAddress)); $i++)  // if a file with such a name already exists in this directory
        for ($i = 1; Storage::disk($dir_disk)->exists(self::getStoragePath($name, $userAddress)); $i++)  // if a file with such a name already exists in this directory
        {
            $name = $baseNameWithoutExt . " (" . $i . ")." . $ext;  // add an index after its base name
        }
        return $name;
    }

    /**
     * Get storage path document
     *
     * @param string $fileName
     * @param string $userAddress
     */
    public static function getStoragePath($fileName, $userAddress = NULL) {
        // $conf_storage_path = env('OFFICE_STORAGE_PATH', '/var/www/storage/app/public/workspace');

        // $storagePath = rtrim(str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $conf_storage_path), DIRECTORY_SEPARATOR);
        // $directory = $storagePath;

        // if ($storagePath != "")
        // {
        //     $directory =  $directory  . DIRECTORY_SEPARATOR;

        //     // if the file directory doesn't exist, make it
        //     if (!file_exists($directory) && !is_dir($directory)) {
        //         mkdir($directory);
        //     }
        // }
        $dir_path = env('OFFICE_STORAGE_SUBDIR', 'workspace');
        $dir_disk = env('OFFICE_STORAGE_DISK', 'public');

        $directory = $dir_path;

        if ($dir_path != '') {

            $directory =  $directory  . (substr($directory,-1) !='/' ? '/':'');

            if (!in_array($dir_path, Storage::disk($dir_disk)->directories(dirname($directory)))) {
                // create dir if not exists
                Storage::disk($dir_disk)->makeDirectory($dir_path);
            }
        }

        Log::info("getStoragePath result: " . $directory . basename($fileName));
        return $directory . basename($fileName);
    }

    /**
     * Get current user host address
     *
     * @param string $userAddress
     * @return string safe string user address
     */
    public static function getCurUserHostAddress($userAddress = NULL) {
        if (is_null($userAddress)) {$userAddress = self::getClientIp();}
        return preg_replace("[^0-9a-zA-Z.=]", '_', $userAddress);
    }

    /**
     * Get client ip address
     *
     * @return string client ip address
     */
    public static function getClientIp() {
        $ipaddress =
            getenv('HTTP_CLIENT_IP')?:
            getenv('HTTP_X_FORWARDED_FOR')?:
            getenv('HTTP_X_FORWARDED')?:
            getenv('HTTP_FORWARDED_FOR')?:
            getenv('HTTP_FORWARDED')?:
            getenv('REMOTE_ADDR')?:
            'Storage';

        $ipaddress = preg_replace("/[^0-9a-zA-Z.=]/", "_", $ipaddress);

        return $ipaddress;
    }

    /**
     * get the path to the file history
     *
     * @return string history dir
     */
    public static function getHistoryDir($storagePath) {
        $dir_disk = env('OFFICE_STORAGE_DISK', 'public');
        $directory = $storagePath . "-hist";

        // if the history directory doesn't exist, make it
        // if (!file_exists($directory) && !is_dir($directory)) {
        //     mkdir($directory);
        // }
        if (!in_array($directory, Storage::disk($dir_disk)->directories(dirname($directory)))) {
            Storage::disk($dir_disk)->makeDirectory($directory);
        }
        return $directory;
    }

    /**
     * get the path to the specified file version
     *
     * @return string versiondir
     */
    public static function getVersionDir($histDir, $version) {
        return $histDir . DIRECTORY_SEPARATOR . $version;
    }

    /**
     * get a number of the last file version from the history directory
     */
    public static function getFileVersion($histDir) {
        $dir_disk = env('OFFICE_STORAGE_DISK', 'public');

        // if (!file_exists($histDir) || !is_dir($histDir)) return 1;  // check if the history directory exists
        if (!in_array($histDir, Storage::disk($dir_disk)->directories(dirname($histDir)))) return 1;  // check if the history directory exists

        // $cdir = scandir($histDir);
        // $ver = 1;
        // foreach($cdir as $key => $fileName) {
        //     if (!in_array($fileName,array(".", ".."))) {
        //         if (is_dir($histDir . DIRECTORY_SEPARATOR . $fileName)) {
        //             $ver++;
        //         }
        //     }
        // }

        $listdir = Storage::disk($dir_disk)->directories($histDir);
        $ver = count($listdir)+1;
        return $ver;
    }

    /**
     * get the path to the forcesaved file version
     * @param string $fileName
     * @param string $userAddress
     * @param bool $create
     */
    public static function getForcesavePath($fileName, $userAddress, $create) {
        // $conf_storage_path = env('OFFICE_STORAGE_PATH', '/var/www/storage/app/public/workspace');
        // $storagePath = rtrim(str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $conf_storage_path), DIRECTORY_SEPARATOR);
        $dir_disk = env('OFFICE_STORAGE_DISK', 'public');
        $storagePath = env('OFFICE_STORAGE_SUBDIR', 'workspace');

        // create the directory to this file version
        // $directory = $storagePath . DIRECTORY_SEPARATOR; // . self::getCurUserHostAddress($userAddress) . DIRECTORY_SEPARATOR;
        $directory = $storagePath . '/';

        // if (!is_dir($directory)) return "";
        if (!in_array($storagePath, Storage::disk($dir_disk)->directories(dirname($directory)))) return "";

        // create the directory to the history of this file version
        // $directory = $directory . $fileName . "-hist" . DIRECTORY_SEPARATOR;
        // if (!$create && !is_dir($directory))  return "";

        $directory = $directory . $fileName . "-hist";
        if (!$create && !in_array($directory, Storage::disk($dir_disk)->directories(dirname($directory)))) return "";

        // mkdir($directory);
        Storage::disk($dir_disk)->makeDirectory($directory);

        $directory = $directory . '/' . $fileName;

        // if (!$create && !file_exists($directory)) return "";
        if (!$create && Storage::disk($dir_disk)->exists($directory)) return "";

        return $directory;
    }

    /**
     * create a file with meta information
     */
    public static function createMeta($fileName, $uid, $uname, $userAddress = NULL) {
        $histDir = self::getHistoryDir(self::getStoragePath($fileName, $userAddress));  // get the history directory

        // turn the file information into the json format
        $json = [
            "created" => date("Y-m-d H:i:s"),
            "uid" => $uid,
            "name" => $uname,
        ];

        // write the encoded file information to the createdInfo.json file
        // file_put_contents($histDir . DIRECTORY_SEPARATOR . "createdInfo.json", json_encode($json, JSON_PRETTY_PRINT));
        $dir_disk = env('OFFICE_STORAGE_DISK', 'public');
        return Storage::disk($dir_disk)->put($histDir . '/createdInfo.json',  json_encode($json, JSON_PRETTY_PRINT));
    }

    public static function getVirtualPath($forDocumentServer) {
        $conf_storage_path = env('OFFICE_STORAGE_PATH', '/var/www/storage/app/public/workspace');
        $storagePath = rtrim(str_replace(array('/','\\'), '/', $conf_storage_path), '/');
        $storagePath = $storagePath != "" ? $storagePath . '/' : "";


        // $virtPath = self::serverPath($forDocumentServer) . '/' . $storagePath . self::getCurUserHostAddress() . '/';
        $virtPath = self::serverPath($forDocumentServer) . '/' . $storagePath . '/';
        Log::debug("getVirtualPath virtPath: " . $virtPath);
        return $virtPath;
    }

    /**
     * get server url
     * */
    public static function serverPath($forDocumentServer = NULL) {
        return env('APP_URL') ? env('APP_URL') : (self::getScheme() . '://' . $_SERVER['HTTP_HOST']);
    }

    public static function getScheme() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    public static function FileUri($file_name, $forDocumentServer = NULL) {
        $sub = env('OFFICE_STORAGE_SUBDIR', '') . DIRECTORY_SEPARATOR;
        $uri = Storage::url($sub .$file_name);
        return $uri;
    }
}
