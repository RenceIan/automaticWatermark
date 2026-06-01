<?php
require_once __DIR__.'/inc/functions.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

// Basic validation
if(empty($_FILES['images'])){
    echo 'No images uploaded.'; exit;
}

$wm_type = $_POST['wm_type'] ?? 'text';
$wm_text = $_POST['wm_text'] ?? '© My Brand';
$position = $_POST['position'] ?? 'bottom-right';
$opacity = intval($_POST['opacity'] ?? 70);
$scale = intval($_POST['scale'] ?? 20);
$as_zip = isset($_POST['as_zip']);

$tmpDir = create_temp_dir();
if(!$tmpDir){ echo 'Unable to create temp dir'; exit; }

$outFiles = [];

// If logo uploaded, move to temp
$logoPath = null;
if(isset($_FILES['logo']) && $_FILES['logo']['error']===0){
    $tmp = $_FILES['logo']['tmp_name'];
    $info = getimagesize($tmp);
    if($info && $info['mime']==='image/png'){
        $logoPath = $tmp; // use directly
    }
}

foreach($_FILES['images']['tmp_name'] as $i => $tmpName){
    if(!is_uploaded_file($tmpName)) continue;
    $origName = $_FILES['images']['name'][$i] ?? 'image_'.$i;
    $safe = safe_filename($origName);
    $info = getimagesize($tmpName);
    if(!$info) continue;
    $mime = $info['mime'];
    $img = load_image_from_file($tmpName);
    if(!$img) continue;

    if($wm_type==='text'){
        apply_text_watermark($img, $wm_text, $position, $opacity, $scale);
    } else {
        if($logoPath) apply_logo_watermark($img, $logoPath, $position, $opacity, $scale);
    }

    $outPath = $tmpDir.DIRECTORY_SEPARATOR.$safe;
    save_image_to_file($img, $outPath, $mime);
    imagedestroy($img);
    $outFiles[] = $outPath;
}

if(count($outFiles)===0){ echo 'No images processed.'; exit; }

if($as_zip){
    $zipPath = $tmpDir.DIRECTORY_SEPARATOR.'watermarked_'.time().'.zip';
    $zip = new ZipArchive();
    if($zip->open($zipPath, ZipArchive::CREATE)!==true){ echo 'Failed to create zip'; exit; }
    foreach($outFiles as $f){
        $zip->addFile($f, basename($f));
    }
    $zip->close();
    // stream zip
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="watermarked.zip"');
    header('Content-Length: '.filesize($zipPath));
    readfile($zipPath);
    // cleanup
    foreach($outFiles as $f) @unlink($f);
    @unlink($zipPath);
    @rmdir($tmpDir);
    exit;
} else {
    // Send simple HTML with links to files
    echo "<h2>Processed images</h2>";
    foreach($outFiles as $f){
        $name = basename($f);
        $data = base64_encode(file_get_contents($f));
        $mime = mime_content_type($f);
        echo "<div><a download='$name' href='data:$mime;base64,$data'>$name</a></div>";
        @unlink($f);
    }
    @rmdir($tmpDir);
}
