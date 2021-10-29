<?php
if ($env['RUNTIME_MODE'] == $env['RUNTIME_MODE_CONSOLE'])
{
    echo "\n----debug----\n";
    echo $env['FRAMEWORK_NAME'] . ' ' . $env['FRAMEWORK_VERSION'] . "\n";
    echo "Time  : " . $debugInterval . " S\n";
    echo "Memory: " . $debugMemory . "M\n";
    
    if (is_array($debugDatamessage))
    {
        $sqlSource = '';
        $time = 0;
        foreach ($debugDatamessage as $key => $value)
        {
            $sqlSource .= $value['engine'] . ':' . " SQL TIME: " . $value['time'] . "S\n" . $value['sql'] . "\n";
            $time += $value['time'];
        }
        echo $sqlSource . "\n";
        echo "sql exec:" . $time . "\n";
    }
    
    if (is_array($debugExceptions) && !empty($debugExceptions))
    {
        foreach ($debugExceptions as $e)
        {
            echo $e['handler'] . ":\n  " . $e['message'] . "\n    In " . $e['file'] . ' on Line ' . $e['line'] . "\n";
        }
    }
    die();
}
?>
<!DOCTYPE html>
<html class="debug">
<head>
<style type="text/css">
.debug { margin: 0;text-align: left}
.debug body { margin: 0;text-align: left}
.debug .box p {margin: 0;}
.debug .bg_yellow {background: #fcfdd1;}
.debug .bg_yellow2 {background: #fcff7f;}
.debug .font_red {color: #F00;}
.debug .font_pink {color: #ff00ff;}
.debug .font_red_d {color: #6a0b0b;}
.debug .font_blue {color: #0000ff;}
.debug .font_yellow_d {color: #ff7200;}
.debug .font_green_d {color: #195011;}
.debug .font_green {color: green;}
.debug .font_gray {color: #666666;}
.debug .font_underline {text-decoration: underline;}
.debug .font_bold {font-weight: bold;}
.debug .box {border: 1px solid #ffce5a;padding: 10px 20px 30px;line-height: 25px;}
.debug .code {background: #FFF;margin-top: 20px;border: 1px solid #215119;font-size: 13px;height: 250px;line-height: 16px;overflow: hidden;}
.debug .code p {padding: 3px 10px;line-height: 16px;}
.debug .code .line {float: left;background: #EEE;color: #666;border-right: 1px solid #ccc;padding: 3px;font-size: 13px;line-height: 16px;margin-right: 10px;}
.debug .code .line p {padding: 3px 8px;}
.debug .flex_menu {margin-top: 10px;float: left;width: 100%;border: 1px solid #AAA;border-top: none;}
.debug .flex_menu .jt{cursor: pointer;font-size: 13px;height: 18px;display: block;background: #FFF1C8;border-top: 1px solid #666;border-bottom: 1px solid #e2e2e2;padding: 8px 5px;color: #F00;font-weight: 400;margin: 0px}
 .debug .flex_menu .jt .jt_down {cursor: pointer;float: right;border: 4px solid #E1FAFF;border-color: #F90 #FFF1C8 #FFF1C8;font-size: 0;line-height: 0;margin-top: 0px;}
.debug .flex_menu .jt .jt_up {cursor: pointer;float: right;border: 4px solid #E1FAFF;border-color: #FFF1C8 #FFF1C8 #F90;font-size: 0;line-height: 0;margin-top: 0px;}
.debug .flex_menu .cont {display: none;border-top: none;background: #FFF;padding: 5px 8px 0px;font: 12px arial;}
.debug .flex_menu .cont_selected {}
.debug .flex_menu p {height: 12px;padding: 4px;margin: 0px;line-height: 15px;}
#debug_docs{width:100%;height:2000px}
#debug_docs iframe{width:100%;height:100%}
</style>
</head>
<body>

<?php
include_once ('exception.php');

$sqlSource = '';
foreach ((array)$debugDatamessage as $key => $value)
    {
        $sqlSource .= '<p > <span style="padding-right:5px">' . $value['engine'] . ' : </span><span class="font_green">' . $value['sql'] . '</span>';
        $sqlSource .= ' <span>耗时: </span><span class="font_red">' . $value['time'] . '<span class="font_blue"> s.</span> </span>';
        $sqlSource .= '</p>';
        $time += $value['time'];
    }
    $sqlTotalTime = $time;
?>

<div id="debug_FileMent" class="flex_menu">
		<div onclick="__Debug.doDown('debug_ExecBox')">
			<h4 class="jt">
				<span style="float: left"> 页面执行时间 <span class="font_red_d"> <?=$debugInterval?> </span>
					second(s), <span class="font_red_d"><?=$debugMemory?></span> M.
				</span> <span id="debug_ExecBox_jt" class="jt_down"></span>
			</h4>
			<div class="cont" style="display: block" id="debug_ExecBox">
 <?php
echo '<p><span class="font_green">当前路径: </span>' . $request->url . '</p>';
echo '<p><span class="font_green">来源路径: </span>' . $request->referrer . '</p>';

echo '<p><span class="font_green">路由器: </span>' . $debugRouterName . '<span class="font_green"> Matched URL: </span>' . $debugRouterUrl . '<span class="font_green"> Matched Router Params: </span>' . var_export($debugRouterParams, TRUE) . '</p>';
echo '<p><span class="font_green">控制器: </span>' . $debugControllerList . '</p>';

echo '<p><span class="font_green">动作Id: </span>' . $actionName . '('. $controllerName . '->' . $actionName . 'Action)</p>';
echo '<p><span class="font_green">模型层: </span>' . $debugModelList .'</p>';
?>
    </div>
		</div>


		<div onclick="__Debug.doDown('debug_SqlHelper', this)">
			<h4 class="jt">
				<span style="float: left;"> SQLHelper,累计执行时间 <span
					class="font_red_d"> <?=$sqlTotalTime?> </span> second(s), <span
					class="font_red_d"><?=$s?></span> querys.
				</span> <span id="debug_SqlHelper_jt" class="jt_up"></span>
			</h4>
			<div id="debug_SqlHelper" style="display: block" class="cont">
<?=$sqlSource?>

</div>
		</div>
		<div onclick="__Debug.doDown('debug_View')">
			<h4 class="jt">
				<span style="float: left">Views</span> <span id="debug_View_jt" class="jt_down"></span>
			</h4>
			<div class="cont" style="display: block" id="debug_View">
<?php
echo '<p><span class="font_green">视图变量:&nbsp;</span>$' . join(', $', array_keys($debugViewAssign)) . '</p>';
foreach ($debugViewPaths as $key => $value)
{
    echo '<p><b style="color:blue">视图模板<span >' . $key . '</span></b></p>';
    echo '<p><span class="font_green">视图路径:&nbsp;</span>' . $value[0] . '</p>';
    echo '<p><span class="font_green">视图引擎:&nbsp;</span>' . $value[1] . '</p>';
}

?>
    </div>
		</div>
		<div onclick="__Debug.doDown('debug_Const')">
			<h4 class="jt">
				<span style="float: left">Variables</span> <span id="debug_Const_jt"
					class="jt_down"></span>
			</h4>
			<div class="cont" id="debug_Const">
 <?php
$const = get_defined_constants(TRUE);
$const = $const['user'];
$requestData = $request->getRequestData();
echo '<p class="font_yellow_d">以下为未经框架处理的原生数据,仅供参考,请勿直接调用$_GET,$_POST,$_COOKIE三个全局数组.应在HttpRequest实例化后,以getQueryString(),getPost(),getCookie()获取.</p><p><b style="color:blue">Const</b></p>';
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
        $value = '<span class="font_blue">&nbsp;' . $value . '&nbsp;</span>';
    }
    elseif (is_int($value))
    {
        $value = '<span class="font_red">&nbsp;' . $value . '&nbsp;</span>';
    }
    elseif (is_string($value))
    {
        $value = '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>';
    }
    echo '<p>' . $key . ' = ' . $value . '</p>';
}
echo '<p><b style="color:blue" >$_GET</b></p>';
foreach ($requestData['get'] as $key => $value)
{
    echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span></p>';
}
echo '<p><b style="color:blue">$_POST</b></p>';
foreach ($requestData['post'] as $key => $value)
{
    echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
}
echo '<p><b style="color:blue">$_COOKIE</b></p>';
foreach ($requestData['cookie'] as $key => $value)
{
    echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
}
if (is_array($_SESSION))
{
    echo '<p><b style="color:blue">$_SESSION</b></p>';
    foreach ($_SESSION as $key => $value)
    {
        echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
    }
}
echo '<p><b style="color:blue">$_SERVER</b></p>';
foreach ($requestData['server'] as $key => $value)
{
    echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
}
echo '<p><b style="color:blue">$argv</b></p>';
if (is_array($_SERVER['argv']))
{
    foreach ($_SERVER['argv'] as $key => $value)
    {
        echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
    }
}
?>
    </div>
		</div>
		<div onclick="__Debug.doDown('debug_Include')">
			<h4 class="jt">
				<span style="float: left">Includes</span> <span
					id="debug_Include_jt" class="jt_down"></span>
			</h4>
			<div class="cont" id="debug_Include">
 <?php
echo '<p><b class="font_blue" >INCLUDE_FILES</b></p>';
$e = get_included_files();
foreach ($e as $key => $value)
{
    echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span></p>';
}
$e = get_loaded_extensions();
echo '<p><b class="font_blue">INCLUDE EXTENSIONS</b></p>';
foreach ($e as $key => $value)
{
    echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
}
$e = get_include_path();
echo '<p><b class="font_blue">INCLUDE PATHS</b></p>';
if (is_array($e))
{
    foreach ($e as $key => $value)
    {
        echo '<p>' . $key . ' = ' . '<span class="font_green">&nbsp;"' . $value . '"&nbsp;</span>' . '</p>';
    }
}

?>
    </div>
		</div>

		<div onclick="__Debug.doDown('debug_docs')">
			<h4 class="jt">
				<span style="float: left">Docs</span> <span id="debug_Doc_jt" class="jt_up"></span>
			</h4>
			<div class="cont" style="display: block" id="debug_docs">
			<iframe id="debug_docs_iframe" width="100%" height="4000px" frameBorder="0" scrolling="no" src="<?=$debugDocsUrl?>"> </iframe>
			</div>
			</div>
			

	</div>
</body>
<script type="text/javascript">

(function(window) {
    var document = window.document;
    var __D = function(id) {
        return document.getElementById(id);
    }
    __D.traversalChildNodes = function (f) {
        if (!f) { return; }
        for (var i in f.childNodes) {
            if (f.childNodes[i].nodeType != 3) {
            	__D.traversalChildNodes(f.childNodes[i]);
            	continue;
            }
            if (f.childNodes.length == 1) {
            	f.innerHTML = __D.highLight(f.childNodes[i].data);
            }
            else if (f.childNodes[i + 1]) {
                    var obj = document.createElement('SPAN');
                    obj.innerHTML = __D.highLight(f.childNodes[i].data);
                    f.insertBefore(obj, f.childNodes[i + 1]);
                    f.childNodes[i].data = '';
            }
            else if (f.childNodes.length == i + 1) {
                    var obj = document.createElement('SPAN');
                    obj.innerHTML = __D.highLight(f.childNodes[i].data);
                    f.appendChild(obj)
                    f.childNodes[i].data = '';
 			}
		}
    }    
    __D.traversalChildNodesSql = function(f) {
        if (!f) { return; }
        for (var i in f.childNodes)
        {
            if (f.childNodes[i].nodeType != 3){
            	__D.traversalChildNodesSql(f.childNodes[i]);
            	continue;
            }
            if (f.childNodes.length == 1) {
            	f.innerHTML = __D.highLightSql(f.childNodes[i].data);
            }
            else if (f.childNodes[i + 1]) {
            	var obj = document.createElement('SPAN');
                obj.innerHTML = __D.highLightSql(f.childNodes[i].data);
                f.insertBefore(obj, f.childNodes[i + 1]);
                f.childNodes[i].data = '';
            }
            else if (f.childNodes.length == i + 1) {
                var obj = document.createElement('SPAN');
                obj.innerHTML = __D.highLightSql(f.childNodes[i].data);
                f.appendChild(obj)
                f.childNodes[i].data = '';
            }
    	}
    }

    __D.loadDebug = function(f) {
    	f = __D(f);
    	if (f && f.style.display != 'none' && !this.isload) {
    		f.innerHTML = '<iframe marginheight="0" marginwidth"0" frameborder=0 style="height:600px;width:99%;border:0" src="?c=debug&a=loadstand"></iframe>';
    	this.isload = true;
    	}
    }


    __D.highLight = function(string) {
        if (!string) {
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

    __D.highLightSql = function(string) {
        if (!string) {
            return string;
        }
        string = string.replace(/(('.+')|(".+")|(`.+`))/gm, "<span class=\"font_yellow_d\">$1</span>");
        string = string.replace(/(INSERT |SELECT |ORDER |BY | JOIN | FROM | LIMIT | DESC | AND | ON |\(|\))/igm, "<span style=\"color:blue\">$1</span>");
        string = string.replace(/(\s+\d+\s+)/gm, "<span style=\"color:red\">$1</span>");
        return string;
    }

    __D.doDown = function(id) {
        var B = __D(id);
        var jt = __D(id + '_jt');

        if (!B['isdown']) {
            B.isdown = true;
            B.style.display = "block";
            jt.className = 'jt_down';
        } else {
            B.isdown = false;
            B.style.display = 'none';
            jt.className = 'jt_up';
        }
    }

    //得到页面长和宽
    __D.getWH = function() {
        var s = arguments[0] || document.getElementsByTag('BODY')[0];
        return { width: Math.max(s.scrollWidth, s.clientWidth), height: Math.max(s.scrollHeight, s.clientHeight)};
    }

    __D.getHeight = function() {
        return __D.getWH(arguments[0]).height;
    }
    
    setTimeout(function() {
    	__D.traversalChildNodesSql(__D('debug_SqlHelper'));
    }
    , 1000);

    setTimeout(function() {
    	__D.traversalChildNodes(__D('debug_code'));
    }
    , 1000);
    window.__Debug = __D;
    var iframe = __D('debug_docs_iframe');
    iframe.contentWindow.onload = function(){
    	console.log('aaa');
    }
})(window);
</script>
</html>
<!--调试代码结束-->