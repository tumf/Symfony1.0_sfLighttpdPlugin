<?php
pake_desc('launch lighttpd');
pake_task('lighttpd', 'project_exists');

pake_desc('setup lighttpd');
pake_task('init-lighttpd', 'project_exists');




sfConfigurePlugin::addItem
("LIGHTTPD",array(
                     "option"=>"--with-lighttpd=CMD" , 
                     "default" => "lighttpd",
                     "description" => "lighttpd command"),"lighttpd");

sfConfigurePlugin::addItem
("SERVER_PORT",array(
                     "option"=>"--with-server-port=PORT" , 
                     "default" => rand(10100,10800),
                     "description" => "server port to listen"),"lighttpd");
sfConfigurePlugin::addItem
("SERVER_BIND",array(
                     "option"=>"--with-server-bind=SERVER" , 
                     "default" => "localhost",
                     "description" => "server name or ip to listen"),"lighttpd");


sfConfigurePlugin::addItem
("OPENURL",array(
                     "option"=>"--with-openurl=CMD" , 
                     "default" => "open",
                     "description" => "open url command"),"lighttpd");

sfConfigurePlugin::addItem
("PHP_FCGI",array(
                  "option"=>"--with-php-fcgi=PHP_FCGI" , 
                  "default" => "/usr/local/bin/php-fcgi",
                  "description" => "php(fast-cgi) path"),"lighttpd");
/**
 *
 * symfony configure --with-dsn=mysql://root:@localhost/dbname
 *
 */
function run_lighttpd($task,$args){
    if(!file_exists("config/lighttpd.conf")) {
        throw new Exception('missing config/lighttpd.conf');
    }
    if(!count($args)){
        throw new Exception('usage: symfony lighttpd [start|stop|open]');
    }

    $command = array_shift($args);
    switch($command){
    case "start":
        __lighttpd_start($task,$args);
        break;
    case "stop":
        __lighttpd_stop($task,$args);
        break;
    case "open":
        __lighttpd_open($task,$args);
        break;
    }

}

function __lighttpd_start($task,$args){
    if($pid = __lighttpd_is_started()){
        pake_echo(sprintf("server (%d) already started",$pid));
        return;
        //throw new Exception(sprintf("server (%d) already started",$pid));
    }
    if(!($lighttpd = $task->get_property('command','lighttpd'))){
        $lighttpd = "lighttpd";
    }
    pake_sh(sprintf("%s -f config/lighttpd.conf &",$lighttpd));
    sleep(1);
}

function __lighttpd_is_started(){
    if(file_exists("log/lighttpd.pid")){
        $pid = (int)file_get_contents("log/lighttpd.pid");
        if(strlen(`ps -p ${pid} -o pid=`) == 0 ){
            pake_echo("${pid} dead but pid file exists.");
            pake_remove("log/lighttpd.pid",null);
            return false;
        }
        return $pid;
    }
    return false;
}

function __lighttpd_stop($task,$args){
    $pid = __lighttpd_is_started();
    if(!$pid){
        throw new Exception("server is not started");
    }
    pake_remove("log/lighttpd.pid",null);
    pake_sh(sprintf("kill %d",$pid));
}

function __lighttpd_open($task,$args){
    if(!__lighttpd_is_started()){
        __lighttpd_start($task,$args);
        if(!__lighttpd_is_started()){
            throw new Exception("server is not started");
        }
    }
    try{
        $openurl = $task->get_property('openurl','lighttpd');
    }catch(Exception $e){
        $openurl = "open";
    }
    try{
        $server_bind = $task->get_property('server_bind','lighttpd');
    }catch(Exception $e){
        $server_bind = "localhost";
    }
    try{
        $server_port = $task->get_property('server_port','lighttpd');
    }catch(Exception $e){
        $server_port = "10090";
    }

    pake_sh(sprintf("%s http://%s:%d",$openurl,$server_bind,$server_port));
}



function run_init_lighttpd($task,$args){
    
    $content = '
server.modules              = (
                                "mod_rewrite",
                                "mod_alias",
                                "mod_fastcgi",
                                "mod_accesslog" )

server.document-root        = "##PWD##/web"
alias.url = ( "/sf/" => "##SYMFONY_DATA_DIR##/web/sf/" )

accesslog.filename          = "##PWD##/log/lighttpd-access.log"
server.errorlog             = "##PWD##/log/lighttpd-error.log"
server.pid-file             = "##PWD##/log/lighttpd.pid"

index-file.names	    = ("index.php")

# server.event-handler = "freebsd-kqueue" # needed on OS X
static-file.exclude-extensions = ( ".php" )
server.port = ##SERVER_PORT##
server.bind = "##SERVER_BIND##"
fastcgi.server             = ( ".php" =>
                               ( "##SERVER_BIND##" =>
                                 (
                                   "socket" => "##PWD##/log/php-fastcgi.socket",
                                   "bin-path" => "##PHP_FCGI##"
                                 )
                               )
                            )
url.rewrite-once = (
        "^/(.*\..+(?!html))$" => "$0",
        "^/(.*)\.(.*)"        => "$0",
        "^/([^.]+)$"          => "/index.php/$1",
        "^/$"                 => "/index.php"
)

# mimetype mapping
mimetype.assign             = (
  ".pdf"          =>      "application/pdf",
  ".sig"          =>      "application/pgp-signature",
  ".spl"          =>      "application/futuresplash",
  ".class"        =>      "application/octet-stream",
  ".ps"           =>      "application/postscript",
  ".torrent"      =>      "application/x-bittorrent",
  ".dvi"          =>      "application/x-dvi",
  ".gz"           =>      "application/x-gzip",
  ".pac"          =>      "application/x-ns-proxy-autoconfig",
  ".swf"          =>      "application/x-shockwave-flash",
  ".tar.gz"       =>      "application/x-tgz",
  ".tgz"          =>      "application/x-tgz",
  ".tar"          =>      "application/x-tar",
  ".zip"          =>      "application/zip",
  ".mp3"          =>      "audio/mpeg",
  ".m3u"          =>      "audio/x-mpegurl",
  ".wma"          =>      "audio/x-ms-wma",
  ".wax"          =>      "audio/x-ms-wax",
  ".ogg"          =>      "application/ogg",
  ".wav"          =>      "audio/x-wav",
  ".gif"          =>      "image/gif",
  ".jpg"          =>      "image/jpeg",
  ".jpeg"         =>      "image/jpeg",
  ".png"          =>      "image/png",
  ".xbm"          =>      "image/x-xbitmap",
  ".xpm"          =>      "image/x-xpixmap",
  ".xwd"          =>      "image/x-xwindowdump",
  ".css"          =>      "text/css",
  ".html"         =>      "text/html",
  ".htm"          =>      "text/html",
  ".js"           =>      "text/javascript",
  ".asc"          =>      "text/plain",
  ".c"            =>      "text/plain",
  ".cpp"          =>      "text/plain",
  ".log"          =>      "text/plain",
  ".conf"         =>      "text/plain",
  ".text"         =>      "text/plain",
  ".txt"          =>      "text/plain",
  ".dtd"          =>      "text/xml",
  ".xml"          =>      "text/xml",
  ".mpeg"         =>      "video/mpeg",
  ".mpg"          =>      "video/mpeg",
  ".mov"          =>      "video/quicktime",
  ".qt"           =>      "video/quicktime",
  ".avi"          =>      "video/x-msvideo",
  ".asf"          =>      "video/x-ms-asf",
  ".asx"          =>      "video/x-ms-asf",
  ".wmv"          =>      "video/x-ms-wmv",
  ".bz2"          =>      "application/x-bzip",
  ".tbz"          =>      "application/x-bzip-compressed-tar",
  ".tar.bz2"      =>      "application/x-bzip-compressed-tar"
 )';
    file_put_contents("config/lighttpd.conf.in",$content);
    pake_echo_action("+file","config/lighttpd.conf.in");


    $prop = file_get_contents("config/properties.ini.in");
    if(!preg_match("/lighttpd/",$prop)){
        if($prop){
            $prop .= "
[lighttpd]
  command=##LIGHTTPD##
  server_port=##SERVER_PORT##
  server_bind=##SERVER_BIND##
  openurl=##OPENURL##
";
            file_put_contents("config/properties.ini.in",$prop);
            pake_echo_action("+file","config/properties.ini.in");
        }
    }



    $content = '#!/usr/bin/env bash
openurl="##OPENURL##"
url="http://##SERVER_BIND##:##SERVER_PORT##"
sf_project_dir="##PWD##"
lighttpd="##LIGHTTPD##"

cmd=$1
pidfile="log/lighttpd.pid"
cd ${sf_project_dir-.}

if [ "${cmd-no}" = "start" ] ; then
    if [ -f log/lighttpd.pid ] ; then
	pid=`cat ${pidfile}`
	echo "server (${pid}) already started"
	exit
    fi

    ${lighttpd-lighttpd}  -f config/lighttpd.conf
    pid=`cat ${pidfile}`
    echo "server started(${pid})"
    exit
fi

if [ "${cmd-no}" = "stop" ] ; then
    if [ -f log/lighttpd.pid ] ; then
	pid=`cat ${pidfile}`
	kill ${pid}
	echo "server stopped(${pid})"
    exit
    fi
fi

if [ "${cmd-no}" = "open" ] ; then
    ${openurl} ${url}
    exit
fi

echo "usage: ${0} [start|stop|open]"
';
    #file_put_contents("server",$content);
    #pake_echo_action("+file","server");
    #pake_sh("chmod +x server");
}
