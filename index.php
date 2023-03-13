<?php
session_start();
require_once 'vendor/autoload.php';
require_once '../config/qqConfig.php';
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
    echo '<a href="'.$url.'">Connect Tumblr</a>';
    exit;
}
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
?>
<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="utf-8">
      <link rel="stylesheet" href="inc/archive.css" type="text/css">
    </head>
    <body>
      <div class="container">
<?php
for ($i=0; $i<count($postData); $i++) {
  echo '<div class="box">'.$postData[$i].'</div>';
}
echo'</div>
      <div>
        <a href="index.php?blogName='.$displayBlogName.'&offset='.$offset.'">Next Page</a><br>
        View somoeone elses blog: 
          <form action="index.php" method="POST">
            <input type="text" name="inputBlogName">
            <input type="submit" value="submit">
          </form>
        <br> <a href="index.php">Home</a>
        <br> <a href="logout.php">Logout</a>
      </div>
      ';
      ?>
</body>
</html>