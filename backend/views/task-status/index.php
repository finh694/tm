<?php

use backend\models\TaskStatus;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel common\models\TaskStatusSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model TaskStatus */

$this->title = 'Task Statuses';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-status-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Task Status', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'rowOptions' => function ($model) {
            return ['style' => "background-color: {$model->color}"];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            'text',
            [
                'attribute' => 'deletedAt',
                'label' => 'Status',
                'filter' => ['is'=>'Active','is not'=>'Deleted'],//смахивает на говнокод
                'value' => function ($model) {
                    return ($model->deletedAt) ? ' Deleted at ' . date("Y-m-d", $model->deletedAt) : "Active";
                }
            ],
            'finally:boolean',
            [
                'class' => 'yii\grid\ActionColumn',
                'visibleButtons' => [
                    'delete' => function ($model) {
                        return $model->deletedAt == null;
                    }
                ],
                'contentOptions' => ['style' => "background-color: white "],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
