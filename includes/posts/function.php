<?php 

require '../dbconc.php';

function getPosts(){

    global $conn;

$query = "SELECT * FROM Posts";
$query_run = mysqli_query($conn,$query);

    if($query_run){

        if(mysqli_num_rows($query_run) > 0){

            $res = mysqli_fetch_all($query_run, MYSQLI_ASSOC);

            $data = [
                'status' => 200,
                'message' => 'Posts List Fetched Successfully',
                'data' => $res
            ];
            header("HTTP/1.0 200 OK");
            return json_encode($data);

        } else{
            $data = [
                'status' => 404,
                'message' => 'No Posts found',
            ];
            header("HTTP/1.0 404 No posts found");
            return json_encode($data);
        }
    }
    else{
        $data = [
        'status' => 500,
        'message' => 'Internal Server Error',
    ];
    header("HTTP/1.0 500 Internal Server Error");
    return json_encode($data);
    }
}