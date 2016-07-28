<?php
/**
 * This file is part of PHP-FFmpeg-Extensions library.
 *
 * (c) Alexander Sharapov <alexander@sharapov.biz>
 * http://sharapov.biz/
 *
 */

namespace Sharapov\FFMpegExtensions\Media;

use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use FFMpeg\Exception\InvalidArgumentException;
use FFMpeg\Exception\RuntimeException;
use FFMpeg\Format\FormatInterface;
use FFMpeg\Format\ProgressableInterface;
use Sharapov\FFMpegExtensions\Filters\Video\Concatenation\DemuxerFilter;
use Sharapov\FFMpegExtensions\Filters\Video\Concatenation\ProtocolFilter;
use Sharapov\FFMpegExtensions\Format\Video\TransportStream;

class Video extends \FFMpeg\Media\Video
{
  /**
   * @var object
   */
  private $_concatFilter;

  /**
   * Concat protocol.
   * Use with formats that support file level concatenation (MPEG-1, MPEG-2 PS, DV).
   * @return null|\Sharapov\FFMpegExtensions\Filters\Video\Concatenation\ProtocolFilter
   */
  public function concatProtocol()
  {
    return $this->_concatFilter = ProtocolFilter::init();
  }

  /**
   * Concat videofilter.
   * Useful if you need to re-encode such as when applying filters.
   * TODO: add concat videofilter support
   */
  public function concatVideoFilter()
  {
    throw new InvalidArgumentException('ConcatVideoFilter does not implemented');
  }

  /**
   * Concat demuxer.
   * Useful when you want to avoid a re-encode and your format does not support file level concatenation.
   * TODO: add concat demuxer support
   */
  public function concatDemuxer()
  {
    throw new InvalidArgumentException('ConcatDemuxer does not implemented');
  }

  /**
   * Runs concatenation process using one of concat methods: Protocol or Demuxer.
   *
   * @param $outputPathfile
   *
   * @return Video
   *
   * @throws RuntimeException
   */
  public function merge($outputPathfile)
  {
    if ($this->_concatFilter instanceof ProtocolFilter) {
      // Make sure the video stream has encoded by h264
      if ($this->getStreams()->videos()->getIterator()->current()->get('codec_name') != 'h264') {
        throw new InvalidArgumentException('Concat protocol supports only file level concatenation: MPEG-1, MPEG-2 PS, DV.');
      }

      $inputs = $this->_concatFilter->getFiles();

      array_unshift($inputs, $this->pathfile);

      $commands = [
          '-y',
          '-i',
          sprintf('concat:%s', implode("|", $inputs)),
          '-c',
          'copy',
          '-bsf:a',
          'aac_adtstoasc',
          $outputPathfile
      ];

      $failure = null;

      try {
        $this->driver->command($commands, false);
      } catch (ExecutionFailureException $e) {
        $failure = $e;
      }

      if (null !== $failure) {
        throw new RuntimeException('Encoding failed', $failure->getCode(), $failure);
      }

      return $this;
    } elseif($this->_concatFilter instanceof DemuxerFilter) {
      throw new RuntimeException('ConcatDemuxer does not implemented yet');
    } else {
      throw new RuntimeException('Unsupported concat method');
    }
  }

  /**
   * Exports the video in the desired format, applies registered filters.
   * Extends an original Save method
   *
   * @param FormatInterface $format
   * @param string          $outputPathfile
   *
   * @return Video
   *
   * @throws RuntimeException
   */
  public function save(FormatInterface $format, $outputPathfile)
  {
    // We have own export processing for TransportStream format
    if ($format instanceof TransportStream) {

      // Make sure the video stream has encoded by h264
      if ($this->getStreams()->videos()->getIterator()->current()->get('codec_name') != 'h264') {
        throw new InvalidArgumentException('Transport stream supports only h264');
      }

      $commands = [
          '-y',
          '-i',
          $this->pathfile,
          '-c',
          'copy',
          '-bsf:v',
          'h264_mp4toannexb',
          '-f',
          'mpegts',
          $outputPathfile
      ];

      $failure = null;

      try {
        /** add listeners here */
        $listeners = null;

        if ($format instanceof ProgressableInterface) {
          $listeners = $format->createProgressListener($this, $this->ffprobe, $format->getPasses(), $format->getPasses());
        }

        $this->driver->command($commands, false, $listeners);
      } catch (ExecutionFailureException $e) {
        $failure = $e;
      }

      if (null !== $failure) {
        throw new RuntimeException('Encoding failed', $failure->getCode(), $failure);
      }

      return $this;
    }

    return parent::save($format, $outputPathfile);
  }
}