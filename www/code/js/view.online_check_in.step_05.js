/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	June 16 2015
 * @link	mailto:kingzmeadow@hotmail.com
 */

_oci.step_05={
    waiver:{
        over_18:function(o){
            if(o.checked){
                _oci.show(_oci.NEXT);
            }
            else{
                _oci.hide(_oci.NEXT);
            }
        }
    }
};