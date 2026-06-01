<?php
function safe_filename($name){
    return preg_replace('/[^A-Za-z0-9_\-\.]/','_', $name);
}

function create_temp_dir(){
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'awm_'.bin2hex(random_bytes(6));
    if(!mkdir($dir, 0700, true)) return false;
    return $dir;
}

function load_image_from_file($path){
    $info = getimagesize($path);
    if(!$info) return false;
    $mime = $info['mime'];
    switch($mime){
        case 'image/jpeg': return imagecreatefromjpeg($path);
        case 'image/png': return imagecreatefrompng($path);
        case 'image/gif': return imagecreatefromgif($path);
        default: return false;
    }
}

function save_image_to_file($img, $path, $mime){
    switch($mime){
        case 'image/jpeg': imagejpeg($img, $path, 90); return true;
        case 'image/png': imagesavealpha($img, true); imagepng($img, $path); return true;
        case 'image/gif': imagegif($img, $path); return true;
    }
    return false;
}

function apply_text_watermark($img, $text, $position, $opacity, $scalePercent){
    $w = imagesx($img); $h = imagesy($img);
    $fontsize = max(12, intval($w * ($scalePercent/100) / mb_strlen($text)));
    $angle = 0;
    $fontfile = __DIR__.DIRECTORY_SEPARATOR.'../fonts/arial.ttf';
    if(file_exists($fontfile)){
        $bbox = imagettfbbox($fontsize, $angle, $fontfile, $text);
        $tw = abs($bbox[2]-$bbox[0]);
        $th = abs($bbox[7]-$bbox[1]);
        $tmp = imagecreatetruecolor($w, $h);
        imagesavealpha($tmp, true);
        $trans = imagecolorallocatealpha($tmp, 0,0,0,127);
        imagefill($tmp,0,0,$trans);
        $color = imagecolorallocatealpha($tmp, 255,255,255, 127 - intval(127*$opacity/100));
        $x = 0; $y = 0;
        switch($position){
            case 'top-left': $x=10; $y=10+$th; break;
            case 'top-right': $x=$w-$tw-10; $y=10+$th; break;
            case 'bottom-left': $x=10; $y=$h-10; break;
            case 'bottom-right': $x=$w-$tw-10; $y=$h-10; break;
            default: $x=($w-$tw)/2; $y=($h+$th)/2; break;
        }
        imagettftext($tmp, $fontsize, $angle, $x, $y, $color, $fontfile, $text);
        imagecopy($img, $tmp, 0,0,0,0,$w,$h);
        imagedestroy($tmp);
        return true;
    } else {
        // fallback to built-in font
        $col = imagecolorallocatealpha($img, 255,255,255, 127 - intval(127*$opacity/100));
        $x=10; $y=10;
        switch($position){
            case 'top-right': $x = $w - 10 - (8*strlen($text)); break;
            case 'bottom-left': $y = $h - 20; break;
            case 'bottom-right': $x = $w - 10 - (8*strlen($text)); $y = $h - 20; break;
            case 'center': $x = ($w/2) - (4*strlen($text)); $y = $h/2; break;
        }
        imagestring($img, 5, $x, $y, $text, $col);
        return true;
    }
}

function apply_logo_watermark($img, $logoPath, $position, $opacity, $scalePercent){
    $w = imagesx($img); $h = imagesy($img);
    $logo = load_image_from_file($logoPath);
    if(!$logo) return false;
    $lw = imagesx($logo); $lh = imagesy($logo);
    $targetW = intval($w * ($scalePercent/100));
    $ratio = $targetW / $lw;
    $targetH = intval($lh * $ratio);
    $tmp = imagecreatetruecolor($targetW, $targetH);
    imagesavealpha($tmp, true);
    $trans = imagecolorallocatealpha($tmp, 0,0,0,127);
    imagefill($tmp,0,0,$trans);
    imagecopyresampled($tmp, $logo, 0,0,0,0, $targetW, $targetH, $lw, $lh);

    $x=0; $y=0;
    switch($position){
        case 'top-left': $x=10; $y=10; break;
        case 'top-right': $x=$w-$targetW-10; $y=10; break;
        case 'bottom-left': $x=10; $y=$h-$targetH-10; break;
        case 'bottom-right': $x=$w-$targetW-10; $y=$h-$targetH-10; break;
        default: $x=($w-$targetW)/2; $y=($h-$targetH)/2; break;
    }

    // imagecopymerge doesn't preserve alpha for PNG; workaround: merge with opacity by adjusting alpha
    // Create overlay at same size as main image
    $overlay = imagecreatetruecolor($w, $h);
    imagesavealpha($overlay, true);
    $trans = imagecolorallocatealpha($overlay, 0,0,0,127);
    imagefill($overlay,0,0,$trans);
    imagecopy($overlay, $tmp, $x, $y, 0,0, $targetW, $targetH);
    // Merge overlay onto img with opacity
    $mergeOpacity = intval(100 - $opacity);
    imagecopymerge($img, $overlay, 0,0,0,0, $w, $h, $opacity);
    imagedestroy($overlay);
    imagedestroy($tmp);
    imagedestroy($logo);
    return true;
}
