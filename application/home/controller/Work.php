<?php
/**
 * Created by PhpStorm.
 * User: laowang
 * Date: 2017/1/17
 * Time: 14:49
 */
namespace app\home\controller;
use app\home\model\Notice;
use app\home\model\Apply;
use app\home\model\WechatUserTag;
use app\home\model\WechatUser;
use think\Request;

class Work extends Base{
    /*
     * 党史
     */
    public function dangshi(){
        return  $this->fetch();
    }
    /*
     * 通讯录
     */
    public function structurelist(){
        return $this->fetch();
    }
    /*
     * 通讯录详情
     */
    public function structuredetail(){
        $party = input('party');
        $this->assign('party',$party);
        return $this->fetch();
    }

    /*
     * 党员签到
     */
    public function sign()
    {
        $map1 = ['meet_time' => ['gt',time()]];
        $map2 = ['meet_time' => ['elt',time()]];
        $order = 'meet_time desc';
        $go = Notice::where($map2)->order($order)->limit(10)->select();
        $now = Notice::where($map1)->order($order)->limit(10)->select();

        $this->assign('go',$go);  // 已经结束
        $this->assign('now',$now);  // 进行中

        return $this->fetch();
    }

    /**
     * 党员签到获取更多
     */
    public function getMoreSignList()
    {
        //1为最新 2为历史
        $type = input('type');
        $len = input('length');
        $map1 = ['meet_time' => ['gt',time()]];
        $map2 = ['meet_time' => ['elt',time()]];
        $order = 'meet_time desc';

        if ($type == 1) {
            $list = Notice::where($map1)->order($order)->limit($len,5)->select();
        } else {
            $list = Notice::where($map2)->order($order)->limit($len,5)->select();
        }

        if (!empty($list)) {
            // 数据转化
            foreach ($list as $k=>$v) {
                $list[$k]['src'] = get_cover($list[$k]['front_cover'])['path'];
                $list[$k]['time'] = date('Y-m-d H:i',$list[$k]['meet']);
            }

            return $this->success('获取成功!',null,$list);
        } else {

            return $this->error('获取失败!');
        }
    }

    /**
     * 党员签到详情
     */
    public function main()
    {
        $this->jssdk();
        $id = input('id');
        $uid = session('userId');
        $data = Notice::where(['id' => $id])->find();
        // 获取签到报名用户信息
        $Apply = Apply::where(['notice_id'=>$id])->order('create_time desc')->select();
        $arr = array();
        if (empty($Apply)) {

        } else {

            foreach ($Apply as $value) {
                $Wechat = WechatUser::where(['userid'=>$value['userid']])->order('id desc')->find();
                $arr[] = "<img src='".$Wechat['avatar']."'/><span>".$Wechat['name']."</span>";
            }
        }
        $data['infoes'] = $arr;

        //查看权限
        $c = WechatUserTag::where(['userid' => $uid,'tagid' => 1])->find();

        // 判断活动是否过期
        if ($data['meet_time'] > time()) {
            $now = 1;
        } else {
            $now = 0;
        }

        $this->assign('data',$data);
        $this->assign('c',$c);
        $this->assign('now',$now);

        return $this->fetch();
    }

    /**
     * 扫一扫签到
     */
    public function scan()
    {
        $id = input('id');
        $userid = input('user_id');
        $result = Apply::where(array('notice_id'=>$id,'userid'=>$userid))->find();
        $Wechat = WechatUser::where(['userid'=>$userid])->find();

        if($result){

            return array('status'=>0,'header'=>$Wechat['avatar'],'name'=>$Wechat['name']);
        }else{

            if($Wechat){

                // 会议过期
                $data = Notice::where(['id' => $id])->find();
                if ($data['meet_time'] <= time()) {
                    return array('status'=>2,'header'=>null,'name'=>null,'err_msg'=>'很抱歉，您已过了签到时间，下次请准时来哦!');
                }

                // 新增签到记录
                $data = array(
                    'notice_id' => $id,
                    'userid' =>$userid,
                    'status' =>0
                );
                $Apply = new Apply();
                $res = $Apply->save($data);

                if ($res) {
                    WechatUser::where(['userid'=>$userid])->setInc('score', 10);

                    return array('status'=>1,'header'=>$Wechat['avatar'],'name'=>$Wechat['name']);
                } else {

                    return array('status'=>2,'header'=>null,'name'=>null,'err_msg'=>'签到失败');
                }
            } else {

                return array('status'=>2,'header'=>null,'name'=>null,'err_msg'=>'请扫描正确党员二维码');
            }

        }
    }

    /**
     * 游客登录首页
     * @return mixed
     */
    public function touristmain2(){
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

    /**
     * 党建之家
     * @return mixed
     */
    public function touristmain3(){
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

    /**
     * 党建动态
     * @return mixed
     */
    public function touristmain4(){
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

    /**
     * 品牌同创
     * @return mixed
     */
    public function touristmain5(){
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

    /**
     * 两学一做
     * @return mixed
     */
    public function touristmain6(){
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

    /**
     * 数据统计
     * @return mixed
     */
    public function touristmain7(){
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

    /**
     * 游客二维码
     */
    public function qrcode()
    {
        $request = Request::instance();
        $this->assign('link',$request->domain());

        return $this->fetch();
    }

}