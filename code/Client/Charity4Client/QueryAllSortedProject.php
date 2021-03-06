<?php
/**
 * Created by PhpStorm.
 * User: Deep
 * Date: 15/8/31
 * Time: 下午3:33
 */


// 0 请求失败
// 1 数据库连接失败
// 2 没有数据
// 3 没有更多的数据用于刷新
// 4 下拉刷新，重新加载数据
// 5 上拉加载更多数据返回

$output = array();

$data = array();

$avatar = array();

if ($_SERVER["REQUEST_METHOD"] == "GET") {

    $times = (int)test_input($_GET["times"]);
    $queryType = (int)test_input($_GET["queryType"]);

    // 连接数据库
    $link = mysql_connect("localhost", "root", "");

    if(!$link)
    {
        $output = array('data'=>NULL, 'info'=> 1, 'code'=>-201);
        exit(json_encode($output));
    }


    $db = mysql_select_db("P2PCharity", $link);

    $sql = "SELECT * from Project WHERE projectStatus = 2 AND spendDay < 30 ORDER BY priority DESC";

    $result = mysql_query($sql, $link);

    if (mysql_num_rows($result) == 0) {

        // 没有数据

        $output = array('data'=>NULL, 'info'=> 2, 'code'=>-201);
        exit(json_encode($output));

    } else {

        // 下拉刷新，重新加载数据
        if ($queryType == 0) {
            for ($i = 0; $i < 10; $i++) {
                if ($row = mysql_fetch_array($result)) {

                    $sponsorId = $row['sponsorId'];
                    $sqlForAvatar = "SELECT avatar FROM User WHERE id = '$sponsorId'";
                    $avatarResult = mysql_query($sqlForAvatar, $link);
                    if (mysql_num_rows($avatarResult) == 0) {
                        continue;
                    } else {
                        $avatarRow = mysql_fetch_array($avatarResult);
                        $avatar[] = $avatarRow;
                    }


                    $data[] = $row;
                } else {
                    break;
                }
            }

            $output = array('data'=>$data, 'avatar' => $avatar, 'info'=> 4, 'code'=>200);
            exit(json_encode($output));

        } elseif($queryType == 1) { // 上拉加载更多数据

            // 没有更多的数据用于刷新
            if (mysql_num_rows($result) < $times * 10) {
                $output = array('data'=>NULL, 'info'=> 3, 'code'=>-201);
                exit(json_encode($output));
            } else {
                // 查询更多的数据并返回
                // 首先跳过指定的行数
                // 再加载10个或不够时少于10个数据
                for($i = 0; $i < (((int)$times + 1) * 10); $i++) {
                    if ($row = mysql_fetch_array($result)) {
                        if ($i >= ($times * 10)) {

                            $sponsorId = $row['sponsorId'];
                            $sqlForAvatar = "SELECT avatar FROM User WHERE id = '$sponsorId'";
                            $avatarResult = mysql_query($sqlForAvatar, $link);
                            if (mysql_num_rows($avatarResult) == 0) {
                                continue;
                            } else {
                                $avatarRow = mysql_fetch_array($avatarResult);
                                $avatar[] = $avatarRow;
                            }

                            $data[] = $row;
                        }
                    } else {
                        break;
                    }
                }

                $output = array('data'=>$data, 'avatar' => $avatar, 'info'=> 5, 'code'=>200);
                exit(json_encode($output));

            }

        }

    }


} else {
    $output = array('data'=>NULL, 'info'=> 0, 'code'=>-201);
    exit(json_encode($output));
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}