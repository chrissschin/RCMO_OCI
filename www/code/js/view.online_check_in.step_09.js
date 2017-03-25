/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	June 1 2015
 * @link	mailto:kingzmeadow@hotmail.com
 */

_oci.step_09={
    tentmate:{
        txt:null,
        s_r_tbody:null,
        loading:null,
        btn_st:null,
        selection:function(btn,tms_id,tm_id){
            if(tms_id===4){
                elm=btn.form.insertBefore(document.createElement('input'),btn.form.getElementsByTagName('input')[0].nextSibling);
                elm.type='hidden';
                elm.name='tentmate_id';
                elm.value=parseInt(tm_id);
            }

            return {n:_oci.NEXT,v:tms_id};
        },
        ta_toggle:function(ta){
            if(ta.value===''){
                ta.value=_oci.step_09.tentmate.txt.ta_default;
            }
            else if(ta.value===_oci.step_09.tentmate.txt.ta_default){
                ta.value='';
            }
        },
        search:function(btn){
            _oci.step_09.tentmate.s_r_tbody.innerHTML='';
            var row=_oci.step_09.tentmate.s_r_tbody.insertRow(-1);
            var cell=row.insertCell(-1);
            cell.colSpan=3;
            cell.innerHTML=_oci.step_09.tentmate.loading.outerHTML;

            var fields='&ajax=tent_mate_search';
            fields+=('&search_email='+btn.form.search_email.value);
            fields+=('&search_first_name='+btn.form.search_first_name.value);
            fields+=('&search_last_name='+btn.form.search_last_name.value);

            _oci.step_09.tentmate.ajax_tentmate_search=new _oci.ajax();
            _oci.step_09.tentmate.ajax_tentmate_search.btn=btn;
            _oci.step_09.tentmate.ajax_tentmate_search.call(window.location,fields,_oci.step_09.tentmate.result);
            _oci.step_09.tentmate.ajax_tentmate_search.btn.disabled=true;
        },
        result:function(e){
            if(_oci.step_09.tentmate.ajax_tentmate_search && _oci.step_09.tentmate.ajax_tentmate_search.complete()){
                eval('var ajax_res='+_oci.step_09.tentmate.ajax_tentmate_search.response());
                var row,cell,code,btn,i;
                _oci.step_09.tentmate.s_r_tbody.innerHTML='';
                for(i in ajax_res){
                    code=('_oci.open(decodeURIComponent(\''+ajax_res[i].page+'\'),\'_blank\');');
                    row=_oci.step_09.tentmate.s_r_tbody.insertRow(-1);
                    row.className='data_fetched';
                    cell=row.insertCell(-1);
                    cell.innerHTML=ajax_res[i].name;
                    cell.onclick=new Function(code);
                    cell=row.insertCell(-1);
                    cell.innerHTML=ajax_res[i].status;
                    cell.onclick=new Function(code);
                    cell=row.insertCell(-1);
                    btn=cell.appendChild(document.createElement('input'));
                    btn.type='button';
                    btn.value=_oci.step_09.tentmate.txt.send_request;
                    btn.onclick=cell.onclick=new Function('_oci.main.submit(\'step_09\',this,4,\''+ajax_res[i].tm_id+'\');');
                }
                _oci.step_09.tentmate.ajax_tentmate_search.btn.disabled=false;
            }
        }
    },
    load:function(e){
        _oci.step_09.tentmate.s_r_tbody=document.getElementById('search_results').getElementsByTagName('tbody')[0]
        _oci.step_09.tentmate.loading=_oci.step_09.tentmate.s_r_tbody.getElementsByTagName('td')[0].removeChild(_oci.step_09.tentmate.s_r_tbody.getElementsByTagName('div')[0]);
        _oci.step_09.tentmate.loading.style.display='block';
    }
};

// # Insert load function to be called
_oci.load_calls[_oci.load_calls.length]=_oci.step_09.load;