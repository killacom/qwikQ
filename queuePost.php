<?php
session_start();
require_once 'vendor/autoload.php';
require_once '../config/qqConfig.php';
$client = new Tumblr\API\Client(
  $consumerKey,
  $consumerSecret,
  $_SESSION['Tumblr_oauth_token'],
  $_SESSION['Tumblr_oauth_token_secret']
);
$requestHandler = $client->getRequestHandler();
$requestHandler->setBaseUrl('https://api.tumblr.com');
$blogName = $_GET['blogName'];
$postId = intval($_GET['postId']);
$reblogKey = $_GET['reblogKey'];
$params['state'] = 'queue';
try {
    $client->reblogPost($blogName, $postId, $reblogKey, $params);
} catch (Exception $e) {
    echo $e->getMessage();
    echo '<br>';
    echo $blogName.'<br>';
    echo $postId.'<br>';
    echo $reblogKey.'<br>';
    print_r($params);
}
echo '<script>window.close()</script>';
?>
