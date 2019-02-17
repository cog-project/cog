<?php
$mes = trim(shell_exec("cd ".getcwd()."; git --no-pager pull github master 2>&1; echo $?"));
$mes = explode("\n",$mes);
$res = !end($mes);
array_pop($mes);
$mes = trim(implode("\n",$mes));
echo json_encode([
  'result' => $res,
  'message' => $mes
]);
?>