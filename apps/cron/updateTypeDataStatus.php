<?php

$res = $mysqli->query("select count(*) as total from typedata where tid='$tid' and status!=1");
$total = $res->fetch_assoc()['total'];

$limit = 1000;
$times = ceil($total / $limit);

$j = 0;
$id = 0;
for ($i = 0; $i < $times; $i++) {

    $sql = "select id, orderid from typedata where id>$id and tid='$tid' and status!=1 order by id asc limit $limit";
    echo $sql."\n";
    $res = $mysqli->query($sql);
    while($row = $res->fetch_assoc()) {
        $j++;

        echo $j."\n";
        $redis->lPush('tid_orderid_'.$tid, $row['orderid']);
        $id = $row['id'];
    }

    $mysqli->query("update typedata set status=1 where tid='$tid' and status!=1 limit $limit");
}

$redis->close();
$mysqli->close();
