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
    // 元画像を読み込む
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
    printf("(xoffset %d, yoffset %d)<br/>\n",$xoffset,$yoffset);

    // 1x1の枠画像
    $waku=imagecolorallocate($image,100,100,255);

    // ブロックを置くべき場所を取得しながら、ひとまず1x1の枠を描画
    $matrix=array();
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
	  $mx=($x-$xoffset)/$step;
	  $my=($y-$yoffset)/$step;
	  if (!isset($matrix[$mx])) $matrix[$mx]=array();
	  $matrix[$mx][$my]=1;
	}
      }
    }

    // m*nのブロックを置けるところに置いてみる...
    $border['2x4']=imagecolorallocate($image,255,80,80);
    $color['2x4']=imagecolorallocate($image,255,120,120);
    $matrix=putblock('2x4',$matrix);
    $border['4x2']=imagecolorallocate($image,40,200,40);
    $color['4x2']=imagecolorallocate($image,120,255,120);
    $matrix=putblock('4x2',$matrix);
    $border['2x2']=imagecolorallocate($image,255,80,255);
    $color['2x2']=imagecolorallocate($image,255,120,255);
    $matrix=putblock('2x2',$matrix);
    foreach ($matrix as $x => $line) {
      foreach ($line as $y => $dot) {
	if (preg_match('/([0-9]+)x([0-9]+)/',$dot,$r)) {
	  //printf("[%d,%d]=%s\n",$x,$y,$dot);
	  $x1=$x*$step+$xoffset;
	  $y1=$y*$step+$yoffset;
	  $x2=($x+$r[1])*$step+$xoffset-1;
	  $y2=($y+$r[2])*$step+$yoffset-1;
	  imagerectangle($image,$x1,$y1,$x2,$y2,$border[$dot]);
	  imagefilledrectangle($image,$x1+1,$y1+1,$x2-1,$y2-1,$color[$dot]);
	}
      }
    }

    // 枠を重ねた画像を表示
    $fn=sprintf("%s/blocksize%dxoffset%dyoffset%d.png",$path,$step,$xoffset,$yoffset);
    imagepng($image,$fn);
    printf("<div><img src=\"%s\"/></div>",str_replace(getcwd().'/','',$fn));
    return;
  }

  // 指定サイズのブロックを置ける場所に置く
  function putblock($size,$matrix) {
    if (preg_match('/([0-9]+)x([0-9]+)/',$size,$r)) {
      // 置きたいブロックのサイズ
      $dx=$r[1];
      $dy=$r[2];

      // X方向,Y方向のサイズ取得
      $xmax=0;
      $ymax=0;
      foreach ($matrix as $x => $line) {
	if ($x>$xmax) $xmax=$x;
	foreach ($line as $y => $dot) {
	  if ($y>$ymax) $ymax=$y;
	}
      }

      // ブロックを置ける場所を探す
      $bcount=0;
      for ($y=0;$y<=$ymax;$y++) {
	for ($x=0;$x<=$xmax;$x++) {
	  if (noblock($matrix,$x,$y)) {
	    // ブロックが置けるかどうか判定
	    $count=0;
	    for ($j=0;$j<$dy;$j++) {
	      for ($i=0;$i<$dx;$i++) {
		if (noblock($matrix,$x+$i,$y+$j)) $count++;
	      }
	    }
	    // ブロックを置けるなら置く
	    if ($count==$dx*$dy) {
	      //printf("[%d x %d]",$x,$y);
	      for ($j=0;$j<$dy;$j++) {
		for ($i=0;$i<$dx;$i++) {
		  $matrix[$x+$i][$y+$j]=0;
		}
	      }
	      $matrix[$x][$y]=$size;
	      $bcount++;
	    }
	  }
	}
      }

      printf("put %dx%d block: %d<br/>\n",$dx,$dy,$bcount);
    }


    return $matrix;
  }

  // ブロックが置ける場所かどうか返す
  function noblock($matrix,$x,$y) {
    return (isset($matrix[$x][$y]) && $matrix[$x][$y]==1);
  }

?>
<!-- 画像アップロード前のWebフォーム -->
<form method="post" enctype="multipart/form-data">
<table>
<tr><td>pngfile</td><td><input type=file name="pngfile" /></td></tr>
<tr><td></td><td><input type=submit value="Upload" /></td></tr>
</table>
</form>