<?php

use backend\assets\ModalAsset;
use frontend\assets\ImageAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model common\models\Task */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => Yii::$app->user->returnUrl ?: 'index'];
$this->params['breadcrumbs'][] = $model->title;
ImageAsset::register($this);
ModalAsset::register($this);
$btnClass = Yii::$app->user->can('admin') || $model->manager_id == Yii::$app->user->getId() ? 'btn mod' : '';
$mainBG = $model->deletedAt ? 'bg-black-gradient' : 'bg-gray';
?>
<div class="task-view">
    <div class="container">
        <div class="box box-widget <?= $mainBG ?>">
            <div class="box-header with-border">
                <div class="box-tools label bg-orange <?= $btnClass ?> priority" data-key="<?= $model->id ?>">
                    Priority: <?= $model->priority->name ?></div>

                <div class="box-title">
                    <div class="user-block">
                        <div class="title text-blue">
                            <?= $model->deletedAt ? '<div class=" label text-red">Deleted</div>' . $model->title : $model->title ?>
                        </div>
                        <div class="help-block small">Created
                            at: <?= date("Y-m-d H:i:s", $model->createdAt) ?> by <?= $model->creator->username ?></div>
                        <div class="help-block small"><?= $model->deletedAt ? 'Deleted' : 'Updated' ?>
                            at: <?= date("Y-m-d H:i:s", $model->updatedAt) ?></div>
                    </div>

                </div>
            </div>
            <div class="box-body">
                <div class="box-body bg-gray-active ">
                    <div class=""> <?= $model->getTextWithLinks() ?></div>
                </div>
                <?php if ($model->attachmentFiles) : ?>
                    <div class="clearfix bg-info">
                        <?php foreach ($model->attachmentFiles as $file) : ?>
                            <div class="container-img file" title="<?= $file->native_name ?>"
                                 data-key="<?= $model->id ?>"
                                 style="background: url('<?= Url::base(true) . Yii::$app->storage->getImgPreview($file->name) ?>');">
                                <?= Html::a('<i class="glyphicon glyphicon-save"></i>', ['/task/download?id=' . $file->id], ['data-pjax' => 0]) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
            <div class="box-tools" style="background-color: <?= $model->status->color ?>">
                <div class="fc-header-left <?= $btnClass ?> <?php if ($model->user_id===Yii::$app->user->getId()) echo 'btn mod'?> status btn-xs text-black" data-key="<?= $model->id ?>">
                    <b> Status: <?= $model->status->text ?></b>
                </div>
            </div>
            <div class="box-footer box-comments <?= $mainBG ?>">
                <div class=" pull-left label btn-sm bg-gray-active <?= $btnClass ?> user" data-key="<?= $model->id?>">
                    Executor: <?= ($model->user) ? $model->user->username : 'not set' ?>
                </div>
                <div class="pull-right">
                    <?php
                    if (Yii::$app->user->can('admin')) {
                        echo Html::a('<i class="fa fa-cogs"></i>', ['update', 'id' => $model->id], [
                            'class' => 'btn btn-primary btn-flat margin-r-5 btn-xs',
                            'title' => 'update'
                        ]);
                    }
                    if (
                        (Yii::$app->user->can('admin')
                            && !$model->deletedAt
                        )
                        or
                        (
                            Yii::$app->user->can('manager')
                            && !$model->deletedAt
                            && $model->manager_id === Yii::$app->user->getId()
                            && $model->status->finally
                        )
                    ) {
                        echo Html::a('<i class="fa fa-trash"></i>', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger btn-flat margin-r-5 btn-xs',
                            'data' => [
                                'confirm' => 'Are you sure you want to delete this task?',
                                'method' => 'post',
                            ],
                            'title' => 'delete'
                        ]);
                    }
                    if (Yii::$app->user->can('admin') && $model->deletedAt) {
                        echo Html::a('<i class="fa fa-reply"></i>', ['recover', 'id' => $model->id], [
                            'class' => 'btn btn-info btn-flat margin-r-5 btn-xs',
                            'data' => [
                                'confirm' => 'Are you sure you want to recover this task?',
                                'method' => 'post',
                            ],
                            'title' => 'recover'
                        ]);
                    }
                    ?>
                    <small class="pull-right label bg-gray-active <?= Yii::$app->user->can('admin') ? $btnClass : '' ?> manager"
                           data-key="<?= $model->id ?>">
                        Manager: <?= $model->manager->username ?> </small>
                </div>
            </div>
        </div>

    </div>
</div>
<?= $this->render('../layouts/_modal-template', ['id' => 'modal-image','size'=>'md']) ?>
<?= $this->render('../layouts/_modal-template', ['id' => 'modal-change','size'=>'sm']) ?>
