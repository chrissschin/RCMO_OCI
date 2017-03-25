/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	June 1 2015
 * @link	mailto:kingzmeadow@hotmail.com
 */

// # Create the Online Check-In MAIN namespace  #########################
_oci.main={
    self:null,
    s_c_div:null,
    submit:function(){
        // # Prepare local variables
        var data,field;
        var o=arguments[1];
        if(o.form===undefined||o.form===null||typeof(o.form)!=='object'){
            o.form=document.forms[0];
        }

        // # Determine if we should allow a submit to occur, validate form fields when requested
        if(o.value===undefined || o.value===null || o.getAttribute('data-oci-main-form')!=='validate' || _oci.main.validate.form(o.form)){
            //Reposistion the wrapper to the amount scrolled plus 20% of the window's inner height or the "main" div's offset height, whichever is less, then, add the curtain to the form
            var top_offset=Number(document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop);
            var top_position=Math.ceil(Number(window.innerHeight<=_oci.main.self.offsetHeight?window.innerHeight:_oci.main.self.offsetHeight)*0.20);
            _oci.main.s_c_div.getElementsByTagName('div')[0].style.top=(Number(document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop)+top_position+'px');
            o.form.insertBefore(_oci.main.s_c_div,o.form.getElementsByTagName('input')[0]);

            switch(String(arguments[0]).toUpperCase()){
                case 'MAIN':{
                    data={
                        n:o.id.toLowerCase(),
                        v:o.id.toLowerCase()
                    };
                    break;
                }
                case 'STEP_00':{
                    data={n:_oci.NEXT,v:_oci.NEXT};
                    break;
                }
                case 'STEP_08':{
                    data={n:'CHECK_STATUS',v:'CHECK_STATUS'};
                    break;
                }
                case 'STEP_09':{
                    data=_oci.step_09.tentmate.selection(o,arguments[2],arguments[3]);
                    break;
                }
            }

            //Add a hidden field to specify the type of submit made
            field=o.form.insertBefore(document.createElement('input'),o.form.getElementsByTagName('input')[0]);
            field.type='hidden';
            field.name=('page['+data.n+']');
            field.value=data.v;

            //Submit the form
            o.form.submit();
        }
    },
    validate:{
        country: null,
        messages:null,
        form:function(form){
            // # Declare local variables
            var i,tmp,obj=[],track={},field,type,value,msg,pass=true;

            // # Hide the validation message
            _oci.main.m_n_v_m_div.className=(_oci.main.m_n_v_m_div.className.replace(/\s+not_displayed/g,'')+' not_displayed');

            // # Find INPUT elements that we want to validate against
            for(i=0;i<(tmp=form.getElementsByTagName('input')).length;i++){
                if(tmp[i].getAttribute('data-oci-main-validate')){
                    if(tmp[i]!=undefined&&tmp[i]!=null&&typeof(tmp[i])==='object'&&(type=String(tmp[i].type)).match(/text|password|number|radio|checkbox|tel|email/i)){
                        //Get the current length of the objects array
                        value=obj.length;

                        //RADIO INPUT elements can be numerous though we only want to display one error message for RADIO INPUT elements with the same value for attribute 'name',
                        //track the first RADIO INPUT having a unique value for attribute 'name' and add a property to track if for the series if one was ever selected.
                        if(type.match(/radio/i)){
                            tmp[i]._oci_main_is_checked=false;
                            if(track[(field=String(tmp[i].name))]===undefined){
                                track[field]=value;
                            }
                            if((obj[value]=tmp[i]).checked){
                                obj[track[field]]._oci_main_is_checked=obj[value].checked;
                            }
                        }
                        //Simply add non-radio input elements to the objects array
                        else{
                            obj[value]=tmp[i];
                        }
                    }
                }
            }

            // # Find TEXTAREA elements that we want to validate against
            for(i=0;i<(tmp=form.getElementsByTagName('textarea')).length;i++){
                if(tmp[i].getAttribute('data-oci-main-validate')){
                    if(tmp[i]!=undefined&&tmp[i]!=null&&typeof(tmp[i])==='object'){
                        obj[obj.length]=tmp[i];
                    }
                }
            }

            // # BEGIN validation logic
            for(i=0;i<obj.length;i++){
                if((field=obj[i]).className.match(/(\s+border_error)$/)){
                    field.className=field.className.replace(/(\s+border_error)$/,'');
                    if(field._oci_error_div){
                        field._oci_error_parent.removeChild(field._oci_error_div);
                        field._oci_error_parent=undefined;
                        field._oci_error_div=undefined;
                    }
                }

                type=String(String(field.tagName).match(/input/i)?field.type:'text').toLowerCase();
                value=String(String(field.tagName).match(/input/i)?field.value:field.text);
                tmp=true;
                msg='';

                if((type.match(/^radio$/i)&&obj[track[String(field.name)]]._oci_main_is_checked===false)||
                   (type.match(/^checkbox$/i)&&field.checked===false)||
                   (type.match(/^(radio|checkbox)$/i)===null&&value.length==0)){
                    if(field.getAttribute('data-oci-main-validate').match(/(-|^)required(-|$)/)){
                        tmp=false;
                        msg=_oci.main.validate.messages.empty;
                    }
                }
                else if(type.match(/^email$/i)){
                    //RegEx obtained from the following URL: http://www.w3.org/TR/html51/forms.html#valid-e-mail-address
                    tmp=(value.match(/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/)!==null);
                    msg=_oci.main.validate.messages.email;
                }
                else if(type.match(/^tel$/i)){
                    value=value.replace(/\(|\)|-|\s/g,'').replace(/^1/,'');
                    tmp=(value.match(/\D/)===null&&value.match(/0{10}|1{10}|2{10}|3{10}|4{10}|5{10}|6{10}|7{10}|8{10}|9{10}/)===null&&value.length==10);
                    msg=_oci.main.validate.messages.tel;
                }
                else if(type.match(/^text$/i)){
                    if(field.getAttribute('data-oci-main-validate').match(/(-|^)number(-|$)/)){
                        tmp=(value.replace(/-|\s/g,'').match(/\D/)===null);
                        msg=_oci.main.validate.messages.num;
                    }
                    else if(field.getAttribute('data-oci-main-validate').match(/(-|^)postal-code(-|$)/)){
                        value=(_oci.main.validate.country==='US'?value.replace(/-|\s/g,''):value.replace(/-|_|\s/g,''));
                        tmp=(_oci.main.validate.country==='US'?(value.match(/\D/)===null&&(value.length==5||value.length==9)):(value.match(/\W/)===null&&value.length==6));
                        msg=_oci.main.validate.messages.zip;
                    }
                    else if(field.getAttribute('data-oci-main-validate').match(/(-|^)username(-|$)/)){
                        tmp=(value.replace(/[a-z]|\d|_/gi,'').match(/[\~\`\!\#\$\%\^\&\*\(\)\s\-\+\=\{\[\}\]\|\\\:\;\"\'\<\,\>\?\/]/)===null);
                        msg=_oci.main.validate.messages.usr;
                    }
                    else{
                        tmp=(value.replace(/[a-z]|\d|\s/gi,'').match(/[\~\`\!\#\$\%\^\&\*\(\)\_\-\+\=\{\[\}\]\|\\\:\;\"\'\<\,\>\?\/]/)===null);
                        msg=_oci.main.validate.messages.txt;
                    }
                }

                //On value failed validation...
                if(tmp===false){
                    tmp=null;
                    pass=false;
                    field.className+=' border_error';

                    //If not a RADIO INPUT element append message, otherwise, append message for the first RADIO INPUT element
                    if(type.match(/^radio$/i)===null || field.value===obj[track[String(field.name)]].value){
                        field._oci_error_parent=((value=(type.match(/radio/i)===null?field:obj[track[String(field.name)]]).getAttribute('data-oci-main-container'))===null?field.parentNode:document.getElementById(value));
                        field._oci_error_div=field._oci_error_parent.appendChild(document.createElement('div'));
                        field._oci_error_div.className='color_error';
                        field._oci_error_div.innerHTML=msg;
                        _oci.main.m_n_v_m_div.className=_oci.main.m_n_v_m_div.className.replace(/\s+not_displayed/g,'');
                    }
                }
            }

            //Return the status of the validation
            return pass;
        }
    },
    load:function(e){
        _oci.main.self=document.getElementById('main');
        _oci.main.s_c_div=document.forms[0].removeChild(document.getElementById('submit_curtain'));
        _oci.main.s_c_div.style.display='block';
        _oci.main.m_n_v_m_div=document.getElementById('main_nav_validation_message');
    },
    // Dropdown change language - By Kevin
    lang_switch:function(lang,fr_id,auth){
        
        var url = document.createElement('a');
        url = window.location;
        var new_url = url.protocol +'//' + url.hostname + url.pathname+'?';
        new_url+='fr_id='+fr_id+'&';
        if(auth!=='') {
            new_url+='auth='+auth+'&';
        }
        new_url+='locale='+lang+'&';
        new_url+='up=';
        new_url+=Math.floor((Math.random() * 99999) + 1)+(new Date%9e6).toString(36);
        window.location.href = new_url;
    },
    // Logout button - By Kevin
    user_logout:function(){
        if(confirm("Are you sure to logout?")) {
        window.location.href = window.location.href +'&logout=1';
    }
    }    
};

// # Insert load function to be called
_oci.load_calls[_oci.load_calls.length]=_oci.main.load;
