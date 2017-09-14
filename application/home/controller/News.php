<?php
/**
 * 党建动态 品牌同创
 * Created by PhpStorm.
 * User: sitff
 * Date: 2017/8/22
 * Time: 下午2:01
 */
namespace app\home\controller;
use app\home\model\News as NewsModel;
use app\home\model\Comment;
use app\home\model\Like;
use app\home\model\Browse;
use app\home\model\WechatUser;


class News extends Base
{
    /**
     * 首页
     * $type 1党建时讯 2校园动态 3先锋活动 4先锋榜样
     */
    public function index($type)
    {
        $list1 = '';
        $list2 = '';
        $list3 = '';

        if ($type == 1 || $type == 4){
            // 两个分类
            if ($type ==1) {

                 // 新闻聚焦
                $list1 = $this->getDataList(1,0);
                 // 各地动态
                $list2 = $this->getDataList(2,0);
            } else {

                 // 好人好事
                $list1 = $this->getDataList(9,0);
                // 支部风采
                $list2 = $this->getDataList(10,0);
            }


        } else if ($type == 2 || $type == 3) {
            // 三个分类
            if ($type ==2) {

                // 主题党日活动
                $list1 = $this->getDataList(3,0);
                // 双报到
                $list2 = $this->getDataList(4,0);
                // 党员先锋活动
                $list3 = $this->getDataList(5,0);
            } else {

                // 竞赛辅导
                $list1 = $this->getDataList(6,0);
                // 心理辅导
                $list2 = $this->getDataList(7,0);
                // 家校联系
                $list3 = $this->getDataList(8,0);
            }
        }

        $this->anonymous(); // 游客模式判断
        $this->assign('list1',$list1);
        $this->assign('list2',$list2);
        $this->assign('list3',$list3);
        $this->assign('type',$type);
        return $this->fetch();
    }

    /**
     * 获取数据
     * @param $type 类型
     * @param $limit 限定条数
     */
    public function getDataList($type,$limit)
    {
        $map = ['status' => ['egt',0]];
        $map['type'] = $type;
        $res = NewsModel::where($map)->limit($limit,7)->select();

        return $res;
    }

    /**
     * 加载更多
     */
    public function getMoreDataList()
    {
        $type = input('class');
        $class = input('type');
        $len = input('length');

        if ($type == 1 || $type == 4){
            // 两个分类
            if ($type ==1) {

                if ($class == 1) {
                    // 新闻聚焦
                    $dataType = 1;
                } else {
                    // 各地动态
                    $dataType = 2;
                }
            } else {

                if ($class == 1) {
                    // 好人好事
                    $dataType = 9;
                } else {
                    // 支部风采
                    $dataType = 10;
                }
            }

        } else if ($type == 2 || $type == 3) {
            // 三个分类
            if ($type ==2) {

                if ($class == 1) {
                    // 主题党日活动
                    $dataType = 3;
                } else if ($class == 2){
                    // 双报到
                    $dataType = 4;
                } else {
                    // 党员先锋活动
                    $dataType = 5;
                }
            } else {

                if ($class == 1) {
                    // 竞赛辅导
                    $dataType = 6;
                } else if ($class == 2){
                    // 心理辅导
                    $dataType = 7;
                } else {
                    // 家校联系
                    $dataType = 8;
                }

            }

        }

        $res = $this->getDataList($dataType,$len);

        if (!empty($res)) {

            foreach ($res as $k => $v) {

                $res[$k]['time'] = date('Y-m-d',$v['create_time']);
                $res[$k]['src'] = get_cover($v['front_cover'])['path'];
            }
            return $this->success('获取成功!',null,$res);
        } else {

            return $this->error('获取失败!');
        }

    }

    /**
     * 详情页
     */
    public function detail()
    {
        //游客模式
        $this ->anonymous();
        $userId = session('userId');
        $id = input('get.id');
        $type = input('get.type');
        if(!empty($id)) {

            $map = array('id' => $id,'status' => ['egt',0]);
            $res = NewsModel::where($map) ->find();
            if (empty($res)) {

                return $this->error('该文章不存在或已删除!');
            }else{
                $news = new NewsModel();
                //浏览加一
                $info['views'] = array('exp','`views`+1');
                $news::where('id',$id)->update($info);

                if ($userId != "visitor") {
                    //浏览不存在则存入pb_browse表
                    $con = array(
                        'user_id' => $userId,
                        'news_id' => $id,
                    );
                    $history = Browse::get($con);
                    if(!$history && $id != 0){

                        Browse::create($con);
                    }
                } else {

                    $con = array(
                        'user_id' => $userId,
                        'news_id' => $id,
                    );
                    Browse::create($con);
                }


                //新闻基本信息
                $list = $news::get($id);

                //获取 文章点赞
                $likeModel = new Like();
                $like = $likeModel->getLike(2,$id,$userId);
                $list['is_like'] = $like;
                $this->assign('new',$list);

                //获取 评论
                $commentModel = new Comment();
                $comment = $commentModel->getComment(2,$id,$userId);
                $this->assign('comment',$comment);
                $this->assign('type',$type);

                return $this->fetch();
            }

            return $this->fetch();
        }else{

            return $this->error('参数错误!');
        }
    }

    /**
     * 线上发布
     */
    public function publish()
    {
        if (IS_POST) {

            $uid = session('userId');
            $data = input('post.');
            $newsModel = new NewsModel();
            $data['publisher'] = get_name($uid);
            $data['create_user'] = $uid;

            if ($data['type'] == 3) {

                $data['table_type'] = 1;
                $score = 10; // 主题党日活动
            } else if ($data['type'] == 4 || $data['type'] == 5){

                $data['table_type'] = 1;
                $score = 5; // 双报到以及其他活动
            } else {

                $data['table_type'] = 2;
                $score = 3; // 品牌同创
            }
            $model = $newsModel->data($data)->save();
            if($model){
                // 积分规则 体悟反思发布文章积分+10分
                WechatUser::where('userid',$uid)->setInc('score',$score);

                return $this->success('新增成功!');
            }else{

                return $this->error($newsModel->getError());
            }

        } else {

            $data = input('get.');
            if ($data['type'] == 1) {
                $type = ($data['tab_type'] == 1?1:2);
            } else if ($data['type'] == 4) {
                $type = ($data['tab_type'] == 1?9:10);
            } else if ($data['type'] == 2) {
                if ($data['tab_type'] == 1) {
                    $type = 3;
                } else if ($data['tab_type'] == 2) {
                    $type = 4;
                } else {
                    $type = 5;
                }
            } else if ($data['type'] == 3) {
                if ($data['tab_type'] == 1) {
                    $type = 6;
                } else if ($data['tab_type'] == 2) {
                    $type = 7;
                } else {
                    $type = 8;
                }
            }

            $this->assign('type',$type);

            return $this->fetch();
        }

    }
}
