<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Convert Between Kelvin, Fahrenheit and Celsius</title>
<style type="text/css" media="all">/*<![CDATA[*/ @import "<?= wfGetPad('/extensions/min/f/extensions/wikihow/ArticleWidgets/common/css/style.css,/extensions/wikihow/ArticleWidgets/KFC/css/styles.css,/extensions/wikihow/ArticleWidgets/components/whUpDown/wh.updown.css&') . WH_SITEREV ?>"; /*]]>*/</style>
<script type="text/javascript" src="<?= wfGetPad('/extensions/min/f/extensions/wikihow/common/jquery-1.4.1.min.js,/extensions/wikihow/ArticleWidgets/common/js/tabs.js,/extensions/wikihow/ArticleWidgets/components/whUpDown/jq.wh.updown.js,/extensions/wikihow/ArticleWidgets/libs/wh.temperature.js&') . WH_SITEREV ?>"></script>
</head>
<body>
	<div id="wrapper">
    	<div id="header">
        	<h1>Convert Between Kelvin, Fahrenheit and Celsius</h1>
			<div class="corner_left"></div>
			<div class="corner_right"></div>
        </div><!--end header-->
        	<div id="content">
            	<div class="tab_container">
    				<div id="tab1" class="tab_content">
        				<div class="left">
                        	<h1>Kelvin</h1>
                            <div class="form" id="kelv">
                            </div><!--end form-->
                        </div><!--end left-->
                        
        				<div class="left left_center">
                        	<h1>Fahrenheit</h1>
                            <div class="form" id="fahr">
                            </div><!--end form-->
                        </div><!--end left-->
                        
                        <div class="right no_result">
                        	<h1>Celsius</h1>    
                            <div class="form" id="cels">
                            </div><!--end form-->                       
                        </div><!--end right-->
                     </div><!--end tab_content-->
               </div><!--end tab_container-->
               <div class="ravno"></div>
               <div class="ravno ravno_2"></div>
               <div class="cop"><span>Powered by</span><a href="http://www.wikihow.com/" title = "wikiHow">wikiHow</a></div>
            </div><!--end content-->
            <div class="bottom"></div>
    


    </div><!--end wrapper-->
</body>

<script>

$("#kelv").whUpDown({units:"ºK", width: 150, value: 0, maxLength: 5, cut: 6, onChange: function(element,value){
    if(!isNaN(value)) {
        $("#cels").whUpDown({"value": k2c(value)});
        $("#fahr").whUpDown({"value": k2f(value)});
    }
} });

$("#cels").whUpDown({units:"ºC", width: 150, value: -273.1, maxLength: 5, cut: 6, onChange: function(element,value){
    if(!isNaN(value)) {
        $("#kelv").whUpDown({"value": c2k(value)});
        $("#fahr").whUpDown({"value": c2f(value)});
    }
} });

$("#fahr").whUpDown({units:"ºF", width: 150, value: -459.7, maxLength: 5, cut: 6, onChange: function(element,value){
    if(!isNaN(value)) {
        $("#kelv").whUpDown({"value": f2k(value)});
        $("#cels").whUpDown({"value": f2c(value)});
    }
} });



</script>

</html>
