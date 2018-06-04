/**
 * 
 * @authors peterYu (1378056783@qq.com)
 * @date    2017-11-18 13:18:58
 * @version $Id$
 */
accessid = ''
accesskey = ''
host = ''
policyBase64 = ''
signature = ''
callbackbody = ''
filename = ''
key = ''//保存线上存储路径
expire = 0
g_object_name = ''
g_object_name_type = ''
now = timestamp = Date.parse(new Date()) / 1000; 

function send_request(upType)
{
    var xmlhttp = null;
    if (window.XMLHttpRequest)
    {
        xmlhttp=new XMLHttpRequest();
    }
    else if (window.ActiveXObject)
    {
        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
  
    if (xmlhttp!=null)
    {//http://192.168.10.24:81/stat/uploadkey//http://ad.miduo.com/auth/getosskey
        serverUrl = 'http://ad.miduo.com/auth/getosskey?type='+upType;
        xmlhttp.open( "GET", serverUrl, false );
        xmlhttp.send( null );//type=1是图片type=2是安装包
        var res = eval("("+xmlhttp.response+")");
        return res.data;
    }
    else
    {
        alert("Your browser does not support XMLHTTP.");
    }
};

function get_signature(upType)
{
    //可以判断当前expire是否超过了当前时间,如果超过了当前时间,就重新取一下.3s 做为缓冲
    now = timestamp = Date.parse(new Date()) / 1000; 
    if (expire < now + 3)
    {
        body = send_request(upType);
        //console.log(body);
        //var obj = eval("("+body+")");
        //console.log(body);
        host = body['host'];
        policyBase64 = body['policy'];
        accessid = body['accessid'];
        signature = body['signature'];
        expire = parseInt(body['expire']);
        callbackbody = body['callback'];
        key = body['dir'];/**/
        return true;
    }
    return false;
};

function random_string(len) {
　　len = len || 32;
　　var chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678';   
　　var maxPos = chars.length;
　　var pwd = '';
　　for (i = 0; i < len; i++) {
    　　pwd += chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return pwd;
}

function get_suffix(filename) {
    pos = filename.lastIndexOf('.')
    suffix = {}
    if (pos != -1) {
        suffix.exe = filename.substring(pos);
        suffix.na = filename.substring(0,pos);
    }
    return suffix;
}

function calculate_object_name(filename)
{
    suffix = get_suffix(filename);
    g_object_name = key + suffix.na + "_" + random_string(10) + suffix.exe;
    /*g_object_name += "${filename}";
    return ''*/
}

function get_uploaded_object_name()
{
    return g_object_name
    /*tmp_name = g_object_name;
    tmp_name = tmp_name.replace("${filename}", filename);
    return tmp_name;*/
}

function set_upload_param(up, filename, ret,upType)
{
    if (ret == false)
    {
        ret = get_signature(upType)
    }
    g_object_name = key;
    if (filename != '') {
        suffix = get_suffix(filename)
        calculate_object_name(filename)
    }
    new_multipart_params = {
        'key' : g_object_name,
        'policy': policyBase64,
        'OSSAccessKeyId': accessid, 
        'success_action_status' : '200', //让服务端返回200,不然，默认会返回204
        'callback' : callbackbody,
        'signature': signature,
    };

    up.setOption({
        'url': host,
        'multipart_params': new_multipart_params
    });

    up.start();
}
var $fileList = $('#file-list'),
    $doc = $(document);
    //$postFile = $("#postfiles");

var uploader = new plupload.Uploader({
    runtimes : 'html5,flash,silverlight,html4',
    browse_button : 'selectfiles', 
    //multi_selection: false,
    container: document.getElementById('container'),
    url : 'http://oss.aliyuncs.com',
    filters: {
        mime_types : [ //只允许上传图片/安装包文件
        { title : "图片文件", extensions : "jpg,gif,png,bmp" }, 
        { title : "安装包", extensions : "apk" }
        ],
        max_file_size : '1024mb', //最大只能上传10mb的文件
        prevent_duplicates : true //不允许选取重复文件
    },
    init: {
        PostInit: function() {
            $fileList.html('');
            /*
            $postFile.on('click', function() {
                var upType = $(this).data('uptype');
                if (uploader.files.length < 1) {
                    console.log('请选择文件！');
                    return false;
                }
                set_upload_param(uploader, '', false,upType);
            })*/
        },

        FilesAdded: function(up, files) {
            for (var i = 0, len = files.length; i < len; i++) {
                var fileType = files[i].type;
                if(!/image\//.test(fileType)){
                    console.log("上传app大小："+files[i].size);
                    if(files[i].size > 1073741824){
                        uploader.files.splice(i, 1);
                        alert("上传安装包小于1G");
                    }else{
                        ! function(i) {
                            $fileList.html($fileList.html() +'<div id="' + files[i].id + '">' + files[i].name + ' (' + plupload.formatSize(files[i].size) + ')<b></b>'
                +'<div class="progress"><div class="progress-bar" style="width: 0%"></div></div></div>');/*$fileList.html() +
                                '<div style="float:left" class="apk_list" id="' + files[i].id + '">' +
                                ' (' + files[i].name + "/" + plupload.formatSize(files[i].size) +
                                ')<a href="javascript:;" class="apk_delete" data-val=' + files[i].id +
                                '>删除</a></div>'*/
                            setTimeout(function(){
                                postFile('#selectfiles');
                            },300);
                        }(i);
                    }
                }else{
                    console.log("上传图片大小："+files[i].size);
                    if (files[i].size > 2097152) {
                        uploader.files.splice(i, 1);
                        alert("上传图片小于2MB");
                    } else {
                        ! function(i) {
                            previewImage(files[i], function(imgsrc) {
                                $fileList.html($fileList.html() +
                                    '<div style="float:left" class="pic_list" id="' + files[i].id + '">' +
                                    ' ('+ files[i].name + "/" + plupload.formatSize(files[i].size) +
                                    ')<a href="javascript:;" class="pic_delete" data-val=' + files[i].id +
                                    '>删除</a><br/>' +
                                    '<img class="listview" width="90%" src="' + imgsrc + '" name="' + files[i].name + '" /></div>');
                            })
                        }(i);
                    }
                }
                
            }
        },

        BeforeUpload: function(up, file) {
            set_upload_param(up, file.name, true);
        },

        UploadProgress: function(up, file) {
            var d = document.getElementById(file.id);
            d.getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
            var prog = d.getElementsByTagName('div')[0];
            var progBar = prog.getElementsByTagName('div')[0]
            progBar.style.width= 2*file.percent+'px';
            progBar.setAttribute('aria-valuenow', file.percent);
        },

        FileUploaded: function(up, file, info) {
            //alert("上传成功！文件名:" + get_uploaded_object_name(file.name));
            document.getElementById("apkurl").value = get_uploaded_object_name(file.name);
            $fileList.html("上传成功！路径:" + get_uploaded_object_name(file.name));
        },

        Error: function(up, err) {
            if (err.code == -600) {
                console.log("\n选择的文件太大了,可以根据应用情况，在upload.js 设置一下上传的最大大小");
            }
            else if (err.code == -601) {
                console.log("\n选择的文件后缀不对,可以根据应用情况，在upload.js进行设置可允许的上传文件类型");
            }
            else if (err.code == -602) {
                console.log("\n这个文件已经上传过一遍了");
            }
            else 
            {
                console.log("\nError xml:" + err.response);
            }/**/
        }
    }
});
uploader.init(); //初始化
function previewImage(file, callback) { //file为plupload事件监听函数参数中的file对象,callback为预览图片准备完成的回调函数
    if (!file || !/image\//.test(file.type)) return; //确保文件是图片
    if (file.type == 'image/gif') { //gif使用FileReader进行预览,因为mOxie.Image只支持jpg和png
        var fr = new mOxie.FileReader();
        fr.onload = function() {
            callback(fr.result);
            fr.destroy();
            fr = null;
        }
        fr.readAsDataURL(file.getSource());
    } else {
        var preloader = new mOxie.Image();
        preloader.onload = function() {
            //preloader.downsize(550, 400);//先压缩一下要预览的图片,宽300，高300
            var imgsrc = preloader.type == 'image/jpeg' ? preloader.getAsDataURL('image/jpeg', 80) : preloader.getAsDataURL(); //得到图片src,实质为一个base64编码的数据
            callback && callback(imgsrc); //callback传入的参数为预览图片的url
            preloader.destroy();
            preloader = null;
        };
        preloader.load(file.getSource());
    }
}
function postFile(obj){
    var upType = $(obj).data('uptype');
    if (uploader.files.length < 1) {
        console.log('请选择文件！');
        return false;
    }
    set_upload_param(uploader, '', false,upType);
}
function up_delete(obj){
    var $obj = $(obj);
    $obj.parent().remove();
    var toremove = '';
    var id = $obj.attr("data-val");
    for (var i in uploader.files) {
        if (uploader.files[i].id === id) {
            toremove = i;
        }
    }
    uploader.files.splice(toremove, 1);
}

$doc.on('click', '.pic_list a.pic_delete', function() {
    up_delete(this);
}).on('click', '.apk_list a.apk_delete', function() {
    up_delete(this);
});