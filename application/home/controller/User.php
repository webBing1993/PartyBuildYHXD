<?php
/**
 * Created by PhpStorm.
 * User: Lxx<779219930@qq.com>
 * Date: 2016/4/20
 * Time: 15:34
 */

namespace app\home\controller;
use app\home\model\Learn as LearnModel;
use app\home\model\News as NewsModel;
use app\home\model\Picture;
use app\home\model\WechatUser;
use app\home\model\WechatUserTag;
use app\home\model\Collect;
use think\Db;
/**
 * Class User
 * 用户个人中心
 */
class User extends Base {
    /**
     * 个人中心主页
     */
    public function index(){
        //是否游客登录
        $this->anonymous();
        $userId = session('userId');
        $user = WechatUser::where('userid',$userId)->find();
        $this->assign('user',$user);

        //是否具备我的发布权限,具备为1，无则为0
        $map = array(
            'userid' => $userId,
            'tagid' => 5, //权限标签id
        );
        $info = WechatUserTag::where($map)->find();
        if($info) {
            $this->assign('is',1);
        }else{
            $this->assign('is',0);
        }
        return $this->fetch();
    }

    /**
     * 个人信息页
     */
    public function personal(){
        $userId = session('userId');
        $user = WechatUser::where('userid',$userId)->find();
        switch ($user['gender']) {
            case 0:
                $user['sex'] = "未定义";
                break;
            case 1:
                $user['sex'] = "男";
                break;
            case 2:
                $user['sex'] = "女";
                break;
            default:
                break;
        }

        // 党龄周年计算
        if (!empty($user['partytime'])) {

            $year = substr($user['partytime'],0,4);
            $thisYear = date('Y',time());
            $user['partyStand'] = $thisYear - $year .'年';
        } else {

            $user['partyStand'] = '';
        }

        $this->assign('user',$user);

        return $this->fetch();
    }

    /**
     * 设置头像
     */
    public function setHeader(){
        $userId = session('userId');
        $header = input('header');
        $map = array(
            'header' => $header,
        );
        $info = WechatUser::where('userid',$userId)->update($map);
        if($info){
            return $this->success("修改成功");
        }else{
            return $this->error("修改失败");
        }
    }

    /***
     * 签到二维码
     */
    public function qcode()
    {
        $this->assign('userId',session('userId'));
        return $this->fetch();
    }


    /**
     * 我的发布
     */
    public function mypublish()
    {
        $uid = session('userId');
        $res = Db::field('title,create_time,music_path as table_type,user_type,id,front_cover') // 利用不存在值,判断两张表
            ->table('pb_learn')
            ->union("SELECT title,create_time,table_type,user_type,id,front_cover FROM pb_news where create_user=$uid order by create_time desc limit 8")
            ->where('create_user',$uid)
            ->select();

        $this->assign('list',$res);

        return $this->fetch();
    }

    /**
     * 我的发布加载更多
     */
    public function moreMypublish()
    {
        $len = input('length');
        $uid = session('userId');
        $res = Db::field('title,create_time,music_path as table_type,user_type,id,front_cover') // 利用不存在值,判断两张表
        ->table('pb_learn')
            ->union("SELECT title,create_time,table_type,user_type,id,front_cover FROM pb_news where create_user=$uid order by create_time desc limit $len,7")
            ->where('create_user',$uid)
            ->select();

        if (!empty($res)) {

            foreach ($res as $k=>$v) {
                $img = Picture::get($v['front_cover']);
                if (empty($img)) {
                    $img['path'] = get_defalut_cover(1); //1 Learn
                }
                $res[$k]['path'] = $img['path'];
                $res[$k]['time'] = date("Y-m-d",$v['create_time']);
            }

            return $this->success('获取成功!',null,$res);
        } else {

            return $this->error('加载失败!');
        }

    }


}
