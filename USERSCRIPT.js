(function(){
    var s, k, c;
    var _tmo = null;
    var targetURL = 'https://REMOVED.com/get_stream.php';

    function intercepted(data){
        if(typeof data.k_url != 'undefined'){
            setLatencyCheck();
            $.ajax({
                url : targetURL,
                type : 'POST',
                data : {
                    'video_url' : data.video_url,
                    'k_url' : data.k_url
                },
                error : function(){
                    clearTimeout(_tmo);
                    setError();
                },
                success : function(r){
                    clearTimeout(_tmo);
                    try {
                        r = JSON.parse(a(r));

                        if(r.c){
                            c(r.c);
                        }else if(r.k){
                            k(r.k);
                        }
                    } catch(err){
                        setError();
                    }
                }
            });
        }else{
            setLatencyCheck();
            $.ajax({
                url : targetURL,
                type : 'POST',
                data : {
                    'video_url' : data.video_url,
                    'bu_url' : data.bu_url
                },
                error : function(){
                    clearTimeout(_tmo);
                    setError();
                },
                success : function(r){
                    clearTimeout(_tmo);
                    try {
                        r = JSON.parse(a(r));

                        if(r.c){
                            c(r.c);
                        }else if(r.s){
                            s(r.s);
                        }
                    } catch(err){
                        setError();
                    }
                }
            });
        }
    }

    function a(b){
        return atob(b);
    }

    function setLatencyCheck(){
        clearTimeout(_tmo);
        _tmo = setTimeout(latency, 4500);
    }

    function setError(){
        genMsg('Luminous has experienced an issue. Make sure you have the latest version!', true);
    }

    function latency(){
        genMsg('Luminous is experiencing a slow connection or failure. Let the page load for at least a minute or two before attempting <b>(do not refresh)</b>. If nothing happens, Luminous is currently not working. Make sure you have the latest version!', true);
    }

    function active(){
        genMsg('You are now watching a game that would normally be unavailable to you! If you would like to support Luminous, <a href="https://www.paypal.me/LuminousExtension" target="_blank" style="color:#800000;">you can make a donation</a>.');
    }

    function genMsg(msg, isErr){
        var nId = Math.random().toString(36).substring(7);
        
        var colorCSS = (!isErr) ? 'background-color:grey;border-bottom:2px solid black;' : 'background-color:#ff8080;border-bottom:2px solid #800000;';

        msg = '<div id="'+ nId +'" style="'+ colorCSS +'padding:4 10 4 10;"><div style="margin-left:15px;display:inline-block;">'+msg+'</div> <div style="float:right;display:inline-block;margin-right:15px;"><a href="#" id="blkOut_'+ nId +'_Notification" style="color:#800000;">Close Message</a></div></div>';

        $('header').after(msg);

        var elId = '#blkOut_'+ nId +'_Notification';

        $(document).on('click', elId, function(){
            $('#'+nId).remove();
        });
    }
    
    (function(){
        var url = null;
        var keyUrl = null;
        var base = location.href;
        
        var interceptedXhr = null;
        var curState = 0;

        var s = null;
        var k = null;
        var t = null;

        var strs = ['video_url', 'k_url', 'bu_url'];
        
        function hookOpen(){
            var oldOpen;

            oldOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function(m,u){
                this.url = u;
                oldOpen.apply(this, arguments);
            }
        }

        function hookSend(callback){
            var oldSend, i;
            if( XMLHttpRequest.callbacks ) {
                XMLHttpRequest.callbacks.push( callback );
            } else {
                XMLHttpRequest.callbacks = [callback];

                oldSend = XMLHttpRequest.prototype.send;

                XMLHttpRequest.prototype.send = function(){
                    for( i = 0; i < XMLHttpRequest.callbacks.length; i++ ) {
                        XMLHttpRequest.callbacks[i]( this );
                    }
                    oldSend.apply(this, arguments);
                }
            }
        }

        function intercept(xhr,callback){
            if(xhr.url == url){
                xhr.onreadystatechange = function(e){
                    if(((xhr.url == url && xhr.readyState == 4) || xhr.url == keyUrl) && xhr.readyState == 4){
                        if(xhr.status == 403){
                            xhr.old_onerror = xhr.onerror;
                            xhr.old_onload = xhr.onload;
                            xhr.old_onprogress = xhr.onprogress;
                            xhr.old_onreadystatechange = xhr.onreadystatechange;
                            xhr.eee = e;

                            xhr.onerror = null;
                            xhr.onload = null;
                            xhr.onreadystatechange = null;
                            xhr.onprogress = null;

                            interceptedXhr = xhr;

                            callback();
                        }
                    }
                }
            }else{
                xhr.old_onreadystatechange = xhr.onreadystatechange;
                xhr.onreadystatechange = function(e){
                    if(xhr.readyState == 4){
                        xhr.e = e;

                        interceptedXhr = xhr;

                        callback();
                    }
                }
            }
        }

        function spoof(){
            xhr = interceptedXhr;

            var v;
            if(curState == 1){
                v = JSON.stringify(s);
            }else{
                var uin = new Uint8Array(transcribeKey(k));
                v = uin;
            }

            Object.defineProperty(xhr, 'response', {
                get: function() { return v },
                set: function(newValue) {  },
                configurable : true
            });
            Object.defineProperty(xhr, 'responseText', {
                get: function() { return v },
                set: function(newValue) {  },
                configurable : true
            });
            Object.defineProperty(xhr, 'statusText', {
                get: function() { return 'OK' },
                set: function(newValue) { },
                configurable : true
            });
            Object.defineProperty(xhr, 'status', {
                get: function() { return 200 },
                set: function(newValue) { },
                configurable : true
            });

            if(xhr.url == url){
                xhr.old_onload();
            }else{
                xhr.old_onreadystatechange(xhr.e);

                active();
            }
        }

        function transcribeKey(key){
            var arr = [];
            for(i = 0; i < key.length; i++){
                arr.push(key.charCodeAt(i));
            }
            return arr;
        }

        function sendEvent(name,data,callback){
            var evt=document.createEvent('CustomEvent');
            evt.initCustomEvent(name, true, true, data);
            document.dispatchEvent(evt);
        }

        hookOpen();
        hookSend(function(xhr) {
            if(xhr.url.includes('browser~unlimited')){
                if(!url){
                    url = xhr.url;

                    intercept(xhr,function(){
                        curState = 1;

                        pl = {};
                        pl[strs[0]] = base;
                        pl[strs[2]] = xhr.url;

                        intercepted(pl);
                    });
                }else{
                    intercept(xhr, function(){
                        spoof();
                    });
                }
            }

            if(xhr.url.includes('/keys/')){
                if(curState == 1){
                    if(!keyUrl){
                        keyUrl = xhr.url;

                        intercept(xhr,function(){
                            curState = 2;

                            if(k){
                                spoof();
                            }else{
                                pl = {};
                                pl[strs[0]] = base;
                                pl[strs[1]] = xhr.url;

                                intercepted(pl);
                            }
                        });
                    }else{
                        intercept(xhr, function(){
                            spoof();
                        });
                    }
                }
            }
        });

        s = function(x){
            s = x;
            spoof();
        }
        
        k = function(x){
            k = x;
            spoof();
        }

        c = function(x){
            s = x.s;
            k = x.k;
            spoof();
        }
    })();
})();