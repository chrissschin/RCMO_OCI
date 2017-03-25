/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	June 1 2015
 * @link	mailto:kingzmeadow@hotmail.com
 */

 // # Create the Online Check-In namespace  ##############################
var _oci={
    PREVIOUS:'previous',
    NEXT:'next',
    load_calls:[],
    load:function(e){
        window.history.forward(); // Added by Kevin
        
        for(i in _oci.load_calls){
            if(typeof(_oci.load_calls[i]==='function')){
                _oci.load_calls[i](e);
            }
        }
    },
    //Added by Kevin
    noBack:function() {
       window.history.forward(); 
    },
    open:function(url,target,specs,replace){
        window.open(String(url),(typeof(target)==='string'?target:'_blank'),(typeof(specs)==='string'?specs:null),(typeof(replace)==='string'?replace:null));
    },
    show:function(id){
        switch(String(id).toLowerCase()){
            case _oci.PREVIOUS:{
                document.getElementById('main_nav_previous').className="_NO_CLASS_NAME_";
                break;
            }
            case _oci.NEXT:{
                document.getElementById('main_nav_next').className="_NO_CLASS_NAME_";
                break;
            }
        }
    },
    hide:function(id){
        switch(String(id).toLowerCase()){
            case _oci.PREVIOUS:{
                document.getElementById('main_nav_previous').className="not_visible";
                break;
            }
            case _oci.NEXT:{
                document.getElementById('main_nav_next').className="not_visible";
                break;
            }
        }
    },
	event_cancel:function(e){
		if(e.preventDefault){e.preventDefault();}
		if(e.stopPropagation){e.stopPropagation();}
		e.cancelBubble=true;
		return false;
	},
    ajax:function(){
        this.complete=function(){
            return (this.http&&typeof(this.http)==='object'&&this.http.readyState===4);
        };
        this.response=function(){
            return this.http.responseText;
        };
        this.call=function(url,data,callback,method){
            try{
                this.http=new window.XMLHttpRequest;
            }
            catch(e){
                try{
                    this.http=new ActiveXObject("Msxml2.XMLHTTP.6.0");
                }
                catch(e){
                    try{
                        this.http=new ActiveXObject("Msxml2.XMLHTTP.3.0");
                    }
                    catch(e){
                        try{
                            this.http=new ActiveXObject("Msxml2.XMLHTTP");
                        }
                        catch(e){
                            try{
                                this.http=new ActiveXObject("Microsoft.XMLHTTP");
                            }
                            catch(e){
                            }
                        }
                    }
                }
            }

            //Proceed if we have an HTTP Request object
            if(this.http){
                //Prepare passed parameters for usage
                method=(typeof(method)==='string'?method.replace(/^\s|\s$/g,'').toUpperCase():'GET');
                data=String(data).replace(/^(\?|&)/,'');
                url=(String(url)+(method==='POST'?null:((String(url).match(/\?/)?'&':'?')+data)));

                //HTTP Request
                this.http.open(method,url,true);
                this.http.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
                this.http.send(method==='POST'?data:null);
                this.http.onreadystatechange=callback;
            }
        };
    }
};

// # Stop contextual behaviors
document.onmousedown=document.onmousemove=document.onselectstart=function(){
    if(!String(((e=(arguments[0]?arguments[0]:event)).target?e.target:e.srcElement).tagName).match(/input|select|textarea/i)){
        return _oci.event_cancel(e);
    }
};

document.ondragstart=document.oncontextmenu=function(){
    return _oci.event_cancel(arguments[0]?arguments[0]:event);
};
