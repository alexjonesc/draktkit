<?php

// the following are authorized shortcuts for the hard-to-type UTILITY::belch function
function burp($content) { return call_user_func_array("UTILITY::burp", func_get_args()); }
function belch($content) { return call_user_func_array("UTILITY::belch", func_get_args()); }
function belchx($content) { return call_user_func_array("UTILITY::belchx", func_get_args()); }
function fbi($content) { return call_user_func_array("UTILITY::fbi", func_get_args()); }
function clockman($tag=null) { return call_user_func_array("UTILITY::clockman", func_get_args()); }

// if ctype_digit is not included in this PHP build, let's use our own
if (!function_exists('ctype_digit')) {
    function ctype_digit($str) { return call_user_func_array('UTILITY::ctype_digit', func_get_args()); }
}

// message types
define('OARS_MESSAGE_FLASH', 'flash');
define('OARS_MESSAGE_NOTICE', 'notice');
define('OARS_MESSAGE_ERROR', 'error');
define('OARS_MESSAGE_ACTION', 'action');
define('OARS_MESSAGE_OK', 'ok');
define('OARS_MESSAGE_FLASHOK', 'flashOK');

// supported languages
define("OARS_LANGUAGE_ENGLISH", 'english');
define("OARS_LANGUAGE_SPANISH", 'spanish');
define("OARS_LANGUAGE_KLINGON", 'klingon');

define("OARS_LANGUAGE_TEXT_ENGLISH", 'English');
define("OARS_LANGUAGE_TEXT_SPANISH", 'Spanish');
define("OARS_LANGUAGE_TEXT_KLINGON", 'Klingon');

function application_error($description) {
    $errorkey = strtoupper(substr(md5(serialize(func_get_args())), 2, 7));
    LOG::applicationerror("functional application error {$errorkey}", print_r(func_get_args(), true));
    if (Users::isAdminOrSpawn() || isOARSAdmin() || !isProduction()) {  // full error vs. user-facing error
        $message = "<p>So says application_error!</p>";
        call_user_func_array("UTILITY::belch", array(func_get_args(), array("GET"=>$_GET, "POST"=>$_POST)));
        die($message);
    } else {
        $webroot = WEBROOT;
        $message = <<<HERE

This may have occurred if your session timed out, or if you were trying to access a page for which you do not have privileges.<br><br>
We have passed along the circumstances of this error to an administrator.  The error code is {$errorkey}.

HERE;
        Responses::errorpage($message);
    } 
    exit;
}

function application_warning($details) {
    global $districtURL;
    LOG::applicationwarning('application_warning', print_r(func_get_args(), true));
    
    $dont_redirect = array("/error.php",$districtURL."/index.php");
    $pattern = '#'. join("|", $dont_redirect).'#';

    if(preg_match($pattern, $_SERVER['SCRIPT_NAME']) > 0) {
        application_error($details);
        exit; //to be sure
    } else {
        OARSMESSAGE::push('There was an error that prevented OARS from displaying the page you requested.<br/>We returned you to the home page. We have passed along the circumstances of this error to an administrator.', 'error');
        header("Location: ". WEBROOT ."index.php");
        exit;
    }
}

if(!function_exists('array_unshift_assoc')) {
    function array_unshift_assoc(&$arr, $key, $val) { return UTILITY::array_unshift_assoc($arr, $key, $val); }
}


class UTILITY {
    // this is a simple class that encapsulates our most helpuful functions

    private function __construct() {} // private obstensibly disables the 'new' functionality
 
    // part I - debugging helpers
    
    // user burp to place outinput indide body tag. belch outputs before html and throws navigation markup off
    public static $burps = '';
    public static function burp($content) {
                
        $arr = array();
        // Usage Example: UTILITY::belch('hello world');
        static $oarsheader; // this puts a 120px margin on the top only if this is the first time that it's invoked
        if (isset($oarsheader)) $oarsheader="margin-top:0px;";
        else $oarsheader="margin-top:0px;";

        $arr[] = "<div class=\"burp\" style='padding:0;margin:6px 0; {$oarsheader} font-family:arial,sans;background:white;white-space:pre;border:4px solid orange;border-bottom:0;color:blue; position:relative; z-index:999;'><span class=\"close\" style=\"cursor:pointer;display: block;position: absolute;right:1px;top:1px;width:20px;height: 20px;background: url(". WEBRESOURCES . "/images/oars/x.gif) center center no-repeat;\"></span><pre>";
        $content = array();
        foreach (func_get_args() as $arg) {
            $content[] = self::getBelchContent($arg);
        }
        $arr[] = implode("\n", $content);
        $arr[] = "</pre>";
        $invoker = self::last_backtrace();
        $elapsed = Profiler::niceElapsedTime();
        $consumed= Profiler::niceElapsedMem();
        $arr[] = "<div style='font-size:0.9em;font-color:black;background:orange;margin:0;padding:0;border:0;whitespace:nowrap;width:100%'>{$elapsed}; &nbsp; {$consumed}; &nbsp; {$invoker}</div>";
        $arr[] = "</div>";
        self::$burps .= implode('',$arr);
    }
    
    public static function belch($content) {
        // Usage Example: UTILITY::belch('hello world');
        static $oarsheader; // this puts a 120px margin on the top only if this is the first time that it's invoked
        if (isset($oarsheader)) $oarsheader="margin-top:0px;";
        else $oarsheader="margin-top:100px;";

        echo "<div style='padding:0;margin:6px 0; {$oarsheader} font-family:arial,sans;background:white;white-space:pre;border:4px solid orange;border-bottom:0;color:blue; position:relative; z-index:999;'><pre>";
        $content = array();
        foreach (func_get_args() as $arg) {
            $content[] = self::getBelchContent($arg);
        }
        echo implode("\n", $content);
        echo "</pre>";
        $invoker = self::last_backtrace();
        $elapsed = Profiler::niceElapsedTime();
        $consumed= Profiler::niceElapsedMem();
        echo "<div style='font-size:0.9em;font-color:black;background:orange;margin:0;padding:0;border:0;whitespace:nowrap;width:100%'>{$elapsed}; &nbsp; {$consumed}; &nbsp; {$invoker}</div>";
        echo "</div>";
        flush();
    }
    public static function getBelchContent($arg) {
        $content = array();
        
        if (is_array($arg)===true) {
            // htmlentities returns blank string if it fails
            $output = htmlentities(str_replace("Array\n", "(array)\n", UTILITY::print_r($arg)), null, 'UTF-8', false);
            $content[] = ($output !== '') ? "{$output}" : "data error\n";
        }
        else if (is_bool($arg)===true) $content[] = "(Boolean) ". (($arg===true) ? "true" : "false") ."\n";
        else if (is_object($arg)) {
            $content[] = "(". gettype($arg) .") ";
            // htmlentities returns blank string if it fails
            $output = htmlentities(UTILITY::print_r($arg), null, 'UTF-8', false); // htmlentities apparently can't handle utf8?
            if ($arg === '') {
                $content[] = $arg;
            } elseif ($output !== '') {
                $content[] = $output;
            } else {
                $output = htmlentities(UTILITY::print_r($arg, true), null, 'UTF-8', false); // htmlentities apparently can't handle utf8?
                echo ($output);
                $content[] = ($output !== '') ? "{$output}" : "data error";
            }
        } else {
            $unwrapped = UTILITY::getUnwrapData($arg);
            if ($unwrapped['type'] == 'primitive') $content[] = "(". gettype($arg) .") ". htmlentities($arg, null, 'UTF-8', false) ."\n";
            else $content[] = "{$unwrapped['type']}: ". self::getBelchContent($unwrapped['data']);
            // this is a leaf, so don't do an extra newline
        }
        
        return implode("", $content);
    }
    // this returns unwrapped content, and what method was used to get it
    public static function getUnwrapData($arg) {
        // this is a leaf; check if we can do something and keep this going, or just return what we have
        $resolve_type = false;
        $unwrapped_data = $arg;
        if (!$resolve_type) {
            if (is_null($arg) || (strtolower($arg) === 'true' || strtolower($arg) === 'false')) { // these die when passed into json decode, so let's catch these early
                $resolve_type = 'primitive';
                $unwrapped_data = $arg;
            }
        }
        if (!$resolve_type) {
            $unjsoned = json_decode($arg, true);
            if ($unjsoned != $arg && json_last_error() == JSON_ERROR_NONE && !is_null($unjsoned)) { // check for equality and nullness to catch primitives
                $resolve_type = 'json';
                $unwrapped_data = $unjsoned;
            }
        }
        if (!$resolve_type) {
            $uncompressed = @gzuncompress($arg);
            if ($uncompressed !== false) {
                $resolve_type = 'compressed';
                $unwrapped_data = $uncompressed;
            }
        }
        if (!$resolve_type) {
            $unserialized = @unserialize($arg);
            if ($unserialized !== false) {
                $resolve_type = 'serialized';
                $unwrapped_data = $unserialized;
            }
        }
        if (!$resolve_type) {
            $resolve_type = 'primitive';
            $unwrapped_data = htmlentities($arg, null, 'UTF-8');
        }
        return array('type'=>$resolve_type, 'data'=>$unwrapped_data);
    }
    public static function unwrapString($string) {
        $unwrapped_data = self::getUnwrapData($string);
        return $unwrapped_data['data'];
    }
    // this does what print_r does, but with some enhancements, like uncompressing/unserializing/unjsoning
    // level is the depth that this function travels
    public static function print_r($data, $level = 10) {
        static $innerLevel = 1;
         
        static $tabLevel = 1;
         
        static $cache = array();
         
        $self = __FUNCTION__;
         
        $type       = gettype($data);
        $tabs       = str_repeat('    ', $tabLevel);
        $quoteTabes = str_repeat('    ', $tabLevel - 1);
         
        $recrusiveType = array('object', 'array');
         
        // Recrusive
        if (in_array($type, $recrusiveType)) {
            // If type is object, try to get properties by Reflection.
            if ($type == 'object') {
                if (in_array($data, $cache)) {
                    return "\n{$quoteTabes}*RECURSION*\n";
                }
                 
                // Cache the data
                $cache[] = $data;
                 
                $output     = get_class($data) . ' ' . ucfirst($type);
                $ref        = new \ReflectionObject($data);
                $properties = $ref->getProperties();
                 
                $elements = array();
                 
                foreach ($properties as $property) {
                    $property->setAccessible(true);
                     
                    $pType = $property->getName();
                     
                    if ($property->isProtected()) {
                        $pType .= ":protected";
                    } elseif ($property->isPrivate()) {
                        $pType .= ":" . $property->class . ":private";
                    }
                     
                    if ($property->isStatic()) {
                        $pType .= ":static";
                    }
                     
                    $elements[$pType] = $property->getValue($data);
                }
            }
            // If type is array, just retun it's value.
            elseif ($type == 'array') {
                $output = ucfirst($type);
                $elements = $data;
            }
             
            // Start dumping datas
            if ($level == 0 || $innerLevel < $level) {
                // Start recrusive print
                $output .= "\n{$quoteTabes}(\n";
                 
                foreach ($elements as $key => $element) {
                    $output .= "{$tabs}[{$key}] => ";
                     
                    // Increment level
                    $tabLevel = $tabLevel + 2;
                    $innerLevel++;
                     
                    $output  .= in_array(gettype($element), $recrusiveType) ? UTILITY::$self($element, $level) : self::getBelchContent($element); // getBelchContent has our logic for leaves
                     
                    // Decrement level
                    $tabLevel = $tabLevel - 2;
                    $innerLevel--;
                }
                 
                $output .= "{$quoteTabes})\n";
            } else {
                $output .= "\n{$quoteTabes}*MAX LEVEL*\n";
            }
        }
         
        // Clean cache
        if ($innerLevel == 1) {
            $cache = array();
        }
         
        return $output;
    }// End function
    public static function belchx($content) {
        call_user_func_array("UTILITY::belch", func_get_args());
        exit;
    }
    public static function fbi($content) { // fb improved
        foreach (func_get_args() as $arg) {
            fb($arg);
        }
    }
    public static function belchsql($sql) {  // attempts to run sql and display the result
        UTILITY::belch(DB::fetcharray($sql));
    }

    public static function flatulence($what, $level="") {  // flattens an array to make it more readable in certain situations
        if (is_array($what)) {
            $produce=array();
            foreach ($what as $wha=>$t) $produce[] = self::flatulence($t, $level ."['$wha']");
            return join("\n", $produce);
        } else return "$level = ". print_r($what, true);
    }

    public static function spew($content) {  // tries to build a table out of the data that it's given
        $tablestyle = "font-family:arial narrow,sans;font-size:10pt;background:#666;border:0;padding:1px";
        $keycellstyle = "border:0;padding:1px;margin:0;background:#AAA;color:#960;white-space:nowrap;";
        $valuecellstyle = "border:0;padding:1px;margin:0;background:#EEE;color:#060;white-space:nowrap;";

        echo "<table style='$tablestyle'>";
        if (!is_array($content)) echo "<tr><td style='{$valuecellstyle}'>". htmlentities(print_r($content, true), null, 'UTF-8') ."</td></tr>";
        else {
            foreach ($content as $k => $v) {

                if (!is_array($v)) echo "<tr><td style='{$keycellstyle}'>{$k}</td><td style='{$valuecellstyle}'>". htmlentities(print_r($v, true), null, 'UTF-8') ."</td></tr>";
                else {
                    if (!isset($headers)) {
                        $headers="yes";
                        echo "<tr><td style='{$keycellstyle}'>&nbsp;</td>";
                        foreach ($v as $kk => $vv) echo "<td style='{$keycellstyle}'>{$kk}</td>";
                        echo "</tr>";
                    }
                    echo "<tr><td style='{$keycellstyle}'>$k</td>";
                    foreach ($v as $kk => $vv) {
                        if(is_null($vv)) $vv = 'NULL';
                        // try to uncompress it, just in case it's compacted as in Mynamo
                        if ($ww = @gzuncompress($vv)) $vv = $ww;
                        // try to unserialize it, just in case it's serialized as in Mynamo
                        if (is_array(unserialize($vv))) $vv=unserialize($vv);
                        echo "<td style='{$valuecellstyle}'><pre style='margin-bottom:0px;'>". htmlentities(print_r($vv, true), null, 'UTF-8') ."</pre></td>";
                    }
                    echo "</tr>";
                }
            }
        }
        echo "</table>";
        flush();
    }
    public static function spewsql($sql) {  // attempts to run sql and display the result in a table
        if(strpos($sql, ";") !== false) $sqls = explode(';', rtrim($sql, ';'));
        else $sqls = array($sql);
        foreach ($sqls AS $k => $query) {
            UTILITY::spew(DB::fetcharray($query));
            echo "<br/>";
        }
    }

    public static function cough($content) {  // very simliar to belch but expects variables that are provided as strings
        // popular usage: UTILITY::cough('_GET', '_POST', '_SESSION');
        $pleaseshow = array();
        foreach (func_get_args() as $arg) $pleaseshow[$arg] = $GLOBALS[$arg];
        UTILITY::belch($pleaseshow);
    }

    public static function backwash($content) { // provides a concise backtrace
        if (func_num_args()>0) call_user_func_array("UTILITY::belch", func_get_args()); 
        UTILITY::belch(UTILITY::backtrace());  // you asked for it!
    }
            
    private static function _backtrace_to_function($file, $line) {
        if (!$file) return "";
        $codes = array_slice(file($file), 0, $line);
        $codes = array_reverse($codes);
        if (strpos($codes[0], 'belch') !== false) return trim($codes[0]);
        foreach ($codes as $code) if (strpos($code, 'function ') !== false) return "in ". trim($code);
        return trim($codes[0]);
    }
    public static function backtrace() { return array();
        $outputs = array();
        $histories = array_reverse(debug_backtrace(false));
        foreach ($histories as $history) {
            $file = $history['file'];
            $line = $history['line'];
            if (!$file) continue;
            $func = self::_backtrace_to_function($file, $line);
            if (strpos($file, 'utility.phinc') !== false) continue;  // exclude this file
            if (strpos($file, 'everywhere_db.phinc') !== false) continue;  // exclude this file
            $outputs[] = "backtrace: ". $file ." line ". $line ." ". $func;
        }
        return $outputs;
    }
    public static function last_backtrace() { return '';
        $outputs = array_reverse(self::backtrace());
        return $outputs[0];
    }

    // part II - HTML helpers

    public static function getFinger() {
        return '<big>&#9758;</big>&nbsp;';
    }
    public static function array2querystring($items) { // takes an associative array and returns a querystring itemkey=urlescapeditemvalue
        // popular usage: $href = "configure.php?". UTILITY::array2querystring($options);
        $outputs = array();
        foreach ($items as $key => $value) {
            if(!is_array($value)){
                $outputs[] = $key .'='. urlencode($value);
            }else{
                foreach($value AS $kk => $vv) $outputs[] = $key.'['.$kk.']='.urlencode($vv);    
            }            
        }
        return join('&', $outputs); 
    }
    public static function array2htmlpairs($items) { // takes an associative array and returns a string of key="value" pairs
        // popular usage: $html = "<td ". UTILITY::array2querystring($attributes) .">";
        // (but you would probably want to use UTILITY::wrap() instead)
        $outputs = array();
        if (!is_array($items)) $items = array();
        foreach ($items as $key => $value) {
            if (strpos($value, '"')!==false) $outputs[] = $key ."='". str_replace("'", "\'", $value) ."'";  // there are doubles so enclose in single quotes
            else $outputs[] = $key .'="'. str_replace('"', '\"', $value) .'"';
        }
        return join(' ', $outputs); 
    }
    public static function wrapContent($selector, $attributes='', $contents='') {  // DEPRECATED (I don't like the order of the attributes)
        return UTILITY::wrap($selector, $contents, $attributes);
    }
    public static function wrap($selector, $contents=null, $attributes=null) { // creates <$selector $attributes>$contents</$selector>
        if (is_array($contents)) $contents = join('', $contents);
        if (is_array($attributes)) $attributes = UTILITY::array2htmlpairs($attributes);
        $attributes = (isset($attributes)) ? " ".$attributes : '';
        if ($selector == "CDATA") return "<![CDATA[{$contents}]]>";
        else if (!isset($contents)) return "<{$selector}{$attributes}/>";
        else return "<{$selector}{$attributes}>{$contents}</{$selector}>";
    }
    public static function divwrap($contents, $classes="sectionBlock") {  // shortcut for <div class="...">...</div>
        if (is_array($classes)) $classes = join(" ", $classes);
        $attributes = array("class"=>$classes);
        return UTILITY::wrap("div", $contents, $attributes);
    }
    public static function sectionwrap($h2, $contents, $classes="sectionBlock") {  // shortcut for <div class="sectionBlock"><h2>...</h2>...</div>
        if (is_array($classes)) $classes = join(" ", $classes);
        $attributes = array("class"=>$classes);
        $preamble = (!$h2) ? "" : UTILITY::wrap("h2", $h2);
        return UTILITY::wrap("div", $preamble.$contents, $attributes);
    }

    public static function ul($contents, $attributes=null) { // makes an unordered list from an array of contents
        if (!$contents) return "";
        if (!is_array($contents)) $contents = array($contents);
        return UTILITY::wrap("ul", UTILITY::wrap("li", join("</li>\n<li>", $contents)), $attributes);
    }
    public static function ol($contents, $attributes=null) { // makes an ordered list from an array of contents
        if (!$contents) return "";
        if (!is_array($contents)) $contents = array($contents);
        return UTILITY::wrap("ol", UTILITY::wrap("li", join("</li>\n<li>", $contents)), $attributes);
    }

    public static function getLink($text, $link=null) {  // constructs a link
        // this is a plug-replacement for Users::getAdminOnlyLink() for when you're finished with the admin link
        if (!$link) return $text;
        // else ...
        if (!is_array($link)) $link=array('href'=>$link, 'class'=>'adminOnly');
        return UTILITY::wrap("a", $text, $link);
    }

    // this function wraps text in a class that requires an "oars" keystroke to display
    public static function makeSecret($text, $attributes=null) {
        if ($attributes['class']) $attributes['class'] .= ' konami';
        else $attributes['class'] = 'konami';
        return self::wrap("span", $text, $attributes);
    }
    
    public static function setValsToKeys($array) {  // makes an associative array out of a simple array
        return array_combine($array, $array);
    }
    public static function wrapJavascript($javascript) {  // this will let us minify on the fly
        echo <<<HERE

<script type="text/javascript">
{$javascript}
</script>

HERE;
    }

    // part II b - Output helpers

    public static function getSQLAsTable($sql, $alternative="No data", $nowrap="nowrap") {
        $outcome = DB::fetcharray($sql);
        return UTILITY::getContentAsTable($outcome, $alternative, $nowrap);
    }

    public static function getContentAsTable(&$content, $alternative="No data", $options=array("nowrap"=>"true")) {  // make a 2D array into a table using the keys as column headers
        // $options=array("nowrap"=>"true", "borders"=>"true", "totals"=>"true");
        // loosely related to what we do in belch and spew
        if (count($content)==0) return $alternative;
        if (!is_array($content)) return $content;
        if ($options=='nowrap') $options = array("nowrap"=>true); // legacy
        if (!is_array($options)) $options = array($options=>true); // safety
        $nowrap = ($options['nowrap']) ? 'nowrap' : '';

        $columns = array();
        foreach ($content as $k => $v) {
            foreach ((array) $v as $kk => $vv) $columns[$kk] = $kk;
        }
        $thead = $body = $tfoot = $totals = array();
        foreach ($content as $k => $v) {
            if (!isset($headers)) {
                $headers="yes";
                $thead[] = '<tr class="aBottom" valign="bottom">';
                foreach ($columns as $cc) $thead[] = "<th class='aCenter' {$nowrap}>{$cc}</th>";
                $thead[] = "</tr>";
            }
            $body[] = '<tr class="aTop" valign="top">';
            foreach ($columns as $cc) {
                $vv = $v[$cc];
                if (is_null($vv)) $vv = 'NULL';
                if (is_numeric($vv)) $body[] = '<td class="aRight" align="right">'. $vv .'</td>';
                else $body[] = "<td {$nowrap} class='aLeft' align='left'>{$vv}</td>";
                if (is_numeric($vv)) $totals[$cc] += $vv;
            }
            $body[] = "</tr>";
        }
        $onetotal = false;
        foreach ($columns as $cc) {
            $vv = $totals[$cc];
            if (is_null($vv)) $vv = 'NULL';
            if (is_numeric($vv)) $tfoot[] = '<td class="aRight" align="right">'. $vv .'</td>';
            else if ($onetotal) $tfoot[] = "<td>-</td>";
            else { 
                $onetotal=true; 
                $tfoot[] = "<th {$nowrap} class='aLeft' align='left'>TOTAL</th>";
            }
        }
        if ($options['totals']) $footers = UTILITY::wrap('tfoot', UTILITY::wrap('tr', join("\n", $tfoot)));
        return UTILITY::wrap('table', UTILITY::wrap('thead', join("\n", $thead)) . $footers . UTILITY::wrap('tbody', join("\n", $body)), array("class"=>"ContentAsTable"));
    }
    
    /*
     * takes a string, or an array, or a collection of arrays
     * and quotes the values, escapes the values and adds a \n to each row
     */
    public static function arrayToCSVWithDelimiter($data_to_seperate, $delimiter=null, $header_row=null) {
        if (!isset($delimiter)) $delimiter = self::getCSVDelimiter();
        if (($header_row)&&(!is_array($header_row))&&(count($data_to_seperate)>0)) $header_row = array_keys($data_to_seperate[0]);
        if(is_string($data_to_seperate)) $data_to_seperate = array($data_to_seperate);
        $end_row = "\n";
        $rows = '';
        if ($header_row) $rows .= self::arrayToCSV($header_row, null, $delimiter);
        if (is_array($data_to_seperate)) {
            foreach ($data_to_seperate AS $k => $element) {
                if(is_array($element)) $rows .= self::arrayToCSV($element, null, $delimiter);
                else $rows .= '"'. addslashes($element)."\"{$delimiter}";
            }
            $rows = rtrim(rtrim($rows, "{$delimiter}"), "\n") . $end_row;
        }
        return $rows;
    }
    
    public static function emitCSVHeaders($filename=null) {
        if (!$filename) $filename = UTILITY::getPrettyFilename("data", "csv");
        if (strpos($filename, ".csv")===false) $filename = getPrettyFilename($filename, "csv");
        header('Content-Type: "Application/x-msexcel; charset=UTF-8');
        $headerStr = "Content-Disposition: attachment; filename=\"$filename\"";
        header($headerStr);
    }

    /*
     * takes a string, or an array, or a collection of arrays
     * and quotes the values, escapes the values and adds a \n to each row
     */
    public static function csvToArrayWithDelimiter($data_to_join, $delimiter=',', $header_row=null) {
        $rows = explode("\n", $data_to_join);

        $rows_array = array();
        foreach ($rows as $i => $row) {
            if (!$row) continue;
            
            // instead of exploding, let's change this up a little,
            // since spreadsheet apps like excel and openoffice might strip quotes
            // this checks individual entries to see if they're quote escaped
            $position = 0; 
            $row_array = array();

            while ($position <= strlen($row)) {
                // check if quote escaped
                $has_ended = 0;
                if (substr($row, $position, 1) == '"') {
                    $start = $position + 1;
                    $end = strpos($row, "\"{$delimiter}", $position+1);
                    if ($end) {
                        $next = $end + 2;
                    } else {
                        $end = strpos($row, "\"", $position+1);
                        if ($end) {
                            $end = strlen($row) - 1;
                        } else {
                            $end = strlen($row) - 2;
                        }
                        $has_ended = 1;
                    }
                } else {
                    $start = $position;
                    $end = strpos($row, "{$delimiter}", $position);
                    if ($end) {
                        $next = $end + 1;
                    } else{
                        $end = strlen($row);
                        $has_ended = 1;
                    }
                }
                $row_array[] = substr($row, $start, $end-$start);
                if ($has_ended) {
                    break;
                } else {
                    $position = $next;
                }
            }

            // remove header row
            if ($i == 0 && $header_row && implode(' ', $row_array) == implode(' ', $header_row)) continue;

            // column mismatch, ignore
            if ($header_row && count($row_array) != count($header_row)) continue;

            foreach ($row_array as $i => $value) {
                $row_array[$i] = stripslashes($value);
            }
            
            $rows_array[] = $row_array;
        }

        return $rows_array;
    }
    
    public static function arrayToCSV($data, $header_row=null, $delimiter=null) {
        if (!isset($delimiter)) $delimiter = self::getCSVDelimiter();
        return self::arrayToCSVWithDelimiter($data, $delimiter, $header_row);
    }
    
    public static function csvToArray($data, $header_row=null, $delimiter=null) {
        if (!isset($delimiter)) $delimiter = self::getCSVDelimiter();
        return self::csvToArrayWithDelimiter($data, $delimiter, $header_row);
    }
    
    private static function getCSVDelimiter() {
        if (isOARSAdmin()) {
            return "\t";
        } else {
            return Roles::getCurrentRole()->getPreference('IO', 'delimiter', ',');
        }
    }
    
    public static function getCSVFileType() {
        if (isOARSAdmin()) {
            return "tab";
        } else {
            return "csv";
        }
    }
    
    // DEPRECATED replaced by arrayToCSV(obviously)
    // this will call exactly what it was doing before any changes
    public static function csv($data_to_seperate) {
        return self::arrayToCSVWithDelimiter($data_to_seperate, ',');
    }
    
    // part III - Conversion helpers
    
    public static function asarray($mixed) {  // always an array
        if (is_object($mixed) && (get_class($mixed)=='SimpleXMLElement')) return self::x2a($mixed);
        else return (array) $mixed;
    }

    public static function x2a($x) { // accepts a SimpleXMLElement and returns a stable php array
        if (get_class($x)!=='SimpleXMLElement') belchx("node is not a SimpleXMLElement. This is terminal.", $x);
        $elements = array();
        if ($x->attributes()) foreach ($x->attributes() as $k=>$v) $elements['@attributes'][$k] = trim((string) $v);
        $isay = trim((string) $x);
        if (isset($isay) && (strlen($isay)>0)) $elements['@'] = $isay;
        if ($x->count()) foreach ($x->children() as $child) if (get_class($x)=='SimpleXMLElement') {
            $node = x2a($child);
            if (count($node)>0) $elements[$child->getName()][] = $node;
        }
        return $elements;
    }

    public static function array_reorder($unordered, $orderingkeys) {  // reorders the array according to the $orderingkeys, then everything else
        $ordered = array();
        foreach ($orderingkeys as $k) if (isset($unordered[$k])) {
            $ordered[$k] = $unordered[$k];
            unset($unordered[$k]);
        }
        foreach ($unordered as $k => $v) $ordered[$k] = $unordered[$k];
        return $ordered;
    }
    
    /**
     * 
     * @param array by ref $arr
     * @param string $key
     * @param mixed $val
     */
    public static function array_unshift_assoc(&$arr, $key, $val){
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        return $arr = array_reverse($arr, true);
    }
    
    public static function array_merge_recursive_save_keys() {
    
        $arrays = func_get_args();
        $base = array_shift($arrays);
    
        foreach ($arrays as $array) {
            reset($base); //important
            while (list($key, $value) = @each($array)) {
                if (is_array($value) && @is_array($base[$key])) {
                    $base[$key] = self::array_merge_recursive_save_keys($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            }
        }
    
        return $base;
    }
    
    public static function number_nice($number,$precision=0,$dec_point='.',$thousands_sep=',') { // returns a minimalistic number format (02.20 (with precision 2) becomes 2.2)
        $formatted_number = number_format($number,$precision,$dec_point,$thousands_sep);
        if (strpos($formatted_number,$dec_point) !== false) $formatted_number = rtrim($formatted_number,'0');
        $formatted_number = rtrim($formatted_number,$dec_point);
        return (strlen($formatted_number)) ? $formatted_number : "0";
    }
    
    public static function sandwich($left, $array, $right=null) {  // wraps each element of the array between $left and $right
        // popular usage: sandwich('"', $names); => "joe" "fred" ... 
        // popular usage: sandwich('<td>', $names, '</td>'); => <td>joe</td><td>fred</td> ... 
        if (!isset($right)) $right=$left;
        foreach ($array as $k=>$v) $array[$k] = $left . $v . $right;
        return $array;
    }
            
    public static function colorToRGB($color) { // returns an array with R, G, B values from a HTML color
        // suggested usage: list($r, $g, $b) = colorToRGB("#abcdef");
        
        $color = str_replace('#','',$color);

        $r = hexdec(substr($color,0,2));
        $g = hexdec(substr($color,2,2));
        $b = hexdec(substr($color,4,2));
        return array($r, $g, $b);
    }
    
    public static function stripRGBText($rgbstring) { // returns string r,g,b from jQuery's "rgb(R, G, B)"
        // popular usage: $scoreband_colors = rgbstrip($scoreband_colors);
        if (preg_match_all('/rgb\((.*)\)/', $rgbstring, $matches)) return $matches[1][0];
        else return $rgbstring;
    }

    public static function nullIfBlank($val) { // returns null if argument is a string of '' // somebody should be using empty()
        return $val === '' ? null : $val;
    }
    
    // removes accented and similar characters from utf8 strings
    public static function normalizeString($string) {
        // Normalizer is in the php-intl package
        $string = Normalizer::normalize($string, Normalizer::FORM_D);
        if (!$string) return false;
        else return preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);
    }

    // takes the contents of a textarea that was (probably) pasted in by a user and returns structured data.
    public static function parsePastedData($pasteddata) { 
        // columns must be named // data must be in rows // tab-separated
        // born in import_inspect_tests.php but used elsewhere in OARSadmin.
        // <textarea name="test_data" id="test_data" rows="30" cols="180" wrap="soft"></textarea>
        // $pasteddata = UTILITY::parsePastedData($_REQUEST['test_data']);
        // foreach ($pasteddata as $row) foreach ($row as $columnname => $columndata) $inserts[] = "{$columnname}=". DB::qq($columndata);

        $pastedrows = explode("\n", $pasteddata);
        $headers = explode("\t", trim(strtolower(array_shift($pastedrows))));
        $pasteddata = array(); // this will receive the contents that were pasted in an array of associative arrays

        $pastedrowcount = count($pastedrows);
        $pastedrowcounttext = UTILITY::plural($pastedrowcount, "row");
        $columncount = count($headers);
        $columncounttext = UTILITY::plural($columncount, "column");
        $headertext = UTILITY::wrap("ol", "<li>". join("<li>", $headers));

        $invalid = array();
        $pastedcount = 0;

        foreach ($pastedrows as $pastedrow) {
            $pastedcount++;
            $columns = explode("\t", trim($pastedrow));
            if (count($columns)<=1) continue;  // bogus row
            if (count($headers)<count($columns)) belchx("Headers don't match columns. This is bad.", $headers, $columns);
            if (count($headers)>count($columns)) { // assume blanks at the end of the rows
                for ($i=count($columns);$i<count($headers);$i++) $columns[]='';
            }
            $mappedcolumns = array_combine($headers, $columns);
            if (!$mappedcolumns) {
                $invalid[] = print_r(array("row"=>$pastedcount+1, "pastedrow"=>$pastedrow, "explodedrow"=>join(" &middot; ", $columns)), true);
                continue;
            }

            $pasteddata[$pastedcount] = $mappedcolumns;
        }
        unset($mappedcolumns); // don't be tempted to use this variable
        return $pasteddata;
    }

    public static function addapoint($breaks) {  // puts a new point in the middle of the biggest span in an array, respecting the endpoints
        // returns the extended array
        $lastbreak=$maxspan=$maxid=0;
        for ($breakid=0; $breakid<count($breaks); $breakid++) {
            $span = $breaks[$breakid+1]-$breaks[$breakid];
            if ($span>$maxspan) {
                $maxspan=$span;
                $maxid=$breakid;
            }
        }
        $newvalue = ($breaks[$maxid+1]+$breaks[$maxid])/2;
        $results=array();
        for ($breakid=0;$breakid<count($breaks);$breakid++) {
            $results[] = $breaks[$breakid];
            if ($breakid==$maxid) $results[] = $newvalue;
        }
        // print_r($breaks); print "$maxid $maxspan $newvalue"; print_r($results);
        return $results;
    }

    public static function projection($sourcebreaks, $targetbreaks, $value) { // proportionally map a value from a source range to a target range
        // returns the mapped value
        $bandcount = count($sourcebreaks);
        if ($bandcount < count($targetbreaks)) $sourcebreaks=self::addapoint($sourcebreaks); // need another sourcebreak
        if ($bandcount > count($targetbreaks)) $targetbreaks=self::addapoint($targetbreaks); // need another targetbreak
        $bandid = 0;
        while (($bandid <= $bandcount) && ($value >= $sourcebreaks[$bandid])) $bandid++;
        if (($bandid==0) || ($bandid>=$bandcount)) {
            $sourcelow=$sourcebreaks[0]; $sourcehigh=$sourcebreaks[$bandcount-1];
            $targetlow=$targetbreaks[0]; $targethigh=$targetbreaks[$bandcount-1];
        } else {
            $sourcelow=$sourcebreaks[$bandid-1]; $sourcehigh=$sourcebreaks[$bandid];
            $targetlow=$targetbreaks[$bandid-1]; $targethigh=$targetbreaks[$bandid];
        }
        $slope = ($targetlow-$targethigh)/($sourcelow-$sourcehigh);
        $result = ($slope * ($value - $sourcelow)) + $targetlow;
        // echo "$value $bandid $sourcelow,$targetlow $sourcehigh,$targethigh $slope $result\n";
        return $result;
    }

    public static function uniord($c) {
        if (ord($c{0}) >=0 && ord($c{0}) <= 127)
            return ord($c{0});
        if (ord($c{0}) >= 192 && ord($c{0}) <= 223)
            return (ord($c{0})-192)*64 + (ord($c{1})-128);
        if (ord($c{0}) >= 224 && ord($c{0}) <= 239)
            return (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
        if (ord($c{0}) >= 240 && ord($c{0}) <= 247)
            return (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
        if (ord($c{0}) >= 248 && ord($c{0}) <= 251)
            return (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
        if (ord($c{0}) >= 252 && ord($c{0}) <= 253)
            return (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
        if (ord($c{0}) >= 254 && ord($c{0}) <= 255)    //  error
            return FALSE;
        return 0;
    }
    
    public static function unichr($o) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding('&#'.intval($o).';', 'UTF-8', 'HTML-ENTITIES');
        } else {
            return chr(intval($o));
        }
    }
    
    public static function ctype_digit($str) {
        if (!is_string($str)) return false;
        return preg_match('/^[0-9]+$/', $str) ? true : false;
    }
    

    // part IV - conversation helpers
  
    public static function plural($value, $singlestring, $multiplestring = null, $zerostring = null) {
        // usage: UTILITY::plural($i, 'balloon')
        // 0 balloons / 1 balloon / 2 balloons
        if (is_numeric($value) && ($value == intval($value))) $nicevalue = number_format($value);
        else $nicevalue = $value;
        if (($value == 0)&&(isset($zerostring))) return $zerostring;
        if ($value == 1) return $nicevalue .' '. $singlestring;
        else if (isset($multiplestring)) return $nicevalue .' '. $multiplestring;
        else return $nicevalue .' '. $singlestring .'s';
    }

    public static function nicesize($value, $suffix='') {
        $av = abs($value)*1000;
        if ($av<200) return round($value*1000) ."milli$suffix";
        $av/=1000;
        if ($av<8) return round($value, 1) ."$suffix";
        if ($av<800) return round($value) ."$suffix";
        $av/=1000;
        if ($av<8) return round($value/1000, 1) ."k$suffix";
        if ($av<500) return round($value/1000) ."k$suffix";
        $av/=1000;
        if ($av<8) return round($value/1000/1000, 1) ."M$suffix";
        if ($av<500) return round($value/1000/1000) ."M$suffix";
        $av/=1000;
        if ($av<8) return round($value/1000/1000/1000, 1) ."G$suffix";
        if ($av<500) return round($value/1000/1000/1000) ."G$suffix";
        return round($value/1000/1000/1000/1000, 1) ."T$suffix";
    }
    
    public static function nicetimelength($seconds, $use_short=true, $num_pieces=2) {
        $units = array('day'=>86400, 'hour'=>3600, 'minute'=>60,  'second'=>1);
        $short = array('day'=>'d',   'hour'=>'h',  'minute'=>'m', 'second'=>'s');
        $pieces = array();
        foreach ($units as $unit=>$value) {
            if ($seconds >= $value) {
                $chunk = floor($seconds / $value);
                $pieces[] = $use_short ? "{$chunk}{$short[$unit]}" : UTILITY::plural($chunk, $unit);
                $seconds -= $chunk * $value;
            }
        }
        $milli = round($seconds * 1000);
        if ($milli > 0) $pieces[] = $use_short ? "{$milli}ms" : UTILITY::plural($milli, 'milliseconds');
        if (count($pieces) > $num_pieces) $pieces = array_slice($pieces, 0, $num_pieces);
        return join(' ', $pieces);
    }

    public static function oxfordjoin($list, $conjunction='or') { // joins the elements of the list with an Oxford OR
        if (!is_array($list)) return $list;
        // an Oxford list, the preferred list for kids these days, has a comma before the final conjunction with more than two items, e.g. "2, 4, 6, and 8".
        return join(((count($list)>2)?",":"") ." $conjunction ", array_filter(array_merge(array(join(', ', array_slice($list, 0, -1))), array_slice($list, -1)), 'strlen'));
    }
    public static function oxfandjoin($list, $conjunction='and') { // joins the elements of the list with an Oxford AND
        return UTILITY::oxfordjoin($list, $conjunction);
    }

    // part V - variable helpers

    public static function ifset($v, $w) {  // based on MySQL's IFNULL
        foreach (func_get_args() as $arg) if ($arg) return $arg;
        return null;
    }
    public static function ifnull($v, $w) {  // based on MySQL's IFNULL
        foreach (func_get_args() as $arg) if (isset($arg)) return $arg;
        return null;
    }

    public static function getNestedVar($base, $keys) {
        $top_key = array_shift($keys);
        return count($keys) > 0 ? UTILITY::getNestedVar($base[$top_key], $keys) : $base[$top_key];
    }

    public static function joiner($glue, $mixeds) {  // for joining strings and stuff together possibly recursively
        $result = array();
        foreach (func_get_args() as $argv => $arg) if ($argv>0) $result[] = (is_array($arg)) ? join($glue, $arg) : $arg;
        return join($glue, $result);
    }
    
    public static function constant($content) { // fb improved
        $params = func_get_args();
        return ($params) ? constant(implode('_', $params)) : null;
    }

    // fieldsort sorts a multidimensional array based on the values in a field, while preserving keys
    // the field can be an array, in which case it will drill down by keys into the last value
    // Sample usage:
    //   $rows = DB::runsql_fetcharray("SELECT * FROM users");
    //   UTILITY::fieldsort($rows, "username");
    private static $_fieldsortfield = null;
    private static $_fieldsortorder = 1;
    private static function _fieldsort_callback($a, $b) {
        if (is_array(self::$_fieldsortfield)) {
            $inner_a = $a;
            $inner_b = $b;
            
            foreach (self::$_fieldsortfield as $i => $field) {
                $inner_a = $inner_a[$field];
                $inner_b = $inner_b[$field];
            }

            if ((is_float($inner_a) && is_float($inner_b) && self::comparefloat($inner_a,$inner_b))) return 0;
            $port = strtolower($inner_a);
            $star = strtolower($inner_b);
            if ($star == $port) return 0;
            return ($port < $star)? -self::$_fieldsortorder : self::$_fieldsortorder;
        } else {
            $port = strtolower($a[self::$_fieldsortfield]);
            $star = strtolower($b[self::$_fieldsortfield]);

            if ($port == $star) return 0;
            return ($port < $star)? -self::$_fieldsortorder : self::$_fieldsortorder;
        }
    }
    public static function fieldsort(&$array, $field, $asc=true) {
        self::$_fieldsortfield = $field;
        self::$_fieldsortorder = ($asc) ? 1 : -1;
        uasort($array, array('UTILITY', '_fieldsort_callback'));
    }

    public static function multidimensionalsort(&$array, $targetcolumnid) {  // DEPRECATED use UTILITY::fieldsort() instead
        UTILITY::fieldsort($array, $targetcolumnid);
    }
    
    public static function comparefloat($a, $b, $e=0.00001) {
        if (!is_float($a) || !is_float($b)) return false;
        return (abs($a-$b) < $e);
    }
    
    // a multiple column sort
    // this will allow an array of associative arrays to be sorted by one field first, followed by another
    // sort directions should have the same index of their respective fields, defaulting to SORT_ASC if a non-valid value is passed
    // eg. columnsort($array, array('lname', 'fname'), array(SORT_ASC, SORT_ASC));
    public static function columnsort(&$array, $columns, $directions=null, $options=null) {
        if (!is_array($columns)) $columns = array($columns);
        if (!is_array($directions)) $directions = array($directions);
        
        $columns = array_reverse($columns, true);
        
        foreach ($columns as $k => $column) {
            $direction = ($directions[$k] === SORT_DESC) ? SORT_DESC : SORT_ASC;
            
            // let's use our existing direction-sensitive fieldsort callback function here
            self::$_fieldsortfield = $column;
            
            if ($direction == SORT_DESC) $array = array_reverse($array, true); // if we're descending, reversing the array should make things easier
            $array = UTILITY::stablesort($array, array('UTILITY', '_fieldsort_callback'));
            if ($direction == SORT_DESC) $array = array_reverse($array, true); // if we're descending, let's make sure to reverse the array once more
            
        }
    }
    
    public static function stablesort($arr, $cmp_function = 'strcmp') {
        return self::mergesort($arr, $cmp_function);
    }
    
    // this provides a stable sorting algorithm, which is necessary for multiple column sorting
    public static function mergesort($arr, $cmp_function = 'strcmp') {
        if (count($arr) <= 1) {
            return $arr;
        }
        
        $left = array_slice($arr, 0, (int)(count($arr)/2), true);
        $right = array_slice($arr, (int)(count($arr)/2), null, true);
        
        $left = self::mergesort($left, $cmp_function);
        $right = self::mergesort($right, $cmp_function);
        
        $result = array();
        
        while (count($left) > 0 && count($right) > 0) {
            if (call_user_func_array($cmp_function, array(reset($left), reset($right))) < 1) {
                $first_key = reset(array_keys($left));
                $result[$first_key] = $left[$first_key];
                unset($left[$first_key]);
            } else {
                $first_key = reset(array_keys($right));
                $result[$first_key] = $right[$first_key];
                unset($right[$first_key]);
            }
        }
        
        $result = array_replace(array_slice($result, 0, count($result), true), $left);
        $result = array_replace(array_slice($result, 0, count($result), true), $right);
        
        return $result;
    }

    // this function return an array of all the values of a key in an array, keeping the array keys
    // eg. array_zoom({aa:{a:1, b:2}, bb:{a:3, b:4}}, b) => {aa:2, bb:4}
    public static function array_zoom($arrays, $target_key) {
        if (!is_array($arrays)) return false;
        
        $values = array();
        foreach ($arrays as $array_key => $array) {
            if (!is_array($array)) return false;
            if (array_key_exists($target_key, $array)) $values[$array_key] = $array[$target_key];
        }
        return $values;
    }
    
    // this function returns the number of leaves in an array; that is, recursively, the number of values that are not arrays
    public static function count_leaves($array) {
        if (!is_array($array)) return 1;
        
        $sum = 0;
        foreach ($array as $key => $value) {
            $sum += self::count_leaves($value);
        }
        return $sum;
    }
    
    // this function returns the largest depth of a multidimensional array
    public static function get_depth($array, $depth=0) {
        if (!is_array($array)) return $depth;
        
        $max = 0;
        foreach ($array as $key => $value) {
            $child_depth = self::get_depth($value, $depth+1);
            if ($child_depth > $max) $max = $child_depth;
        }
        return $max;
    }

    // part Vb - date and time variable helpers

    private static function timespan_seconds($date, $seconddate='now') {  // returns the number of seconds between two text dates
        $starttime = strtotime($date);
        $endtime = strtotime($seconddate);
        return $endtime - $starttime;
    }

    public static function timespan($date, $seconddate='now') {  // returns a nice text representation of the time between two arbitrary dates
        $difference = abs(self::timespan_seconds($date, $seconddate));
        
        $factors = array();
        $factors['second'] = 60.0;
        $factors['minute'] = 60.0;
        $factors['hour']   = 24.0;
        $factors['day']    = 7.0;
        $factors['week']   = 4.348; // average number of weeks in a month = 365.25/12/7
        $factors['month']  = 12.0;
        $factors['year']   = 100.0;

        if ($difference < 15) return 'a few seconds';
        foreach ($factors as $spanname => $duration) {
            if ($difference < ($duration*0.85)) break;
            $difference /= $duration;
        }
        $r = round($difference);
        if ($r!=1) $spanname .= 's';
        return $r .' '. $spanname;
    }

    public static function ago($date) {  // returns the date given, with the timespan in parenthese afterwards
        $difference = self::timespan_seconds($date);
        $agoword = ($difference > 0) ? "ago" : "from now";
        if ($agospan = self::timespan($date)) return "{$date} ({$agospan} {$agoword})";
        else return $date;
    }
    
    // this should all be in the view and customizable by user, not by programmer, but what are you going to do?

    private static function nicedate($datestr=null, $format='l, F n, Y g:i a') {  // returns the date given but formatted in a standardish way
        if (!isset($datestr)) $datestr = date($format); // default now
        if (($timestamp = strtotime($datestr)) === false) return 'undefined';
        else return date($format, $timestamp);
    }

    public static function niceshortdatetime($datestr=null) { // returns a short version of the formatted datetime
        $format = 'n/j/Y g:ia';
        return self::nicedate($datestr, $format);
    }
    public static function niceshortdate($datestr=null) { // returns a short version of the formatted date
        $daysaway = abs(self::timespan_seconds($datestr))/24/3600;
        if (!isset($datestr))      $format = 'n/j/y D'; // "now"
        else if ($daysaway < 7)   $format = 'n/j/y D'; // rather recent -> "day of week"
       // else if ($daysaway < 360)  $format = 'n/j/y'; // more than a couple of months -> "month of year"
        else                       $format = 'n/j/Y';   // more than a year -> whole year
        return self::nicedate($datestr, $format);
    }
    public static function niceshorttime($datestr=null) { // returns a short version of the formatted time
        $format = 'g:ia';
        return self::nicedate($datestr, $format);
    }

    public static function niceshortestdate($datestr=null) { // leaves out the DOW
        $format = 'n/j/y';
        return self::nicedate($datestr, $format);
    }

    public static function nicelongdatetime($datestr=null) { // returns a long version of the formatted datetime
        $daysaway = abs(self::timespan_seconds($datestr))/24/3600;
        if ($daysaway < 7) $format = 'l, F j, Y g:ia';
        else $format = 'F j, Y g:ia';
        return self::nicedate($datestr, $format);
    }
    public static function nicelongdate($datestr=null) { // returns a long version of the formatted date
        $daysaway = abs(self::timespan_seconds($datestr))/24/3600;
        if ($daysaway < 7) $format = 'l, F j, Y';
        else $format = 'F j, Y';
        return self::nicedate($datestr, $format);
    }
    public static function nicelongtime($datestr=null) { // returns a long version of the formatted time
        $format = 'g:ia';
        return self::nicedate($datestr, $format);
    }
    public static function anytimeDate($datestr=null) {  // this is exclusively for the Anytime popup plugin
        $format = 'n/j/y';
        return self::nicedate($datestr, $format);
    }
    
    // NOT highly human-readable dates
    public static function todatetime($datestr, $format='Y-m-d H:i:s') {
        return self::nicedate($datestr, $format); 
    }
    public static function todate($datestr, $format='Y-m-d') {
        return self::nicedate($datestr, $format);
    }

    
    
    // part VI - PHP helpers

    public static function microtime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
 
    public static function elapsed() {  // the time since the first time that elapsed() was called
        static $starttime;
        if (isset($starttime)) return UTILITY::microtime() - $starttime;
        else $starttime = UTILITY::microtime();
        return 0;
    }

    public static function polydecimal($int) {
        // uses chacraters to make the int fit into fewer bytes with more compression than hexidecimals
        return base_convert(226, 10, 36);
    }

    public static function startswith($haystack, $needle) {    // crowdsourced
        return !strncmp($haystack, $needle, strlen($needle));
    }
    public static function endswith($haystack, $needle) {
        return substr($haystack, -strlen($needle))===$needle;
    }

    // part VII - validation helpers

    public static function isValidEmail($email) {
        return EMAIL::isValidEmail($email);
    }
    
    // part VIII - network/browser helpers

    public static function getExternalRoot() {
        $matches = array();
        preg_match('@(https://[^/]+/[^/]+/)@', $_SERVER['SCRIPT_URI'], $matches);
        return $matches[1];
    }
    
    public static function getFullyQualifiedDeepURL($local_url='') {
        return self::getExternalRoot() . $local_url;
    }
    
    public static function getRemoteIp() { // find client's IP Address - including proxy
        return REQUEST::getRemoteIp();
    }

    public static function getRemoteBrowserInfo() {  // attemtps to return an array of facts about the user's browser
        return UserBrowser::getRemoteBrowserInfo();
    }
    
    public static function getCookieName($which=null, $options=null) {
        //Get the name of the cookie depending on the domain we are in
        if(is_null($which) || $which == '') {
            $cookie_name = 'timerExpires';
        }
        else {
            $cookie_name = $which;
        }
        
        $domain = $_SERVER['HTTP_HOST'];
        $subdomain = substr($domain, 0, strpos($domain, '.'));
        // php expects a _ instead of a . ???
        $separator = (isset($options) && $options['use_period']) ? '.' : '_';
        $subdomain = ($subdomain) ? $subdomain . $separator : '';
        return $subdomain . $cookie_name;
    }
    
    public static function str_plus($reset = false) {
        static $cur_pos_of_alpha;
        $alpha_base = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        if($reset || is_null($cur_pos_of_alpha)) $cur_pos_of_alpha = -1;
        $cur_pos_of_alpha += 1;
        return $alpha_base[$cur_pos_of_alpha];
    }
    
    // this sets up attachment as text file, and sends it as an attachment
    // make sure to give this file the correct options to ensure functionality
    public static function sendAttachment($data, $filename=null, $options=null) {
        $default_filename = 'OARSFile.txt';
        $filename = ($filename) ? $filename : $default_filename;
        
        $default_settings = array();
        $default_settings['Content-Disposition'] = 'attachment; filename="'. $filename .'"';
        $default_settings['Content-Type'] = 'text/plain; charset="utf-8"';
        $default_settings['Content-Transfer-Encoding'] = 'base64';
        $default_settings['Expires'] = '0';
        $default_settings['Pragma'] = 'no-cache';
        
        $options = self::asarray($options);
        $settings = array_merge($default_settings, $options);
        
        foreach ($settings as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $data;
        exit;
    }
    
    // part IX - filesystem helpers

    public static function listAllDirectories($dir) { // list all directories in a directory in path=>filename format
        $files = array();
        $d = dir($dir);
        if (!$d) return $files;
        while (false !== ($entry = $d->read())) {
          $path = rtrim($dir, "/") .'/'. $entry;
          if ($entry == '.' || $entry == '..') continue;
          if (is_dir($path)) $files[$path] = $entry;
        }
        $d->close();
        return $files;
    }
    public static function listAllFiles($dir) {  // list all files in a directory in path=>filename format
        $files = array();
        $d = dir($dir);
        if (!$d) return $files;
        while (false !== ($entry = $d->read())) {
          $path = rtrim($dir, "/") .'/'. $entry;
          if ($entry == '.' || $entry == '..') continue;
          if (!is_dir($path)) $files[$path] = $entry;
        }
        $d->close();
        return $files;
    }
    public static function listAllFilesRecursive($dir, $subdir='') {  // all the files in and below the directory in path=>filename format
        $files = array();
        $d = dir($dir);
        if (!$d) return $files;
        while (false !== ($entry = $d->read())) {
          $path = rtrim($dir, "/") .'/'. $entry;
          if ($entry == '.' || $entry == '..') continue;
          if (is_dir($path)) $files = array_merge($files, UTILITY::listAllFilesRecursive($path, "$subdir/$entry"));
          else $files[$path] = "$subdir/$entry";
        }
        $d->close();
        return $files;
    }

    // part X - ini helpers
    
    public static function iniToBytes($size_str) {
        switch (substr ($size_str, -1)) {
            case 'K': case 'k': return (int)$size_str * 1024;
            case 'M': case 'm': return (int)$size_str * 1048576;
            case 'G': case 'g': return (int)$size_str * 1073741824;
            default: return $size_str;
        }
    }
    
    public static function getMaxFileSizeBytes() {
        $pms = self::iniToBytes(ini_get('post_max_size'));
        $umf = self::iniToBytes(ini_get('upload_max_filesize'));
        return ($pms<$umf) ? $pms : $umf;
    }
    
    public static function getMaxFileSizeNice() {
        $pms = ini_get('post_max_size');
        $umf = ini_get('upload_max_filesize');
        return ((self::iniToBytes($pms)<self::iniToBytes($umf)) ? $pms : $umf) .'B';
    }
    
    public static function getMaxUploadTimeMinutes() {
        return round(ini_get('max_input_time')/60);
    }
    
    public static function getMaxUploadTimeNice() {
        return UTILITY::plural(self::getMaxUploadTimeMinutes(), "minute");
    }
    
    /*
     * Generate Random string. This could be useful for passwords.
     */        
    public static function str_rand($length = 8, $seeds = 'alphanum') {
        // Possible seeds
        $seedings['alpha'] = 'abcdefghijklmnopqrstuvwqyz';
        $seedings['lower'] = 'abcdefghijklmnopqrstuvwqyz';
        $seedings['upper'] = 'ABCDEFGHIJKLMNOPQRSTUVWQYZ';
        $seedings['numeric'] = '0123456789';
        $seedings['alphanum'] = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwqyz0123456789';
        $seedings['hexidec'] = '0123456789abcdef';
        $seedings['gattaca'] = 'GATC';
        $seedings['human'] = 'BCDFGHJKLMNPRSTVWXYZbcdfghjkmnprstvwyz2346789';  // avoid vowels and ambiguous characters
        $seedings['special'] = '!@#$%^&**()-=_+[]{}\'|;",./<>?';
        $seedings['allem'] = $seedings['gattaca'] . $seedings['human'] . $seedings['special'];  // for making scary keys

        // Choose seed
        if (isset($seedings[$seeds])) $seeds = $seedings[$seeds];
        if ((!isset($seeds)) || empty($seeds)) $seeds = $seedings['alphanum'];

        // Generate
        $characters = array();
        $seeds_count = strlen($seeds);
        for ($i = 0; $length > $i; $i++) $characters[] = substr($seeds, mt_rand(0, $seeds_count - 1), 1);
        return join('', $characters);
    }
    
    public static function apply($inventory, $template, $templatesubstitution='VVVVV') { // returns an array of items based on $template where $templatesubstituion is replaced by each item in $inventory
        if (!is_array($inventory)) $inventory = array($inventory);
        foreach ($inventory as &$item) $item = str_replace($templatesubstitution, $item, $template);
        return $inventory;
    }
    
    public static function getFullSafeFileName($suggestion=null) {
        return self::getSafeFilePlace() . self::getSafeFileName($suggestion);
    }
    private static function getSafeFileName($suggestion=null) {
        $iam = preg_replace('/.php/', '', substr($_SERVER['SCRIPT_URL'], 1+strrpos($_SERVER['SCRIPT_URL'], '/')));
        $suggestion = (isset($suggestion)) ? substr($suggestion, 0, 64) : '';
        return $_SESSION['oarsadmin']['admin_district'] .'.'. substr(microtime(true), -8) . rand(10,99) .'.'. str_replace(' ','',$iam .'.'. $suggestion);
    }
    private static function getSafeFilePlace($suggestion=null) {
        return FILEUPLOADS_TEMP;
    }
    private static function getSafeWebPlace($suggestion=null) {
        return WEBUPLOADS_TEMP;
    }
    
    public static function getSystemUploadPlace() {    // this is the system-uploads directory path on the FTP server where the system uploads should be stored
        $fol = FILEFTP ."system-uploads/";
        if (!file_exists($fol)) mkdir($fol, 0755);
        $iam = preg_replace('/.php/', '', substr($_SERVER['SCRIPT_URL'], 1+strrpos($_SERVER['SCRIPT_URL'], '/')));
        if (!$iam) $iam = "lost";
        $sup = FILEFTP ."system-uploads/{$iam}/";
        if (!file_exists($sup)) mkdir($sup, 0755);
        return $sup;
    }

    public static function URLAsLocalFile($url, $file_extension = null) {  // fetches the file based on the url and stores it in a temporary file in the web tree and returns a url to that file
        $filename = md5($url);
        if ($file_extension) $filename .= ".". $file_extension; 
        $filelocation = self::saveFromURL($url, $filename);
        return $filelocation;
    }
    public static function URLAsLocalURL($url, $file_extension = null) {  // fetches the file based on the url and stores it in a temporary file in the web tree and returns a url to that file
        $filename = md5($url);
        if ($file_extension) $filename .= ".". $file_extension; 
        self::saveFromURL($url, $filename);
        return WEBUPLOADS_TEMP.$filename;
    }
    private static function saveFromURL($url, $file_name) {  // if the file doesn't exist, get it from the URL
        $pf = FILEUPLOADS_TEMP . $file_name;
        if (!file_exists($pf)) {
            $fp = fopen($pf, "w+");
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            curl_close($ch); 
        }
        return $pf;
    }
    
    
    // Truncate text and appennd ellipsis
    public static function ellipsis($text, $max=100, $append='&hellip;') {
        if (strlen($text) <= $max) {
            return $text;
        }
        $out = substr($text,0,$max);
        if (strpos($text,' ') === FALSE) {
            return $out.$append;
        }
        return preg_replace('/\w+$/','',$out).$append;
    }

    public static function sanitizeHTML($unsafeHTML) { //Tries to protect against against XSS injection attacks. Used by District Announcements.
        $safeTags = '<a><address><em><strong><b><i><big><small><sub><sup><cite><code><ul><ol><li><dl><lh><dt><dd><br><p><table><th><td><tr><pre><blockquote><h1><h2><h3><h4><h5><h6><hr>';
        $allowed_attributes = array('id', 'href');
        $unsafeHTML = strip_tags($unsafeHTML, $safeTags);
        return $unsafeHTML;
    }
    
    public static function sanitizeFilename($unsafeFilename) {
        return preg_replace('/[^a-z0-9]/', '_', strtolower($unsafeFilename));
    }
    
    // this undoes the magic quotes, but undoes it better than stripslashes() because it preserves our LATEX formatting
    // see https://trac.oars.net/redmine/issues/272
    public static function stripextraslashes($val) {
        if (is_array($val)) {
            foreach ($val as $k=>&$v) $v = self::stripextraslashes($v);
            return $val;
        } else {
            $in = $val;
            $val = str_replace('\\\\', '\\', $val); // replaces all double-backslashes with single backslashes
            $val = preg_replace('/\\\\([^a-zA-Z0-9])/', '\1', $val); // leaves \frac &etc. alone
            return $val;
        }
    }
    
    public static function MakeUTF8($mixedstring) {
        // makes a string that is reliably UTF-8
        // this uses a very fast test to look for UTF-8 characters, and if there are, it assumes that the string is all valid UTF-8
        // if there is no valid UTF-8 then it won't hurt to convert it.
        // this is probably not the most efficient way to do this, especially if you know the encoding of your string
        // however it should be reliable
        $is_utf8 = preg_match('%(?:
                 [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
                 |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
                 |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
                 |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
                 |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
                 |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
                 |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
                 )+%xs', $mixedstring);
        if ($is_utf8) return $mixedstring;
        else return utf8_encode($mixedstring);
    }
    
    public static function getFileUploadError($file_error) {
        switch ($file_error) {
            case UPLOAD_ERR_OK: // No error
                break;
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "The uploaded file exceeds the upload_max_filesize directive in php.ini.<br />";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.<br />";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "The uploaded file was only partially uploaded.<br />";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Missing a temporary folder.<br />";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Failed to write file to disk.<br />";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "File upload stopped by extension.<br />";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "No file was uploaded.<br />";
                break;
            default:
                $error_message = "Unknown Error";
            break;
        }
        return $error_message;
    } 
    
    public static function getPrettyHostname($uglyhostname=null) {
        if (!$uglyhostname) $uglyhostname=$_SERVER['HTTP_HOST'];
        $parsedurl = parse_url(trim($uglyhostname)); 
        return trim($parsedurl['host'] ? $parsedurl['host'] : array_shift(explode('/', $parsedurl['path'], 2))); 
    }

    public static function getPrettyFilename($filename, $filetype='.pdf', $include_time=false, $date_string=null) {
        if(strlen($filename) > 140) {
            $filename = substr($filename, 0, 140);
        }
        $filetype = (strpos($filetype, '.') !== false) ? $filetype : '.' . $filetype;
        
        $filename = strtolower($filename);
        $filename = preg_replace('/[^0-9a-zA-Z_]/', '_', $filename);
        
        $date_format = '_Y_md'. (($include_time) ? '_His' : '');
        $date_string = $date_string ? strtotime($date_string) : time();
        $date_string = date($date_format, $date_string);
        
        return $filename.$date_string.$filetype;
    }
    
    // part XI - session 

    // PHP cookies with an optional TTL in seconds
    // initially used with reducing multiple queries
    public static function setSession($name, $value=null, $ttl=null) {
        if (!$name) return;
        if (isset($ttl)) {
            $ttlname = "{$name}__TTL__";
            if ($ttl>0) $_SESSION[$ttlname] = mktime() + $ttl;
            else unset($_SESSION[$ttlname]);
        }
        if (isset($value)) $_SESSION[$name] = $value;
        else unset($_SESSION[$name]);
    }
    public static function getSession($name, $value, $ttl) {  // returns null unless there's something timely in there
        if (!$name) return;
        $ttlname = "{$name}__TTL__";
        if (($_SESSION[$ttlname]) && ($_SESSION[$ttlname] < mktime())) return;
        return $_SESSION[$name];
    }

    // part XII - profiling

    public static function clockman($tag=null) {
        static $clocks;
        static $first_clock;
        
        $now = microtime(true);
        
        if (is_null($first_clock)) $first_clock = $now;
        
        if(is_null($tag)) {
            if (empty($clocks)) return;
            
            $sorted  = array();
            $clocked = 0;
            $total   = $now - $first_clock;
        
            $longest_tag_name = 0;
        
            uasort($clocks, array('self', 'clockman_sort'));
        
            foreach($clocks as $tag=>$clock) {
                $count   = str_pad('x' . $clock['count'], 4, ' ', STR_PAD_LEFT);
                $seconds = str_pad(round($clock['time'], 1), 5, ' ', STR_PAD_LEFT);
                $percent = round(100*$clock['time']/$total, 1);
                $dashes  = str_repeat('-', round($percent));
                $percent = str_pad($percent, 5, ' ', STR_PAD_LEFT);
                $sorted[$tag] = "{$count} {$seconds} Seconds, {$percent}% {$dashes}";
                $clocked     += $clock['time'];
                $longest_tag_name = max($longest_tag_name, strlen($tag));
            }
        
            foreach ($sorted as $tag=>$stuff) {
                $padding = $longest_tag_name - strlen($tag);
                unset($sorted[$tag]);
                $new_tag = str_repeat(' ', $padding) . $tag;
                $sorted[$new_tag] = $stuff;
            }
        
            $nicely = array();
            $nicely['Total Time'] = round($total,2);
            $nicely['Clockman\'s Time'] = round($clocked, 2) . " (" . round(100*$clocked/$total, 1) . "%)";
            $nicely['Clockman\'s Clocks'] = $sorted;
        
            return $nicely;
        }
        
        if(is_null($clocks)) $clocks = array();
        if(is_null($clocks[$tag])) $clocks[$tag] = array('started'=>false, 'time'=>0, 'count'=>0);
        
        $started = $clocks[$tag]['started'];
        
        if($started) {
            $clocks[$tag]['time'] += ($now - $started);
            $clocks[$tag]['started'] = false;
            $clocks[$tag]['count']++;
        } else {
            $clocks[$tag]['started'] = $now;
        }
    }
    
    private static function clockman_sort($a,$b) {
        $aval = $a['time'];
        $bval = $b['time'];
        return ($aval == $bval) ? 0 : (($aval > $bval) ? -1 : 1);
    }
}  // UTILITY class

class BINSTR {
    // this converts oÌ� (o+Â´) to Ã³ (Ã³) (yes, they are different characters; try erasing them)
    public static function combinedToLetter($str) {
        // Normalizer is in the php-intl package
        return Normalizer::normalize($str, Normalizer::FORM_C);
    }
    // Checks string against the internal encoding (hopefully UTF-8) and converts if necessary
    // This is probably VERY SLOW 
    public static function makeOK($str) {
        return (mb_check_encoding($str)) ? $str : iconv("UTF-8", "ISO-8859-1", $str); 
        return (mb_check_encoding($str)) ? $str : mb_convert_encoding($str, mb_internal_encoding(), 'ISO-8859-1');
    }

    public static function isOK($str) {
        return mb_check_encoding($str);
    }

    // * Converts a string's encoding. Originally for use by the FPDF library.  * Luis A. Echeverria 8/25/2005 
    // * LEGACY - DEPRECATED - DON'T USE THIS ANYMORE use makeOK
    public static function ConvertEncoding($str, $to = 'ISO-8859-1', $from = 'UTF-8') {
        // return mb_convert_encoding($str, $to, $from);
        return $str;
    }
    // * Returns a string with special characters replaced with HTML entities.  * Luis A. Echeverria 8/25/2005
    public static function EscapeSpecialChars($str, $encoding = 'UTF-8') {
        return htmlentities($str, ENT_COMPAT, $encoding);
    }

    public static function sizeof($str) {  // an unambigious alias for strlen
        return self::strlen($str);
    }
    public static function strlen($str) { // returns the byte length, instead of the string length. Reverses mb_strlen().
        return mb_strlen($str, '8bit');
    }

    public static function substr($str, $start, $length=null) { // returns the bytes between start and end, instead of the substring. Reverses mb_substr().
        return mb_substr($str, $start, $length, '8bit');
    }

    public static function strpos($haystack, $needle, $offset=0) {
        return mb_strpos($haystack, $needle, $offset, '8bit');
    }
    
    public static function strrpos($haystack, $needle, $offset=0) {
        return mb_strrpos($haystack, $needle, $offset, '8bit');
    }

    public static function strtolower($str) {
        return mb_strtolower($str, '8bit');
    }
    
    public static function strtoupper($str) {
        return mb_strtoupper($str, '8bit');
    }
    
    public static function substr_count($haystack, $needle, $offset=0, $length=0) {
        return mb_substr_count($haystack, $needle, '8bit');
    }
    
} // BINSTR class

class LOG {

    public static function minilog($logname='general', $thingstolog) { // writes a line to a systemlog
        if (!isProduction()) return;
        $logfile = "/var/log/oars/minilog_{$logname}_log";
        $pieces = array();
        $pieces[] = date('YmdHis');
        $pieces[] = $_SERVER[SCRIPT_URI];
        foreach (func_get_args() as $argc=>$argv) if ($argc>0) $pieces[] = preg_replace('#[\r\n]#',"", (is_array($argv)) ? join("\t", $argv) : $argv);
        file_put_contents($logfile, join(" ", $pieces) ."\n", FILE_APPEND | LOCK_EX);
    }
    
    public static function sqlerror($description, $sql) {
        $type='sql_error';
        self::_alert($type, $description, $sql);
        return self::_log($type, $description, $sql);
    }
    public static function sqlwarning($description, $sql) {
        return self::_log('sql_warning', $description, $sql);
    }
    public static function slowquery($description, $sql) {
        return self::_log('sql_slow', $description, $sql);
    }
    
    public static function batcherror($description, $detail='') {
        return self::_log('batch_error', $description, $detail);
    }
    public static function batchmonitor($description, $detail='') {
        return self::_log('batch_monitor', $description, $detail);
    }
    public static function accessviolation($description, $detail = '') {
        $dump = self::_dumpGlobals();
        $detail = "{$detail}\n{$dump}";
        self::_log('access_violation', $description, $detail);
        $message = <<<HERE
        
        This may have occurred if your session timed out, or if you were trying to access a page for which you do not have privileges.<br><br>
        We have passed along the circumstances of this error to an administrator.  The error code is {$errorkey}.
        
HERE;
        Responses::errorpage($message);
    }
    public static function oaerror($description, $detail='') {
        $type='oa_error';
        //EMAIL::alert_support($type, "$description <hr> $detail", 'oac');
        return self::_log($type, $description, $detail);
    }
    public static function oamonitor($description, $detail='') {
        $type='oa_monitor';
        return self::_log($type, $description, $detail);
    }
    // student side
    public static function oaserror($type, $description, $detail='') {
        if (!is_string($type)) $type = 'oas_error';
        else $type = "oaserror_{$type}";
        //EMAIL::alert_support($type, "$description <hr> $detail", 'oac');
        return self::_log($type, $description, $detail);
    }
    public static function oasmonitor($description, $detail='') {
        $type='oas_monitor';
        return self::_log($type, $description, $detail);
    }
    
    public static function accesserror($description, $detail='') {
        $type='access_error';
        // if (isProduction()) self::_alert($type, $description, $detail);
        return self::_log($type, $description, $detail);
    }
    public static function accesswarning($description, $detail='') {
        return self::_log('access_error', $description, $detail);
    }

    public static function applicationerror($description, $detail='') {
        $type='application_error';
        
        $dump = self::_dumpGlobals();
        
        $detail = "{$detail}\n{$dump}";
        
        if (isProduction()) self::_alert($type, $description, $detail);
        return self::_log($type, $description, $detail);
    }
    public static function applicationwarning($description, $detail='') {
        $dump = self::_dumpGlobals();
        $details = "$detail \n $dump";
        if(isProduction()) self::_alert('application warning', $description, $details);
        return self::_log('application_warning', $description, $details);
    }
    public static function applicationmonitor($description, $detail='') {
        return self::_log('application_monitor', $description, $detail);
    }

    public static function create($file_name, $description, $details = '') {
        if($details == '') {
            $details = array('_SESSION'=>$_SESSION);
            unset($details['_SESSION']['userpreferences']);
        }
        return self::_log($file_name, $description, $details);
    }
    private static function _dumpGlobals() {
        $dump = array();
        foreach ($GLOBALS as $k=>$v) {
            if ($k == 'GLOBALS') continue;
            if ($k == '_SESSION') {
                unset($v['userpreferences']);
                /* these others don't exist any more do they? */
                unset($v['oars_data']);
                unset($v['label_long']);
                unset($v['label_table']);
                unset($v['label_short']);
            }
            $dump[] = "{$k}: ". print_r($v, true);
        }
        return implode("\n", $dump);
    }
    
    private static function _logfilename($type) {
        $pieces = array();
        $pieces[] = $type;
        if (isOARSAdmin()) {
            $pieces[] = UTILITY::ifset($_SESSION['oarsadmin']['admin_district'], "oarsadminnodistrict");
        } else if (defined("DISTRICT_ID")) {
            $pieces[] = DISTRICT_ID;
        } else {
            $pieces[] = "nodistrict";
        }
        $pieces[] = BootLib::versionInfo();
        $pieces[] = 'log';
        if (!defined("LOGROOT")) define("LOGROOT","/home/www/oars/uploads/logs/nologroot_");
        return LOGROOT . join('_', $pieces);
    }

    private static function _stamp($type) {
        $pieces = array();
        $pieces[] = $type;
        $pieces[] = date('Y md H\hi:s');
        $pieces[] = "OARS ". BootLib::versionInfo();
        if (isOARSAdmin()) $pieces[] = "OARSAdmin";
        else if (class_exists("Users") && Users::isStudent()) $pieces[] = "Online Assessment";
        else {
            $pieces[] = "district: ". $_SESSION['auth'];
            if (class_exists("Users")) $pieces[] = "user: ". Users::getCurrentUser()->username;
            $pieces[] = "server: ". $_SERVER['SERVER_ADDR'];
        }
        return 'EVENT '. join(' - ', $pieces);
    }
    private static function _log($type='unspecified', $description, $detail) {
        // the values we want from the server array
        $server_keys = array('HTTP_HOST', 'HTTP_COOKIE', 'HTTP_USER_AGENT', 'SERVER_ADDR', 'REMOTE_ADDR', 'SCRIPT_URI');
        $server_info = array();
        foreach ($server_keys as $key) {
            if ($key == 'SSL_SERVER_CERT') continue;
            $server_info[$key] = $_SERVER[$key];
        }
        
        // in case an error happens during login
        $log_post = $_POST;
        unset($log_post['password']);
        
        if (is_array($detail)) $detail = print_r($detail, true);
        $logfile = self::_logfilename($type);
        $entries = array();
        $entries[] = self::_stamp($type);
        $entries[] = '-';
        $entries[] = $description;
        $entries[] = '-';
        $entries[] = $detail;
        $entries[] = '-';
        $entries[] = join("\n", UTILITY::backtrace());
        $entries[] = 'page: '. $_SERVER["REQUEST_URI"];
        $entries[] = '-';
        $entries[] = '_GET:'. print_r($_GET, true);
        $entries[] = '_POST:'. print_r($log_post, true);
        
        if (!isOARSAdmin() && class_exists("Users") && Users::isStudent()) {
            $entries[] = 'NAV:';
            $entries[] = 'level: '. UserNav::getLevel();
            $entries[] = 'school: '. UserNav::getRosterSchoolID();
            $entries[] = 'teacher: '. UserNav::getRosterTeacherID();
            $entries[] = 'section: '. UserNav::getRosterSectionID();
            $entries[] = 'ig: '. UserNav::getIGID();
        }
        $entries[] = '';
        $entries[] = '_SERVER (partial):'. print_r($server_info, true);
        $entries[] = '-';
        $entries[] = 'memory: '. UTILITY::nicesize(memory_get_usage(), 'B') .' - peak memory: '. UTILITY::nicesize(memory_get_peak_usage(), 'B');
        $entries[] = '=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=';

        // prevent file explosions by limiting the size of the $entries
        $totallength=0;
        foreach ($entries as &$entry) {
            $thislength = mb_strlen($entry, '8bit');  // measures bytes, not characters
            if ($thislength > 1024*1024) $entry = "This entry is too large at $thislength bytes and has been removed.";
            else $totallength+=$thislength;
        }
        if ($totallength > 1024*1024*1024) {
            $numberofentries = count($entries);
            $entries=array("This message is way too long at $totallength bytes. There were $numberofentries entries before they were all destroyed. Sorry.");
        }
        // prevent file explosions by only writing a '.' if the file is really big
        clearstatcache(true, $logfile); // work with fresh information for filesize
        if ((!file_exists($logfile)) || ((filesize($logfile)>=0) && (filesize($logfile) < 1*1024*1024*1024))) file_put_contents($logfile, join("\n", $entries) ."\n", FILE_APPEND | LOCK_EX);
        else file_put_contents($logfile, ".", FILE_APPEND | LOCK_EX);  // etcetera

        return $description;
    }
    private static function _alert($type, $description, $detail) {
        //EMAIL::alert_admin($type, "$description <hr> $detail");
    }

}  // LOG class

class Language {
    private static $currentlanguage;

    public static function init() {
        self::$currentlanguage = Language::getDefaultLanguage();
    }
    
    public static function getSupportedLanguages() {
        return array(OARS_LANGUAGE_ENGLISH, OARS_LANGUAGE_SPANISH);
    }
    
    public static function getDefaultLanguage() {
        return OARS_LANGUAGE_ENGLISH;
    }
    
    public static function setLanguage($language) {
        if (in_array($language, Language::getSupportedLanguages())) self::$currentlanguage = $language;
        else self::$currentlanguage = Language::getDefaultLanguage();
    }
    
    public static function getLanguage() {
        return self::$currentlanguage;
    }
    
    public static function getLanguageName($language) {
        if ($language == OARS_LANGUAGE_ENGLISH) return OARS_LANGUAGE_TEXT_ENGLISH;
        elseif ($language == OARS_LANGUAGE_SPANISH) return OARS_LANGUAGE_TEXT_SPANISH;
        elseif ($language == OARS_LANGUAGE_KLINGON) return OARS_LANGUAGE_TEXT_KLINGON;
        else return '';
    }
}
Language::init();

class ActivityLog {
    private function __construct() {}
    
    /**
     * Write user activity to the activity_log table in the db
     * If both params are left blank the we will right the file this was called from and the cur user name
     * @param string $user_id
     * @param string $activity
     * @author srs
     */
    public static function user($activity = null, $user_id = null) {
        if (empty($user_id)) $user_id = Users::getCurrentUser()->getID();
        return self::_doWrite($user_id, null, $activity);
    }
    public static function role($activity = null, $user_id = null, $role_id = null) {
        if (empty($user_id)) $user_id = Users::getCurrentUser()->getID();
        if (empty($role_id)) $role_id = Roles::getCurrentRole()->getID();
        return self::_doWrite($user_id, $role_id, $activity);
    }
    
    public static function create($user_id, $activity) {
        return self::_doWrite($user_id, $activity);
    }
    
    private static function _doWrite($user_id='', $role_id=null, $activity='') {
        if (empty($user_id)) $user_id = Roles::getCurrentRole()->getID();
        $role_id = (isset($role_id)) ? DB::qq($role_id) : "NULL";
        if (is_array($activity)) $activity = join(' | ', $activity);
        
        $activities = array();
        $activities[] = BootLib::versionInfo();
        $activities[] = $_SERVER['SCRIPT_NAME'];
        $activities[] = $_SERVER['REMOTE_ADDR'];
        $activities[] = $activity;
        
        $sql = "INSERT INTO activity_log SET user_id=". DB::qq($user_id) .", role_id={$role_id}, activity=". DB::qq(DB::qe(join(' | ', $activities)));
        $result = DB::runsql_silent($sql);
        return true;
    }
} //ActivityLog class

class AccessLog {
    private function __construct() {}
    
    public static function authorized() { self::_doWrite(Users::getCurrentUser()->getID(), true); }
    
    // this function is not used. Will need to rewrite when it is.
    public static function unauthorized($unauth_user) { self::_doWrite($unauth_user); }
    
    private static function _doWrite($user_id, $auth = false) {
        $remote_addr = BootLib::qe($_SERVER['REMOTE_ADDR']);
        $http_user_agent = BootLib::qe($_SERVER['HTTP_USER_AGENT']);
        $status = ($auth ? "authorized" : "unauthorized");
        
        // if this fails, then we need to create the table from our oarslibrary.access_log_template 
        $sql = "INSERT INTO access_log (user_id, remote_addr, http_user_agent, status)
                    VALUES (". DB::qq($user_id) .", '$remote_addr', '$http_user_agent', '$status')";
        $result = DB::runsql_silent($sql);
        
        return true;
    }
}

// let's keep a new variable in session that queues up messages to display in the front end
// there are 3 message types: flash, notice, and error
class OARSMESSAGE {
    private function __construct() {}
    
    public static function push($messages, $type='flash') {
        if (!is_array($messages)) $messages = array($messages);
        foreach ($messages as $message) {
            $_SESSION['OARSMESSAGE'][$type][] = $message;
        }
    }
    public static function pop($type='flash') {
        return (is_array($_SESSION['OARSMESSAGE'][$type])) ? array_shift($_SESSION['OARSMESSAGE'][$type]) : null;
    }
    public static function flush() {
        $messages = ($_SESSION['OARSMESSAGE']) ? $_SESSION['OARSMESSAGE'] : array();
        $_SESSION['OARSMESSAGE'] = array();
        return $messages;
    }
    
    public static function getInitJS() {
        $messages = self::flush();
        $messages = json_encode($messages);
        $messages_js =<<<END
        var OARSMessageQueue = {$messages};
END;
        return $messages_js;
    }

}  // OARSMESSAGE class

Profiler::start();

class Profiler {
    private static $starttime;
    private static $laptime;
    private static $startmem;
    private static $lapmem;
    private static $sqlruntime;

    public static function start() {
        self::$starttime = UTILITY::microtime();
        self::$laptime = self::$starttime;
        self::$startmem = 0;
        self::$lapmem = memory_get_usage();
        self::$sqlruntime = 0;
    }

    public static function addSQLTime($elapsed) { // add the elapsed sql time to the total run time
        self::$sqlruntime+=$elapsed;
    }
    public static function elapsedSQLTime() {
        return self::$sqlruntime;
    }

    public static function elapsedTime() {
        self::$laptime = UTILITY::microtime();
        return round((self::$laptime - self::$starttime), 3);
    }
    public static function niceElapsedTime() {
        $sincelastlap = round((UTILITY::microtime() - self::$laptime), 3);
        $sincestart = round(self::elapsedTime(), 3);
        $pieces = array();
        if ($sincelastlap==$sincestart) $pieces[] = UTILITY::plural($sincestart, 'second');
        else {
            $pieces[] = UTILITY::plural($sincelastlap, 'second');
            $pieces[] = "(T+". UTILITY::plural($sincestart, 'second') .")";
        }
        if (self::$sqlruntime>0) {
            $pieces[] = "(". UTILITY::plural(self::$sqlruntime, 'second') ." running SQL)";
        }
        return 'elapsed: '. join(" ", $pieces);
    }

    public static function elapsedMem() {
        self::$lapmem = memory_get_usage(true);
        return memory_get_usage(true); // usage in B
    }
    public static function niceElapsedMem() {
        $sincelastlap = memory_get_usage(true) - self::$lapmem;
        $sincestart = self::elapsedMem();
        $pieces = array();
        $pieces[] = UTILITY::nicesize($sincestart, 'B');
        $pieces[] = "(". (($sincelastlap>=0)?'+':'') . UTILITY::nicesize($sincelastlap, 'B') .")";
        return 'memory: '. join(" ", $pieces);
    }

}

class simpleCURL {

    private $settings=array();
    private $url;
    private $data;

    public function __construct($url=null, $data=null) {
        $this->url = $url;
        $this->data = $data;
        $this->settings = array();
        $this->settings[CURLOPT_RETURNTRANSFER] = true;  // return the result on success rather than belching it to console
        $this->settings[CURLINFO_HEADER_OUT] = true;     // prepare for debugging by saving the header
        $this->settings[CURLOPT_FOLLOWLOCATION] = true;  // automatically follow redirects
        $this->settings[CURLOPT_TIMEOUT] = 300;          // wait before giving up
        $this->settings[CURLOPT_SSL_VERIFYPEER] = false; // tolerate https certificate failures
    }

/*
     ob_start();
     curl_exec ($ch);
     curl_close ($ch);
     $string = ob_get_contents();
     ob_end_clean();
*/

    public static function GET($url) {      // used by the GradeCam REST utility for fetching data
        $settings=array();
        $cm = new simpleCURL($url, $data);
        return $cm->sendRequest($settings);
    }

    public static function POSTJSON($url, $data) {  // used by the GradeCam REST utility for printing scan forms
        $settings=array();
        $settings[CURLOPT_CUSTOMREQUEST] = "POST";
        $settings[CURLOPT_HTTPHEADER] = array(
            'Content-Type: application/json',
            'Content-Length: '. strlen($data)
        );
        $cm = new simpleCURL($url, $data);
        return $cm->sendRequest($settings);
    }

    public function sendRequest($settings=null) {
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $this->url);

        if ($this->data) {
            $data = (is_array($this->data)) ? json_encode($this->data) : $this->data;    // magical json_encode has not been tested
            curl_setopt($curl_session, CURLOPT_POSTFIELDS, $data);
        }

        foreach ($this->settings as $option => $value) curl_setopt($curl_session, $option, $value);
        if ($settings) foreach ($settings as $option => $value) curl_setopt($curl_session, $option, $value);

        $response = curl_exec($curl_session);
        // belch(curl_getinfo($curl_session));
        if ($response) return $response;
        else return 'Curl error: '. curl_errno($curl_session) .'  '. curl_error($curl_session);
    }

}

