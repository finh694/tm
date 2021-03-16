<?php

use yii\helpers\Html;
use yii\web\YiiAsset;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Task */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);
?>
<div class="task-view">


    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
//            'id',
            'title',
//            'text:html',
            [
                'label' => 'Text',
                'format' => 'html',
                'value' => preg_replace("/(^|[\n ])([\w]*?)((ht|f)tp(s)?:\/\/[\w]+[^ ,\"\n\r\t<]*)/is",
                    "$1$2<a href=\"$3\" >$3</a>", $model->text),
            ],
            'files',
            [
                'label' => 'Priority',
                'value' => $model->priority->name,
            ],
            [
                'label' => 'Status',
                'value' => $model->status->text,
                'contentOptions' => ['style' => 'background-color:' . $model->status->color]
            ],
            [
                'label' => 'User',
                'value' => $model->user ? $model->user->username : 'not set',
            ],
            [
                'label' => 'Manager',
                'value' => $model->manager ? $model->manager->username : 'not set',
            ],
            [
                'label' => 'Creator',
                'value' => $model->creator ? $model->creator->username : 'not set',
            ],
            'createdAt:datetime',
            'updatedAt:datetime',
            'deletedAt:datetime',
        ],
    ]) ?>

</div>
