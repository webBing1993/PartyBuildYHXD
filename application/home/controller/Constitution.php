<?php
/**
 * Created by PhpStorm.
 * User: Lxx<779219930@qq.com>
 * Date: 2016/8/16
 * Time: 9:12
 */

namespace app\home\controller;
use app\home\model\WechatUser;
use think\Controller;
use app\admin\model\Question;
use app\home\model\Answer;
use app\home\model\Answers;

/**
 * class Constitution
 * 抄党章
 */
class Constitution extends Base {
    /**
     * 排行榜
     */
    public function rank(){
        $this->anonymous();
        $this ->checkAnonymous();
        $type = input('type');  //获取类型，1为常规模式排行 2为互动模式排行

        $wechatModel = new WechatUser();
        $userId = session('userId');
        $personal = $wechatModel->where('userid',$userId)->find();    //获取个人信息

        //传统模式
        $map['trad_score'] = array('neq',0);
        $trad = $wechatModel->where($map)->order('trad_score desc')->limit(50)->select();
        foreach ($trad as $key => $value) {
            if($value['userid'] == $userId) {
                $personal['trad_rank'] = $key+1;     //该用户传统排名
            }
        }
        //游戏模式
        $map1['game_score'] = array('neq',0);
        $game = $wechatModel->where($map1)->order('game_score desc')->limit(50)->select();
        foreach ($game as $key => $value) {
            if($value['userid'] == $userId) {
                $personal['game_rank'] = $key+1;     //该用户游戏排名
            }
        }
        if(isset($personal['trad_rank'])) {
            $personal['trad_rank'] = "第".$personal['trad_rank']."名";
        }else {
            $personal['trad_rank'] = "无";
        }
        if(isset($personal['game_rank'])) {
            $personal['game_rank'] = "第".$personal['game_rank']."名";
        }else {
            $personal['game_rank'] = "无";
        }
        $this->assign('per',$personal);
        $this->assign('trad',$trad);
        $this->assign('game',$game);
        $this->assign('type',$type);

        return $this->fetch();
    }

    /**
     * 互动模式主页
     */
    public function game(){
        //获取该用户的的信息
        $this ->checkAnonymous();
        $users=$users=session('userId');
        $info = Answer::get(['userid'=>$users]);
        if($info) {
            $exist=$info->exist;
            $this->assign('exist',$exist);
            $this->assign('info',$info);
        }else {
            $this->assign('exist',"");
            $this->assign('info',"");
        }

        return $this->fetch();
    }
    
    /**
     * 答题页面
     */
    public function answer(){
        $this ->checkAnonymous();
        //取单选
        $arr=Question::all(['type'=>0]);
        foreach($arr as $value){
            $ids[]=$value->id;
        }
        //获取用户已经得到的题目
        $users=$users=session('userId');
        $List=Answer::get(['userid'=>$users]);
        if($List !==null){
            $list=$List->question_id;
            $lists=json_decode($list);
        }else{
            $lists=array();
        }
        //随机获取单选的题目
        $num=20;//题目数目
        $data=array();
        while(true){
            if(count($data)==$num){
                break;
            }
            $index=mt_rand(0,count($ids)-1);
            $res=$ids[$index];
            if(!in_array($res,$data) && !in_array($res,$lists)){
                $data[]=$res;
            }
        }
        foreach($data as $value){
            $question[]=Question::get($value);
        }

        //取多选
        $arr2=Question::all(['type'=>1]);
        foreach($arr2 as $value){
            $ids2[]=$value->id;
        }
        //随机获取多选
        $num2=10;//题目数目
        $data2=array();
        while(true){
            if(count($data2)==$num2){
                break;
            }
            $index2=mt_rand(0,count($ids2)-1);
            $res2=$ids2[$index2];
            if(!in_array($res2,$data2) && !in_array($res2,$lists)){
                $data2[]=$res2;
            }
        }
        foreach($data2 as $value){
            $questions[]=Question::get($value);
        }
        $this->assign('question',$question);
        $this->assign('questions',$questions);
        return $this->fetch();
    }
    /*
     * 用户题目保存
     */
    public function save(){
        //获取用户提交信息
        $data=input('post.');
        //将题目ID数目json编码
        $questions=json_encode($data['arrId']);
        //将用户提交答案数组json编码
        $rights=json_encode($data['arr']);
        $users=session('userId');
        //若该用户已经存在则更新
        if(Answer::get(['userid'=>session('userId')])){
            $answer=Answer::get(['userid'=>session('userId')]);
            $answer->question_id=$questions;
            $answer->value=$rights;
            $answer->exist=0;
            if($answer->save()){
                return $this->success('保存成功');
            }else{
                return $this->error('保存失败');
            };
        }
        //若该用户不存在则添加
        $Answer=new Answer();
        $Answer->userid=$users;
        $Answer->question_id=$questions;
        $Answer->value=$rights;
        $Answer->exist=0;

        if($Answer->save()){
            return $this->success('保存成功');
        }else{
            return $this->error('保存失败');
        }
    }
    /*
     * 继续答题
     */
    public function goon(){
        //获取该用户的已经保存的信息
        $users=$users=session('userId');
        $Info=Answer::get(['userid'=>$users]);
        //用户信息不存在,则报错跳转
        if(empty($Info)){
            return $this->error('用户信息不存在错误',Url('Constitution/game'));
        }
        //获取的题目ID,将json格式转化为数组
        $arr=json_decode($Info->question_id,true);
        //获取单选和多选的题目
        foreach($arr as $key=>$value){
            if($key>19){
                $arr2[]=Question::get($value);
            }else{
                $arr1[]=Question::get($value);
            }
        }
        //获取的题目答案,将json格式转化为数组
        $rights=json_decode($Info->value,true);
        //获取单选和多选的答案
        foreach($rights as $key=>$value){
            if($key<20){
                $right1[]=$value;
            }else{
                $right2[]=$value;
            }
        }
        $this->assign('right1',$right1);
        $this->assign('right2',$right2);
        $this->assign('arr1',$arr1);
        $this->assign('arr2',$arr2);
        return $this->fetch();
    }
    /*
     * 用户题目提交
     */
    public function submits(){
        //获取用户提交信息
        $data=input('post.');
        $score=0;
        //判断题目的对错,并改变分数
        foreach($data['arrId'] as $key=>$value){
            $question=Question::get($value);
            if($key <20){
                if($data['arr'][$key]==$question->value){
                    $status[$key]=1;
                    $score++;
                }else{
                    $status[$key]=0;
                }
            }else{
                if($data['arr'][$key]===explode(':',$question->value)){
                    $status[$key]=1;
                    $score++;
                }else{
                    $status[$key]=0;
                }
            }
        }
        //将获取的数据进行json格式转化
        $questions=json_encode($data['arrId']);
        $rights=json_encode($data['arr']);
        $status=json_encode($status);
        $users=session('userId');
        //将分数添加至用户积分排名
        $wechatModel = new WechatUser();
        $wechatModel->where('userid',session('userId'))->setInc('game_score',$score);
        //若该用户存在则修改数据
        if(Answer::get(['userid'=>session('userId')])){
            $answer=Answer::get(['userid'=>session('userId')]);
            $answer->question_id=$questions;
            $answer->value=$rights;
            $answer->status=$status;
            $answer->score=$score;
            $answer->exist=1;
            if($answer->save()){
                return $this->success('提交成功');
            }else{
                return $this->error('提交失败');
            };
        }
        //若该用户不存在则添加数据
        $Answer=new Answer();
        $Answer->userid=$users;
        $Answer->question_id=$questions;
        $Answer->value=$rights;
        $Answer->status=$status;
        $Answer->score=$score;
        $Answer->exist=1;
        if($Answer->save()){
            return $this->success('提交成功');
        }else{
            return $this->error('提交失败');
        }

    }
    /*
     * 查看分数
     */
    public function score(){
        $Answer=Answer::get(['userid'=>session('userId')]);
        $WechatUser=WechatUser::get(['userid'=>session('userId')]);
        $num=$WechatUser->game_score;
        $score=$Answer->score;
        $this->assign('num',$num);
        $this->assign('score',$score);
        return $this->fetch();
    }
    /*
     * 查看错题
     */
    public function errors(){
        $Answer=Answer::get(['userid'=>session('userId')]);
        $arr=json_decode($Answer->status,true);
        $lists=json_decode($Answer->question_id,true);
        $rights=json_decode($Answer->value,true);
        foreach($arr as $key=>$value){
            if($value == 0){
                $Question=Question::get($lists[$key]);
                if($key <20 ){
                    $re[$key]=$Question;
                    $right1[$key]=$rights[$key];
                }else{
                    $res[$key]=$Question;
                    $right2[$key]=$rights[$key];
                }
            }
        }
        $this->assign('question',$re);
        $this->assign('questions',$res);
        $this->assign('right1',$right1);
        $this->assign('right2',$right2);
        return $this->fetch();
    }
    /*
     * 每日一课 页面
     */
    public function course(){
        $userid = session('userId');
        $day = date("j",time());  // 获取当天日期  没有前导0
        $mon = date("n",time());  // 获取当天月份  没有前导0
        $year = date("Y",time());  // 获取当天年份
        $map = array(
            'userid' => $userid,
        );
        $Answers = Answers::where($map)->order('id desc')->limit(1)->find();
        if(empty($Answers)){   // 没有数据
            //取单选
            $arr=Question::all(['type'=>0]);
            foreach($arr as $value){
                $ids[]=$value->id;
            }
            //随机获取单选的题目
            $num=3;//题目数目
            $data=array();
            while(true){
                if(count($data) == $num){
                    break;
                }
                $index=mt_rand(0,count($ids)-1);
                $res=$ids[$index];
                if(!in_array($res,$data)){
                    $data[]=$res;
                }
            }
            foreach($data as $value){
                $question[]=Question::get($value);
            }
            $this->assign('question',$question);
            return $this->fetch();
        }else{  //  有数据
            $user_day = date("j", $Answers['create_time']);  // 获取用户答题的日期
            $user_mon = date("n", $Answers['create_time']);  // 获取用户答题的月份  无前导0
            $user_year = date("Y", $Answers['create_time']);  // 获取用户答题的年
            if($day != $user_day ){  // 当天 还未答题
                //取单选
                $arr=Question::all(['type'=>0]);
                foreach($arr as $value){
                    $ids[]=$value->id;
                }
                //随机获取单选的题目
                $num=3;//题目数目
                $data=array();
                while(true){
                    if(count($data) == $num){
                        break;
                    }
                    $index=mt_rand(0,count($ids)-1);
                    $res=$ids[$index];
                    if(!in_array($res,$data)){
                        $data[]=$res;
                    }
                }
                foreach($data as $value){
                    $question[]=Question::get($value);
                }
                $this->assign('question',$question);
                return $this->fetch();
            }else{
                if ($mon != $user_mon){
                    //取单选
                    $arr=Question::all(['type'=>0]);
                    foreach($arr as $value){
                        $ids[]=$value->id;
                    }
                    //随机获取单选的题目
                    $num=3;//题目数目
                    $data=array();
                    while(true){
                        if(count($data) == $num){
                            break;
                        }
                        $index=mt_rand(0,count($ids)-1);
                        $res=$ids[$index];
                        if(!in_array($res,$data)){
                            $data[]=$res;
                        }
                    }
                    foreach($data as $value){
                        $question[]=Question::get($value);
                    }
                    $this->assign('question',$question);
                    return $this->fetch();
                }else{
                    if($year != $user_year){
                        //取单选
                        $arr=Question::all(['type'=>0]);
                        foreach($arr as $value){
                            $ids[]=$value->id;
                        }
                        //随机获取单选的题目
                        $num=3;//题目数目
                        $data=array();
                        while(true){
                            if(count($data) == $num){
                                break;
                            }
                            $index=mt_rand(0,count($ids)-1);
                            $res=$ids[$index];
                            if(!in_array($res,$data)){
                                $data[]=$res;
                            }
                        }
                        foreach($data as $value){
                            $question[]=Question::get($value);
                        }
                        $this->assign('question',$question);
                        return $this->fetch();
                    }else{
                        // 当天已经答过题
                        $Qid = json_decode($Answers->question_id);
                        $rights=json_decode($Answers->value);
                        $re = array();
                        foreach($Qid as $key => $value){
                            $re[$key] = Question::get($value);
                            $re[$key]['right'] = $rights[$key];
                        }
                        $this->assign('question',$re);
                        return $this->fetch('scan');
                    }
                }
            }
        }
    }
    /*
     * 每日一课 提交
     */
    public function commit(){
        // 获取用户提交数据
        $data = input('post.');
        $arr = $data['data'];
        $question = array();
        $status = array();
        $options = array();
        $Right = array();
        $score = 0;
        foreach($arr as $key => $value){
            $Question=Question::get($value[0]);
            switch($Question->value){
                case 1:
                    $right = "A";
                    break;
                case 2:
                    $right = "B";
                    break;
                case 3:
                    $right = "C";
                    break;
                case 4:
                    $right = "D";
                    break;

            }
            $Right[$key+1] = $right;
            $question[$key] = $value[0];
            $options[$key] = $value[1];
             // 判断 题目的对错
            if($value[1] == $Question->value){
                $status[$key] = 1;
                $score ++;
            }else{
                $status[$key] = 0;
            }
        }
        //将获取的数据进行json格式转化
        $questions = json_encode($question);
        $rights = json_encode($options);
        $status = json_encode($status);
        $users = session('userId');
        //将分数添加至用户积分排名
        if ($score != 3) {
            $score = 0;
        }
        $wechatModel = new WechatUser();
        $wechatModel->where('userid',$users)->setInc('score',$score);
        //  存储 表
        $Answers = new Answers();
        $Answers->userid = $users;
        $Answers->question_id = $questions;
        $Answers->value = $rights;
        $Answers->status = $status;
        $Answers->score = $score;
        $Answers->create_time = time();
        $res = $Answers->save();
        if($res){
            return $this->success('提交成功',array('id' =>$res,'score'=>$score,'right'=>$Right));
        }else{
            return $this->error('提交失败');
        }
    }
    /*
     * 每日一课  查看详情
     */
    public function scan(){
        $id = input('id');
        $Answers = Answers::get($id);
        $Qid = json_decode($Answers->question_id);
        $rights=json_decode($Answers->value);
        $re = array();
        foreach($Qid as $key => $value){
            $re[$key] = Question::get($value);
            $re[$key]['right'] = $rights[$key];
        }
        $this->assign('question',$re);
        return $this->fetch();
    }
}