<?php
$response = new stdClass();
$response->success = true;
try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_FILES)) {

        $files = array();
        if (is_array($_FILES['file']['name'])) {
            $count = count($_FILES['file']['name']);
            $keys = array_keys($_FILES['file']);
            for ($i = 0; $i < $count; $i++) {
                foreach ($keys as $key) {
                    $files[$i][$key] = $_FILES['file'][$key][$i];
                }
            }
            } else {
            $files[] = $_FILES['file'];
        }
        
        $job = new stdClass();
        $job->files = array();
        $job->zipFile = md5(uniqid(rand(), true)) . '.zip';
        $job->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $_SERVER['REMOTE_ADDR'] . '@' . time();
        mkdir($job->dir);
        foreach ($files as $file) {
            $fileName = $job->dir . DIRECTORY_SEPARATOR . $file['name'];
            move_uploaded_file($file['tmp_name'], $fileName);
            $job->files[] = $fileName;
        }

        $response->link = $job->zipFile;
        
        $client = new GearmanClient();
        $client->addServer();
        $result = $client->doBackground("convert", json_encode($job));
    }
} catch (Exception $e) {
    $response->success = false;
    $response->error = $e->getMessage();
}
die(json_encode($response));