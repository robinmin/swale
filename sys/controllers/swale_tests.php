<?php

require SYS_PATH.'/controllers/utest.php';

class swale_tests extends utest{
    private $_test_user_info = array();

    public function __construct($name=''){
        parent::__construct($name);
        $this->_test_user_info = array(
            'C_USER_NAME'   => 'robin',
            'N_ROLE_ID'     => 0,
            'C_PASSWORD'    => 'password',
            'C_EMAIL'       => 'test@test.com',
            'C_PHONE'       => '13800138000',
            'C_ALIAS'       => 'robin',
            'D_CREATE'      => date('Y-m-d H:i:s'),
            'C_CREATER'     => 'system'
        );

        $auth = $this->get_model('auth_model');

        // Delete test user account here
        if( !is_int($auth->delete('SYS_USERS', 'C_USER_NAME=:username', ['username' => $this->_test_user_info['C_USER_NAME']])) ){
            log_message('error', 'Failed to cleanup test user information');
        }

        // Add test user account here
        $user_info = $this->_test_user_info;
        $user_info['C_PASSWORD'] = $auth->get_password_hash($user_info['C_USER_NAME'], $user_info['C_PASSWORD']);
        if( !is_int($auth->insert('SYS_USERS', $user_info)) ){
            log_message('error', 'Failed to add test user information');
        }
    }

    public function __destruct(){
        $auth = $this->get_model('auth_model');

        // Delete test user account here
        if( !is_int($auth->delete('SYS_USERS', 'C_USER_NAME=:username', ['username' => $this->_test_user_info['C_USER_NAME']])) ){
            log_message('error', 'Failed to cleanup test user information');
        }
    }
    // /**
    //  * Cleanup function that is run before each test case
    //  * Override this method in test classes!
    //  */
    // public function _pre() {
    //     $auth = $this->get_model('auth_model');

    //     $this->_post();

    //     // Add test user account here
    //     $user_info = $this->_test_user_info;
    //     $user_info['C_PASSWORD'] = $auth->get_password_hash($user_info['C_USER_NAME'], $user_info['C_PASSWORD']);
    //     if( !is_int($auth->insert('SYS_USERS', $user_info)) ){
    //         log_message('error', 'Failed to add test user information');
    //     }
    // }

    // /**
    //  * Cleanup function that is run after each test case
    //  * Override this method in test classes!
    //  */
    // public function _post() {
    //     $auth = $this->get_model('auth_model');

    //     // Delete test user account here
    //     if( !is_int($auth->delete('SYS_USERS', 'C_USER_NAME=:username', ['username' => $this->_test_user_info['C_USER_NAME']])) ){
    //         log_message('error', 'Failed to cleanup test user information');
    //     }
    // }

    public function test_auth_init(){
        $auth = $this->get_model('auth_model');

        $this->_assert_true(!$auth->is_logged_in(),      'Not auto login');
        $this->_assert_true($auth->get_user_name()==='', 'No user name');
        $this->_assert_true($auth->get_role_name()==='', 'No role name');
        $this->_assert_true($auth->logout(),             'Logout on invalid session');
    }

    public function test_auth_login(){
        $auth = $this->get_model('auth_model');
        $this->_assert_true(!$auth->login('user' ,'password'), 'Invalid username/password');

        $this->_assert_true($auth->login($this->_test_user_info['C_USER_NAME'], $this->_test_user_info['C_PASSWORD']), 'Valid username & password');
        $this->_assert_true($auth->logout(), 'Normal logout');

        $this->_assert_true($auth->login($this->_test_user_info['C_EMAIL'], $this->_test_user_info['C_PASSWORD']), 'Valid email & password');
        $auth->logout();

        $this->_assert_true($auth->login($this->_test_user_info['C_PHONE'], $this->_test_user_info['C_PASSWORD']), 'Valid mobile# & password');
        $auth->logout();
    }

    public function test_auth_user_info(){
        $auth = $this->get_model('auth_model');

        $this->_assert_true($auth->login($this->_test_user_info['C_USER_NAME'], $this->_test_user_info['C_PASSWORD']), 'Valid username & password');

        $this->_assert_true($auth->is_a('Unknown'),   'Check Unknown');
        $this->_assert_false($auth->is_a('SysAdmin'), 'Check SysAdmin');
        $this->_assert_false($auth->is_a('BiZAdmin'), 'Check BiZAdmin');
        $this->_assert_true($auth->get_user_name()===$this->_test_user_info['C_USER_NAME'], 'Check user name');

        $this->_assert_true(is_array($auth->get_user_info()), 'Check user info is not null');

        $this->_assert_true($auth->logout(), 'Normal logout');
        $this->_assert_true($auth->get_user_info()===false, 'Check user info is false when logout');
    }

    public function test_auth_user_role(){
        $auth = $this->get_model('auth_model');

        $this->_assert_true($auth->login($this->_test_user_info['C_USER_NAME'], $this->_test_user_info['C_PASSWORD']), 'Valid username & password');
        $this->_assert_true($auth->is_a('Unknown'),   'Check role : Unknown');

        $this->_assert_true($auth->grant($auth->get_user_name(), 'SysAdmin'),   'Check grant SysAdmin');
        $this->_assert_true($auth->is_a('SysAdmin'),   'Check role : SysAdmin');

        $this->_assert_true($auth->logout(), 'Normal logout');
    }

}
