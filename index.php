<?php

include_once('db.php');

$conn = getConnect();
initDB($conn);
/*
$statement = $conn->query('SELECT * FROM `users`');
$users = array();
while ($row = $statement->fetch()) {
  $users[$row['id']] = $row['name'];
}
var_dump($users);

$statement = $conn->query('SELECT * FROM `user_accounts`');
$user_accounts = array();
while ($row = $statement->fetch()) {
  $user_accounts[$row['id']] = $row['user_id'];
}
var_dump($user_accounts);

$statement = $conn->query('SELECT * FROM `transactions`');
$transactions = array();
while ($row = $statement->fetch()) {
  $transactions[$row['id']] = $row['amount'];
}
var_dump($transactions);
*/


function getUsers() {
  $db = new PDO('mysql:host=sql11.freemysqlhosting.net;dbname=sql11696765', 'sql11696765', 'xScn3YUafY');
  $query = "SELECT `U`.`id`, `U`.`name` ".
           "FROM `users` AS `U` ".
           "INNER JOIN `user_accounts` AS `ACC` ".
             "ON `ACC`.`user_id` = `U`.`id` ".
           "INNER JOIN `transactions` AS `T` ".
             "ON `T`.`account_from` = `ACC`.`id` OR `T`.`account_to` = `ACC`.`id` ".
           "GROUP BY `U`.`id`";  
             
  $statement = $db->query($query);
  $users = array();
  while ($row = $statement->fetch()) {
    $users[$row['id']] = $row['name'];
  }
  return $users;
}

// Функция для получения данных о транзакциях
function getTransactions($user_id) {
  $db = new PDO('mysql:host=sql11.freemysqlhosting.net;dbname=sql11696765', 'sql11696765', 'xScn3YUafY');
 
  $query = "SELECT `ACC`.`id` FROM user_accounts AS `ACC` WHERE `ACC`.`user_id`=?";
  $statement = $db->prepare($query);
  $statement->execute([$user_id]);
  $accounts = array();
  while ($row = $statement->fetch()) {
    $accounts[] = $row['id'];
  } 

  $transactions = array();
  $query = "SELECT ".
          "   MONTH(trdate) AS `month`, ".
          "   COUNT(`T`.`id`) AS `trn_count`".
          "FROM transactions AS `T` ".                      
          "WHERE `T`.`account_from` IN ( ".implode(',', $accounts).") ".
            "OR `T`.`account_to` IN ( ".implode(',', $accounts).") ".
          "GROUP BY `month`";
  $statement = $db->prepare($query);
  $statement->execute();
  while ($row = $statement->fetch()) {
    $transactions[$row['month']] = [
      'total' => 0,
      'count' => (int)$row['trn_count'],
    ];
  }

  foreach ($accounts as $account_id) {
    $query = "SELECT ".
            "   SUM(IIF(`T`.`account_from` = ".$account_id.", -amount, amount)) AS `total`, ". 
            "   MONTH(trdate) AS `month`, ".
            "   COUNT(`T`.`id`) AS `trn_count`".
            "FROM transactions AS `T` ".                      
            "WHERE `T`.`account_from` = ".$account_id." ".
               "OR `T`.`account_to` = ".$account_id." ".
            "GROUP BY `month`";
    $statement = $db->prepare($query);
    $statement->execute();
    while ($row = $statement->fetch()) {
      $total = (float)($row['total']) ?? 0;
      #$count = (int)$row['trn_count'];
      $transactions[$row['month']]['total'] += $total;      
    }       
  }          

  return $transactions;
}

$month_names = [
  1 => 'January',
  2 => 'Februarry',
  3 => 'March'
]

?>


<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Информация о транзакциях пользователей</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Информация о транзакциях пользователя</h1>
  <form action="index.php" method="get">
    <label for="user">Выберите пользователя:</label>
    <select name="user" id="user">
      <?php
      $users = getUsers();
      foreach ($users as $id => $name) {
        echo "<option value=\"$id\">".$name."</option>";
      }
      ?>
    </select>
    <input type="submit" value="Показать">
  </form>

  <?php
  if (isset($_GET['user'])) {
    $user_id = $_GET['user'];

    // Получить данные о транзакциях
    $transactions = getTransactions($user_id);

    // Отобразить таблицу
    echo "<h2>Данные по ".$users[$user_id]."</h2>";
    echo "<table>";
    echo "<tr><th>Месяц</th><th>Сумма</th><th>Количество</th></tr>";
    foreach ($transactions as $month => $data) {
      echo "<tr><td>".$month_names[$month]."</td><td>".number_format($data['total'], 2)."</td><td>".$data['count']."</td><td></td></tr>";
    }
    echo "</table>";
  }
  ?>

  <script src="script.js"></script>
</body>
</html>
