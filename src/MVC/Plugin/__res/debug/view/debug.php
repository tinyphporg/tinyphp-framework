<?php
if ($env['RUNTIME_MODE'] == $env['RUNTIME_MODE_CONSOLE'])
{
    echo "\n----debug----\n";
    echo $env['FRAMEWORK_NAME'] . ' ' . $env['FRAMEWORK_VERSION'] . "\n";
    echo "Time  : " . $debugInterval . " S\n";
    echo "Memory: " . $debugMemory ."M\n";

    if (is_array($datamessage))
    {
        $sqlSource = '';
        $time=0;
        foreach ($datamessage as $key => $value)
        {
            $sqlSource .= $value['engine'] . ':' .   " SQL TIME: "  . $value['time'] . "S\n" . $value['sql'] . "\n" ;
            $time += $value['time'];
        }
        echo $sqlSource . "\n";
        echo "sql exec:" . $time . "\n";
    }

    if (is_array($debugExceptions) && !empty($debugExceptions))
    {
        foreach ($debugExceptions as $e)
        {
            echo $e['handler'] .":\n  " . $e['message'] . "\n    In " . $e['file'] . ' on Line ' . $e['line'] . "\n";
        }
    }
die;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type="text/css">
html,body {
	margin: 0;
	text-align: left
}

.debug_Box p {
	margin: 0;
}
/*背景*/
.debug_YellowBG {
	background: #fcfdd1;
}

.debug_YellowBG2 {
	background: #fcff7f;
}
/*字体颜色*/
.debug_Red {
	color: #F00;
}

.debug_Pink {
	color: #ff00ff;
}

.debug_DRed {
	color: #6a0b0b;
}

.debug_Blue {
	color: #0000ff;
}

.debug_DYellow {
	color: #ff7200;
}

.debug_DGreen {
	color: #195011;
}

.debug_Green {
	color: green;
}

.debug_Gray {
	color: #666666;
}
/*字体样式*/
.debug_U {
	text-decoration: underline;
}

.debug_B {
	font-weight: bold;
}
/*边框*/
.debug_Box {
	border: 1px solid #ffce5a;
	padding: 10px 20px 30px;
	line-height: 25px;
}
/*代码样式*/
.debug_Code {
	background: #FFF;
	margin-top: 20px;
	border: 1px solid #215119;
	font-size: 13px;
	height: 250px;
	line-height: 16px;
	overflow: hidden;
}

.debug_Code p {
	padding: 3px 10px;
	line-height: 16px;
}

.debug_Code .line {
	float: left;
	background: #EEE;
	color: #666;
	border-right: 1px solid #CCC;
	padding: 3px;
	font-size: 13px;
	line-height: 16px;
	margin-right: 10px;
}

.debug_Code .line p {
	padding: 3px 8px;
}

.debug_flexMenu {
	margin-top: 10px;
	float: left;
	width: 100%;
	border: 1px solid #AAA;
	border-top: none;
}

.debug_flexMenu h4 {
	cursor: pointer;
	font-size: 13px;
	height: 18px;
	display: block;
	background: #FFF1C8;
	border-top: 1px solid #666;
	border-bottom: 1px solid #e2e2e2;
	padding: 8px 5px;
	color: #F00;
	font-weight: 400;
	margin: 0px
}

.debug_flexMenu h4 .jt_down {
	cursor: pointer;
	float: right;
	border: 4px solid #E1FAFF;
	border-color: #F90 #FFF1C8 #FFF1C8;
	font-size: 0;
	line-height: 0;
	margin-top: 0px;
}

.debug_flexMenu h4 .jt_up {
	cursor: pointer;
	float: right;
	border: 4px solid #E1FAFF;
	border-color: #FFF1C8 #FFF1C8 #F90;
	font-size: 0;
	line-height: 0;
	margin-top: 0px;
}

.debug_flexMenu .cont {
	display: none;
	border-top: none;
	background: #FFF;
	padding: 5px 8px 0px;
	font: 12px arial;
}

.debug_flexMenu .cont_selected {

}

.debug_flexMenu p {
	height: 12px;
	padding: 4px;
	margin: 0px;
	line-height: 15px;
}
</style>

</head>
<body>
<?php
if (count($debugExceptions) > 0)
{
	include_once ('exception.php');
}
$sqlSource = '';
if (is_array($datamessage))
{
	foreach ($datamessage as $key => $value)
	{
		$sqlSource .= '<p > <span style="padding-right:5px">' . $value['engine'] . ' : </span><span style="color:green">' . $value['sql'] . '</span>';
		$sqlSource .= ' <span>耗时: </span><span style="color:red">' . $value['time'] . '<span style="color:blue"> s.</span> </span>';
		$sqlSource .= '</p>';
		$time += $value['time'];
	}
	$sqlTotalTime = $time;
}
?>

<div id="debug_FileMent" class="debug_flexMenu">
		<div onclick="__Debug.doDown('debug_ExecBox')">
			<h4>
				<span style="float: left">
					页面执行时间
					<span class="debug_DRed"> <?=$debugInterval?> </span>
					second(s),
					<span class="debug_DRed"><?=$debugMemory?></span>
					M.
				</span>
				<span id="debug_ExecBox_jt" class="jt_down"></span>
			</h4>
			<div class="cont" style="display: block" id="debug_ExecBox">
 <?php
	$controller = $request->getController();
	$action = $request->getAction();

	echo '<p><span class="debug_Green">当前路径: </span>' . $request->rawUrl . '</p>';
	echo '<p><span class="debug_Green">来源路径: </span>' . $request->urlReferrer . '</p>';
	echo '<p><span class="debug_Green">控制器: </span>' . $controller . '</p>';
	echo '<p><span class="debug_Green">动作Id: </span>' . $action . '(' . $action . ')</p>';
	?>
    </div>
		</div>


		<div onclick="__Debug.doDown('debug_SqlHelper', this)">
			<h4>
				<span style="float: left;">
					SQLHelper,累计执行时间
					<span class="debug_DRed"> <?=$sqlTotalTime?> </span>
					second(s),
					<span class="debug_DRed"><?=$s?></span>
					querys.
				</span>

				<span id="debug_SqlHelper_jt" class="jt_up"></span>
			</h4>
			<div id="debug_SqlHelper" style="display: block" class="cont">
<?=$sqlSource?>

</div>
		</div>
		<div onclick="__Debug.doDown('debug_View')">
			<h4>
				<span style="float: left">Views</span>
				<span id="debug_View_jt" class="jt_down"></span>
			</h4>
			<div class="cont" style="display: block" id="debug_View">
<?php
foreach ($debugViewPaths as $key => $value)
{
	echo '<p><b style="color:blue">视图文件<span >' . basename($key) . '</span></b></p>';
	echo '<p><span class="debug_Green">视图路径:&nbsp;</span>' . $key . '</p>';
	echo '<p><span class="debug_Green">视图引擎:&nbsp;</span>' . $value . '</p>';
}
echo '<p><span class="debug_Green">视图变量:&nbsp;</span>' . join(',', array_keys($debugViewAssign)) . '</p>';
?>
    </div>
		</div>
		<div onclick="__Debug.doDown('debug_Const')">
			<h4>
				<span style="float: left">Variables</span>
				<span id="debug_Const_jt" class="jt_down"></span>
			</h4>
			<div class="cont" id="debug_Const">
 <?php
	$const = get_defined_constants(true);
	$const = $const['user'];
	$requestData = $request->getRequestData();
	echo '<p class="debug_DYellow">以下为未经框架处理的原生数据,仅供参考,请勿直接调用$_GET,$_POST,$_COOKIE三个全局数组.应在HttpRequest实例化后,以getQueryString(),getPost(),getCookie()获取.</p><p><b style="color:blue">Const</b></p>';
	foreach ($const as $key => $value)
	{
		if (is_bool($value))
		{
			if ($value)
			{
				$value = 'true';
			}
			else
			{
				$value = 'false';
			}
			$value = '<span style="color:blue">&nbsp;' . $value . '&nbsp;</span>';
		}
		elseif (is_int($value))
		{
			$value = '<span style="color:red">&nbsp;' . $value . '&nbsp;</span>';
		}
		elseif (is_string($value))
		{
			$value = '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>';
		}
		echo '<p>' . $key . ' = ' . $value . '</p>';
	}
	echo '<p><b style="color:blue" >$_GET</b></p>';
	foreach ($requestData['get'] as $key => $value)
	{
		echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span></p>';
	}
	echo '<p><b style="color:blue">$_POST</b></p>';
	foreach ($requestData['post'] as $key => $value)
	{
		echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
	}
	echo '<p><b style="color:blue">$_COOKIE</b></p>';
	foreach ($requestData['cookie'] as $key => $value)
	{
		echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
	}
	if (is_array($_SESSION))
	{
		echo '<p><b style="color:blue">$_SESSION</b></p>';
		foreach ($_SESSION as $key => $value)
		{
			echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
		}
	}
	echo '<p><b style="color:blue">$_SERVER</b></p>';
	foreach ($requestData['server'] as $key => $value)
	{
		echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
	}
	echo '<p><b style="color:blue">$argv</b></p>';
	if (is_array($_SERVER['argv']))
	{
		foreach ($_SERVER['argv'] as $key => $value)
		{
			echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
		}
	}
	?>
    </div>
		</div>
		<div onclick="__Debug.doDown('debug_Include')">
			<h4>
				<span style="float: left">Includes</span>
				<span id="debug_Include_jt" class="jt_down"></span>
			</h4>
			<div class="cont" id="debug_Include">
 <?php
	echo '<p><b style="color:blue" >INCLUDE_FILES</b></p>';
	$e = get_included_files();
	foreach ($e as $key => $value)
	{
		echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span></p>';
	}
	$e = get_loaded_extensions();
	echo '<p><b style="color:blue">INCLUDE EXTENSIONS</b></p>';
	foreach ($e as $key => $value)
	{
		echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
	}
	$e = get_include_path();
	echo '<p><b style="color:blue">INCLUDE PATHS</b></p>';
	if (is_array($e))
	{
		foreach ($e as $key => $value)
		{
			echo '<p>' . $key . ' = ' . '<span style="color:green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
		}
	}
	?>
    </div>
		</div>

	</div>
</body>
<script type="text/javascript">
var TinyDebug =  function(window)
{
    var document = window.document;
    var __D = function(id)
    {
        return document.getElementById(id);
    }
    __D.traversalChildNodes = function (f) {
        if (!f) { return; }
        for (var i in f.childNodes)
        {
            if (f.childNodes[i].nodeType == 3)
            {
                if (f.childNodes.length == 1)
                {
                    f.innerHTML = __D.highLight(f.childNodes[i].data);
                }
                else if (f.childNodes[i + 1])
                {
                    var obj = document.createElement('SPAN');
                    obj.innerHTML = __D.highLight(f.childNodes[i].data);
                    f.insertBefore(obj, f.childNodes[i + 1]);
                    f.childNodes[i].data = '';
                }
                else if (f.childNodes.length == i + 1)
                {
                    var obj = document.createElement('SPAN');
                    obj.innerHTML = __D.highLight(f.childNodes[i].data);
                    f.appendChild(obj)
                    f.childNodes[i].data = '';
                }
            }
            else            {
                __D.traversalChildNodes(f.childNodes[i]);
            }
        }

    }

    __D.traversalChildNodesSql = function(f)
    {
        if (!f) { return; }
        for (var i in f.childNodes)
        {
            if (f.childNodes[i].nodeType == 3)
            {
                if (f.childNodes.length == 1)
                {
                    f.innerHTML = __D.highLightSql(f.childNodes[i].data);
                }
                else if (f.childNodes[i + 1])
                {
                    var obj = document.createElement('SPAN');
                    obj.innerHTML = __D.highLightSql(f.childNodes[i].data);
                    f.insertBefore(obj, f.childNodes[i + 1]);
                    f.childNodes[i].data = '';
                }
                else if (f.childNodes.length == i + 1)
                {
                    var obj = document.createElement('SPAN');
                    obj.innerHTML = __D.highLightSql(f.childNodes[i].data);
                    f.appendChild(obj)
                    f.childNodes[i].data = '';
                }
            }
            else
            {
                __D.traversalChildNodesSql(f.childNodes[i]);
            }
        }
    }

    __D.loadDebug = function(f) {
    	f = __D(f);

    	if (f && f.style.display != 'none' && !this.isload)
    	{
    		f.innerHTML = '<iframe marginheight="0" marginwidth"0" frameborder=0 style="height:600px;width:99%;border:0" src="?c=debug&a=loadstand"></iframe>';
    	this.isload = true;
    	}
    	}


    __D.highLight = function(string) {
        if (!string)
        {
            return string;
        }
        string = string.replace(/(('.+')|(".+"))/gm, "<span style=\"color:green\">$1</span>");
        string = string.replace(/(public|var|if|else|elseif|show|array|__construct|static|private|protected|class|throw|show|\->|;|throw|\(|\)|\{|\}|\=>|return|new|\$this|(\:\:)|function|self)/igm, "<span style=\"color:blue\">$1</span>");
        string = string.replace(/(\/\*.*\*\/)/gm, "<span style=\"color:green\">$1</span>");
        string = string.replace(/(\$[A-Za-z]{1}([a-zA-Z0-9]*)?)/gm, "<span style=\"color:#6a0b0b\">$1</span>");
        string = string.replace(/(\s+\d+\s+)/gm, "<span style=\"color:red\">$1</span>");
        string = string.replace(/(\/\/.*)/gm, "<span style=\"color:#aaa\">$1</span>");
        return string;
    }

    __D.highLightSql = function(string)
    {
        if (!string)
        {
            return string;
        }
        // string = string.toLowerCase();
        string = string.replace(/(('.+')|(".+")|(`.+`))/gm, "<span style=\"color:green\">$1</span>");
        string = string.replace(/(INSERT|SELECT|ORDER|BY|FROM|JOIN|FROM|LIMIT|DESC|AND|ON|\(|\)||)/igm, "<span style=\"color:blue\">$1</span>");
        string = string.replace(/(\s+\d+\s+)/gm, "<span style=\"color:red\">$1</span>");
        return string;
    }

    __D.doDown = function(id)
    {

        B = __D(id);
        var jt = __D(id + '_jt');

        if (!B['isdown'])
        {
            B.isdown = true;
            B.style.display = "block";
            jt.className = 'jt_down';
        }
        else
        {
            B.isdown = false;
            B.style.display = 'none';
            jt.className = 'jt_up';
        }

    }

    //得到页面长和宽
    __D.getWH = function() {
        var s;
        var r = {};
        if (!arguments[0])
        {
            s = document.getElementsByTag('BODY')[0];
        }
        else
        {
            s = arguments[0];
        }
        r.width = Math.max(s.scrollWidth, s.clientWidth);
        r.height = Math.max(s.scrollHeight, s.clientHeight);
        return r;
    }

    __D.getHeight = function()
    {
        var body = __D.getWH(arguments[0]);
        return body.height;
    }
    __D.initialize = function()
    {
        if(window.onload)
        {
            window.oldOnload = window.onload;
            window.onload = function() {
                window.oldOnload();
                setTimeout("__Debug.traversalChildNodes(__Debug('debug_SqlHelper'));", 1000);
                setTimeout("__Debug.traversalChildNodes(__Debug('debug_Code'));", 1000);
            }
        }
        else
        {
            window.onload = function() {
                setTimeout("__Debug.traversalChildNodes(__Debug('debug_SqlHelper'));", 1000);
                setTimeout("__Debug.traversalChildNodes(__Debug('debug_Code'));", 1000);
            }
        }
    }

    __D.initialize();
    window.__Debug  = window.__D = __D;
}(window);

</script>
</html>
<!--调试代码结束-->