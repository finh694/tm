<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\TaskPriority */

$this->title = 'Create Task Priority';
$this->params['breadcrumbs'][] = ['label' => 'Task Priorities', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-priority-create">



    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
