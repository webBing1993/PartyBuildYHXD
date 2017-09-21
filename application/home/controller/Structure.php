<?php
/**
 * Created by PhpStorm.
 * User: 老王
 * Date: 2016/11/2
 * Time: 13:21
 */
namespace app\home\controller;
use app\home\model\WechatDepartment;
use app\home\model\WechatDepartmentUser;
use app\home\model\WechatUser;

class Structure extends Base{
    /*
     * 组织架构主页
     */
    public function index()
    {
        $department = WechatDepartment::where('parentid',1)->order('id desc')->select();
        $this->assign('deparment',$department);

        return $this->fetch();
    }
    /*
     * 组织架构详情页
     */
    public function detail()
    {
        $this ->checkAnonymous();
        $party = input('party');
        $users = WechatDepartmentUser::where('departmentid',$party)->select();
        $this->assign('users',$users);

        return $this->fetch();
    }

    /**
     * 个人信息详情
     */
    public function overview()
    {
        $uid = input('id');
        $user = WechatUser::where('userid',$uid)->find();
        $this->assign('user',$user);

        return $this->fetch();
    }

}
