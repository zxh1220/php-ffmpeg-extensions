<?php
/**
 * This file is part of PHP-FFmpeg-Extensions library.
 *
 * (c) Alexander Sharapov <alexander@sharapov.biz>
 * http://sharapov.biz/
 *
 */

namespace Sharapov\FFMpegExtensions\Stream;

/**
 * Interface FileInterface
 * @package Sharapov\FFMpegExtensions\Stream
 */
interface FileInterface
{
  public function setFile($file);
  public function getFile();
}
