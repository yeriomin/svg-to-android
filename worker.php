<?php
$worker= new GearmanWorker();
$worker->addServer();
$worker->addFunction("convert", "convert");
while ($worker->work());

function convert($jobObject) {
    $jobString = $jobObject->workload();
    file_put_contents('jobs.log', date('c') . ' - ' . $jobString . "\n", FILE_APPEND);
    $job = json_decode($jobString);
    if (empty($job->dir) || empty($job->zipFile) || empty($job->files)) {
        return;
    }

    $baseWidth = 48;
    $defaultDrawableName = "drawable";
    $widthRatios = array(
        "1" =>   "mdpi",
        "1.5" => "hdpi",
        "2" =>   "xhdpi",
        "3" =>   "xxhdpi",
        "4" =>   "xxxhdpi",
    );
    
    foreach ($widthRatios as $suffix) {
        mkdir($job->dir . DIRECTORY_SEPARATOR . $defaultDrawableName . "-" . $suffix);
    }

    $zip = new ZipArchive();
    $zip->open($job->zipFile, ZipArchive::CREATE);
    foreach ($job->files as $fileName) {
        foreach ($widthRatios as $ratio => $suffix) {
            $targetDir = $job->dir . DIRECTORY_SEPARATOR . $defaultDrawableName . "-" . $suffix;
            $info = pathinfo($fileName);
            $targetFileName = $targetDir . DIRECTORY_SEPARATOR . $info['filename'] . '.png';
            $commandInkscape = "inkscape -w" . intval($ratio*$baseWidth) . " --export-background-opacity=0 --export-png=" . $targetFileName . " " . $fileName;
            shell_exec($commandInkscape);
            $commandPngquant = "pngquant --force --quality=0-10 --output=" . $targetFileName . " " . $targetFileName;
            shell_exec($commandPngquant);
            $zip->addFile($targetFileName, $defaultDrawableName . "-" . $suffix . "/" . $info['filename'] . '.png');
        }
    }
    $zip->close();
}