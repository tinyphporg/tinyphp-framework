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

/**
 * 异常类型字符
 * @var array
 */
const EXCEPTION_TYPES = [
	0 => 'Fatal error',
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
	E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
    E_NOFOUND => '404 NOT FOUND'
];

$firstE = array_shift($debugExceptions);
if (!$firstE)
{
    return;
}

$fileLines = file($firstE['file']);

$startLine = $firstE['line'] - 7;
$endLine = $firstE['line'] + 5;
if ($startLine < 0)
{
    $startLine = 0;
}
$totalLine = count($fileLines);
if ($endLine >= $totalLine)
{
    $endLine = $totalLine - 1;
}

$srcCode = [];
$debugCode = '<div id ="debug_code" class="code"><div class="line">';
for ($i = $startLine ; $i <= $endLine; $i++)
{
    $sourceCode[$i] = $fileLines[$i];
}
foreach ($sourceCode as $key => $value)
{
    if ($firstE['line'] == ($key + 1))
	{
	   $debugCode .= '<p class="font_red">' . ($key + 1) . '.</p>';
	}
	else
	{
	   $debugCode .= '<p>' . ($key + 1) . '.</p>';
	}
}
$debugCode .= ' </div><div class="">';
foreach ($sourceCode as $key => $value)
{
    if ($firstE['line'] == $key + 1)
	{
	   $debugCode .= '<p class="bg_yellow2">' . $value . '</p>';
	}
	else
	{
	   $debugCode .= '<p>' . $value . '</p>';
	}
}
$debugCode .= '</div></div>';

?>

<div class="box bg_yellow">
	<h2 class="font_red font_underline"><?=EXCEPTION_TYPES[$firstE['level']]?></h2>
	<p>
		<span class="font_red_d "><?=$firstE['handler']?>: <?=$firstE['message']?></span>
	</p>
	<p>
		Line:
		<span class="font_red"> <?=$firstE['line']?></span>
	</p>
	<p>File: <?=$firstE['file']?></p>
	<p>
		<span class=>Trace: <?echo str_replace('#','<br /># File:', $firstE['traceString'])?></span>
	</p>
<?=$debugCode?>
<?php foreach ($exception as $key => $value){echo '<p>' . EXCEPTION_TYPES['handler'] . ' ' . EXCEPTION_TYPES[$value['level']] . ':' . $value['message'] . 'On' . $value['file'] . ' Line ' . $value['line'] . '</p>';
}
?>
</div>