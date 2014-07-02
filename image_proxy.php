<?php
/**
 * Image Thumbnail Proxy
 *
 * @author sskaje
 * @url https://sskaje.me/2014/07/image-thumbnail-proxy/
 */

# use readfile() or send http 302
$redirect_image = true;

# URL like http://image_proxy.sskaje.me/image_proxy.php?/photo/2014/01/sskaje_100x100.jpg
$image_path = $_SERVER['QUERY_STRING'];

# ImageMagick convert, you can change to GraphicsMagic
$convert_bin = '/usr/bin/convert';

# root dir for website
#$root = __DIR__;
$root = '/var/www/image';

# default image url
$default_img = 'http://sskaje.me/default.jpg';

# Size white list, width x height
# Leave it empty to allow any sizes.
$size_whitelist = array(
#    '100x100'   =>  1,
#    '200x1'     =>  0,
);

# Accept url format xxx/xxx/xxx/xxx_1x20.jpg
$pattern = '#^(?<prefix>.+)\_(?<width>\d+)x(?<height>\d+)\.(?<extension>jpg|jpeg|png)$#i';

# parse query string
if (parse_image_path($image_path, $image_info)) {
    extract($image_info);

    # show default image
    if (!empty($size_whitelist) && (!isset($size_whitelist[$thumbnail_geometry]) || !$size_whitelist[$thumbnail_geometry])) {
        show_default();
    }

    # create image
    create_thumbnail($root . '/' . $src_img, $root . '/' . $image_path, $width, $height);

    # display / forward
    show_image($image_path);
}

# default
show_default();


/**
 * Parse info from image path
 *
 * @param string $image_path
 * @param array $image_info
 * @return bool
 */
function parse_image_path($image_path, &$image_info)
{
    global $pattern;

    if (preg_match($pattern, $image_path, $m)) {

        $width = $m['width'];
        $height = $m['height'];

        $src_image_path = $m['prefix'] . '.' . $m['extension'];
        $thumbnail_geometry = $width . 'x' . $height;

        $extension = strtolower($m['extension']);

        $image_info = array(
            'width'                 =>  $width,
            'height'                =>  $height,
            'src_image_path'        =>  $src_image_path,
            'image_path'            =>  $image_path,
            'thumbnail_geometry'    =>  $thumbnail_geometry,
            'extension'             =>  $extension,
        );

        return true;
    }

    return false;
}

/**
 * Send Content-Type header by file extension
 *
 * @param string $extension
 */
function send_image_header($extension)
{
    if ($extension == 'jpg' || $extension == 'jpeg') {
        header('Content-Type: image/jpeg');
    } else if ($extension == 'png') {
        header('Content-Type: image/png');
    }
}

/**
 * Read image file and output
 *
 * @param string $image_path
 */
function output_image($image_path)
{
    readfile($image_path);
    exit;
}

/**
 * Send HTTP 302 to image
 *
 * @param string $image_path
 */
function redirect_image($image_path)
{
    header('Location: ' . $image_path);
    exit;
}

/**
 * Show image
 *
 * @param string $image_path
 */
function show_image($image_path)
{
    global $redirect_image;
    if ($redirect_image) {
        redirect_image($image_path);
    } else {
        global $root, $extension;
        send_image_header($extension);
        output_image($root . '/' . $image_path);
    }

}

/**
 * Create thumbnail using external commands like ImageMagick
 *
 * @param string $src_img
 * @param string $dst_img
 * @param int $width
 * @param int $height
 */
function create_thumbnail($src_img, $dst_img, $width, $height) {
    global $convert_bin;
    $command = "{$convert_bin} {$src_img} -thumbnail {$width}x{$height} {$dst_img}";
    shell_exec($command);
}

/**
 * Show default image
 *
 * @param string $url
 */
function show_default($width=0, $height=0) {
    global $default_img;
    if ($width && $height) {
        $url = create_thumbnail_image_path($default_img, $width, $height);
    } else {
        $url = $default_img;
    }

    header('location: '  . $url);
    exit;
}

/**
 * Create thumbnail url
 *
 * @param $image_path
 * @param int $width
 * @param int $height
 * @return string
 */
function create_thumbnail_image_path($image_path, $width=0, $height=0)
{
    $rpos = strrpos($image_path, '.');
    if ($rpos === false) {
        return $image_path;
    }

    return substr($image_path, 0, $rpos) . '_' . $width . 'x' . $height . substr($image_path, $rpos);
}


# EOF
