<?php
/**
 * Image Thumbnail Proxy
 *
 * @author sskaje
 * @url https://sskaje.me/2014/07/image-thumbnail-proxy/
 */


$config = array(
    'redirect_image'    =>  true,
    'image_path'        =>  $_SERVER['QUERY_STRING'],
    'convert_bin'       =>  '/usr/bin/convert',
    'image_root'        =>  '/var/www/image',
    'default_image_url' =>  '',
    'size_whitelist'    =>  array(),
    'literal_config'    =>  array(
        'small' =>  array(
            'resize'     => '320x240>',
        ),
        'medium' =>  array(
            'resize'     => '640x480>',
        ),
        'large' =>  array(
            'resize'     => '800x600>',
        ),
        'cover' => array(
            'resize'     => '180x120^',
            'gravity'    => 'center',
            'extent'     => '180x120',
        ),
    ),
);

return $config;
