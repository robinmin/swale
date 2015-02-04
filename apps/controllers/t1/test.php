<?php

class test extends base_ctrl{
    public function index2($param=''){
        $this->_data = [
            'class' => __CLASS__,
            'line'  => __LINE__,
            'param' => $param
        ];
        $this->render();
    }

    public function task(){
        $svr = AppServer::get_instance();
        echo '<pre>';
        $svr->task('MyTest', 'run_test_job', array('time'=>123));
        echo '</pre>';
    }

    public function test_sql_read(){
        $model = $this->get_model('base_model');

        // $svr = AppServer::get_instance();
        // $svr->get_plugin('database');

        // $plgn = super_app::get_plugin('database');

        // var_dump($model->sql_read("select * from dx_users where username='robin'"));
        // var_dump($model->sql_write("update dx_users set email='test@geexfinance.cn' where username='robin'"));

        var_dump($model->find_by(
            'dx_users',
            'username = :username',
            '',
            array('username'=>'haier')
        ));
    }

    public function test_sess(){
        $app = super_app::get_app();
        echo '<pre>';
        echo 'old  = '.$app->session->get('once').'<br />';

        $new_val = rand(1, 9999);
        $app->session->set('once', $new_val);
        echo 'new  = '.$app->session->get('once').'<br />';
        print_r($_SESSION);
        echo '</pre>';
    }
}
