可以按照以下步骤来部署和运行程序:
1.请确保机器 已经安装了Yaf框架, 并且已经加载入PHP;
2.把sample目录Copy到Webserver的DocumentRoot目录下;
3.需要在php.ini里面启用如下配置，生产的代码才能正确运行：
	yaf.environ="product"
4.重启Webserver;
5.访问http://yourhost/sample/,出现Hellow Word!, 表示运行成功,否则请查看php错误日志;


踩过的坑：
1、根据php扩展对应的配置不同，Controller、Model、Plugin的class命名也是不同的，比如：
   Controller_Index、ControllerIndex、IndexController
   一定要清楚yaf.ini里的配置，来相应进行命名，否则就一直各种错误
2、部署在odp上，如果关闭自动加载模板： Yaf_Dispatcher::getInstance()->autoRender(false);
   那么在模板文件找不到时，不会渲染，也不会报任何错误的
3、所有的目录名必须小写，所有的html模板名也必须小写，php无所谓
   否则会出现文件找不到，如果是模板，则参考第2点，不报任何错误啊啊啊


NGINX配置：
1、nginx.conf 增加如下配置，可以在error_log看到url路由结果：
    http {
        rewrite_log on;
2、rewrite 配置参考：
server {
    location ~ ^/(favicon.ico|static) {
        root            /home/work/odp/webroot;
    }

    location ~ \.(js|css|gif|jpg|jpeg|png|ico|swf|ttf|woff|woff2|eot|otf|svg|html?)$ {
        root            /home/work/odp/webroot;
    }
    location / {
        root /home/work/odp/webroot;
        index index.php;
        fastcgi_pass    $php_upstream;
        include         fastcgi.conf;

        rewrite ^(/.*)?$  /index.php$1 break;
    }