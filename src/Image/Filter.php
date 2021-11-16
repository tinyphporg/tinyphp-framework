<?php
/**
*
* @Copyright (C), 2013 tinynet@qq.com
* @Name  Filter.php
* @Author  King
* @Version  Beta 1.0
* @Date:  2014-6-7下午2:35:52
* @Description 图片识别并过滤
* @Class List
*      1.
*  @Function List
*   1.
*  @History
*      <author>    <time>                     <version >                  <desc>
*        King   2014-6-7下午2:35:52      Beta 1.0                    第一次建立该文件
*        King 2020年6月1日14:21 stable 1.0 审定
*
*/
namespace Tiny\Image;

/**
 * 图片识别并过滤类
 * @package
 * @since 2014-6-7下午2:36:51
 * @final 2014-6-7下午2:36:51
 */
class Filter
{

	/**
	*  参照颜色值
	* @var array
	*/
	protected $_colors = array ();

	/**
	* 容差值
	* @var int
	*/
	protected $_tolerance = 20;

	/**
	* 默认过滤规则 如果颜色总数不超过3 黑加白色超过90% 白超过其中60%就判断需要过滤
	* @param string　$pic 图片地址或者字符串
	* @return bool  true为需要过滤 false为不需要过滤
	*/
	public static function isFilterByDefault($pic, $isFile = false)
	{
		$colors = array (array ('red' => 0xff, 'green' => 0xff, 'blue' => 0xff),
				array ('red' => 0xc0, 'green' => 0xc0, 'blue' => 0xc0),
				array ('red' => 0x00, 'green' => 0x00, 'blue' => 0x00),
				array ('red' => 0x00, 'green' => 0x64, 'blue' => 0x00),
				array ('red' => 0x00, 'green' => 0x00, 'blue' => 0xff),
				array ('red' => 0x00, 'green' => 0xff, 'blue' => 0x00),
				array ('red' => 0xff, 'green' => 0x00, 'blue' => 0x00),
				array ('red' => 0xff, 'green' => 0xff, 'blue' => 0x00),
				array ('red' => 0xff, 'green' => 0x00, 'blue' => 0xff),
				array ('red' => 0x00, 'green' => 0xff, 'blue' => 0xff));

		$ins = new self($colors, 20);
		$data = $ins->getColorSortSets($pic, $isFile);
		if (! is_array($data) || empty($data))
		{
			return false;
		}
		$colorIds = array_keys($data);
		if (count($colorIds) > 4)
		{
			return false;
		}
		if (! (($colorIds[0] == 0 && $colorIds[1] == 2) || ($colorIds[0] == 0 && $colorIds[1] == 1)))
		{
			return false;
		}
		$total = 0;
		foreach ($data as $d)
		{
			$total += $d;
		}
		$wTotal = $data[$colorIds[0]];
		$bTotal = $data[$colorIds[1]];
		if (($wTotal + $bTotal) / $total < 0.90)
		{
			return false;
		}
		if ($wTotal / $total < 0.85)
		{
			return false;
		}
		if ($wTotal / $total < 0.7)
		{
			return false;
		}
		return true;
	}

	/**
	*  构造函数
	* @param array $colors
	* @return void
	*/
	public function __construct(array $colors = null, $tolerance = null)
	{
		if (null !== $colors)
		{
			$this->_colors = $colors;
		}
		if (null !== $tolerance)
		{
			$this->_tolerance = $tolerance;
		}
	}

	/**
	* 设置参照颜色值数组
	* @param array $colors 颜色数组
	* @return self
	*/
	public function setColors(array $colors)
	{
		$this->_colors = $colors;
		return $this;
	}

	/**
	* 设置容差值
	* @param int $tolerance 容差值
	* @return self
	*/
	public function setTolerance($tolerance)
	{
		$this->_tolerance = $tolerance;
		return $this;
	}

	/**
	* 获取颜色值的排序集合
	* @param string $pic 图片文件地址或者字符串
	* @param bool $isFile 标示是否输入文件
	* @return array
	*/
	public function getColorSortSets($pic, $isFile = false)
	{
		$image = $this->_createImage($pic, $isFile);
		if (! $image)
		{
			return null;
		}

		$im = $image[0];
		$width = $image[1];
		$height = $image[2];
		$colorData = array ();
		$colorSet = array ();
		for ($i = 0; $i < $width; $i++)
		{
			for ($m = 0; $m < $height; $m++)
			{
				$colorIndex = imagecolorat($im, $i, $m);
				$colorTran = imagecolorsforindex($im, $colorIndex);
				$alpha = $colorTran['alpha'];
				unset($colorTran['alpha']);
				if (100 < $alpha)
				{
					continue;
				}
				$key = $colorTran['red'] . ':' . $colorTran['green'] . ':' . $colorTran['blue'];
				if (! isset($colorSet[$key]))
				{
					$colorSet[$key] = $colorTran;
				}
				$colorData[$key]++;
			}
		}
		$set = array ();
		arsort($colorData);
		foreach ($colorData as $key => $value)
		{
			if ($value < 10)
			{
				break;
			}
			$colorTran = $colorSet[$key];
			foreach ($this->_colors as $colorid => $color)
			{
				if ($this->_isValidColor($color['red'], $colorTran['red']) && $this->_isValidColor($color['green'], $colorTran['green']) && $this->_isValidColor($color['blue'], $colorTran['blue']))
				{
					$set[$colorid] += $value;
				}
			}
		}
		imagedestroy($im);
		arsort($set);
		return $set;
	}

	/**
	 * 验证参照颜色值
	 * @param $confVal string 默认值
	 * @param $val
	 * @return
	 */
	protected function _isValidColor($def, $cur)
	{
		return $cur >= $def - $this->_tolerance && $cur <= $def + $this->_tolerance;
	}

	/**
	* 获取图像对象和图片宽度以及高度
	* @param $pic
	* @return
	*/
	protected function _createImage($pic, $isFile = false)
	{
		$im = null;
		if (! $isFile)
		{
			$im = imagecreatefromstring($pic);
			return array ($im, imagesx($im), imagesy($im));
		}
		if (! is_file($pic))
		{
			return false;
		}

		$size = getimagesize($pic);
		if (! $size)
		{
			return false;
		}

		$width = $size[0];
		$height = $size[1];
		switch ($size['mime'])
		{
			case 'image/png' :
				$im = imagecreatefrompng($pic);
				break;
			case 'image/gif' :
				$im = imagecreatefromjpeg($pic);
				break;
			case 'image/gif' :
				$im = imagecreatefromgif($pic);
				break;
			default :
				break;
		}
		if (! $im)
		{
			return false;
		}
		return array ($im, $width, $height);
	}
}
?>