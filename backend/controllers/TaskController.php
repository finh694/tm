<?php

namespace backend\controllers;

use common\models\AttachmentFiles;
use backend\models\TaskPriority;
use backend\models\TaskStatus;
use common\models\User;
use Yii;
use backend\models\Task;
use common\models\TaskSearch;
use yii\filters\AccessControl;
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
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => [
                            'index', 'managed-tasks', 'created-tasks', 'unassigned-tasks', 'my-tasks', 'download',
                            'my-active-tasks', 'my-closed-tasks', 'view', 'create', 'update', 'delete', 'modal', 'modal-image'
                        ],
                        'allow' => true,
                        'roles' => ['manager'],
                    ],
                    [
                        'actions' => ['deleted-tasks', 'recover'],
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
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
        $params['TaskSearch'] = ['deletedAt' => 0];
        $title = 'All tasks';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionManagedTasks()
    {
        $params['TaskSearch'] = ['manager_id' => Yii::$app->user->id, 'deletedAt' => 0];
        $title = 'Controlled tasks by me';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionCreatedTasks()
    {
        $params['TaskSearch'] = ['creator_id' => Yii::$app->user->id, 'deletedAt' => 0];
        $title = 'Tasks created by me';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionUnassignedTasks()
    {
        $params['TaskSearch'] = ['user_id' => 0, 'deletedAt' => 0];
        $title = 'Unassigned tasks';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionMyTasks()
    {
        $params['TaskSearch'] = ['user_id' => Yii::$app->user->id, 'deletedAt' => 0];
        $title = 'My tasks';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionMyActiveTasks()
    {
        $params['TaskSearch'] = ['user_id' => Yii::$app->user->id, 'deletedAt' => 0, 'statusFinally' => false];
        $title = 'My active tasks';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionMyClosedTasks()
    {
        $params['TaskSearch'] = ['user_id' => Yii::$app->user->id, 'deletedAt' => 0, 'statusFinally' => true];
        $title = 'My closed tasks';
        return $this->index($params, $title);
    }

    /**
     * @return string
     */
    public function actionDeletedTasks()
    {
        $params['TaskSearch'] = ['deletedAt' => 1];
        $title = 'Deleted tasks';
        return $this->index($params, $title);
    }

    /**
     * @param $params
     * @param $title
     * @return string
     */
    public function index($params, $title)
    {
        $searchModel = new TaskSearch();
        Yii::$app->user->returnUrl = Yii::$app->request->url;
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
            ->orderBy('value')
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
            ->orderBy('value')
            ->asArray()
            ->column();
        $statusColor = TaskStatus::find()
            ->select(['color', 'id'])
            ->andWhere(['deletedAt' => null])
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
                if ($toDelete = Yii::$app->request->post("toDelete")) { //todo find better way to delete
                    $this->deleteFiles($toDelete);
                }
                if ($files = UploadedFile::getInstances($model, 'files')) {
                    $this->saveFiles($files, $model->id);
                }
                $this->log($view, $model->title, $model->id);
                return $this->redirect(Yii::$app->user->returnUrl ?: Yii::$app->homeUrl);
            }
        }
        if ($view === 'modal-change') {
            return $this->renderAjax($view, [
                'model' => $model,
                'userList' => $userList,
                'statusList' => $statusList,
                'priorityList' => $priorityList,
                'statusColor' => $statusColor,
                'changeMod' => $changeMod,
                'managerList' => $managerList,
            ]);
        }
        return $this->render($view, [
            'model' => $model,
            'userList' => $userList,
            'statusList' => $statusList,
            'priorityList' => $priorityList,
            'managerList' => $managerList,
            'statusColor' => $statusColor,
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
        if ($model = $this->findModel($id)) {
            $model->softDelete();
            $this->log('delete', $model->title, $id);
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRecover($id)
    {
        if ($model = $this->findModel($id)) {
            $model->recover();
            $this->log('recover', $model->title, $id);
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * @param $id
     * @param $mod
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionModal($id, $mod)
    {
        $model = $this->findModel($id);
        if (Yii::$app->user->can('admin') || $model->manager_id === Yii::$app->user->getId()
            || ($model->user_id === Yii::$app->user->getId() && $mod==='status')) {
            return $this->taskCreateOrUpdate($model, 'modal-change', $mod);
        } else {
            return 'error';
        }
    }

    /**
     * @param $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionModalImage($id)
    {
        $model = $this->findModel($id);
        return $this->renderAjax('modal-image', [
            'model' => $model,
        ]);
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

    /**
     * @param string $idsToDelete
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
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

    /**
     * @param array $files
     * @param $taskId
     */
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

    /**
     * @param string $action
     * @param string $title
     * @param int $modelId
     */
    private function log(string $action, string $title, int $modelId)
    {
        Yii::info($action . ' task "' . $title . '"(id-' . $modelId . ')', 'log');
    }

    /**
     * @param $id
     */
    public function actionDownload($id)
    {
        $file = AttachmentFiles::findOne($id);
        $path = Yii::$app->storage->getFileLocation($file->name);
        if (file_exists($path)) {
            return Yii::$app->response->sendFile($path, $file->native_name)->send();
        }
    }
}
