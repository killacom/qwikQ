<?php
session_start();
require_once 'vendor/autoload.php';
require_once '../config/qqConfig.php';
//require_once 'qqConfig.php';
$header = '
<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="utf-8">
      <link rel="stylesheet" href="inc/archive.css" type="text/css">
    </head>
    <body>
      <div class="container">';
$body = '<h1>QwikQ.Site<br>Click on a post to add it to your tumblr queue!</h1><br>';
$tmpToken = isset($_SESSION['tmp_oauth_token'])? $_SESSION['tmp_oauth_token'] : null;
$tmpTokenSecret = isset($_SESSION['tmp_oauth_token_secret'])? $_SESSION['tmp_oauth_token_secret'] : null;
$client = new Tumblr\API\Client($consumerKey, $consumerSecret, $tmpToken, $tmpTokenSecret);
$requestHandler = $client->getRequestHandler();
$requestHandler->setBaseUrl('https://www.tumblr.com/');
if (!empty($_GET['oauth_verifier'])) {
    // exchange the verifier for the keys
    $verifier = trim($_GET['oauth_verifier']);
    $resp = $requestHandler->request('POST', 'oauth/access_token', array('oauth_verifier' => $verifier));
    $out = (string) $resp->body;
    $data = array();
    parse_str($out, $data);
    unset($_SESSION['tmp_oauth_token']);
    unset($_SESSION['tmp_oauth_token_secret']);
    $_SESSION['Tumblr_oauth_token'] = $data['oauth_token'];
    $_SESSION['Tumblr_oauth_token_secret'] = $data['oauth_token_secret'];
    $_SESSION['verifier'] = $verifier;
}
if (empty($_SESSION['Tumblr_oauth_token']) || empty($_SESSION['Tumblr_oauth_token_secret'])) {
    $callbackUrl = 'http://qwikq.site/';
    $resp = $requestHandler->request('POST', 'oauth/request_token', array(
            'oauth_callback' => $callbackUrl
        ));
    $result = (string) $resp->body;
    parse_str($result, $keys);
    $_SESSION['tmp_oauth_token'] = $keys['oauth_token'];
    $_SESSION['tmp_oauth_token_secret'] = $keys['oauth_token_secret'];
    $url = 'https://www.tumblr.com/oauth/authorize?oauth_token=' . $keys['oauth_token'];
    $body = '<div class="menu"><a href="'.$url.'">Connect Tumblr</a></div>';
} else {
$client = new Tumblr\API\Client(
    $consumerKey,
    $consumerSecret,
    $_SESSION['Tumblr_oauth_token'],
    $_SESSION['Tumblr_oauth_token_secret']
);
$info = $client->getUserInfo();
$blogName = $displayBlogName = $info->user->name;
if (isset($_POST['inputBlogName'])) {
  $displayBlogName = $_POST['inputBlogName'];
} elseif (isset($_GET['blogName'])) {
  $displayBlogName = $_GET['blogName'];
}
$postData=[];
$limit = 20;
if(!isset($_GET['offset'])) {
  $offset = 0;
} else {
  $offset = intval($_GET['offset']);
}
$parameters['limit'] = $limit;
$max = 40;
for ($h=0; $h<$max; ($h=$h+$limit)) {
  $parameters['offset'] = $offset;
  $publishedPosts = $client->getBlogPosts($displayBlogName, $parameters, $offset);
  $publishedPosts = $publishedPosts->posts;
  $postCount = count($publishedPosts);
  for($i=0; $i<$postCount; $i++) {
    $images = '';
    $postId = $publishedPosts[$i]->id;
    $postType = $publishedPosts[$i]->type;
    $postReblogKey = $publishedPosts[$i]->reblog_key;
    if ($postType == 'photo') {
      $photoCount = count($publishedPosts[$i]->photos);
      $photos = '';
      for($j=0; $j<1; $j++){
        $currentPhoto = [];
        $currentPhoto['url'] = $publishedPosts[$i]->photos[$j]->original_size->url;
        $currentPhoto['w'] = $publishedPosts[$i]->photos[$j]->original_size->width;
        $currentPhoto['h'] = $publishedPosts[$i]->photos[$j]->original_size->height;
        $photo = '<img src="'.$currentPhoto['url'].'">';
        $photos.= $photo;
      }
      $postContent = $photos;
    } elseif($postType == 'text') { 
      $post = $publishedPosts[$i]->trail[0]->content;
      $firstImgSrcStart = strpos($post, '<img src=');
      $firstImgSrcEnd = strpos($post, 'alt="', $firstImgSrcStart);
      $firstImgSrc = substr($post, $firstImgSrcStart, ($firstImgSrcEnd-$firstImgSrcStart));
      if (!$firstImgSrc) {
        $postContent = '<div class="textBox">'.$publishedPosts[$i]->trail[0]->content.'</div>';
      } else {
        $postContent = $firstImgSrc.'>';
      }
    } elseif($postType == 'answer') {
      $questionText = '<div class="textBox">'.$publishedPosts[$i]->question.'</div>';
      $postContent = $questionText;
    }
    $postBox = '<a href="queuePost.php?blogName='.$blogName.'&postId='.$postId.'&reblogKey='.$postReblogKey.'" target="_BLANK">'.$postContent.'</a>';
    array_push($postData, $postBox); 
  }
  $offset+=$limit;
}

for ($i=0; $i<count($postData); $i++) {
  $body.= '<div class="box">'.$postData[$i].'</div>';
}
$body.='</div>
      <div class="menu">
        <div class="menuItem">
          <form action="index.php" method="GET">
            <input type="hidden" name="blogName" value="'.$displayBlogName.'">
            <input type="hidden" name="offset" value="'.$offset.'">
            <input class="button" type="submit" value="Next Page">
          </form><br>
        </div>
        <div class="menuItem">
          <h2>View someone elses blog:</h2>
            <form action="index.php" method="POST">
              <input type="text" name="inputBlogName">.tumblr.com<br>
              <input class="smButton" type="submit" value="Go!">
            </form>
        </div>
        <div class="menuItem">
          <br> <a class="button" href="index.php">Home</a>
        </div>
        <div class="menuItem">
          <br> <a class="button" href="logout.php">Logout</a>
        </div>
      </div>
      ';
        }
echo $header.$body;
      ?>
</body>
</html>