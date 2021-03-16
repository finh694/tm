<?php

namespace backend\controllers;

use common\models\AttachmentFiles;
use common\models\TaskPriority;
use common\models\TaskStatus;
use common\models\User;
use Yii;
use common\models\Task;
use common\models\TaskSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * TaskController implements the CRUD actions for Task model.
 */
class TaskController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Task models.
     * @return mixed
     */
    public function actionIndex()
    {
//        var_dump( Yii::$app->basePath);exit();
        $params['TaskSearch'] = ['deletedAt' => 0];
        $title = 'All tasks';
        return $this->index($params, $title);
    }

    public function actionManagedTasks()
    {
        $params['TaskSearch'] = ['manager_id' => Yii::$app->user->id, 'deletedAt' => 0];
        $title = 'Controlled tasks by me';
        return $this->index($params, $title);
    }

    public function actionCreatedTasks()
    {
        $params['TaskSearch'] = ['creator_id' => Yii::$app->user->id, 'deletedAt' => 0];
        $title = 'Tasks created by me';
        return $this->index($params, $title);
    }

    public function actionUnassignedTasks()
    {
        $params['TaskSearch'] = ['user_id' => 0, 'deletedAt' => 0];
        $title = 'Unassigned tasks';
        return $this->index($params, $title);
    }

    public function actionMyTasks()
    {
        $params['TaskSearch'] = ['user_id' => Yii::$app->user->id, 'deletedAt' => 0];
        $title = 'My tasks';
        return $this->index($params, $title);
    }

    public function actionDeletedTasks()
    {
        $params['TaskSearch'] = ['deletedAt' => 1];
        $title = 'Deleted tasks';
        return $this->index($params, $title);
    }

    public function index($params, $title)
    {
        $searchModel = new TaskSearch();
        $dataProvider = $searchModel->search(array_merge_recursive(Yii::$app->request->queryParams, $params));
        $statusList = TaskStatus::find()
            ->select(['text', 'id'])
            ->andWhere(['deletedAt' => null])
            ->indexBy('id')
            ->asArray()
            ->column();
        $priorityList = TaskPriority::find()
            ->select(['name', 'id'])
            ->andWhere(['active' => true])
            ->indexBy('id')
            ->asArray()
            ->column();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'statusList' => $statusList,
            'priorityList' => $priorityList,
            'title' => $title,
        ]);
    }

    /**
     * Displays a single Task model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Task model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Task();
        return $this->taskCreateOrUpdate($model, 'create');
    }

    /**
     * Updates an existing Task model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        return $this->taskCreateOrUpdate($model, 'update');
    }

    /**
     * @param Task $model
     * @param string $view
     * @return string|\yii\web\Response
     */
    public function taskCreateOrUpdate(Task $model, string $view, string $changeMod = '')
    {
        $userList = User::find()
            ->select(['username', 'id'])
            ->leftJoin('auth_assignment', '"user".id = cast( auth_assignment.user_id as integer)')
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->andWhere(['!=', "item_name", "banned"])
            ->indexBy('id')
            ->asArray()
            ->column();
        $managerList = User::find()
            ->select(['username', 'id'])
            ->leftJoin('auth_assignment', '"user".id = cast( auth_assignment.user_id as integer)')
            ->andWhere(['status' => User::STATUS_ACTIVE])
            ->andWhere(['!=', "item_name", "banned"])
            ->andWhere(['!=', "item_name", "user"])
            ->indexBy('id')
            ->asArray()
            ->column();
        $statusList = TaskStatus::find()
            ->select(['text', 'id'])
            ->andWhere(['deletedAt' => null])
            ->indexBy('id')
            ->asArray()
            ->column();
        $priorityList = TaskPriority::find()
            ->select(['name', 'id'])
            ->andWhere(['active' => true])
            ->indexBy('id')
            ->asArray()
            ->column();
        $statusColor = TaskStatus::find()
            ->select(['color','id'])
            ->andWhere(['deletedAt'=> null])
            ->indexBy('id')
            ->asArray()
            ->column();
        if ($model->load(Yii::$app->request->post())) {
            if ($view === "create") {
                $model->creator_id = Yii::$app->user->getId();
                if (!Yii::$app->user->can('admin')) {
                    $model->manager_id = $model->creator_id;
                }
            }
            if ($model->save()) {
                if ($toDelete =  Yii::$app->request->post("toDelete")) { //todo remake
                    $this->deleteFiles($toDelete);
                }
                if ($files = UploadedFile::getInstances($model, 'files')) {
                    $this->saveFiles($files, $model->id);
                }
                return $this->redirect(['index']);
            }
        }
        if ($view === 'modal-change') {
            return $this->renderAjax($view, [
                'model' => $model,
                'userList' => $userList,
                'statusList' => $statusList,
                'priorityList' => $priorityList,
                'statusColor'=>$statusColor,
                'changeMod' => $changeMod
            ]);
        }
        return $this->render($view, [
            'model' => $model,
            'userList' => $userList,
            'statusList' => $statusList,
            'priorityList' => $priorityList,
            'managerList'=>$managerList,
            'statusColor'=>$statusColor,
        ]);

    }

    /**
     * Deletes an existing Task model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->softDelete();

        return $this->redirect(['index']);
    }

    public function actionRecover($id)
    {
        $this->findModel($id)->recover();
        return $this->redirect(['index']);
    }

    public function actionModal($id, $mod)
    {
        $model = $this->findModel($id);

        return $this->taskCreateOrUpdate($model, 'modal-change', $mod);

    }

    /**
     * Finds the Task model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Task the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    private function deleteFiles(string $idsToDelete)
    {
        $ids = explode(',', $idsToDelete);
        foreach ($ids as $idFile) {
            if ($modelFile = AttachmentFiles::find()->where(['id' => $idFile])->one()) {
                $modelFile->delete();
                Yii::$app->storage->deleteFile($modelFile->name);
            }
        }
        return true;
    }

    private function saveFiles(array $files, $taskId)
    {
        foreach ($files as $file) {
            $modelFile = new AttachmentFiles();
            $modelFile->task_id = $taskId;
            $modelFile->native_name = $file->name;
            $modelFile->name = Yii::$app->storage->saveUploadedFile($file);
            $modelFile->save();
        }
    }
}