<?php
/************************************************************************
 * @author	Kingsley U. Uwode II, contractor via Robert Half Technologies
 * @since	May 26 2015
 * @link	mailto:kingzmeadow@hotmail.com
 *
 * Online Check-In shared funcs (abstract file, not to be used by itself)
 *
 * Shared Online Check-In functionality used through-out
 * all functions reside here
 */

/**
 * ctrlr_online_check_in_pro_page()	page/step retrieval and updating within the end-user's online check-in process
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @param	ARRAY	&$msgs	reference to variable '$main_msgs' where any messages are stored
 * @param	INTEGER	$page_upd_val=NULL	value to be used when updating page/step
 * @return	INTEGER	the current step on error or the retrieved/updated page/step
 */
function ctrlr_online_check_in_pro_page(&$env,&$msgs,$page_upd_val=NULL){
    # Default to returning the current step
    $page_val=$env['step']['current'];

    # Closure used to ensure value used is within page range
    $page_scrubbed_val=function(&$env,$pv){
        return (($pv=(int)$pv)<$env['step']['min']?$env['step']['min']:($pv>$env['step']['max']?$env['step']['max']:$pv));
    };

    # Create the appropriate request for retrieving the current page or updating page value
    if($get_page=(is_int($page_upd_val)===FALSE)){
        $env['request'][]=array(
            'url'=>$env['API']['URL']['TR'],
            'fields'=>array(
                'api_key'=>$env['API']['KEY'],
                'v'=>$env['API']['VER'],
                'method'=>'getFlowStep',
                'fr_id'=>@$env['$_get']['fr_id'],
                $env['API']['AUTH']['FIELD']=>@$env['API']['AUTH']['TOKEN']
            )
        );
    }
    else{
        $env['request'][]=array(
            'url'=>$env['API']['URL']['TR'],
            'fields'=>array(
                'api_key'=>$env['API']['KEY'],
                'v'=>$env['API']['VER'],
                'method'=>'updateRegistration',
                'fr_id'=>@$env['$_get']['fr_id'],
                $env['API']['AUTH']['FIELD']=>@$env['API']['AUTH']['TOKEN'],
                'flow_step'=>($page_upd_val=$page_scrubbed_val($env,$page_upd_val))
            )
        );

        if(is_int(@$env['data']['oci']['check_in_status'])){
            $env['request'][count($env['request'])-1]['fields']['checkin_status']=(string)$env['model']->check_in->status[$env['data']['oci']['check_in_status']];
        }
    }

    # Process the request
    $request_idx=_ctrlr_pro_curl($env,$msgs);

    # Get the page value from a retrieval or update respnose, record any messages
    if($env['request'][$request_idx]['response']['sxml'] instanceof SimpleXMLElement){
        if($get_page){
            if($env['request'][$request_idx]['response']['sxml']->getName()==='getFlowStepResponse'){
                $page_val=$page_scrubbed_val($env,@$env['request'][$request_idx]['response']['sxml']->flowStep);
            }
        }
        else if($env['request'][$request_idx]['response']['sxml']->getName()==='updateRegistrationResponse'){
            if(strtoupper($env['request'][$request_idx]['response']['sxml']->success)==='TRUE'){
                $page_val=$page_upd_val;
            }
            else{
                $msgs['error'][]='whoops, failed to update flowStep!!!';
            }
        }
    }
    else{
        $msgs['error'][]='whoops, unspecified error while handling the page/step!!!';
    }

    # Return the page value
    return $page_val;
}

/**
 * ctrlr_online_check_in_pro_xpath_register_namespace()	registers namespaces within an SimpleXML document for use with XPath queries
 *
 * @param	OBJECT	&$sxml	reference to an SimpleXML document
 * @return	VOID	N/A
 */
function ctrlr_online_check_in_pro_xpath_register_namespace(&$sxml){
    
    $res = true;
    //XML response has elements of the same name but different values for attribute 'id',
    //must register namespaces within the XML response with XPath to access correct element value.
    foreach($sxml->getDocNamespaces() as $p=>$ns) {
        //For XPath usage, all namespaces must have a prefix,
        //and by schemea definition, only one namespace cannot have a prefix, which in this case, is Convio's namespace
        if(!$sxml->registerXPathNamespace(($p?$p:'convio'),$ns)) {
            break;
        }
    }
    return($res);
}

/**
 * ctrlr_online_check_in_pro_get_tentmate_status_text()	retrieves the tentmate status text according to '$ts_id' from the application's model
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @param	INTEGER	&$ts_id	the tentmate status id
 * @return	STRING	the tentmate status text
 */
function ctrlr_online_check_in_pro_get_tentmate_status_text(&$env,$ts_id){
    return (($tms_txt=lng($env['model']->tentmate->status[($ts_id=(int)$ts_id)]))!==''?$tms_txt:($ts_id.': unknown'));
}

/**
 * ctrlr_online_check_in_pro_get_registration_field()	designed for use when updating, retrieves a value from the registration response for the end-user
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @param	ARRAY	&$msgs	reference to variable '$main_msgs' where any messages are stored
 * @param	STRING	$field	field name to index within the registration response
 * @return	STRING	the check-in status
 */
function ctrlr_online_check_in_pro_get_registration_field(&$env,&$msgs,$field){
    if(isset($env['data']['oci']['r_idx_get_registration_field'])){
        $field_value=$env['request'][$env['data']['oci']['r_idx_get_registration_field']]['response']['sxml']->registration->$field;
    }
    else{
        $env['request'][]=array(
            'url'=>$env['API']['URL']['TR'],
            'fields'=>array(
                'api_key'=>$env['API']['KEY'],
                'v'=>$env['API']['VER'],
                'method'=>'getRegistration',
                'fr_id'=>@$env['$_get']['fr_id'],
                $env['API']['AUTH']['FIELD']=>@$env['API']['AUTH']['TOKEN']
            )
        );

        $gr_idx=_ctrlr_pro_curl($env,$msgs);
        if(($env['request'][$gr_idx]['response']['sxml'] instanceof SimpleXMLElement)===FALSE || strstr('|errorResponse|getRegistrationResponse|',('|'.$env['request'][$gr_idx]['response']['sxml']->getName().'|'))===FALSE){
            $msgs['error'][]='whoops, couldn\'t get '.$field.' value!!!';
        }
        else if($env['request'][$gr_idx]['response']['sxml']->getName()==='getRegistrationResponse'){
            $env['data']['oci']['r_idx_get_registration_field']=$gr_idx;
            $field_value=$env['request'][$gr_idx]['response']['sxml']->registration->$field;
        }
    }

    return (string)@$field_value;
}

/**
 * ctrlr_online_check_in_pro_skip_tenting_assignment()	returns whether to skip tenting assignment for the end-user's
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @param	ARRAY	&$msgs	reference to variable '$main_msgs' where any messages are stored
 * @param	STRING	$pt_txt	participation type text to be checked instead of getting said value from a call to the API
 * @return	BOOLEAN
 */
function ctrlr_online_check_in_pro_skip_tenting_assignment(&$env,&$msgs,$pt_txt=NULL){
    # Default value to return
    $skip_tenting_assignment=true;

    # Logic to determine if skipping tenting assignment
    if(isset($pt_txt)===FALSE){
        $env['request'][]=array(
            'url'=>$env['API']['URL']['TR'],
            'fields'=>array(
                'api_key'=>$env['API']['KEY'],
                'v'=>$env['API']['VER'],
                'method'=>'getParticipationType',
                'fr_id'=>@$env['$_get']['fr_id'],
                $env['API']['AUTH']['FIELD']=>@$env['API']['AUTH']['TOKEN']
            )
        );

        $gpt_idx=_ctrlr_pro_curl($env,$msgs);
        if(($env['request'][$gpt_idx]['response']['sxml'] instanceof SimpleXMLElement)===FALSE ||
           ($env['request'][$gpt_idx]['response']['sxml']->getName()==='getParticipationTypeResponse' && (string)$env['request'][$gpt_idx]['response']['sxml']->participationType->name==='')){
            $msgs['error'][]='whoops, couldn\'t determine tenting assisgnment because there was no participation type!!!';
        }
        else if($env['request'][$gpt_idx]['response']['sxml']->getName()==='getParticipationTypeResponse'){
            $pt_txt=(string)$env['request'][$gpt_idx]['response']['sxml']->participationType->name;
        }
    }

    if(is_string($pt_txt)){
        $skip_tenting_assignment=!in_array($pt_txt,
            array(
                '2-Day Walker',
                '1-Day Walker',
                '2-Day Rider',
                '1-Day Rider',
                'K200 Rider',
                'M200 Rider',
                'Rider',
                'Crew',
            )
        );
    }

    return $skip_tenting_assignment;
}

/**
 * ctrlr_online_check_in_pro_funds_check()	Determines if there are enough personal or team funds to complete the Online Check-In process
 *
 * @param	ARRAY	&$env	reference to global variable '$_app_env'
 * @param	ARRAY	&$msgs	reference to variable '$main_msgs' where any messages are stored
 * @param	ARRAY	&$reg	reference to a SimpleXML document representing an end-user's registration record
 * @param	BOOLEAN	&$frr	reference to a SimpleXML document representing an end-user's fundraising results record
 * @return	ARRAY	result of the check and if the user is teamless
 */
function ctrlr_online_check_in_pro_funds_check(&$env,&$msgs,&$reg=NULL,&$frr=NULL){
    # Set local variables
    $funds_check['result']=array();
    # If not passed a registration record, then retrieve it
    if(!isset($reg)){
        $env['request'][]=array(
            'url'=>$env['API']['URL']['TR'],
            'fields'=>array(
                'api_key'=>$env['API']['KEY'],
                'v'=>$env['API']['VER'],
                'method'=>'getRegistration',
                'fr_id'=>@$env['$_get']['fr_id'],
                'sso_auth_token'=>@$env['$_get']['sso_auth_token']
            )
        );
        $gr_idx=_ctrlr_pro_curl($env,$msgs);
        $reg=@$env['request'][$gr_idx]['response']['sxml'];
    }

    if($reg instanceof SimpleXMLElement && $reg->getName()==='getRegistrationResponse'){
        # If not passed a fundraising results record, then retrieve it
        if(!isset($frr)){
            $env['request'][]=array(
                'url'=>$env['API']['URL']['TR'],
                'fields'=>array(
                    'api_key'=>$env['API']['KEY'],
                    'v'=>$env['API']['VER'],
                    'method'=>'getFundraisingResults',
                    'fr_id'=>@$env['$_get']['fr_id'],
                    $env['API']['AUTH']['FIELD']=>@$env['API']['AUTH']['TOKEN'],
                    'cons_id'=>(string)$reg->registration->consId
                )
            );
            

            $gfr_idx=_ctrlr_pro_curl($env,$msgs);
            $frr=@$env['request'][$gfr_idx]['response']['sxml'];
        }
        if($frr instanceof SimpleXMLElement && $frr->getName()==='getFundraisingResponse'){
            //Track that the end user's funds calculations should be returned
            $funds_check['is_user_metrics']=true;

            //The cost, in cents, for the end-user(noting that the cost varies on the end-user's participation type) to go through the Online Check-In process.
            $funds_check['oci_cost']=(float)$frr->fundraisingRecord->minimumGoal;

            //Check end-user's funds:
            //  1) Get the end-user's funds raised
            //  2) Get the cost for an individual Online Check-In
            //  3) Subtract the cost for an individual Online Check-In from he end-user's funds raised to yield funds needed to allow Online Check-In
            //      a) Return 0.0 should funds needed be greater than or equal to 0.0, meaning, the end-user can cover the cost of Online Check-In
            //      b) Otherwise use the absolute value of the negative value as the amount of funds needed to be raised in order to Online Check-In
            //  4) Store logic evaluation above as an easily interpreted BOOLEAN value in variable "$funds_check['user_covering_oci']"
            $funds_check['user_funds_raised']=(float)$frr->fundraisingRecord->amountRaised;
            $funds_check['user_funds_required']=$funds_check['oci_cost'];
            $funds_check['user_funds_needed']=(($ufn=($funds_check['user_funds_raised'] - $funds_check['user_funds_required'])) >= 0.0 ? 0.0 : abs($ufn));
            $funds_check['user_covering_oci']=($funds_check['user_funds_raised'] >= $funds_check['user_funds_required']);

            # Should the end-user not have sufficient funds, check if the usage of team funds are allowed, and if so, check if the team can cover the end-user's Online Check-In
            if($funds_check['user_covering_oci']===FALSE && ((int)$reg->registration->teamId>=0) && @$env['data']['oci']['use_team_funds']===TRUE){
                //Retrieve team data
                $env['request'][]=array(
                    'url'=>$env['API']['URL']['TR'],
                    'fields'=>array(
                        'api_key'=>$env['API']['KEY'],
                        'v'=>$env['API']['VER'],
                        'method'=>'getTeamsByInfo',
                        'fr_id'=>@$env['$_get']['fr_id'],
                        'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
                        'team_id'=>(int)$reg->registration->teamId
                    )
                );
                $gtbi_idx=_ctrlr_pro_curl($env,$msgs);
                $team_name = (string)@$env['request'][$gtbi_idx]['response']['sxml']->team->name;
              
                if(@$env['request'][$gtbi_idx]['response']['sxml'] instanceof SimpleXMLElement && $env['request'][$gtbi_idx]['response']['sxml']->getName()==='getTeamSearchByInfoResponse'){
                    $env['request'][]=array(
                        'url'=>$env['API']['URL']['TR'],
                        'fields'=>array(
                            'api_key'=>$env['API']['KEY'],
                            'v'=>$env['API']['VER'],
                            'method'=>'getParticipants',
                            'fr_id'=>@$env['$_get']['fr_id'],
                            'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
                            'team_name'=>$team_name,
                            'list_filter_column'=>'reg.CHECKIN_STATUS',
                            'list_filter_text'=>'paid',
                            'list_page_size'=>500
                        )
                    );
                    $gp_idx['paid']=_ctrlr_pro_curl($env,$msgs);
                    
                    // Test
                    
                    $participants_status_list = array('paid','committed','complete','started','unknown','initial');
                    echo "Team name: ".$team_name.'<br>';
                    foreach ($participants_status_list as $participants_status) {
                        $gp_idx[$participants_status] = get_participant_list($env,$msgs,$participants_status,$team_name);
                        $xml_data = $env['request'][$gp_idx[$participants_status]]['response']['sxml'];
                        echo ' status: '.$participants_status.' - total: ';
                        echo (int)$xml_data->totalNumberResults;
                        echo ' Total raised amount: '. calculate_total_raised_amount($xml_data,$participants_status).'<br>'; 
                        
                    }
//                    $status = 'started';
//                    $gp_idx[$status] = get_participant_list($env,$msgs,$status,$team_name);
//                   // print_r(@$env['request'][$gp_idx[$status]]['response']['sxml']);
//                    echo (int)$env['request'][$gp_idx[$status]]['response']['sxml']->totalNumberResults .'<br>';
                    
                    // end
                    
                   
                    if(@$env['request'][$gp_idx['paid']]['response']['sxml'] instanceof SimpleXMLElement && $env['request'][$gp_idx['paid']]['response']['sxml']->getName()==='getParticipantsResponse'){
                        $env['request'][]=array(
                            'url'=>$env['API']['URL']['TR'],
                            'fields'=>array(
                                'api_key'=>$env['API']['KEY'],
                                'v'=>$env['API']['VER'],
                                'method'=>'getParticipants',
                                'fr_id'=>@$env['$_get']['fr_id'],
                                'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
                                'team_name'=>$team_name,
                                'list_filter_column'=>'reg.CHECKIN_STATUS',
                                'list_filter_text'=>'committed',
                                'list_page_size'=>500
                            )
                        );
                        $gp_idx['committed']=_ctrlr_pro_curl($env,$msgs);
                        if(@$env['request'][$gp_idx['committed']]['response']['sxml'] instanceof SimpleXMLElement && $env['request'][$gp_idx['committed']]['response']['sxml']->getName()==='getParticipantsResponse'){
                            $env['request'][]=array(
                                'url'=>$env['API']['URL']['TR'],
                                'fields'=>array(
                                    'api_key'=>$env['API']['KEY'],
                                    'v'=>$env['API']['VER'],
                                    'method'=>'getParticipants',
                                    'fr_id'=>@$env['$_get']['fr_id'],
                                    'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
                                    'team_name'=>$team_name,
                                    'list_filter_column'=>'reg.CHECKIN_STATUS',
                                    'list_filter_text'=>'complete',
                                    'list_page_size'=>500
                                )
                            );
                            $gp_idx['complete']=_ctrlr_pro_curl($env,$msgs);
                            if(@$env['request'][$gp_idx['complete']]['response']['sxml'] instanceof SimpleXMLElement && $env['request'][$gp_idx['complete']]['response']['sxml']->getName()==='getParticipantsResponse'){
                                $env['request'][]=array(
                                    'url'=>$env['API']['URL']['TR'],
                                    'fields'=>array(
                                        'api_key'=>$env['API']['KEY'],
                                        'v'=>$env['API']['VER'],
                                        'method'=>'getParticipants',
                                        'fr_id'=>@$env['$_get']['fr_id'],
                                        'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
                                        'team_name'=>$team_name,
                                        'list_filter_column'=>'reg.PARTICIPATION_ID',
                                        'list_filter_text'=>$env['API']['PARTICIPATION']['TYPE']['CREW'],
                                        'list_page_size'=>500
                                    )
                                );
                                $gp_idx['crew']=_ctrlr_pro_curl($env,$msgs);
                                             
                                if(@$env['request'][$gp_idx['crew']]['response']['sxml'] instanceof SimpleXMLElement && $env['request'][$gp_idx['crew']]['response']['sxml']->getName()==='getParticipantsResponse'){
                                    $env['request'][]=array(
                                        'url'=>$env['API']['URL']['TR'],
                                        'fields'=>array(
                                            'api_key'=>$env['API']['KEY'],
                                            'v'=>$env['API']['VER'],
                                            'method'=>'getParticipants',
                                            'fr_id'=>@$env['$_get']['fr_id'],
                                            'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
                                            'team_name'=>$team_name,
                                            'list_page_size'=>500
                                        )
                                    );
                                    $gp_idx['all']=_ctrlr_pro_curl($env,$msgs);
0
                                   
                                  //  if(@$env['request'][$gp_idx['all']]['response']['sxml'] instanceof SimpleXMLElement && $env['request'][$gp_idx['all']]['response']['sxml']->getName()==='getParticipantsResponse'){
                                    if(@$env['request'][$gp_idx['all']]['response']['sxml'] instanceof SimpleXMLElement){
                                        //Track that the team's funds calculations should be returned
                                        $funds_check['is_user_metrics']=false;

                                        //For XPath usage, all namespaces must have a prefix. Encase in a logic check so when debugging no error is thrown.
                                        ctrlr_online_check_in_pro_xpath_register_namespace($env['request'][$gp_idx['paid']]['response']['sxml']);
                                        ctrlr_online_check_in_pro_xpath_register_namespace($env['request'][$gp_idx['committed']]['response']['sxml']);
                                        ctrlr_online_check_in_pro_xpath_register_namespace($env['request'][$gp_idx['complete']]['response']['sxml']);
                                        ctrlr_online_check_in_pro_xpath_register_namespace($env['request'][$gp_idx['crew']]['response']['sxml']);
                                        ctrlr_online_check_in_pro_xpath_register_namespace($env['request'][$gp_idx['all']]['response']['sxml']);
                                        
                                        
//                                        print_r(@$env['request'][$gtbi_idx]['response']);echo ' - 1 -<hr>';
//                                        print_r($env['request'][$gp_idx['paid']]['response']);echo '- 2 -<hr>';
//                                        print_r($env['request'][$gp_idx['committed']]['response']);echo '- 3 - <hr>';
//                                        print_r($env['request'][$gp_idx['complete']]['response']);echo '- 4 -<hr>';
//                                        print_r($env['request'][$gp_idx['crew']]['response']);echo '- 5 - <hr>';
//                                        print_r($env['request'][$gp_idx['all']]['response']);echo '- 6 - <hr>';

                                        //Check team funds:
                                        //  1) Get the team funds raised (which include non-individual/entity based contributions), while also,
                                        //     for each participant who made a DSP with an amount raised less than or equal to the cost of Online Check-In
                                        //     increase the team funds raised by the positive difference from the particpant's amount raised and the cost of Online Check-In.

                                        // Added by Kevin
                                        // Get total numbers of teammembers based on memeber status
                                        $status_list = array('all','paid','committed','complete','crew');
                                        foreach ($status_list as $memeber_status) {
                                            $team_numbers[$memeber_status]=(int)$env['request'][$gp_idx[$memeber_status]]['response']['sxml']->totalNumberResults;
                                        }
                                        
                                        $status ='all';
                                        $funds_check['team_funds_raised']=(float)@$env['request'][$gtbi_idx]['response']['sxml']->team->amountRaised;
                                        $total_team_raised = $funds_check['team_funds_raised'];
                                        
                                        $tmp = 0;
                                        $total_participants_raised = 0;
                                        for($i=0;(string)@$env['request'][$gp_idx[$status]]['response']['sxml']->participant[$i]->consId!=='';$i++){
                                            $fname=@$env['request'][$gp_idx[$status]]['response']['sxml']->participant[$i]->name->first;
                                            $lname=@$env['request'][$gp_idx[$status]]['response']['sxml']->participant[$i]->name->last;
                                            $amt=(float)$env['request'][$gp_idx[$status]]['response']['sxml']->participant[$i]->amountRaised;
                                            $total_participants_raised+=$amt;

                                            if($amt < $funds_check['oci_cost'])
                                                {
                                                $tmp+= $amt;
                                                $funds_check['team_funds_raised']+=abs($amt - $funds_check['oci_cost']);
                                            }
                                        }
                                         
                                        //  2) Calculate team funds required for the end-user attempting to Online Check-In by starting with the cost of an Online Check-In
                                        //     then adding the cost of Online Check-In for each participant (excluding those of type 'CREW') with a check-in status
                                        //     of PAID/COMMITTED/COMPLETE or participants who have not checked-in but have funds to Online Check-In.
                                        $funds_check['team_funds_required']=$funds_check['oci_cost'];
                                        for($i=0;(($cid=(string)@$env['request'][$gp_idx['all']]['response']['sxml']->participant[$i]->consId)!=='');$i++){
                                            //XPATH result sets using constituent id to prevent processing the same constituent multiple times
                                            $xpr['paid']=(is_callable(array($env['request'][$gp_idx['paid']]['response']['sxml'],'xpath'))?$env['request'][$gp_idx['paid']]['response']['sxml']->xpath('/convio:getParticipantsResponse/convio:participant[convio:consId=\''.$cid.'\']'):NULL);
                                            $xpr['committed']=(is_callable(array($env['request'][$gp_idx['committed']]['response']['sxml'],'xpath'))?$env['request'][$gp_idx['committed']]['response']['sxml']->xpath('/convio:getParticipantsResponse/convio:participant[convio:consId=\''.$cid.'\']'):NULL);
                                            $xpr['complete']=(is_callable(array($env['request'][$gp_idx['complete']]['response']['sxml'],'xpath'))?$env['request'][$gp_idx['complete']]['response']['sxml']->xpath('/convio:getParticipantsResponse/convio:participant[convio:consId=\''.$cid.'\']'):NULL);
                                            $xpr['crew']=(is_callable(array($env['request'][$gp_idx['crew']]['response']['sxml'],'xpath'))?$env['request'][$gp_idx['crew']]['response']['sxml']->xpath('/convio:getParticipantsResponse/convio:participant[convio:consId=\''.$cid.'\']'):NULL);
                                            $xpr['raised'][0]=@$env['request'][$gp_idx['all']]['response']['sxml']->participant[$i];

                                            //Check XPATH queries to see if any the constituent indexed was matched in a result set
                                            if(($participant=@$xpr['paid'][0]) instanceof SimpleXMLElement ||
                                               ($participant=@$xpr['committed'][0]) instanceof SimpleXMLElement ||
                                               ($participant=@$xpr['complete'][0]) instanceof SimpleXMLElement ||
                                               (($participant=@$xpr['raised'][0]) instanceof SimpleXMLElement && (float)$participant->amountRaised >= $funds_check['oci_cost'])){
                                                if((@$xpr['crew'][0] instanceof SimpleXMLElement)===FALSE){
                                                    $funds_check['team_funds_required']+=$funds_check['oci_cost'];
                                                }
                                            }
                                        }

                                        //  3) Subtract the team funds required from team funds raised to yield funds needed to allow Online Check-In
                                        //      a) Return 0.0 should funds needed be greater than or equal to 0.0, meaning, the team can cover the cost of Online Check-In
                                        //      b) Otherwise use the absolute value of the negative value as the amount of funds needed to be raised in order to Online Check-In
                                        $funds_check['team_funds_needed']=(($tfn=($funds_check['team_funds_raised'] - $funds_check['team_funds_required'])) >= 0.0 ? 0.0 : abs($tfn));

                                        //  4) Store logic evaluation above as an easily interpreted BOOLEAN value in variable "$funds_check['team_covering_oci']"
                                        $funds_check['team_covering_oci']=($funds_check['team_funds_raised'] >= $funds_check['team_funds_required']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if(is_bool(@$funds_check['is_user_metrics'])){
        //Luminate Online stores money values in cents, meaning, there is no decimal used, so, have to divide the amount raised by 100 to get its value in dollars.
        $funds_check['result']['oci']=($funds_check['user_covering_oci'] || @$funds_check['team_covering_oci'] ? 'allow' : 'deny');
        $funds_check['result']['check']=($funds_check['is_user_metrics']===TRUE ? 'individual' : 'team');
        $funds_check['result']['raised']=number_format((($funds_check['is_user_metrics']===TRUE ? $funds_check['user_funds_raised'] : $funds_check['team_funds_raised']) / 100.0),2);
        $funds_check['result']['required']=number_format((($funds_check['is_user_metrics']===TRUE ? $funds_check['user_funds_required'] : $funds_check['team_funds_required']) / 100.0),2);
        $funds_check['result']['needed']=number_format((($funds_check['is_user_metrics']===TRUE ? $funds_check['user_funds_needed'] : $funds_check['team_funds_needed']) / 100.0),2);
// added by Kevin        
        $funds_check['result']['individual_raised']=number_format($funds_check['user_funds_raised'] / 100.0,2);
        $funds_check['result']['individual_required']=number_format($funds_check['user_funds_required'] / 100.0,2);
        $funds_check['result']['individual_needed']=number_format($funds_check['user_funds_needed'] / 100.0,2);
        $funds_check['result']['team_raised']=number_format($total_team_raised / 100.0,2);
        $funds_check['result']['participants_raised']=number_format($total_participants_raised / 100.0,2);
        $funds_check['result']['team_gifts']=number_format(($total_team_raised - $total_participants_raised) / 100.0,2);
        $tmp=$funds_check['user_funds_needed'] - ($total_team_raised - $total_participants_raised);
        $updated_individual_needed=($tmp>0?$tmp:0);
        $funds_check['result']['updated_individual_needed']=number_format($updated_individual_needed / 100.0,2);
        $funds_check['result']['total_team_members']=$team_numbers;
// end 
    }
    echo '<div style="display:none">';
    print_r($funds_check['result']);
    echo '</div>';        

    return @$funds_check['result'];
}


function get_participant_list(&$env,&$msgs,$status,$team_name)
{
    $env['request'][]=array(
        'url'=>$env['API']['URL']['TR'],
        'fields'=>array(
            'api_key'=>$env['API']['KEY'],
            'v'=>$env['API']['VER'],
            'method'=>'getParticipants',
            'fr_id'=>@$env['$_get']['fr_id'],
            'sso_auth_token'=>@$env['$_get']['sso_auth_token'],
            'team_name'=>$team_name,
            'list_filter_column'=>'reg.CHECKIN_STATUS',
            'list_filter_text'=>$status,
            'list_page_size'=>500
        )
    );
    return(_ctrlr_pro_curl($env,$msgs));
}


function calculate_total_raised_amount($xml_data,$participants_status)
{
    $total_participants_raised = 0;
     ctrlr_online_check_in_pro_xpath_register_namespace($xml_data);
     for($i=0;(string)@$xml_data->participant[$i]->consId!=='';$i++){
         $tmp = (float)$xml_data->participant[$i]->amountRaised;
         if($participants_status=='complete' || $tmp>0)
        $total_participants_raised+=$tmp;
    }
    return(number_format($total_participants_raised / 100.0,2));
}
function get_total_team_raised_amount($xml_data,$participants_status)
{
    $total_participants_raised = 0;
     ctrlr_online_check_in_pro_xpath_register_namespace($xml_data);
     for($i=0;(string)@$xml_data->participant[$i]->consId!=='';$i++){
         $tmp = (float)$xml_data->participant[$i]->amountRaised;
         if($participants_status=='complete' || $tmp>0)
        $total_participants_raised+=$tmp;
    }
    return(number_format($total_participants_raised / 100.0,2));
}
