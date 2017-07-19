<?php

namespace app\controllers;

use app\models\StAbsenteeism;
use app\models\StEstimation;
use app\models\StGroups;
use app\models\StItems;
use app\models\StListStudents;
use app\models\StSpecialization;
use app\models\Teachers;
use app\models\Units;
use app\models\UserUnits;
use yii\web\UploadedFile;
use Yii;
use yii\data\Pagination;

class StudentsController extends \yii\web\Controller
{
    public $layout = "mainWork";

    public function actionIndex()
    {
        //return $this->render('index');
    }

    /**
     * @return string
     */
    public function actionGroups()
    {
        $model = new StGroups();
        $chains = Units::find()->where(['type'=>'3'])->all();
        $id_facult = UserUnits::rIdFacult();
        $qroups = StGroups::find()->with(['idCurator'])->where(['id_facult'=>$id_facult])->all();
        $specialization = StSpecialization::rArrSpecialization();

        $arr_render = [];
        $arr_render['model'] = $model;
        $arr_render['chains'] = $chains;
        $arr_render['groups'] = $qroups;
        $arr_render['specialization'] = $specialization;

        $request = \Yii::$app->request;
        $get = $request->get();
        if(isset($get['msg']))
            $arr_render['msg'] = "Групу успішно створено!";

        if($request->isPost){
            $post = $request->post("StGroups");
            $name = $post['name']; $name = trim($name);
            $id_curator = isset($post['id_curator']) ? $post['id_curator'] : "";

            if($name == ""){
                $arr_render['error'] = "Ви не ввели назву групи!";
                return $this->render('groups',$arr_render);
            }elseif(!isset($post['id_curator']) || $id_curator == ""){
                $arr_render['error'] = "Ви не обрали куратора групи!";
                return $this->render('groups',$arr_render);
            }else{
                date_default_timezone_set('Europe/Kiev');

                $model->name = $name;
                $model->id_facult = $id_facult;
                $model->year_entry = $post['year_entry'];
                $model->year_graduation = $post['year_graduation'];
                $model->id_curator = $id_curator;
                $model->create_date = date("Y-m-d H:i:s");
                $model->education_form = $post['education_form'];
                $model->id_specialization = $post['id_specialization'];
                if($model->save()){
                    Yii::$app->getResponse()->redirect(Yii::$app->urlManager->createUrl(["students/groups",
                        "msg"=>'1',
                    ]));
                }
            }

        }

        return $this->render('groups',$arr_render);
    }

    public function actionList_items(){
        date_default_timezone_set('Europe/Kiev');
        $request = \Yii::$app->request;
        $model = new StItems();

        $get = $request->get();

        if(isset($get['group'])){
            $id = $get['group'];
            $group = StGroups::find()->select(['id','name'])->where(['id'=>$id])->one();

            if($request->isPost){
                $post = $request->post("StItems");
                $model->id_group = $id;
                $model->title = $post['title'];
                $model->teacher = $post['teacher'];
                $model->create_date = date("Y-m-d H:i:s");
                $model->save();
            }

            $query = StItems::find()->where(['id_group'=>$id])->all();

            return $this->render('list_items',[
                'group'=>$group,
                'model'=>$model,
                'query'=>$query,
            ]);
        }
    }

    public function actionAdd_mark(){
        $request = \Yii::$app->request;
        $get = $request->get();
        if(isset($get['id_group']) && isset($get['id_item'])){
            if($request->isPost){
                $post = $request->post("StEstimation");
                $marks = $post['Cmark'];
                foreach($marks as $k=>$v){
                    $model = new StEstimation();
                    $model->id_item = $get['id_item'];
                    $model->id_group = $get['id_group'];
                    $model->id_student = $k;
                    $model->mark = (trim($v) != '') ? $v : 0;
                    $model->date = $post['date'];
                    $model->save();
                }
            }
            $model = new StEstimation();
            $students = StListStudents::find()->select(['id','full_name'])->orderBy("full_name ASC")->where(['id_group'=>$get['id_group']])->all();
            $group = StGroups::find()->select(['name'])->where(['id'=>$get['id_group']])->one();
            $item = StItems::find()->select(['title'])->where(['id'=>$get['id_item']])->one();

            return $this->render('add_mark',[
                'group'=>$group,
                'item'=>$item,
                'students'=>$students,
                'model'=>$model,
            ]);
        }
    }

    public function actionAdd_student(){
        $request = \Yii::$app->request;
        $model = new StListStudents();
        $get = $request->get();

        if(isset($get['group'])){
            $id_group = $get['group'];
            $group = StGroups::find()->select(['name'])->where(['id'=>$id_group])->one();
            $specialization = StSpecialization::rArrSpecialization();

            if($request->isPost){
                $form = $request->post("StListStudents");
                $id_facult = UserUnits::rIdFacult();
                $model->photo = UploadedFile::getInstance($model, 'photo');
                if ($model->upload($form, $id_group, $id_facult)) {
                    Yii::$app->getResponse()->redirect(Yii::$app->urlManager->createUrl(["students/list_student","group"=>$id_group]));
                }
            }

            return $this->render('add_student',[
                'group'=>$group->name,
                'specialization'=>$specialization,
                'model'=>$model,
            ]);
        }
    }

    public function actionList_student(){
        $request = \Yii::$app->request;
        $model = new StListStudents();
        $get = $request->get();

        if(isset($get['group'])){
            $id_group = $get['group'];
            $group = StGroups::find()->select(['name'])->where(['id'=>$id_group])->one();
            $students = StListStudents::find()->where(['id_group'=>$id_group])->all();

            return $this->render('list_student',[
                'group'=>$group->name,
                'model'=>$model,
                'students'=>$students,
                'id_group'=>$id_group,
            ]);
        }
    }

    public function actionAbsenteeism(){
        //date_default_timezone_set('Europe/Kiev');
        $request = \Yii::$app->request;
        $get = $request->get();

        if(isset($get['group'])){
            $model = new StAbsenteeism();
            $group = StGroups::find()->select(['name'])->where(['id'=>$get['group']])->one();
            $group = $group->name;
            $items = StItems::find()->select(['id','title'])->where(['id_group'=>$get['group']])->all();
            $students = StListStudents::find()->select(['id','full_name'])->where(['id_group'=>$get['group']])->orderBy("full_name ASC")->all();

            $arrItems = [];
            foreach($items as $key){
                $arrItems[$key->id] = $key->title;
            }

            if($request->isPost){
                $post = $request->post("StAbsenteeism");
                $id_item = $post['id_item'];
                $teacher = trim($post['teacher']);
                $hours = trim($post['hours']);
                $cstudent = $post['Cstudent'];

                foreach ($cstudent as $k=>$v){
                    if($v == 1){
                        $record = new StAbsenteeism();
                        $record->id_group = $get['group'];
                        $record->id_student = $k;
                        $record->id_item = $id_item;
                        $record->hours = $hours;
                        $record->worked_hours = 0;
                        $record->teacher = $teacher;
                        $record->date = date("Y-m-d");
                        $record->save();
                    }
                }
            }

            return $this->render('absenteeism',[
                'model'=>$model,
                'items'=>$arrItems,
                'students'=>$students,
                'group'=>$group,
            ]);
        }
    }

    public function actionView_absenteeism(){
        $request = \Yii::$app->request;
        $model = new StAbsenteeism();

        if($request->isPost){
            $post = $request->post('StAbsenteeism');
            $id_facult = $post['id_facult'];
            $id_group = $post['id_group'];
            $e_form = $post['education_form'];
            //$reason = $post['reason'];

            Yii::$app->getResponse()->redirect(Yii::$app->urlManager->createUrl(['students/res_view_absenteeism',
                'id_facult'=> $id_facult,
                'id_group'=>$id_group,
                'e_form'=>$e_form,
                //'reason'=>$reason,
            ]));
        }

        $facults = Units::listFacult();
        $arrGroups = StGroups::rSortGroups('','');

        return $this->render('view_absenteeism',[
            'model'=>$model,
            'facults'=>$facults,
            'groups'=>$arrGroups,
        ]);
    }

    public function actionRes_view_absenteeism(){
        $request = \Yii::$app->request;
        if($request->isGet){
            $model = new StAbsenteeism();
            $id_facult = $request->get('id_facult');
            $id_group = $request->get('id_group');
            $e_form = $request->get('education_form');

            $query = StAbsenteeism::find()->joinWith(['idGroup','idStudent']);

            if($id_facult != "") $query = $query->andWhere(['st_groups.id_facult'=>$id_facult]);
            if($id_group != "") $query = $query->andWhere(['st_absenteeism.id_group'=>$id_group]);
            if($e_form != "") $query = $query->andWhere(['st_groups.education_form'=>$e_form]);

            $query = $query->groupBy(['st_absenteeism.id_student'])->orderBy("sum(hours) DESC");

            $count = $query->count();
            $pages = new Pagination(['totalCount' => $count, 'pageSize' => '50']);
            $query = $query->offset($pages->offset)->limit($pages->limit);

            $query = $query->all();

            return $this->render('res_view_absenteeism', [
                'query'=> $query,
                'count'=>$count,
                'pages'=>$pages,
                'model'=>$model,
            ]);
        }
    }

    public function actionStudent_absenteeism(){
        $request = \Yii::$app->request;
        if($request->isGet){
            $model = new StAbsenteeism();
            $id = $request->get('id');
            $student = StListStudents::find()->select(['full_name','id_group'])->with(['idGroup'])->where(['id'=>$id])->one();

            $type = $request->get('type');
            $query = StAbsenteeism::find()->with(['idItem'])->orderBy('date ASC')->where(['id_student'=>$id]);
            switch($type){
                case '1': $query = $query->andWhere(['worked_hours'=>'0']); break;
                case '2': $query = $query->andWhere("worked_hours != '0'"); break;
            }

            $count = $query->count();
            $pages = new Pagination(['totalCount' => $count, 'pageSize' => '50']);
            $query = $query->offset($pages->offset)->limit($pages->limit);

            $query = $query->all();

            return $this->render('student_absenteeism', [
                'query'=> $query,
                'count'=>$count,
                'pages'=>$pages,
                'model'=>$model,
                'full_name'=>$student->full_name,
                'group'=>$student->idGroup->name,
            ]);
        }
    }

    public function actionAdd_reference(){
        $request = \Yii::$app->request;
        $get = $request->get();
        if(isset($get['id'])){
            $model = new StAbsenteeism();
            $id = $get['id'];
            if($request->isPost){
                $post = $request->post('StAbsenteeism');
                $reason = $post['reason'];
                $cstudent = $post['Cstudent'];
                foreach($cstudent as $k=>$v){
                    if($v==1){
                        $record = StAbsenteeism::findOne($k);
                        $record->reason = $reason;
                        $record->save();
                    }
                }
                Yii::$app->getResponse()->redirect(Yii::$app->getRequest()->getUrl());
            }

            $student = StListStudents::find()->select(['full_name','id_group'])->with(['idGroup'])->where(['id'=>$id])->one();
            $query = StAbsenteeism::find()->with(['idItem'])->orderBy('date ASC')->where(['id_student'=>$id]);

            $count = $query->count();
            $pages = new Pagination(['totalCount' => $count, 'pageSize' => '50']);
            $query = $query->offset($pages->offset)->limit($pages->limit);

            $query = $query->all();
            return $this->render('add_reference', [
                'query'=> $query,
                'count'=>$count,
                'pages'=>$pages,
                'model'=>$model,
                'full_name'=>$student->full_name,
                'group'=>$student->idGroup->name,
            ]);
        }
    }

    public function actionSearch(){
        $model = new StListStudents();
        $request = \Yii::$app->request;

        if($request->isPost){
            $post = $request->post('StListStudents');
            $full_name = trim($post['full_name']);
            $id_facult = $post['id_facult'];
            $id_group = $post['id_group'];
            $payer = $post['payer'];

            if($full_name != ""){
                $query = StListStudents::find()->andFilterWhere(['like','full_name',$full_name])->orderBy('full_name ASC');
            }else{
                $query = StListStudents::find()->orderBy('full_name ASC');
                if($id_facult != "" && $id_group == ""){
                    $query = $query->where(['id_facult'=>$id_facult]);
                }
                if($id_group != ""){
                    $query = $query->where(['id_group'=>$id_group]);
                }
                if($payer != ""){
                    $query = $query->andFilterWhere(['payer'=>$payer]);
                }
            }

            $count = $query->count();
            $pages = new Pagination(['totalCount' => $count, 'pageSize' => 50]);
            $query = $query->offset($pages->offset)->limit($pages->limit)->all();

            return $this->render('result_search',['query'=> $query,'count'=>$count,'pages'=>$pages]);

        }
        $facults = Units::listFacult();

        $query_groups = StGroups::find()->select(['id','name'])->orderBy("name ASC")->all();
        $arrGroups = [];
        foreach($query_groups as $key){
            $arrGroups[$key->id] = $key->name;
        }

        return $this->render('search',[
            'model'=>$model,
            'facults'=>$facults,
            'groups'=>$arrGroups,
        ]);
    }

    public function actionStudent_info(){
        $request = \Yii::$app->request;

        if($request->isGet){
            $id = $request->get('id');
            $count = StListStudents::find()->where(['id'=>$id])->count();
            $student = StListStudents::findOne($id);

            return $this->render('student_info',[
                'count'=>$count,
                'student'=>$student,
            ]);
        }else{
            return $this->render('student_info',[
                'count'=>'0'
            ]);
        }
    }

    /**************************************** ajax ***********************************************************/

    public function actionGroups_json(){
        $request = \Yii::$app->request;
        $data = json_decode($request->post('jsonData'));
        $facult = $data->{'facult'};
        $e_form = $data->{'e_form'};

        $arr = StGroups::rSortGroups($facult,$e_form);
        echo json_encode($arr);
    }

    public function actionAdd_workout_json(){
        $request = \Yii::$app->request;
        $data = json_decode($request->post('jsonData'));
        $id = $data->{'id'};
        $teacher = $data->{'teacher'};
        $date = $data->{'date'};
        $hours = $data->{'hours'};

        $record = StAbsenteeism::findOne($id);
        $record->worked_teacher = $teacher;
        $record->worked_hours = $hours;
        $record->worked_date = $date;
        if($record->save()) echo json_encode('1');
        else json_encode('2');
    }
}















