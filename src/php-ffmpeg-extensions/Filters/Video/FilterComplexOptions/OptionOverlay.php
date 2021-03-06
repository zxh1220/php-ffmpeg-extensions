<?php
/**
 * This file is part of PHP-FFmpeg-Extensions library.
 *
 * (c) Alexander Sharapov <alexander@sharapov.biz>
 * http://sharapov.biz/
 *
 */

namespace Sharapov\FFMpegExtensions\Filters\Video\FilterComplexOptions;

use Sharapov\FFMpegExtensions\Coordinate\Point;
use Sharapov\FFMpegExtensions\Coordinate\TimeLine;
use Sharapov\FFMpegExtensions\Filters\ExtraInputStreamInterface;
use Sharapov\FFMpegExtensions\Filters\ExtraInputStreamTrait;

/**
 * Overlay filter option
 * @package Sharapov\FFMpegExtensions\Filters\Video\FilterComplexOptions
 */
class OptionOverlay
  implements
  OptionInterface,
  ExtraInputStreamInterface {
  use TimeLineTrait;
  use FadeInOutTrait;
  use CoordinatesTrait;
  use DimensionsTrait;
  use ExtraInputStreamTrait;
  use ZindexTrait;

  /**
   * Get input streams collection.
   *
   * @return \FFMpeg\FFProbe\DataMapping\StreamCollection
   */
  public function getProbeData() {
    return $this->getProbe()->streams( $this->getExtraInputStream()->getPath() );
  }

  /**
   * Get input stream format.
   *
   * @return \FFMpeg\FFProbe\DataMapping\Format
   */
  public function getFormat() {
    return $this->getProbe()->format( $this->getExtraInputStream()->getPath() );
  }

  /**
   * Checks if overlay is image.
   *
   * @return bool
   */
  public function isImage() {
    return false !== @imagecreatefromstring( @file_get_contents( $this->getExtraInputStream()->getPath() ) );
  }

  /**
   * Returns command string.
   *
   * @return string
   */
  public function getCommand() {
    $coordinates = ( $this->getCoordinates() instanceof Point ) ? (string) $this->getCoordinates() : '0:0';

    if ( $this->getTimeLine() instanceof TimeLine ) {
      $timeLine = sprintf( ":%s", (string) $this->getTimeLine() );
    } else {
      $timeLine = '';
    }

    if ( $this->_fadeInSeconds or $this->_fadeOutSeconds ) {
      $fadeTime = [];
      if ( $this->_fadeInSeconds ) {
        $fadeTime[] = sprintf( "fade=t=in:st=0:d=%s:alpha='1'", $this->_fadeInSeconds );
      }
      if ( $this->_fadeOutSeconds ) {
        if ( $this->getTimeLine() instanceof TimeLine ) {
          // We have to calculate the starting point of fade out if we have the TimeLine object
          $fadeTime[] = sprintf( "fade=t=out:st=%s:d=%s:alpha='1'", ( $this->getTimeLine()->getEndTime() - $this->_fadeOutSeconds ), $this->_fadeOutSeconds );
        } else {
          // Otherwise we add {VIDEO_LENGTH} tag to calculate the starting point on the next step
          $fadeTime[] = sprintf( "fade=t=out:st={VIDEO_LENGTH}:d=%s:alpha='1'", $this->_fadeOutSeconds );
        }
      }
      $fadeTime = sprintf( ",%s", implode( ",", $fadeTime ) );
    } else {
      $fadeTime = '';
    }

    // We have to add shortest=1 for images to stop the overlay when the main video ends
    if ( $this->isImage() ) {
      $shortest = ":shortest='1'";
    } else {
      $shortest = '';
    }

    return sprintf( "[%s]format=yuva420p,scale=%s%s[%s],[%s][%s]overlay=%s%s%s[%s]", ':s1', (string) $this->getDimensions(), $fadeTime, ':s2', ':s3', ':s4', $coordinates, $shortest, $timeLine, ':s5' );
  }

  /**
   * Returns a command string.
   *
   * @return string
   */
  public function __toString() {
    return $this->getCommand();
  }
}
