<?php
require_once("minecraftAvatar.php");
require_once('GifCreator.php');
require_once('3d.class.php');

use GifCreator\GifCreator;

/**
 * Class moreMCavatar
 *
 * @author  Max Korlaar
 * @license MIT
 */
class moreMCavatar extends MCavatar
{
    public $images;
    public $username;
    public $headOnly;
    public $helmet;
    public $layers;
    public $speed;
    public $frames;
    public $filepath;
    public $invert;
    public $debug = false;
    private $playerRender = null;

    /**
     * @param        $username
     * @param int $size
     * @param int $speed
     * @param int $rotation
     * @param bool $headOnly
     * @param bool $helmet
     * @param bool $layers
     * @param string $return
     *
     * @return bool|resource|string
     */
    public function getRotatingSkinFromCache($username, $size = 2, $speed = 3, $rotation = 5, $headOnly = false, $helmet = true, $layers = false, $return = 'binary')
    {

        if ($layers) {
            $l = '-withlayers';
        } else {
            $l = '';
        }

        if ($speed != 3 || $rotation != 5) {
            $imagepath = $this->imagepath . 'rotate_gif/' . strtolower($username) . "-{$size}x-{$speed}s-{$rotation}fms{$l}.gif";
            $this->publicurl = '/img/rotate_gif/' . strtolower($username) . "-{$size}x-{$speed}s-{$rotation}fms{$l}.gif";
        } else {
            $imagepath = $this->imagepath . 'rotate_gif/' . strtolower($username) . "-{$size}x{$l}.gif";
            $this->publicurl = '/img/rotate_gif/' . strtolower($username) . "-{$size}x{$l}.gif";
        }
        $this->filepath = $imagepath;

        if (file_exists($imagepath)) {
            if (filemtime($imagepath) < strtotime('-1 week')) {
                $this->cacheInfo = '3d full skin expired, redownloading';
                unlink($imagepath);
                if ($return == 'binary') {
                    return $this->getRotatingSkin($username, $size, $speed, $rotation, $headOnly, $helmet, $layers, 'save-binary');
                }
                return $this->getRotatingSkin($username, $size, $speed, $rotation, $headOnly, $helmet, $layers, $return);

            } else {
                $this->cacheInfo = '3d full skin image exists and OK';
                if (isset($_GET['db'])) echo 'file exists and OK';
                if ($return == 'binary') {
                    return file_get_contents($imagepath);
                }
                if ($return == 'url' || $return == 'resource') {
                    if ($return == 'resource') {
                        return imagecreatefromgif($imagepath);
                    } else {
                        return $imagepath;
                    }
                }
                return $imagepath;

            }
        } else {
            if (isset($_GET['db'])) echo 'file not found!';
            if (isset($_GET['db'])) echo ' filename: ' . $imagepath;
            $this->cacheInfo = '3d full skin image not yet downloaded';
            if ($return == 'binary') {
                return $this->getRotatingSkin($username, $size, $speed, $rotation, $headOnly, $helmet, $layers, 'save-binary');
            }
            return $this->getRotatingSkin($username, $size, $speed, $rotation, $headOnly, $helmet, $return);
        }
    }

    /**
     * @param        $username
     * @param int $size
     * @param int $speed
     * @param int $frames
     * @param bool $headOnly
     * @param bool $helmet
     * @param bool $layers
     * @param string $return
     *
     * @internal param int $rotation
     * @return bool|resource|string
     */
    function getRotatingSkin($username, $size = 2, $speed = 3, $frames = 5, $headOnly = false, $helmet = true, $layers = false, $return = 'binary')
    {
        $this->images = [];
        $this->username = $username;
        $this->size = $size;
        $this->headOnly = $headOnly;
        $this->helmet = $helmet;
        $this->layers = $layers;
        $this->speed = $speed;
        $this->frames = $frames;
        /**
         * @param $angle
         */
        $rotation = function ($angle) {
            if ($this->invert) {
                $angle = $angle * -1;
            }
            $player = new render3DPlayer($this->username, '0', $angle, '0', '0', '0', '0', '0', "{$this->helmet}", "{$this->headOnly}", 'png', $this->size, 'false', $this->layers);
            array_push($this->images, $player->get3DRender());
        };

        if ($frames < 0) {
            $frames = $frames * -1;
            $this->invert = true;
        } else {
            $this->invert = false;
        }

        $circle = range(0, 360, $frames);
        array_map($rotation, $circle);
        $durations = [];
        for ($i = 0; $i < 360 / $frames + 1; $i++) {
            array_push($durations, $speed);
        }
        $gc = new GifCreator();
        $gc->create($this->images, $durations, 0);
        $gifBinary = $gc->getGif();

        foreach ($this->images as $img) {
            imagedestroy($img);
        }
        if ($return == 'binary') return $gifBinary;

        if ($return == 'url' || $return == 'resource' || $return == 'save-binary') {
            if ($layers) {
                $l = '-withlayers';
            } else {
                $l = '';
            }
            if ($this->invert) {
                $frames = $frames * -1;
            }
            if ($speed != 3 || $frames != 5) {
                $imagepath = $this->imagepath . 'rotate_gif/' . strtolower($username) . "-{$size}x-{$speed}s-{$frames}fms{$l}.gif";
                $this->publicurl = '/img/rotate_gif/' . strtolower($username) . "-{$size}x-{$speed}s-{$frames}fms{$l}.gif";
            } else {
                $imagepath = $this->imagepath . 'rotate_gif/' . strtolower($username) . "-{$size}x{$l}.gif";
                $this->publicurl = '/img/rotate_gif/' . strtolower($username) . '.gif';
            }

            if (!file_exists($this->imagepath . 'rotate_gif/')) {
                mkdir($this->imagepath . 'rotate_gif/', 0777, true);
            }
            @file_put_contents($imagepath, $gifBinary);
            if ($return == 'resource') {
                return imagecreatefromgif($imagepath);
            } elseif ($return == 'save-binary') {
                return $gifBinary;
            } else {
                return $imagepath;
            }
        }
        return false;
    }

    /**
     * @param      $username
     * @param int $size
     * @param int $angle
     * @param bool $headOnly
     * @param bool $helmet
     * @param bool $layers
     *
     * @return resource
     */
    function getThreeDSkinFromCache($username, $size = 2, $angle = 0, $headOnly = false, $helmet = true, $layers = false)
    {
        if ($headOnly) {
            $h = '-head';
        } else {
            $h = '';
        }
        if (!$helmet) {
            $nh = '-nohelm';
        } else {
            $nh = '';
        }
        if ($layers) {
            $l = '-withlayers';
        } else {
            $l = '';
        }
        $imagepath = $this->imagepath . '3d/' . strtolower($username) . "-{$size}x-{$angle}{$h}{$nh}{$l}.png";
        $this->publicurl = '/img/3d/' . strtolower($username) . "-{$size}x-{$angle}{$h}{$nh}{$l}.png";

        /*
         * PutinPHP v0.1b
         */
        if (isset($_GET['putin'])) {
            return imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . '/resources/Putin-125px.png');
        }

        if (file_exists($imagepath)) {
            if (filemtime($imagepath) < strtotime('-3 days')) {
                $this->cacheInfo = '3d skin expired, regenerating';
                unlink($imagepath);
                $image = $this->getThreeDSkin($username, $size, $angle, $headOnly, $helmet, $layers);
                if ($this->playerRender->fetchError === null) imagepng($image, $imagepath); // Only cache the image if it's fetched successfully.
                return $image;
            } else {
                $this->cacheInfo = '3d skin image exists and OK';
                return imagecreatefrompng($imagepath);
            }
        } else {
            $this->cacheInfo = '3d skin image not yet generated';
            $image = $this->getThreeDSkin($username, $size, $angle, $headOnly, $helmet, $layers);

            if ($this->playerRender->fetchError === null) imagepng($image, $imagepath); // Only cache the image if it's fetched successfully.
            return $image;
        }
    }

    /**
     * @param      $username
     * @param int $size
     * @param int $angle
     * @param bool $headOnly
     * @param bool $helmet
     * @param bool $layers
     *
     * @return resource|string
     */
    public function getThreeDSkin($username, $size = 2, $angle = 0, $headOnly = false, $helmet = true, $layers = false)
    {
        $this->username = $username;
        $this->size = $size;
        $this->headOnly = $headOnly;
        $this->helmet = $helmet;
        $this->layers = $layers;
        $this->playerRender = new render3DPlayer($this->username, '0', $angle, '0', '0', '0', '0', '0', $this->helmet, $this->headOnly, 'png', $this->size, 'false', $this->layers, $this->debug);
        return $this->playerRender->get3DRender();
    }

}