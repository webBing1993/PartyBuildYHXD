<?php
/**
 * Created by PhpStorm.
 * User: 老王
 * Date: 2016/11/2
 * Time: 13:21
 */
namespace app\home\controller;
use app\home\model\Learn;
use app\home\model\Browse;
use app\home\model\Picture;
use app\home\model\Like;
use app\home\model\Comment;
use app\home\model\WechatUser;
use app\home\model\Collect;

class Topic extends Base{
    /*
     * 专题讨论主页
     */
    public function index(){
        $this->anonymous();        //判断是否是游客
        $map1 = array(
            'type' => 4,
            'status' => 0
        );
        $learn = Learn::where($map1)->order('id desc')->limit(7)->select();
        $this->assign('list',$learn);
        return $this->fetch();
    }
    /*
     * 专题讨论   加载更多
     */
    public function more(){
        $len = input('length');
        $map = array(
            'type' => 4,
            'status' => array('eq',0),
        );
        $list = Learn::where($map)->order('id desc')->limit($len,5)->select();
        foreach($list as $k=>$v){

            if (!$v['front_cover']) {
                $img = get_defalut_cover(1); //1 Learn
            } else {
                $img = Picture::get($v['front_cover']);

                $img = $img['path'];
            }
            $list[$k]['src'] = $img;
            $list[$k]['time'] = date("Y-m-d",$v['create_time']);
        }
        if($list){
            return $this->success("加载成功",'',$list);
        }else{
            return $this->error("加载失败");
        }
    }

    /*
     * 详情页
     */
    public function detail(){
        $this->anonymous();        //判断是否是游客
        $this->jssdk();
        $id = input('id');
        $userId = session('userId');
        $learnModel = new Learn();
        //浏览加一
        $info['views'] = array('exp','`views`+1');
        $learnModel::where('id',$id)->update($info);

        $article = $learnModel::get($id);
        $article['user'] = session('userId');

        if($userId != "visitor"){
            //浏览不存在则存入pb_browse表
            $con = array(
                'user_id' => $userId,
                'learn_id' => $id,
            );
            $history = Browse::get($con);
            if(!$history && $id != 0){

                Browse::create($con);
            }
        } else {

            $con = array(
                'user_id' => $userId,
                'learn_id' => $id,
            );
            Browse::create($con);
        }


        //分享图片及链接及描述
        $image = Picture::where('id',$article['front_cover'])->find();
        $article['share_image'] = "http://".$_SERVER['SERVER_NAME'].$image['path'];
        $article['link'] = $_SERVER['REDIRECT_URL'];
        $article['desc'] = str_replace('&nbsp;','',strip_tags($article['content']));



        //获取 文章点赞
        $likeModel = new Like;
        $like = $likeModel->getLike(1,$id,$userId);
        $article['is_like'] = $like;
        if($article['type'] == 4){
            $this->assign('c',1);
        }else{
            $this->assign('c',0);
        }
        $this->assign('detail',$article);

        //获取 评论
        $commentModel = new Comment();
        $comment = $commentModel->getComment(1,$id,$userId);
        $this->assign('comment',$comment);

        return $this->fetch();
    }

    /**
     * 线上发布
     */
    public function publish()
    {
        if (IS_POST) {

            $uid = session('userId');
            $data = input('post.');
            $learnModel = new Learn();
            $data['publisher'] = get_name($uid);
            $data['create_user'] = $uid;
            $model = $learnModel->data($data)->save();
            if($model){
                // 积分规则 体悟反思发布文章积分+10分
                WechatUser::where('userid',$uid)->setInc('score',10);

                return $this->success('新增成功!');
            }else{

                return $this->error($learnModel->getError());
            }

        } else {

            return $this->fetch();
        }

    }

}
