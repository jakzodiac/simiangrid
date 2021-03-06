<?php

class User extends Controller {

    function User()
    {
        parent::Controller();
        $this->load->library('Openid');
        $this->load->library('Form_validation');
        $this->load->library('table');

        $this->load->helper('form');
        $this->load->helper('simian_view_helper');
        $this->load->helper('simian_openid_helper');
        $this->load->helper('simian_facebook_helper');    
        
        $this->lang->load('simian_grid', get_language() );    
    }

    function _me_or_admin($uuid)
    {
        $my_uuid = $this->sg_auth->get_uuid();
        $is_admin = $this->sg_auth->is_admin();
        if ( $my_uuid == $uuid || $is_admin ) {
            return true;
        } else {
            return false;
        }
    }

    function identities($uuid, $action=null)
    {
        if ( ! $this->_me_or_admin($uuid) ) {
            return redirect('user/index');
        }
        if ( $action == 'remove' ) {
            return $this->_remove_identity($uuid);
        } elseif ( $action == 'add_openid' ) {
            return $this->_add_openid($uuid);
        } elseif ( $action == 'add_facebook' ) {
            return $this->_add_facebook($uuid);
        }
        return $this->_list_identities($uuid);
    }
    
    function _add_facebook($uuid)
    {
        if ( ! empty($_SERVER['QUERY_STRING']) ) {
            parse_str($_SERVER['QUERY_STRING'],$_GET); 
            if ( ! empty($_GET['code']) ) {
                $token = process_facebook_verification($_GET['code'], site_url("user/identities/$uuid/add_facebook"));
                $fb_id = facebook_get_id($this, $token);
                if ( ! $this->sg_auth->facebook_exists($fb_id) ) {
                    if ( ! $this->simiangrid->identity_set($uuid, 'facebook', $fb_id) ) {
                        push_message(lang('sg_auth_fb_error_assoc'), 'error');
                    }
                }
            }
        }
        return redirect("user/view/$uuid");
    }
    
    function _add_openid($uuid)
    {
        $callback_url = site_url("user/identities/$uuid/add_openid");
        if ($this->input->post('action') == 'verify') {
            return openid_process_verify($this, $callback_url);
        } else if ( $this->session->flashdata('openid_identifier') OR openid_check($this, $callback_url, $data) ) {
            $openid = null;
            if ($this->session->flashdata('openid_identifier')) {
                $openid = $this->session->flashdata('openid_identifier');
                $data['openid_identifier'] = $openid;
                $this->session->keep_flashdata('openid_identifier');
            } else {
                $openid = $data['openid_identifier'];
                $this->session->set_flashdata('openid_identifier', $openid);
            }

            if ( ! $this->sg_auth->openid_exists($openid) ) {
                if ( ! $this->simiangrid->identity_set($uuid, 'openid', $openid) ) {
                    push_message(lang('sg_auth_open_error_assoc'), 'error');
                }
            }
        }
        return redirect("user/view/$uuid/identities");
    }
    
    function _remove_identity($uuid)
    {
        $val = $this->form_validation;
        $val->set_rules('type', 'Type', 'trim|required|xss_clean');
        $val->set_rules('identifier', 'Identifier', 'trim|required|xss_clean');
        
        if ( $val->run() ) {
            $type = $val->set_value('type');
            $identifier = $val->set_value('identifier');
            if ( ! $this->simiangrid->identity_remove($uuid, $type, $identifier) ) {
                push_message(set_message('sg_auth_ident_remove_error', $type), 'error');
            }
        }
        return redirect("user/view/$uuid");
    }
    
    function _render_remove_identity($user_id, $type, $identifier)
    {
        $form = form_open(site_url("user/identities/$user_id/remove"));
        $form = $form . form_hidden('type', $type);
        $form = $form . form_hidden('identifier', $identifier);
        $form = $form . form_submit('remove','Remove', 'class="button"');
        $form = $form . form_close();
        return $form;
    }
    
    function _list_identities($uuid) {
        $data = array();
        $data['uuid'] = $uuid;
        $data['identities'] = $this->simiangrid->get_user_identities($uuid);
    
        $this->table->set_heading(lang('sg_type'), lang('sg_user_identifier'), lang('sg_actions') );
        
        $data['has_openid'] = false;
        $data['has_facebook'] = false;
        
        foreach ( $data['identities'] as $identity ) {
            $type = $identity['Type'];
            $enabled = (bool) $identity['Enabled'];
            $real_identifier = $identity['Identifier'];
            $ident = $real_identifier;
            if ( $type == "openid" ) {
                $this->has_openid = true;
                $url = parse_url($real_identifier);
                $ident = $url['host'];
            } elseif ( $type == "facebook" ) {
                $data['has_facebook'] = true;
            }
            if ( $type != "md5hash" && $type != "a1hash" ) {
                $actions = $this->_render_remove_identity($uuid, $type, $real_identifier);
            } else {
                $actions = '';
            }
            $this->table->add_row($type, $ident, $actions);
        }
        return parse_template('user/identities', $data, true);
    }

    function index()
    {
        $data = array();
        $data['page'] = 'users';
        parse_template('user/index', $data);
    }

    function profile_pic($uuid)
    {
        if ( $this->config->item('use_imagick') && extension_loaded('imagick') ) {
            $grid_user = $this->simiangrid->get_user($uuid);
            if ( isset($grid_user['LLAbout']) && isset($grid_user['LLAbout']['Image']) ) {
                $image = $this->simiangrid->get_texture($grid_user['LLAbout']['Image'], 200, 200);
                if ( $image == null ){
                    return show_404($uuid);
                } else {
                    header('Content-type: image/jpeg');
                    echo $image;
                }
            }
        } else {
			return redirect(base_url() . "static/images/user.png", 'location');
        }
    }

    function profile($uuid)
    {
        $data = array();
        $data['user_id'] = $uuid;
        $data['my_uuid'] = $this->sg_auth->get_uuid();
        $grid_user = $this->simiangrid->get_user($uuid);
        if ( $grid_user == null ) {
            return show_404($uuid);
        }
        if ( isset($grid_user['LastLocation'] ) ) {
            $last_scene_id = $grid_user['LastLocation']['SceneID'];
            if ( $last_scene_id != null ) {
                $data['last_scene'] = $this->simiangrid->get_scene($last_scene_id);
            }
        }
        $data['user_info'] = array(
            'name' => $grid_user['Name'],
            'email' => $grid_user['Email']
        );
        if ( isset($grid_user['LLAbout']) ) {
            if ( isset($grid_user['LLAbout']['About']) ) {
                $data['user_info']['about'] = $grid_user['LLAbout']['About'];
            }
            if ( $this->config->item('use_imagick') && extension_loaded('imagick') && isset($grid_user['LLAbout']['Image']) ) {
                $data['avatar_image'] = $uuid;
            }
        }
        parse_template('user/profile', $data, true);
    }

    function _render_user_callback($user_id, $callback)
    {
		$user = $this->simiangrid->get_user($user_id);
        return anchor('user/view/' . $user_id, $user['Name'], array('class'=>'search_result','onclick' => $callback . '(\'' . $user_id . '\'); return false;'));
    }

    function _truncate_search($search_results, $offset, $page_count, $callback=null)
    {
        $results = array();
        $offset_count = 0;
        $result_count = 0;
        foreach ( $search_results as $search_result ) {
            if ( $offset_count >= $offset && $result_count < $page_count ) {
                $user_id = $search_result['id'];
                if ( $this->sg_auth->is_user_searchable($user_id) ) {
					$search_item = null;
					if ( $callback == null ) {
                    	$search_item = array(
	                        render_user_link($user_id)
	                    );
					} else {
						$search_item = array(
							$this->_render_user_callback($user_id, $callback)
						);
					}
                    array_push($results, $search_item);
                    $result_count = $result_count + 1;
                } else if ( $offset_count < $offset ) {
                	$offset_count = $offset_count + 1;
				}
            }
        }
        return $results;
    }

    function search($callback=null)
    {
        parse_str($_SERVER['QUERY_STRING'],$_GET); 
        $offset = $_GET['iDisplayStart'];
        $limit = $_GET['iDisplayLength'];
        $search = $_GET['sSearch'];
		if ( $callback != 'change_region_owner' ) {
			$callback = null;
		}
        if ( $search == '' || $search == ' ' || strlen($search) <= 3 ) {
            $trunc_count = 0;
            $trunc_results = array();
        } else {
            $search_results = $this->simiangrid->search_user($search);
            $trunc_results = $this->_truncate_search($search_results, $offset, $limit, $callback);
            $trunc_count = count($search_results);
        }
        $result = array(
            "sEcho" => $_GET['sEcho'],
            "iTotalRecords" => $this->simiangrid->total_user_count(),
            "iTotalDisplayRecords" => $trunc_count,
            "aaData" => $trunc_results
        );
        echo json_encode($result);
        return;
    }
    
    function self()
    {
        $uuid = $this->sg_auth->get_uuid();
        if ( $uuid == null ) {
            return redirect('user/', 'location');
        } else {
            return redirect('user/view/' . $uuid, 'location');
        }
    }

    function view($uuid, $extra=null)
    {    
        $data = array();
        $user = $this->simiangrid->get_user($uuid);
        if ( $user == null ) {
            $user = $this->simiangrid->get_user_by_name($uuid);
            if ( $user != null ) {
                $data['uuid'] = $user['UserID'];
				$data['username'] = $user['Name'];
            } else {
                push_message(set_message('sg_user_not_found', $uuid), 'error');
                return redirect('user/');
            }
        } else {
            $data['uuid'] = $uuid;
			$data['username'] = $user['Name'];
        }
        $my_uuid = $this->sg_auth->get_uuid();
        $data['page'] = 'users';
        if ( $my_uuid != null ) {
            $data['my_uuid'] = $my_uuid;
            if ( $my_uuid == $uuid ) {
                $data['page'] = 'account';
            }
        }
        $data['tab'] = '';
        if ( $extra == "actions" ) {
            $data['tab'] = 'actions';
        } else if ( $extra == 'identities' ) {
            $data['tab'] = 'identities';
        } else if ( $extra == 'admin_actions' ) {
            $data['tab'] = 'admin_actions';
        }
        $data['title'] = $user['Name'];
		$data['meta'] = generate_open_graph(site_url("user/view/$uuid"), $user['Name'], site_url("user/profile_pic/$uuid"), "avatar");
        parse_template('user/view', $data);
    }
    
    function raw($uuid)
    {
        if ( ! $this->_me_or_admin($uuid) ) {
            return redirect('user/index');
        }
        $data['user_data'] = $this->simiangrid->get_user($uuid);
        parse_template('user/raw', $data, true);
    }
    
    function _change_password($uuid)
    {    
        $success = false;
        
        $val = $this->form_validation;
        $val->set_rules('password', 'Password', 'trim|required|xss_clean|min_length[8]|max_length[30]');
        
        if ( $val->run() ) {
            $password = $val->set_value('password');
            $user_data = $this->simiangrid->get_user($uuid);
            if ( $this->simiangrid->identity_set($uuid, 'md5hash', $user_data['Name'], '$1$' . md5($password)) ) {
                if ( $this->simiangrid->identity_set($uuid, 'a1hash', $user_data['Name'], md5($user_data['Name'] . ':Inventory:' . $password)) ) {
                    $success = true;
                }
            }
        }
        $result = json_encode(array('success'=>$success));
        echo $result;
        return;
    }

    function _change_access_level($uuid)
    {
        $val = $this->form_validation;
        $val->set_rules('value', 'access_level', 'trim|required|xss_clean|numeric');
        
        if ( $val->run() ) {
            $level = $val->set_value('value');
            $levels = $this->sg_auth->access_level_map();
            if ( ! empty($levels[$level]) ) {
                if ( $this->simiangrid->set_access_level($uuid, $level) ) {
                    echo $levels[$level];
                } 
            }
        }
        return;
    }
    
    function _load_access_level($uuid)
    {
        $user = $this->simiangrid->get_user($uuid);
        if ( $user != null ) {
            echo json_access_levels($user['AccessLevel']);
        }
    }
    
    function _change_ban_status($uuid)
    {
        $val = $this->form_validation;
        $val->set_rules('value', 'access_level', 'trim|required|xss_clean');
        
        if ( $val->run() ) {
            $real_val = $val->set_value('value');
            
            if ( $real_val == 'true' ) {
                $status = true;
            } else if ( $real_val == 'false' ) {
                $status = false;
            } else {
                return;
            }
            if ( $status ) {
                $result = $this->sg_auth->ban_user($uuid);
            } else {
                $result = $this->sg_auth->unban_user($uuid);
            }
            if ( $result ) {
                if ( $status ) {
                    echo lang('sg_auth_banned');
                } else {
                    echo lang('sg_auth_not_banned');
                }
            }
        }
        return;
    }
    
    function _load_ban_status($uuid)
    {
        $ban_data = array(
            'true' => lang('sg_auth_banned'),
            'false' => lang('sg_auth_not_banned')
        );
        if ( $this->sg_auth->is_banned($uuid) ) {
            $ban_data['selected'] = 'true';
        } else {
            $ban_data['selected'] = 'false';
        }
        echo json_encode($ban_data);
    }
    
    function _change_validation_status($uuid)
    {
        $val = $this->form_validation;
        $val->set_rules('value', 'validation_status', 'trim|required|xss_clean');
        
        if ( $val->run() ) {
            $real_val = $val->set_value('value');
            
            if ( $real_val == 'true' ) {
                $status = true;
            } else if ( $real_val == 'false' ) {
                $status = false;
            } else {
                return;
            }
            if ( $status ) {
                $result = $this->sg_auth->set_valid($uuid);
            } else {
                $result = $this->sg_auth->reset_validation($uuid);
            }
            if ( $result ) {
                if ( $status ) {
                    echo lang('sg_auth_validated');
                } else {
                    echo lang('sg_auth_not_validated');
                }
            }
        }
        return;
    }

    function _load_validation_status($uuid)
    {
        $validation_data = array(
            'true' => lang('sg_auth_validated'),
            'false' => lang('sg_auth_not_validated')
        );
        if ( $this->sg_auth->is_validated($uuid) ) {
            $validation_data['selected'] = 'true';
        } else {
            $validation_data['selected'] = 'false';
        }
        echo json_encode($validation_data);
    }
    
    function _get_language($uuid)
    {
        return get_language($uuid);
    }
    
    function _load_language($uuid)
    {
        $current_language = $this->_get_language($uuid);
        $languages = $this->config->item('languages');
        $result = array();
        
        if ( ! empty($languages))
        {
            foreach ( $languages as $language ) {
                $language_name = lang("sg_lang_$language");
                if ( $language_name == null ) {
                    $language_name = $language;
                }
                $result[$language] = $language_name;
            }
        }
        echo json_encode($result);
    }
    
    function _change_language($uuid)
    {
        $val = $this->form_validation;
        $val->set_rules('value', '', 'trim|required|xss_clean');
        
        if ( $val->run() ) {
            $language = $val->set_value('value');
            $languages = $this->config->item('languages');
            if ( array_search($language, $languages) !== false ) {
                $this->user_settings->set_language($uuid, $language);
            }
        }
    }
    
    function _get_search_flag($uuid)
    {
        $user = $this->simiangrid->get_user($uuid);
        $search_flag = $this->config->item('user_search_default');
        if ( isset($user['AllowPublish']) ) {
            $search_flag = $user['AllowPublish'];
        }
        return $search_flag;
    }

    function _change_search_flag($uuid)
    {
        $val = $this->form_validation;
        $val->set_rules('value', '', 'trim|required|xss_clean');
        
        if ( $val->run() ) {
            $raw_flag = $val->set_value('value');
            if ( $raw_flag == "true" ) {
                $flag = true;
            } else if ( $raw_flag == "false" ) {
                $flag = false;
            } else {
                log_message('error', "_change_search_flag unknown flag $flag");
                return;
            }
            $this->simiangrid->set_user_data($uuid, 'AllowPublish', $flag);
            if ( $flag ) {
                echo lang('sg_user_search_public');
            } else {
                echo lang('sg_user_search_private');
            }
        }
    }

    function _load_search_flag($uuid)
    {
        $search_flags = array(
            'true' => lang('sg_user_search_public'),
            'false' => lang('sg_user_search_private')
        );
        if ( $this->_get_search_flag($uuid) ) {
            $search_flags['selected'] = 'true';
        } else {
            $search_flags['selected'] = 'false';
        }
        echo json_encode($search_flags);
    }

    function _reset_avatar($uuid)
    {
        if ( ! $this->simiangrid->create_avatar($uuid, "DefaultAvatar") ) {
            push_message(set_message('sg_avatar_reset_fail', 'Backend Failure'), 'error');
        }
        return redirect('user/view/' . $uuid, 'location');
    }

    function admin_actions($uuid, $action=null)
    {
        if ( ! $this->sg_auth->is_admin($uuid) ) {
            return redirect('user/index');
        }
        $user = $this->simiangrid->get_user($uuid);
        if ( $user == null ) {
            push_message(set_message('sg_user_not_found', $uuid), 'error');
            return redirect('user/');
        }
        if ( $action == "change_access_level" && $this->sg_auth->is_admin() ) {
            return $this->_change_access_level($uuid);
        } else if ( $action == "load_access_level" && $this->sg_auth->is_admin() ) {
            return $this->_load_access_level($uuid);
        } else if ( $action == "change_ban_status" && $this->sg_auth->is_admin() ) {
            return $this->_change_ban_status($uuid);
        } else if ( $action == "load_ban_status" ) {
            return $this->_load_ban_status($uuid);
        } else if ( $action == "change_validation_status" && $this->sg_auth->is_admin() ) {
            return $this->_change_validation_status($uuid);
        } else if ( $action == "load_validation_status" ) {
            return $this->_load_validation_status($uuid);
        } else if ( $action == "reset_avatar" ) {
            return $this->_reset_avatar($uuid);
        } else {
            $data['user_id'] = $uuid;
            $data['user_data'] = $this->simiangrid->get_user($uuid);
            $data['my_uuid'] = $this->sg_auth->get_uuid();
            if ( $this->sg_auth->is_banned($uuid) ) {
                $data['banned'] = lang('sg_auth_banned');
            } else {
                $data['banned'] = lang('sg_auth_not_banned');
            }
            if ( $this->sg_auth->is_validated($uuid) ) {
                $data['validation'] = lang('sg_auth_validated');
            } else {
                $data['validation'] = lang('sg_auth_not_validated');
            }
            return parse_template('user/admin_actions', $data, true);
        }
    }

    function actions($uuid, $action=null)
    {
        if ( ! $this->_me_or_admin($uuid) ) {
            return redirect('about');
        }
        $user = $this->simiangrid->get_user($uuid);
        if ( $user == null ) {
            push_message(set_message('sg_user_not_found', $uuid), 'error');
            return redirect('user/');
        }
        if ( $action == "change_password" ) {
            return $this->_change_password($uuid);
        } else if ( $action == "change_language" ) {
            return $this->_change_language($uuid);
        } else if ( $action == "load_language") {
            return $this->_load_language($uuid);
        } else if ( $action == "change_search_flag" ) {
            return $this->_change_search_flag($uuid);
        } else if ( $action == "load_search_flag" ) {
            return $this->_load_search_flag($uuid);
        } else {
            $data['user_id'] = $uuid;
            $data['user_data'] = $this->simiangrid->get_user($uuid);
            $data['my_uuid'] = $this->sg_auth->get_uuid();
            $data['language'] = $this->_get_language($uuid);
            if ( $this->_get_search_flag($uuid) ) {
                $data['search_visibility'] = lang('sg_user_search_public');
            } else {
                $data['search_visibility'] = lang('sg_user_search_private');
            }
            return parse_template('user/actions', $data, true);
        }
    }
}
