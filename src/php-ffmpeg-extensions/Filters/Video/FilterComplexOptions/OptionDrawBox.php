<?php
/**
 * This file is part of PHP-FFmpeg-Extensions library.
 *
 * (c) Alexander Sharapov <alexander@sharapov.biz>
 * http://sharapov.biz/
 *
 */

namespace Sharapov\FFMpegExtensions\Filters\Video\FilterComplexOptions;

use FFMpeg\Exception\InvalidArgumentException;
use Sharapov\FFMpegExtensions\Coordinate\Dimension;
use Sharapov\FFMpegExtensions\Coordinate\TimeLine;

/**
 * DrawText filter option
 * @package Sharapov\FFMpegExtensions\Filters\Video\FilterComplexOptions
 */
class OptionDrawBox implements OptionsInterface
{
  use TimeLineTrait;
  use CoordinatesTrait;

  protected $_color = 'black@0.4:t=max';

  protected $_dimensions;

  /**
   * Constructor. Set box color.
   *
   * @param        $color
   * @param float  $transparency
   * @param string $thickness
   */
  public function __construct($color = 'black', $transparency = 0.4, $thickness = 'max')
  {
    $this->setColor($color, $transparency, $thickness);
  }

  /**
   * Set box color.
   *
   * @param        $color
   * @param float  $transparency
   * @param string $thickness
   *
   * @return $this
   */
  public function setColor($color, $transparency = 0.4, $thickness = 'max')
  {
    if (!is_numeric($transparency) || $transparency < 0 || $transparency > 1) {
      throw new InvalidArgumentException('Transparency should be integer or float value from 0 to 1. ' . $transparency . ' given.');
    }

    if ($thickness != 'max' and !is_int($thickness)) {
      throw new InvalidArgumentException('Thickness should be positive integer or "max". ' . $thickness . ' given.');
    }
    $this->_color = $color . '@' . $transparency . ":t=" . $thickness;

    return $this;
  }

  /**
   * Get box color.
   * @return string
   */
  public function getColor()
  {
    return $this->_color;
  }

  /**
   * Returns coordinates object.
   *
   * @param Dimension $dimension
   *
   * @return mixed
   */
  public function setDimensions(Dimension $dimension)
  {
    $this->_dimensions = $dimension;

    return $this;
  }

  /**
   * Returns dimensions object.
   * @return mixed
   */
  public function getDimensions()
  {
    return $this->_dimensions;
  }

  /**
   * Returns command string.
   * @return string
   */
  public function getCommand()
  {
    $filterOptions = [
        "x=" . $this->getCoordinates()->getX(),
        "y=" . $this->getCoordinates()->getY(),
        "w=" . $this->getDimensions()->getWidth(),
        "h=" . $this->getDimensions()->getHeight(),
        "color='" . $this->getColor() . "'"
    ];

    if ($this->_timeLine instanceof TimeLine) {
      $filterOptions[] = $this->_timeLine->getCommand();
    }

    return "drawbox=" . implode(":", $filterOptions);
  }

  /**
   * Returns a command string.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->getCommand();
  }
}
