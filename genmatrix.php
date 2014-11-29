<?php

  // アップロード済みの画像で再表示する場合
  if (isset($_GET['source'])) {
    $source=$_GET['source'];
    if (file_exists($source)) {
      $path=preg_replace('/\/[^\/]+$/','/',$source);
      $_POST=$_GET;
    } else {
      unset($source);
    }
  }
  // 新規にアップロードされた画像で表示する場合
  if (is_array($_FILES) && is_array($_FILES['pngfile'])) {
    // デバッグ用表示
    //print_r($_FILES);
    //echo '<br/>';

    // アップロードされた画像を取得
    $path=tempnam("tmp","genmat");
    unlink($path);
    mkdir($path);
    $source=$path.'/'.$_FILES['pngfile']['name'];
    //echo "$source\n";
    move_uploaded_file($_FILES['pngfile']['tmp_name'],$source);
    //$source=$_FILES['pngfile']['tmp_name'];
  }

  if (isset($source) && isset($path)) {
    // 元画像を読み込む
    $image=imagecreatefrompng($source);
    printf("filename %s<br/>\n",preg_replace('/^.*\//','',$source));

    // 画像サイズの取得と表示
    $width=imagesx($image);
    $height=imagesy($image);
    printf("imagesize %d x %d<br/>\n",$width,$height);

    // マス目のサイズ設定
    $step=10;
    if (isset($_POST['blocksize']) && preg_match('/[0-9]+/',$_POST['blocksize'],$r)) {
      $step=$r[0];
    }
    // X方向の開始位置オフセット設定
    $xoffset=0;
    if (isset($_POST['xoffset']) && preg_match('/[0-9]+/',$_POST['xoffset'],$r)) {
      $xoffset=$r[0];
    }
    // Y方向の開始位置オフセット設定
    $yoffset=0;
    if (isset($_POST['yoffset']) && preg_match('/[0-9]+/',$_POST['yoffset'],$r)) {
      $yoffset=$r[0];
    }

    // 各サイズのブロックを置く優先度を設定
    $blockorder=array('2x4','4x2','2x2','1x4','4x1','1x3','3x1','1x2','2x1');
    if (isset($_POST['order']) && preg_match('/[0-9]+x[0-9]+/',$_POST['order'])) {
      $blockorder=explode(',',$_POST['order']);
    }

    // マス目サイズ選択用セレクタ
    printf("<select name=\"blocksize\" onChange=\"location.href=this.options[this.selectedIndex].value;\">\n");
    for ($d=1;$d<min($width,$height);$d++) {
      $selected="";
      if ($d==$step) $selected=" selected";
      printf("<option value=\"?source=%s&blocksize=%d&xoffset=%d&yoffset=%d\"%s>%d x %d\n",str_replace(getcwd().'/','',$source),$d,$xoffset,$yoffset,$selected,$d,$d);
    }
    printf("</select> block<br/>\n");

    // X/Yオフセット選択用リンク
    $url=sprintf("?source=%s&blocksize=%d",str_replace(getcwd().'/','',$source),$step);
    $dx=$xoffset-1;
    if ($dx<0) $dx=$step-1;
    printf("[<a href=\"%s&xoffset=%d&yoffset=%d\">←</a>] ",$url,$dx,$yoffset);
    $dy=$yoffset-1;
    if ($dy<0) $dy=$step-1;
    printf("[<a href=\"%s&xoffset=%d&yoffset=%d\">↑</a>] ",$url,$xoffset,$dy);
    $dy=$yoffset+1;
    if ($dy>=$step) $dy-=$step;
    printf("[<a href=\"%s&xoffset=%d&yoffset=%d\">↓</a>] ",$url,$xoffset,$dy);
    $dx=$xoffset+1;
    if ($dx>=$step) $dx-=$step;
    printf("[<a href=\"%s&xoffset=%d&yoffset=%d\">→</a>] ",$url,$dx,$yoffset);
    printf("(xoffset %d, yoffset %d)<br/>\n",$xoffset,$yoffset);

    $url=sprintf("?source=%s&blocksize=%d&xoffset=%d&yoffset=%d",str_replace(getcwd().'/','',$source),$step,$xoffset,$yoffset);
    printf("<select size=\"%d\" onChange=\"location.href=this.options[this.selectedIndex].value;\">\n",count($blockorder));
    foreach ($blockorder as $block) {
	printf("<option value=\"%s\"/>%s block\n",$url.'&order='.raiseOrder($blockorder,$block),$block);
    }
    printf("</select>\n");

    printf("<div style=\"float:right;\"><img src=\"image.php?source=%s&blocksize=%d&xoffset=%d&yoffset=%d&order=%s\"/></div>",str_replace(getcwd().'/','',$source),$step,$xoffset,$yoffset,implode(',',$blockorder));
    return;
  }

  function raiseOrder($blockorder,$block) {
    $ret=array();
    foreach ($blockorder as $b) {
      if ($b==$block) {
	$c=array_pop($ret);
	$ret[]=$b;
	$ret[]=$c;
      } else {
	$ret[]=$b;
      }
    }
    return implode(',',$ret);
  }

?>
<!-- 画像アップロード前のWebフォーム -->
<form method="post" enctype="multipart/form-data">
<table>
<tr><td>pngfile</td><td><input type=file name="pngfile" /></td></tr>
<tr><td></td><td><input type=submit value="Upload" /></td></tr>
</table>
</form>