<?php
  if (isset($_GET['source'])) {
    $source=$_GET['source'];
    if (file_exists($source)) {
      $path=preg_replace('/\/[^\/]+$/','/',$source);
      $_POST=$_GET;
    } else {
      unset($source);
    }
  }
  if (is_array($_FILES) && is_array($_FILES['pngfile'])) {
    // デバッグ用表示
    //print_r($_FILES);
    //echo '<br/>';

    // アップロードされた画像を取得
    $path=tempnam("tmp","genmat");
    unlink($path);
    mkdir($path);
    $source=$path.'/'.$_FILES['pngfile']['name'];
    echo "$source\n";
    move_uploaded_file($_FILES['pngfile']['tmp_name'],$source);
    //$source=$_FILES['pngfile']['tmp_name'];
  }

  if (isset($source) && isset($path)) {
    $image=imagecreatefrompng($source);
    // 画像サイズの取得と表示
    $width=imagesx($image);
    $height=imagesy($image);
    printf("image size %d x %d<br/>\n",$width,$height);

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
    printf("(xoffset %d, yoffset %d)\n",$xoffset,$yoffset);

    // 1x1の枠画像
    $waku=imagecolorallocate($image,100,100,255);
    for ($y=$yoffset;$y<$height;$y+=$step) {
      for ($x=$xoffset;$x<$width;$x+=$step) {
	$wcount=0;
	$ocount=0;
	for ($j=0;$j<$step;$j++) {
	  for ($i=0;$i<$step;$i++) {
	    $col=imagecolorat($image,$x+$i,$y+$j);
	    if ($col==0xffffff) {
	      $wcount++; // 白いピクセルを数える
	    } else {
	      $ocount++; // 白以外のピクセルを数える
	    }
	  }
	}
	if ($ocount>=$wcount) { // 白の方が少なければ枠画像表示
	  imagerectangle($image,$x,$y,$x+$step-1,$y+$step-1,$waku);
	}
      }
    }

    // 枠を重ねた画像を表示
    $fn=sprintf("%s/blocksize%dxoffset%dyoffset%d.png",$path,$step,$xoffset,$yoffset);
    imagepng($image,$fn);
    printf("<div><img src=\"%s\"/></div>",str_replace(getcwd().'/','',$fn));
    return;
  }
?>
<!-- 画像アップロード前のWebフォーム -->
<form method="post" enctype="multipart/form-data">
<table>
<tr><td>pngfile</td><td><input type=file name="pngfile" /></td></tr>
<tr><td></td><td><input type=submit value="Upload" /></td></tr>
</table>
</form>