<?php
/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	May 26 2015
 * @link	mailto:kingzmeadow@hotmail.com
 *
 * Online Check-In main file
 *
 * Functionality to start the Online Check-In process
 */

## REQUIRE OCI UTILTY CODE FILE  ########################################
require_once(preg_replace('/\.main\.php$/','.utility',__FILE__));

/**
 * ctrlr_online_check_in_pub_main()	Initiates/declares data/variables shared throught the Online Check-In process
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @return	VOID	N/A
 */
function ctrlr_online_check_in_pub_main(&$env){
    # Start/resume the PHP session for the end-user
    session_start();

    # Prepare local variables
    $main_msgs=null;

    # Prepare environment variables
    $env['flags']['is_step_view_on_error']=false;
    $env['flags']['is_showing_err_messages']=false;
    $env['flags']['is_allowing_api']=null;
    $env['flags']['is_frid_pop']=null;
    $env['flags']['is_auth_pop']=null;
    $env['flags']['is_hiding_nav']['all']=false;
    $env['flags']['is_hiding_nav']['previous']=false;
    $env['flags']['is_hiding_nav']['next']=false;
    $env['flags']['is_valid_step']=false;
    $env['flags']['is_CRUD']=false;
    $env['flags']['is_view_data_retrieved']=false;

    $env['step']['min']=0;
    $env['step']['max']=10;
    $env['step']['current']=$env['step']['min'];
    $env['step']['rendered']=$env['step']['current'];

    $env['API']=null;

    $env['data']['oci']['action']='step';
    $env['data']['oci']['country']='ca';

    $env['request']=null;

    $env['model']=_ctrlr_pro_model(__FUNCTION__);

    # Scrub necessary GET parameters only assigning what was passed otherwise leave '$_get' unmodified
    $env['flags']['is_frid_pop']=((trim(@$env['$_get']['fr_id'])!==''?($env['$_get']['fr_id']=trim(@$env['$_get']['fr_id'])):'')!=='');
    $env['flags']['is_auth_pop']=((trim(@$env['$_get']['auth'])!==''?($env['$_get']['auth']=trim(@$env['$_get']['auth'])):'')!=='');

    # Check that we have a fundraising event id, otherwise, abort processing unless option to proceed on error is set
    if($env['flags']['is_frid_pop']===TRUE || $env['flags']['is_step_view_on_error']===TRUE){
        $env['API']['KEY']='cfowca';
        $env['API']['VER']='1.0';
        $env['API']['URL']['SRCONS']=('https://secure2.convio.net/'.$env['API']['KEY'].'/site/SRConsAPI');
        $env['API']['URL']['CONS']=('https://secure2.convio.net/'.$env['API']['KEY'].'/site/CRConsAPI');
        $env['API']['URL']['TR']=('https://secure2.convio.net/'.$env['API']['KEY'].'/site/CRTeamraiserAPI');

        $env['API']['AUTH']['FIELD']='sso_auth_token';
        $env['API']['AUTH']['TOKEN']=@$env['$_get']['auth'];

        $env['API']['SURVEY']['ID']=1801;
        $env['API']['SURVEY']['QUESTION'][0]=1788; # Cancer Survivor
        $env['API']['SURVEY']['QUESTION'][1]=1800; # Vegetarian
        $env['API']['SURVEY']['QUESTION'][2]=1783; # Health Insurance Policy Name
        $env['API']['SURVEY']['QUESTION'][3]=1784; # Health Insurance Policy Number
        $env['API']['SURVEY']['QUESTION'][4]=1781; # Waiver Full Name
        $env['API']['SURVEY']['QUESTION'][5]=1782; # Is 18 years old or above
        $env['API']['SURVEY']['QUESTION'][6]=1785; # Upsell Accepted
        $env['API']['SURVEY']['QUESTION'][7]=1801; # Hidden Upsell Value
        $env['API']['SURVEY']['QUESTION'][8]=2043; # Hidden Safety Video Watched
        $env['API']['SURVEY']['UPSELL'][0]=1001; # Upsell Item: Pre-Registration for $25
        $env['API']['SURVEY']['UPSELL'][1]=1002; # Upsell Item: Pre-Registration for $125
        $env['API']['SURVEY']['UPSELL'][2]=1003; # Upsell Item: Pre-Registration for $250

        $env['API']['PARTICIPATION']['TYPE']['CREW']=1071;

        $env['data']['oci']['use_team_funds']=FALSE;

        //Determine if submitted information constitutes a valid step and proceed accordingly
        if($env['flags']['is_valid_step']=(is_numeric(@$env['$_post']['online_check_in_step']) && (int)$env['$_post']['online_check_in_step']>=$env['step']['min'] && (int)$env['$_post']['online_check_in_step']<=$env['step']['max'])){
            $env['step']['current']=(int)$env['$_post']['online_check_in_step'];
            $env['step']['rendered']=$env['step']['current'];
            $env['flags']['is_CRUD']=isset($env['$_post']['page']['next']);
        }
        else{
            $env['step']['current']=ctrlr_online_check_in_pro_page($env,$main_msgs);
            $env['step']['rendered']=$env['step']['current'];
        }
    }

    # Generate any error/notice messages from submitted information
    if($env['flags']['is_frid_pop']===FALSE || ($env['flags']['is_auth_pop']===FALSE && ($env['flags']['is_valid_step']===FALSE || $env['step']['current']!==$env['step']['min']))){
        $main_msgs['error']=array_merge(array('(Online Check-In) missing '.($env['flags']['is_frid_pop']===FALSE?'fundraiser id':null).($env['flags']['is_auth_pop']===FALSE?(($env['flags']['is_frid_pop']===FALSE?' and ':null).'user authorization'):null)),(array)@$main_msgs['error']);
    }

    # Prepare output/view local variables and process submitted information as well as obtain views
    $output=array(
        'FND'=>array(
            '_PAGE_TITLE_',
            (nl().'_CASCADING_STYLE_SHEET_'),
            (nl().'_JAVASCRIPT_'),
            '_EYE_CANDY_CLASS_',
            (nl().'_VIEW_')
        ),
        'RPL'=>array(
            'page_title'=>null,
            'cascading_style_sheet'=>array('template.default.css'),
            'javascript'=>array('template.default.js'),
            'eye_candy_class'=>($env['is_DEV']?'not_displayed':''),
            'view'=>null
        ),
        'TEMPLATE'=>(__DIR__.'/../view/template.default.html')
    );

    $main_view=_ctrlr_pro_view(__FUNCTION__);
    $main_nav_class='not_displayed';
    $main_nav_previous_class='';
    $main_nav_next_class='';
    $main_msgs_class=' not_displayed';

    $output['RPL']['page_title']=lng($main_view['sxml']->page_title);
    $output['RPL']['cascading_style_sheet'][]=$main_view['files']['css'];
    $output['RPL']['javascript'][]=$main_view['files']['js'];
    $output['RPL']['view']=$main_view['mark_up'];

    # Call the general 'step' function to manage behavior for the page/step action/view
    eval(_ctrlr_pro_action(__FILE__,$env['data']['oci']['action'],'$env,$main_msgs,$output'));

    # See if we're showing any messages, and, on certain error messages prevent AJAX responses along with determining to hide the navigation bar
    if(is_null($main_msgs)===FALSE){
        if(count($main_msgs)>1 || array_key_exists('error',$main_msgs)===FALSE || $env['flags']['is_showing_err_messages']===TRUE){
            $main_msgs_class='';
        }
        if($env['flags']['is_allowing_api']===FALSE){
            $env['flags']['is_AJAX']=false;
            if($env['flags']['is_step_view_on_error']===FALSE){
                $env['flags']['is_hiding_nav']['all']=true;
            }
        }
    }

    # See if we're hidding the navigation bar
    if($env['flags']['is_hiding_nav']['all']===FALSE){
        $main_nav_class='';
        if($env['flags']['is_hiding_nav']['previous'] || $env['step']['rendered']==$env['step']['min']){
            $main_nav_previous_class='not_visible';
        }
        if($env['flags']['is_hiding_nav']['next'] || $env['step']['rendered']==$env['step']['max']){
            $main_nav_next_class='not_visible';
        }
    }

    # Replace text placeholders with entries from the main view
    $output['RPL']['view']=str_replace(
        array(
            '-TXT-IMG-BANNER-TOP-ALT-',
            '-TXT-IMG-BANNER-BOTTOM-ALT-',
            '-TXT-SUBMIT-PREVIOUS-',
            '-TXT-SUBMIT-NEXT-',
            '-TXT-VALIDATE-MSG-PLEASE-CORRECT-',
            '-TXT-VALIDATE-MSG-EMPTY-',
            '-TXT-VALIDATE-MSG-EMAIL-',
            '-TXT-VALIDATE-MSG-TELEPHONE-',
            '-TXT-VALIDATE-MSG-NUMBER-',
            '-TXT-VALIDATE-MSG-ZIP-',
            '-TXT-VALIDATE-MSG-USERNAME-',
            '-TXT-VALIDATE-MSG-TEXT-'
        ),
        array(
            lng($main_view['sxml']->text[$t=0]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[++$t]),
            lng($main_view['sxml']->text[(strtoupper($env['data']['oci']['country'])==='US'?$t+1:$t+2)]),
            lng($main_view['sxml']->text[($t+=3)]),
            lng($main_view['sxml']->text[++$t])
        ),
        $output['RPL']['view']
    );

    # Replace data placeholders with values and put any step view data into the main view
    $output['RPL']['view']=str_replace(
        array(
            '_ONLINE_CHECK_IN_STEP_',
            '_VALIDATION_COUNTRY_',
            (nl().'_ANIMATION_WAITING_'),
            '_MAIN_NAV_CLASS_',
            '_MAIN_NAV_PREVIOUS_CLASS_',
            '_MAIN_NAV_NEXT_CLASS_',
            '_MAIN_MSGS_CLASS_',
            (nl().'_MAIN_MSGS_HTML_')
        ),
        array(
            $env['step']['rendered'],
            $env['data']['oci']['country'],
            str_replace("\n",("\n".t(5)),rtrim($env['model']->animation->waiting->style[0])),
            $main_nav_class,
            $main_nav_previous_class,
            $main_nav_next_class,
            $main_msgs_class,
            _ctrlr_pro_messages_to_html($main_msgs)
        ),
        $output['RPL']['view']
    );

    # For surveys, the default value may be set to "User Provided No Response", so, replace any such occurrences as a FORM INPUT 'value' or within a FORM TEXTAREA
    $pattern='\s*User\s*Provided\s*No\s*Response\s*';
    $output['RPL']['view']=preg_replace(('/(")'.$pattern.'(")/'),'$1$2',$output['RPL']['view']);
    $output['RPL']['view']=preg_replace(('/(>)'.$pattern.'(<)/'),'$1$2',$output['RPL']['view']);

    # Pass all view output for rendering
    _ctrlr_pro_render($env,$output);
}

/**
 * ctrlr_online_check_in_pri_step()	page/step generalized processing
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @param	ARRAY	&$msgs	reference to variable '$main_msgs' where any messages are stored
 * @param	ARRAY	&$out	reference to variable '$output' which contains content to be rendered
 * @return	VOID	N/A
 */
function ctrlr_online_check_in_pri_step(&$env,&$msgs,&$out){
    //Closure to check/record if access to the Luminate Online API was allowed
    $allowing_api_access=function(&$env){
        return ($env['flags']['is_allowing_api']=($env['flags']['is_frid_pop']===TRUE && $env['flags']['is_auth_pop']===TRUE && (($last=count($env['request'])-1)<0 || (($env['request'][$last]['response']['sxml'] instanceof SimpleXMLElement)===TRUE && ($env['request'][$last]['response']['sxml']->getName()!=='errorResponse' || (string)$env['request'][$last]['response']['sxml']->code!=='5')))));
    };

    # For the current step, require include abstract step file and call current action to handle processing
    require_once(_ctrlr_pro_action(__FILE__,($action=$env['data']['oci']['action'].'_'.pz($env['step']['current'])),true));
    eval(_ctrlr_pro_action(__FILE__,$action,'$env,$msgs,$out,true'));

    # Skip a page/step if specified
    switch($env['step']['current']){
        case 0:{
            if($allowing_api_access($env)===TRUE){
                $page_step=1;
                $env['flags']['is_valid_step']=true;
            }
            break;
        }
        default:{
            break;
        }
    }

    # Determine the page/step to render
    $page_step=(isset($page_step)?$page_step:(isset($env['$_post']['page']['next'])?($env['step']['current']+1):(isset($env['$_post']['page']['previous'])?($env['step']['current']-1):$env['step']['current'])));

    # If there are no errors thus far and a valid step was submitted then attempt to update the end-user's registration page/step
    if(isset($msgs['error'])===FALSE && $env['flags']['is_valid_step']===TRUE){
        $env['step']['rendered']=ctrlr_online_check_in_pro_page($env,$msgs,$page_step);
    }

    # DEV ENVIRONMENT ONLY STATEMENTS, when there are errors and still rendering the view then use the calculated page/step
    if($env['is_DEV'] && isset($msgs['error'])===TRUE && $env['flags']['is_step_view_on_error']===TRUE){
        $env['step']['rendered']=$page_step;
    }
    # DEV end

    # Attempt to generate the view for the page/step specified to be rendered...
    do{
        //For the rendered step, require include abstract step file and call rendering action for view content, optionally, can be skipped on error(s) occurred
        if(isset($msgs['error'])===FALSE || ($env['flags']['is_frid_pop']===TRUE&&$env['step']['rendered']===0) || $env['flags']['is_step_view_on_error']===TRUE){
            require_once(_ctrlr_pro_action(__FILE__,($action=$env['data']['oci']['action'].'_'.pz($env['step']['rendered'])),true));
            eval(_ctrlr_pro_action(__FILE__,$action,'$env,$msgs,$out'));
        }

        //If we could not retrieve the data to generate the page's/step's view then report a view generation error
        if($env['flags']['is_view_data_retrieved']===FALSE){
            $msgs['error'][]='whoops, couldn\'t get the data needed to generate the view for this page/step!!!';
        }

        //If data necessary to generate the page's/step's view was retrieved or there was access to the API or we don't have a fundraising id then only make the initial attempt, otherwise, have the end-user log in
        if($env['flags']['is_view_data_retrieved']===TRUE || $allowing_api_access($env)===TRUE || $env['flags']['is_frid_pop']===FALSE || $env['flags']['is_step_view_on_error']===TRUE){
            break;
        }
        else if(isset($attempts)===FALSE){
            $env['step']['rendered']=0;
        }
    }
    while(@++$attempts<=1);

    # On no view content, require include abstract fault file and call 'step_fault' action for fault view content
    if(strstr($out['RPL']['view'],'_STEP_VIEW_')){
        require_once(_ctrlr_pro_action(__FILE__,($action=$env['data']['oci']['action'].'_fault'),true));
        eval(_ctrlr_pro_action(__FILE__,$action,'$env,$msgs,$out'));
    }
}
?>