<?php
/**
 * @Copyright (C), 2011-, King.$i
 * @Name Exception.php
 * @Author King
 * @Version Beta 1.0
 * @Date: Thu Dec 22 18:28 11 CST 2011
 * @Description
 * @Class List
 * 1.异常信息输出视图
 * @Function List
 * 1.
 * @History
 * <author> <time> <version > <desc>
 * King Thu Dec 22 18:28:11 CST 2011 Beta 1.0 第一次建立该文件
 */
$exceptionType = array (
	0 => 'Fatal error',
	E_DATA => 'E_DATA',
	E_NOFOUND => 'E_NOFOUND',
	E_CACHE => 'E_CACHE',
	E_ERROR => 'ERROR',
	E_WARNING => 'WARNING',
	E_PARSE => 'PARSING ERROR',
	E_NOTICE => 'NOTICE',
	E_CORE_ERROR => 'CORE ERROR',
	E_CORE_WARNING => 'CORE WARNING',
	E_COMPILE_ERROR => 'COMPILE ERROR',
	E_COMPILE_WARNING => 'COMPILE WARNING',
	E_USER_ERROR => 'USER ERROR',
	E_USER_WARNING => 'USER WARNING',
	E_USER_NOTICE => 'USER NOTICE',
	E_STRICT => 'STRICT NOTICE',
	E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR');
;
$exception = $debugExceptions;
$firstE = array_shift($exception);
if (is_array($firstE))
{
	$file = file($firstE['file']);
	$sourceCode = array ();
	$startLine = $firstE['line'] - 7;
	$toLine = $firstE['line'] + 5;
	if ($startLine < 0)
	{
		$startLine = 0;
	}
	$totalLine = count($file);
	if ($toLine >= $totalLine)
	{
		$toLine = $totalLine - 1;
	}
	$debugCode = '<div id ="debug_Code" class="debug_Code "><div class="line">';
	for ($i = $startLine; $i <= $toLine; $i++)
	{
		$sourceCode[$i] = $file[$i];
	}
	foreach ($sourceCode as $key => $value)
	{
		if ($firstE['line'] == ($key + 2))
		{
			$debugCode .= '<p class="debug_Red">' . ($key + 2) . '.</p>';
		}
		else
		{
			$debugCode .= '<p>' . ($key + 2) . '.</p>';
		}
	}
	$debugCode .= ' </div><div class="code">';
	foreach ($sourceCode as $key => $value)
	{
		if ($firstE['line'] == $key + 1)
		{
			$debugCode .= '<p class="debug_YellowBG2">' . $value . '</p>';
		}
		else
		{
			$debugCode .= '<p>' . $value . '</p>';
		}
	}
	$debugCode .= '</div></div>';
}
?>

<div class="debug_Box debug_YellowBG">
	<p>
	
	
	<h2 class="debug_Red debug_U"><?=$firstE['handle']?></h2>
	</p>
	<p>
		<span class="debug_DRed"><?=$exceptionType[$firstE['level']]?>: <?=$firstE['message']?></span>
	</p>

	<p>
		Line:
		<span class="debug_Red"> <?=$firstE['line']?></span>
	</p>
	<p>File: <?=$firstE['file']?></p>
	<p>
		<span class=>Trace: <?echo str_replace('#','<br /># File:', $firstE['traceToString'])?></span>
	</p>
<?=$debugCode?>
<?php foreach ($exception as $key => $value){echo '<p>' . $exceptionType['handle'] . ' ' . $exceptionType[$value['level']] . ':' . $value['message'] . 'On' . $value['file'] . ' Line ' . $value['line'] . '</p>';
}
?>
</div>