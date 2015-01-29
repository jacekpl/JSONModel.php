<?php

require_once('test/TasksOO.php');

function test_insert ($test, $sql, $message) {
    $app = new Application($sql);
    $tasks = $app->tasks();
    $task = $tasks->insert($message);
    $saved = json_encode($task->map);
    $stored = $tasks->fetchById($task->getInt('task'));
    $intersect = new JSONMessage(@array_intersect_assoc($stored->map, $task->map));
    $test->is($intersect->uniform(), $task->uniform(), $saved);
}

function test_insert_free ($test, $sql, $message) {
    $tasks = new JSONModel($sql, Tasks::types(), array(
        'domain' => 'test_',
        'name' => 'task',
        'columns' => NULL,
        'primary' => 'task',
        'jsonColumn' => 'task_json'
    ));
    $task = $tasks->insert($message);
    $saved = json_encode($task->map);
    $stored = $tasks->fetchById($task->getInt('task'));
    $intersect = new JSONMessage(@array_intersect_assoc($stored->map, $task->map));
    $test->is($intersect->uniform(), $task->uniform(), $saved);
}

$t = new TestMore();

$t->plan(5);

$pdo = SQLAbstractPDO::openMySQL('wp', 'test', 'dummy');

$t->is(TRUE, TRUE, 'openMySQL did not fail');

$sql = new SQLAbstractPDO($pdo, 'wp_');

test_insert($t, $sql, new JSONMessage(array(
    'task_name' => 'in one hour',
    'task_created_at' => time(),
    'task_scheduled_for' => time()+3600,
    'extensions' => array(
        'list' => array(1,2,3)
        )
    )));

test_insert($t, $sql, new JSONMessage(array(
    'task_name' => 'in one hour',
    'task_created_at' => time(),
    'task_scheduled_for' => time()+3600
    )));

test_insert($t, $sql, new JSONMessage(array(
    'task_name' => 'in one hour',
    'task_scheduled_for' => time()+3600
    )));

test_insert_free($t, $sql, new JSONMessage(array(
    'task_name' => 'in two hour',
    'task_created_at' => time(),
    'task_scheduled_for' => time()+7200,
    'extensions' => array(
        'list' => array('A', 'B', 'C')
        )
    )));

