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

# root dir for image http site
#$image_root = __DIR__;
$image_root = '/var/www/image';

# default image url
$default_image_url = 'http://sskaje.me/default.jpg';

# Size white list, width x height
# Leave it empty to allow any sizes.
$size_whitelist = array(
#    '100x100'   =>  1,
#    '200x1'     =>  0,
);

$literal_config = array(
    'small' =>  array(
        'resize'     => '320x240>',
        'gravity'    => '',
        'extent'     => '',
    ),
    'medium' =>  array(
        'resize'     => '640x480>',
        'gravity'    => '',
        'extent'     => '',
    ),
    'large' =>  array(
        'resize'     => '800x600>',
        'gravity'    => '',
        'extent'     => '',
    ),

    'cover' => array(
        'resize'     => '180x120^',
        'gravity'    => 'center',
        'extent'     => '180x120',
    ),
);

# external config
if (is_file(__DIR__ . '/config.php')) {
    $config = include(__DIR__ . '/config.php');
    if (isset($config['redirect_image'])) {
        $redirect_image = $config['redirect_image'] ? true : false;
    }
    if (isset($config['image_path'])) {
        $image_path = $config['image_path'];
    }
    if (isset($config['convert_bin'])) {
        $convert_bin = $config['convert_bin'];
    }
    if (isset($config['image_root'])) {
        $image_root = $config['image_root'];
    }
    if (isset($config['default_image_url'])) {
        $default_image_url = $config['default_image_url'];
    }
    if (isset($config['size_whitelist'])) {
        $size_whitelist = $config['size_whitelist'];
    }
    if (isset($config['literal_config'])) {
        $literal_config = $config['literal_config'];
    }
}

/**
 * Accepted image extensions
 * for URL pattern regular expressions
 *
 * @var string
 */
$accepted_extensions = 'jpg|jpeg|png';

/**
 * Accepted url patterns
 *  xxx/xxx/xxx_1x20.jpg
 *  xxx/xxx/xxx_large.jpg
 *
 * @var array
 */
$accepted_patterns = array(
    'sized'   =>  '#^(?<prefix>.+)\_(?<width>\d+)x(?<height>\d+)\.(?<extension>'.$accepted_extensions.')$#i',
    'literal' =>  '#^(?<prefix>.+)\_(?<name>[a-z0-9]+)\.(?<extension>'.$accepted_extensions.')$#i',
);



if (parse_sized_path($image_path, $image_info)) {
    # parse image url with size

    # show default image
    if (!empty($size_whitelist) && (!isset($size_whitelist[$image_info['thumbnail_geometry']]) || !$size_whitelist[$image_info['thumbnail_geometry']])) {
        show_default();
    }

    if (!is_file($image_root . '/' . $image_info['src_image_path'])) {
        show_default();
    }

    # create image
    create_thumbnail(
        $image_root . '/' . $image_info['src_image_path'],
        $image_root . '/' . $image_info['image_path'],
        $image_info['width'],
        $image_info['height']
    );

    # display / redirect
    show_image($image_path);
} else if (parse_literal_path($image_path, $image_info)) {
    # parse image url with literal name

    if (!is_file($image_root . '/' . $image_info['src_image_path'])) {
        show_default();
    }

    create_resized_image(
        $image_root . '/' . $image_info['src_image_path'],
        $image_root . '/' . $image_info['image_path'],
        $image_info['options']
    );
    
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
function parse_sized_path($image_path, &$image_info)
{
    global $accepted_patterns;

    if (preg_match($accepted_patterns['sized'], $image_path, $m)) {

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
 *
 *
 * @param $image_path
 * @param $image_info
 * @return bool
 */
function parse_literal_path($image_path, &$image_info)
{
    global $accepted_patterns;
   
    if (preg_match($accepted_patterns['literal'], $image_path, $m)) {
        global $literal_config;

        $src_image_path = $m['prefix'] . '.' . $m['extension'];

        # TODO: calc size
        # list($width, $height, ) = getimagesize($image_path);

        $cfg = $literal_config[$m['name']];

        $image_info = array(
            'src_image_path'    =>  $src_image_path,
            'image_path'        =>  $image_path,
            'options'           =>  $cfg,
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

    # TODO: gif
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
        global $image_root, $extension;
        send_image_header($extension);
        output_image($image_root . '/' . $image_path);
    }

}

/**
 * Create thumbnail using -thumbnail
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
 * Create image using -resize
 *
 * @param string $src_img
 * @param string $dst_img
 * @param array $options
 */
function create_resized_image($src_img, $dst_img, array $options)
{
    global $convert_bin;
    $option_list = '';
    foreach ($options as $option=>$value) {
        if ($value === '' || $value === false || $value === null) {
            continue;
        }
        if ($value === true) {
            $option_list .= " -{$option} ";
        } else {
            $option_list .= " -{$option} '{$value}' ";
        }
    }
    $command = "{$convert_bin} {$src_img} {$option_list} {$dst_img}";
    shell_exec($command);
}

/**
 * Show default image
 *
 */
function show_default() {
    global $default_image_url;

    header('location: '  . $default_image_url);
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
