/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	June 5 2015
 * @link	mailto:kingzmeadow@hotmail.com
 */

_oci.step_06={
    YouTube:{
        txt:null,
        player:null,
        btn:null,
        ready:function(e){
            _oci.step_06.YouTube.containter.removeChild(_oci.step_06.YouTube.c_loading);

            e.target.setPlaybackQuality('default');
            e.target.setVolume(100);

            _oci.step_06.YouTube.btn=document.getElementById('YouTube_Action').getElementsByTagName('input')[0];
            _oci.step_06.YouTube.btn.disabled=false;
        },
        state:function(e){
            switch(e.data){
                case -1:
                case YT.PlayerState.CUED:
                case YT.PlayerState.BUFFERING:{
                    _oci.step_06.YouTube.btn.disabled=true;
                    break;
                }
                case YT.PlayerState.PLAYING:{
                    _oci.step_06.YouTube.btn.disabled=false;
                    _oci.step_06.YouTube.btn.value=_oci.step_06.YouTube.txt.pause;
                    break;
                }
                case YT.PlayerState.PAUSED:{
                    _oci.step_06.YouTube.btn.value=_oci.step_06.YouTube.txt.resume;
                    break;
                }
                case YT.PlayerState.ENDED:{
                    _oci.step_06.YouTube.btn.value=_oci.step_06.YouTube.txt.replay;
                    _oci.show(_oci.NEXT);
                    break;
                }
            }
        },
        action:function(o,ytp){
            switch(ytp.getPlayerState()){
                case -1:
                case YT.PlayerState.CUED:
                case YT.PlayerState.BUFFERING:
                case YT.PlayerState.PAUSED:
                case YT.PlayerState.ENDED:{
                    ytp.playVideo();
                    break;
                }
                case YT.PlayerState.PLAYING:{
                    ytp.pauseVideo();
                    break;
                }
            }
        }
    },
    load:function(e){
        _oci.step_06.YouTube.containter=document.getElementById('YouTube_Container');
        _oci.step_06.YouTube.c_loading=_oci.step_06.YouTube.containter.getElementsByTagName('div')[0];

        var api=document.createElement('script');
        api.id='YouTube_API';
        api.type='text/javascript';
        api.src='https://www.youtube.com/iframe_api';
        document.getElementsByTagName('head')[0].appendChild(api);
    }
};

// # Insert load function to be called
_oci.load_calls[_oci.load_calls.length]=_oci.step_06.load;